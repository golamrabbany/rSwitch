<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\User;
use App\Services\BalanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * For every CyberNest client:
 *  - enable Random Caller ID on their SIP accounts
 *  - give a random balance (BDT 50-200) funded by 1-3 realistic online
 *    bKash / Nagad payments, each credited through BalanceService so that
 *    payments + transactions + balance all reconcile.
 * Idempotent: clients that already have a payment are skipped.
 */
class AddCyberNestBalancePayments extends Command
{
    protected $signature = 'cybernest:add-balance-payments
        {--reseller=46 : Reseller user id whose clients to fund}
        {--min=50 : minimum balance (BDT)}
        {--max=200 : maximum balance (BDT)}
        {--limit=0 : only first N (0 = all)}
        {--dry-run : preview without writing}';

    protected $description = 'Enable Random CLI + add random BDT balance via realistic bKash/Nagad payment history';

    public function handle(BalanceService $balance): int
    {
        $resellerId = (int) $this->option('reseller');
        $min = (int) $this->option('min');
        $max = (int) $this->option('max');
        $limit = (int) $this->option('limit');
        $dry = (bool) $this->option('dry-run');

        // ---- Step 1: Random Caller ID on every CyberNest SIP account ----
        $clientIds = User::where('parent_id', $resellerId)->where('role', 'client')->pluck('id');
        if (! $dry) {
            $cli = DB::table('sip_accounts')->whereIn('user_id', $clientIds)->update(['random_caller_id' => 1]);
            $this->info("Random Caller ID enabled on {$cli} SIP accounts.");
        }

        // ---- Step 2: balance + bKash/Nagad payment history ----
        $q = User::where('parent_id', $resellerId)->where('role', 'client')->orderBy('id');
        if ($limit > 0) {
            $q->limit($limit);
        }
        $clients = $q->get(['id', 'username', 'phone', 'name']);

        $this->info(sprintf('%d clients | balance BDT %d-%d | %s',
            $clients->count(), $min, $max, $dry ? 'DRY-RUN' : 'LIVE'));

        $funded = 0; $skipped = 0; $totalPayments = 0;
        $bar = $dry ? null : $this->output->createProgressBar($clients->count());
        $bar?->start();

        foreach ($clients as $c) {
            if (Payment::where('user_id', $c->id)->exists()) {
                $skipped++; $bar?->advance(); continue;
            }

            mt_srand((int) substr($c->username, -7) + 7); // stable per account
            $target = mt_rand($min, $max);
            $parts = $this->splitAmount($target);

            foreach ($parts as $amount) {
                $isBkash = mt_rand(0, 1) === 1;
                $method = $isBkash ? 'online_bkash' : 'online_nagad';
                $source = $isBkash ? 'bkash' : 'nagad';
                $trx = $this->genTrxId();
                $when = Carbon::now()->subDays(mt_rand(1, 90))->subMinutes(mt_rand(0, 1440));
                $payerMsisdn = $c->phone ?: ('01' . mt_rand(300000000, 999999999));

                if ($dry) {
                    $this->line(sprintf('%s | %-22s | %s %6sBDT | TrxID %s | %s', $c->username, $c->name, strtoupper($source), $amount, $trx, $when->toDateString()));
                    $totalPayments++;
                    continue;
                }

                $gwResp = $isBkash
                    ? ['trxID' => $trx, 'transactionStatus' => 'Completed', 'amount' => (string) $amount, 'currency' => 'BDT', 'paymentExecuteTime' => $when->toIso8601String(), 'payerReference' => $payerMsisdn, 'merchantInvoiceNumber' => 'INV' . $trx]
                    : ['payment_ref_id' => $trx, 'status' => 'Success', 'amount' => (string) $amount, 'currency' => 'BDT', 'datetime' => $when->toIso8601String(), 'client_mobile_no' => $payerMsisdn, 'issuer_payment_ref' => 'NGD' . $trx];

                DB::transaction(function () use ($c, $amount, $method, $source, $trx, $when, $gwResp, $balance) {
                    $payment = Payment::create([
                        'user_id'                => $c->id,
                        'amount'                 => $amount,
                        'currency'               => 'BDT',
                        'payment_method'         => $method,
                        'gateway_transaction_id' => $trx,
                        'gateway_response'       => $gwResp,
                        'status'                 => 'completed',
                        'completed_at'           => $when,
                        'notes'                  => strtoupper($source) . ' online recharge',
                    ]);

                    $txn = $balance->credit(
                        user: $c,
                        amount: (string) $amount,
                        type: 'topup',
                        referenceType: 'payment',
                        referenceId: $payment->id,
                        description: strtoupper($source) . ' payment ' . $trx,
                        createdBy: $c->id,
                        source: $source,
                        remarks: 'TrxID ' . $trx,
                    );

                    $payment->update(['transaction_id' => $txn->id, 'created_at' => $when, 'updated_at' => $when]);
                });

                $totalPayments++;
            }

            $funded++;
            $bar?->advance();
        }

        $bar?->finish();
        $this->newLine();
        $this->info("Done. funded={$funded} skipped={$skipped} payments_created={$totalPayments}");
        return self::SUCCESS;
    }

    /** Split a target into 1-3 payment amounts (each >= 20) that sum exactly to it. */
    private function splitAmount(int $target): array
    {
        $maxParts = min(3, intdiv($target, 20));
        $n = max(1, mt_rand(1, max(1, $maxParts)));
        if ($n === 1) {
            return [$target];
        }
        $parts = [];
        $remaining = $target;
        for ($i = 0; $i < $n - 1; $i++) {
            $maxThis = $remaining - 20 * ($n - 1 - $i);
            $a = mt_rand(20, max(20, $maxThis));
            $parts[] = $a;
            $remaining -= $a;
        }
        $parts[] = $remaining;
        return $parts;
    }

    /** Realistic 10-char uppercase alphanumeric transaction id (bKash/Nagad style). */
    private function genTrxId(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $s = '';
        for ($i = 0; $i < 10; $i++) {
            $s .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $s;
    }
}
