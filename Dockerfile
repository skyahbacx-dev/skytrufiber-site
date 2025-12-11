FROM php:8.2-apache

# Enable Apache rewrite module
RUN a2enmod rewrite

# Install PostgreSQL extension
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Allow .htaccess overrides
RUN sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

WORKDIR /var/www/html
COPY . .

EXPOSE 8080
CMD ["apache2-foreground"]
