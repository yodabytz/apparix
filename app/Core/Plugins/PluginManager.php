<?php

namespace App\Core\Plugins;

use App\Models\Plugin;

/**
 * Plugin Manager - Handles loading, activation, and management of plugins
 */
class PluginManager
{
    private static ?PluginManager $instance = null;
    private Plugin $pluginModel;
    private array $loadedPlugins = [];
    private array $paymentProviders = [];
    private bool $initialized = false;

    private function __construct()
    {
        $this->pluginModel = new Plugin();
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(): PluginManager
    {
        if (self::$instance === null) {
            self::$instance = new PluginManager();
        }
        return self::$instance;
    }

    /**
     * Initialize and load all active plugins
     */
    public function init(): void
    {
        if ($this->initialized) {
            return;
        }

        $activePlugins = $this->pluginModel->getActive();

        foreach ($activePlugins as $pluginData) {
            $this->loadPlugin($pluginData);
        }

        $this->initialized = true;
    }

    /**
     * Load a plugin by its database record
     */
    private function loadPlugin(array $pluginData): bool
    {
        $slug = $pluginData['slug'];

        // Check if already loaded
        if (isset($this->loadedPlugins[$slug])) {
            return true;
        }

        // Find the plugin class file
        $pluginPath = BASE_PATH . '/content/plugins/' . $slug;
        $classFile = $pluginPath . '/' . $this->getPluginClassName($slug) . '.php';

        // For built-in plugins, check alternative locations
        if (!file_exists($classFile)) {
            $classFile = $pluginPath . '/Plugin.php';
        }

        if (!file_exists($classFile)) {
            error_log("Plugin class file not found for: {$slug}");
            return false;
        }

        require_once $classFile;

        // Determine class name
        $className = $this->getPluginFullClassName($slug);

        if (!class_exists($className)) {
            error_log("Plugin class not found: {$className}");
            return false;
        }

        try {
            $plugin = new $className();

            // Verify it implements the interface
            if (!($plugin instanceof PluginInterface)) {
                error_log("Plugin {$slug} does not implement PluginInterface");
                return false;
            }

            // Initialize the plugin
            $plugin->init();

            // Store loaded plugin
            $this->loadedPlugins[$slug] = $plugin;

            // If it's a payment provider, register it
            if ($plugin instanceof PaymentProviderInterface) {
                $this->paymentProviders[$slug] = $plugin;
            }

            return true;
        } catch (\Exception $e) {
            error_log("Error loading plugin {$slug}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get plugin class name from slug
     */
    private function getPluginClassName(string $slug): string
    {
        $knownNames = [
            'paypal' => 'PayPalPlugin',
            'authorizenet' => 'AuthorizeNetPlugin',
        ];
        if (isset($knownNames[$slug])) {
            return $knownNames[$slug];
        }

        // Convert slug to PascalCase and add "Plugin"
        $parts = explode('-', $slug);
        $className = implode('', array_map('ucfirst', $parts)) . 'Plugin';
        return $className;
    }

    /**
     * Get full namespaced class name
     */
    private function getPluginFullClassName(string $slug): string
    {
        $className = $this->getPluginClassName($slug);
        return "App\\Plugins\\{$className}";
    }

    /**
     * Get a loaded plugin by slug
     */
    public function getPlugin(string $slug): ?PluginInterface
    {
        return $this->loadedPlugins[$slug] ?? null;
    }

    /**
     * Get all loaded plugins
     */
    public function getLoadedPlugins(): array
    {
        return $this->loadedPlugins;
    }

    /**
     * Get all loaded payment providers
     */
    public function getPaymentProviders(): array
    {
        return $this->paymentProviders;
    }

    /**
     * Get a payment provider by slug
     */
    public function getPaymentProvider(string $slug): ?PaymentProviderInterface
    {
        return $this->paymentProviders[$slug] ?? null;
    }

    /**
     * Get the default payment provider
     */
    public function getDefaultPaymentProvider(): ?PaymentProviderInterface
    {
        // Stripe is default if available
        if (isset($this->paymentProviders['stripe'])) {
            return $this->paymentProviders['stripe'];
        }

        // Otherwise return first available
        return reset($this->paymentProviders) ?: null;
    }

    /**
     * Get all configured payment providers (for checkout)
     */
    public function getConfiguredPaymentProviders(): array
    {
        return array_filter(
            $this->paymentProviders,
            fn($provider) => $provider->isConfigured()
        );
    }

    /**
     * Install a plugin from a ZIP file
     */
    public function installFromZip(string $zipPath): array
    {
        return $this->installPackage($zipPath);
    }

    /**
     * Install an authorized update for one already-installed plugin.
     */
    public function installUpdateFromZip(string $zipPath, string $expectedSlug, string $expectedVersion): array
    {
        if (!$this->pluginModel->exists($expectedSlug)) {
            return ['success' => false, 'error' => 'Plugin is not installed on this site.'];
        }
        return $this->installPackage($zipPath, $expectedSlug, $expectedVersion);
    }

    private function installPackage(string $zipPath, ?string $expectedSlug = null, ?string $expectedVersion = null): array
    {
        $installer = new PluginPackageInstaller();
        $installed = $installer->install($zipPath, $expectedSlug, $expectedVersion);
        if (!$installed['success']) {
            return $installed;
        }

        $manifest = $installed['manifest'];
        $slug = $manifest['slug'];
        $existing = $this->pluginModel->getBySlug($slug);
        try {
            if ($existing) {
                if (!$this->pluginModel->updateManifest((int)$existing['id'], $manifest)) {
                    throw new \RuntimeException('Failed to update plugin metadata.');
                }
                $pluginId = (int)$existing['id'];
            } else {
                $pluginId = $this->pluginModel->install($manifest);
                if (!$pluginId) {
                    throw new \RuntimeException('Failed to register plugin in the database.');
                }
            }
        } catch (\Throwable $e) {
            if (!$installer->restore($installed) && !$existing && is_dir($installed['target_path'])) {
                $this->removeDirectory($installed['target_path']);
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        return [
            'success' => true,
            'plugin_id' => $pluginId,
            'slug' => $slug,
            'name' => $manifest['name'],
            'version' => $manifest['version'],
            'updated' => (bool)$existing,
        ];
    }

    /**
     * Activate a plugin
     */
    public function activate(string $slug): array
    {
        $plugin = $this->pluginModel->getBySlug($slug);

        if (!$plugin) {
            return ['success' => false, 'error' => 'Plugin not found'];
        }

        if ($plugin['is_active']) {
            return ['success' => true, 'message' => 'Plugin already active'];
        }

        // Try to load the plugin first
        if (!$this->loadPlugin($plugin)) {
            return ['success' => false, 'error' => 'Failed to load plugin'];
        }

        // Activate in database
        $this->pluginModel->activate($plugin['id']);

        // Call onActivate hook
        $loadedPlugin = $this->loadedPlugins[$slug] ?? null;
        if ($loadedPlugin) {
            $loadedPlugin->onActivate();
        }

        return ['success' => true];
    }

    /**
     * Deactivate a plugin
     */
    public function deactivate(string $slug): array
    {
        $plugin = $this->pluginModel->getBySlug($slug);

        if (!$plugin) {
            return ['success' => false, 'error' => 'Plugin not found'];
        }

        // Don't allow deactivating Stripe if it's the only payment provider
        if ($slug === 'stripe') {
            $otherProviders = array_filter(
                $this->paymentProviders,
                fn($p) => $p->getSlug() !== 'stripe' && $p->isConfigured()
            );
            if (empty($otherProviders)) {
                return ['success' => false, 'error' => 'Cannot deactivate Stripe - no other payment providers configured'];
            }
        }

        // Call onDeactivate hook
        $loadedPlugin = $this->loadedPlugins[$slug] ?? null;
        if ($loadedPlugin) {
            $loadedPlugin->onDeactivate();
        }

        // Deactivate in database
        $this->pluginModel->deactivate($plugin['id']);

        // Remove from loaded plugins
        unset($this->loadedPlugins[$slug]);
        unset($this->paymentProviders[$slug]);

        return ['success' => true];
    }

    /**
     * Uninstall a plugin
     */
    public function uninstall(string $slug): array
    {
        // Deactivate first
        $this->deactivate($slug);

        $plugin = $this->pluginModel->getBySlug($slug);

        if (!$plugin) {
            return ['success' => false, 'error' => 'Plugin not found'];
        }

        // Don't allow uninstalling built-in plugins
        if ($slug === 'stripe') {
            return ['success' => false, 'error' => 'Cannot uninstall built-in plugins'];
        }

        // Remove from database and files
        $result = $this->pluginModel->uninstall($plugin['id'], true);

        if (!$result) {
            return ['success' => false, 'error' => 'Failed to uninstall plugin'];
        }

        return ['success' => true];
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
}
