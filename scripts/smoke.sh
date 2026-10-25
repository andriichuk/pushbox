#!/usr/bin/env bash
set -euo pipefail
pushbox_root="$(cd "$(dirname "$0")/.." && pwd)"
pushbox_smoke_dir="$(mktemp -d)"
trap 'rm -rf "$pushbox_smoke_dir"' EXIT
pushbox_app="$pushbox_smoke_dir/app"
composer create-project laravel/laravel "$pushbox_app" "^${1:-13}.0" --no-install --no-scripts --no-interaction
php "$pushbox_root/scripts/configure-smoke.php" "$pushbox_app" "$pushbox_root"
cd "$pushbox_app"
composer update --prefer-dist --no-interaction
php artisan pushbox:install --no-interaction
pushbox_registration_before="$(sha256sum routes/pushbox.php)"
php artisan pushbox:install --no-interaction
test "$pushbox_registration_before" = "$(sha256sum routes/pushbox.php)"
php "$pushbox_root/scripts/smoke-request.php" "$pushbox_app" 200
php artisan config:cache
php "$pushbox_root/scripts/smoke-request.php" "$pushbox_app" 200
php artisan config:clear
php artisan route:cache
php "$pushbox_root/scripts/smoke-request.php" "$pushbox_app" 200
APP_ENV=production php "$pushbox_root/scripts/smoke-request.php" "$pushbox_app" 404
APP_ENV=staging PUSHBOX_LOCAL_ONLY=false php "$pushbox_root/scripts/smoke-request.php" "$pushbox_app" 403
php artisan route:clear
composer install --no-dev --prefer-dist --no-interaction
APP_ENV=production php "$pushbox_root/scripts/smoke-request.php" "$pushbox_app" 404
