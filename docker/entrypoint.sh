#!/usr/bin/env bash
set -e

# Lấy cổng từ môi trường Render
PORT_TO_USE="${PORT:-8080}"

# Cập nhật nginx.conf với cổng mới
sed -i "s/__PORT__/${PORT_TO_USE}/g" /etc/nginx/conf.d/default.conf

# Khởi tạo các config Laravel (như cache, routes, storage link...)
php -v
php artisan key:generate --force || true
php artisan storage:link || true
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Bắt đầu Supervisor (cả PHP-FPM và Nginx)
exec /usr/bin/supervisord -c /etc/supervisord.conf
