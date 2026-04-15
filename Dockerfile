FROM php:8.1-apache

# Install PostgreSQL extension
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Aktifkan rewrite (opsional tapi bagus)
RUN a2enmod rewrite

# Copy project
WORKDIR /var/www/html
COPY . .

# Expose port (Apache default)
EXPOSE 80