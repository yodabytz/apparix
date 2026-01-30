#!/usr/bin/env php
<?php
/**
 * Apparix Backup Cron Job
 *
 * Run every 30 minutes to check if scheduled backup is needed:
 * *!/30 * * * * php /var/www/SITEPATH/cron/backup.php >> /var/log/apparix-backup.log 2>&1
 *
 * This script checks if a scheduled backup should run based on plugin settings.
 * It uses low-priority execution to minimize server impact.
 */

// Only run via CLI
if (php_sapi_name() !== 'cli') {
    exit('This script must be run from the command line.');
}

// Set low priority (nice level) if possible
if (function_exists('proc_nice')) {
    proc_nice(10);
}

// Load the application
require_once dirname(__DIR__) . '/public/index.php';

use App\Core\Database;

echo "[" . date('Y-m-d H:i:s') . "] Backup cron started\n";

try {
    $db = Database::getInstance();

    // Check if backup plugin is active
    $plugin = $db->selectOne(
        "SELECT * FROM plugins WHERE slug = 'backup' AND is_active = 1"
    );

    if (!$plugin) {
        echo "Backup plugin is not active. Exiting.\n";
        exit(0);
    }

    // Load the backup plugin
    require_once dirname(__DIR__) . '/content/plugins/backup/BackupPlugin.php';

    $backupPlugin = new \Plugins\Backup\BackupPlugin();
    $backupPlugin->initialize();

    // Check if scheduled backup should run
    if (!$backupPlugin->shouldRunScheduledBackup()) {
        echo "No scheduled backup needed at this time.\n";
        exit(0);
    }

    echo "Starting scheduled backup...\n";

    // Create backup (CLI mode allows more time and larger files)
    $result = $backupPlugin->createBackup(true);

    if ($result['success']) {
        echo "Backup completed successfully!\n";
        echo "  Filename: {$result['filename']}\n";
        echo "  Size: {$result['size_formatted']}\n";
        echo "  Duration: {$result['duration']}s\n";
    } else {
        echo "Backup failed!\n";
        foreach ($result['errors'] as $error) {
            echo "  Error: {$error}\n";
        }
        exit(1);
    }

} catch (\Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "[" . date('Y-m-d H:i:s') . "] Backup cron finished\n";
