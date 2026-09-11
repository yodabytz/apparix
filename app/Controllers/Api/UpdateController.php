<?php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Core\License;
use App\Core\PluginUpdateCatalog;

/**
 * API Controller for software update system
 * Handles version checks and update downloads for licensed installations
 */
class UpdateController extends Controller
{
    private Database $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
    }

    /**
     * Check for available updates
     * POST /api/updates/check
     *
     * Required: license_key, current_version, domain
     * Optional: php_version
     */
    public function check(): void
    {
        // Set JSON response headers
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');

        // Handle preflight
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        // Get request data
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $licenseKey = trim($input['license_key'] ?? '');
        $currentVersion = trim($input['current_version'] ?? '');
        $domain = trim($input['domain'] ?? '');
        $phpVersion = trim($input['php_version'] ?? PHP_VERSION);
        $installedPlugins = $this->normalizeInstalledPlugins($input['plugins'] ?? []);

        // Validate required fields
        if (empty($licenseKey) || empty($currentVersion) || empty($domain)) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Missing required fields: license_key, current_version, domain'
            ], 400);
            return;
        }

        // Validate license key format and get edition
        $licenseInfo = $this->validateLicenseKey($licenseKey);
        if (!$licenseInfo['valid']) {
            $this->jsonResponse([
                'success' => false,
                'error' => $licenseInfo['error']
            ], 401);
            return;
        }

        // Check domain lock if applicable
        if ($licenseInfo['domain'] !== '*') {
            if (!$this->domainMatches($domain, $licenseInfo['domain'], $licenseInfo['domain_hash'] ?? null)) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'License is not valid for this domain'
                ], 403);
                return;
            }
        }

        $pluginUpdates = (new PluginUpdateCatalog($this->db))->updatesFor($installedPlugins, $licenseInfo);

        // Get the next sequential version (step-by-step updates)
        $nextRelease = $this->getNextRelease($currentVersion, $licenseInfo['edition'], $phpVersion);

        if (!$nextRelease) {
            $this->jsonResponse([
                'success' => true,
                'update_available' => false,
                'current_version' => $currentVersion,
                'plugin_updates' => $pluginUpdates['available'],
                'plugin_purchase_required' => $pluginUpdates['purchase_required'],
                'message' => empty($pluginUpdates['available']) ? 'No updates available' : 'Plugin updates are available'
            ]);
            return;
        }

        $response = [
            'success' => true,
            'update_available' => true,
            'current_version' => $currentVersion,
            'latest_version' => $nextRelease['version'],
            'edition' => $licenseInfo['edition'],
            'edition_name' => $this->getEditionName($licenseInfo['edition']),
            'plugin_updates' => $pluginUpdates['available'],
            'plugin_purchase_required' => $pluginUpdates['purchase_required']
        ];

        $response['update'] = [
            'version' => $nextRelease['version'],
            'release_type' => $nextRelease['release_type'],
            'release_notes' => $nextRelease['release_notes'],
            'changelog' => $nextRelease['changelog'],
            'file_size' => $nextRelease['file_size'],
            'file_size_formatted' => $this->formatBytes($nextRelease['file_size']),
            'released_at' => $nextRelease['released_at'],
            'min_php_version' => $nextRelease['min_php_version'],
            'download_url' => '/api/updates/download'
        ];

        $this->jsonResponse($response);
    }

    /**
     * Download update package
     * POST /api/updates/download
     *
     * Required: license_key, domain, target_version
     */
    public function download(): void
    {
        // Get request data
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $licenseKey = trim($input['license_key'] ?? '');
        $domain = trim($input['domain'] ?? '');
        $targetVersion = trim($input['target_version'] ?? '');
        $currentVersion = trim($input['current_version'] ?? '');

        // Validate required fields
        if (empty($licenseKey) || empty($domain) || empty($targetVersion)) {
            header('Content-Type: application/json');
            $this->jsonResponse([
                'success' => false,
                'error' => 'Missing required fields'
            ], 400);
            return;
        }

        // Validate license
        $licenseInfo = $this->validateLicenseKey($licenseKey);
        if (!$licenseInfo['valid']) {
            header('Content-Type: application/json');
            $this->jsonResponse([
                'success' => false,
                'error' => $licenseInfo['error']
            ], 401);
            return;
        }

        // Check domain lock
        if ($licenseInfo['domain'] !== '*') {
            if (!$this->domainMatches($domain, $licenseInfo['domain'], $licenseInfo['domain_hash'] ?? null)) {
                header('Content-Type: application/json');
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'License not valid for this domain'
                ], 403);
                return;
            }
        }

        // Get the release
        $release = $this->db->selectOne(
            "SELECT * FROM releases WHERE version = ? AND is_active = 1",
            [$targetVersion]
        );

        if (!$release) {
            header('Content-Type: application/json');
            $this->jsonResponse([
                'success' => false,
                'error' => 'Version not found'
            ], 404);
            return;
        }

        // Check edition eligibility
        if (!$this->editionCanAccess($licenseInfo['edition'], $release['min_edition'])) {
            header('Content-Type: application/json');
            $this->jsonResponse([
                'success' => false,
                'error' => 'Your license edition does not have access to this version. Please upgrade.'
            ], 403);
            return;
        }

        // Check if file exists and is readable
        $filePath = BASE_PATH . '/storage/updates/' . basename($release['update_file']);
        if (!is_readable($filePath)) {
            header('Content-Type: application/json');
            $this->jsonResponse([
                'success' => false,
                'error' => 'Update file not available'
            ], 500);
            return;
        }

        // Log the download
        $this->logUpdate($licenseKey, $domain, $currentVersion, $targetVersion, 'downloaded');

        // Increment download count
        $this->db->update(
            "UPDATE releases SET download_count = download_count + 1 WHERE id = ?",
            [$release['id']]
        );

        // Serve the file
        $filesize = filesize($filePath);

        // Clear output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/gzip');
        header('Content-Disposition: attachment; filename="' . basename($release['update_file']) . '"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . $filesize);
        header('X-File-Hash: ' . preg_replace('/[^a-fA-F0-9]/', '', $release['file_hash']));
        header('X-Version: ' . $release['version']);
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Expires: 0');

        readfile($filePath);
        exit;
    }

    /**
     * Report update installation status
     * POST /api/updates/report
     */
    public function report(): void
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $licenseKey = trim($input['license_key'] ?? '');
        $domain = trim($input['domain'] ?? '');
        $version = trim($input['version'] ?? '');
        $status = trim($input['status'] ?? 'installed');
        $errorMessage = trim($input['error_message'] ?? '');

        if (empty($licenseKey) || empty($version)) {
            $this->jsonResponse(['success' => false, 'error' => 'Missing required fields'], 400);
            return;
        }

        // Update the log entry
        $this->db->update(
            "UPDATE update_logs SET status = ?, error_message = ?
             WHERE license_key = ? AND to_version = ?
             ORDER BY created_at DESC LIMIT 1",
            [$status, $errorMessage, $licenseKey, $version]
        );

        $this->jsonResponse(['success' => true]);
    }

    /**
     * Download an authorized plugin update package.
     */
    public function pluginDownload(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $licenseKey = trim((string)($input['license_key'] ?? ''));
        $domain = trim((string)($input['domain'] ?? ''));
        $slug = trim((string)($input['plugin_slug'] ?? ''));
        $targetVersion = trim((string)($input['target_version'] ?? ''));
        $currentVersion = trim((string)($input['current_version'] ?? ''));

        if ($licenseKey === '' || $domain === '' || !preg_match('/^[a-z0-9][a-z0-9-]{0,99}$/', $slug)
            || !preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $targetVersion)) {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid plugin update request'], 400);
            return;
        }

        $license = $this->validateLicenseKey($licenseKey);
        if (!$license['valid']) {
            $this->jsonResponse(['success' => false, 'error' => $license['error']], 401);
            return;
        }
        if ($license['domain'] !== '*' && !$this->domainMatches($domain, $license['domain'], $license['domain_hash'] ?? null)) {
            $this->jsonResponse(['success' => false, 'error' => 'License not valid for this domain'], 403);
            return;
        }

        $catalog = new PluginUpdateCatalog($this->db);
        $release = $catalog->release($slug, $targetVersion);
        if (!$release) {
            $this->jsonResponse(['success' => false, 'error' => 'Plugin update not found'], 404);
            return;
        }
        if (!$catalog->canAccess($release, $license)) {
            $this->jsonResponse(['success' => false, 'error' => 'This license does not own the requested plugin'], 403);
            return;
        }
        if ($currentVersion !== '' && version_compare($targetVersion, $currentVersion, '<=')) {
            $this->jsonResponse(['success' => false, 'error' => 'Requested plugin version is not newer'], 409);
            return;
        }

        $this->logPluginUpdate($licenseKey, $domain, $slug, $currentVersion, $targetVersion, 'downloaded');
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $slug . '-' . $targetVersion . '.zip"');
        header('Content-Length: ' . $release['file_size']);
        header('X-File-Hash: ' . $release['file_hash']);
        header('X-Plugin-Slug: ' . $slug);
        header('X-Plugin-Version: ' . $targetVersion);
        header('Cache-Control: no-store');
        readfile($release['package_path']);
        exit;
    }

    /**
     * Record the result reported by a customer installation.
     */
    public function pluginReport(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $licenseKey = trim((string)($input['license_key'] ?? ''));
        $domain = trim((string)($input['domain'] ?? ''));
        $slug = trim((string)($input['plugin_slug'] ?? ''));
        $fromVersion = trim((string)($input['from_version'] ?? ''));
        $toVersion = trim((string)($input['to_version'] ?? ''));
        $status = trim((string)($input['status'] ?? 'failed'));
        $error = trim((string)($input['error_message'] ?? ''));

        $license = $this->validateLicenseKey($licenseKey);
        if (!$license['valid']) {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid license key'], 401);
            return;
        }
        if ($license['domain'] !== '*' && !$this->domainMatches($domain, $license['domain'], $license['domain_hash'] ?? null)) {
            $this->jsonResponse(['success' => false, 'error' => 'License not valid for this domain'], 403);
            return;
        }
        if (!preg_match('/^[a-z0-9][a-z0-9-]{0,99}$/', $slug)
            || !in_array($status, ['installed', 'failed'], true)
            || !preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $toVersion)) {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid plugin update report'], 400);
            return;
        }

        $catalog = new PluginUpdateCatalog($this->db);
        $release = $catalog->release($slug, $toVersion);
        if (!$release || !$catalog->canAccess($release, $license)) {
            $this->jsonResponse(['success' => false, 'error' => 'Plugin update is not authorized'], 403);
            return;
        }

        $this->logPluginUpdate($licenseKey, $domain, $slug, $fromVersion, $toVersion, $status, $error);
        $this->jsonResponse(['success' => true]);
    }

    /**
     * Get version info (public endpoint)
     * GET /api/updates/version
     */
    public function version(): void
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');

        $latest = $this->db->selectOne(
            "SELECT version, release_type, released_at FROM releases
             WHERE is_active = 1 AND release_type = 'stable'
             ORDER BY version_major DESC, version_minor DESC, version_patch DESC
             LIMIT 1"
        );

        $this->jsonResponse([
            'success' => true,
            'product' => 'Apparix E-Commerce Platform',
            'latest_version' => $latest['version'] ?? '1.0.0',
            'release_type' => $latest['release_type'] ?? 'stable',
            'released_at' => $latest['released_at'] ?? null
        ]);
    }

    /**
     * Validate a license key and return its info
     */
    private function validateLicenseKey(string $key): array
    {
        // First check if it's a purchased license in our database
        $dbLicense = $this->db->selectOne(
            "SELECT ol.*, o.customer_email, o.user_id
             FROM order_licenses ol
             JOIN orders o ON ol.order_id = o.id
             JOIN products p ON ol.product_id = p.id
             WHERE ol.license_key = ? AND ol.is_active = 1
               AND (p.sku = 'APX-LICENSE' OR p.slug = 'apparix-ecommerce-platform')",
            [$key]
        );

        if ($dbLicense) {
            return [
                'valid' => true,
                'edition' => $dbLicense['edition_code'],
                'domain' => $dbLicense['domain'],
                'domain_hash' => null,
                'source' => 'database',
                'license_key' => $key,
                'email' => $dbLicense['customer_email'] ?? null,
                'user_id' => $dbLicense['user_id'] ?? null,
            ];
        }

        $nonPlatformLicense = $this->db->selectOne(
            "SELECT id FROM order_licenses WHERE license_key = ? LIMIT 1",
            [$key]
        );
        if ($nonPlatformLicense) {
            return ['valid' => false, 'error' => 'License key is not an Apparix platform license'];
        }

        $storeLicense = $this->db->selectOne(
            "SELECT email, license_key, edition, domain FROM license_purchases WHERE license_key = ?",
            [$key]
        );

        // Otherwise validate using the License class (for manually generated keys)
        $result = License::validateKeyForApi($key);

        if (!$result['valid']) {
            return [
                'valid' => false,
                'error' => 'Invalid license key'
            ];
        }

        return [
            'valid' => true,
            'edition' => $result['edition'],
            'domain' => $result['is_wildcard'] ? '*' : null,
            'domain_hash' => $result['domain_hash'],
            'source' => $storeLicense ? 'license_store' : 'generated',
            'license_key' => $key,
            'email' => $storeLicense['email'] ?? null,
            'user_id' => null,
        ];
    }

    private function normalizeInstalledPlugins(mixed $plugins): array
    {
        if (!is_array($plugins)) {
            return [];
        }
        $normalized = [];
        foreach (array_slice($plugins, 0, 100) as $plugin) {
            if (!is_array($plugin)) {
                continue;
            }
            $slug = trim((string)($plugin['slug'] ?? ''));
            $version = trim((string)($plugin['version'] ?? ''));
            if (!preg_match('/^[a-z0-9][a-z0-9-]{0,99}$/', $slug)
                || !preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version)) {
                continue;
            }
            $normalized[$slug] = ['slug' => $slug, 'version' => $version];
        }
        return array_values($normalized);
    }

    private function logPluginUpdate(
        string $licenseKey,
        string $domain,
        string $slug,
        string $fromVersion,
        string $toVersion,
        string $status,
        string $error = ''
    ): void {
        try {
            $this->db->insert(
                "INSERT INTO plugin_update_logs
                    (license_key_hash, domain, plugin_slug, from_version, to_version, status, error_message, ip_address)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    hash('sha256', $licenseKey),
                    substr($domain, 0, 255),
                    $slug,
                    $fromVersion ?: null,
                    $toVersion,
                    $status,
                    $error === '' ? null : substr($error, 0, 1000),
                    $_SERVER['REMOTE_ADDR'] ?? null,
                ]
            );
        } catch (\Throwable $e) {
            error_log('Plugin update log failed: ' . $e->getMessage());
        }
    }

    /**
     * Check if domain matches (supports wildcard and domain hash)
     */
    private function domainMatches(string $requestDomain, ?string $licenseDomain, ?string $domainHash = null): bool
    {
        // Wildcard - any domain
        if ($licenseDomain === '*') {
            return true;
        }

        // For database licenses with explicit domain
        if ($licenseDomain) {
            $requestDomain = strtolower(preg_replace('/^www\./', '', $requestDomain));
            $licenseDomain = strtolower(preg_replace('/^www\./', '', $licenseDomain));
            return $requestDomain === $licenseDomain;
        }

        // For generated keys with domain hash
        if ($domainHash) {
            return License::verifyDomainForKey($requestDomain, $domainHash);
        }

        return false;
    }

    /**
     * Get the next sequential release after the current version.
     * Returns one version at a time so customers step through updates
     * in order and never skip intermediate releases.
     */
    private function getNextRelease(string $currentVersion, string $edition, string $phpVersion): ?array
    {
        // Parse current version
        $parts = explode('.', $currentVersion);
        $curMajor = (int)($parts[0] ?? 0);
        $curMinor = (int)($parts[1] ?? 0);
        $curPatch = (int)($parts[2] ?? 0);

        // Edition hierarchy for access check
        $hierarchy = ['S' => 1, 'P' => 2, 'E' => 3, 'D' => 4, 'U' => 5];
        $userLevel = $hierarchy[$edition] ?? 0;
        $accessibleEditions = array_keys(array_filter($hierarchy, fn($lvl) => $lvl <= $userLevel));

        if (empty($accessibleEditions)) {
            return null;
        }

        $placeholders = implode(',', array_fill(0, count($accessibleEditions), '?'));

        $params = array_merge(
            [$phpVersion],
            $accessibleEditions,
            [$curMajor, $curMajor, $curMinor, $curMajor, $curMinor, $curPatch]
        );

        $result = $this->db->selectOne(
            "SELECT * FROM releases
             WHERE is_active = 1
             AND release_type = 'stable'
             AND min_php_version <= ?
             AND min_edition IN ($placeholders)
             AND (
                 version_major > ? OR
                 (version_major = ? AND version_minor > ?) OR
                 (version_major = ? AND version_minor = ? AND version_patch > ?)
             )
             ORDER BY version_major ASC, version_minor ASC, version_patch ASC
             LIMIT 1",
            $params
        );

        return $result ?: null;
    }

    /**
     * Check if edition can access a release
     */
    private function editionCanAccess(string $userEdition, string $minEdition): bool
    {
        $hierarchy = ['S' => 1, 'P' => 2, 'E' => 3, 'D' => 4, 'U' => 5];
        $userLevel = $hierarchy[$userEdition] ?? 0;
        $minLevel = $hierarchy[$minEdition] ?? 0;
        return $userLevel >= $minLevel;
    }

    /**
     * Log update activity
     */
    private function logUpdate(string $licenseKey, string $domain, ?string $fromVersion, string $toVersion, string $status): void
    {
        $this->db->insert(
            "INSERT INTO update_logs (license_key, domain, from_version, to_version, ip_address, user_agent, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $licenseKey,
                $domain,
                $fromVersion,
                $toVersion,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $status
            ]
        );
    }

    /**
     * Get edition name
     */
    private function getEditionName(string $code): string
    {
        $names = [
            'S' => 'Standard',
            'P' => 'Professional',
            'E' => 'Enterprise',
            'D' => 'Developer',
            'U' => 'Unlimited'
        ];
        return $names[$code] ?? 'Unknown';
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }

    /**
     * Send JSON response
     */
    private function jsonResponse(array $data, int $code = 200): void
    {
        http_response_code($code);
        echo json_encode($data);
        exit;
    }
}
