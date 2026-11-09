<?php

/*
|--------------------------------------------------------------------------
| Application profile
|--------------------------------------------------------------------------
| One shared codebase runs as either the CENTRAL platform or a SCHOOL
| installation. A deployment is installed as exactly one profile and must
| never be switched afterwards (the installation record is checked on every
| web request).
*/

return [
    'name' => env('APP_PROFILE', 'school'),

    'version' => env('APP_VERSION', '0.1.0-phase1'),

    // Dates are stored in UTC and shown in this time zone.
    'display_timezone' => env('APP_DISPLAY_TIMEZONE', 'Africa/Lagos'),

    // When true, `queue:work --stop-when-empty` is run by the scheduler
    // (cPanel / shared hosting). Set to false when a dedicated worker runs.
    'queue_via_scheduler' => (bool) env('QUEUE_VIA_SCHEDULER', true),
];
