<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;
use App\Policies\Concerns\DeniesInactiveUsers;

/**
 * Audit logs are read only and visible to admins.
 */
class AuditLogPolicy
{
    use DeniesInactiveUsers;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AuditLog $auditLog): bool
    {
        return false;
    }

    public function delete(User $user, AuditLog $auditLog): bool
    {
        return false;
    }
}
