<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use App\Policies\Concerns\DeniesInactiveUsers;

/**
 * Content is managed by content editors and admins only.
 */
class CategoryPolicy
{
    use DeniesInactiveUsers;

    public function viewAny(User $user): bool
    {
        return $user->canManageContent();
    }

    public function view(User $user, Category $category): bool
    {
        return $user->canManageContent();
    }

    public function create(User $user): bool
    {
        return $user->canManageContent();
    }

    public function update(User $user, Category $category): bool
    {
        return $user->canManageContent();
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->canManageContent();
    }
}
