FROM php:8.2-apache

RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && a2dismod mpm_event mpm_worker && a2enmod mpm_prefork

WORKDIR /var/www/html
COPY . .