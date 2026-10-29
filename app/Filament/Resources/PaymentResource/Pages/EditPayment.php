<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Services\AuditLogger;
use Filament\Resources\Pages\EditRecord;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

    protected function afterSave(): void
    {
        $changes = array_diff_key($this->record->getChanges(), array_flip(['updated_at']));

        if ($changes === []) {
            return;
        }

        app(AuditLogger::class)->log('payment.updated', $this->record, [
            'changes' => $changes,
            'previous' => array_intersect_key($this->record->getPrevious(), $changes),
        ]);
    }
}
