FROM php:8.3-fpm

# Extensiones necesarias: pdo_mysql (Doctrine), intl, opcache.
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libicu-dev \
    && docker-php-ext-install pdo_mysql intl opcache \
    && rm -rf /var/lib/apt/lists/*

# Composer (desde la imagen oficial).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
