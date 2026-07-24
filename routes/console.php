<?php

use App\Models\Setting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// HR sync cadence is configurable from /admin/settings (persisted in the `settings` table),
// falling back to config/hr_sync.php — hourly by default, nightly if the Group's HR changes
// are infrequent. See architecture-plan.md §2.4.
$syncCommand = Schedule::command('hr:sync')->withoutOverlapping();

match (Setting::get('hr_sync_schedule', config('hr_sync.schedule'))) {
    'nightly' => $syncCommand->dailyAt('00:00'),
    default => $syncCommand->hourly(),
};
