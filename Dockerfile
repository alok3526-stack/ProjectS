FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip git libssl-dev pkg-config \
    && (pecl install mongodb-1.21.0 || pecl install mongodb-1.20.1) \
    && docker-php-ext-enable mongodb \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . /var/www/html/

RUN composer install --no-dev --optimize-autoloader

RUN mkdir -p /var/www/html/data \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/data

EXPOSE 80
