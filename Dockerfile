# ---------- Stage: vendor (composer chạy bằng PHP 8.2) ----------
FROM php:8.2-fpm-alpine AS vendor

# Lib cần để cài ext + chạy composer
RUN apk add --no-cache bash curl git unzip libzip-dev icu-dev oniguruma-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring intl zip opcache

# Cài Composer (chạy dưới PHP 8.2 của stage này)
RUN curl -sS https://getcomposer.org/installer \
    | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app
COPY composer.json composer.lock ./

# Khóa platform về 8.2 để tương thích nette/schema (≤ PHP 8.3)
RUN composer config platform.php 8.2.0 \
 && COMPOSER_ALLOW_SUPERUSER=1 composer install \
      --no-dev --no-interaction --prefer-dist --optimize-autoloader

# ---------- Stage: runtime ----------
FROM php:8.2-fpm-alpine

# Web server & supervisor + libs cho ext
RUN apk add --no-cache nginx supervisor bash curl git libzip-dev icu-dev oniguruma-dev \
 && docker-php-ext-install pdo pdo_mysql mbstring intl zip opcache

# OPCache
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Nginx & Supervisor configs (đúng path theo repo)
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY supervisord.conf /etc/supervisord.conf

# Ứng dụng
WORKDIR /app
COPY . /app

# Vendor đã build sẵn ở stage "vendor"
COPY --from=vendor /app/vendor /app/vendor

# Entrypoint
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Port cho Render/Heroku style
ENV PORT=8080
EXPOSE 8080

CMD ["/entrypoint.sh"]
