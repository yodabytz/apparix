#!/usr/bin/env php
<?php
/**
 * Cron Job: Send Review Request Emails
 *
 * Sends review request emails to customers whose orders have been:
 * - Marked as delivered, OR
 * - Placed more than 3 weeks ago
 *
 * Run this daily via cron:
 * 0 10 * * * /usr/bin/php /var/www/www.apparix.vibrixmedia.com/cron/send-review-requests.php
 */

// Set working directory
chdir(dirname(__DIR__));

// Load autoloader and environment
require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Load core classes
require_once 'app/Core/Database.php';
require_once 'app/Core/Model.php';
require_once 'app/Models/Review.php';
require_once 'app/Core/ReviewEmailService.php';

use App\Core\ReviewEmailService;

// Log start
$startTime = date('Y-m-d H:i:s');
echo "[{$startTime}] Starting review request email job...\n";

try {
    $emailService = new ReviewEmailService();
    $sent = $emailService->processPendingRequests(50);

    $endTime = date('Y-m-d H:i:s');
    echo "[{$endTime}] Completed. Sent {$sent} review request emails.\n";

} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    error_log("Review request cron error: " . $e->getMessage());
    exit(1);
}

exit(0);
