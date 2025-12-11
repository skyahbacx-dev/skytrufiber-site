FROM php:8.2-apache

# Install dependencies + PostgreSQL extension
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Enable URL rewriting
RUN a2enmod rewrite

# Allow .htaccess to override Apache config
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Expose Railway port
EXPOSE 8080

# Start Apache
CMD ["apache2-foreground"]
