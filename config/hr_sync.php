<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Schedule
    |--------------------------------------------------------------------------
    | 'hourly' or 'nightly' — see routes/console.php for where this is read.
    */
    'schedule' => env('HR_SYNC_SCHEDULE', 'hourly'),

    /*
    |--------------------------------------------------------------------------
    | Source binding
    |--------------------------------------------------------------------------
    | 'fake' uses App\Services\HrSync\FakeHrSource for local dev/demo.
    | Swap to 'rest_api' (and implement HrRestApiSource) once real HR API
    | credentials exist — see App\Providers\AppServiceProvider.
    */
    'source' => env('HR_SYNC_SOURCE', 'fake'),

    'api' => [
        'base_url' => env('HR_SYNC_API_URL'),
        'api_key' => env('HR_SYNC_API_KEY'),
        // Assumed to be a standard Laravel paginated JSON resource (data/links/meta).
        // Adjust HrRestApiSource once the real HR API's actual shape is confirmed.
        'endpoint' => env('HR_SYNC_API_ENDPOINT', '/api/employees'),
        'timeout' => (int) env('HR_SYNC_API_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Employment status map
    |--------------------------------------------------------------------------
    | Translates HR's es_id (or status code) into our EmploymentStatus enum.
    | Placeholder integer keys — replace once the real es_id values are known.
    */
    'status_map' => [
        1 => 'active',
        2 => 'on_leave',
        3 => 'resigned',
        4 => 'inactive',
        'active' => 'active',
        'on_leave' => 'on_leave',
        'resigned' => 'resigned',
        'inactive' => 'inactive',
    ],
];
