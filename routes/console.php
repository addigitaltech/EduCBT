<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

// Heartbeat so the system health page can tell whether the scheduler (cron) runs.
Schedule::call(function (): void {
    Cache::put('scheduler.heartbeat', now()->getTimestamp(), 3600);
})->name('scheduler-heartbeat')->everyMinute();

// Shared hosting has no long-running workers: drain the database queue every minute.
if (config('profile.queue_via_scheduler')) {
    Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
        ->name('queue-drain')
        ->everyMinute()
        ->withoutOverlapping(5);
}
