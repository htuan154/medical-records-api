FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    icu-dev oniguruma-dev libzip-dev zlib-dev \
    freetype-dev libjpeg-turbo-dev libpng-dev \
    bash git curl nano autoconf g++ make

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) pdo pdo_mysql mbstring intl zip gd bcmath opcache

# Redis ext (nếu bạn dùng cache/queue redis)
RUN pecl install redis && docker-php-ext-enable redis || true

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www
COPY opcache.ini /usr/local/etc/php/conf.d/opcache.ini

CMD ["php-fpm"]
