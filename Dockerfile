# =========================
# Stage 1: vendor (Composer chạy trên PHP 8.2)
# =========================
FROM php:8.2-fpm-alpine AS vendor

RUN apk add --no-cache bash curl git unzip libzip-dev icu-dev oniguruma-dev \
 && docker-php-ext-install pdo pdo_mysql mbstring intl zip opcache

# Cài Composer (chạy trong PHP 8.2)
RUN curl -sS https://getcomposer.org/installer \
  | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app
COPY composer.json composer.lock ./

RUN composer config platform.php 8.2.0 \
 && COMPOSER_ALLOW_SUPERUSER=1 composer install \
  --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts


# =========================
# Stage 2: runtime (php-fpm + nginx + supervisor)
# =========================
FROM php:8.2-fpm-alpine

RUN apk add --no-cache nginx supervisor bash curl git libzip-dev icu-dev oniguruma-dev \
 && docker-php-ext-install pdo pdo_mysql mbstring intl zip opcache

# PHP OPCache
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Nginx & Supervisor (CHÚ Ý: đúng path theo repo hiện tại)
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/supervisord.conf   /etc/supervisord.conf

# Ứng dụng
WORKDIR /app
COPY . /app

# Vendor từ stage vendor
COPY --from=vendor /app/vendor /app/vendor

# Entrypoint (CHÚ Ý: đúng path theo repo hiện tại)
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENV PORT=8080
EXPOSE 8080

CMD ["/entrypoint.sh"]
