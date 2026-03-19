ARG PHP_VERSION=8.4
FROM php:${PHP_VERSION}-cli-alpine

WORKDIR /var/www/html

# Packages: no nginx here (nginx is managed on the deploy host).
RUN apk add --no-cache \
    bash \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    $PHPIZE_DEPS \
    unzip \
    zip \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    intl \
    zip \
    opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del --no-cache $PHPIZE_DEPS \
    && mkdir -p /var/www/html

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHP settings
COPY docker/backend/php.ini /usr/local/etc/php/conf.d/99-app.ini

# Entrypoint
COPY docker/backend/entrypoint.sh /entrypoint.sh
RUN sed -i 's/\r$//' /entrypoint.sh && chmod +x /entrypoint.sh

# Install PHP dependencies for production image
COPY composer.json composer.lock /var/www/html/
RUN composer install --no-interaction --no-ansi --no-progress --prefer-dist --no-dev --optimize-autoloader

# App source (for production image).
COPY . /var/www/html

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=80"]

