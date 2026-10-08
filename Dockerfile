FROM php:8.2-apache
RUN apt-get update && apt-get install -y unzip && pecl install mongodb && docker-php-ext-enable mongodb
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY . /var/www/html/
RUN composer install
EXPOSE 80