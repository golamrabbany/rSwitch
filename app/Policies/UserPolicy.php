<?php

namespace App\Policies;

use App\Models\User;

/**
 * Policy for User model authorization.
 * Centralizes authorization logic for user management across all controllers.
 */
class UserPolicy
{
    /**
     * Perform pre-authorization checks.
     * Super admins can do anything except delete themselves.
     */
    public function before(User $user, string $ability): ?bool
    {
        // Super admin bypass (except for specific checks we handle in methods)
        if ($user->isSuperAdmin() && !in_array($ability, ['delete', 'rechargeBalance'])) {
            return true;
        }

        return null;
    }

    /**
     * Determine if the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAnyAdmin() || $user->isReseller();
    }

    /**
     * Determine if the user can view the model.
     */
    public function view(User $user, User $target): bool
    {
        return $user->canView($target);
    }

    /**
     * Determine if the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isRegularAdmin() || $user->isReseller();
    }

    /**
     * Determine if the user can create a user with the specified role.
     */
    public function createRole(User $user, string $role): bool
    {
        return $user->canCreateRole($role);
    }

    /**
     * Determine if the user can update the model.
     */
    public function update(User $user, User $target): bool
    {
        return $user->canManage($target);
    }

    /**
     * Determine if the user can delete the model.
     */
    public function delete(User $user, User $target): bool
    {
        return $user->canDelete($target);
    }

    /**
     * Determine if the user can perform balance operations on the target.
     */
    public function rechargeBalance(User $user, User $target): bool
    {
        return $user->canRechargeBalance($target);
    }

    /**
     * Determine if the user can toggle the target's status.
     */
    public function toggleStatus(User $user, User $target): bool
    {
        // Can't toggle own status
        if ($user->id === $target->id) {
            return false;
        }

        return $user->canManage($target);
    }

    /**
     * Determine if the user can manage admin assignments.
     */
    public function manageAdmins(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can view audit logs.
     */
    public function viewAuditLogs(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isRegularAdmin();
    }
}
