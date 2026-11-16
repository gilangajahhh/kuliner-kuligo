# Build the frontend assets with the same Node version required by Vite 7.
FROM node:22-alpine AS assets
WORKDIR /app
COPY . .
RUN npm ci && npm run build

# Install production PHP dependencies.
FROM composer:2 AS vendor
WORKDIR /app
COPY . .
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Run Laravel with Apache. Render's default web service port is 10000.
FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libpq-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql mbstring exif pcntl bcmath gd zip opcache \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY --from=vendor /app /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build

RUN a2enmod rewrite headers \
    && printf 'Listen 10000\n' > /etc/apache2/ports.conf \
    && printf '%s\n' \
        '<VirtualHost *:10000>' \
        '    DocumentRoot /var/www/html/public' \
        '    <Directory /var/www/html/public>' \
        '        AllowOverride All' \
        '        Require all granted' \
        '    </Directory>' \
        '    ErrorLog ${APACHE_LOG_DIR}/error.log' \
        '    CustomLog ${APACHE_LOG_DIR}/access.log combined' \
        '</VirtualHost>' \
        > /etc/apache2/sites-available/000-default.conf \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/public bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 10000

# Runtime environment variables are supplied by the Render dashboard.
CMD ["sh", "-c", "php artisan config:cache && exec apache2-foreground"]
