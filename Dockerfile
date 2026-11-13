FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends gosu libcurl4-openssl-dev libicu-dev libonig-dev libzip-dev supervisor unzip \
    && docker-php-ext-install bcmath curl intl mbstring pdo_mysql pcntl zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
ENV PORT=8080

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader --no-scripts

COPY . .
COPY --from=frontend /app/public/build ./public/build

RUN mkdir -p \
        storage/app/private \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache \
    && php artisan package:discover --ansi \
    && php artisan storage:link --force

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint
COPY deployment/railway/supervisord.conf /etc/supervisor/conf.d/railway.conf
COPY deployment/railway/php.ini /usr/local/etc/php/conf.d/railway.ini
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["/usr/local/bin/docker-entrypoint"]

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/railway.conf"]
