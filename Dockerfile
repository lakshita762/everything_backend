FROM php:8.2-fpm
WORKDIR /var/www/html

RUN apt-get update && apt-get install -y git unzip libzip-dev zlib1g-dev
RUN docker-php-ext-install pdo pdo_mysql zip

COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html

# Install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

EXPOSE 9000
CMD ["php-fpm"]

# Deployment notes:
# - Run Redis for pub/sub in production (managed Azure Redis Cache or AWS ElastiCache).
# - Scale app instances behind a load balancer. Use Redis pub/sub to broadcast live location updates across instances.
# - Consider moving history to a time-series DB for long retention.
