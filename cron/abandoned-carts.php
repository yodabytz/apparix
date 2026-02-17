<?php
/**
 * Abandoned Cart Email Cron Job
 * Run every hour: 0 * * * * php /var/www/www.apparix.vibrixmedia.com/cron/abandoned-carts.php
 */

require_once dirname(__DIR__) . '/public/index.php';

use App\Models\AbandonedCart;

// Only run via CLI
if (php_sapi_name() !== 'cli') {
    exit('This script must be run from the command line.');
}

$abandonedCart = new AbandonedCart();

// Get carts abandoned for at least 2 hours but not more than 72 hours
$carts = $abandonedCart->getAbandonedCarts(2, 72);

$sent = 0;
$failed = 0;

foreach ($carts as $cart) {
    $items = $abandonedCart->getCartItems($cart['id']);

    if (empty($items)) {
        continue;
    }

    $result = $abandonedCart->sendAbandonedCartEmail($cart, $items);

    if ($result) {
        $abandonedCart->markEmailSent($cart['id']);
        $sent++;
        echo "Sent email for cart #{$cart['id']} to " . ($cart['email'] ?? $cart['user_email']) . "\n";
    } else {
        $failed++;
        echo "Failed to send email for cart #{$cart['id']}\n";
    }

    // Rate limiting
    usleep(200000); // 0.2 second delay
}

echo "\nCompleted: {$sent} sent, {$failed} failed\n";
