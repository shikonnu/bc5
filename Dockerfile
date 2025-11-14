FROM php:8.1-apache
RUN apt-get update && apt-get install -y libpq-dev && docker-php-ext-install pdo pdo_pgsql
RUN a2enmod rewrite
WORKDIR /var/www/html
COPY . .
RUN echo "<?php http_response_code(200); echo 'OK'; ?>" > health.php
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html
CMD ["apache2-foreground"]
