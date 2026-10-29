<?php

namespace App\Policies;

use App\Models\Page;
use App\Models\User;
use App\Policies\Concerns\DeniesInactiveUsers;

/**
 * Content is managed by content editors and admins only.
 */
class PagePolicy
{
    use DeniesInactiveUsers;

    public function viewAny(User $user): bool
    {
        return $user->canManageContent();
    }

    public function view(User $user, Page $page): bool
    {
        return $user->canManageContent();
    }

    public function create(User $user): bool
    {
        return $user->canManageContent();
    }

    public function update(User $user, Page $page): bool
    {
        return $user->canManageContent();
    }

    public function delete(User $user, Page $page): bool
    {
        return $user->canManageContent();
    }
}
