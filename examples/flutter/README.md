# Pushbox Android demo

A single-screen Flutter app that registers its FCM token with a local Laravel demo and receives notifications sent from Pushbox. It displays foreground notifications, restores the latest message, handles notification taps, and re-registers refreshed tokens. This example supports one default test phone at a time.

## Firebase

Use the same Firebase project for the Android client and Laravel sender.

1. Register an Android app with package name `dev.pushbox.pushbox_demo`.
2. Put its downloaded `google-services.json` in `android/app/` (ignored by Git).
3. Put the Firebase Admin service account JSON **on the server only**, for example `storage/app/private/pushbox-firebase.json`, and configure `FIREBASE_CREDENTIALS=/var/www/html/storage/app/private/pushbox-firebase.json` when using Sail. Never put an Admin private key in the mobile app.

The app uses Firebase Messaging 16.x and Firebase Core 4.x to obtain FCM registration tokens compatible with Pushbox's current PHP FCM channel. Messaging 17 introduces FID registration APIs; adopting those requires a separate sender compatibility review. Dependencies are locked in `pubspec.lock`.

## Laravel demo endpoint

The companion files are in [`../laravel`](../laravel). Copy them into a Laravel 11+ demo that already has Pushbox and `laravel-notification-channels/fcm` configured. The feature test additionally uses the commerce notification examples from this demo.

Register `routes/api.php` in `bootstrap/app.php`:

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    // Keep your other existing routing options.
)
```

Append the middleware to the existing web group:

```php
$middleware->web(append: [\App\Http\Middleware\UsePushboxDemoDevice::class]);
```

Generate a local random registration key (`php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'`) and add it to the server's `.env`:

```dotenv
PUSHBOX_ALLOW_SEND=true
PUSHBOX_DEMO_REGISTRATION_KEY=your-random-local-registration-key
FIREBASE_CREDENTIALS=/var/www/html/storage/app/private/pushbox-firebase.json
```

Clear cached config (`./vendor/bin/sail artisan config:clear`). `POST /api/pushbox/device` accepts `{"token":"FCM_REGISTRATION_TOKEN"}` with `Authorization: Bearer YOUR_REGISTRATION_KEY`. It is available only in `local`/`testing`, validates the payload, and limits registration to 10 requests per minute. The registered token is stored in the server's file cache and becomes Pushbox's configured default. A manually saved token in the browser's Device settings takes precedence; clear it to use the phone's default. Registration never sends a notification.

## Run

```sh
flutter pub get
flutter run
```

Enter the server URL and registration key in the app, then tap **Register device**. The settings are remembered locally. Enter the registration key directly in the app; it is never compiled into the APK. To prefill a different server address, copy `run.example.json` to ignored `run.local.json`, edit the URL, and use:

```sh
flutter run --dart-define-from-file=run.local.json
```

- Android emulator: `http://10.0.2.2:8080` reaches the host machine's demo.
- USB phone: run `adb reverse tcp:8080 tcp:8080` and use `http://127.0.0.1:8080`.
- Wi-Fi phone: use the computer's reachable LAN IP and port. Both devices must be on the same network.

Plain HTTP is enabled only in the Android **debug** manifest for local development. Release builds require HTTPS. The registration key is limited to this local demo endpoint and is not a production authentication scheme.

After registration, reload `http://localhost:8080/pushbox` on the computer, select a notification, and press **Send notification**. With the phone app open, it displays a system notification and the latest payload. With the app in the background, Android displays notification payloads; tapping one opens its content in the app. Open the app at least once after installation and allow notifications. Force-stopping it in Android settings prevents delivery until it is reopened. No queue worker is needed for the synchronous Pushbox send.

Firebase's [Flutter setup](https://firebase.google.com/docs/flutter/setup) and [message receiving documentation](https://firebase.google.com/docs/cloud-messaging/flutter/receive-messages) cover the platform requirements.

## Checks

```sh
flutter analyze
flutter test
flutter build apk --debug
```

The APK is written to `build/app/outputs/flutter-apk/app-debug.apk`. A successful build does not prove real push delivery: that requires valid Firebase configuration, the server credential, and a Google Play-enabled emulator or Android phone.
