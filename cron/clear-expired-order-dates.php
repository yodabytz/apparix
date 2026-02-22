<?php
/**
 * Clear Expired Order-By Dates
 *
 * Removes custom_date and custom_date_label from products whose deadline has passed.
 * Cron: Run daily at midnight
 * 0 0 * * * php /var/www/apparix.app/cron/clear-expired-order-dates.php >> /var/log/cron.log 2>&1
 */

// Bootstrap the application
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/vendor/autoload.php';

// Load environment variables
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

use App\Core\Database;

$db = Database::getInstance();
$cleared = $db->update("UPDATE products SET custom_date = NULL, custom_date_label = NULL WHERE custom_date < CURDATE()");

if ($cleared > 0) {
    echo date('Y-m-d H:i:s') . " Cleared expired order-by dates from {$cleared} product(s).\n";
}
