<?php
/**
 * Submit queued Printify orders.
 * Run from cron, for example: php /var/www/apparix.app/cron/printify-sync-orders.php
 */

require __DIR__ . '/bootstrap.php';

use App\Core\Plugins\PluginManager;

$manager = PluginManager::getInstance();
$manager->init();
$plugin = $manager->getPlugin('printify-sync');

if (!$plugin || !method_exists($plugin, 'submitPendingOrders')) {
    echo "Printify Sync plugin is not active.\n";
    exit(0);
}

$result = $plugin->submitPendingOrders(20);
echo json_encode($result, JSON_UNESCAPED_SLASHES) . "\n";
