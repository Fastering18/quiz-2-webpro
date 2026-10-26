#!/bin/sh
set -e
cd /var/www/html

if [ -z "$APP_KEY" ]; then
  echo "ERROR: APP_KEY is not set. Set it as an environment variable (e.g. base64:...)." >&2
  exit 1
fi

# Render provides PORT (default 10000); Apache reads it from the environment.
export PORT="${PORT:-10000}"

# Clear stale caches baked at build time, if any
php artisan config:clear >/dev/null 2>&1 || true

if [ "$RESET_DB" = "1" ]; then
  echo "RESET_DB=1 - running migrate:fresh..."
  php artisan migrate:fresh --force
else
  echo "Running migrations..."
  php artisan migrate --force
fi

# Seed only when the users table (pengguna) is empty
COUNT=$(php artisan tinker --execute='echo \App\Models\User::count();' 2>/dev/null | tr -dc '0-9')
if [ "${COUNT:-0}" = "0" ]; then
  echo "pengguna table empty - seeding..."
  php artisan db:seed --force
else
  echo "pengguna table has ${COUNT} rows - skipping seed."
fi

# Caching is safe here: config only reads env at runtime via the container env
php artisan config:cache
php artisan route:cache || true

exec "$@"
