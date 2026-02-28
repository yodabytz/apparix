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

**For Nginx** (recommended), you need two configuration files: a global `nginx.conf` snippet for bot blocking, and a per-site server block.

#### 4a. Global Bot Blocking (nginx.conf)

Add these maps inside the `http {}` block in `/etc/nginx/nginx.conf`, **before** any `server` blocks or `include` directives:

```nginx
# Bot & Scraper Blocking
# Layer 1: User Agent blocking
map $http_user_agent $limit_bots {
    default 0;
    ~*(ahrefsbot|semrushbot|mj12bot|dotbot|rogerbot|ccbot|bytespider|petalbot) 1;
    ~*(python|curl|wget|go-http-client|perl|lwp-trivial|httrack|harvest|libwww) 1;
    ~*(nikto|sqlmap|nmap|masscan|zgrab|nuclei|httpx|wpscan|dirbuster|gobuster) 1;
    ~*(HeadlessChrome|aiohttp|censys|ZmEu|Havij|w3af|openvas) 1;
    ~*(GPTBot|ChatGPT-User|CCBot|anthropic-ai|Claude-Web|Google-Extended) 1;
    ~*(spider|scraper|crawler|extractor|stripper|sucker|webzip|offline) 1;
}

# Layer 2: Rate limiting zones
limit_req_zone $binary_remote_addr zone=global_login:10m rate=5r/m;
limit_req_zone $binary_remote_addr zone=global_checkout:10m rate=10r/m;
```

#### 4b. Site Server Block

Create `/etc/nginx/sites-available/yourdomain.com`:

```nginx
# HTTP to HTTPS redirect
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;

    # Bot blocking
    if ($limit_bots) { return 403; }

    # Allow Let's Encrypt validation
    location ^~ /.well-known/acme-challenge/ {
        default_type text/plain;
        root /var/www/certbot;
        try_files $uri =404;
    }

    location / {
        return 301 https://yourdomain.com$request_uri;
    }
}

# Main HTTPS server
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;

    root /var/www/yourdomain.com/public;
    index index.php;

    # SSL (use Let's Encrypt — see Security section below)
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers off;

    # Bot blocking
    if ($limit_bots) { return 403; }

    # Security headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # File upload size
    client_max_body_size 50M;

    # Deny access to sensitive/hidden files
    location ~ /\.(env|git|htaccess|gitignore|ssh|aws)$ {
        deny all;
        access_log off;
        log_not_found off;
    }
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    # Block WordPress vulnerability scanners (return 410 Gone)
    location ~* ^/(wp-admin|wp-login\.php|wp-content|wp-includes|xmlrpc\.php|wp-config|wordpress|wp-json|wp-cron\.php|wp-signup\.php|wp-trackback\.php|wlwmanifest\.xml)(/|$) {
        access_log off;
        log_not_found off;
        return 410;
    }

    # Block other common CMS/vulnerability scans
    location ~* ^/(phpmyadmin|pma|myadmin|mysql|admin\.php|administrator|joomla|drupal|magento|config\.php|install\.php|setup\.php|shell\.php)(/|$) {
        access_log off;
        log_not_found off;
        return 410;
    }

    # Custom 404 page
    error_page 404 /404.php;
    location = /404.php {
        internal;
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, max-age=31536000";
        access_log off;
    }

    # PHP-FPM handling
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_param HTTPS on;
        fastcgi_param SERVER_PORT 443;
        fastcgi_buffer_size 32k;
        fastcgi_buffers 16 16k;
    }

    # Rate limiting on sensitive endpoints
    location /login {
        limit_req zone=global_login burst=3 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }
    location /checkout {
        limit_req zone=global_checkout burst=5 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Front controller — route all requests to index.php
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

Enable the site and test:

```bash
sudo ln -s /etc/nginx/sites-available/yourdomain.com /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

> **Note:** Adjust `php8.3-fpm.sock` to match your PHP version (e.g., `php8.1-fpm.sock`, `php8.2-fpm.sock`).

**For Apache**, Apparix includes a `.htaccess` file in the `public/` directory that handles URL rewriting, security headers, scanner blocking, and static asset caching automatically. You just need to:

1. Enable `mod_rewrite`:

```bash
sudo a2enmod rewrite headers expires deflate
sudo systemctl restart apache2
```

2. Create a virtual host at `/etc/apache2/sites-available/yourdomain.com.conf`:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    Redirect permanent / https://yourdomain.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com

    DocumentRoot /var/www/yourdomain.com/public

    # SSL (use Let's Encrypt — see Security section below)
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/yourdomain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/yourdomain.com/privkey.pem

    <Directory /var/www/yourdomain.com/public>
        AllowOverride All
        Require all granted
    </Directory>

    # Deny access to app directory (above document root, but just in case)
    <Directory /var/www/yourdomain.com/app>
        Require all denied
    </Directory>
    <Directory /var/www/yourdomain.com/storage>
        Require all denied
    </Directory>
    <Directory /var/www/yourdomain.com/vendor>
        Require all denied
    </Directory>

    # File upload limit
    LimitRequestBody 52428800

    ErrorLog ${APACHE_LOG_DIR}/yourdomain.com-error.log
    CustomLog ${APACHE_LOG_DIR}/yourdomain.com-access.log combined
</VirtualHost>
```

3. Enable the site:

```bash
sudo a2ensite yourdomain.com.conf
sudo a2enmod ssl
sudo apache2ctl configtest
sudo systemctl reload apache2
```

> **Note:** The `.htaccess` file in `public/` handles URL rewriting, security headers, vulnerability scanner blocking, static file caching, and gzip compression. Make sure `AllowOverride All` is set in your virtual host.

**For Shared Hosting (cPanel, Plesk, etc.)**, most shared hosts use Apache with `.htaccess` support enabled by default:

1. Upload all Apparix files to your hosting account
2. Set the **document root** to the `public/` subdirectory:
   - **cPanel**: Go to "Domains" → edit your domain → change Document Root to `public`
   - **Plesk**: Go to "Hosting & DNS" → "Hosting Settings" → change Document Root
   - **Other**: Contact your host — most can change it in their control panel
3. If you **cannot change the document root**, create an `.htaccess` in your web root (`public_html/`) with:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

4. Set directory permissions via your host's File Manager:
   - `storage/` → 775
   - `public/assets/images/` → 775
   - `.env` → 600
5. Import the database using phpMyAdmin (available in most control panels)
6. Navigate to `https://yourdomain.com/install` to run the installer

> **Shared hosting limitations:** Server-level protections (ModSecurity, SecuNX, Fail2Ban, Nginx bot map) won't be available, but the built-in PHP BotBlocker, CSRF protection, and security headers still work automatically.

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

## Security Setup

Apparix includes multiple layers of security. The **BotBlocker** (PHP-level) works out of the box with zero configuration. The server-level protections below are strongly recommended for production deployments.

### Layer 1: Built-in BotBlocker (Automatic)

Apparix ships with an automatic bot detection system (`app/Core/BotBlocker.php`) that runs on every request:

- **Honeypot traps**: Bots probing WordPress, `.env`, phpMyAdmin, shell backdoors, and 40+ other scanner paths are instantly blocked
- **Malicious user agents**: Known attack tools (sqlmap, nikto, zgrab, masscan, HeadlessChrome, etc.) are auto-blocked
- **Auto-ban**: Blocked IPs are banned for 7 days and stored in `storage/security/blocked_ips.json`
- **Legitimate bot whitelist**: Googlebot, Bingbot, Applebot, facebookexternalhit, Twitterbot, LinkedInBot, UptimeRobot, Let's Encrypt, and other legitimate services are never blocked

**No configuration required** — this works automatically after installation.

Block logs are written to `storage/security/bot_blocks.log` for auditing.

### Layer 2: SSL/TLS Certificate (Required)

SSL is required for Stripe payments and strongly recommended for all traffic.

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Auto-renew (certbot installs a systemd timer by default)
sudo certbot renew --dry-run
```

### Layer 3: ModSecurity WAF (Recommended)

ModSecurity is an open-source Web Application Firewall that blocks SQL injection, XSS, and other OWASP Top 10 attacks at the Nginx level:

```bash
# Ubuntu/Debian with Nginx
sudo apt install libnginx-mod-http-modsecurity

# Enable ModSecurity
sudo cp /etc/modsecurity/modsecurity.conf-recommended /etc/modsecurity/modsecurity.conf
sudo sed -i 's/SecRuleEngine DetectionOnly/SecRuleEngine On/' /etc/modsecurity/modsecurity.conf

# Install OWASP Core Rule Set
sudo apt install modsecurity-crs
```

Add to your Nginx server block:

```nginx
modsecurity on;
modsecurity_rules_file /etc/nginx/modsecurity_includes.conf;
```

### Layer 4: SecuNX IP Blocklist (Recommended)

**SecuNX** is a lightweight IP blocklist firewall for Nginx that automatically blocks 30,000+ known malicious IPs using curated threat intelligence feeds.

```bash
git clone https://github.com/yodabytz/secunx.git
cd secunx
sudo ./install.sh
```

Add to your Nginx server block:

```nginx
include snippets/secunx.conf;
```

GitHub: https://github.com/yodabytz/secunx

### Layer 5: Fail2Ban Intrusion Prevention (Recommended)

Fail2Ban monitors log files and automatically bans IPs that show malicious behavior (brute-force login attempts, vulnerability scanning, etc.).

#### Install Fail2Ban

```bash
sudo apt install fail2ban
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

#### Login Brute-Force Protection

Create `/etc/fail2ban/filter.d/apparix-admin.conf`:

```ini
[Definition]
failregex = client: <HOST>.*"POST /admin/login
```

Create `/etc/fail2ban/jail.d/apparix-admin.conf`:

```ini
[apparix-admin]
enabled  = true
filter   = apparix-admin
logpath  = /var/log/nginx/yourdomain.com.error.log
maxretry = 5
findtime = 600
bantime  = 3600
action   = iptables-multiport[name=apparix-admin, port="http,https", protocol=tcp]
```

#### Honeypot Scanner Protection

This jail auto-bans any IP that probes WordPress, phpMyAdmin, `.env`, or other paths that no real user would visit. One hit = 7-day ban.

Create `/etc/fail2ban/filter.d/nginx-honeypot.conf`:

```ini
[Definition]
failregex = ^<HOST> \- \S+ \[.*?\] \"(GET|POST|HEAD) \S*(wp-admin|wp-login|wp-content|wp-includes|xmlrpc\.php|wp-config|wordpress|wp-json|wp-cron|wp-signup|wp-trackback|wlwmanifest)\S* \S+\" (410|403|444) .+$
            ^<HOST> \- \S+ \[.*?\] \"(GET|POST|HEAD) \S*(phpmyadmin|pma|myadmin|mysql|admin\.php|administrator|joomla|drupal|magento)\S* \S+\" (410|403|444) .+$
            ^<HOST> \- \S+ \[.*?\] \"(GET|POST|HEAD) \S*\.env\S* \S+\" (403|444) .+$
            ^<HOST> \- \S+ \[.*?\] \"(GET|POST|HEAD) \S*cmd_sco\S* \S+\" \d+ .+$
            ^<HOST> \- \S+ \[.*?\] \"(GET|POST|HEAD) \S*\.git\S* \S+\" (403|444) .+$
            ^<HOST> \- \S+ \[.*?\] \"(GET|POST|HEAD) \S*(\.aws|\.ssh|shell\.php|setup\.php|install\.php|config\.php)\S* \S+\" (403|410|444) .+$

ignoreregex =

datepattern = {^LN-BEG}%%ExY(?P<_sep>[-/.])%%m(?P=_sep)%%d[T ]%%H:%%M:%%S(?:[.,]%%f)?(?:\s*%%z)?
              ^[^\[]*\[({DATE})
              {^LN-BEG}
```

Create `/etc/fail2ban/jail.d/nginx-honeypot.conf`:

```ini
[nginx-honeypot]
enabled  = true
filter   = nginx-honeypot
logpath  = /var/log/nginx/yourdomain.com.access.log
maxretry = 1
findtime = 86400
bantime  = 604800
action   = iptables-multiport[name=nginx-honeypot, port="http,https", protocol=tcp]
```

Reload Fail2Ban:

```bash
sudo fail2ban-client reload
sudo fail2ban-client status
```

### Layer 6: File Permissions

```bash
# Web files owned by www-data
sudo chown -R www-data:www-data /var/www/yourdomain.com

# Restrict .env file (contains database passwords and API keys)
chmod 600 /var/www/yourdomain.com/.env

# Storage directories need write access
chmod -R 775 /var/www/yourdomain.com/storage
chmod -R 775 /var/www/yourdomain.com/public/assets/images
```

### Layer 7: Database Security

```bash
# Create a dedicated MySQL user (don't use root)
mysql -u root -p
```

```sql
CREATE DATABASE apparix_ecommerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'apparix'@'localhost' IDENTIFIED BY 'your-strong-password-here';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP ON apparix_ecommerce.* TO 'apparix'@'localhost';
FLUSH PRIVILEGES;
```

- Use a strong password (16+ characters with mixed case, numbers, symbols)
- Never grant `ALL PRIVILEGES` — only grant what's needed
- Disable remote root access: `bind-address = 127.0.0.1` in `/etc/mysql/mysql.conf.d/mysqld.cnf`

### Security Summary

| Layer | Protection | Type | Setup |
|-------|-----------|------|-------|
| BotBlocker | Honeypot traps, bad user agents, auto-ban | PHP (built-in) | Automatic |
| Nginx Bot Map | Known scraper/attack user agents | Nginx | Manual config |
| Nginx Location Blocks | WordPress/CMS scanner traps (410 Gone) | Nginx | Manual config |
| Nginx Rate Limiting | Login/checkout brute-force prevention | Nginx | Manual config |
| SSL/TLS | Encrypted traffic, required for payments | Certbot | Manual setup |
| ModSecurity | SQL injection, XSS, OWASP Top 10 | Nginx WAF | Manual install |
| SecuNX | 30,000+ known malicious IPs | Nginx blocklist | Manual install |
| Fail2Ban (admin) | Login brute-force auto-ban | Log monitor | Manual install |
| Fail2Ban (honeypot) | Scanner auto-ban (1 hit = 7-day ban) | Log monitor | Manual install |
| Security Headers | Clickjacking, MIME sniffing, XSS | PHP + Nginx | Automatic |
| CSRF Protection | Cross-site request forgery | PHP (built-in) | Automatic |
| Argon2id Hashing | Secure password storage | PHP (built-in) | Automatic |

### Regular Maintenance

- **Update Apparix**: Check Admin > Updates regularly
- **Update system packages**: `sudo apt update && sudo apt upgrade`
- **Monitor Fail2Ban**: `sudo fail2ban-client status` to check banned IPs
- **Review bot logs**: Check `storage/security/bot_blocks.log` periodically
- **Renew SSL**: Certbot auto-renews, but verify with `sudo certbot renew --dry-run`

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
