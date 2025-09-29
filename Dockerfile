# =========================
# Stage 1: vendor (Composer chạy trong PHP 8.2)
# =========================
FROM php:8.2-fpm-alpine AS vendor

# Lib để build extension + composer chạy
RUN apk add --no-cache bash curl git unzip libzip-dev icu-dev oniguruma-dev \
 && docker-php-ext-install pdo pdo_mysql mbstring intl zip opcache

# Cài Composer (chạy dưới PHP 8.2)
RUN curl -sS https://getcomposer.org/installer \
  | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app
COPY composer.json composer.lock ./

# Khóa platform về 8.2 cho chắc (tránh nette/schema > 8.3)
RUN composer config platform.php 8.2.0 \
 && COMPOSER_ALLOW_SUPERUSER=1 composer install \
      --no-dev --no-interaction --prefer-dist --optimize-autoloader

# =========================
# Stage 2: runtime (php-fpm + nginx + supervisor)
# =========================
FROM php:8.2-fpm-alpine

# Web server + supervisor + ext
RUN apk add --no-cache nginx supervisor bash curl git libzip-dev icu-dev oniguruma-dev \
 && docker-php-ext-install pdo pdo_mysql mbstring intl zip opcache

# OPCache
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Nginx & Supervisor
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY supervisord.conf /etc/supervisord.conf

# Ứng dụng
WORKDIR /app
COPY . /app

# Vendor từ stage vendor
COPY --from=vendor /app/vendor /app/vendor

# Entrypoint
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Render/Heroku-style PORT
ENV PORT=8080
EXPOSE 8080

CMD ["/entrypoint.sh"]
