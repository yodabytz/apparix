<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Plugin extends Model
{
    protected string $table = 'plugins';

    /**
     * Get all installed plugins
     */
    public function getAll(string $orderBy = 'type, name', ?int $limit = null): array
    {
        $query = "SELECT * FROM {$this->table} ORDER BY {$orderBy}";
        if ($limit) {
            $query .= " LIMIT {$limit}";
        }
        return $this->db->select($query);
    }

    /**
     * Get all active plugins
     */
    public function getActive(): array
    {
        return $this->db->select(
            "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY type, name"
        );
    }

    /**
     * Get plugins by type
     */
    public function getByType(string $type): array
    {
        return $this->db->select(
            "SELECT * FROM {$this->table} WHERE type = ? ORDER BY name",
            [$type]
        );
    }

    /**
     * Get active plugins by type
     */
    public function getActiveByType(string $type): array
    {
        return $this->db->select(
            "SELECT * FROM {$this->table} WHERE type = ? AND is_active = 1 ORDER BY name",
            [$type]
        );
    }

    /**
     * Get plugin by slug
     */
    public function getBySlug(string $slug): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM {$this->table} WHERE slug = ?",
            [$slug]
        );
    }

    /**
     * Check if plugin exists
     */
    public function exists(string $slug): bool
    {
        $result = $this->db->selectOne(
            "SELECT COUNT(*) as count FROM {$this->table} WHERE slug = ?",
            [$slug]
        );
        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Install a plugin from manifest data
     */
    public function install(array $manifest): ?int
    {
        $data = [
            'slug' => $manifest['slug'],
            'name' => $manifest['name'],
            'description' => $manifest['description'] ?? null,
            'version' => $manifest['version'],
            'author' => $manifest['author'] ?? null,
            'author_url' => $manifest['author_url'] ?? null,
            'type' => $manifest['type'],
            'is_active' => 0,
            'settings' => json_encode($manifest['default_settings'] ?? []),
            'icon' => $manifest['icon'] ?? null
        ];

        return $this->create($data);
    }

    /**
     * Activate a plugin
     */
    public function activate(int $id): bool
    {
        return $this->update($id, ['is_active' => 1]);
    }

    /**
     * Deactivate a plugin
     */
    public function deactivate(int $id): bool
    {
        return $this->update($id, ['is_active' => 0]);
    }

    /**
     * Get plugin settings
     */
    public function getSettings(int $id): array
    {
        $plugin = $this->find($id);
        if (!$plugin) {
            return [];
        }

        $settings = $plugin['settings'] ?? '[]';
        return json_decode($settings, true) ?: [];
    }

    /**
     * Update plugin settings
     */
    public function updateSettings(int $id, array $settings): bool
    {
        return $this->update($id, [
            'settings' => json_encode($settings)
        ]);
    }

    /**
     * Get a specific setting value
     */
    public function getSetting(int $id, string $key, $default = null)
    {
        $settings = $this->getSettings($id);
        return $settings[$key] ?? $default;
    }

    /**
     * Set a specific setting value
     */
    public function setSetting(int $id, string $key, $value): bool
    {
        $settings = $this->getSettings($id);
        $settings[$key] = $value;
        return $this->updateSettings($id, $settings);
    }

    /**
     * Delete plugin and optionally remove files
     */
    public function uninstall(int $id, bool $removeFiles = true): bool
    {
        $plugin = $this->find($id);
        if (!$plugin) {
            return false;
        }

        // Don't allow deleting built-in plugins
        if ($plugin['slug'] === 'stripe') {
            return false;
        }

        if ($removeFiles) {
            $pluginPath = BASE_PATH . '/content/plugins/' . $plugin['slug'];
            if (is_dir($pluginPath)) {
                $this->removeDirectory($pluginPath);
            }
        }

        return $this->delete($id);
    }

    /**
     * Recursively remove a directory
     */
    private function removeDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        return rmdir($dir);
    }

    /**
     * Get active payment providers
     */
    public function getActivePaymentProviders(): array
    {
        return $this->getActiveByType('payment');
    }

    /**
     * Check if a payment provider is active
     */
    public function isPaymentProviderActive(string $slug): bool
    {
        $plugin = $this->getBySlug($slug);
        return $plugin && $plugin['is_active'] && $plugin['type'] === 'payment';
    }

    /**
     * Get the default payment provider (first active one, prefer Stripe)
     */
    public function getDefaultPaymentProvider(): ?array
    {
        // Try Stripe first
        $stripe = $this->getBySlug('stripe');
        if ($stripe && $stripe['is_active']) {
            return $stripe;
        }

        // Fall back to any active payment provider
        $providers = $this->getActivePaymentProviders();
        return $providers[0] ?? null;
    }
}
