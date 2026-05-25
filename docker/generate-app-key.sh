#!/bin/sh
# Resolve APP_KEY for Docker/Dokploy: use env, persisted file, or generate a new one.
set -e

APP_ROOT="${APP_ROOT:-/var/www/html}"
KEY_FILE="${APP_KEY_FILE:-$APP_ROOT/storage/app/.app_key}"

is_valid_key() {
    [ -n "$1" ] && [ "$1" != "base64:" ]
}

if is_valid_key "$APP_KEY"; then
    printf '%s\n' "$APP_KEY"
    exit 0
fi

if [ -f "$KEY_FILE" ]; then
    KEY=$(tr -d '\r\n' < "$KEY_FILE")
    if is_valid_key "$KEY"; then
        printf '%s\n' "$KEY"
        exit 0
    fi
fi

KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"

mkdir -p "$(dirname "$KEY_FILE")"
printf '%s\n' "$KEY" > "$KEY_FILE"
chown www-data:www-data "$KEY_FILE" 2>/dev/null || true
chmod 600 "$KEY_FILE"

echo "INFO: APP_KEY auto-generated and saved to $KEY_FILE" >&2
echo "INFO: Add to Dokploy Environment (recommended): APP_KEY=$KEY" >&2
printf '%s\n' "$KEY"
