<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

/**
 * Policy for Transaction model authorization.
 */
class TransactionPolicy
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
     * Determine if the user can view any transactions.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view their scoped transactions
    }

    /**
     * Determine if the user can view the transaction.
     */
    public function view(User $user, Transaction $transaction): bool
    {
        return $this->isInScope($user, $transaction);
    }

    /**
     * Determine if the user can create transactions (balance operations).
     */
    public function create(User $user): bool
    {
        return $user->isAnyAdmin() || $user->isReseller();
    }

    /**
     * Check if the transaction is within the user's scope.
     */
    protected function isInScope(User $user, Transaction $transaction): bool
    {
        $userIds = $user->descendantIds();

        return in_array($transaction->user_id, $userIds);
    }
}
