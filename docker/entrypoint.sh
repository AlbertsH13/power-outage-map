#!/bin/sh
set -e

if [ -d "/var/www/html/build-dist" ]; then
    mkdir -p /var/www/html/public/build
    cp -rf /var/www/html/build-dist/* /var/www/html/public/build/
fi

if [ ! -f "/var/www/html/.env" ]; then
    cp /var/www/html/.env.example /var/www/html/.env
fi

KEY_FILE="/var/www/html/storage/framework/app.key"

if [ -z "$APP_KEY" ]; then
    if [ ! -s "$KEY_FILE" ]; then
        mkdir -p "/var/www/html/storage/framework"
        TMP_KEY="$KEY_FILE.$$"
        head -c 32 /dev/urandom | base64 | awk '{print "base64:"$1}' > "$TMP_KEY"
        mv "$TMP_KEY" "$KEY_FILE"
    fi
    APP_KEY=$(cat "$KEY_FILE")
fi

export APP_KEY

if grep -q "^APP_KEY=" /var/www/html/.env 2>/dev/null; then
    sed -i "s|^APP_KEY=.*|APP_KEY=${APP_KEY}|" /var/www/html/.env
else
    echo "APP_KEY=${APP_KEY}" >> /var/www/html/.env
fi

php artisan config:clear --no-interaction || true

exec "$@"
