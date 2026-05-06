#!/bin/bash
set -e

echo "=== College CMS — Docker Entrypoint ==="

CORE_DIR="/var/www/html/core"
SYSTEM_DIR="/var/www/html/system"

# ── Create .env if not present ────────────────────────────────────────
if [ ! -f "$CORE_DIR/.env" ]; then
    echo "[INFO] Creating .env from environment variables..."
    cat > "$CORE_DIR/.env" <<EOF
APP_NAME="College CMS"
APP_ENV=production
APP_KEY=${APP_KEY:-}
APP_DEBUG=false
APP_URL=${APP_URL:-http://localhost}

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=${DB_CONNECTION:-mysql}
DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE:-college_cms}
DB_USERNAME=${DB_USERNAME:-root}
DB_PASSWORD=${DB_PASSWORD:-}

CACHE_STORE=file
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=false
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict
SESSION_ABSOLUTE_TIMEOUT=480
SESSION_EXPIRE_ON_CLOSE=false

QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local
MAIL_MAILER=log

MEDIA_DRIVER=${MEDIA_DRIVER:-local}
TRUSTED_PROXIES=*
FORWARDED_ALLOW_IPS=*
CLOUDINARY_CLOUD_NAME=${CLOUDINARY_CLOUD_NAME:-}
CLOUDINARY_API_KEY=${CLOUDINARY_API_KEY:-}
CLOUDINARY_API_SECRET=${CLOUDINARY_API_SECRET:-}
CLOUDINARY_UPLOAD_PRESET=${CLOUDINARY_UPLOAD_PRESET:-}
EOF
fi

cd "$CORE_DIR"

# ── Generate APP_KEY if missing ────────────────────────────────────────
if grep -q "^APP_KEY=$" "$CORE_DIR/.env" || ! grep -q "^APP_KEY=base64:" "$CORE_DIR/.env"; then
    echo "[INFO] Generating APP_KEY..."
    php artisan key:generate --force
fi

# ── Storage dirs ───────────────────────────────────────────────────────
echo "[INFO] Preparing storage directories..."
mkdir -p storage/framework/{sessions,cache/data,views} storage/logs storage/app/rate_limits storage/tmp bootstrap/cache
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data /var/www/html/core/storage /var/www/html/core/bootstrap/cache

# ── Run migrations ─────────────────────────────────────────────────────
echo "[INFO] Running database migrations..."
php artisan migrate --force || echo "[WARN] Migration failed — check DB connection"

# ── Create install.lock ────────────────────────────────────────────────
if [ ! -f "$SYSTEM_DIR/install.lock" ]; then
    echo "[INFO] Creating install.lock..."
    mkdir -p "$SYSTEM_DIR"
    echo "installed=$(date -u +%Y-%m-%dT%H:%M:%SZ)" > "$SYSTEM_DIR/install.lock"
    touch "$CORE_DIR/storage/app/.setup_done"
fi

# ── Optimize Laravel ───────────────────────────────────────────────────
echo "[INFO] Optimizing Laravel..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo "=== Starting Apache ==="
exec "$@"
