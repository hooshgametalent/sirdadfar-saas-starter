<?php

namespace App\Policies;

use App\Models\City;
use App\Models\User;
use App\Policies\Concerns\DeniesInactiveUsers;

/**
 * Only admins manage this resource.
 */
class CityPolicy
{
    use DeniesInactiveUsers;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, City $city): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, City $city): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, City $city): bool
    {
        return $user->isAdmin();
    }
}
