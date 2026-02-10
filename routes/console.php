<?php

use App\Models\Contest;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
Schedule::call(function () {
    Contest::where('status', 'active')
        ->whereDate('end_date', '<', now())
        ->update(['status' => 'ended']);
})->daily();

