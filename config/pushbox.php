<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Enable Pushbox
    |--------------------------------------------------------------------------
    |
    | Enabled by default in every environment. Set PUSHBOX_ENABLED=false to disable.
    | When false, routes are not registered and cached routes return 404.
    |
    */
    'enabled' => env('PUSHBOX_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Route path & middleware
    |--------------------------------------------------------------------------
    */
    'path' => env('PUSHBOX_PATH', 'pushbox'),

    // Add authentication middleware as needed. A defined viewPushbox Gate is also enforced.
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

    // Defaults to APP_NAME, or app.name when unset. Set a custom label to override.
    'preview_app_name' => env('APP_NAME'),

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
    | Test sending
    |--------------------------------------------------------------------------
    |
    | Enable real sends separately from workspace access. Sends require
    | PUSHBOX_ENABLED=true and are limited to 10 per minute per IP address.
    |
    */
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
    | the enabled flag and any configured authorization). Values are
    | compared against Request::ip().
    |
    */
    'allowed_ips' => array_values(array_filter(array_map('trim', explode(',', (string) env('PUSHBOX_ALLOWED_IPS', ''))))),

];
