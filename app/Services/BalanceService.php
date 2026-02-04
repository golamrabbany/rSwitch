<?php

namespace App\Services;

use App\Exceptions\Billing\InsufficientBalanceException;
use App\Models\CallRecord;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BalanceService
{
    /**
     * Debit a user's balance atomically with row-level locking.
     *
     * @throws InsufficientBalanceException
     */
    public function debit(
        User $user,
        string $amount,
        string $type,
        ?string $referenceType = null,
        ?int $referenceId = null,
        string $description = '',
        ?int $createdBy = null,
    ): Transaction {
        if (bccomp($amount, '0', 4) <= 0) {
            throw new \InvalidArgumentException('Debit amount must be positive');
        }

        return DB::transaction(function () use ($user, $amount, $type, $referenceType, $referenceId, $description, $createdBy) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            if (!$lockedUser) {
                throw new \RuntimeException("User {$user->id} not found during balance debit");
            }

            if ($lockedUser->isPrepaid()) {
                $available = bcadd((string) $lockedUser->balance, (string) $lockedUser->credit_limit, 4);
                if (bccomp($available, $amount, 4) < 0) {
                    throw new InsufficientBalanceException(
                        $lockedUser->id,
                        $amount,
                        $available,
                    );
                }
            }

            $newBalance = bcsub((string) $lockedUser->balance, $amount, 4);

            $lockedUser->update(['balance' => $newBalance]);
            $user->balance = $newBalance;

            return Transaction::create([
                'user_id' => $lockedUser->id,
                'type' => $type,
                'amount' => '-' . $amount,
                'balance_after' => $newBalance,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'created_by' => $createdBy,
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Credit a user's balance atomically.
     */
    public function credit(
        User $user,
        string $amount,
        string $type,
        ?string $referenceType = null,
        ?int $referenceId = null,
        string $description = '',
        ?int $createdBy = null,
    ): Transaction {
        if (bccomp($amount, '0', 4) <= 0) {
            throw new \InvalidArgumentException('Credit amount must be positive');
        }

        return DB::transaction(function () use ($user, $amount, $type, $referenceType, $referenceId, $description, $createdBy) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            if (!$lockedUser) {
                throw new \RuntimeException("User {$user->id} not found during balance credit");
            }

            $newBalance = bcadd((string) $lockedUser->balance, $amount, 4);

            $lockedUser->update(['balance' => $newBalance]);
            $user->balance = $newBalance;

            return Transaction::create([
                'user_id' => $lockedUser->id,
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'created_by' => $createdBy,
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Check if a user can afford a call.
     */
    public function canAffordCall(User $user, string $estimatedCost = '0'): bool
    {
        $available = bcadd((string) $user->balance, (string) $user->credit_limit, 4);

        if ($user->isPrepaid()) {
            $required = bcadd((string) $user->min_balance_for_calls, $estimatedCost, 4);
            return bccomp($available, $required, 4) >= 0;
        }

        // Postpaid: balance can go negative but not below -credit_limit
        $floor = bcmul((string) $user->credit_limit, '-1', 4);
        $afterCharge = bcsub((string) $user->balance, $estimatedCost, 4);
        return bccomp($afterCharge, $floor, 4) >= 0;
    }

    /**
     * Charge a user for a completed, rated call.
     *
     * @throws InsufficientBalanceException
     */
    public function chargeCall(User $user, CallRecord $callRecord): Transaction
    {
        $amount = (string) $callRecord->total_cost;

        if (bccomp($amount, '0', 4) <= 0) {
            return DB::transaction(function () use ($user, $callRecord) {
                $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

                return Transaction::create([
                    'user_id' => $lockedUser->id,
                    'type' => 'call_charge',
                    'amount' => '0.0000',
                    'balance_after' => (string) $lockedUser->balance,
                    'reference_type' => 'call_record',
                    'reference_id' => $callRecord->id,
                    'description' => "Call to {$callRecord->callee} ({$callRecord->billable_duration}s) - zero cost",
                    'created_by' => null,
                    'created_at' => now(),
                ]);
            });
        }

        return $this->debit(
            user: $user,
            amount: $amount,
            type: 'call_charge',
            referenceType: 'call_record',
            referenceId: $callRecord->id,
            description: "Call to {$callRecord->callee} ({$callRecord->billable_duration}s @ {$callRecord->rate_per_minute}/min)",
        );
    }

    /**
     * Get a user's effective available balance for calls.
     */
    public function getAvailableBalance(User $user): string
    {
        return bcadd((string) $user->balance, (string) $user->credit_limit, 4);
    }
}
