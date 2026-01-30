<?php

/**
 * Wishlist Reminder Cron Job
 *
 * Sends reminder emails to users who have items in their wishlist for more than 7 days
 *
 * Recommended schedule: Daily at 10 AM
 * Crontab: 0 10 * * * php /var/www/www.apparix.vibrixmedia.com/cron/wishlist-reminders.php
 */

// Ensure this is run from CLI
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('This script must be run from the command line.');
}

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

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
