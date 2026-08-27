<?php
/**
 * CLI Bootstrap for Cron Jobs
 * Loads env, autoloader, database — without web-only stuff
 * (no BotBlocker, sessions, security headers, routing, license check)
 */

if (php_sapi_name() !== 'cli') {
    exit('This script must be run from the command line.');
}

define('BASE_PATH', dirname(__DIR__));
define('PUBLIC_PATH', BASE_PATH . '/public');

// Load environment variables
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

if (empty($_ENV['DB_HOST']) || empty($_ENV['DB_NAME']) || empty($_ENV['DB_USER'])) {
    die('Database configuration missing. Check your .env file.');
}

// Autoload composer dependencies + app helpers
require BASE_PATH . '/vendor/autoload.php';

// Autoload application classes
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

// Initialize database
use App\Core\Database;
try {
    Database::getInstance();
} catch (Exception $e) {
    die('Database connection failed: ' . $e->getMessage());
}
