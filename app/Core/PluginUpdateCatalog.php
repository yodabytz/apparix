<?php

namespace App\Core;

use ZipArchive;

/**
 * Resolves protected plugin products into update releases and checks ownership.
 */
class PluginUpdateCatalog
{
    public function __construct(private Database $db)
    {
    }

    public function updatesFor(array $installedPlugins, array $license): array
    {
        $catalog = $this->catalog();
        $available = [];
        $locked = [];
        foreach ($installedPlugins as $installed) {
            $slug = $installed['slug'];
            if (!isset($catalog[$slug])) {
                continue;
            }
            $release = $catalog[$slug];
            if (version_compare($release['version'], $installed['version'], '<=')) {
                continue;
            }
            $item = [
                'slug' => $slug,
                'name' => $release['name'],
                'current_version' => $installed['version'],
                'version' => $release['version'],
                'file_size' => $release['file_size'],
                'file_size_formatted' => $this->formatBytes($release['file_size']),
                'release_notes' => $release['release_notes'],
                'product_url' => rtrim($_ENV['APP_URL'] ?? 'https://apparix.app', '/') . '/products/' . $release['product_slug'],
            ];
            if ($this->canAccess($release, $license)) {
                $available[] = $item;
            } else {
                $locked[] = $item;
            }
        }
        return ['available' => $available, 'purchase_required' => $locked];
    }

    public function release(string $slug, string $version): ?array
    {
        $release = $this->catalog()[$slug] ?? null;
        return $release && hash_equals($release['version'], $version) ? $release : null;
    }

    public function canAccess(array $release, array $license): bool
    {
        $licenseKey = (string)($license['license_key'] ?? '');
        if ($licenseKey !== '') {
            try {
                $grant = $this->db->selectOne(
                    "SELECT id FROM plugin_update_entitlements
                     WHERE license_key_hash = ? AND plugin_slug = ? AND revoked_at IS NULL",
                    [hash('sha256', $licenseKey), $release['slug']]
                );
                if ($grant) {
                    return true;
                }
            } catch (\Throwable $e) {
                error_log('Plugin entitlement lookup failed: ' . $e->getMessage());
            }
        }

        $conditions = [];
        $params = [(int)$release['product_id']];
        if (!empty($license['user_id'])) {
            $conditions[] = 'o.user_id = ?';
            $params[] = (int)$license['user_id'];
        }
        if (!empty($license['email'])) {
            $conditions[] = 'LOWER(o.customer_email) = LOWER(?)';
            $params[] = trim((string)$license['email']);
        }
        if (!$conditions) {
            return false;
        }
        return (bool)$this->db->selectOne(
            "SELECT o.id FROM orders o
             JOIN order_items oi ON oi.order_id = o.id
             WHERE oi.product_id = ? AND o.payment_status = 'paid'
               AND (" . implode(' OR ', $conditions) . ")
             LIMIT 1",
            $params
        );
    }

    private function catalog(): array
    {
        $products = $this->db->select(
            "SELECT id, name, slug, description, download_file FROM products
             WHERE is_active = 1 AND is_digital = 1 AND download_file LIKE '%.zip'"
        );
        $catalog = [];
        foreach ($products as $product) {
            $package = $this->packageMetadata((string)$product['download_file']);
            if ($package === null) {
                continue;
            }
            $slug = $package['manifest']['slug'];
            $release = [
                'slug' => $slug,
                'name' => $package['manifest']['name'] ?? $product['name'],
                'version' => $package['manifest']['version'],
                'release_notes' => $package['manifest']['description'] ?? $product['description'] ?? '',
                'product_id' => (int)$product['id'],
                'product_slug' => $product['slug'],
                'package_path' => $package['path'],
                'file_hash' => hash_file('sha256', $package['path']),
                'file_size' => filesize($package['path']),
            ];
            if (!isset($catalog[$slug]) || version_compare($release['version'], $catalog[$slug]['version'], '>')) {
                $catalog[$slug] = $release;
            }
        }
        return $catalog;
    }

    private function packageMetadata(string $filename): ?array
    {
        if ($filename === '' || basename($filename) !== $filename) {
            return null;
        }
        $downloadsDir = realpath(BASE_PATH . '/storage/downloads');
        $path = realpath(BASE_PATH . '/storage/downloads/' . $filename);
        if ($downloadsDir === false || $path === false || !str_starts_with($path, $downloadsDir . '/') || !is_readable($path)) {
            return null;
        }
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return null;
        }
        try {
            $manifest = null;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = str_replace('\\', '/', (string)$zip->getNameIndex($i));
                if (basename($name) !== 'plugin.json') {
                    continue;
                }
                if ($manifest !== null) {
                    return null;
                }
                $raw = $zip->getFromIndex($i);
                $manifest = is_string($raw) ? json_decode($raw, true) : null;
            }
            if (!is_array($manifest)
                || !preg_match('/^[a-z0-9][a-z0-9-]{0,99}$/', (string)($manifest['slug'] ?? ''))
                || !preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', (string)($manifest['version'] ?? ''))) {
                return null;
            }
            return ['manifest' => $manifest, 'path' => $path];
        } finally {
            $zip->close();
        }
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
