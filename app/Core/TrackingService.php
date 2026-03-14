<?php

namespace App\Core;

use App\Core\Tracking\TrackingProviderInterface;
use App\Core\Tracking\SeventeenTrackProvider;
use App\Core\Tracking\AfterShipProvider;

/**
 * Tracking Service — factory that delegates to the configured provider
 * Supports multiple tracking providers (17track, AfterShip, etc.)
 * Provider and API key are configured in Admin > Settings > Integrations
 */
class TrackingService
{
    private ?TrackingProviderInterface $provider = null;
    private string $providerKey = '';

    /**
     * Available tracking providers (slug => class)
     */
    private static array $providers = [
        '17track'   => SeventeenTrackProvider::class,
        'aftership' => AfterShipProvider::class,
    ];

    public function __construct()
    {
        $apiKey = '';
        $providerSlug = '';

        try {
            $db = Database::getInstance();
            $rows = $db->select(
                "SELECT setting_key, setting_value FROM settings
                 WHERE setting_key IN ('tracking_provider', 'tracking_api_key')"
            );
            foreach ($rows as $row) {
                if ($row['setting_key'] === 'tracking_api_key') $apiKey = $row['setting_value'];
                if ($row['setting_key'] === 'tracking_provider') $providerSlug = $row['setting_value'];
            }
        } catch (\Throwable $e) {
            // Settings table may not exist yet
        }

        // Fall back to .env
        if (empty($apiKey)) {
            $apiKey = $_ENV['TRACKING_API_KEY'] ?? $_ENV['AFTERSHIP_API_KEY'] ?? '';
        }
        if (empty($providerSlug)) {
            $providerSlug = $_ENV['TRACKING_PROVIDER'] ?? '17track';
        }

        $this->providerKey = $providerSlug;

        if (!empty($apiKey) && isset(self::$providers[$providerSlug])) {
            $class = self::$providers[$providerSlug];
            $this->provider = new $class($apiKey);
        }
    }

    /**
     * Check if a tracking provider is configured
     */
    public function isConfigured(): bool
    {
        return $this->provider !== null && $this->provider->isConfigured();
    }

    /**
     * Get the active provider name
     */
    public function getProviderName(): string
    {
        return $this->provider ? $this->provider::getName() : 'None';
    }

    /**
     * Register tracking numbers for monitoring
     */
    public function registerTracking(array $trackingNumbers): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Tracking not configured'];
        }
        return $this->provider->register($trackingNumbers);
    }

    /**
     * Get tracking status for numbers
     */
    public function getTrackingStatus(array $trackingNumbers): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Tracking not configured'];
        }
        return $this->provider->getStatus($trackingNumbers);
    }

    /**
     * Stop tracking numbers (free up quota)
     */
    public function stopTracking(array $trackingNumbers): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Tracking not configured'];
        }
        return $this->provider->stopTracking($trackingNumbers);
    }

    /**
     * Get all available provider info for the admin UI
     */
    public static function getAvailableProviders(): array
    {
        $list = [];
        foreach (self::$providers as $slug => $class) {
            $list[$slug] = [
                'name'        => $class::getName(),
                'url'         => $class::getUrl(),
                'description' => $class::getDescription(),
            ];
        }
        return $list;
    }
}
