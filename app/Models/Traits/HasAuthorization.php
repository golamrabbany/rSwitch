<?php

namespace App\Models\Traits;

use App\Models\User;

/**
 * Trait for authorization helper methods.
 */
trait HasAuthorization
{
    /**
     * Check if this user can manage the given user.
     */
    public function canManage(User $target): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->isRegularAdmin()) {
            return $this->canAdminManage($target);
        }

        if ($this->isReseller() && $target->parent_id === $this->id) {
            return true;
        }

        return $this->id === $target->id;
    }

    /**
     * Check if regular admin can manage target user.
     */
    protected function canAdminManage(User $target): bool
    {
        // Cannot manage super admins or other admins
        if ($target->isSuperAdmin() || $target->isRegularAdmin() || $target->isRechargeAdmin()) {
            return false;
        }

        $assignedResellerIds = $this->getCachedAssignedResellerIds();

        // Can manage assigned resellers
        if ($target->isReseller()) {
            return in_array($target->id, $assignedResellerIds);
        }

        // Can manage clients under assigned resellers
        if ($target->isClient()) {
            return in_array($target->parent_id, $assignedResellerIds);
        }

        return false;
    }

    /**
     * Check if this user can perform balance recharge/adjustment on the target user.
     */
    public function canRechargeBalance(User $target): bool
    {
        if ($this->isSuperAdmin() || $this->isRegularAdmin()) {
            return $this->canManage($target);
        }

        if ($this->isRechargeAdmin()) {
            $assignedResellerIds = $this->getCachedAssignedResellerIds();

            if ($target->isReseller()) {
                return in_array($target->id, $assignedResellerIds);
            }

            if ($target->isClient()) {
                return in_array($target->parent_id, $assignedResellerIds);
            }
        }

        return false;
    }

    /**
     * Check if this user can view the given user.
     */
    public function canView(User $target): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->isRegularAdmin() || $this->isRechargeAdmin()) {
            $assignedResellerIds = $this->getCachedAssignedResellerIds();

            if ($target->isReseller()) {
                return in_array($target->id, $assignedResellerIds);
            }

            if ($target->isClient()) {
                return in_array($target->parent_id, $assignedResellerIds);
            }

            return false;
        }

        if ($this->isReseller()) {
            return $target->id === $this->id || $target->parent_id === $this->id;
        }

        return $this->id === $target->id;
    }

    /**
     * Check if this user can create users of the given role.
     */
    public function canCreateRole(string $role): bool
    {
        return match ($this->role) {
            'super_admin' => true,
            'admin' => in_array($role, ['reseller', 'client']),
            'reseller' => $role === 'client',
            default => false,
        };
    }

    /**
     * Check if this user can delete the given user.
     */
    public function canDelete(User $target): bool
    {
        // Cannot delete self
        if ($this->id === $target->id) {
            return false;
        }

        // Super admin can delete anyone except themselves
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Regular admin can delete managed resellers/clients
        if ($this->isRegularAdmin()) {
            return $this->canAdminManage($target);
        }

        // Resellers can delete their own clients
        if ($this->isReseller() && $target->isClient()) {
            return $target->parent_id === $this->id;
        }

        return false;
    }
}
