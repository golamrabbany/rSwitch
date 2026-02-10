<?php

namespace App\Policies;

use App\Models\Did;
use App\Models\User;

/**
 * Policy for DID model authorization.
 */
class DidPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Determine if the user can view any DIDs.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view their scoped DIDs
    }

    /**
     * Determine if the user can view the DID.
     */
    public function view(User $user, Did $did): bool
    {
        return $this->isInScope($user, $did);
    }

    /**
     * Determine if the user can create DIDs.
     * Only admins can create DIDs (resellers/clients can only view assigned DIDs).
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if the user can update the DID.
     */
    public function update(User $user, Did $did): bool
    {
        if ($user->isRechargeAdmin() || $user->isClient()) {
            return false;
        }

        // Resellers can update destination of DIDs assigned to them/their clients
        if ($user->isReseller()) {
            return $this->isInScope($user, $did);
        }

        return $user->isRegularAdmin() && $this->isInScope($user, $did);
    }

    /**
     * Determine if the user can delete the DID.
     */
    public function delete(User $user, Did $did): bool
    {
        // Only super admins can delete DIDs (handled in before())
        return false;
    }

    /**
     * Check if the DID is within the user's scope.
     */
    protected function isInScope(User $user, Did $did): bool
    {
        // Unassigned DIDs are only visible to admins
        if (!$did->assigned_to_user_id) {
            return $user->isAdmin();
        }

        $userIds = $user->descendantIds();

        return in_array($did->assigned_to_user_id, $userIds);
    }
}
