FROM php:8.2-apache

# Enable Apache rewrite
RUN a2enmod rewrite

# Install PostgreSQL
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Create custom vhosts directory
RUN mkdir /etc/apache2/sites-custom

# Copy main project files
COPY . /var/www/html/

# Copy our virtualhost config
COPY vhosts.conf /etc/apache2/sites-custom/vhosts.conf

# Include it in Apache
RUN echo "Include /etc/apache2/sites-custom/vhosts.conf" >> /etc/apache2/apache2.conf

# Change permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Railway needs Apache to listen on 8080
RUN echo "Listen 8080" >> /etc/apache2/ports.conf

EXPOSE 8080

CMD ["apache2-foreground"]
