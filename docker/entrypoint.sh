#!/usr/bin/env bash
set -e

PORT_TO_USE="${PORT:-8080}"
sed -i "s/__PORT__/${PORT_TO_USE}/g" /etc/nginx/conf.d/default.conf

# Ensure storage and cache are writable (for logging, views, cache, etc.)
umask 002
mkdir -p /app/storage/logs /app/storage/framework/{cache,data,sessions,views} /app/storage/api-docs /app/bootstrap/cache
touch /app/storage/logs/laravel.log || true
# First try proper ownership for php-fpm user
chown -R www-data:www-data /app/storage /app/bootstrap/cache || true
chmod -R ug+rwX /app/storage /app/bootstrap/cache || true
# Fallback: in case of UID/GID mismatch on some platforms, force open perms
chmod -R 777 /app/storage /app/bootstrap/cache || true

php -v
php artisan package:discover --ansi || true
# Only generate key if not provided via env (Render should provide APP_KEY)
if [ -z "${APP_KEY}" ]; then
	php artisan key:generate --force || true
fi
php artisan storage:link || true
php artisan config:clear || true
php artisan cache:clear || true
php artisan config:cache || true
# Pre-generate Swagger docs to avoid first-hit 500 if the folder is empty or permissions were missing
php artisan l5-swagger:generate || true
php artisan route:cache || true
php artisan view:cache || true

exec /usr/bin/supervisord -c /etc/supervisord.conf
