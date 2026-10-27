#!/bin/sh
set -e
cd /app

if [ -z "$APP_KEY" ]; then
  echo "ERROR: APP_KEY is not set. Set it as an environment variable (e.g. base64:...)." >&2
  exit 1
fi

export PORT="${PORT:-10000}"
export OCTANE_SERVER="${OCTANE_SERVER:-frankenphp}"

if [ "$RESET_DB" = "1" ]; then
  php artisan app:prepare --fresh
else
  php artisan app:prepare
fi

# One worker fits Render's free 512MB instance. The process stays booted between requests.
exec php artisan octane:frankenphp \
  --host=0.0.0.0 \
  --port="${PORT}" \
  --workers="${OCTANE_WORKERS:-1}" \
  --max-requests="${OCTANE_MAX_REQUESTS:-500}"
