<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SyntheticRechargeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoRechargeClients extends Command
{
    protected $signature = 'billing:auto-recharge {--min=50} {--max=200} {--limit=0} {--dry-run}';

    protected $description = 'Auto top-up enabled clients whose balance is at/below their low-balance threshold';

    public function handle(SyntheticRechargeService $recharge): int
    {
        $min   = (int) $this->option('min');
        $max   = (int) $this->option('max');
        $dry   = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $query = User::query()
            ->where('role', 'client')
            ->where('status', 'active')
            ->where('auto_recharge_enabled', true)
            ->where('low_balance_threshold', '>', 0)
            ->whereColumn('balance', '<=', 'low_balance_threshold')
            ->orderBy('id');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $clients = $query->get();
        $count = 0; $total = 0;

        foreach ($clients as $client) {
            if ($dry) {
                $this->line("would recharge {$client->username} (balance {$client->balance} <= {$client->low_balance_threshold})");
                $count++;
                continue;
            }
            try {
                $payment = $recharge->recharge($client, $min, $max, 'Auto-recharge');
                $count++;
                $total += (int) $payment->amount;
            } catch (\Throwable $e) {
                Log::warning('auto-recharge failed', ['user_id' => $client->id, 'error' => $e->getMessage()]);
            }
        }

        $this->info("Auto-recharge: topped_up={$count} total_added={$total}");

        return self::SUCCESS;
    }
}
