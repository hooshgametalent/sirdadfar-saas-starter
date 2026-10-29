<?php

namespace App\Policies;

use App\Models\Province;
use App\Models\User;
use App\Policies\Concerns\DeniesInactiveUsers;

/**
 * Only admins manage this resource.
 */
class ProvincePolicy
{
    use DeniesInactiveUsers;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Province $province): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Province $province): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Province $province): bool
    {
        return $user->isAdmin();
    }
}
