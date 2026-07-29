<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Schedule
    |--------------------------------------------------------------------------
    | 'hourly' or 'nightly' — see routes/console.php for where this is read.
    | The scheduler is gated off entirely until the first live sync has been
    | reviewed via Sync Preview and confirmed — see HrSyncService::preview()
    | and Setting 'hr_first_sync_completed_at'. architecture-plan.md §2.5.
    */
    'schedule' => env('HR_SYNC_SCHEDULE', 'hourly'),

    /*
    |--------------------------------------------------------------------------
    | Source binding
    |--------------------------------------------------------------------------
    | 'fake' uses App\Services\HrSync\FakeHrSource for local dev/demo.
    | 'rest_api' uses App\Services\HrSync\HrRestApiSource against the real HR API.
    */
    'source' => env('HR_SYNC_SOURCE', 'fake'),

    'api' => [
        'base_url' => env('HR_SYNC_API_URL'),
        'api_key' => env('HR_SYNC_API_KEY'),
        'endpoint' => env('HR_SYNC_API_ENDPOINT', '/api/employees'),
        'timeout' => (int) env('HR_SYNC_API_TIMEOUT', 30),
    ],

    // No status_map here by design — employment status is a synced lookup table
    // (employee_statuses), not an OCED-owned translation. See architecture-plan.md §2.5.
];
