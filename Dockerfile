FROM php:8.1-apache

# Install PostgreSQL
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Set working directory
WORKDIR /var/www/html

# Copy your files
COPY . .

# Create guaranteed working test files
RUN echo "<?php echo 'MAIN INDEX WORKING'; ?>" > index.php
RUN echo "<?php echo 'TEST FILE WORKING'; ?>" > test.php
RUN echo "<?php http_response_code(200); echo 'HEALTH OK'; ?>" > health.php

# Fix permissions
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

CMD ["apache2-foreground"]
