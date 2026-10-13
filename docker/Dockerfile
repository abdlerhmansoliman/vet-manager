FROM php:8.3-fpm-alpine
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    linux-headers \
    $PHPIZE_DEPS \
    && docker-php-ext-install pdo pdo_mysql bcmath gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

RUN chown -R www-data:www-data /var/www

USER www-data

EXPOSE 9000
CMD ["php-fpm"]