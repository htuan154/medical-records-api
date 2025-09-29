# --- Stage 1: build vendor ---
FROM composer:2 AS vendor
ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /app
COPY composer.json composer.lock ./

# Quan trọng: ép platform PHP về 8.2 để khớp runtime, tránh lỗi nette/schema (<= 8.3)
RUN composer config platform.php 8.2.0 \
 && composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# --- Stage 2: runtime (php-fpm + nginx + supervisor) ---
FROM php:8.2-fpm-alpine

# Packages & PHP extensions
RUN apk add --no-cache nginx supervisor bash curl git libzip-dev icu-dev oniguruma-dev \
  && docker-php-ext-install pdo pdo_mysql mbstring intl zip opcache

# App path
ENV APP_DIR=/app
WORKDIR ${APP_DIR}

# Copy source
COPY . ${APP_DIR}
# Copy vendor từ stage 1
COPY --from=vendor /app/vendor ${APP_DIR}/vendor

# Copy config
# NOTE: default.conf nên trỏ root /app/public và listen __PORT__ (sẽ được entrypoint thay bằng $PORT)
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Laravel dirs & perms
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
 && chown -R www-data:www-data ${APP_DIR}

EXPOSE 8080
CMD ["/entrypoint.sh"]
