<?php

namespace App\Policies;

use App\Models\DriverProfile;
use App\Models\User;
use App\Policies\Concerns\DeniesInactiveUsers;

class DriverProfilePolicy
{
    use DeniesInactiveUsers;

    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, DriverProfile $profile): bool
    {
        return $user->isStaff() || (int) $profile->user_id === (int) $user->getKey();
    }

    public function create(User $user): bool
    {
        return $user->isDriver() && $user->driverProfile()->doesntExist();
    }

    public function update(User $user, DriverProfile $profile): bool
    {
        return $user->isStaff() || (int) $profile->user_id === (int) $user->getKey();
    }

    public function verify(User $user, DriverProfile $profile): bool
    {
        return $user->isStaff();
    }

    public function delete(User $user, DriverProfile $profile): bool
    {
        return $user->isAdmin();
    }
}
