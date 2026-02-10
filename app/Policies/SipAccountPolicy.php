<?php

namespace App\Policies;

use App\Models\SipAccount;
use App\Models\User;

/**
 * Policy for SipAccount model authorization.
 */
class SipAccountPolicy
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
     * Determine if the user can view any SIP accounts.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view their scoped SIP accounts
    }

    /**
     * Determine if the user can view the SIP account.
     */
    public function view(User $user, SipAccount $sipAccount): bool
    {
        return $this->isInScope($user, $sipAccount);
    }

    /**
     * Determine if the user can create SIP accounts.
     */
    public function create(User $user): bool
    {
        return $user->isAnyAdmin() || $user->isReseller();
    }

    /**
     * Determine if the user can update the SIP account.
     */
    public function update(User $user, SipAccount $sipAccount): bool
    {
        if ($user->isRechargeAdmin()) {
            return false; // Recharge admin is view-only
        }

        if ($user->isClient()) {
            // Clients can only update limited fields (password, caller_id)
            return $sipAccount->user_id === $user->id;
        }

        return $this->isInScope($user, $sipAccount);
    }

    /**
     * Determine if the user can delete the SIP account.
     */
    public function delete(User $user, SipAccount $sipAccount): bool
    {
        if ($user->isRechargeAdmin() || $user->isClient()) {
            return false;
        }

        return $this->isInScope($user, $sipAccount);
    }

    /**
     * Determine if the user can reprovision the SIP account.
     */
    public function reprovision(User $user, SipAccount $sipAccount): bool
    {
        if ($user->isRechargeAdmin() || $user->isClient()) {
            return false;
        }

        return $this->isInScope($user, $sipAccount);
    }

    /**
     * Check if the SIP account is within the user's scope.
     */
    protected function isInScope(User $user, SipAccount $sipAccount): bool
    {
        $userIds = $user->descendantIds();

        return in_array($sipAccount->user_id, $userIds);
    }
}
