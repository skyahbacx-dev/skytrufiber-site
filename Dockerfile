# Use an official PHP image
FROM php:8.2-apache

# Copy all project files into the container
COPY . /var/www/html/

# Expose port 10000 for Render
EXPOSE 10000

# Start PHP’s built-in server
CMD ["php", "-S", "0.0.0.0:10000", "-t", "."]
