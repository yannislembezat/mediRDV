FROM php:8.4-fpm-alpine
 
# System deps
RUN apk add --no-cache \
    bash \
    curl \
    git \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    unzip \
    && docker-php-ext-install \
        intl \
        mbstring \
        opcache \
        pdo_mysql \
        zip
 
# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
 
WORKDIR /var/www/html
 
# Install PHP dependencies first (layer-cached until composer.json changes)
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
 
# Copy application source
COPY . .
 
# Install assets and JS importmap packages (needs internet access at build time)
RUN APP_ENV=prod php bin/console assets:install public \
    && APP_ENV=prod php bin/console importmap:install
 
# Clear and warm the production cache (single pass — no redundant clear)
RUN APP_ENV=prod php bin/console cache:clear --no-warmup \
    && APP_ENV=prod php bin/console cache:warmup
 
# Entrypoint (runs migrations + JWT key gen before FPM starts)
COPY docker/php/entrypoint.sh /entrypoint.sh
RUN sed -i 's/\r$//' /entrypoint.sh && chmod +x /entrypoint.sh
 
EXPOSE 9000
ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]