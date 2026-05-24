FROM php:8.2-apache

# Install required system packages and PHP extensions
RUN apt-get update && apt-get install -y zip unzip git libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy all backend files
COPY . .

# Point Apache to the Laravel public folder
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN a2enmod rewrite

# Install PHP dependencies
RUN composer install

# Give Laravel permission to write to storage files
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
