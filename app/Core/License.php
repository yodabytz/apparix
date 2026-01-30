<?php

namespace App\Core;

/**
 * License validation for Apparix E-Commerce Platform
 *
 * Serial format: APX-XXXXX-XXXXX-XXXXX-XXXXX
 * Contains encoded data: domain binding (optional), edition, checksum
 */
class License
{
    /**
     * Secret key for license generation/validation
     * IMPORTANT: Change this before distributing!
     */
    private const SECRET_KEY = 'Apparix_2024_Lic3ns3_S3cr3t_K3y_Ch@ng3_M3!';

    /**
     * Product prefix
     */
    private const PREFIX = 'APX';

    /**
     * Valid edition codes
     */
    private const EDITIONS = [
        'F' => 'free',
        'S' => 'standard',
        'P' => 'professional',
        'E' => 'enterprise',
        'D' => 'developer',
        'U' => 'unlimited'
    ];

    /**
     * Edition feature limits and capabilities
     */
    private const EDITION_FEATURES = [
        'F' => [ // Free (no license)
            'max_products' => 10,
            'max_admin_users' => 1,
            'max_orders_month' => 50,
            'api_access' => false,
            'advanced_analytics' => false,
            'bulk_import_export' => false,
            'abandoned_cart' => false,
            'multi_currency' => false,
            'priority_support' => false,
            'custom_theme' => false,
            'coupons' => false,
            'newsletter' => false,
            'disable_branding' => false,
        ],
        'S' => [ // Standard
            'max_products' => 100,
            'max_admin_users' => 1,
            'max_orders_month' => 500,
            'api_access' => false,
            'advanced_analytics' => false,
            'bulk_import_export' => false,
            'abandoned_cart' => false,
            'multi_currency' => false,
            'priority_support' => false,
            'custom_theme' => true,
            'coupons' => true,
            'newsletter' => true,
            'disable_branding' => true,
        ],
        'P' => [ // Professional
            'max_products' => 1000,
            'max_admin_users' => 5,
            'max_orders_month' => 5000,
            'api_access' => true,
            'advanced_analytics' => true,
            'bulk_import_export' => true,
            'abandoned_cart' => false,
            'multi_currency' => false,
            'priority_support' => false,
            'custom_theme' => true,
            'coupons' => true,
            'newsletter' => true,
            'disable_branding' => true,
        ],
        'E' => [ // Enterprise
            'max_products' => -1, // unlimited
            'max_admin_users' => -1,
            'max_orders_month' => -1,
            'api_access' => true,
            'advanced_analytics' => true,
            'bulk_import_export' => true,
            'abandoned_cart' => true,
            'multi_currency' => true,
            'priority_support' => true,
            'custom_theme' => true,
            'coupons' => true,
            'newsletter' => true,
            'disable_branding' => true,
        ],
        'D' => [ // Developer (same as Enterprise, for development)
            'max_products' => -1,
            'max_admin_users' => -1,
            'max_orders_month' => -1,
            'api_access' => true,
            'advanced_analytics' => true,
            'bulk_import_export' => true,
            'abandoned_cart' => true,
            'multi_currency' => true,
            'priority_support' => false,
            'custom_theme' => true,
            'coupons' => true,
            'newsletter' => true,
            'disable_branding' => true,
        ],
        'U' => [ // Unlimited (everything)
            'max_products' => -1,
            'max_admin_users' => -1,
            'max_orders_month' => -1,
            'api_access' => true,
            'advanced_analytics' => true,
            'bulk_import_export' => true,
            'abandoned_cart' => true,
            'multi_currency' => true,
            'priority_support' => true,
            'custom_theme' => true,
            'coupons' => true,
            'newsletter' => true,
            'disable_branding' => true,
        ],
    ];

    /**
     * Cached validation result
     */
    private static ?array $cachedResult = null;

    /**
     * Validate the license key from environment
     */
    public static function validate(): array
    {
        if (self::$cachedResult !== null) {
            return self::$cachedResult;
        }

        $licenseKey = $_ENV['LICENSE_KEY'] ?? getenv('LICENSE_KEY') ?? '';

        if (empty($licenseKey)) {
            self::$cachedResult = [
                'valid' => true,
                'edition' => 'free',
                'edition_code' => 'F',
                'domain_locked' => false,
                'key' => '',
                'is_free' => true
            ];
            return self::$cachedResult;
        }

        self::$cachedResult = self::validateKey($licenseKey);
        return self::$cachedResult;
    }

    /**
     * Validate a specific license key
     */
    public static function validateKey(string $key): array
    {
        $key = strtoupper(trim($key));

        // Check format: APX-XXXXX-XXXXX-XXXXX-XXXXX
        if (!preg_match('/^APX-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}$/', $key)) {
            return [
                'valid' => false,
                'error' => 'Invalid license key format',
                'code' => 'INVALID_FORMAT'
            ];
        }

        // Parse the key
        $parts = explode('-', $key);
        // $parts[0] = APX (prefix)
        // $parts[1] = Edition + Random
        // $parts[2] = Domain hash or wildcard
        // $parts[3] = Random padding
        // $parts[4] = Checksum

        $edition = $parts[1][0] ?? 'S';
        $domainHash = $parts[2];
        $checksum = $parts[4];

        // Validate checksum
        $dataToHash = $parts[0] . '-' . $parts[1] . '-' . $parts[2] . '-' . $parts[3];
        $expectedChecksum = self::generateChecksum($dataToHash);

        if ($checksum !== $expectedChecksum) {
            return [
                'valid' => false,
                'error' => 'Invalid license key',
                'code' => 'INVALID_CHECKSUM'
            ];
        }

        // Check domain binding (if not wildcard)
        if ($domainHash !== 'AAAAA') {
            $currentDomain = self::getCurrentDomain();
            $currentDomainHash = self::hashDomain($currentDomain);

            if ($domainHash !== $currentDomainHash) {
                return [
                    'valid' => false,
                    'error' => 'License not valid for this domain',
                    'code' => 'DOMAIN_MISMATCH',
                    'expected_domain_hash' => $domainHash,
                    'current_domain_hash' => $currentDomainHash
                ];
            }
        }

        // Valid license
        return [
            'valid' => true,
            'edition' => self::EDITIONS[$edition] ?? 'standard',
            'edition_code' => $edition,
            'domain_locked' => $domainHash !== 'AAAAA',
            'key' => $key
        ];
    }

    /**
     * Generate a new license key
     */
    public static function generate(string $edition = 'S', ?string $domain = null): string
    {
        $edition = strtoupper($edition);
        if (!isset(self::EDITIONS[$edition])) {
            $edition = 'S';
        }

        // Part 1: Edition + 4 random chars
        $part1 = $edition . self::randomString(4);

        // Part 2: Domain hash or wildcard (AAAAA = any domain)
        if ($domain) {
            $part2 = self::hashDomain($domain);
        } else {
            $part2 = 'AAAAA'; // Wildcard - works on any domain
        }

        // Part 3: Random padding
        $part3 = self::randomString(5);

        // Part 4: Checksum
        $dataToHash = self::PREFIX . '-' . $part1 . '-' . $part2 . '-' . $part3;
        $part4 = self::generateChecksum($dataToHash);

        return self::PREFIX . '-' . $part1 . '-' . $part2 . '-' . $part3 . '-' . $part4;
    }

    /**
     * Generate checksum for license data
     */
    private static function generateChecksum(string $data): string
    {
        $hash = hash_hmac('sha256', $data, self::SECRET_KEY);
        // Take first 5 chars, convert to uppercase alphanumeric
        $checksum = '';
        for ($i = 0; $i < 5; $i++) {
            $char = strtoupper($hash[$i * 2] ?? 'A');
            // Ensure it's alphanumeric
            if (!ctype_alnum($char)) {
                $char = chr(65 + (ord($hash[$i * 2]) % 26));
            }
            $checksum .= $char;
        }
        return strtoupper($checksum);
    }

    /**
     * Hash a domain name to 5 chars
     */
    private static function hashDomain(string $domain): string
    {
        // Normalize domain
        $domain = strtolower(trim($domain));
        $domain = preg_replace('/^www\./', '', $domain);

        $hash = hash_hmac('sha256', $domain, self::SECRET_KEY . '_domain');
        $result = '';
        for ($i = 0; $i < 5; $i++) {
            $charCode = ord($hash[$i]);
            // Map to A-Z, 0-9
            if ($charCode % 2 === 0) {
                $result .= chr(65 + ($charCode % 26)); // A-Z
            } else {
                $result .= chr(48 + ($charCode % 10)); // 0-9
            }
        }
        return strtoupper($result);
    }

    /**
     * Get current domain
     */
    private static function getCurrentDomain(): string
    {
        $domain = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        // Remove port if present
        $domain = preg_replace('/:\d+$/', '', $domain);
        // Remove www
        $domain = preg_replace('/^www\./', '', strtolower($domain));
        return $domain;
    }

    /**
     * Generate random alphanumeric string
     */
    private static function randomString(int $length): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Removed confusing chars: I, O, 0, 1
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $result;
    }

    /**
     * Check if license is valid (simple boolean check)
     */
    public static function isValid(): bool
    {
        return self::validate()['valid'] ?? false;
    }

    /**
     * Check if running on free tier (no license)
     */
    public static function isFree(): bool
    {
        return self::getEditionCode() === 'F';
    }

    /**
     * Check if running on a paid license
     */
    public static function isPaid(): bool
    {
        return !self::isFree();
    }

    /**
     * Get license edition
     */
    public static function getEdition(): string
    {
        $result = self::validate();
        return $result['edition'] ?? 'unlicensed';
    }

    /**
     * Clear cached result (useful for testing)
     */
    public static function clearCache(): void
    {
        self::$cachedResult = null;
    }

    /**
     * Get edition code (S, P, E, D, U)
     */
    public static function getEditionCode(): string
    {
        $result = self::validate();
        return $result['edition_code'] ?? 'S';
    }

    /**
     * Get all features for current license
     */
    public static function getFeatures(): array
    {
        $code = self::getEditionCode();
        return self::EDITION_FEATURES[$code] ?? self::EDITION_FEATURES['S'];
    }

    /**
     * Get a specific feature value
     */
    public static function getFeature(string $feature, mixed $default = null): mixed
    {
        $features = self::getFeatures();
        return $features[$feature] ?? $default;
    }

    /**
     * Check if a feature is enabled
     */
    public static function hasFeature(string $feature): bool
    {
        return (bool) self::getFeature($feature, false);
    }

    /**
     * Get a limit value (-1 means unlimited)
     */
    public static function getLimit(string $limit): int
    {
        return (int) self::getFeature($limit, 0);
    }

    /**
     * Check if within a limit
     */
    public static function withinLimit(string $limit, int $currentCount): bool
    {
        $max = self::getLimit($limit);
        if ($max === -1) {
            return true; // Unlimited
        }
        return $currentCount < $max;
    }

    /**
     * Check if can add more products
     */
    public static function canAddProduct(int $currentProductCount): bool
    {
        return self::withinLimit('max_products', $currentProductCount);
    }

    /**
     * Check if can add more admin users
     */
    public static function canAddAdminUser(int $currentAdminCount): bool
    {
        return self::withinLimit('max_admin_users', $currentAdminCount);
    }

    /**
     * Get remaining count for a limit
     */
    public static function getRemainingCount(string $limit, int $currentCount): int|string
    {
        $max = self::getLimit($limit);
        if ($max === -1) {
            return 'unlimited';
        }
        return max(0, $max - $currentCount);
    }

    /**
     * Get edition display info for admin panel
     */
    public static function getEditionInfo(): array
    {
        $result = self::validate();
        $code = $result['edition_code'] ?? 'S';
        $features = self::EDITION_FEATURES[$code] ?? self::EDITION_FEATURES['S'];

        return [
            'name' => ucfirst($result['edition'] ?? 'standard'),
            'code' => $code,
            'valid' => $result['valid'] ?? false,
            'domain_locked' => $result['domain_locked'] ?? false,
            'limits' => [
                'products' => $features['max_products'] === -1 ? 'Unlimited' : number_format($features['max_products']),
                'admin_users' => $features['max_admin_users'] === -1 ? 'Unlimited' : $features['max_admin_users'],
                'orders_month' => $features['max_orders_month'] === -1 ? 'Unlimited' : number_format($features['max_orders_month']),
            ],
            'features' => [
                'api_access' => $features['api_access'],
                'advanced_analytics' => $features['advanced_analytics'],
                'bulk_import_export' => $features['bulk_import_export'],
                'abandoned_cart' => $features['abandoned_cart'],
                'multi_currency' => $features['multi_currency'],
                'priority_support' => $features['priority_support'],
            ],
        ];
    }

    /**
     * Get upgrade URL for a feature
     */
    public static function getUpgradeUrl(): string
    {
        return 'https://apparix.app/pricing';
    }

    /**
     * Validate a key without domain checking (for API use)
     * Returns key info including domain hash
     */
    public static function validateKeyForApi(string $key): array
    {
        $key = strtoupper(trim($key));

        // Check format: APX-XXXXX-XXXXX-XXXXX-XXXXX
        if (!preg_match('/^APX-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}$/', $key)) {
            return [
                'valid' => false,
                'error' => 'Invalid license key format'
            ];
        }

        // Parse the key
        $parts = explode('-', $key);
        $edition = $parts[1][0] ?? 'S';
        $domainHash = $parts[2];
        $checksum = $parts[4];

        // Validate checksum
        $dataToHash = $parts[0] . '-' . $parts[1] . '-' . $parts[2] . '-' . $parts[3];
        $expectedChecksum = self::generateChecksum($dataToHash);

        if ($checksum !== $expectedChecksum) {
            return [
                'valid' => false,
                'error' => 'Invalid license key'
            ];
        }

        // Return key info without domain validation
        return [
            'valid' => true,
            'edition' => $edition,
            'edition_name' => self::EDITIONS[$edition] ?? 'standard',
            'domain' => $domainHash === 'AAAAA' ? '*' : null, // Wildcard or domain-locked
            'domain_hash' => $domainHash,
            'is_wildcard' => $domainHash === 'AAAAA'
        ];
    }

    /**
     * Verify a domain matches a license key's domain hash
     */
    public static function verifyDomainForKey(string $domain, string $domainHash): bool
    {
        if ($domainHash === 'AAAAA') {
            return true; // Wildcard - any domain
        }
        $actualHash = self::hashDomain($domain);
        return $actualHash === $domainHash;
    }
}
