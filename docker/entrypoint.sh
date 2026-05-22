#!/bin/sh
set -e

cd /var/www/html

if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    echo "ERROR: Set APP_KEY in Dokploy environment variables."
    echo "Generate locally: php artisan key:generate --show"
    exit 1
fi

mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

php artisan storage:link --force 2>/dev/null || true

php artisan package:discover --ansi

php artisan config:cache
php artisan route:cache
php artisan view:cache

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force --no-interaction
fi

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
