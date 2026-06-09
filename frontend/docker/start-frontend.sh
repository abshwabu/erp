#!/usr/bin/env sh
set -eu

mkdir -p /app/node_modules

if [ ! -d /app/node_modules/vite ]; then
  cp -a /opt/frontend/node_modules/. /app/node_modules/
fi

cd /app
exec npm run dev -- --host 0.0.0.0 --port 3000
