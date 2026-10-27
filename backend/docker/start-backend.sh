#!/usr/bin/env bash
set -euo pipefail

# Debian nginx ships a default site that shadows conf.d/default.conf
rm -f /etc/nginx/sites-enabled/default

# Ensure storage directories exist
mkdir -p /var/www/html/storage/app/public

# Ensure relative symlink exists for storage so it works seamlessly inside Docker AND on host
if [ ! -L /var/www/html/public/storage ] || [ "$(readlink /var/www/html/public/storage 2>/dev/null)" != "../storage/app/public" ]; then
  rm -rf /var/www/html/public/storage
  cd /var/www/html/public && ln -sf ../storage/app/public storage
fi

# Ensure central schema exists (fresh volumes otherwise break /api/auth/register)
if [ -f /var/www/html/artisan ]; then
  php /var/www/html/artisan migrate --force --no-interaction >/proc/1/fd/1 2>/proc/1/fd/2 || true
fi

php-fpm -D
exec nginx -g 'daemon off;'
