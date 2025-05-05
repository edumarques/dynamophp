FROM php:8.3-cli-alpine

WORKDIR /app

RUN apk add --no-cache \
    git \
    unzip \
    libzip-dev

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del -f .build-deps

COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN git config --global --add safe.directory /app

CMD ["tail", "-f", "/dev/null"]
