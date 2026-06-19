# Use the official PHP 8.2 image with Apache
FROM php:8.2-apache

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql zip

# Enable Apache mod_rewrite (often useful for PHP APIs)
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application files into the container
COPY . /var/www/html/

# Install Composer globally
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install PHP dependencies if composer.json exists
# We use a trick to only run if the file is present
RUN if [ -f "composer.json" ]; then composer install --no-dev --optimize-autoloader; fi

# Fix permissions for the web server
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 80 for Render to route traffic
EXPOSE 80

# By default, Render will run Apache automatically from the base image
