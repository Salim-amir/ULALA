FROM php:8.2-cli

# Install PostgreSQL extension
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

WORKDIR /app
COPY . .

# 🔥 pakai port tetap
CMD sh -c "php -S 0.0.0.0:${PORT:-8080}"