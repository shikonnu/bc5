FROM php:8.1-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    gd

# Enable Apache mod_rewrite
RUN a2enmod rewrite \
    && a2enmod headers

# Configure Apache completely (NO .htaccess needed)
RUN echo "<VirtualHost *:80>" > /etc/apache2/sites-available/000-default.conf \
    && echo "  ServerName localhost" >> /etc/apache2/sites-available/000-default.conf \
    && echo "  DocumentRoot /var/www/html" >> /etc/apache2/sites-available/000-default.conf \
    && echo "  <Directory /var/www/html/>" >> /etc/apache2/sites-available/000-default.conf \
    && echo "    Options -Indexes +FollowSymLinks" >> /etc/apache2/sites-available/000-default.conf \
    && echo "    AllowOverride All" >> /etc/apache2/sites-available/000-default.conf \
    && echo "    Require all granted" >> /etc/apache2/sites-available/000-default.conf \
    && echo "    DirectoryIndex index.php index.html" >> /etc/apache2/sites-available/000-default.conf \
    && echo "    RewriteEngine On" >> /etc/apache2/sites-available/000-default.conf \
    && echo "    RewriteCond %{REQUEST_FILENAME} !-f" >> /etc/apache2/sites-available/000-default.conf \
    && echo "    RewriteCond %{REQUEST_FILENAME} !-d" >> /etc/apache2/sites-available/000-default.conf \
    && echo "    RewriteRule ^(.*)$ index.php [QSA,L]" >> /etc/apache2/sites-available/000-default.conf \
    && echo "  </Directory>" >> /etc/apache2/sites-available/000-default.conf \
    && echo "  ErrorLog \${APACHE_LOG_DIR}/error.log" >> /etc/apache2/sites-available/000-default.conf \
    && echo "  CustomLog \${APACHE_LOG_DIR}/access.log combined" >> /etc/apache2/sites-available/000-default.conf \
    && echo "</VirtualHost>" >> /etc/apache2/sites-available/000-default.conf

# Add security headers to Apache config
RUN echo "Header always set X-Content-Type-Options nosniff" >> /etc/apache2/conf-available/security-headers.conf \
    && echo "Header always set X-Frame-Options DENY" >> /etc/apache2/conf-available/security-headers.conf \
    && echo "Header always set X-XSS-Protection \"1; mode=block\"" >> /etc/apache2/conf-available/security-headers.conf \
    && a2enconf security-headers

# Set working directory
WORKDIR /var/www/html

# Copy application files FIRST
COPY . .

# THEN set permissions (files exist now)
RUN mkdir -p /var/www/html/data \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && find . -type f -name "*.php" -exec chmod 644 {} \; 2>/dev/null || true \
    && find . -type f -name "*.html" -exec chmod 644 {} \; 2>/dev/null || true

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]