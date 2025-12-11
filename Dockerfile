FROM php:8.2-apache

# Enable Apache rewrite module
RUN a2enmod rewrite

# Set Apache DocumentRoot
ENV APACHE_DOCUMENT_ROOT=/var/www/html

# Allow .htaccess overrides everywhere
RUN sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Install PostgreSQL extension
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Copy project to Apache root
COPY . /var/www/html/

# Expose Railway port
EXPOSE 8080

CMD ["apache2-foreground"]
