<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentCreditService
{
    public function __construct(
        private BalanceService $balanceService,
    ) {}

    /**
     * Atomically credit the user (and reseller parent if applicable) for a completed payment.
     * Returns true if credited, false if already processed.
     */
    public function creditPayment(Payment $payment, array $gatewayResponse = []): bool
    {
        if ($payment->status === 'completed') {
            return false; // Idempotency
        }

        $user = User::find($payment->user_id);
        if (!$user) {
            Log::error('PaymentCreditService: user not found', ['payment_id' => $payment->id, 'user_id' => $payment->user_id]);
            return false;
        }

        $gateway = str_replace('online_', '', $payment->payment_method);

        DB::transaction(function () use ($user, $payment, $gateway, $gatewayResponse) {
            $amount = (string) $payment->amount;

            // Step 1: Credit the user
            $userTxn = $this->balanceService->credit(
                user: $user,
                amount: $amount,
                type: 'topup',
                referenceType: 'payment',
                referenceId: $payment->id,
                description: ucfirst($gateway) . " top-up: {$amount}",
            );

            // Step 2: Credit the reseller parent (if user is a client under a reseller)
            $resellerTxn = null;
            if ($user->parent_id && $user->isClient()) {
                $parent = User::find($user->parent_id);
                if ($parent && $parent->isReseller()) {
                    $resellerTxn = $this->balanceService->credit(
                        user: $parent,
                        amount: $amount,
                        type: 'client_payment',
                        referenceType: 'payment',
                        referenceId: $payment->id,
                        description: "Client payment ({$user->name}): {$amount}",
                    );
                }
            }

            // Update payment record
            $payment->update([
                'status' => 'completed',
                'completed_at' => now(),
                'transaction_id' => $userTxn->id,
                'reseller_transaction_id' => $resellerTxn?->id,
                'gateway_response' => !empty($gatewayResponse) ? $gatewayResponse : $payment->gateway_response,
            ]);

            AuditService::logAction("payment.{$gateway}.completed", $payment, [
                'user_id' => $user->id,
                'amount' => $payment->amount,
                'reseller_credited' => $resellerTxn ? true : false,
            ]);
        });

        Log::info("Payment completed via {$gateway}", [
            'payment_id' => $payment->id,
            'user_id' => $user->id,
            'amount' => $payment->amount,
        ]);

        return true;
    }

    /**
     * Record a non-monetary transaction when a payment ends in failed status
     * (gateway error, validation error, amount mismatch, etc).
     */
    public function logFailedAttempt(Payment $payment, string $description): void
    {
        $this->logTerminalAttempt($payment, 'payment_failed', $description);
    }

    /**
     * Record a non-monetary transaction when the user cancelled the payment
     * (chose to abandon at the gateway).
     */
    public function logCancelledAttempt(Payment $payment, string $description): void
    {
        $this->logTerminalAttempt($payment, 'payment_cancelled', $description);
    }

    /**
     * Idempotent insert into transactions for a non-monetary payment outcome.
     * Skips if a transaction already exists for the payment reference.
     */
    private function logTerminalAttempt(Payment $payment, string $type, string $description): void
    {
        $exists = DB::table('transactions')
            ->where('reference_type', 'payment')
            ->where('reference_id', $payment->id)
            ->exists();

        if ($exists) {
            return;
        }

        $user = User::find($payment->user_id);
        if (!$user) {
            return;
        }

        DB::table('transactions')->insert([
            'user_id' => $user->id,
            'type' => $type,
            'amount' => 0,
            'balance_after' => $user->balance,
            'reference_type' => 'payment',
            'reference_id' => $payment->id,
            'description' => $description,
            'created_by' => null,
            'created_at' => now(),
        ]);
    }
}
