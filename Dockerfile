FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev \
    libpq-dev libzip-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Node.js 20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy seluruh project
COPY . .

# Install PHP dependencies (memory unlimited biar ga OOM)
RUN php -d memory_limit=-1 /usr/bin/composer install --no-dev --optimize-autoloader --no-interaction

# Install & build frontend assets
RUN npm ci && npm run build

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Konfigurasi Apache → arahkan ke /public
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' \
        /etc/apache2/sites-available/000-default.conf \
    && echo '<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>' \
        >> /etc/apache2/sites-available/000-default.conf \
    && a2enmod rewrite

EXPOSE 80

# Entrypoint: generate key, cache config, migrate, lalu start Apache
CMD bash -c "\
    php artisan key:generate --force && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    php artisan migrate --force && \
    apache2-foreground"