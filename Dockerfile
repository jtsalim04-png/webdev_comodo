# syntax=docker/dockerfile:1
FROM php:8.3-fpm-bookworm

ARG INSTALL_DEV_DEPS=0
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip curl nginx \
    libicu-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql intl zip opcache gd \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock symfony.lock ./
RUN if [ "$INSTALL_DEV_DEPS" = "1" ]; then \
        composer install --no-interaction --prefer-dist --no-scripts; \
    else \
        composer install --no-interaction --prefer-dist --no-dev --no-scripts --optimize-autoloader; \
    fi

COPY . .

RUN cp .env.example .env

RUN if [ "$INSTALL_DEV_DEPS" = "1" ]; then \
        composer install --no-interaction --prefer-dist --no-scripts; \
    else \
        composer install --no-interaction --prefer-dist --no-dev --no-scripts --optimize-autoloader; \
    fi \
    && composer dump-autoload --optimize --classmap-authoritative \
    && test -f vendor/autoload_runtime.php

RUN mkdir -p var/cache var/log public/uploads config/jwt \
    && chown -R www-data:www-data var public/uploads config/jwt

COPY docker/nginx-main.conf /etc/nginx/nginx.conf
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY docker/php-fpm-www.conf /usr/local/etc/php-fpm.d/zz-docker.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
