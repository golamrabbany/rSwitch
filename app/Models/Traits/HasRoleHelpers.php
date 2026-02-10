<?php

namespace App\Models\Traits;

/**
 * Trait for role checking helper methods.
 * Extracts role logic from User model to reduce bloat.
 */
trait HasRoleHelpers
{
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Check if user has admin-level access (super_admin or admin).
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['super_admin', 'admin']);
    }

    /**
     * Check if user is a regular admin (not super admin).
     */
    public function isRegularAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isRechargeAdmin(): bool
    {
        return $this->role === 'recharge_admin';
    }

    /**
     * Check if user has any admin role.
     */
    public function isAnyAdmin(): bool
    {
        return in_array($this->role, ['super_admin', 'admin', 'recharge_admin']);
    }

    public function isReseller(): bool
    {
        return $this->role === 'reseller';
    }

    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    public function isPrepaid(): bool
    {
        return $this->billing_type === 'prepaid';
    }

    public function isPostpaid(): bool
    {
        return $this->billing_type === 'postpaid';
    }

    /**
     * Get display name for the role.
     */
    public function getRoleDisplayName(): string
    {
        return match ($this->role) {
            'super_admin' => 'Super Admin',
            'admin' => 'Regular Admin',
            'recharge_admin' => 'Recharge Admin',
            'reseller' => 'Reseller',
            'client' => 'Client',
            default => ucfirst($this->role),
        };
    }

    /**
     * Get role badge CSS class.
     */
    public function getRoleBadgeClass(): string
    {
        return match ($this->role) {
            'super_admin' => 'badge-purple',
            'admin' => 'badge-info',
            'recharge_admin' => 'badge-amber',
            'reseller' => 'badge-blue',
            'client' => 'badge-sky',
            default => 'badge-gray',
        };
    }
}
