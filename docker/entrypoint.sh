#!/bin/sh
set -e

cd /var/www/html

resolve_app_key() {
    if [ -n "$APP_KEY" ] && [ "$APP_KEY" != "base64:" ]; then
        return 0
    fi

    KEY_FILE="/var/www/html/storage/app/.app_key"

    if [ -f "$KEY_FILE" ]; then
        APP_KEY=$(cat "$KEY_FILE")
        export APP_KEY
        return 0
    fi

    APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
    export APP_KEY

    mkdir -p /var/www/html/storage/app
    echo "$APP_KEY" > "$KEY_FILE"
    chown www-data:www-data "$KEY_FILE"
    chmod 600 "$KEY_FILE"

    echo "=========================================="
    echo "WARNING: APP_KEY is missing in Dokploy."
    echo "Add to Dokploy Environment:"
    echo "APP_KEY=$APP_KEY"
    echo "=========================================="
}

resolve_app_key

# Laravel defaults to sqlite when DB_CONNECTION is unset — causes readonly DB errors in Docker.
if [ "${APP_ENV:-production}" = "production" ] && [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    echo "ERROR: DB_CONNECTION is sqlite (or unset)."
    echo "Set these in Dokploy Environment (use Railway PUBLIC TCP host/port):"
    echo "  DB_CONNECTION=mysql"
    echo "  DB_HOST=<RAILWAY_TCP_PROXY_DOMAIN>"
    echo "  DB_PORT=<RAILWAY_TCP_PROXY_PORT>"
    echo "  DB_DATABASE=truckfund"
    echo "  DB_USERNAME=root"
    echo "  DB_PASSWORD=<MYSQL_ROOT_PASSWORD>"
    exit 1
fi

mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache storage/app database
chown -R www-data:www-data storage bootstrap/cache database
chmod -R ug+rwx storage bootstrap/cache database

rm -f /var/www/html/database/database.sqlite 2>/dev/null || true

php artisan storage:link --force 2>/dev/null || true

php artisan package:discover --ansi

php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force --no-interaction
fi

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
