# Stage 1: Composer
FROM composer:2 AS composer
WORKDIR /app

# Needed for simplesoftwareio/simple-qrcode (ext-gd)
RUN apk add --no-cache \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts
COPY . .
RUN composer dump-autoload --optimize

# Stage 2: App
FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    nginx \
    bash \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    zip \
    unzip \
    curl

RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    gd \
    zip

RUN { \
    echo "upload_max_filesize=10M"; \
    echo "post_max_size=10M"; \
    echo "max_file_uploads=20"; \
} > /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www
COPY --from=composer /app /var/www

# Nginx config
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

RUN mkdir -p /run/nginx /var/lib/nginx/tmp \
    && chown -R www-data:www-data /var/www

EXPOSE 8080

CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]