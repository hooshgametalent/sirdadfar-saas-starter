<?php

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;
use App\Policies\Concerns\DeniesInactiveUsers;

/**
 * Only admins manage this resource.
 */
class SettingPolicy
{
    use DeniesInactiveUsers;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Setting $setting): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Setting $setting): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Setting $setting): bool
    {
        return $user->isAdmin();
    }
}
