# Releasing the next major

This branch changes the support policy and send/access behavior. Release it as a new major version; leave `v1.0.0` unchanged.

## Repository checks

- Require the `tests`, `quality`, `browser`, and `smoke` checks on `main` through repository branch protection or rulesets. Repository administration permission is required to configure this; workflow files cannot enforce a repository ruleset by themselves.
- Review dependency updates. Automatic dependency merges and bot writes to main have been removed.
- Run the checks listed in README on the exact proposed release commit. CI builds the supported PHP/Laravel combinations, including real FCM integration with a mocked provider and a lane where FCM is absent.
- Build both a Git archive and a `composer archive`. Confirm they contain `config/`, `routes/`, `src/`, `stubs/`, Blade views, the favicon, bundled CSS, and JavaScript. They should omit the development lock file, tests, build tools, local examples, and launch media. A user does not run npm to install the package.
- Refresh `docs/images/preview.png` and `workflow.gif` when the workspace changes. The README uses commit-pinned hosted images so package downloads can omit launch media and deleting a feature branch does not break them. Keep `social-preview.svg` and its 1280 × 640 PNG in sync; upload the PNG under the repository’s General settings → Social preview.

## Fresh Laravel application

Use a temporary application for each supported Laravel major. Install this branch through a Composer path or VCS repository in `require-dev`.

1. Configure the app key, sessions, cache, and database. Run its migrations if its session/cache drivers require database tables.
2. Run `php artisan pushbox:install --no-interaction` twice. Confirm it preserves the registration file.
3. Open `/pushbox` with no registrations: expect 200 and onboarding instructions. Add the README notification and a typed recipient; verify payloads, variants, and translations.
4. Repeat after `php artisan config:cache` and `php artisan route:cache`; check customized `pushbox.path` forms too.
5. Build routes locally, clear any locally cached configuration, and boot with `APP_ENV=production`: `/pushbox` must return 404 when local-only remains enabled. With non-local access enabled and no Gate, it must return 403.
6. Add an authorized staging Gate and confirm it admits only authorized developers. Verify CSRF rejection, token Save/Clear never sending, stale-target rejection, and the send rate limit.
7. Remove the optional FCM driver: previews should work and real sends should report the missing dependency.
8. Run `composer install --no-dev` and rebuild package discovery/caches for production. `/pushbox` must return 404.

`scripts/smoke-request.php /path/to/fresh-app EXPECTED_STATUS` exercises GET `/pushbox` in a fresh process. It is intended only for disposable test applications. The `smoke` workflow automates the empty-install/cache/environment/no-dev checks with both supported Laravel majors.

## Device acceptance

Use a dedicated test Firebase project and your own device. Do not commit credentials or tokens.

- Send one confirmed notification and verify it reaches the intended physical device.
- Verify user-dependent text, selected language, and the production device route remaining unchanged.
- Check invalid/unregistered tokens and provider failures. Accepted means provider acceptance, not proof of delivery.
- If advertising both Android and Apple device behavior, verify both and record tested OS/client versions. The preview cards are simulations.

## Publish

After review and successful CI/device acceptance, merge the PR. Update this changelog with the actual release date and version, then create a new immutable tag and matching GitHub release. Verify Packagist imports the tag and a fresh stable Composer install selects it.

Real-device validation and publishing require the maintainer's credentials and explicit release decision. Neither is performed by the mocked integration suite.
