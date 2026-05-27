# syntax=docker/dockerfile:1
FROM php:8.3-fpm-bookworm AS builder

ARG INSTALL_DEV_DEPS=0
ENV COMPOSER_ALLOW_SUPERUSER=1

# Install system dependencies including Node.js for asset compilation
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip curl nginx \
    libicu-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    nodejs npm \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql intl zip opcache gd \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
# Copy only composer files first for better layer caching
COPY composer.json* composer.lock* symfony.lock* package.json* package-lock.json* ./
RUN if [ "$INSTALL_DEV_DEPS" = "1" ]; then \
        composer install --no-interaction --prefer-dist --no-scripts; \
    else \
        composer install --no-interaction --prefer-dist --no-dev --no-scripts --optimize-autoloader; \
    fi

# Install npm dependencies if package.json exists (optional)
RUN if [ -f package.json ]; then npm ci --omit=dev || npm install; fi
# Copy the rest of the application
COPY . .

# Setup environment
RUN cp .env.example .env

# Final composer install (with scripts this time)
RUN if [ "$INSTALL_DEV_DEPS" = "1" ]; then \
        composer install --no-interaction --prefer-dist; \
    else \
        composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader; \
    fi \
    && composer dump-autoload --optimize --classmap-authoritative \
    && test -f vendor/autoload_runtime.php

# ===== CRITICAL: Build AssetMapper assets =====
RUN echo "=== Installing importmap packages ===" \
    && php bin/console importmap:install --no-interaction \
    && echo "=== Compiling assets for production ===" \
    && php bin/console asset-map:compile --no-interaction

# Verify assets were created (fail build if missing)
RUN echo "=== Verifying assets ===" \
    && ls -la public/assets/ || (echo "ERROR: assets directory not created" && exit 1) \
    && ls -la public/assets/app.js || echo "Warning: app.js not found, check your importmap configuration"

# Create necessary directories with proper permissions
RUN mkdir -p var/cache var/log public/uploads config/jwt \
    && chown -R www-data:www-data var public/uploads config/jwt public/assets

# Copy configuration files
COPY docker/nginx-main.conf /etc/nginx/nginx.conf
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY docker/php-fpm-www.conf /usr/local/etc/php-fpm.d/zz-docker.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]