<?php

use App\Models\Contest;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
Schedule::command('contests:end-expired')->dailyAt('00:10');


Schedule::command('users:deactivate-inactive-planners')->dailyAt('00:00')->withoutOverlapping();
