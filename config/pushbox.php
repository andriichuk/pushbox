<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Enable Pushbox
    |--------------------------------------------------------------------------
    |
    | When false, routes are not registered.
    |
    */
    'enabled' => env('PUSHBOX_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Local only
    |--------------------------------------------------------------------------
    |
    | When true, routes are only available in local/testing, including cached routes, unless
    | you override with PUSHBOX_LOCAL_ONLY=false.
    |
    */
    'local_only' => env('PUSHBOX_LOCAL_ONLY', true),

    /*
    |--------------------------------------------------------------------------
    | Route path & middleware
    |--------------------------------------------------------------------------
    */
    'path' => env('PUSHBOX_PATH', 'pushbox'),

    // Outside local/testing, the viewPushbox Gate must also authorize access.
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Registration file
    |--------------------------------------------------------------------------
    |
    | Same idea as Mailbook: register notifications in this file.
    |
    */
    'route_file' => base_path('routes/pushbox.php'),

    /*
    |--------------------------------------------------------------------------
    | Locales
    |--------------------------------------------------------------------------
    |
    | When non-empty, the UI shows a locale switcher and sets app locale
    | while resolving previews.
    |
    */
    'locales' => [
        // 'en' => 'English',
        // 'nl' => 'Dutch',
    ],

    /*
    |--------------------------------------------------------------------------
    | Database rollback
    |--------------------------------------------------------------------------
    |
    | When true, each preview runs inside a transaction that is rolled back
    | after resolution. Only the default DB connection is covered; external
    | effects and registration-file side effects are not rolled back.
    |
    */
    'database_rollback' => env('PUSHBOX_DATABASE_ROLLBACK', false),

    /*
    |--------------------------------------------------------------------------
    | Test sending (dangerous — use only in local/staging)
    |--------------------------------------------------------------------------
    */
    /*
    |--------------------------------------------------------------------------
    | Allow test sends outside local
    |--------------------------------------------------------------------------
    |
    | Keep false unless you explicitly need Pushbox sends in staging.
    | Non-local access still requires the viewPushbox Gate. Sends are
    | limited to 10 per minute per IP address.
    |
    */
    'send_allow_non_local' => env('PUSHBOX_SEND_NON_LOCAL', false),

    'send' => [
        'enabled' => env('PUSHBOX_ALLOW_SEND', false),
        'fcm' => [
            // Default target when no device token is stored in session (Pushbox UI).
            'token' => env('PUSHBOX_FCM_TOKEN'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed IPs (optional)
    |--------------------------------------------------------------------------
    |
    | When non-empty, only these IPs may access Pushbox (in addition to
    | enabled/local_only checks). Values are passed to Request::ip().
    |
    */
    'allowed_ips' => array_values(array_filter(array_map('trim', explode(',', (string) env('PUSHBOX_ALLOWED_IPS', ''))))),

];
