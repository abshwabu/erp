#!/usr/bin/env bash
# ==============================================================================
# ERP Production Build Script for Shared Hosting
# ==============================================================================
# This script prepares the application for standalone shared-hosting deployment:
# 1. Compiles the Vue 3 Single Page Application
# 2. Synchronizes built assets into Laravel's public/ directory
# 3. Clears development caches
# 4. Generates an optimized production package
# ==============================================================================

set -e

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$PROJECT_DIR"

echo "========================================================="
echo " Building ERP Platform for Shared Hosting..."
echo " Project Root: $PROJECT_DIR"
echo "========================================================="

# 1. Build Vue Frontend
FE_DIR=""
if [ -d "$PROJECT_DIR/frontend" ]; then
    FE_DIR="$PROJECT_DIR/frontend"
elif [ -d "$PROJECT_DIR/../frontend" ]; then
    FE_DIR="$PROJECT_DIR/../frontend"
fi

if [ -n "$FE_DIR" ]; then
    echo "--> [1/4] Compiling Vue SPA frontend assets in $FE_DIR..."
    cd "$FE_DIR"
    if command -v npm &> /dev/null; then
        npm run build-only || npx vite build
    else
        echo "Warning: npm not found. Skipping frontend compilation (existing dist will be used)."
    fi
    cd "$PROJECT_DIR"

    # 2. Copy built dist into public/
    echo "--> [2/4] Syncing compiled assets into public/ directory..."
    if [ -d "$FE_DIR/dist" ]; then
        rm -rf "$PROJECT_DIR/public/assets"
        cp -r "$FE_DIR/dist/"* "$PROJECT_DIR/public/"

        # Ensure registerSW.js forces SW update and controller reload
        cat << 'EOF' > "$PROJECT_DIR/public/registerSW.js"
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js', { scope: '/' }).then(reg => {
      reg.update().catch(() => {});
    }).catch(() => {});
  });
  let refreshing = false;
  navigator.serviceWorker.addEventListener('controllerchange', () => {
    if (!refreshing) {
      refreshing = true;
      window.location.reload();
    }
  });
}
EOF
    fi
fi

# 3. Create needed directories and relative symlinks
echo "--> [3/4] Ensuring storage directories & permissions..."
mkdir -p "$PROJECT_DIR/storage/app/public"
mkdir -p "$PROJECT_DIR/storage/framework/cache/data"
mkdir -p "$PROJECT_DIR/storage/framework/sessions"
mkdir -p "$PROJECT_DIR/storage/framework/views"
mkdir -p "$PROJECT_DIR/storage/logs"
mkdir -p "$PROJECT_DIR/bootstrap/cache"

chmod -R 775 "$PROJECT_DIR/storage" "$PROJECT_DIR/bootstrap/cache" 2>/dev/null || true

# Relative storage symlink
rm -f "$PROJECT_DIR/public/storage"
cd "$PROJECT_DIR/public" && ln -s ../storage/app/public storage && cd "$PROJECT_DIR"

# 4. Summary
echo "--> [4/4] Done! Monolithic ERP application is ready for shared hosting."
echo "========================================================="
echo " Next Steps for Shared Hosting (cPanel / Plesk / Apache):"
echo " 1. Upload this folder to your server (e.g. public_html or subfolder)."
echo " 2. Copy .env.shared-hosting.example to .env and set your MySQL database."
echo " 3. Ensure document root points to public/ (or use the included root .htaccess)."
echo " 4. Read SHARED_HOSTING_GUIDE.md for complete configuration steps."
echo "========================================================="
