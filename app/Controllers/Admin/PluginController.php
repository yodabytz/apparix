<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Plugins\PluginManager;
use App\Models\Plugin;
use App\Models\AdminUser;

class PluginController extends Controller
{
    private Plugin $pluginModel;
    private PluginManager $pluginManager;
    private AdminUser $adminModel;
    private ?array $admin = null;

    public function __construct()
    {
        parent::__construct();
        $this->pluginModel = new Plugin();
        $this->pluginManager = PluginManager::getInstance();
        $this->adminModel = new AdminUser();
        $this->requireAdmin();
    }

    /**
     * Require admin authentication
     */
    protected function requireAdmin(): void
    {
        $token = $_COOKIE['admin_token'] ?? null;
        if (!$token) {
            $this->redirect('/admin/login');
            exit;
        }

        $session = $this->adminModel->validateSession($token);
        if (!$session) {
            setcookie('admin_token', '', ['expires' => time() - 3600, 'path' => '/admin']);
            $this->redirect('/admin/login');
            exit;
        }

        $this->admin = $session;
    }

    /**
     * List all plugins
     */
    public function index(): void
    {
        $plugins = $this->pluginModel->getAll();

        // Group by type
        $grouped = [];
        foreach ($plugins as $plugin) {
            $type = $plugin['type'];
            if (!isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            $grouped[$type][] = $plugin;
        }

        $this->render('admin.plugins.index', [
            'title' => 'Plugins',
            'admin' => $this->admin,
            'plugins' => $plugins,
            'groupedPlugins' => $grouped,
            'typeLabels' => [
                'payment' => 'Payment Providers',
                'marketplace' => 'Marketplace Integrations',
                'shipping' => 'Shipping',
                'analytics' => 'Analytics',
                'marketing' => 'Marketing',
                'utility' => 'Utilities'
            ]
        ], 'admin');
    }

    /**
     * Upload and install a plugin
     */
    public function upload(): void
    {
        $this->requireValidCSRF();

        if (!isset($_FILES['plugin_zip']) || $_FILES['plugin_zip']['error'] !== UPLOAD_ERR_OK) {
            setFlash('error', 'Please select a valid ZIP file');
            $this->redirect('/admin/plugins');
            return;
        }

        $file = $_FILES['plugin_zip'];

        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if ($mimeType !== 'application/zip') {
            setFlash('error', 'Invalid file type. Please upload a ZIP file');
            $this->redirect('/admin/plugins');
            return;
        }

        // Install the plugin
        $result = $this->pluginManager->installFromZip($file['tmp_name']);

        if ($result['success']) {
            setFlash('success', "Plugin '{$result['name']}' installed successfully");
        } else {
            setFlash('error', $result['error']);
        }

        $this->redirect('/admin/plugins');
    }

    /**
     * Activate a plugin
     */
    public function activate(): void
    {
        $this->requireValidCSRF();

        $slug = $this->post('slug');

        if (!$slug) {
            $this->json(['success' => false, 'error' => 'Plugin slug required']);
            return;
        }

        $result = $this->pluginManager->activate($slug);

        if ($this->isAjax()) {
            $this->json($result);
        } else {
            if ($result['success']) {
                setFlash('success', 'Plugin activated successfully');
            } else {
                setFlash('error', $result['error']);
            }
            $this->redirect('/admin/plugins');
        }
    }

    /**
     * Deactivate a plugin
     */
    public function deactivate(): void
    {
        $this->requireValidCSRF();

        $slug = $this->post('slug');

        if (!$slug) {
            $this->json(['success' => false, 'error' => 'Plugin slug required']);
            return;
        }

        $result = $this->pluginManager->deactivate($slug);

        if ($this->isAjax()) {
            $this->json($result);
        } else {
            if ($result['success']) {
                setFlash('success', 'Plugin deactivated');
            } else {
                setFlash('error', $result['error']);
            }
            $this->redirect('/admin/plugins');
        }
    }

    /**
     * Delete/uninstall a plugin
     */
    public function delete(): void
    {
        $this->requireValidCSRF();

        $slug = $this->post('slug');

        if (!$slug) {
            $this->json(['success' => false, 'error' => 'Plugin slug required']);
            return;
        }

        $result = $this->pluginManager->uninstall($slug);

        if ($this->isAjax()) {
            $this->json($result);
        } else {
            if ($result['success']) {
                setFlash('success', 'Plugin uninstalled');
            } else {
                setFlash('error', $result['error']);
            }
            $this->redirect('/admin/plugins');
        }
    }

    /**
     * Show plugin settings
     */
    public function settings(): void
    {
        $slug = $this->get('slug');

        if (!$slug) {
            $this->redirect('/admin/plugins');
            return;
        }

        $plugin = $this->pluginModel->getBySlug($slug);

        if (!$plugin) {
            setFlash('error', 'Plugin not found');
            $this->redirect('/admin/plugins');
            return;
        }

        // Get the loaded plugin instance for settings view
        $loadedPlugin = $this->pluginManager->getPlugin($slug);
        $settingsHtml = '';
        $settingsSchema = [];

        // If plugin isn't loaded (not active), load it temporarily for settings
        if (!$loadedPlugin) {
            $loadedPlugin = $this->loadPluginTemporarily($slug);
        }

        if ($loadedPlugin) {
            $settingsHtml = $loadedPlugin->getSettingsView();
            $settingsSchema = $loadedPlugin->getSettingsSchema();
        }

        $settings = json_decode($plugin['settings'] ?? '{}', true) ?: [];

        $this->render('admin.plugins.settings', [
            'title' => 'Plugin Settings - ' . $plugin['name'],
            'admin' => $this->admin,
            'plugin' => $plugin,
            'settings' => $settings,
            'settingsHtml' => $settingsHtml,
            'settingsSchema' => $settingsSchema
        ], 'admin');
    }

    /**
     * Save plugin settings
     */
    public function saveSettings(): void
    {
        $this->requireValidCSRF();

        $slug = $this->post('slug');

        if (!$slug) {
            $this->json(['success' => false, 'error' => 'Plugin slug required']);
            return;
        }

        $plugin = $this->pluginModel->getBySlug($slug);

        if (!$plugin) {
            setFlash('error', 'Plugin not found');
            $this->redirect('/admin/plugins');
            return;
        }

        $settings = $this->post('settings', []);

        // Validate settings if plugin is loaded (or load temporarily)
        $loadedPlugin = $this->pluginManager->getPlugin($slug);
        if (!$loadedPlugin) {
            $loadedPlugin = $this->loadPluginTemporarily($slug);
        }
        if ($loadedPlugin) {
            $errors = $loadedPlugin->validateSettings($settings);
            if (!empty($errors)) {
                if ($this->isAjax()) {
                    $this->json(['success' => false, 'errors' => $errors]);
                    return;
                }
                setFlash('error', 'Validation errors: ' . implode(', ', $errors));
                $this->redirect('/admin/plugins/settings?slug=' . $slug);
                return;
            }
        }

        // Save settings
        $this->pluginModel->updateSettings($plugin['id'], $settings);

        if ($this->isAjax()) {
            $this->json(['success' => true]);
        } else {
            setFlash('success', 'Settings saved');
            $this->redirect('/admin/plugins/settings?slug=' . $slug);
        }
    }

    /**
     * Load a plugin temporarily (for viewing settings of inactive plugins)
     */
    private function loadPluginTemporarily(string $slug): ?\App\Core\Plugins\PluginInterface
    {
        $pluginPath = BASE_PATH . '/content/plugins/' . $slug;

        // Convert slug to class name (e.g., etsy-sync -> EtsySyncPlugin)
        $parts = explode('-', $slug);
        $className = implode('', array_map('ucfirst', $parts)) . 'Plugin';
        $classFile = $pluginPath . '/' . $className . '.php';

        if (!file_exists($classFile)) {
            return null;
        }

        require_once $classFile;

        $fullClassName = "App\\Plugins\\{$className}";

        if (!class_exists($fullClassName)) {
            return null;
        }

        try {
            $plugin = new $fullClassName();
            if ($plugin instanceof \App\Core\Plugins\PluginInterface) {
                return $plugin;
            }
        } catch (\Exception $e) {
            error_log("Error loading plugin temporarily: " . $e->getMessage());
        }

        return null;
    }

}
