# Apparix E-Commerce Platform

A powerful, fully-featured e-commerce platform built for modern online stores. Zero complexity — just deploy and start selling.

## Quick Start

```bash
# 1. Create a project directory
mkdir my-store && cd my-store

# 2. Download the docker-compose file and setup script
curl -O https://raw.githubusercontent.com/apparix/docker/main/docker-compose.yml
curl -O https://raw.githubusercontent.com/apparix/docker/main/docker/setup-env.sh
chmod +x setup-env.sh

# 3. Generate .env with secure random passwords
./setup-env.sh

# 4. Edit .env — add your license key, Stripe keys, store name, etc.
nano .env

# 5. Start everything
docker compose up -d

# 6. Visit http://localhost — your store is ready!
#    Admin credentials are shown in the container logs on first run:
docker compose logs app | grep -A5 "ADMIN CREDENTIALS"
```

## Free Edition

Apparix has a **free tier** — no credit card required. Get your free license key at [apparix.app](https://apparix.app/products/apparix-ecommerce-platform).

| Feature | Free | Standard ($99) | Pro ($199) | Enterprise ($299) | Unlimited ($499) |
|---------|------|----------------|------------|-------------------|-------------------|
| Products | 50 | 100 | 1,000 | Unlimited | Unlimited |
| Orders/month | 50 | 500 | 5,000 | Unlimited | Unlimited |
| Admin users | 1 | 1 | 5 | Unlimited | Unlimited |
| Custom themes | - | ✓ | ✓ | ✓ | ✓ |
| Coupons & discounts | - | ✓ | ✓ | ✓ | ✓ |
| API access | - | - | ✓ | ✓ | ✓ |
| Abandoned cart recovery | - | - | - | ✓ | ✓ |
| Multi-domain | - | - | - | - | ✓ |
| White-label | - | - | - | - | ✓ |

All editions are **one-time purchases** — no monthly fees.

## What's Included

- Complete shopping cart and secure Stripe checkout
- Customer accounts with order history
- Product variants (size, color, etc.)
- Digital product downloads
- Fully responsive design
- Admin dashboard with analytics
- Automated email notifications (order confirmations, shipping updates)
- SEO-friendly URLs
- Plugin system for extensibility
- Automatic software updates
- Backorder support

## Architecture

This image runs **PHP 8.3-FPM + Nginx** in a single container. Pair it with the official MariaDB image for the database.

```
┌─────────────────────┐     ┌─────────────────┐
│   apparixapp/       │     │   mariadb:10.11  │
│   ecommerce         │────▶│                  │
│                     │     │   (database)     │
│  Nginx + PHP-FPM    │     └─────────────────┘
│  + Cron             │
└─────────────────────┘
        │
    Port 80
```

## docker-compose.yml

```yaml
services:
  app:
    image: apparixapp/apparix:latest
    ports:
      - "80:80"
    env_file: .env
    environment:
      - DB_HOST=db
    volumes:
      - storage:/var/www/html/storage
      - uploads:/var/www/html/public/uploads
    depends_on:
      db:
        condition: service_healthy
    restart: unless-stopped

  db:
    image: mariadb:10.11
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASS:?Set DB_ROOT_PASS in .env}
      MYSQL_DATABASE: ${DB_NAME:-apparix_ecommerce}
      MYSQL_USER: ${DB_USER:-apparix}
      MYSQL_PASSWORD: ${DB_PASS:?Set DB_PASS in .env}
    volumes:
      - dbdata:/var/lib/mysql
    healthcheck:
      test: ["CMD", "healthcheck.sh", "--connect", "--innodb_initialized"]
      interval: 10s
      timeout: 5s
      retries: 5
    restart: unless-stopped

volumes:
  storage:
  uploads:
  dbdata:
```

## Environment Variables

| Variable | Required | Description |
|----------|----------|-------------|
| `LICENSE_KEY` | Yes | Your Apparix license key ([get one here](https://apparix.app/products/apparix-ecommerce-platform)) |
| `DB_HOST` | Yes | Database hostname (use `db` with docker-compose) |
| `DB_NAME` | Yes | Database name |
| `DB_USER` | Yes | Database username |
| `DB_PASS` | Yes | Database password (use `openssl rand -base64 24`) |
| `DB_ROOT_PASS` | Yes | Database root password (use `openssl rand -base64 24`) |
| `APP_NAME` | No | Your store name |
| `APP_URL` | No | Your store's public URL |
| `APP_DEBUG` | No | Enable debug mode (`true`/`false`) |
| `STRIPE_PUBLIC_KEY` | For checkout | Stripe publishable key |
| `STRIPE_SECRET_KEY` | For checkout | Stripe secret key |
| `MAIL_FROM` | No | Sender email for notifications |
| `ADMIN_EMAIL` | No | Admin login email (default: `admin@localhost`) |
| `ADMIN_PASSWORD` | No | Admin login password (random if not set) |

## SSL / HTTPS

This container serves HTTP on port 80. For production, put a reverse proxy in front:

- **Cloudflare** (easiest — free SSL)
- **Traefik** (auto Let's Encrypt)
- **Nginx Proxy Manager**
- **Caddy**

## Persistent Data

Data is stored in Docker volumes:
- `storage` — logs, sessions, cache, backups, uploads
- `uploads` — product images
- `dbdata` — MariaDB database

Your data survives container restarts and updates.

## Updating

```bash
docker compose pull
docker compose up -d
```

The container automatically runs database migrations on startup.

## Plugins

Extend Apparix with official plugins:
- **Payment:** PayPal, Square, Authorize.net
- **Marketplace:** Etsy, eBay, Amazon sync
- **Utility:** Backup, Community Hub

Available at [apparix.app/products](https://apparix.app/products)

## Support

- **Documentation:** [apparix.app](https://apparix.app)
- **System Status:** [status.apparix.app](https://status.apparix.app)
- **Email:** support@apparix.app

## License

Apparix is commercial software. A license key is required. Free and paid editions available at [apparix.app](https://apparix.app/products/apparix-ecommerce-platform).
