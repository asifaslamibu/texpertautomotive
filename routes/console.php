<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Google Sheet stock sync — the command itself checks the on/off switch and interval.
Schedule::command('stock:sync')->hourly()->withoutOverlapping();
