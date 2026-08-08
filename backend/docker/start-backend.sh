#!/usr/bin/env bash
set -euo pipefail

# Debian nginx ships a default site that shadows conf.d/default.conf
rm -f /etc/nginx/sites-enabled/default

# Ensure central schema exists (fresh volumes otherwise break /api/auth/register)
if [ -f /var/www/html/artisan ]; then
  php /var/www/html/artisan migrate --force --no-interaction >/proc/1/fd/1 2>/proc/1/fd/2 || true
fi

php-fpm -D
exec nginx -g 'daemon off;'
