#!/bin/sh
set -e

cd /var/www/html

set_env_value() {
    key="$1"
    value="$2"
    escaped_value=$(printf '%s\n' "$value" | sed 's/[\/&]/\\&/g')

    if grep -q "^${key}=" .env; then
        sed -i "s|^${key}=.*|${key}=${escaped_value}|" .env
    else
        printf '\n%s=%s\n' "$key" "$value" >> .env
    fi
}

if [ ! -f .env ]; then
    cp .env.example .env
fi

set_env_value APP_NAME "${APP_NAME:-File Retention Test}"
set_env_value APP_ENV "${APP_ENV:-local}"
set_env_value APP_DEBUG "${APP_DEBUG:-true}"
set_env_value APP_URL "${APP_URL:-http://localhost:8088}"
set_env_value DB_CONNECTION "${DB_CONNECTION:-mysql}"
set_env_value DB_HOST "${DB_HOST:-mysql}"
set_env_value DB_PORT "${DB_PORT:-3306}"
set_env_value DB_DATABASE "${DB_DATABASE:-file_retention}"
set_env_value DB_USERNAME "${DB_USERNAME:-laravel}"
set_env_value DB_PASSWORD "${DB_PASSWORD:-secret}"
set_env_value SESSION_DRIVER "${SESSION_DRIVER:-file}"
set_env_value CACHE_STORE "${CACHE_STORE:-file}"
set_env_value QUEUE_CONNECTION "${QUEUE_CONNECTION:-sync}"
set_env_value FILE_DELETION_NOTIFICATION_EMAIL "${FILE_DELETION_NOTIFICATION_EMAIL:-qa@example.com}"
set_env_value RABBITMQ_HOST "${RABBITMQ_HOST:-rabbitmq}"
set_env_value RABBITMQ_PORT "${RABBITMQ_PORT:-5672}"
set_env_value RABBITMQ_USER "${RABBITMQ_USER:-guest}"
set_env_value RABBITMQ_PASSWORD "${RABBITMQ_PASSWORD:-guest}"
set_env_value RABBITMQ_VHOST "${RABBITMQ_VHOST:-/}"
set_env_value RABBITMQ_EXCHANGE "${RABBITMQ_EXCHANGE:-file-events}"
set_env_value RABBITMQ_QUEUE "${RABBITMQ_QUEUE:-file-deletion-notifications}"
set_env_value RABBITMQ_ROUTING_KEY "${RABBITMQ_ROUTING_KEY:-file.deleted}"

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

if [ -n "${DB_HOST}" ] && [ -n "${DB_PORT}" ]; then
    until nc -z "${DB_HOST}" "${DB_PORT}"; do
        echo "Waiting for MySQL at ${DB_HOST}:${DB_PORT}..."
        sleep 2
    done
fi

if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
fi

php artisan config:clear
php artisan cache:clear

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

exec "$@"
