# --- Stage 1: build vendor ---
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# --- Stage 2: app image ---
FROM php:8.2-fpm-alpine

# Cài các dependencies của hệ thống
RUN apk add --no-cache nginx supervisor bash curl git libzip-dev icu-dev oniguruma-dev \
  && docker-php-ext-install pdo pdo_mysql mbstring intl zip opcache

# Copy source code vào container
ENV APP_DIR=/app
WORKDIR ${APP_DIR}

# Copy vendor từ Stage 1
COPY --from=vendor /app/vendor ${APP_DIR}/vendor

# Copy các file cấu hình từ thư mục docker/
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Laravel storage & permissions
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
 && chown -R www-data:www-data ${APP_DIR}

# Expose port mà Render cấp
EXPOSE 8080

# Khởi động cả PHP-FPM và Nginx với Supervisor
CMD ["/entrypoint.sh"]
