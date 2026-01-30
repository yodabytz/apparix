<?php

/**
 * Main installer class that handles the installation process
 */
class Installer
{
    private array $config;
    private ?PDO $pdo = null;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Test database connection
     */
    public function testDatabaseConnection(): bool
    {
        $host = $this->config['db_host'] ?? 'localhost';
        $name = $this->config['db_name'] ?? '';
        $user = $this->config['db_user'] ?? '';
        $pass = $this->config['db_pass'] ?? '';

        if (empty($name) || empty($user)) {
            throw new Exception('Database name and user are required');
        }

        try {
            $dsn = "mysql:host={$host};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            return true;
        } catch (PDOException $e) {
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Run the full installation
     */
    public function run(): void
    {
        // Ensure we have a connection
        if (!$this->pdo) {
            $this->testDatabaseConnection();
        }

        // Create database if it doesn't exist
        $this->createDatabase();

        // Connect to the specific database
        $this->connectToDatabase();

        // Run migrations
        $this->runMigrations();

        // Create .env file
        $this->createEnvFile();

        // Create admin user
        $this->createAdminUser();

        // Set up store settings
        $this->setupStoreSettings();

        // Activate selected theme
        $this->activateTheme();

        // Generate cron setup script
        $this->generateCronSetupScript();

        // Create lock file
        $this->createLockFile();
    }

    /**
     * Create database if it doesn't exist
     */
    private function createDatabase(): void
    {
        $name = $this->config['db_name'];

        // Check if database exists
        $stmt = $this->pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = " . $this->pdo->quote($name));
        if (!$stmt->fetch()) {
            // Create database using query method
            $this->pdo->query("CREATE DATABASE `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }
    }

    /**
     * Connect to the specific database
     */
    private function connectToDatabase(): void
    {
        $host = $this->config['db_host'] ?? 'localhost';
        $name = $this->config['db_name'];
        $user = $this->config['db_user'];
        $pass = $this->config['db_pass'] ?? '';

        $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
        $this->pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
    }

    /**
     * Run all database migrations
     */
    private function runMigrations(): void
    {
        $migrationsPath = BASE_PATH . '/database/migrations';
        $files = glob($migrationsPath . '/*.sql');
        sort($files);

        foreach ($files as $file) {
            $sql = file_get_contents($file);
            if (!empty(trim($sql))) {
                // Execute each statement separately (handle multiple statements)
                $statements = $this->splitSqlStatements($sql);
                foreach ($statements as $statement) {
                    if (!empty(trim($statement))) {
                        $this->pdo->query($statement);
                    }
                }
            }
        }
    }

    /**
     * Split SQL into individual statements
     */
    private function splitSqlStatements(string $sql): array
    {
        // Simple split on semicolons (handles most cases)
        $statements = [];
        $current = '';
        $inString = false;
        $stringChar = '';

        for ($i = 0; $i < strlen($sql); $i++) {
            $char = $sql[$i];

            if (!$inString && ($char === '"' || $char === "'")) {
                $inString = true;
                $stringChar = $char;
            } elseif ($inString && $char === $stringChar && ($i === 0 || $sql[$i-1] !== '\\')) {
                $inString = false;
            }

            if (!$inString && $char === ';') {
                $statements[] = $current;
                $current = '';
            } else {
                $current .= $char;
            }
        }

        if (!empty(trim($current))) {
            $statements[] = $current;
        }

        return $statements;
    }

    /**
     * Create the .env configuration file
     */
    private function createEnvFile(): void
    {
        $envContent = $this->generateEnvContent();
        $envPath = BASE_PATH . '/.env';

        // Backup existing .env if it exists
        if (file_exists($envPath)) {
            copy($envPath, $envPath . '.backup.' . time());
        }

        file_put_contents($envPath, $envContent);
    }

    /**
     * Generate .env file content
     */
    private function generateEnvContent(): string
    {
        $content = "# Apparix E-Commerce Configuration\n";
        $content .= "# Generated by installer on " . date('Y-m-d H:i:s') . "\n\n";

        // Database
        $content .= "# Database Configuration\n";
        $content .= "DB_HOST={$this->config['db_host']}\n";
        $content .= "DB_NAME={$this->config['db_name']}\n";
        $content .= "DB_USER={$this->config['db_user']}\n";
        $content .= "DB_PASS={$this->config['db_pass']}\n\n";

        // Application
        $content .= "# Application Settings\n";
        $content .= "APP_DEBUG=false\n";
        $content .= "APP_URL=" . ($this->config['store_url'] ?: 'http://localhost') . "\n";
        $content .= "APP_NAME=" . addslashes($this->config['store_name']) . "\n\n";

        // Stripe
        $content .= "# Payment (Stripe)\n";
        $content .= "STRIPE_PUBLIC_KEY=" . ($this->config['stripe_public'] ?? '') . "\n";
        $content .= "STRIPE_SECRET_KEY=" . ($this->config['stripe_secret'] ?? '') . "\n\n";

        // Email
        $content .= "# Email Configuration\n";
        $content .= "MAIL_HOST=" . ($this->config['mail_host'] ?: 'localhost') . "\n";
        $content .= "MAIL_PORT=" . ($this->config['mail_port'] ?: '25') . "\n";
        $content .= "MAIL_FROM=" . ($this->config['mail_from'] ?: $this->config['store_email']) . "\n";
        $content .= "MAIL_FROM_NAME=" . addslashes($this->config['store_name']) . "\n\n";

        // Security
        $content .= "# Security\n";
        $content .= "RECAPTCHA_SITE_KEY=" . ($this->config['recaptcha_site'] ?? '') . "\n";
        $content .= "RECAPTCHA_SECRET_KEY=" . ($this->config['recaptcha_secret'] ?? '') . "\n\n";

        // Session
        $content .= "# Session Configuration\n";
        $content .= "SESSION_LIFETIME=604800\n";
        $content .= "SESSION_COOKIE_SECURE=true\n";
        $content .= "SESSION_COOKIE_HTTPONLY=true\n";
        $content .= "SESSION_COOKIE_SAMESITE=Lax\n\n";

        // Admin
        $content .= "# Admin\n";
        $content .= "ADMIN_EMAIL=" . $this->config['admin_email'] . "\n";

        return $content;
    }

    /**
     * Create the admin user account
     */
    private function createAdminUser(): void
    {
        $email = $this->config['admin_email'];
        $name = $this->config['admin_name'];
        $password = password_hash($this->config['admin_password'], PASSWORD_DEFAULT);

        // Check if admin already exists
        $stmt = $this->pdo->prepare("SELECT id FROM admin_users WHERE email = ?");
        $stmt->execute([$email]);

        if (!$stmt->fetch()) {
            $stmt = $this->pdo->prepare(
                "INSERT INTO admin_users (email, password_hash, name, role, created_at)
                 VALUES (?, ?, ?, 'super_admin', NOW())"
            );
            $stmt->execute([$email, $password, $name]);
        }
    }

    /**
     * Set up initial store settings
     */
    private function setupStoreSettings(): void
    {
        $settings = [
            ['store_name', $this->config['store_name'], 'string', 'store', 1],
            ['store_email', $this->config['store_email'] ?? '', 'string', 'store', 0],
            ['store_currency', 'USD', 'string', 'store', 1],
            ['store_currency_symbol', '$', 'string', 'store', 1],
        ];

        $stmt = $this->pdo->prepare(
            "INSERT INTO settings (setting_key, setting_value, setting_type, category, is_public)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        );

        foreach ($settings as $setting) {
            $stmt->execute($setting);
        }
    }

    /**
     * Activate the selected theme
     */
    private function activateTheme(): void
    {
        $themeSlug = $this->config['theme'] ?? 'boutique';

        // Deactivate all themes
        $this->pdo->query("UPDATE themes SET is_active = 0");

        // Activate selected theme
        $stmt = $this->pdo->prepare("UPDATE themes SET is_active = 1 WHERE slug = ?");
        $stmt->execute([$themeSlug]);
    }

    /**
     * Generate cron setup helper script
     */
    private function generateCronSetupScript(): void
    {
        $sitePath = BASE_PATH;
        $scriptContent = <<<BASH
#!/bin/bash
#
# Apparix E-Commerce Cron Jobs Setup Script
# Generated by installer on $(date '+%Y-%m-%d %H:%M:%S')
#
# This script will add the required cron jobs to your crontab.
# Run with: sudo bash setup-cron.sh
#

SITE_PATH="{$sitePath}"

# Create log files
echo "Creating log files..."
touch /var/log/apparix-cron.log
touch /var/log/google-feed.log
chmod 666 /var/log/apparix-cron.log
chmod 666 /var/log/google-feed.log

# Create temporary cron file
CRON_FILE=\$(mktemp)

# Get existing crontab (if any)
crontab -l 2>/dev/null > "\$CRON_FILE" || true

# Check if Apparix cron jobs already exist
if grep -q "Apparix E-Commerce" "\$CRON_FILE" 2>/dev/null; then
    echo "Apparix cron jobs already exist. Skipping..."
    rm "\$CRON_FILE"
    exit 0
fi

# Add Apparix cron jobs
cat >> "\$CRON_FILE" << 'CRON'

# ===========================================
# Apparix E-Commerce Cron Jobs
# ===========================================

# Abandoned cart emails - Every hour
0 * * * * php \$SITE_PATH/cron/abandoned-carts.php >> /var/log/apparix-cron.log 2>&1

# Review request emails - Daily at 10 AM
0 10 * * * php \$SITE_PATH/cron/send-review-requests.php >> /var/log/apparix-cron.log 2>&1

# Wishlist reminders - Daily at 9 AM
0 9 * * * php \$SITE_PATH/cron/wishlist-reminders.php >> /var/log/apparix-cron.log 2>&1

# Check delivery status - Every 4 hours
0 */4 * * * php \$SITE_PATH/cron/check-delivery-status.php >> /var/log/apparix-cron.log 2>&1

# Google Merchant Feed - Daily at 3 AM
0 3 * * * php \$SITE_PATH/scripts/generate-google-feed.php >> /var/log/google-feed.log 2>&1

# Cleanup orphaned favorites - Weekly on Sunday at 2 AM
0 2 * * 0 php \$SITE_PATH/scripts/cleanup-orphaned-favorites.php >> /var/log/apparix-cron.log 2>&1

# Image optimization - Daily at 4 AM
0 4 * * * php \$SITE_PATH/scripts/optimize-images.php >> /var/log/apparix-cron.log 2>&1

CRON

# Replace placeholder with actual path
sed -i "s|\\\$SITE_PATH|{$sitePath}|g" "\$CRON_FILE"

# Install new crontab
crontab "\$CRON_FILE"
rm "\$CRON_FILE"

echo ""
echo "✅ Cron jobs installed successfully!"
echo ""
echo "To verify, run: crontab -l"
echo "To view logs: tail -f /var/log/apparix-cron.log"
echo ""
BASH;

        $scriptPath = BASE_PATH . '/setup-cron.sh';
        file_put_contents($scriptPath, $scriptContent);
        chmod($scriptPath, 0755);
    }

    /**
     * Create the installation lock file
     */
    private function createLockFile(): void
    {
        $lockFile = BASE_PATH . '/storage/.installed';
        $content = json_encode([
            'installed_at' => date('Y-m-d H:i:s'),
            'version' => '1.0.0'
        ], JSON_PRETTY_PRINT);

        file_put_contents($lockFile, $content);
    }
}
