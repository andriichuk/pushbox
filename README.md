# Pushbox

[![PHP](https://img.shields.io/badge/PHP-8.4+-8892BF.svg)](https://www.php.net/)
[![Tests](https://img.shields.io/badge/tests-passing-brightgreen.svg)](./)

Pushbox is a Laravel package inspired by [Mailbook](https://github.com/Xammie/mailbook): it lets you **preview FCM push payloads** for `Notification` classes in the browser, without wiring a one-off controller in your app.

**Requirements:** PHP 8.4+, Laravel 11 or 12.

## Installation

```bash
composer require --dev andriichuk/pushbox
```

Register the service provider and facade (Laravel 11+ auto-discovers them; otherwise add `Andriichuk\Pushbox\PushboxServiceProvider` to `config/app.php`).

Publish configuration (optional):

```bash
php artisan vendor:publish --tag="pushbox-config"
```

Scaffold the registration file:

```bash
php artisan pushbox:install
```

This creates [`routes/pushbox.php`](stubs/routes/pushbox.stub) where you register notifications (same idea as Mailbook’s `routes/mailbook.php`).

## Usage

Open `/pushbox` (or your configured `pushbox.path`) when the app runs with routes enabled (by default: **local** only — see [Security](#security)).

### Registering notifications

```php
// routes/pushbox.php
use Andriichuk\Pushbox\Facades\Pushbox;
use App\Notifications\OrderShippedNotification;

Pushbox::add(OrderShippedNotification::class);

Pushbox::add(function (): OrderShippedNotification {
    $order = Order::factory()->create();

    return new OrderShippedNotification($order);
});
```

### Notifiable (`to()`)

Many notifications expect a notifiable user. Mirror Mailbook’s API:

```php
Pushbox::to(User::factory()->make())->add(WelcomeNotification::class);
```

You can also scope several registrations:

```php
Pushbox::to(User::factory()->make())->group(function () {
    Pushbox::add(WelcomeNotification::class);
    Pushbox::add(TrialEndedNotification::class);
});
```

### Categories & groups

```php
Pushbox::category('Orders')->group(function () {
    Pushbox::add(OrderCreatedNotification::class);
    Pushbox::add(OrderShippedNotification::class);
});
```

### Variants

```php
Pushbox::add(OrderCreatedNotification::class)
    ->variant('1 item', fn () => new OrderCreatedNotification(Order::factory()->withOneProduct()->create()))
    ->variant('2 items', fn () => new OrderCreatedNotification(Order::factory()->withTwoProducts()->create()));
```

### Localization

Add locales to `config/pushbox.php` (`locales` array). The UI shows a dropdown; the preview resolver sets `app()->setLocale()` while resolving payloads.

### Database rollback

When `pushbox.database_rollback` is `true`, previews run inside a DB transaction that is **rolled back** after rendering (handy when factories persist models), similar to Mailbook.

## FCM (push)

Install the channel package (suggested):

```bash
composer require laravel-notification-channels/fcm
```

Implement `toFcm($notifiable)` on your notification as documented by that package. Pushbox serializes the returned `FcmMessage` via `toArray()` for the UI — **no Firebase HTTP request** happens during preview.

## Test sending (optional)

**Dangerous:** sends a real notification through your configured FCM driver.

- `PUSHBOX_ALLOW_SEND=true`
- `pushbox.send.fcm.token` / `PUSHBOX_FCM_TOKEN` for test sends
- By default, sends are only reasonable in `local`; set `PUSHBOX_SEND_NON_LOCAL=true` (and `pushbox.send_allow_non_local`) only if you explicitly need staging.

Every send is logged under the `pushbox.sent` / `pushbox.send_failed` context.

## Security

- Routes register when `pushbox.enabled` is true and either `pushbox.local_only` is false **or** the environment is `local` / `testing`.
- Optional IP allowlist: `PUSHBOX_ALLOWED_IPS` (comma-separated).
- Keep the package in `require-dev` if you only need previews locally.

## Mailbook parity (overview)

| Mailbook | Pushbox |
|----------|---------|
| `routes/mailbook.php` | `routes/pushbox.php` |
| `Mailbook::add()` | `Pushbox::add()` |
| `Mailbook::to()` / `group()` / `category()` / `variant()` | Same fluent ideas |
| Preview HTML mail | Preview FCM JSON |
| Optional send | Optional FCM send (gated) |

## Testing

```bash
composer test
# or
./vendor/bin/phpunit
```

## License

The MIT License. See [LICENSE.md](LICENSE.md).
