#!/bin/sh
set -eu

mkdir -p \
    /app/storage/app/private \
    /app/storage/app/public \
    /app/storage/framework/cache/data \
    /app/storage/framework/sessions \
    /app/storage/framework/views \
    /app/storage/logs \
    /app/bootstrap/cache

chown www-data:www-data \
    /app/storage \
    /app/storage/app \
    /app/storage/app/private \
    /app/storage/app/public \
    /app/storage/framework \
    /app/storage/framework/cache \
    /app/storage/framework/cache/data \
    /app/storage/framework/sessions \
    /app/storage/framework/views \
    /app/storage/logs \
    /app/bootstrap/cache

exec gosu www-data "$@"
