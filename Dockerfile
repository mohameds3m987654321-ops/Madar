FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends gosu libcurl4-openssl-dev libfreetype6-dev libicu-dev libjpeg62-turbo-dev libonig-dev libpng-dev libzip-dev supervisor unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install bcmath curl gd intl mbstring pdo_mysql pcntl zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
ENV PORT=8080

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader --no-scripts

COPY . .

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
