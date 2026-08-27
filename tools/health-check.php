#!/usr/bin/env php
<?php
/**
 * Apparix health check.
 *
 * Non-destructive HTTP checks for deploy/update verification. Exits non-zero if
 * any required endpoint returns a 5xx, network error, or unexpected status.
 */

declare(strict_types=1);

$options = getopt('', ['url::', 'timeout::', 'json', 'help']);
if (isset($options['help'])) {
    echo <<<HELP
Apparix Health Check

Usage:
  php tools/health-check.php --url=https://apparix.app

Options:
  --url=URL       Base URL to test (default from APP_URL in .env or https://apparix.app)
  --timeout=N    Per-request timeout seconds (default: 15)
  --json         Output JSON instead of text

HELP;
    exit(0);
}

$baseUrl = rtrim((string)($options['url'] ?? getenv('APP_URL') ?: 'https://apparix.app'), '/');
$timeout = max(3, (int)($options['timeout'] ?? 15));
$jsonOutput = array_key_exists('json', $options);

$checks = [
    ['name' => 'Homepage', 'path' => '/', 'expect' => [200]],
    ['name' => 'Products page', 'path' => '/products', 'expect' => [200, 301, 302]],
    ['name' => 'Admin login', 'path' => '/admin/login', 'expect' => [200, 301, 302]],
    ['name' => 'Update API version', 'path' => '/api/updates/version', 'expect' => [200]],
    ['name' => 'Sitemap', 'path' => '/sitemap.xml', 'expect' => [200]],
    ['name' => 'Robots', 'path' => '/robots.txt', 'expect' => [200]],
];

$results = [];
$failed = false;
foreach ($checks as $check) {
    $url = $baseUrl . $check['path'];
    $result = request($url, $timeout);
    $result['name'] = $check['name'];
    $result['path'] = $check['path'];
    $result['expected'] = $check['expect'];
    $result['ok'] = $result['error'] === null
        && in_array($result['status'], $check['expect'], true)
        && ($result['status'] < 500);
    if (!$result['ok']) {
        $failed = true;
    }
    $results[] = $result;
}

if ($jsonOutput) {
    echo json_encode(['ok' => !$failed, 'base_url' => $baseUrl, 'checks' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
} else {
    echo "Apparix health check: {$baseUrl}\n";
    echo str_repeat('-', 72) . "\n";
    foreach ($results as $result) {
        $status = $result['status'] ?? 'ERR';
        $time = number_format((float)$result['time'], 3);
        $label = $result['ok'] ? 'OK ' : 'BAD';
        echo sprintf("[%s] %-20s HTTP %-4s %ss %s\n", $label, $result['name'], (string)$status, $time, $result['path']);
        if ($result['error']) {
            echo "      Error: {$result['error']}\n";
        }
    }
}

exit($failed ? 1 : 0);

function request(string $url, int $timeout): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => 'Apparix-HealthCheck/1.0',
        CURLOPT_HEADER => false,
    ]);
    $start = microtime(true);
    $body = curl_exec($ch);
    $time = microtime(true) - $start;
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch) ?: null;
    curl_close($ch);

    $fatalPattern = null;
    if (is_string($body) && preg_match('/(Fatal error|Parse error|Warning:\s+require|Service Unavailable|HTTP ERROR 500)/i', $body, $m)) {
        $fatalPattern = $m[1];
    }

    return [
        'url' => $url,
        'status' => $status,
        'time' => round($time, 4),
        'bytes' => is_string($body) ? strlen($body) : 0,
        'error' => $error ?: ($fatalPattern ? 'Fatal-looking response content: ' . $fatalPattern : null),
    ];
}
