# syntax=docker/dockerfile:1
# FrankenPHP 1.13 with PHP 8.5 includes the patched PHP build (PHP2Shell).
FROM dunglas/frankenphp:1.13-php8.5

RUN install-php-extensions pcntl pdo_pgsql zip bcmath opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts \
    --prefer-dist --optimize-autoloader --classmap-authoritative --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-interaction \
 && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
 && printf '%s\n' \
      'opcache.enable=1' \
      'opcache.enable_cli=1' \
      'opcache.validate_timestamps=0' \
      'opcache.memory_consumption=96' \
      'opcache.interned_strings_buffer=16' \
      'opcache.max_accelerated_files=20000' \
      'opcache.revalidate_freq=0' \
      > /usr/local/etc/php/conf.d/opcache.ini \
 && printf '%s\n' \
      'upload_max_filesize=64M' \
      'post_max_size=72M' \
      'memory_limit=192M' \
      'max_execution_time=120' \
      > /usr/local/etc/php/conf.d/uploads.ini \
 && chmod -R ug+rwX storage bootstrap/cache \
 && chmod +x docker/entrypoint.sh

EXPOSE 10000
ENTRYPOINT ["docker/entrypoint.sh"]
