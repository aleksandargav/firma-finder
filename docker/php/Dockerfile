FROM php:8.5-fpm

# Start from PHP's recommended development settings.
RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Host user and group IDs, so files created inside the container
# (logs, cache, storage) belong to the developer on the host.
ARG UID=1000
ARG GID=1000

# System libraries needed to compile the PHP extensions below,
# plus unzip and git, which Composer uses to install packages.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libicu-dev \
        libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# pdo_mysql: MySQL driver
# intl: required by Illuminate\Support\Number (used by db:show, db:table)
# zip: lets Composer extract dist archives
# pcntl: process control, required by Laravel Horizon
RUN docker-php-ext-install pdo_mysql intl zip pcntl

# phpredis: native Redis client, faster than the predis PHP package
RUN pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Run PHP-FPM as www-data with the host user's IDs instead of the default 33.
RUN groupmod --gid "${GID}" www-data \
    && usermod --uid "${UID}" --gid "${GID}" www-data \
    && chown www-data:www-data /var/www

USER www-data

WORKDIR /var/www/html
