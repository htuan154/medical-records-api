# ---------- Stage: vendor (chạy composer bằng PHP 8.2) ----------
FROM php:8.2-fpm-alpine AS vendor

# Cài packages để build ext và chạy composer
RUN apk add --no-cache bash curl git unzip libzip-dev icu-dev oniguruma-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring intl zip opcache

# Cài Composer (chạy dưới PHP 8.2 của stage này)
RUN curl -sS https://getcomposer.org/installer \
    | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app
COPY composer.json composer.lock ./

# Ép platform về 8.2 để lockfile nhất quán
RUN composer config platform.php 8.2.0 \
 && COMPOSER_ALLOW_SUPERUSER=1 composer install \
      --no-dev --no-interaction --prefer-dist --optimize-autoloader

# ---------- Stage: runtime ----------
FROM php:8.2-fpm-alpine

# Web + process supervisor + build deps cho ext
RUN apk add --no-cache nginx supervisor bash curl git libzip-dev icu-dev oniguruma-dev \
 && docker-php-ext-install pdo pdo_mysql mbstring intl zip opcache

# OPCache tối ưu
COPY opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Nginx & Supervisor
COPY default.conf /etc/nginx/conf.d/default.conf
COPY supervisord.conf /etc/supervisord.conf

# Ứng dụng
WORKDIR /app
COPY . /app
# Copy vendor đã build sẵn từ stage "vendor"
COPY --from=vendor /app/vendor /app/vendor

# Entrypoint: set PORT, cache artisan, chạy php-fpm + nginx qua supervisor
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENV PORT=8080
EXPOSE 8080
CMD ["/entrypoint.sh"]
