FROM php:8.2-cli

RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

WORKDIR /app
COPY . .

RUN chmod +x start.sh

CMD ["/bin/sh", "start.sh"]