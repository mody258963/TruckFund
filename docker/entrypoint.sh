#!/bin/sh
set -e

cd /var/www/html

# Dokploy: paste REAL values in Environment (not placeholders like <RAILWAY_TCP_PROXY_DOMAIN>).
write_runtime_env() {
    ENV_FILE="/var/www/html/.env"

    APP_NAME_VAL="${APP_NAME:-TruckFund}"
    APP_ENV_VAL="${APP_ENV:-production}"
    APP_DEBUG_VAL="${APP_DEBUG:-false}"
    APP_URL_VAL="${APP_URL:-http://localhost}"
    APP_FORCE_HTTPS_VAL="${APP_FORCE_HTTPS:-}"

    if [ "$APP_ENV_VAL" = "production" ] && [ -z "$APP_FORCE_HTTPS_VAL" ]; then
        APP_FORCE_HTTPS_VAL="true"
    fi

    if [ "$APP_FORCE_HTTPS_VAL" = "true" ]; then
        case "$APP_URL_VAL" in
            http://*) APP_URL_VAL="https://${APP_URL_VAL#http://}" ;;
        esac
    fi

    ASSET_URL_VAL="${ASSET_URL:-$APP_URL_VAL}"

    DB_CONN="${DB_CONNECTION:-mysql}"
    DB_URL_VAL="${DB_URL:-${MYSQL_PUBLIC_URL:-${DATABASE_URL:-}}}"

    if [ -z "$DB_URL_VAL" ] && [ -n "$DB_HOST" ] && [ -n "$DB_PASSWORD" ]; then
        DB_URL_VAL="mysql://${DB_USERNAME:-root}:${DB_PASSWORD}@${DB_HOST}:${DB_PORT:-3306}/${DB_DATABASE:-truckfund}"
    fi

    {
        echo "APP_NAME=${APP_NAME_VAL}"
        echo "APP_ENV=${APP_ENV_VAL}"
        echo "APP_KEY=${APP_KEY}"
        echo "APP_DEBUG=${APP_DEBUG_VAL}"
        echo "APP_URL=${APP_URL_VAL}"
        echo "ASSET_URL=${ASSET_URL_VAL}"
        if [ -n "$APP_FORCE_HTTPS_VAL" ]; then
            echo "APP_FORCE_HTTPS=${APP_FORCE_HTTPS_VAL}"
        fi
        echo ""
        echo "LOG_CHANNEL=${LOG_CHANNEL:-stderr}"
        echo "LOG_LEVEL=${LOG_LEVEL:-warning}"
        echo ""
        echo "DB_CONNECTION=${DB_CONN}"
        if [ -n "$DB_URL_VAL" ]; then
            echo "DB_URL=${DB_URL_VAL}"
        fi
        echo "DB_HOST=${DB_HOST:-}"
        echo "DB_PORT=${DB_PORT:-3306}"
        echo "DB_DATABASE=${DB_DATABASE:-truckfund}"
        echo "DB_USERNAME=${DB_USERNAME:-root}"
        echo "DB_PASSWORD=${DB_PASSWORD:-}"
        echo ""
        echo "SESSION_DRIVER=${SESSION_DRIVER:-database}"
        echo "CACHE_STORE=${CACHE_STORE:-database}"
        echo "QUEUE_CONNECTION=${QUEUE_CONNECTION:-database}"
        echo "FILESYSTEM_DISK=${FILESYSTEM_DISK:-local}"
        echo ""
        echo "TRUCKFUND_HIGH_VALUE_SCORE=${TRUCKFUND_HIGH_VALUE_SCORE:-185}"
    } > "$ENV_FILE"

    chown www-data:www-data "$ENV_FILE"
    chmod 640 "$ENV_FILE"
}

APP_KEY=$(/usr/local/bin/generate-app-key.sh)
export APP_KEY
write_runtime_env

# Warn if DB still not configured (do not exit — avoids 502 crash loop)
if [ -z "${DB_URL:-}" ] && [ -z "${MYSQL_PUBLIC_URL:-}" ] && [ -z "${DATABASE_URL:-}" ]; then
    if [ -z "${DB_HOST:-}" ] || [ -z "${DB_PASSWORD:-}" ]; then
        echo "WARNING: Database env vars missing. In Dokploy Environment add either:"
        echo "  MYSQL_PUBLIC_URL=<full URL from Railway Connect tab>"
        echo "  OR: DB_CONNECTION=mysql, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD"
        echo "Use real values — not text in angle brackets < >"
    fi
fi

mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache storage/app/documents storage/app database
chown -R www-data:www-data storage bootstrap/cache database
chmod -R ug+rwx storage bootstrap/cache database

rm -f /var/www/html/database/database.sqlite 2>/dev/null || true

php artisan storage:link --force 2>/dev/null || true

php artisan package:discover --ansi

php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

if [ "${RUN_MIGRATE_FRESH:-false}" = "true" ]; then
    echo "RUN_MIGRATE_FRESH=true — dropping all tables and re-seeding..."
    php artisan migrate:fresh --force --no-interaction \
        && php artisan truckfund:seed-demo --no-interaction \
        || php artisan migrate:fresh --seed --force --no-interaction \
        || echo "WARNING: migrate:fresh / seed failed — check DB env"
elif [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force --no-interaction \
        || echo "WARNING: migrations failed — fix DB env and redeploy"
    if [ "${RUN_SEED_DATABASE:-false}" = "true" ]; then
        echo "RUN_SEED_DATABASE=true — seeding users and demo data..."
        php artisan truckfund:seed-demo --no-interaction \
            || php artisan db:seed --force --no-interaction \
            || echo "WARNING: seed failed"
    fi
fi

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
