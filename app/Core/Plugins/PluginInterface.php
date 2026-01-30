<?php

namespace App\Core\Plugins;

/**
 * Base interface that all plugins must implement
 */
interface PluginInterface
{
    /**
     * Get the plugin slug (unique identifier)
     */
    public function getSlug(): string;

    /**
     * Get the plugin name for display
     */
    public function getName(): string;

    /**
     * Get the plugin version
     */
    public function getVersion(): string;

    /**
     * Get the plugin type (payment, shipping, analytics, marketing, utility)
     */
    public function getType(): string;

    /**
     * Get plugin description
     */
    public function getDescription(): string;

    /**
     * Get author name
     */
    public function getAuthor(): string;

    /**
     * Initialize the plugin
     */
    public function init(): void;

    /**
     * Called when the plugin is activated
     */
    public function onActivate(): void;

    /**
     * Called when the plugin is deactivated
     */
    public function onDeactivate(): void;

    /**
     * Get the admin settings form HTML
     */
    public function getSettingsView(): string;

    /**
     * Validate settings before saving
     * Returns array of errors or empty array if valid
     */
    public function validateSettings(array $settings): array;

    /**
     * Get default settings for the plugin
     */
    public function getDefaultSettings(): array;

    /**
     * Get the settings schema for the admin form
     * Returns array of field definitions
     */
    public function getSettingsSchema(): array;
}
