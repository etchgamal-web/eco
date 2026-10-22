#!/usr/bin/env bash
set -Eeuo pipefail

cd /var/www/html

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

php artisan storage:link --force >/dev/null 2>&1 || true
php artisan optimize

if [[ "$#" -gt 0 ]]; then
    exec "$@"
fi

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
