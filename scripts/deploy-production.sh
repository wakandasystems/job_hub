#!/usr/bin/env bash

set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"

cd "$APP_DIR"

if [[ ! -f .env ]]; then
    echo "Missing .env. Copy .env.production.example to .env and set production secrets first."
    exit 1
fi

echo "Installing Composer dependencies..."
"$COMPOSER_BIN" install --no-interaction --prefer-dist --optimize-autoloader --no-dev

if ! grep -q '^APP_KEY=base64:' .env; then
    echo "Generating APP_KEY..."
    "$PHP_BIN" artisan key:generate --force
fi

echo "Preparing Laravel caches and storage..."
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan storage:link || true

echo "Running database migrations..."
"$PHP_BIN" artisan migrate --force

echo "Building production caches..."
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache

echo "Deployment complete."
