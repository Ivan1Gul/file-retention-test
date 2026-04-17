FROM composer:2.9 AS composer

FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev netcat-openbsd \
    && docker-php-ext-install pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY docker/php/entrypoint.sh /usr/local/bin/project-entrypoint
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html

RUN chmod +x /usr/local/bin/project-entrypoint

ENTRYPOINT ["project-entrypoint"]
