<?php

namespace App\Core\Plugins;

use App\Models\Plugin;

/**
 * Abstract base class for payment provider plugins
 * Implements common functionality
 */
abstract class AbstractPaymentProvider implements PaymentProviderInterface
{
    protected array $settings = [];
    protected ?int $pluginId = null;

    public function __construct()
    {
        $this->loadSettings();
    }

    /**
     * Load settings from database
     */
    protected function loadSettings(): void
    {
        $pluginModel = new Plugin();
        $plugin = $pluginModel->getBySlug($this->getSlug());

        if ($plugin) {
            $this->pluginId = $plugin['id'];
            $this->settings = json_decode($plugin['settings'] ?? '{}', true) ?: [];
        }

        // Merge with defaults
        $this->settings = array_merge($this->getDefaultSettings(), $this->settings);
    }

    /**
     * Get a setting value
     */
    protected function getSetting(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * Get all settings
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * Default implementation - override as needed
     */
    public function getType(): string
    {
        return 'payment';
    }

    /**
     * Default initialization - override as needed
     */
    public function init(): void
    {
        // Override in subclass if needed
    }

    /**
     * Default activation handler - override as needed
     */
    public function onActivate(): void
    {
        // Override in subclass if needed
    }

    /**
     * Default deactivation handler - override as needed
     */
    public function onDeactivate(): void
    {
        // Override in subclass if needed
    }

    /**
     * Default settings validation - override for custom validation
     */
    public function validateSettings(array $settings): array
    {
        $errors = [];
        $schema = $this->getSettingsSchema();

        foreach ($schema as $field) {
            $key = $field['key'];
            $required = $field['required'] ?? false;
            $value = $settings[$key] ?? null;

            if ($required && empty($value)) {
                $errors[$key] = ($field['label'] ?? $key) . ' is required';
            }
        }

        return $errors;
    }

    /**
     * Default settings view - generates form from schema
     */
    public function getSettingsView(): string
    {
        $schema = $this->getSettingsSchema();
        $html = '<div class="plugin-settings-form">';

        foreach ($schema as $field) {
            $key = $field['key'];
            $type = $field['type'] ?? 'text';
            $label = $field['label'] ?? $key;
            $value = escape($this->getSetting($key, $field['default'] ?? ''));
            $required = ($field['required'] ?? false) ? 'required' : '';
            $help = $field['help'] ?? '';

            $html .= '<div class="form-group">';
            $html .= "<label for=\"setting_{$key}\">{$label}</label>";

            switch ($type) {
                case 'password':
                    $html .= "<input type=\"password\" name=\"settings[{$key}]\" id=\"setting_{$key}\" value=\"{$value}\" class=\"form-control\" {$required}>";
                    break;

                case 'textarea':
                    $html .= "<textarea name=\"settings[{$key}]\" id=\"setting_{$key}\" class=\"form-control\" {$required}>{$value}</textarea>";
                    break;

                case 'select':
                    $html .= "<select name=\"settings[{$key}]\" id=\"setting_{$key}\" class=\"form-control\" {$required}>";
                    foreach ($field['options'] as $opt) {
                        $selected = ($value === $opt) ? 'selected' : '';
                        $html .= "<option value=\"{$opt}\" {$selected}>{$opt}</option>";
                    }
                    $html .= '</select>';
                    break;

                case 'checkbox':
                    $checked = $value ? 'checked' : '';
                    $html .= "<input type=\"hidden\" name=\"settings[{$key}]\" value=\"0\">";
                    $html .= "<input type=\"checkbox\" name=\"settings[{$key}]\" id=\"setting_{$key}\" value=\"1\" {$checked}>";
                    break;

                default:
                    $html .= "<input type=\"text\" name=\"settings[{$key}]\" id=\"setting_{$key}\" value=\"{$value}\" class=\"form-control\" {$required}>";
            }

            if ($help) {
                $html .= "<span class=\"form-help\">{$help}</span>";
            }

            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Default supported features
     */
    public function getSupportedFeatures(): array
    {
        return [
            'refunds' => true,
            'partial_refunds' => true,
            'webhooks' => true,
            'recurring' => false,
            'saved_cards' => false
        ];
    }

    /**
     * Check if feature is supported
     */
    public function supportsFeature(string $feature): bool
    {
        $features = $this->getSupportedFeatures();
        return $features[$feature] ?? false;
    }

    /**
     * Log payment event for debugging
     */
    protected function log(string $message, array $context = []): void
    {
        $logFile = BASE_PATH . '/storage/logs/payments.log';
        $timestamp = date('Y-m-d H:i:s');
        $provider = $this->getSlug();
        $contextJson = json_encode($context);

        $logLine = "[{$timestamp}] [{$provider}] {$message} {$contextJson}\n";
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * Convert amount to cents (for providers that need integer amounts)
     */
    protected function toCents(float $amount): int
    {
        return (int) round($amount * 100);
    }

    /**
     * Convert cents to dollars
     */
    protected function fromCents(int $cents): float
    {
        return $cents / 100;
    }
}
