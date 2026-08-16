# syntax=docker/dockerfile:1

# ------------------------------------------------------------------------------
# Stage 1: PHP dependencies
# ------------------------------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

# ext-gd is required by mpdf but absent from the composer image; it is installed
# in the production stage, and nothing here executes the downloaded code.
#
# Anonymous GitHub downloads are rate limited (HTTP 429), so keep the parallel
# download count low, retry with backoff, and reuse the cache between attempts.
# Pass `--secret id=github_token,env=GITHUB_TOKEN` to the build to raise the limit.
ENV COMPOSER_CACHE_DIR=/tmp/composer-cache \
    COMPOSER_MAX_PARALLEL_HTTP=6

RUN --mount=type=cache,target=/tmp/composer-cache \
    --mount=type=secret,id=github_token \
    set -eu; \
    if [ -s /run/secrets/github_token ]; then \
        composer config --global --auth github-oauth.github.com "$(cat /run/secrets/github_token)"; \
    fi; \
    attempt=1; \
    until composer install \
            --no-dev \
            --no-interaction \
            --no-scripts \
            --prefer-dist \
            --optimize-autoloader \
            --ignore-platform-req=ext-gd; do \
        if [ "$attempt" -ge 5 ]; then \
            echo "composer install failed after $attempt attempts" >&2; \
            exit 1; \
        fi; \
        delay=$((attempt * 20)); \
        echo "composer install attempt $attempt failed; retrying in ${delay}s" >&2; \
        sleep "$delay"; \
        attempt=$((attempt + 1)); \
    done

COPY . .

# Do not run artisan during build (no APP_KEY / .env yet — hangs on package:discover).
RUN composer dump-autoload --optimize --classmap-authoritative --no-scripts --ignore-platform-req=ext-gd

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

LABEL org.opencontainers.image.title="AutoFund"
LABEL org.opencontainers.image.description="Laravel AutoFund application"

RUN apk add --no-cache \
    nginx \
    supervisor \
    wget \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    freetype-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        intl \
        opcache \
        zip \
        gd \
    && rm -rf /var/cache/apk/*

COPY docker/php.ini /usr/local/etc/php/conf.d/99-truckfund.ini
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/generate-app-key.sh /usr/local/bin/generate-app-key.sh

RUN chmod +x /usr/local/bin/entrypoint.sh /usr/local/bin/generate-app-key.sh \
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
