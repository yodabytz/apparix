<?php
/**
 * Check Delivery Status Cron Job
 *
 * Polls 17track for tracking updates and auto-updates order status to "delivered"
 * 17track auto-detects carrier — no carrier specification needed
 * Cron: Run every 2 hours
 */

require_once __DIR__ . '/bootstrap.php';

use App\Core\Database;
use App\Core\TrackingService;
use App\Core\OrderStatusEmailService;

echo "[" . date('Y-m-d H:i:s') . "] Starting delivery status check...\n";

$tracker = new TrackingService();

if (!$tracker->isConfigured()) {
    echo "Tracking not configured. Set API key in Admin > Settings > Integrations.\n";
    exit(0);
}

$db = Database::getInstance();

// Get all shipped orders with tracking numbers (carrier not required — 17track auto-detects)
$orders = $db->select(
    "SELECT id, order_number, customer_email, tracking_number, status, total
     FROM orders
     WHERE status = 'shipped'
       AND tracking_number IS NOT NULL
       AND tracking_number != ''
     ORDER BY updated_at ASC
     LIMIT 40"
);

if (empty($orders)) {
    echo "No shipped orders with tracking to check.\n";
    exit(0);
}

echo "Found " . count($orders) . " orders to check.\n";

// Build tracking number list and map back to orders
$trackingMap = [];
$trackingNumbers = [];
foreach ($orders as $order) {
    $num = trim($order['tracking_number']);
    $trackingNumbers[] = $num;
    $trackingMap[$num] = $order;
}

// Step 1: Register all tracking numbers (17track requires registration before lookup)
echo "Registering tracking numbers...\n";
$regResult = $tracker->registerTracking($trackingNumbers);
if (!$regResult['success']) {
    echo "Registration failed: " . ($regResult['error'] ?? 'Unknown') . "\n";
    // Continue anyway — numbers may already be registered from a previous run
}

// Brief pause to let 17track process registrations
sleep(2);

// Step 2: Get tracking status for all numbers in one batch call
echo "Fetching tracking status...\n";
$statusResult = $tracker->getTrackingStatus($trackingNumbers);

if (!$statusResult['success']) {
    echo "Failed to get tracking status: " . ($statusResult['error'] ?? 'Unknown') . "\n";
    exit(1);
}

$updated = 0;
$errors = 0;

foreach ($statusResult['results'] as $trackingNumber => $info) {
    $order = $trackingMap[$trackingNumber] ?? null;
    if (!$order) continue;

    $status = $info['status'];
    $carrier = $info['carrier'] ?? 'Unknown';
    echo "  #{$order['order_number']} ({$carrier}): {$status}";

    if ($status === 'Delivered') {
        echo " -> Updating to delivered... ";

        $db->update(
            "UPDATE orders SET status = 'delivered', updated_at = NOW() WHERE id = ?",
            [$order['id']]
        );

        // Log the tracking event
        try {
            $db->insert(
                "INSERT INTO tracking_events (order_id, carrier, tracking_number, status, location, description, event_time)
                 VALUES (?, ?, ?, 'delivered', ?, ?, ?)",
                [
                    $order['id'],
                    $carrier,
                    $trackingNumber,
                    $info['location'] ?? null,
                    $info['description'] ?? 'Package delivered',
                    $info['event_time'] ?? date('Y-m-d H:i:s')
                ]
            );
        } catch (\Throwable $e) {
            // tracking_events table may not exist on all installs
        }

        // Send delivery notification email
        try {
            $emailService = new OrderStatusEmailService();
            $emailService->sendStatusEmail($order, 'delivered');
            echo "Email sent!";
        } catch (\Throwable $e) {
            echo "Email failed: " . $e->getMessage();
        }

        // Stop tracking this number to free up quota
        $tracker->stopTracking([$trackingNumber]);

        $updated++;
    } elseif ($status === 'NotFound') {
        echo " (not yet in carrier system)";
    }

    echo "\n";
}

echo "\n[" . date('Y-m-d H:i:s') . "] Complete. Checked: " . count($statusResult['results']) . ", Updated: {$updated}\n";
