FROM php:8.2-fpm-alpine

# Install system dependencies & PHP extensions yang dibutuhkan Laravel/PostgreSQL
RUN apk update && apk add --no-cache \
    libpq-dev \
    libpng-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-install pdo pdo_pgsql
    && docker-php-ext-configure gd --with-freetype --with-jpeg \

# Ambil Composer resmi
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy seluruh source code project dari Git ke dalam container
COPY . .

# Install dependencies vendor secara otomatis saat build
RUN composer install --no-dev --optimize-autoloader

# Buat folder yang sering hilang di Git & buka permission-nya secara otomatis
RUN mkdir -p storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    && chmod -R 775 storage bootstrap/cache public \
    && chown -R www-data:www-data /var/www/html

EXPOSE 9000
CMD ["php-fpm"]