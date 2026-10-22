FROM composer:2.8 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

FROM php:8.3-fpm-bookworm

ENV APP_ENV=production \
    APP_DEBUG=false \
    PORT=10000

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
        libicu-dev \
        libonig-dev \
        libpq-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        intl \
        mbstring \
        opcache \
        pdo_pgsql \
        zip \
    && rm -rf /var/lib/apt/lists/* \
    && mkdir -p /run/php /var/log/supervisor

WORKDIR /var/www/html

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer
COPY --from=vendor /app/vendor ./vendor
COPY . .

COPY deploy/render/nginx.conf /etc/nginx/sites-available/default
COPY deploy/render/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY deploy/render/entrypoint.sh /usr/local/bin/render-entrypoint

RUN chmod +x /usr/local/bin/render-entrypoint \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/render-entrypoint"]
