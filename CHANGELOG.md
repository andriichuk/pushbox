# Changelog

## Unreleased

### Changed

- Support PHP 8.4/8.5 and Laravel 12/13; remove Laravel 11 support.
- Control workspace access with `PUSHBOX_ENABLED`, enabled by default and independent of the application environment.
- Enforce the optional `viewPushbox` authorization Gate consistently in every environment.
- Separate token Save/Clear from sending. Send requests validate the current target hash.
- Log actual outcomes as `pushbox.accepted`, `pushbox.failed`, and `pushbox.skipped`.

### Fixed

- Route the FCM test token under the driver's `fcm` key.
- Preserve typed recipients and selected translations for test payloads without changing the recipient's real device routes.
- Restore locale, nested group context, database transactions, and scoped registry state.
- Render setup instructions for empty installs and contain preview failures.
- Enforce the enabled flag and configured access restrictions at request time, including cached routes.
- Report rejected, skipped, or missing provider results accurately; include actual FCM results and redact the test token.

### Added

- Stale-target detection and send throttling.
- Bundled offline UI assets and real-driver, browser, lifecycle, and access regression tests.
- Pull-request CI for the supported compatibility matrix and optional-driver absence, security audits, and reproducible asset builds.
- Fresh-app, Firebase setup, and release documentation.

## 1.0.0 - 2026-04-11

- Initial release: Mailbook-style registry, FCM payload preview, and optional test sending.
