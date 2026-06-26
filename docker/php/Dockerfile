FROM php:8.4-cli-alpine

RUN apk add --no-cache \
        postgresql-dev \
        icu-dev \
        libzip-dev \
        libsodium-dev \
        rabbitmq-c-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install \
        pdo_pgsql \
        intl \
        zip \
        sodium \
        opcache \
    && pecl install redis amqp \
    && docker-php-ext-enable redis amqp \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public/"]
