FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libssl-dev \
    libicu-dev \
    pkg-config \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install intl \
    && pecl install mongodb-2.3.3 \
    && docker-php-ext-enable mongodb

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
