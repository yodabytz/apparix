<?php
/**
 * Check Delivery Status Cron Job
 *
 * Polls AfterShip for tracking updates and auto-updates order status to "delivered"
 * Cron: Run every 2 hours
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
use App\Core\AfterShipService;
use App\Core\OrderStatusEmailService;

echo "[" . date('Y-m-d H:i:s') . "] Starting delivery status check...\n";

$afterShip = new AfterShipService();

if (!$afterShip->isConfigured()) {
    echo "AfterShip not configured. Set AFTERSHIP_API_KEY in .env\n";
    exit(0);
}

$db = Database::getInstance();

// Get all shipped orders with tracking that aren't delivered yet
$orders = $db->fetchAll(
    "SELECT id, order_number, customer_email, shipping_first_name, billing_first_name,
            tracking_carrier, tracking_number, status, total
     FROM orders
     WHERE status = 'shipped'
       AND tracking_number IS NOT NULL
       AND tracking_number != ''
       AND tracking_carrier IS NOT NULL
     ORDER BY updated_at ASC
     LIMIT 50"
);

if (empty($orders)) {
    echo "No shipped orders with tracking to check.\n";
    exit(0);
}

echo "Found " . count($orders) . " orders to check.\n";

$updated = 0;
$errors = 0;

foreach ($orders as $order) {
    echo "Checking order #{$order['order_number']} ({$order['tracking_number']})... ";

    // Rate limit - AfterShip has limits
    usleep(500000); // 0.5 second delay between requests

    $result = $afterShip->getTracking($order['tracking_number'], $order['tracking_carrier']);

    if (!$result['success']) {
        echo "Error: " . ($result['error'] ?? 'Unknown') . "\n";
        $errors++;
        continue;
    }

    $tag = $result['tag'];
    echo "Status: {$tag}";

    // Check if delivered
    if ($tag === 'Delivered') {
        echo " -> Updating to delivered... ";

        // Update order status
        $db->update(
            "UPDATE orders SET status = 'delivered', updated_at = NOW() WHERE id = ?",
            [$order['id']]
        );

        // Send delivery notification email
        try {
            $emailService = new OrderStatusEmailService();
            $emailService->sendStatusEmail($order, 'delivered');
            echo "Email sent!";
        } catch (\Throwable $e) {
            echo "Email failed: " . $e->getMessage();
        }

        $updated++;
    }

    echo "\n";
}

echo "\n[" . date('Y-m-d H:i:s') . "] Complete. Updated: {$updated}, Errors: {$errors}\n";
