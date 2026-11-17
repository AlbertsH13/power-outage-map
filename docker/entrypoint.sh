#!/bin/sh
set -e

# 1. Sync compiled frontend assets if available
if [ -d "/var/www/html/build-dist" ]; then
    mkdir -p /var/www/html/public/build
    cp -rf /var/www/html/build-dist/* /var/www/html/public/build/
fi

# 2. Ensure /var/www/html/.env exists
if [ ! -f "/var/www/html/.env" ]; then
    cp /var/www/html/.env.example /var/www/html/.env
fi

# 3. Unset APP_KEY if empty (prevents Docker Compose from injecting an empty string over .env)
if [ -z "$APP_KEY" ]; then
    unset APP_KEY
fi

# 4. Ensure a valid application encryption key exists in /var/www/html/.env
if ! grep -q "^APP_KEY=base64:" /var/www/html/.env 2>/dev/null; then
    php artisan key:generate --force --no-interaction
fi

# 5. Clear configuration cache to guarantee no stale or empty APP_KEY is cached
php artisan config:clear --no-interaction || true

exec "$@"
