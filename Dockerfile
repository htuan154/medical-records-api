# =========================
# Stage 1: build vendor
# =========================
# Dùng composer chạy trên PHP 8.2 để khớp với runtime (tránh nette/schema <= 8.3 lỗi)
FROM composer:2.7-php8.2 AS vendor
ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /app

COPY composer.json composer.lock ./
# Cài deps theo lock (không cần --ignore-platform-req vì đã là PHP 8.2)
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader


# =========================
# Stage 2: runtime (php-fpm + nginx + supervisor)
# =========================
FROM php:8.2-fpm-alpine

# Packages & PHP extensions
RUN apk add --no-cache \
      nginx supervisor bash curl git \
      libzip-dev icu-dev oniguruma-dev \
  && docker-php-ext-install pdo pdo_mysql mbstring intl zip opcache

# Thư mục ứng dụng
ENV APP_DIR=/app
WORKDIR ${APP_DIR}

# Copy toàn bộ source
COPY . ${APP_DIR}
# Copy vendor đã build ở stage 1
COPY --from=vendor /app/vendor ${APP_DIR}/vendor

# ---- Nginx / PHP / Supervisor config ----
# Lưu ý: default.conf phải dùng 'listen __PORT__' và root /app/public
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/php/opcache.ini     /usr/local/etc/php/conf.d/opcache.ini
COPY docker/supervisord.conf    /etc/supervisord.conf
COPY docker/entrypoint.sh       /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Laravel: đảm bảo folder & quyền
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
 && chown -R www-data:www-data ${APP_DIR}

# Render cấp $PORT động → expose placeholder
EXPOSE 8080

# Khởi động: entrypoint sẽ thay __PORT__ bằng $PORT và cache config/route/view
CMD ["/entrypoint.sh"]
