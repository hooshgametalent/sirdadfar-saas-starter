<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait DeniesInactiveUsers
{
    /**
     * Deactivated accounts may not perform any action.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->is_active ? null : false;
    }
}
