<?php

namespace App\Core\Tracking;

/**
 * Interface for tracking providers (17track, AfterShip, Ship24, etc.)
 * All providers must normalize their responses to this common format.
 */
interface TrackingProviderInterface
{
    /**
     * Check if the provider is configured with valid credentials
     */
    public function isConfigured(): bool;

    /**
     * Register tracking numbers for monitoring (if required by the provider)
     * Some providers (like 17track) require registration before lookup.
     * Others (like AfterShip) register automatically. Those can return success immediately.
     *
     * @param array $trackingNumbers Simple array of tracking number strings
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function register(array $trackingNumbers): array;

    /**
     * Get tracking status for one or more numbers
     * Returns normalized status results.
     *
     * @param array $trackingNumbers Simple array of tracking number strings
     * @return array ['success' => bool, 'results' => [trackingNumber => TrackingResult], 'error' => string|null]
     *
     * Each TrackingResult is an associative array:
     *   'status'      => string  (Delivered|InTransit|OutForDelivery|InfoReceived|Exception|NotFound|Unknown)
     *   'description' => string|null  (human-readable latest event)
     *   'location'    => string|null  (latest event location)
     *   'event_time'  => string|null  (ISO 8601 timestamp)
     *   'carrier'     => string|null  (detected carrier name)
     */
    public function getStatus(array $trackingNumbers): array;

    /**
     * Stop tracking numbers (free up quota if applicable)
     *
     * @param array $trackingNumbers Simple array of tracking number strings
     * @return array ['success' => bool]
     */
    public function stopTracking(array $trackingNumbers): array;

    /**
     * Get the provider's display name
     */
    public static function getName(): string;

    /**
     * Get the provider's signup/dashboard URL
     */
    public static function getUrl(): string;

    /**
     * Get a short description of the provider
     */
    public static function getDescription(): string;
}
