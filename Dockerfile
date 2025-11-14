FROM php:8.1-apache

# Install PostgreSQL
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Enable rewrite module
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy your application files
COPY . .

# Create health check endpoint
RUN echo "<?php http_response_code(200); echo 'OK'; ?>" > health.php

# Fix permissions
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

CMD ["apache2-foreground"]
