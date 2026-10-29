<?php

namespace App\Policies;

use App\Models\Faq;
use App\Models\User;
use App\Policies\Concerns\DeniesInactiveUsers;

/**
 * Content is managed by content editors and admins only.
 */
class FaqPolicy
{
    use DeniesInactiveUsers;

    public function viewAny(User $user): bool
    {
        return $user->canManageContent();
    }

    public function view(User $user, Faq $faq): bool
    {
        return $user->canManageContent();
    }

    public function create(User $user): bool
    {
        return $user->canManageContent();
    }

    public function update(User $user, Faq $faq): bool
    {
        return $user->canManageContent();
    }

    public function delete(User $user, Faq $faq): bool
    {
        return $user->canManageContent();
    }
}
