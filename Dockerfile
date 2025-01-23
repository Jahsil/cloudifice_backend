FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git unzip curl libzip-dev libonig-dev libpng-dev libmcrypt-dev libpq-dev libjpeg-dev \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

COPY . .

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
    
EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]


# CMD [ "php", "artisan", "migrate","&&", "php", "artisan", "serve", "--host=0.0.0.0", "--port=8000" ]
