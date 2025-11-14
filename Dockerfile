FROM php:8.1-apache

# Install PostgreSQL support only (faster build)
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite headers

# Configure Apache
RUN echo "<VirtualHost *:80>\n\
  ServerName localhost\n\
  DocumentRoot /var/www/html\n\
  <Directory /var/www/html/>\n\
    Options -Indexes +FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
    DirectoryIndex index.php index.html\n\
  </Directory>\n\
</VirtualHost>" > /etc/apache2/sites-available/000-default.conf

# Add security headers
RUN echo "Header always set X-Content-Type-Options nosniff\n\
Header always set X-Frame-Options DENY\n\
Header always set X-XSS-Protection \"1; mode=block\"" > /etc/apache2/conf-available/security-headers.conf \
    && a2enconf security-headers

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Create health check endpoint (FIXES THE ISSUE)
RUN echo "<?php http_response_code(200); echo 'OK'; ?>" > health.php

# Set permissions
RUN mkdir -p /var/www/html/data \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
