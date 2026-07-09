FROM php:8.3-fpm-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        zip \
        intl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
