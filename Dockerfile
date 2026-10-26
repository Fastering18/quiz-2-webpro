# syntax=docker/dockerfile:1
# Composer image only resolves vendor/ (platform checks ignored here; final image is PHP 8.4)
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts \
    --prefer-dist --optimize-autoloader --ignore-platform-reqs

# PHP 8.4 required: composer.lock dependencies need PHP >= 8.4.1
FROM php:8.4-apache

RUN apt-get update \
 && apt-get install -y --no-install-recommends libpq-dev libsqlite3-dev libzip-dev libonig-dev unzip \
 && docker-php-ext-install pdo_pgsql pdo_sqlite zip mbstring bcmath opcache \
 && a2enmod rewrite headers deflate \
 && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public \
    PORT=10000
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
 && sed -ri 's!Listen 80!Listen ${PORT}!' /etc/apache2/ports.conf \
 && sed -ri 's!<VirtualHost \*:80>!<VirtualHost *:${PORT}>!' /etc/apache2/sites-available/000-default.conf \
 && echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf \
 && a2enconf servername \
 && printf 'opcache.enable=1\nopcache.enable_cli=0\nopcache.validate_timestamps=0\nopcache.memory_consumption=128\nopcache.interned_strings_buffer=16\nopcache.max_accelerated_files=10000\n' > /usr/local/etc/php/conf.d/opcache.ini \
 && printf 'upload_max_filesize=12M\npost_max_size=14M\nmemory_limit=256M\n' > /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
 && composer dump-autoload --no-dev --optimize --no-interaction \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R ug+rwX storage bootstrap/cache \
 && chmod +x docker/entrypoint.sh

EXPOSE 10000
ENTRYPOINT ["docker/entrypoint.sh"]
CMD ["apache2-foreground"]
