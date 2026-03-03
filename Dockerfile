FROM php:8.2-apache

# Install extensions
RUN docker-php-ext-install pdo pdo_mysql

# Enable mod_rewrite
RUN a2enmod rewrite headers

# Create storage directory
RUN mkdir -p /storage/data && chmod 777 /storage/data

# Copy files
COPY . /var/www/html/

# Set permissions
RUN chmod -R 755 /var/www/html/ \
    && chmod +x /var/www/html/*.sh \
    && chmod 666 /var/www/html/api.php

# Configure Apache
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

EXPOSE 80
CMD ["bash", "start.sh"]
