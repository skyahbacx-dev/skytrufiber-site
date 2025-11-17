FROM php:8.2-cli

WORKDIR /app
COPY . .

RUN docker-php-ext-install pdo pgsql pdo_pgsql

CMD ["php", "-S", "0.0.0.0:8080", "-t", "."]
