FROM php:8.4-fpm

# 1. Download the official PHP Extension Installer
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# 2. Install basic runtime tools (Composer needs git and unzip)
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# 3. Install all PHP extensions in a single, clean step
# Using Debian guarantees glibc compatibility, resolving MongoDB C-driver build issues.
RUN chmod +x /usr/local/bin/install-php-extensions \
    && install-php-extensions \
        intl \
        pdo_pgsql \
        xdebug \
        redis \
        mongodb

# 4. Copy custom Xdebug configuration
COPY docker/php/conf.d/xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug-custom.ini

# 5. Install Composer globally
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html