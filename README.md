# Apparix E-Commerce Platform

A modern, full-featured e-commerce platform built with PHP. Perfect for boutiques, handmade goods, electronics, fashion, and any online store.

## Features

- **Product Management**: Variants, options, inventory tracking, digital downloads
- **Order Management**: Order processing, shipping integration, tracking
- **Payment Processing**: Stripe integration with secure checkout
- **Customer Management**: User accounts, order history, favorites
- **Marketing Tools**: Coupons, newsletters, referral system, social proof popups
- **Themes**: 4 pre-built themes with customization options
- **Admin Dashboard**: Comprehensive analytics and store management
- **Security**: CSRF protection, secure sessions, input validation

---

## Requirements

- **PHP 8.1+** with extensions: PDO, PDO_MySQL, mbstring, curl, json, openssl, gd
- **MySQL 5.7+** or MariaDB 10.3+
- **Nginx** or Apache with mod_rewrite
- **Composer** (for dependency management)
- **SSL Certificate** (required for Stripe payments)

---

## Quick Installation

### 1. Upload Files

Upload all files to your web server's document root (e.g., `/var/www/yourdomain.com/`)

### 2. Set Directory Permissions

```bash
chmod 755 /var/www/yourdomain.com
chmod -R 775 /var/www/yourdomain.com/storage
chmod -R 775 /var/www/yourdomain.com/public/assets/images
```

### 3. Install Dependencies

```bash
cd /var/www/yourdomain.com
composer install --no-dev --optimize-autoloader
```

### 4. Configure Web Server

**For Nginx** (recommended), create a site configuration:

```nginx
server {
    listen 80;
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/yourdomain.com/public;
    index index.php;

    # SSL (use Let's Encrypt)
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    # Redirect HTTP to HTTPS
    if ($scheme != "https") {
        return 301 https://$host$request_uri;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 5. Run the Installer

Navigate to `https://yourdomain.com/install` in your browser and follow the setup wizard:

1. **Requirements Check** - Verifies PHP version and extensions
2. **Database Setup** - Enter MySQL credentials
3. **Store Information** - Name, URL, contact email
4. **Admin Account** - Create your admin login
5. **Integrations** - Stripe, Email, reCAPTCHA (can skip and configure later)
6. **Theme Selection** - Choose your store's look
7. **Complete** - Installation finishes

---

## Configuration Guide

### Stripe Payment Setup

1. Create a Stripe account at https://stripe.com
2. Get your API keys from the Stripe Dashboard > Developers > API keys
3. Enter in Admin > Settings > Payments:
   - **Publishable Key**: `pk_live_...` (or `pk_test_...` for testing)
   - **Secret Key**: `sk_live_...` (or `sk_test_...` for testing)

4. Set up Webhooks in Stripe Dashboard:
   - Endpoint URL: `https://yourdomain.com/webhook/stripe`
   - Events to listen for:
     - `payment_intent.succeeded`
     - `charge.refunded`
     - `charge.dispute.created`

### Google reCAPTCHA v3

Protects forms from spam and abuse.

1. Go to https://www.google.com/recaptcha/admin
2. Register a new site:
   - Choose **reCAPTCHA v3**
   - Add your domain(s)
3. Enter keys in Admin > Settings > Integrations:
   - **Site Key**: Public key for frontend
   - **Secret Key**: Private key for verification

### Google Analytics

1. Create a GA4 property at https://analytics.google.com
2. Get your Measurement ID (format: `G-XXXXXXXXXX`)
3. Enter in Admin > Settings > Integrations > Google Analytics ID

### Email Configuration (SMTP)

For order confirmations, shipping notifications, and newsletters.

**Gmail Example:**
```
SMTP Host: smtp.gmail.com
SMTP Port: 587
SMTP User: your-email@gmail.com
SMTP Pass: (App Password - generate in Google Account settings)
From Email: your-email@gmail.com
From Name: Your Store Name
```

**Amazon SES Example:**
```
SMTP Host: email-smtp.us-east-1.amazonaws.com
SMTP Port: 587
SMTP User: (AWS SMTP credentials)
SMTP Pass: (AWS SMTP password)
From Email: noreply@yourdomain.com
From Name: Your Store Name
```

---

## Security Recommendations

### Web Application Firewall (Required)

We strongly recommend using a Web Application Firewall (WAF) to protect against common attacks.

#### ModSecurity

ModSecurity is an open-source WAF that integrates with Nginx and Apache:

```bash
# Ubuntu/Debian with Nginx
sudo apt install libnginx-mod-http-modsecurity

# Enable ModSecurity
sudo cp /etc/modsecurity/modsecurity.conf-recommended /etc/modsecurity/modsecurity.conf
sudo sed -i 's/SecRuleEngine DetectionOnly/SecRuleEngine On/' /etc/modsecurity/modsecurity.conf

# Install OWASP Core Rule Set
sudo apt install modsecurity-crs
```

#### SecuNX Web Firewall (Recommended)

**SecuNX** is a lightweight, high-performance IP blocklist firewall specifically designed for Nginx. It provides real-time protection against malicious IPs using curated threat intelligence feeds.

**Features:**
- Automatic blocklist updates from multiple threat feeds
- Minimal performance impact
- Easy integration with Nginx
- Detailed logging and monitoring

**Installation:**
```bash
git clone https://github.com/yodabytz/secunx.git
cd secunx
sudo ./install.sh
```

GitHub: https://github.com/yodabytz/secunx

### Additional Security Measures

1. **SSL/TLS Certificate**: Required for Stripe. Use Let's Encrypt:
   ```bash
   sudo apt install certbot python3-certbot-nginx
   sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
   ```

2. **File Permissions**:
   ```bash
   # Web files owned by www-data
   sudo chown -R www-data:www-data /var/www/yourdomain.com

   # Restrict .env file
   chmod 600 /var/www/yourdomain.com/.env
   ```

3. **Security Headers** (already configured in the application):
   - X-Content-Type-Options
   - X-Frame-Options
   - X-XSS-Protection
   - Content-Security-Policy

4. **Database Security**:
   - Use a dedicated MySQL user with limited privileges
   - Strong password (16+ characters)
   - Disable remote root access

5. **Regular Updates**:
   - Keep PHP and MySQL updated
   - Monitor for Apparix updates in Admin > Updates

---

## Directory Structure

```
/
├── app/                    # Application code
│   ├── Controllers/        # Request handlers
│   ├── Models/             # Database models
│   ├── Views/              # PHP templates
│   ├── Core/               # Framework classes
│   └── Helpers/            # Utility functions
├── public/                 # Web root (point Nginx here)
│   ├── index.php           # Front controller
│   ├── assets/             # CSS, JS, images
│   └── .htaccess           # Apache rewrite rules
├── storage/                # Logs, sessions, uploads
├── vendor/                 # Composer dependencies
├── install/                # Installation wizard
├── database/               # SQL migrations
└── .env                    # Environment configuration
```

---

## Environment Variables (.env)

After installation, your `.env` file will contain:

```env
# Application
APP_NAME="Your Store Name"
APP_URL=https://yourdomain.com
APP_DEBUG=false

# Database
DB_HOST=localhost
DB_NAME=your_database
DB_USER=your_db_user
DB_PASS=your_db_password

# Stripe
STRIPE_PUBLIC_KEY=pk_live_...
STRIPE_SECRET_KEY=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...

# Email
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USER=your_smtp_user
MAIL_PASS=your_smtp_password
MAIL_FROM=noreply@yourdomain.com
MAIL_FROM_NAME="Your Store Name"

# reCAPTCHA
RECAPTCHA_SITE_KEY=6Le...
RECAPTCHA_SECRET_KEY=6Le...

# Google Analytics
GOOGLE_ANALYTICS_ID=G-XXXXXXXXXX

# Session
SESSION_LIFETIME=604800

# License (required)
LICENSE_KEY=XXXX-XXXX-XXXX-XXXX
```

---

## Admin Panel

Access the admin panel at: `https://yourdomain.com/admin`

**Key Sections:**
- **Dashboard**: Sales overview, recent orders, analytics
- **Products**: Add/edit products, manage inventory
- **Orders**: Process orders, add tracking, issue refunds
- **Customers**: View customer accounts and order history
- **Coupons**: Create discount codes
- **Shipping**: Configure zones and rates
- **Settings**: Store info, payments, integrations
- **Themes**: Customize your store's appearance

---

## Troubleshooting

### "500 Internal Server Error"
- Check PHP error logs: `tail -f /var/log/php8.2-fpm.log`
- Verify file permissions
- Ensure `.env` file exists and is readable

### "Database Connection Failed"
- Verify MySQL is running: `sudo systemctl status mysql`
- Check credentials in `.env`
- Ensure database user has proper privileges

### "Stripe Payments Not Working"
- Verify API keys are correct (live vs test)
- Check webhook is configured properly
- Ensure SSL certificate is valid

### "Emails Not Sending"
- Verify SMTP credentials
- Check if port is blocked by firewall
- Test with a service like Mailtrap first

---

## License Tiers

| Feature | Standard (Free) | Professional | Business | Enterprise |
|---------|----------------|--------------|----------|------------|
| Orders/Month | 50 | 500 | Unlimited | Unlimited |
| Products | 100 | 1,000 | Unlimited | Unlimited |
| Admin Users | 5 | 15 | 50 | Unlimited |
| Digital Products | ✓ | ✓ | ✓ | ✓ |
| Themes | Basic | All | All | All + Custom |
| Plugins | Community | All | All | All + Custom |
| Support | Community | Email | Priority | Dedicated |
| Price | **Free** | $99/year | $249/year | Contact Us |

**Purchase your license at: [https://apparix.app/pricing](https://apparix.app/pricing)**

---

## Official Plugins

Available for Professional licenses and above:

- **PayPal** - Accept PayPal payments
- **Square** - Square payment processing
- **Authorize.net** - Authorize.net gateway
- **Etsy Sync** - Sync products with Etsy
- **Amazon Sync** - Sync with Amazon Marketplace
- **eBay Sync** - Sync with eBay

Download plugins at: [https://apparix.app/plugins](https://apparix.app/plugins)

---

## Support

- **Documentation**: [https://apparix.app/docs](https://apparix.app/docs)
- **GitHub Issues**: [https://github.com/yodabytz/apparix/issues](https://github.com/yodabytz/apparix/issues)
- **Email Support**: support@apparix.app (paid licenses)
- **Community**: [https://community.apparix.app](https://community.apparix.app)

---

## License

Apparix is released under the [Apparix License](LICENSE).

- **Free** for personal and small business use (with limitations)
- **Commercial licenses** available for larger deployments and premium features

Purchase licenses at: [https://apparix.app/pricing](https://apparix.app/pricing)

---

*Built by [Vibrix Media](https://vibrixmedia.com)*
