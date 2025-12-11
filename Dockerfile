FROM php:8.2-apache

# Enable Apache rewrite
RUN a2enmod rewrite

# Install PostgreSQL extension
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Create custom vhost directory
RUN mkdir /etc/apache2/sites-custom

# Copy your application
COPY . /var/www/html/

# Copy virtual host config
COPY vhosts.conf /etc/apache2/sites-custom/vhosts.conf

# Load vhosts
RUN echo "Include /etc/apache2/sites-custom/vhosts.conf" >> /etc/apache2/apache2.conf

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Railway uses PORT=8080
RUN echo "Listen 8080" >> /etc/apache2/ports.conf

EXPOSE 8080

CMD ["apache2-foreground"]
