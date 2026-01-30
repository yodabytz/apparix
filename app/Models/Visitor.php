<?php

namespace App\Models;

use App\Core\Model;

class Visitor extends Model
{
    protected string $table = 'visitors';

    /**
     * Common bot user agent patterns
     */
    private array $botPatterns = [
        'bot', 'spider', 'crawler', 'googlebot', 'bingbot', 'yandex', 'baidu',
        'duckduckbot', 'slurp', 'facebookexternalhit', 'twitterbot', 'linkedinbot',
        'pinterest', 'semrush', 'ahrefs', 'mj12bot', 'dotbot', 'petalbot', 'bytespider',
        'applebot', 'gptbot', 'claudebot', 'anthropic', 'openai', 'headless', 'phantomjs',
        'curl', 'wget', 'python', 'scrapy', 'axios', 'node-fetch', 'go-http-client',
        'java/', 'libwww', 'uptimerobot', 'pingdom', 'monitoring', 'gtmetrix', 'pagespeed',
        'screaming frog', 'ahrefsbot', 'blexbot', 'dataforseo', 'megaindex', 'rogerbot'
    ];

    /**
     * Known bot IP CIDR ranges (Google, Bing, Facebook, Cloud providers, etc.)
     * Google: https://developers.google.com/search/apis/ipranges/googlebot.json
     */
    private array $botIpRanges = [
        // Google crawlers
        '66.249.64.0/19',    // Google crawlers (66.249.64.0 - 66.249.95.255)
        '66.249.96.0/20',    // Google additional range
        '64.233.160.0/19',   // Google
        '72.14.192.0/18',    // Google
        '74.125.0.0/16',     // Google
        '209.85.128.0/17',   // Google
        '216.239.32.0/19',   // Google
        '192.178.0.0/15',    // Google cloud crawlers
        '34.64.0.0/10',      // Google Cloud (some bots)
        '35.192.0.0/11',     // Google Cloud
        // Microsoft/Bing
        '40.77.167.0/24',    // Bing
        '157.55.39.0/24',    // Bing
        '207.46.0.0/16',     // Microsoft/Bing
        // Facebook crawlers
        '173.252.64.0/18',   // Facebook
        '66.220.144.0/20',   // Facebook
        '31.13.24.0/21',     // Facebook Ireland
        '31.13.64.0/18',     // Facebook
        '69.63.176.0/20',    // Facebook
        '69.171.224.0/19',   // Facebook
        '129.134.0.0/16',    // Facebook
        '157.240.0.0/16',    // Facebook
        // Cloud providers (scrapers/bots)
        '167.99.0.0/16',     // DigitalOcean
        '134.209.0.0/16',    // DigitalOcean
        '54.39.0.0/16',      // OVH Hosting
        '51.79.0.0/16',      // OVH
        '152.69.0.0/16',     // Oracle Cloud
        '134.185.0.0/16',    // Oracle Cloud
        '140.238.0.0/16',    // Oracle Cloud
        // Tencent Cloud (Chinese scrapers)
        '43.128.0.0/10',     // Tencent Cloud (43.128-43.191)
        '49.51.0.0/16',      // Tencent Cloud
        '170.106.0.0/16',    // Tencent Cloud
        // Hosting/VPS providers
        '45.149.0.0/16',     // Clouvider
        '23.230.0.0/16',     // EGI Hosting
        // Amazon AWS (data centers, not real users)
        '3.0.0.0/8',         // AWS (3.x.x.x)
        '13.0.0.0/8',        // AWS (13.x.x.x)
        '15.0.0.0/8',        // AWS (15.x.x.x)
        '18.0.0.0/8',        // AWS (18.x.x.x)
        '23.20.0.0/14',      // AWS (23.20-23.23)
        '35.0.0.0/8',        // AWS (35.x.x.x)
        '44.192.0.0/10',     // AWS (44.192-44.255)
        '50.16.0.0/14',      // AWS (50.16-50.19)
        '52.0.0.0/8',        // AWS (52.x.x.x)
        '54.0.0.0/8',        // AWS (54.x.x.x)
        '75.101.128.0/17',   // AWS
        '99.77.0.0/16',      // AWS
        '99.80.0.0/12',      // AWS
        '107.20.0.0/14',     // AWS (107.20-107.23)
        '174.129.0.0/16',    // AWS
        '184.72.0.0/15',     // AWS
        // Microsoft Azure
        '20.0.0.0/8',        // Azure (20.x.x.x)
        '40.0.0.0/8',        // Azure (40.x.x.x)
        '51.0.0.0/8',        // Azure (51.x.x.x)
        '52.0.0.0/8',        // Azure (52.x.x.x)
        '104.40.0.0/13',     // Azure
    ];

    /**
     * Check if IP is in a CIDR range
     */
    private function ipInRange(string $ip, string $cidr): bool
    {
        if (strpos($ip, ':') !== false) {
            return false; // Skip IPv6 for now
        }

        list($subnet, $bits) = explode('/', $cidr);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - (int)$bits);
        $subnet &= $mask;

        return ($ip & $mask) === $subnet;
    }

    /**
     * Check if IP belongs to a known bot network
     */
    public function isBotIp(?string $ip): bool
    {
        if (empty($ip)) {
            return false;
        }

        foreach ($this->botIpRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user agent is a bot
     */
    public function isBot(?string $userAgent, ?string $ip = null): bool
    {
        // Check IP first (catches Google Shopping bots with normal UA)
        if ($ip && $this->isBotIp($ip)) {
            return true;
        }

        if (empty($userAgent)) {
            return true; // Empty user agent is likely a bot
        }

        $ua = strtolower($userAgent);
        foreach ($this->botPatterns as $pattern) {
            if (strpos($ua, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Bot indicator URLs - only bots request these
     */
    private array $botUrls = [
        'robots.txt',
        'sitemap.xml',
        '.well-known/',
        'wp-login.php',
        'wp-admin',
        'xmlrpc.php',
        'wp-includes',
        'wp-content',
        '/admin',
        'phpmyadmin',
        '.env',
        '.git',
    ];

    /**
     * API-only endpoints - real users never hit these directly
     * They are called by JavaScript after loading a page
     */
    private array $apiOnlyUrls = [
        '/favorites/ids',
        '/reviews/product',
        '/cart/count',
        '/cart/add',
        '/api/',
    ];

    /**
     * Check if URL indicates bot/scanner activity
     */
    public function isBotUrl(?string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        $urlLower = strtolower($url);
        foreach ($this->botUrls as $pattern) {
            if (strpos($urlLower, strtolower($pattern)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if URL is an API-only endpoint (called by JS, not directly)
     */
    public function isApiOnlyUrl(?string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        foreach ($this->apiOnlyUrls as $pattern) {
            if (strpos($url, $pattern) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find and flag IPs that only hit API endpoints (no real page views)
     * Returns count of flagged bots
     */
    public function flagApiOnlyBots(): int
    {
        // Build pattern for API-only URLs
        $apiPatterns = array_map(function($url) {
            return "page_url LIKE '" . addslashes($url) . "%'";
        }, $this->apiOnlyUrls);
        $apiWhere = '(' . implode(' OR ', $apiPatterns) . ')';

        // Find IPs where ALL visits are to API endpoints only
        $sql = "
            UPDATE visitors v
            INNER JOIN (
                SELECT ip_address
                FROM visitors
                WHERE is_bot = 0
                GROUP BY ip_address
                HAVING
                    SUM(CASE WHEN {$apiWhere} THEN 1 ELSE 0 END) = COUNT(*)
                    AND COUNT(*) > 0
            ) api_only ON v.ip_address = api_only.ip_address
            SET v.is_bot = 1
            WHERE v.is_bot = 0
        ";

        return $this->db->update($sql, []);
    }

    /**
     * Track a page visit
     */
    public function track(array $data): int|string
    {
        $userAgent = $data['user_agent'] ?? '';
        $ipAddress = $data['ip_address'] ?? '';
        $pageUrl = $data['page_url'] ?? '';

        // Check user agent, IP, and URL patterns for bot detection
        $isBot = $this->isBot($userAgent, $ipAddress) || $this->isBotUrl($pageUrl);

        return $this->create([
            'ip_address' => $ipAddress,
            'user_agent' => substr($userAgent, 0, 500),
            'referrer' => substr($data['referrer'] ?? '', 0, 500),
            'page_url' => substr($data['page_url'] ?? '', 0, 500),
            'country' => $data['country'] ?? null,
            'country_code' => $data['country_code'] ?? null,
            'city' => $data['city'] ?? null,
            'session_id' => $data['session_id'] ?? null,
            'is_bot' => $isBot ? 1 : 0
        ]);
    }

    /**
     * Get unique visitors count for a period
     */
    public function getUniqueVisitors(string $period = 'today', ?bool $botsOnly = false): int
    {
        $where = $this->getPeriodWhere($period);
        $botWhere = $this->getBotWhere($botsOnly);
        $result = $this->queryOne(
            "SELECT COUNT(DISTINCT ip_address) as count FROM {$this->table} WHERE {$where} AND {$botWhere}"
        );
        return (int)($result['count'] ?? 0);
    }

    /**
     * Get total page views for a period
     */
    public function getPageViews(string $period = 'today', ?bool $botsOnly = false): int
    {
        $where = $this->getPeriodWhere($period);
        $botWhere = $this->getBotWhere($botsOnly);
        $result = $this->queryOne(
            "SELECT COUNT(*) as count FROM {$this->table} WHERE {$where} AND {$botWhere}"
        );
        return (int)($result['count'] ?? 0);
    }

    /**
     * Get visitors by country
     */
    public function getByCountry(string $period = 'all', int $limit = 10, ?bool $botsOnly = false): array
    {
        $where = $period === 'all' ? '1=1' : $this->getPeriodWhere($period);
        $botWhere = $this->getBotWhere($botsOnly);
        return $this->query(
            "SELECT country, country_code, COUNT(DISTINCT ip_address) as unique_visitors, COUNT(*) as page_views
             FROM {$this->table}
             WHERE {$where} AND {$botWhere} AND country IS NOT NULL
             GROUP BY country, country_code
             ORDER BY unique_visitors DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Get top referrers
     */
    public function getTopReferrers(string $period = 'all', int $limit = 10, ?bool $botsOnly = false): array
    {
        $where = $period === 'all' ? '1=1' : $this->getPeriodWhere($period);
        $botWhere = $this->getBotWhere($botsOnly);
        return $this->query(
            "SELECT
                CASE
                    WHEN referrer IS NULL OR referrer = '' THEN 'Direct'
                    ELSE SUBSTRING_INDEX(SUBSTRING_INDEX(referrer, '://', -1), '/', 1)
                END as source,
                COUNT(DISTINCT ip_address) as unique_visitors,
                COUNT(*) as page_views
             FROM {$this->table}
             WHERE {$where} AND {$botWhere}
             GROUP BY source
             ORDER BY unique_visitors DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Get top pages
     */
    public function getTopPages(string $period = 'all', int $limit = 10, ?bool $botsOnly = false): array
    {
        $where = $period === 'all' ? '1=1' : $this->getPeriodWhere($period);
        $botWhere = $this->getBotWhere($botsOnly);
        return $this->query(
            "SELECT page_url, COUNT(*) as views, COUNT(DISTINCT ip_address) as unique_visitors
             FROM {$this->table}
             WHERE {$where} AND {$botWhere}
             GROUP BY page_url
             ORDER BY views DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Get top bots by user agent
     */
    public function getTopBots(string $period = 'all', int $limit = 10): array
    {
        $where = $period === 'all' ? '1=1' : $this->getPeriodWhere($period);
        return $this->query(
            "SELECT
                CASE
                    WHEN LOWER(user_agent) LIKE '%googlebot%'
                         OR ip_address LIKE '66.249.%'
                         OR ip_address LIKE '64.233.%'
                         OR ip_address LIKE '72.14.%'
                         OR ip_address LIKE '74.125.%'
                         OR ip_address LIKE '209.85.%'
                         OR ip_address LIKE '216.239.%' THEN 'Googlebot'
                    WHEN LOWER(user_agent) LIKE '%bingbot%'
                         OR ip_address LIKE '40.77.167.%'
                         OR ip_address LIKE '157.55.39.%'
                         OR ip_address LIKE '207.46.%' THEN 'Bingbot'
                    WHEN LOWER(user_agent) LIKE '%yandex%' THEN 'YandexBot'
                    WHEN LOWER(user_agent) LIKE '%baidu%' THEN 'Baiduspider'
                    WHEN LOWER(user_agent) LIKE '%facebookexternalhit%'
                         OR ip_address LIKE '173.252.%'
                         OR ip_address LIKE '66.220.%'
                         OR ip_address LIKE '31.13.%'
                         OR ip_address LIKE '69.63.%'
                         OR ip_address LIKE '69.171.%'
                         OR ip_address LIKE '129.134.%'
                         OR ip_address LIKE '157.240.%' THEN 'Facebook'
                    WHEN LOWER(user_agent) LIKE '%twitterbot%' THEN 'Twitterbot'
                    WHEN LOWER(user_agent) LIKE '%linkedinbot%' THEN 'LinkedInBot'
                    WHEN LOWER(user_agent) LIKE '%pinterest%' THEN 'Pinterest'
                    WHEN LOWER(user_agent) LIKE '%applebot%' THEN 'Applebot'
                    WHEN LOWER(user_agent) LIKE '%semrush%' THEN 'Semrush'
                    WHEN LOWER(user_agent) LIKE '%ahrefs%' THEN 'Ahrefs'
                    WHEN LOWER(user_agent) LIKE '%mj12bot%' THEN 'Majestic'
                    WHEN LOWER(user_agent) LIKE '%dotbot%' THEN 'DotBot'
                    WHEN LOWER(user_agent) LIKE '%petalbot%' THEN 'PetalBot'
                    WHEN LOWER(user_agent) LIKE '%bytespider%' THEN 'ByteSpider'
                    WHEN LOWER(user_agent) LIKE '%gptbot%' THEN 'GPTBot'
                    WHEN LOWER(user_agent) LIKE '%claudebot%' OR LOWER(user_agent) LIKE '%anthropic%' THEN 'ClaudeBot'
                    WHEN LOWER(user_agent) LIKE '%duckduckbot%' THEN 'DuckDuckBot'
                    WHEN LOWER(user_agent) LIKE '%uptimerobot%' THEN 'UptimeRobot'
                    WHEN LOWER(user_agent) LIKE '%curl%' THEN 'cURL'
                    WHEN LOWER(user_agent) LIKE '%python%' THEN 'Python'
                    WHEN user_agent IS NULL OR user_agent = '' THEN 'Unknown'
                    ELSE 'Other Bot'
                END as bot_name,
                COUNT(*) as hits,
                COUNT(DISTINCT ip_address) as unique_ips
             FROM {$this->table}
             WHERE {$where} AND is_bot = 1
             GROUP BY bot_name
             ORDER BY hits DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Get daily stats for chart
     */
    public function getDailyStats(int $days = 30, ?bool $botsOnly = false): array
    {
        $botWhere = $this->getBotWhere($botsOnly);
        return $this->query(
            "SELECT DATE(created_at) as date,
                    COUNT(DISTINCT ip_address) as unique_visitors,
                    COUNT(*) as page_views
             FROM {$this->table}
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
               AND {$botWhere}
             GROUP BY DATE(created_at)
             ORDER BY date ASC",
            [$days]
        );
    }

    /**
     * Get hourly stats for chart (today or yesterday)
     */
    public function getHourlyStats(?bool $botsOnly = false, string $day = 'today'): array
    {
        $botWhere = $this->getBotWhere($botsOnly);
        $dateWhere = $day === 'yesterday'
            ? 'DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)'
            : 'DATE(created_at) = CURDATE()';

        return $this->query(
            "SELECT HOUR(created_at) as hour,
                    COUNT(DISTINCT ip_address) as unique_visitors,
                    COUNT(*) as page_views
             FROM {$this->table}
             WHERE {$dateWhere}
               AND {$botWhere}
             GROUP BY HOUR(created_at)
             ORDER BY hour ASC"
        );
    }

    /**
     * Get recent visitors (humans only by default)
     */
    public function getRecent(int $limit = 20, ?bool $botsOnly = false): array
    {
        $botWhere = $this->getBotWhere($botsOnly);
        return $this->query(
            "SELECT * FROM {$this->table}
             WHERE {$botWhere}
             ORDER BY created_at DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Get stats summary (humans only)
     */
    public function getStatsSummary(): array
    {
        return [
            'today' => [
                'unique' => $this->getUniqueVisitors('today', false),
                'views' => $this->getPageViews('today', false)
            ],
            'yesterday' => [
                'unique' => $this->getUniqueVisitors('yesterday', false),
                'views' => $this->getPageViews('yesterday', false)
            ],
            'week' => [
                'unique' => $this->getUniqueVisitors('week', false),
                'views' => $this->getPageViews('week', false)
            ],
            'month' => [
                'unique' => $this->getUniqueVisitors('month', false),
                'views' => $this->getPageViews('month', false)
            ],
            'all' => [
                'unique' => $this->getUniqueVisitors('all', false),
                'views' => $this->getPageViews('all', false)
            ]
        ];
    }

    /**
     * Get bot stats summary
     */
    public function getBotStatsSummary(): array
    {
        return [
            'today' => [
                'unique' => $this->getUniqueVisitors('today', true),
                'views' => $this->getPageViews('today', true)
            ],
            'yesterday' => [
                'unique' => $this->getUniqueVisitors('yesterday', true),
                'views' => $this->getPageViews('yesterday', true)
            ],
            'week' => [
                'unique' => $this->getUniqueVisitors('week', true),
                'views' => $this->getPageViews('week', true)
            ],
            'month' => [
                'unique' => $this->getUniqueVisitors('month', true),
                'views' => $this->getPageViews('month', true)
            ],
            'all' => [
                'unique' => $this->getUniqueVisitors('all', true),
                'views' => $this->getPageViews('all', true)
            ]
        ];
    }

    /**
     * Get WHERE clause for period
     */
    private function getPeriodWhere(string $period): string
    {
        return match($period) {
            'today' => 'DATE(created_at) = CURDATE()',
            'yesterday' => 'DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)',
            'week' => 'created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)',
            'month' => 'created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)',
            default => '1=1'
        };
    }

    /**
     * Get WHERE clause for bot filtering
     */
    private function getBotWhere(?bool $botsOnly): string
    {
        if ($botsOnly === true) {
            return 'is_bot = 1';
        } elseif ($botsOnly === false) {
            return 'is_bot = 0';
        }
        return '1=1'; // null means all
    }

    /**
     * Clean old records (keep last X days)
     */
    public function cleanOld(int $keepDays = 90): int
    {
        return $this->db->update(
            "DELETE FROM {$this->table} WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$keepDays]
        );
    }

    /**
     * Get device/OS stats from user agent (grouped by unique visitor/IP)
     */
    public function getDeviceStats(string $period = 'month', int $limit = 10, ?bool $botsOnly = false): array
    {
        $where = $period === 'all' ? '1=1' : $this->getPeriodWhere($period);
        $botWhere = $this->getBotWhere($botsOnly);

        // First get each IP's primary device (most used), then count
        return $this->query(
            "SELECT device_os, COUNT(*) as unique_visitors
             FROM (
                SELECT ip_address,
                    CASE
                        WHEN LOWER(user_agent) LIKE '%iphone%' THEN 'iOS (iPhone)'
                        WHEN LOWER(user_agent) LIKE '%ipad%' THEN 'iOS (iPad)'
                        WHEN LOWER(user_agent) LIKE '%android%' AND LOWER(user_agent) LIKE '%mobile%' THEN 'Android (Mobile)'
                        WHEN LOWER(user_agent) LIKE '%android%' THEN 'Android (Tablet)'
                        WHEN LOWER(user_agent) LIKE '%windows nt 10%' THEN 'Windows 10/11'
                        WHEN LOWER(user_agent) LIKE '%windows nt 6.3%' THEN 'Windows 8.1'
                        WHEN LOWER(user_agent) LIKE '%windows nt 6.1%' THEN 'Windows 7'
                        WHEN LOWER(user_agent) LIKE '%windows%' THEN 'Windows (Other)'
                        WHEN LOWER(user_agent) LIKE '%macintosh%' OR LOWER(user_agent) LIKE '%mac os x%' THEN 'macOS'
                        WHEN LOWER(user_agent) LIKE '%linux%' AND LOWER(user_agent) NOT LIKE '%android%' THEN 'Linux'
                        WHEN LOWER(user_agent) LIKE '%cros%' THEN 'Chrome OS'
                        WHEN user_agent IS NULL OR user_agent = '' THEN 'Unknown'
                        ELSE 'Other'
                    END as device_os
                FROM {$this->table}
                WHERE {$where} AND {$botWhere}
                GROUP BY ip_address, device_os
             ) as visitor_devices
             GROUP BY device_os
             ORDER BY unique_visitors DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Get browser stats from user agent (grouped by unique visitor/IP)
     */
    public function getBrowserStats(string $period = 'month', int $limit = 10, ?bool $botsOnly = false): array
    {
        $where = $period === 'all' ? '1=1' : $this->getPeriodWhere($period);
        $botWhere = $this->getBotWhere($botsOnly);

        // Each IP counts once per browser they used
        return $this->query(
            "SELECT browser, COUNT(*) as unique_visitors
             FROM (
                SELECT ip_address,
                    CASE
                        WHEN LOWER(user_agent) LIKE '%edg/%' OR LOWER(user_agent) LIKE '%edge/%' THEN 'Edge'
                        WHEN LOWER(user_agent) LIKE '%opr/%' OR LOWER(user_agent) LIKE '%opera%' THEN 'Opera'
                        WHEN LOWER(user_agent) LIKE '%chrome%' AND LOWER(user_agent) NOT LIKE '%chromium%' THEN 'Chrome'
                        WHEN LOWER(user_agent) LIKE '%safari%' AND LOWER(user_agent) NOT LIKE '%chrome%' THEN 'Safari'
                        WHEN LOWER(user_agent) LIKE '%firefox%' THEN 'Firefox'
                        WHEN LOWER(user_agent) LIKE '%msie%' OR LOWER(user_agent) LIKE '%trident%' THEN 'Internet Explorer'
                        WHEN LOWER(user_agent) LIKE '%samsung%' THEN 'Samsung Browser'
                        WHEN LOWER(user_agent) LIKE '%ucbrowser%' THEN 'UC Browser'
                        WHEN LOWER(user_agent) LIKE '%brave%' THEN 'Brave'
                        WHEN user_agent IS NULL OR user_agent = '' THEN 'Unknown'
                        ELSE 'Other'
                    END as browser
                FROM {$this->table}
                WHERE {$where} AND {$botWhere}
                GROUP BY ip_address, browser
             ) as visitor_browsers
             GROUP BY browser
             ORDER BY unique_visitors DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Get HTTP status code stats from nginx access log
     */
    public function getHttpStatusStats(string $period = 'month', int $limit = 10): array
    {
        // Use site-specific log file (configurable via env or defaults)
        $logFile = $_ENV['NGINX_ACCESS_LOG'] ?? '/var/log/nginx/access.log';

        if (!file_exists($logFile) || !is_readable($logFile)) {
            return [];
        }

        // Determine date cutoff based on period
        $cutoffTime = match($period) {
            'today' => strtotime('today'),
            'yesterday' => strtotime('yesterday'),
            'week' => strtotime('-7 days'),
            'month' => strtotime('-30 days'),
            default => 0 // all time
        };

        // For 'all', read more of the log
        $readBytes = ($period === 'all') ? 2000000 : 500000;

        // Read from access log
        $lines = [];
        $fp = fopen($logFile, 'r');
        if ($fp) {
            $fileSize = filesize($logFile);
            $seekPos = max(0, $fileSize - $readBytes);
            fseek($fp, $seekPos, SEEK_SET);
            if ($seekPos > 0) fgets($fp); // Skip partial line
            while (!feof($fp)) {
                $lines[] = fgets($fp);
            }
            fclose($fp);
        }

        // Static file extensions to exclude (to match page view counts)
        $staticExtensions = ['css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot', 'map'];

        $statusCounts = [];
        foreach ($lines as $line) {
            // Parse nginx combined log format: IP - - [28/Dec/2025:10:30:45 -0500] "GET /path HTTP/1.1" 200 1234 "ref" "ua"
            if (preg_match('/\[(\d{2})\/(\w{3})\/(\d{4}):(\d{2}):(\d{2}):(\d{2})[^\]]+\]\s+"[A-Z]+\s+([^"]+)\s+HTTP[^"]*"\s*(\d{3})\s+\d+\s+"/', $line, $matches)) {
                $day = $matches[1];
                $month = $matches[2];
                $year = $matches[3];
                $requestPath = $matches[7];
                $status = $matches[8];

                // Skip static assets
                $extension = strtolower(pathinfo(parse_url($requestPath, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
                if (in_array($extension, $staticExtensions)) {
                    continue;
                }

                // Parse the date
                $logTime = strtotime("{$day} {$month} {$year}");

                // Filter by period
                if ($period === 'yesterday') {
                    if (date('Y-m-d', $logTime) !== date('Y-m-d', strtotime('yesterday'))) {
                        continue;
                    }
                } elseif ($cutoffTime > 0 && $logTime < $cutoffTime) {
                    continue;
                }

                $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
            }
        }

        // Sort by count descending
        arsort($statusCounts);

        $statusLabels = [
            // 2xx Success
            '200' => 'OK',
            '201' => 'Created',
            '204' => 'No Content',
            '206' => 'Partial Content',
            // 3xx Redirects
            '301' => 'Moved Permanently',
            '302' => 'Found (Redirect)',
            '304' => 'Not Modified',
            '307' => 'Temporary Redirect',
            '308' => 'Permanent Redirect',
            // 4xx Client Errors
            '400' => 'Bad Request',
            '401' => 'Unauthorized',
            '403' => 'Forbidden',
            '404' => 'Not Found',
            '405' => 'Method Not Allowed',
            '408' => 'Request Timeout',
            '429' => 'Too Many Requests',
            // 5xx Server Errors
            '500' => 'Server Error',
            '502' => 'Bad Gateway',
            '503' => 'Service Unavailable',
            '504' => 'Gateway Timeout'
        ];

        $result = [];
        foreach (array_slice($statusCounts, 0, $limit, true) as $code => $count) {
            $result[] = [
                'status_code' => $code,
                'label' => $statusLabels[$code] ?? "Status {$code}",
                'count' => $count,
                'type' => $this->getStatusType($code)
            ];
        }

        return $result;
    }

    /**
     * Get status code type for coloring
     */
    private function getStatusType(string $code): string
    {
        return match(substr($code, 0, 1)) {
            '2' => 'success',
            '3' => 'redirect',
            '4' => 'client_error',
            '5' => 'server_error',
            default => 'unknown'
        };
    }
}
