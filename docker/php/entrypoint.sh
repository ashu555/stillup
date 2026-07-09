#!/bin/sh
set -e

cd /var/www/html

if [ ! -d vendor ]; then
    composer install --no-interaction --prefer-dist
fi

if [ -f .env ] && grep -q '^APP_KEY=$' .env 2>/dev/null; then
    php artisan key:generate --force
fi

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

exec "$@"
