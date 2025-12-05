FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev unzip libonig-dev libxml2-dev \
    && docker-php-ext-install pdo_mysql

# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy project files
COPY . .

# Install PHP dependencies (NO npm)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Start outage detection loop
CMD ["bash", "-c", "while true; do php artisan detect:outages; sleep 60; done"]
