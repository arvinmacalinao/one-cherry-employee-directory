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
// are infrequent. See architecture-plan.md §2.5.
//
// Gated on the first-sync flag: the scheduler must never run before an Admin has reviewed
// a Sync Preview and explicitly run the first live sync from /admin/sync — see
// App\Livewire\Admin\Sync::confirmFirstSync() and architecture-plan.md §2.5.
$syncCommand = Schedule::command('hr:sync')
    ->withoutOverlapping()
    ->when(fn () => Setting::get('hr_first_sync_completed_at') !== null);

match (Setting::get('hr_sync_schedule', config('hr_sync.schedule'))) {
    'nightly' => $syncCommand->dailyAt('00:00'),
    default => $syncCommand->hourly(),
};
