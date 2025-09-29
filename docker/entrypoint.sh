#!/usr/bin/env bash
set -e

PORT_TO_USE="${PORT:-8080}"
sed -i "s/__PORT__/${PORT_TO_USE}/g" /etc/nginx/conf.d/default.conf

php -v
php artisan key:generate --force || true
php artisan storage:link || true
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

exec /usr/bin/supervisord -c /etc/supervisord.conf
