FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    git \
    unzip \
    libzip-dev \
    rabbitmq-c-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    build-base \
    autoconf \
    gcompat \
    libstdc++ \
    libc6-compat\
    linux-headers

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions amqp bcmath gd intl opcache pdo_mysql zip redis

RUN apk add --no-cache nodejs npm && \
    npm install -g tailwindcss

RUN mkdir -p var/tailwind && chown -R www-data:www-data var/tailwind

RUN pecl install xdebug && docker-php-ext-enable xdebug

RUN echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini


COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
