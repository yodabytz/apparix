<?php

/**
 * Database Configuration
 * Requires environment variables - no fallback values for security
 */

// Ensure required environment variables are set
$required = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
foreach ($required as $var) {
    if (empty($_ENV[$var])) {
        throw new RuntimeException("Required environment variable {$var} is not set. Check your .env file.");
    }
}

return [
    'host' => $_ENV['DB_HOST'],
    'name' => $_ENV['DB_NAME'],
    'user' => $_ENV['DB_USER'],
    'pass' => $_ENV['DB_PASS'],
    'charset' => 'utf8mb4',
];
