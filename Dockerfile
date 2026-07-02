# Gunakan versi yang lebih stabil jika 8.4-fpm-alpine mengalami masalah update
FROM php:8.4-fpm-alpine

# Update repository dan install dependensi dalam satu baris untuk menghindari cache error
RUN apk update && \
    apk add --no-cache \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git

# Install ekstensi PHP
RUN docker-php-ext-install pdo pdo_pgsql zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

# Set permission
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]