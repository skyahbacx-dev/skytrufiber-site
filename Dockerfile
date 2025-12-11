# Use official PHP 8.2 + Apache image
FROM php:8.2-apache

# Enable required Apache modules
RUN a2enmod rewrite

# Install PostgreSQL extension
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Set Apache document root (public files)
ENV APACHE_DOCUMENT_ROOT /var/www/html

# Apply document root to Apache config
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/000-default.conf

# Copy your full project
COPY . /var/www/html/

# Permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Expose Railway default port
EXPOSE 8080

# Force Apache to listen on Railway's PORT
RUN echo "Listen 8080" >> /etc/apache2/ports.conf

CMD ["apache2-foreground"]
