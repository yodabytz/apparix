<?php

/**
 * Wishlist Reminder Cron Job
 *
 * Sends reminder emails to users who have items in their wishlist for more than 7 days
 *
 * Recommended schedule: Daily at 10 AM
 * Crontab: 0 10 * * * php /var/www/www.apparix.app/cron/wishlist-reminders.php
 */

// Ensure this is run from CLI
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('This script must be run from the command line.');
}

// Load application
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/vendor/autoload.php';

// Load environment variables
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

use App\Core\WishlistReminderService;

echo "=== Wishlist Reminder Cron Started at " . date('Y-m-d H:i:s') . " ===\n";

try {
    $service = new WishlistReminderService();
    $results = $service->processReminders();

    echo "Processed: {$results['processed']} users\n";
    echo "Emails sent: {$results['sent']}\n";

    if (!empty($results['errors'])) {
        echo "Errors:\n";
        foreach ($results['errors'] as $error) {
            echo "  - {$error}\n";
        }
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "=== Wishlist Reminder Cron Completed at " . date('Y-m-d H:i:s') . " ===\n";
