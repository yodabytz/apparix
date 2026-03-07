#!/bin/sh
set -e

echo "============================================"
echo "  Apparix E-Commerce Platform"
echo "  Starting up..."
echo "============================================"

# Ensure writable directories exist and have correct ownership
echo "[1/5] Setting permissions..."
mkdir -p \
    /var/www/html/storage/logs \
    /var/www/html/storage/sessions \
    /var/www/html/storage/cache \
    /var/www/html/storage/uploads \
    /var/www/html/storage/downloads \
    /var/www/html/storage/backups \
    /var/www/html/storage/updates \
    /var/www/html/storage/updates_temp \
    /var/www/html/storage/security \
    /var/www/html/public/uploads \
    /var/www/html/public/assets/images/branding \
    /var/www/html/public/assets/images/themes \
    /var/www/html/content/themes \
    /tmp/nginx_client_body

# www-data must own ALL app files for the update system to write anywhere
chown -R www-data:www-data /var/www/html

chmod -R 750 /var/www/html/storage
chmod 1777 /tmp/nginx_client_body

# Configure PHP-FPM to use unix socket
echo "[2/5] Configuring PHP-FPM..."
cat > /usr/local/etc/php-fpm.d/zz-apparix.conf <<'FPMCONF'
[www]
listen = /var/run/php-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660
FPMCONF

# Generate .env from environment variables
echo "[2.5/5] Configuring environment..."
echo "  Generating .env from environment variables..."
cat > /var/www/html/.env <<ENVFILE
# Auto-generated from Docker environment variables
LICENSE_KEY=${LICENSE_KEY:-}

DB_HOST=${DB_HOST:-db}
DB_NAME=${DB_NAME:-apparix_ecommerce}
DB_USER=${DB_USER:-apparix}
DB_PASS=${DB_PASS}

APP_NAME=${APP_NAME:-Apparix Store}
APP_URL=${APP_URL:-http://localhost}
APP_DEBUG=${APP_DEBUG:-false}
APP_TIMEZONE=${APP_TIMEZONE:-America/New_York}

STRIPE_PUBLIC_KEY=${STRIPE_PUBLIC_KEY:-}
STRIPE_SECRET_KEY=${STRIPE_SECRET_KEY:-}
STRIPE_WEBHOOK_SECRET=${STRIPE_WEBHOOK_SECRET:-}

MAIL_FROM=${MAIL_FROM:-orders@localhost}
MAIL_FROM_NAME=${MAIL_FROM_NAME:-${APP_NAME:-Apparix Store}}
ADMIN_EMAIL=${ADMIN_EMAIL:-admin@localhost}

RECAPTCHA_SITE_KEY=${RECAPTCHA_SITE_KEY:-}
RECAPTCHA_SECRET_KEY=${RECAPTCHA_SECRET_KEY:-}
GOOGLE_ANALYTICS_ID=${GOOGLE_ANALYTICS_ID:-}

SESSION_LIFETIME=${SESSION_LIFETIME:-604800}

AFTERSHIP_API_KEY=${AFTERSHIP_API_KEY:-}
NTFY_TOPIC=${NTFY_TOPIC:-}
HOST_IP=${HOST_IP:-}
ENVFILE
chown www-data:www-data /var/www/html/.env
chmod 600 /var/www/html/.env

# Wait for database
echo "[3/5] Waiting for database..."
DB_HOST="${DB_HOST:-db}"
DB_USER="${DB_USER:-apparix}"
DB_PASS="${DB_PASS}"
DB_NAME="${DB_NAME:-apparix_ecommerce}"

if [ -z "$DB_PASS" ]; then
    echo "[ERROR] DB_PASS is not set. Please set it in your .env file."
    echo "[ERROR] Generate one with: openssl rand -base64 24"
    exit 1
fi

MAX_RETRIES=30
RETRY=0
until php -r "
    try {
        new PDO('mysql:host=${DB_HOST};dbname=${DB_NAME}', '${DB_USER}', '${DB_PASS}');
        echo 'OK';
        exit(0);
    } catch (Exception \$e) {
        exit(1);
    }
" 2>/dev/null; do
    RETRY=$((RETRY + 1))
    if [ "$RETRY" -ge "$MAX_RETRIES" ]; then
        echo "[ERROR] Database not available after ${MAX_RETRIES} attempts"
        echo "[ERROR] Check DB_HOST, DB_NAME, DB_USER, DB_PASS in your .env"
        exit 1
    fi
    echo "  Waiting for database... (attempt $RETRY/$MAX_RETRIES)"
    sleep 2
done
echo "  Database connected!"

# Run migrations if needed
echo "[4/5] Checking database..."
if [ ! -f /var/www/html/storage/.installed ]; then
    APP_URL="${APP_URL:-http://localhost}"
    echo "  First run detected — launching web installer"
    echo ""
    echo "  ============================================"
    echo "  Visit: ${APP_URL}/install"
    echo "  You will need your Apparix license key."
    echo "  ============================================"
    echo ""
    echo "  DATABASE CREDENTIALS (for the installer)"
    echo "  Host:     ${DB_HOST}"
    echo "  Database: ${DB_NAME}"
    echo "  User:     ${DB_USER}"
    echo "  Password: ${DB_PASS}"
    echo "  ============================================"
    echo ""
else
    echo "  Already installed — skipping setup"
fi

# Install cron jobs
echo "[5/5] Setting up cron..."
chmod 0600 /etc/crontabs/www-data
touch /var/log/apparix-cron.log
chown www-data:www-data /var/log/apparix-cron.log

echo "============================================"
echo "  Apparix is ready!"
echo "  Visit http://localhost to get started"
echo "============================================"

# Execute CMD (supervisord)
exec "$@"
