<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\LowBalanceNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CheckLowBalances extends Command
{
    protected $signature = 'billing:check-low-balances';

    protected $description = 'Notify users whose balance has fallen below their low_balance_threshold';

    public function handle(): int
    {
        $users = User::where('status', 'active')
            ->where('low_balance_threshold', '>', 0)
            ->whereRaw('balance <= low_balance_threshold')
            ->get();

        $notified = 0;

        foreach ($users as $user) {
            // Throttle: only notify once per 24 hours per user
            $cacheKey = "low_balance_notified:{$user->id}";

            if (Cache::has($cacheKey)) {
                continue;
            }

            $user->notify(new LowBalanceNotification(
                currentBalance: (string) $user->balance,
                threshold: (string) $user->low_balance_threshold,
            ));

            Cache::put($cacheKey, true, now()->addHours(24));
            $notified++;
        }

        $this->info("{$notified} low balance notification(s) sent.");

        return self::SUCCESS;
    }
}
