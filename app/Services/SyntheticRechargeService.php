<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Creates one completed bKash/Nagad online payment and credits it to a client's
 * balance, so the top-up appears in payment history + the ledger identically to
 * a real recharge. Shared by auto-recharge and the CyberNest bulk command.
 */
class SyntheticRechargeService
{
    public function __construct(private BalanceService $balance) {}

    public function recharge(User $client, int $min = 50, int $max = 200, string $reason = 'Auto-recharge'): Payment
    {
        $amount  = random_int($min, $max);
        $isBkash = random_int(0, 1) === 1;
        $method  = $isBkash ? 'online_bkash' : 'online_nagad';
        $source  = $isBkash ? 'bkash' : 'nagad';
        $trx     = $this->genTrxId();
        $when    = Carbon::now();
        $msisdn  = $client->phone ?: ('01' . random_int(300000000, 999999999));

        $gwResp = $isBkash
            ? ['trxID' => $trx, 'transactionStatus' => 'Completed', 'amount' => (string) $amount, 'currency' => 'BDT', 'paymentExecuteTime' => $when->toIso8601String(), 'payerReference' => $msisdn]
            : ['payment_ref_id' => $trx, 'status' => 'Success', 'amount' => (string) $amount, 'currency' => 'BDT', 'datetime' => $when->toIso8601String(), 'client_mobile_no' => $msisdn];

        return DB::transaction(function () use ($client, $amount, $method, $source, $trx, $when, $gwResp, $reason) {
            $payment = Payment::create([
                'user_id'                => $client->id,
                'amount'                 => $amount,
                'currency'               => $client->currency ?: 'BDT',
                'payment_method'         => $method,
                'gateway_transaction_id' => $trx,
                'gateway_response'       => $gwResp,
                'status'                 => 'completed',
                'completed_at'           => $when,
                'notes'                  => $reason . ' (' . strtoupper($source) . ')',
            ]);

            $txn = $this->balance->credit(
                user: $client,
                amount: (string) $amount,
                type: 'topup',
                referenceType: 'payment',
                referenceId: $payment->id,
                description: strtoupper($source) . ' ' . $reason . ' ' . $trx,
                createdBy: null,
                source: $source,
                remarks: 'TrxID ' . $trx,
            );

            $payment->update(['transaction_id' => $txn->id]);

            return $payment->refresh();
        });
    }

    private function genTrxId(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $s = '';
        for ($i = 0; $i < 10; $i++) {
            $s .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $s;
    }
}
