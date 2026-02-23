<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('billing:rate-calls --chunk=200')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::call(function () {
    \App\Models\CallRecord::where('status', 'in_progress')
        ->where('call_start', '<', now()->subMinutes(5))
        ->update([
            'status' => \Illuminate\Support\Facades\DB::raw("CASE WHEN disposition = 'ANSWERED' AND billsec > 0 THEN 'completed' ELSE 'unbillable' END"),
            'call_end' => \Illuminate\Support\Facades\DB::raw("COALESCE(call_end, call_start + INTERVAL GREATEST(duration, 1) SECOND)"),
        ]);
})->everyMinute()->withoutOverlapping()->name('cleanup-stale-calls');

Schedule::command('cdr:aggregate')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('trunk:health-check')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('billing:generate-invoices')
    ->monthlyOn(1, '02:00')
    ->withoutOverlapping();

Schedule::command('billing:check-low-balances')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('data:purge')
    ->dailyAt('03:00')
    ->withoutOverlapping();
