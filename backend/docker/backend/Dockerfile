ARG PHP_VERSION=8.4
FROM php:${PHP_VERSION}-fpm-alpine

WORKDIR /var/www/html

# Packages: nginx + supervisor to run both services.
RUN apk add --no-cache \
    bash \
    nginx \
    openssl \
    supervisor \
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
    && mkdir -p /run/nginx /var/log/supervisor /etc/nginx/certs

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHP settings
COPY docker/backend/php.ini /usr/local/etc/php/conf.d/99-app.ini

# Nginx templates (dev/prod TLS) + entrypoint
COPY docker/backend/nginx/default.dev.conf /etc/nginx/templates/default.dev.conf
COPY docker/backend/nginx/default.tls.conf /etc/nginx/templates/default.tls.conf
COPY docker/backend/supervisord.conf /etc/supervisord.conf
COPY docker/backend/entrypoint.sh /entrypoint.sh
RUN sed -i 's/\r$//' /entrypoint.sh && chmod +x /entrypoint.sh

# App source (for production image). In dev you'll typically bind-mount.
COPY . /var/www/html

EXPOSE 80 443

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]

