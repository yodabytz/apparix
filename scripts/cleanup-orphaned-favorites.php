#!/usr/bin/env php
<?php
/**
 * Cleanup Orphaned Favorites
 *
 * Removes favorites that:
 * - Have no user_id (guest favorites)
 * - Are older than 7 days
 *
 * Run daily via cron:
 * 0 3 * * * /usr/bin/php /var/www/www.apparix.vibrixmedia.com/scripts/cleanup-orphaned-favorites.php
 */

require_once __DIR__ . '/../app/Core/Database.php';

use App\Core\Database;

try {
    $db = Database::getInstance();

    // Delete orphaned favorites older than 7 days
    $result = $db->update(
        "DELETE FROM favorites
         WHERE user_id IS NULL
         AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)"
    );

    $timestamp = date('Y-m-d H:i:s');
    echo "[{$timestamp}] Cleaned up {$result} orphaned favorites\n";

} catch (Exception $e) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[{$timestamp}] Error: " . $e->getMessage() . "\n";
    exit(1);
}
