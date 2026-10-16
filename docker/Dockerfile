FROM php:8.3-fpm-alpine

ARG UID=1000
ARG GID=1000

# Install system dependencies and build libraries
RUN apk add --no-cache \
    git \
    curl \
    shadow \
    zip \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    libxml2-dev \
    icu-dev \
    linux-headers \
    $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        bcmath \
        gd \
        zip \
        intl \
        pcntl \
        posix \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del --no-network $PHPIZE_DEPS \
    && rm -rf /tmp/pear

# Ensure www-data UID/GID matches host Linux user (UID 1000) to prevent bind-mount permission issues
RUN usermod -u ${UID} www-data && groupmod -g ${GID} www-data

# Copy Composer binary from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
