<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('etsy:sync-orders')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('etsy:sync-inventory')->everyThirtyMinutes()->withoutOverlapping();
Schedule::command('etsy:sync-products')->dailyAt('02:00')->withoutOverlapping();
