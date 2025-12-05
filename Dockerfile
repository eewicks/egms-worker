FROM php:8.2-apache

# Enable Apache rewrite
RUN a2enmod rewrite

# Fix ServerName warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Install system packages + Node.js for Vite
RUN apt-get update && apt-get install -y \
    zip unzip git curl libpng-dev libonig-dev libxml2-dev libzip-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring zip exif pcntl gd \
    && curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

WORKDIR /var/www/html

# Copy project
COPY . .

# Install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install backend dependencies
RUN composer install --no-dev --optimize-autoloader

# Build Vite assets
RUN npm install
RUN npm run build

# Fix folder permissions
RUN chown -R www-data:www-data storage bootstrap/cache

# Set Apache to point to public folder
RUN sed -i 's#/var/www/html#/var/www/html/public#' /etc/apache2/sites-available/000-default.conf

EXPOSE 8080

# STARTUP COMMANDS – RUN AFTER ENV EXISTS
CMD php artisan storage:link || true \
    && php artisan config:clear \
    && php artisan view:clear \
    && php artisan route:clear \
    && sed -i "s/80/${PORT}/" /etc/apache2/ports.conf \
    && sed -i "s/:80/:${PORT}/" /etc/apache2/sites-enabled/000-default.conf \
    && apachectl -D FOREGROUND
