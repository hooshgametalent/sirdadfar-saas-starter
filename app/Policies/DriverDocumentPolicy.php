<?php

namespace App\Policies;

use App\Enums\ReviewStatus;
use App\Models\DriverDocument;
use App\Models\User;
use App\Policies\Concerns\DeniesInactiveUsers;

class DriverDocumentPolicy
{
    use DeniesInactiveUsers;

    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, DriverDocument $document): bool
    {
        return $user->isStaff() || $this->owns($user, $document);
    }

    public function create(User $user): bool
    {
        return $user->isDriver() && $user->driverProfile()->exists();
    }

    public function update(User $user, DriverDocument $document): bool
    {
        return $user->isStaff();
    }

    public function delete(User $user, DriverDocument $document): bool
    {
        return $user->isAdmin()
            || ($this->owns($user, $document) && $document->status === ReviewStatus::Pending);
    }

    protected function owns(User $user, DriverDocument $document): bool
    {
        return (int) $document->driverProfile?->user_id === (int) $user->getKey();
    }
}
