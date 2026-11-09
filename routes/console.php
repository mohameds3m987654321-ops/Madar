<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('backup:run')->dailyAt('02:00');
Schedule::command('backup:clean')->dailyAt('02:30');
Schedule::command('backup:monitor')->dailyAt('02:45');
Schedule::command('activitylog:clean')->dailyAt('03:00');

// لحظات فشل المهام الخلفية في اللوج لتتبعها بسهولة
Schedule::command('queue:prune-failed --hours=720')->dailyAt('03:10');
Schedule::command('model:prune')->dailyAt('03:20');
Schedule::command('lessons:cleanup-uploads')->hourly();
