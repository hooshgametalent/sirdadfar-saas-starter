<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Services\AuditLogger;
use App\Services\OfferService;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Admins can never change their own role or deactivate themselves, even
     * if the disabled fields are tampered with in the browser.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (UserResource::isCurrentUser($this->record)) {
            unset($data['role'], $data['is_active']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $changes = $this->record->getChanges();
        $previous = $this->record->getPrevious();
        $audit = app(AuditLogger::class);

        if (array_key_exists('role', $changes)) {
            $audit->log('user.role_changed', $this->record, [
                'from' => $previous['role'] ?? null,
                'to' => $changes['role'],
            ]);
        }

        if (array_key_exists('is_active', $changes)) {
            $audit->log($this->record->is_active ? 'user.activated' : 'user.deactivated', $this->record);
        }

        // A deactivated user, or one who is no longer a driver, cannot be
        // assigned any more, so their pending offers are withdrawn.
        if ((array_key_exists('is_active', $changes) || array_key_exists('role', $changes))
            && (! $this->record->is_active || ! $this->record->isDriver())) {
            app(OfferService::class)->withdrawAllPendingFor(
                $this->record,
                reason: $this->record->is_active ? 'role_changed' : 'user_deactivated',
            );
        }

        if (array_key_exists('password', $changes)) {
            $audit->log('user.password_reset', $this->record);
        }
    }
}
