#!/usr/bin/env php
<?php
/**
 * Cron Job: Send Review Request Emails
 *
 * Sends tokenized review request emails for paid orders marked delivered.
 *
 * Run this daily via cron:
 * 0 10 * * * /usr/bin/php /var/www/www.apparix.app/cron/send-review-requests.php
 */

require __DIR__ . '/bootstrap.php';

use App\Core\ReviewEmailService;

// Log start
$startTime = date('Y-m-d H:i:s');
echo "[{$startTime}] Starting review request email job...\n";

try {
    $emailService = new ReviewEmailService();
    $sent = $emailService->processPendingRequests(50);

    $endTime = date('Y-m-d H:i:s');
    echo "[{$endTime}] Completed. Sent {$sent} review request emails.\n";

} catch (\Throwable $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    error_log("Review request cron error: " . $e->getMessage());
    exit(1);
}

exit(0);
