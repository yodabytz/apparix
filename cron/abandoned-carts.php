<?php
/**
 * Abandoned Cart Email Cron Job
 * Run every hour: 0 * * * * php /var/www/www.apparix.app/cron/abandoned-carts.php
 */

require_once __DIR__ . '/bootstrap.php';

use App\Models\AbandonedCart;

$abandonedCart = new AbandonedCart();

// Get carts abandoned for at least 2 hours but not more than 72 hours
$carts = $abandonedCart->getAbandonedCarts(2, 72);

$sent = 0;
$failed = 0;

foreach ($carts as $cart) {
    $items = $abandonedCart->getCartItems($cart['session_id']);

    if (empty($items)) {
        continue;
    }

    $result = $abandonedCart->sendAbandonedCartEmail($cart, $items);

    if ($result) {
        $abandonedCart->markEmailSent($cart['session_id'], $cart['email']);
        $sent++;
        echo "Sent email for session {$cart['session_id']} to {$cart['email']}\n";
    } else {
        $failed++;
        echo "Failed to send email for session {$cart['session_id']}\n";
    }

    // Rate limiting
    usleep(200000); // 0.2 second delay
}

echo "\nCompleted: {$sent} sent, {$failed} failed\n";
