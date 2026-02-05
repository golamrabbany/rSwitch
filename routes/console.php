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
