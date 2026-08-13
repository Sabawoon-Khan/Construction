<?php

use App\Jobs\PruneMisLogsJob;
use App\Jobs\PruneTrashJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new PruneMisLogsJob(days: 90))
    ->dailyAt('02:30')
    ->withoutOverlapping();

Schedule::job(new PruneTrashJob)
    ->dailyAt('03:00')
    ->withoutOverlapping();

Schedule::command('uploads:prune')->hourly()->withoutOverlapping();
