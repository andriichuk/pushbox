<p align="center"><img src="resources/favicon.svg" width="72" height="72" alt="Pushbox logo"></p>

<h1 align="center">Pushbox</h1>

<p align="center"><strong>A notification studio for Laravel.</strong><br>
Preview FCM payloads, compare variants and translations, and send test notifications to your device.</p>

<p align="center">
  <a href="https://github.com/andriichuk/pushbox/actions/workflows/run-tests.yml"><img src="https://github.com/andriichuk/pushbox/actions/workflows/run-tests.yml/badge.svg" alt="Tests"></a>
  <a href="https://github.com/andriichuk/pushbox/actions/workflows/phpstan.yml"><img src="https://github.com/andriichuk/pushbox/actions/workflows/phpstan.yml/badge.svg" alt="Quality"></a>
  <a href="https://packagist.org/packages/andriichuk/pushbox"><img src="https://img.shields.io/packagist/v/andriichuk/pushbox.svg" alt="Packagist version"></a>
</p>

<p align="center">
  <a href="#install-and-preview">Getting started</a> &middot;
  <a href="#recipients-categories-variants-and-translations">Variants and translations</a> &middot;
  <a href="#send-to-a-test-device">Test sending</a>
</p>

<p align="center">
  <a href="https://raw.githubusercontent.com/andriichuk/pushbox/be1aeb5e4cec3f127bfba3cc9f8e46d2e97d516a/docs/images/preview.png"><img src="https://raw.githubusercontent.com/andriichuk/pushbox/be1aeb5e4cec3f127bfba3cc9f8e46d2e97d516a/docs/images/preview.png" alt="Pushbox workspace with notification content, data payload, and an expanded Android preview" width="1280"></a>
</p>

Inspired by [Mailbook](https://github.com/Xammie/mailbook).

**Requirements:** PHP 8.4/8.5 and Laravel 12/13. The optional real-send integration supports **laravel-notification-channels/fcm 6.2+ (6.x)**.

## Install and preview

```bash
composer require andriichuk/pushbox
php artisan pushbox:install
```

Open `/pushbox`. Register notifications in the generated `routes/pushbox.php`.

To preview FCM notifications, install the optional channel:

```bash
composer require laravel-notification-channels/fcm:^6.2
php artisan make:notification WelcomePush
```

`app/Notifications/WelcomePush.php`:

```php
<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class WelcomePush extends Notification
{
    public function via(object $notifiable): array
    {
        return [FcmChannel::class];
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        return new FcmMessage(
            notification: new FcmNotification(
                title: __('Welcome'),
                body: 'Hello '.($notifiable->name ?? 'developer').'!',
            ),
            data: ['screen' => 'welcome'], // FCM data values must be strings.
        );
    }
}
```

Register it in `routes/pushbox.php`:

```php
use Andriichuk\Pushbox\Facades\Pushbox;
use App\Notifications\WelcomePush;

Pushbox::add(WelcomePush::class);
```

Previews do not send notifications. Without the FCM channel, Pushbox can preview objects exposing `toArray()`.

## Recipients, categories, variants, and translations

Use a typed recipient and factories for different examples:

```php
use App\Models\User;

Pushbox::to(User::factory()->make(['name' => 'Ada']))
    ->category('Onboarding')
    ->group(function () {
        Pushbox::add(WelcomePush::class)
            ->variant('Another greeting', fn (): WelcomePush => new WelcomePush);
    });
```

Factories must declare their concrete notification return type. To add a language selector, publish the configuration and set `locales`:

```bash
php artisan vendor:publish --tag=pushbox-config
```

```php
// config/pushbox.php
'locales' => ['en' => 'English', 'uk' => 'Ukrainian'],
```

Use `factory()->make()` for sample recipients. `PUSHBOX_DATABASE_ROLLBACK=true` rolls back preview writes on the default database connection only.

## Send to a test device

1. Configure Firebase using the FCM channel's [installation instructions](https://github.com/laravel-notification-channels/fcm#installation).
2. Enable sending in `.env`:

   ```dotenv
   PUSHBOX_ALLOW_SEND=true
   # Optional default device token:
   PUSHBOX_FCM_TOKEN=your-device-token
   ```

3. Open **Device settings**, save your device's FCM token, then press **Send notification**. Use a token from the same Firebase project as your credentials.

Sends run immediately through FCM. Results show **accepted**, **failed**, or **skipped**; accepted does not guarantee delivery.

## Enablement and access

Pushbox is enabled by default in every environment and remains installed with `composer install --no-dev`. To disable it:

```dotenv
PUSHBOX_ENABLED=false
```

Restrict access with your application's middleware or a `viewPushbox` Gate:

```php
use App\Models\User;
use Illuminate\Support\Facades\Gate;

Gate::define('viewPushbox', function (?User $user): bool {
    return $user !== null && $user->can('use-developer-tools');
});
```

A defined Gate applies to every Pushbox route. Configure `pushbox.middleware` for authentication, `pushbox.path` for a custom URL, or `PUSHBOX_ALLOWED_IPS` for an IP allowlist.

After configuration changes, rebuild any configuration and route caches used by your application.

## Contributing and support

See [Contributing](CONTRIBUTING.md) for development and checks, [Support](SUPPORT.md) for help, and [Security](SECURITY.md) for vulnerability reports.

## License

[MIT](LICENSE.md).
