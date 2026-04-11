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
    | When true, routes are only registered in the local environment unless
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
    | after rendering (safe for factories that persist models).
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
    |
    */
    'send_allow_non_local' => env('PUSHBOX_SEND_NON_LOCAL', false),

    'send' => [
        'enabled' => env('PUSHBOX_ALLOW_SEND', false),
        'fcm' => [
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
