#!/bin/sh
# Apparix Docker Quick Setup
# Generates a .env file with secure random passwords

if [ -f .env ]; then
    echo ".env already exists. Remove it first if you want to regenerate."
    exit 1
fi

DB_PASS=$(openssl rand -base64 24 | tr -dc 'A-Za-z0-9' | head -c 24)
DB_ROOT_PASS=$(openssl rand -base64 24 | tr -dc 'A-Za-z0-9' | head -c 24)

cat > .env <<EOF
# Apparix E-Commerce - Docker Configuration
# Generated on $(date -u +"%Y-%m-%d %H:%M UTC")

# License key (leave empty for free tier, or enter your key)
LICENSE_KEY=

# Database (auto-generated secure passwords)
DB_HOST=db
DB_NAME=apparix_ecommerce
DB_USER=apparix
DB_PASS=${DB_PASS}
DB_ROOT_PASS=${DB_ROOT_PASS}

# Application
APP_NAME="My Store"
APP_URL=http://localhost
APP_DEBUG=false
APP_TIMEZONE=America/New_York

# Stripe (required for checkout)
STRIPE_PUBLIC_KEY=
STRIPE_SECRET_KEY=
STRIPE_WEBHOOK_SECRET=

# Email
MAIL_FROM=orders@localhost
MAIL_FROM_NAME="My Store"
ADMIN_EMAIL=admin@localhost

# Optional: Set admin credentials (random password generated if not set)
# ADMIN_PASSWORD=

# Optional integrations
RECAPTCHA_SITE_KEY=
RECAPTCHA_SECRET_KEY=
GOOGLE_ANALYTICS_ID=
AFTERSHIP_API_KEY=
NTFY_TOPIC=
SESSION_LIFETIME=604800
EOF

echo "Created .env with secure random database passwords."
echo ""
echo "  DB_PASS:      ${DB_PASS}"
echo "  DB_ROOT_PASS: ${DB_ROOT_PASS}"
echo ""
echo "Edit .env to add your license key, Stripe keys, etc."
echo "Then run: docker-compose up -d"
