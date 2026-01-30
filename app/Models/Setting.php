<?php

namespace App\Models;

use App\Core\Model;

/**
 * Setting model for dynamic configuration storage
 * Includes in-memory caching for performance
 */
class Setting extends Model
{
    protected string $table = 'settings';

    /**
     * In-memory cache for settings
     */
    private static array $cache = [];

    /**
     * Whether cache has been loaded from database
     */
    private static bool $cacheLoaded = false;

    /**
     * Load all settings into cache
     */
    private function loadCache(): void
    {
        if (self::$cacheLoaded) {
            return;
        }

        $settings = $this->query("SELECT * FROM {$this->table}");
        foreach ($settings as $setting) {
            $value = $this->castValue($setting['setting_value'], $setting['setting_type']);
            self::$cache[$setting['setting_key']] = [
                'value' => $value,
                'type' => $setting['setting_type'],
                'id' => $setting['id']
            ];
        }
        self::$cacheLoaded = true;
    }

    /**
     * Get a setting value by key
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->loadCache();

        if (isset(self::$cache[$key])) {
            return self::$cache[$key]['value'];
        }

        return $default;
    }

    /**
     * Set a setting value
     */
    public function set(string $key, mixed $value, string $type = 'string', string $category = 'store', bool $isPublic = false): void
    {
        $serialized = $this->serializeValue($value, $type);

        $existing = $this->findBy('setting_key', $key);

        if ($existing) {
            $this->update($existing['id'], [
                'setting_value' => $serialized,
                'setting_type' => $type,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            $this->create([
                'setting_key' => $key,
                'setting_value' => $serialized,
                'setting_type' => $type,
                'category' => $category,
                'is_public' => $isPublic ? 1 : 0
            ]);
        }

        // Update cache
        self::$cache[$key] = [
            'value' => $value,
            'type' => $type,
            'id' => $existing['id'] ?? null
        ];
    }

    /**
     * Get all settings by category
     */
    public function getByCategory(string $category): array
    {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE category = ? ORDER BY setting_key",
            [$category]
        );
    }

    /**
     * Get all public settings (safe for frontend)
     */
    public function getPublicSettings(): array
    {
        $settings = $this->query(
            "SELECT setting_key, setting_value, setting_type FROM {$this->table} WHERE is_public = 1"
        );

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting['setting_key']] = $this->castValue(
                $setting['setting_value'],
                $setting['setting_type']
            );
        }
        return $result;
    }

    /**
     * Get multiple settings by keys
     */
    public function getMultiple(array $keys): array
    {
        $this->loadCache();

        $result = [];
        foreach ($keys as $key) {
            $result[$key] = self::$cache[$key]['value'] ?? null;
        }
        return $result;
    }

    /**
     * Delete a setting
     */
    public function deleteSetting(string $key): bool
    {
        $setting = $this->findBy('setting_key', $key);
        if ($setting) {
            $this->delete($setting['id']);
            unset(self::$cache[$key]);
            return true;
        }
        return false;
    }

    /**
     * Clear the cache (useful after bulk updates)
     */
    public function clearCache(): void
    {
        self::$cache = [];
        self::$cacheLoaded = false;
    }

    /**
     * Cast value based on type
     */
    private function castValue(?string $value, string $type): mixed
    {
        if ($value === null || $value === '') {
            return match($type) {
                'integer' => 0,
                'boolean' => false,
                'json' => [],
                default => ''
            };
        }

        return match($type) {
            'integer' => (int)$value,
            'boolean' => (bool)$value && $value !== '0' && strtolower($value) !== 'false',
            'json' => json_decode($value, true) ?? [],
            default => $value
        };
    }

    /**
     * Serialize value for storage
     */
    private function serializeValue(mixed $value, string $type): string
    {
        return match($type) {
            'json' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string)$value
        };
    }
}
