<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VehicleType;
use App\Policies\Concerns\DeniesInactiveUsers;

/**
 * Only admins manage this resource.
 */
class VehicleTypePolicy
{
    use DeniesInactiveUsers;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, VehicleType $vehicleType): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, VehicleType $vehicleType): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, VehicleType $vehicleType): bool
    {
        return $user->isAdmin();
    }
}
