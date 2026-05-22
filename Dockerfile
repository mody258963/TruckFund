# syntax=docker/dockerfile:1

# ------------------------------------------------------------------------------
# Stage 1: PHP dependencies
# ------------------------------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

COPY . .

# Do not run artisan during build (no APP_KEY / .env yet — hangs on package:discover).
RUN composer dump-autoload --optimize --classmap-authoritative --no-scripts

# ------------------------------------------------------------------------------
# Stage 2: Frontend assets (Vite + Flux)
# ------------------------------------------------------------------------------
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
COPY --from=vendor /app/vendor ./vendor

RUN npm run build

# ------------------------------------------------------------------------------
# Stage 3: Production image (Nginx + PHP-FPM)
# ------------------------------------------------------------------------------
FROM php:8.3-fpm-alpine AS production

LABEL org.opencontainers.image.title="TruckFund"
LABEL org.opencontainers.image.description="Laravel TruckFund application"

RUN apk add --no-cache \
    nginx \
    supervisor \
    wget \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        intl \
        opcache \
        zip \
    && rm -rf /var/cache/apk/*

COPY docker/php.ini /usr/local/etc/php/conf.d/99-truckfund.ini
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p /var/www/html /run/nginx \
    && chown -R www-data:www-data /var/www/html

WORKDIR /var/www/html

COPY --from=vendor --chown=www-data:www-data /app .
COPY --from=frontend --chown=www-data:www-data /app/public/build ./public/build

RUN mkdir -p storage/app/documents storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    RUN_MIGRATIONS=true

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD wget -qO- http://127.0.0.1/up || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
