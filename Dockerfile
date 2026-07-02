FROM php:8.4-fpm-alpine

# Install dependensi sistem yang dibutuhkan PHP-FPM
RUN apk add --no-cache libpq-dev zip unzip git
RUN docker-php-ext-install pdo pdo_pgsql zip

# Copy composer dari official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

# Pastikan permission benar
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]