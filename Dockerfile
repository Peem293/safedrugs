# Menggunakan PHP 8.4 versi FPM
FROM php:8.4-fpm

# Install dependensi sistem
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Atur direktori kerja
WORKDIR /var/www/html

# Salin source code
COPY . .

# Set permission agar PHP-FPM bisa menulis ke storage
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache