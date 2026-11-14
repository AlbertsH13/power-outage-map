#!/bin/sh
set -e

if [ -d "/var/www/html/build-dist" ]; then
    mkdir -p /var/www/html/public/build
    cp -rf /var/www/html/build-dist/* /var/www/html/public/build/
fi

exec "$@"