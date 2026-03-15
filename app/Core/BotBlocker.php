<?php

namespace App\Core;

/**
 * BotBlocker — Automatic bot detection and IP blocking
 *
 * Detects malicious bots by:
 * 1. Honeypot paths (WordPress probes, .env harvesting, cmd injection, etc.)
 * 2. Malicious user agents (HeadlessChrome, zgrab, masscan, etc.)
 * 3. Previously blocked IPs (stored in flat file for fast lookup)
 *
 * Whitelists legitimate bots (Googlebot, Bingbot, Applebot, facebookexternalhit, etc.)
 */
class BotBlocker
{
    private static ?self $instance = null;
    private string $blocklistFile;
    private string $logFile;
    private array $blockedIps = [];
    private bool $loaded = false;

    /** Ban duration in seconds (default: 7 days) */
    private int $banDuration = 604800;

    /** Max blocked IPs to store (prevents file bloat) */
    private int $maxEntries = 10000;

    /**
     * Honeypot paths — only bots/scanners hit these.
     * Matched as prefix (startsWith) against the request path.
     */
    private array $honeypotPaths = [
        // WordPress probes
        '/wp-admin', '/wp-login', '/wp-content', '/wp-includes',
        '/xmlrpc.php', '/wp-config', '/wordpress', '/wp-json',
        '/wp-cron', '/wp-signup', '/wp-trackback', '/wlwmanifest',
        '/wp-plain', '/wp-blog',
        // CMS probes
        '/joomla', '/drupal', '/magento', '/administrator',
        // Database admin probes
        '/phpmyadmin', '/pma', '/myadmin', '/mysql', '/adminer',
        '/dbadmin', '/phpMyAdmin', '/sql',
        // Shell/backdoor probes
        '/shell', '/cmd', '/cmd_sco', '/c99', '/r57',
        '/webshell', '/backdoor', '/hack',
        // Config/credential harvesting
        '/.env', '/.git', '/.aws', '/.ssh', '/.svn',
        '/.htpasswd', '/.htaccess', '/.DS_Store',
        '/config.php', '/config.json', '/config.yml',
        '/credentials', '/secrets',
        // Framework probes
        '/_next', '/vendor/phpunit', '/vendor/bin',
        '/laravel', '/telescope', '/horizon',
        '/elmah.axd', '/trace.axd',
        // Common exploit paths
        '/setup.php', '/install.php', '/admin.php',
        '/cgi-bin', '/cgi', '/scripts',
        '/.well-known/security.txt',
        // API discovery
        '/swagger', '/api-docs', '/graphql',
        '/actuator', '/health', '/debug',
    ];

    /**
     * Malicious user agents — instant block.
     * Case-insensitive partial match.
     */
    private array $badAgents = [
        'HeadlessChrome',
        'zgrab',
        'masscan',
        'nuclei',
        'httpx',
        'censys',
        'NetcraftSurveyAgent',
        'Nimbostratus',
        'sqlmap',
        'nikto',
        'dirbuster',
        'gobuster',
        'wpscan',
        'nmap',
        'ZmEu',
        'Havij',
        'w3af',
        'openvas',
        'Morpheus',
        'PetalBot',
        'Bytespider',
    ];

    /**
     * Legitimate bots — NEVER block these.
     * Case-insensitive partial match against user agent.
     */
    private array $goodBots = [
        // Search engines
        'Googlebot', 'Mediapartners-Google', 'AdsBot-Google', 'Google-InspectionTool',
        'APIs-Google', 'Storebot-Google', 'GoogleOther',
        'Bingbot', 'bingbot', 'msnbot',
        'Slurp',        // Yahoo
        'DuckDuckBot',
        'Baiduspider',
        'YandexBot',
        // Social media
        'facebookexternalhit', 'FacebookCatalog', 'Facebot',
        'Twitterbot',
        'LinkedInBot',
        'Pinterestbot', 'Pinterest',
        'WhatsApp',
        'Discordbot',
        'TelegramBot',
        'Slackbot',
        // Apple
        'Applebot',
        // Monitoring / uptime
        'UptimeRobot',
        'Site24x7',
        'StatusCake',
        'Pingdom',
        // SSL / Let's Encrypt
        "Let's Encrypt",
        // Rich preview / link unfurling
        'Embedly',
        'Quora Link Preview',
        // Feed readers
        'Feedfetcher',
        'Feedly',
        // Apparix update client
        'Apparix-Update',
    ];

    /**
     * Paths to exclude from honeypot detection (legitimate paths that
     * might accidentally match honeypot prefixes).
     */
    private array $excludedPaths = [
        '/.well-known/acme-challenge',  // Let's Encrypt validation
    ];

    private function __construct()
    {
        $storagePath = defined('BASE_PATH')
            ? BASE_PATH . '/storage/security'
            : dirname(__DIR__, 2) . '/storage/security';

        if (!is_dir($storagePath)) {
            @mkdir($storagePath, 0755, true);
        }

        $this->blocklistFile = $storagePath . '/blocked_ips.json';
        $this->logFile = $storagePath . '/bot_blocks.log';
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Main check — call this early in the request lifecycle.
     * Returns true if the request is allowed, false/exits if blocked.
     */
    public function check(): bool
    {
        $ip = $this->getClientIp();
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Never block this server's own IP or localhost (prevents self-blocking
        // from internal requests, update checks, cron jobs, etc.)
        $serverIp = $_SERVER['SERVER_ADDR'] ?? '';
        if ($ip === $serverIp || $ip === '127.0.0.1' || $ip === '::1') {
            return true;
        }

        // In Docker, SERVER_ADDR is the container IP, not the host's public IP.
        // Allow whitelisting the host IP via environment variable.
        $hostIp = $_ENV['HOST_IP'] ?? ($_SERVER['HOST_IP'] ?? '');
        if ($hostIp && $ip === $hostIp) {
            return true;
        }

        // Never block legitimate bots
        if ($this->isGoodBot($ua)) {
            return true;
        }

        // Check if IP is already blocked
        if ($this->isBlocked($ip)) {
            $this->denyRequest();
            return false;
        }

        // Check honeypot paths
        if ($this->isHoneypotHit($uri)) {
            $this->blockIp($ip, 'honeypot', $uri);
            $this->denyRequest();
            return false;
        }

        // Check malicious user agents
        if ($this->isBadAgent($ua)) {
            $this->blockIp($ip, 'bad_agent', $ua);
            $this->denyRequest();
            return false;
        }

        // Check for cloud provider bot farms (distributed scrapers using fake browser UAs)
        if ($this->isCloudBotFarm($ip, $ua)) {
            $this->blockIp($ip, 'cloud_bot', $ua);
            $this->denyRequest();
            return false;
        }

        // Check for empty user agent (most real browsers send one)
        if (empty($ua) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            // Don't block webhooks/APIs that legitimately have no UA
            $isApiOrWebhook = str_starts_with($uri, '/api/') ||
                              str_starts_with($uri, '/webhook/') ||
                              str_starts_with($uri, '/cron/');
            if (!$isApiOrWebhook) {
                $this->blockIp($ip, 'empty_ua', $uri);
                $this->denyRequest();
                return false;
            }
        }

        return true;
    }

    /**
     * Check if user agent belongs to a legitimate bot.
     */
    private function isGoodBot(string $ua): bool
    {
        if (empty($ua)) {
            return false;
        }

        foreach ($this->goodBots as $bot) {
            if (stripos($ua, $bot) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the IP is in the blocklist.
     */
    private function isBlocked(string $ip): bool
    {
        $this->loadBlocklist();

        if (!isset($this->blockedIps[$ip])) {
            return false;
        }

        // Check if ban has expired
        $entry = $this->blockedIps[$ip];
        if (time() > ($entry['blocked_at'] + $this->banDuration)) {
            unset($this->blockedIps[$ip]);
            $this->saveBlocklist();
            return false;
        }

        // Increment hit count
        $this->blockedIps[$ip]['hits'] = ($entry['hits'] ?? 0) + 1;
        // Don't save on every hit — save periodically
        if (($this->blockedIps[$ip]['hits'] % 10) === 0) {
            $this->saveBlocklist();
        }

        return true;
    }

    /**
     * Check if the URI matches a honeypot path.
     */
    private function isHoneypotHit(string $uri): bool
    {
        $uriLower = strtolower($uri);

        // Check exclusions first (e.g., Let's Encrypt)
        foreach ($this->excludedPaths as $excluded) {
            if (str_starts_with($uriLower, strtolower($excluded))) {
                return false;
            }
        }

        foreach ($this->honeypotPaths as $path) {
            if (str_starts_with($uriLower, strtolower($path))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Known cloud provider IP prefixes used by bot farms.
     * These ranges are datacenter IPs — real customers don't browse from them.
     */
    private array $cloudPrefixes = [
        // Tencent Cloud
        '101.32.', '101.33.', '101.34.', '101.42.', '101.43.', '101.46.',
        '111.119.', '111.230.', '111.231.',
        '118.24.', '118.25.', '118.89.', '118.126.', '118.212.',
        '119.28.', '119.29.', '119.45.',
        '121.4.', '121.5.', '121.51.', '121.91.',
        '124.156.', '124.220.', '124.222.', '124.223.', '124.243.',
        '129.226.', '129.204.',
        '140.143.', '148.70.',
        '150.40.', '150.109.', '150.158.',
        '152.136.',
        '154.8.', '154.40.',
        '159.75.',
        '175.24.', '175.27.',
        '182.254.',
        '189.1.',
        '190.92.',
        '211.159.',
        '212.64.',
        '49.232.', '49.233.', '49.234.', '49.235.',
        '62.234.',
        '81.68.', '81.69.', '81.70.', '81.71.',
        '82.156.', '82.157.',
        '94.74.',
        // Huawei Cloud
        '110.238.', '114.116.', '116.63.', '116.204.', '116.205.',
        '119.3.', '119.8.', '119.12.', '119.13.',
        '121.36.', '121.37.',
        '122.112.',
        '139.9.', '139.159.',
        '159.138.',
        // Alibaba Cloud
        '47.74.', '47.88.', '47.89.', '47.90.', '47.91.', '47.92.',
        '47.93.', '47.94.', '47.95.', '47.96.', '47.97.', '47.98.',
        '47.99.', '47.100.', '47.101.', '47.102.', '47.103.', '47.104.',
        '47.240.', '47.241.', '47.242.', '47.243.', '47.244.', '47.245.',
        '47.246.', '47.250.', '47.251.', '47.252.', '47.253.', '47.254.',
        '8.208.', '8.209.', '8.210.', '8.211.', '8.212.', '8.213.',
        '8.214.', '8.215.', '8.216.', '8.217.', '8.218.', '8.219.',
        '149.129.',
        '161.117.',
        // Generic datacenter indicators (Singapore/HK cloud)
        '166.108.',
        '188.239.',
    ];

    /**
     * Detect cloud provider bot farms.
     * These use real-looking browser UAs from datacenter IPs.
     */
    private function isCloudBotFarm(string $ip, string $ua): bool
    {
        if (empty($ua)) {
            return false;
        }

        // Only check if IP is from a known cloud provider range
        $isCloud = false;
        foreach ($this->cloudPrefixes as $prefix) {
            if (str_starts_with($ip, $prefix)) {
                $isCloud = true;
                break;
            }
        }

        if (!$isCloud) {
            return false;
        }

        // Cloud IP + browser-like UA = bot farm
        // Real users don't browse e-commerce sites from Tencent/Huawei/Alibaba Cloud VMs
        $hasBrowserUa = stripos($ua, 'Mozilla/5.0') !== false &&
                        (stripos($ua, 'Chrome') !== false ||
                         stripos($ua, 'Safari') !== false ||
                         stripos($ua, 'Firefox') !== false);

        if ($hasBrowserUa) {
            return true;
        }

        // Also catch malformed UAs (e.g., "Mozilla/5.0184010163 Mozilla/5.0...")
        if (preg_match('/Mozilla\/5\.0\d{3,}/', $ua)) {
            return true;
        }

        return false;
    }

    /**
     * Check if the user agent is known malicious.
     */
    private function isBadAgent(string $ua): bool
    {
        if (empty($ua)) {
            return false;
        }

        foreach ($this->badAgents as $agent) {
            if (stripos($ua, $agent) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add an IP to the blocklist.
     */
    private function blockIp(string $ip, string $reason, string $detail = ''): void
    {
        $this->loadBlocklist();

        $this->blockedIps[$ip] = [
            'blocked_at' => time(),
            'reason' => $reason,
            'detail' => substr($detail, 0, 200),
            'hits' => 1,
        ];

        // Prune expired entries if over limit
        if (count($this->blockedIps) > $this->maxEntries) {
            $this->pruneExpired();
        }

        $this->saveBlocklist();
        $this->logBlock($ip, $reason, $detail);
    }

    /**
     * Send 403 response and terminate.
     */
    private function denyRequest(): void
    {
        // Clean any output buffer
        while (ob_get_level()) {
            ob_end_clean();
        }

        http_response_code(403);
        header('Content-Type: text/plain');
        header('Connection: close');
        header('Cache-Control: no-store');
        echo "Access denied.";
        exit;
    }

    /**
     * Get the real client IP (handles proxies).
     */
    private function getClientIp(): string
    {
        // Check forwarded headers (nginx proxy)
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['HTTP_X_REAL_IP']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '0.0.0.0';

        // Take first IP if comma-separated (X-Forwarded-For chain)
        if (strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }

        return $ip;
    }

    /**
     * Load the blocklist from disk.
     */
    private function loadBlocklist(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->loaded = true;

        if (!file_exists($this->blocklistFile)) {
            $this->blockedIps = [];
            return;
        }

        $data = @file_get_contents($this->blocklistFile);
        if ($data === false) {
            $this->blockedIps = [];
            return;
        }

        $decoded = json_decode($data, true);
        $this->blockedIps = is_array($decoded) ? $decoded : [];
    }

    /**
     * Save the blocklist to disk (atomic write).
     */
    private function saveBlocklist(): void
    {
        $tmpFile = $this->blocklistFile . '.tmp';
        $data = json_encode($this->blockedIps, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (@file_put_contents($tmpFile, $data, LOCK_EX) !== false) {
            @rename($tmpFile, $this->blocklistFile);
        }
    }

    /**
     * Remove expired entries from the blocklist.
     */
    private function pruneExpired(): void
    {
        $now = time();
        $this->blockedIps = array_filter(
            $this->blockedIps,
            fn($entry) => $now <= ($entry['blocked_at'] + $this->banDuration)
        );
    }

    /**
     * Log a block event for auditing.
     */
    private function logBlock(string $ip, string $reason, string $detail): void
    {
        $line = sprintf(
            "[%s] BLOCKED ip=%s reason=%s detail=%s ua=%s\n",
            date('Y-m-d H:i:s'),
            $ip,
            $reason,
            substr($detail, 0, 100),
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 100)
        );

        @file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);

        // Rotate log if too large (> 5MB)
        if (@filesize($this->logFile) > 5 * 1024 * 1024) {
            @rename($this->logFile, $this->logFile . '.old');
        }
    }

    /**
     * Get blocked IP count (for admin display).
     */
    public function getBlockedCount(): int
    {
        $this->loadBlocklist();
        return count($this->blockedIps);
    }

    /**
     * Get all blocked IPs (for admin display).
     */
    public function getBlockedIps(): array
    {
        $this->loadBlocklist();
        return $this->blockedIps;
    }

    /**
     * Manually unblock an IP.
     */
    public function unblockIp(string $ip): bool
    {
        $this->loadBlocklist();
        if (isset($this->blockedIps[$ip])) {
            unset($this->blockedIps[$ip]);
            $this->saveBlocklist();
            return true;
        }
        return false;
    }

    /**
     * Manually block an IP.
     */
    public function addBlock(string $ip, string $reason = 'manual'): void
    {
        $this->blockIp($ip, $reason);
    }
}
