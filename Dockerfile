FROM php:8.2-cli

# Install PostgreSQL extension
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Set working directory
WORKDIR /app

# Copy semua file project
COPY . .

# Jalankan server PHP
CMD ["php", "-S", "0.0.0.0:8080"]