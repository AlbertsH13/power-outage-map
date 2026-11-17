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

# 3. Synchronize APP_KEY from environment (injected via env_file from host .env)
if [ -n "$APP_KEY" ]; then
    if grep -q "^APP_KEY=" /var/www/html/.env 2>/dev/null; then
        sed -i "s|^APP_KEY=.*|APP_KEY=${APP_KEY}|" /var/www/html/.env
    else
        echo "APP_KEY=${APP_KEY}" >> /var/www/html/.env
    fi
fi

# 4. Clear configuration cache to guarantee fresh environment variables
php artisan config:clear --no-interaction || true

exec "$@"
