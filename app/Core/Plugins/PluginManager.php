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
        $zip = new \ZipArchive();

        if ($zip->open($zipPath) !== true) {
            return ['success' => false, 'error' => 'Failed to open ZIP file'];
        }

        // Look for plugin.json in the ZIP
        $manifest = null;
        $pluginDir = null;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if (basename($filename) === 'plugin.json') {
                $manifestContent = $zip->getFromIndex($i);
                $manifest = json_decode($manifestContent, true);
                $pluginDir = dirname($filename);
                break;
            }
        }

        if (!$manifest) {
            $zip->close();
            return ['success' => false, 'error' => 'plugin.json not found in ZIP'];
        }

        // Validate manifest
        if (empty($manifest['slug']) || empty($manifest['name']) || empty($manifest['version'])) {
            $zip->close();
            return ['success' => false, 'error' => 'Invalid plugin.json - missing required fields'];
        }

        $slug = $manifest['slug'];

        // Check if plugin already exists
        if ($this->pluginModel->exists($slug)) {
            $zip->close();
            return ['success' => false, 'error' => 'Plugin already installed'];
        }

        // Extract to plugins directory
        $extractPath = BASE_PATH . '/content/plugins/' . $slug;

        if (!is_dir($extractPath)) {
            mkdir($extractPath, 0755, true);
        }

        // Extract files
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);

            // Skip if not in plugin directory
            if ($pluginDir && strpos($filename, $pluginDir) !== 0) {
                continue;
            }

            // Get relative path
            $relativePath = $pluginDir ? substr($filename, strlen($pluginDir) + 1) : $filename;

            if (empty($relativePath)) {
                continue;
            }

            $targetPath = $extractPath . '/' . $relativePath;

            // Create directory if needed
            if (substr($filename, -1) === '/') {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
            } else {
                $targetDir = dirname($targetPath);
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                file_put_contents($targetPath, $zip->getFromIndex($i));
            }
        }

        $zip->close();

        // Security check for themes - reject PHP files
        if (($manifest['type'] ?? '') === 'theme') {
            $phpFiles = glob($extractPath . '/**/*.php', GLOB_BRACE);
            if (!empty($phpFiles)) {
                $this->removeDirectory($extractPath);
                return ['success' => false, 'error' => 'Themes cannot contain PHP files'];
            }
        }

        // Register in database
        $pluginId = $this->pluginModel->install($manifest);

        if (!$pluginId) {
            $this->removeDirectory($extractPath);
            return ['success' => false, 'error' => 'Failed to register plugin in database'];
        }

        return [
            'success' => true,
            'plugin_id' => $pluginId,
            'slug' => $slug,
            'name' => $manifest['name']
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
