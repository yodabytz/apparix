<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\ThemeService;
use App\Models\AdminUser;
use App\Models\Setting;
use App\Models\Theme;

class ThemeController extends Controller
{
    private AdminUser $adminModel;
    private Theme $themeModel;
    private ?array $admin = null;

    public function __construct()
    {
        parent::__construct();
        $this->adminModel = new AdminUser();
        $this->themeModel = new Theme();
        $this->requireAdmin();
    }

    protected function requireAdmin(): void
    {
        $token = $_COOKIE['admin_token'] ?? null;
        if (!$token) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Authentication required'], 401);
                exit;
            }
            $this->redirect('/admin/login');
            exit;
        }

        $session = $this->adminModel->validateSession($token);
        if (!$session) {
            setcookie('admin_token', '', ['expires' => time() - 3600, 'path' => '/admin']);
            if ($this->isAjax()) {
                $this->json(['error' => 'Session expired'], 401);
                exit;
            }
            $this->redirect('/admin/login');
            exit;
        }

        $this->admin = $session;
    }

    /**
     * Theme selection page
     */
    public function index(): void
    {
        $presets = $this->themeModel->getPresets();
        $custom = $this->themeModel->getCustomThemes();
        $active = $this->themeModel->getActive();

        $this->render('admin.themes.index', [
            'title' => 'Theme Settings',
            'admin' => $this->admin,
            'presets' => $presets,
            'custom' => $custom,
            'active' => $active
        ], 'admin');
    }

    /**
     * Activate a theme
     */
    public function activate(): void
    {
        $this->requireValidCSRF();

        $themeId = (int)$this->post('theme_id');

        if (!$themeId) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Theme ID required'], 400);
            } else {
                redirectWithFlash('/admin/themes', 'error', 'Theme ID required');
            }
            return;
        }

        $success = $this->themeModel->activate($themeId);

        if ($this->isAjax()) {
            if ($success) {
                $this->json(['success' => true, 'message' => 'Theme activated']);
            } else {
                $this->json(['error' => 'Theme not found'], 404);
            }
        } else {
            if ($success) {
                redirectWithFlash('/admin/themes', 'success', 'Theme activated successfully');
            } else {
                redirectWithFlash('/admin/themes', 'error', 'Theme not found');
            }
        }
    }

    /**
     * Theme customization page
     */
    public function customize(): void
    {
        $themeId = (int)$this->get('id');

        if (!$themeId) {
            $this->redirect('/admin/themes');
            return;
        }

        $theme = $this->themeModel->find($themeId);

        if (!$theme) {
            redirectWithFlash('/admin/themes', 'error', 'Theme not found');
            return;
        }

        $fonts = $this->themeModel->getAvailableFonts();

        $this->render('admin.themes.customize', [
            'title' => 'Customize Theme: ' . $theme['name'],
            'admin' => $this->admin,
            'theme' => $theme,
            'fonts' => $fonts,
            'isPreset' => (bool)$theme['is_preset']
        ], 'admin');
    }

    /**
     * Save theme customization
     */
    public function save(): void
    {
        $this->requireValidCSRF();

        $themeId = (int)$this->post('theme_id');
        $theme = $this->themeModel->find($themeId);

        if (!$theme) {
            $this->json(['error' => 'Theme not found'], 404);
            return;
        }

        // Build effect settings from form fields
        $effectSettings = [
            'button_glow' => [
                'enabled' => (bool)$this->post('effect_button_glow_enabled'),
                'intensity' => $this->post('effect_button_glow_intensity', 'medium')
            ],
            'hover_animations' => [
                'enabled' => (bool)$this->post('effect_hover_enabled'),
                'speed' => $this->post('effect_hover_speed', 'normal')
            ],
            'background_animation' => [
                'enabled' => (bool)$this->post('effect_background_enabled'),
                'style' => $this->post('effect_background_style', 'circles'),
                'opacity' => max(0.05, min(1, (float)$this->post('effect_background_opacity', '0.5')))
            ],
            'header_effect' => [
                'enabled' => (bool)$this->post('effect_header_enabled'),
                'style' => $this->post('effect_header_style', 'swirl'),
                'location' => in_array($this->post('effect_header_location'), ['navbar', 'hero']) ? $this->post('effect_header_location') : 'navbar',
                'opacity' => max(0.05, min(1, (float)$this->post('effect_header_opacity', '0.5')))
            ],
            'page_transitions' => [
                'enabled' => (bool)$this->post('effect_page_transitions_enabled'),
                'style' => $this->post('effect_page_transitions_style', 'fade-up')
            ],
            'shimmer_effects' => [
                'enabled' => (bool)$this->post('effect_shimmer_enabled')
            ],
            'shadow_style' => $this->post('effect_shadow_style', 'soft'),
            'border_radius' => $this->post('effect_border_radius', 'rounded'),
            'card_hover' => [
                'enabled' => (bool)$this->post('effect_card_hover_enabled'),
                'style' => $this->post('effect_card_hover_style', 'lift')
            ],
            'holiday_effects' => [
                'enabled' => (bool)$this->post('effect_holiday_enabled'),
                'preview' => $this->post('holiday_preview_holiday', 'none'),
                'heroes' => $this->buildHolidayHeroes()
            ]
        ];

        $data = [
            'name' => $this->post('name', $theme['name']),
            'primary_color' => $this->post('primary_color', $theme['primary_color']),
            'secondary_color' => $this->post('secondary_color', $theme['secondary_color']),
            'accent_color' => $this->post('accent_color', $theme['accent_color']),
            'navbar_bg_color' => $this->post('navbar_bg_color', $theme['navbar_bg_color'] ?? '#FFFFFF'),
            'navbar_text_color' => $this->post('navbar_text_color', $theme['navbar_text_color'] ?? '#1f2937'),
            'glow_color' => $this->post('glow_color', $theme['glow_color'] ?? $theme['primary_color']),
            'hero_bg_start' => $this->post('hero_bg_start', $theme['hero_bg_start'] ?? '#0d0d1a'),
            'hero_bg_end' => $this->post('hero_bg_end', $theme['hero_bg_end'] ?? '#1a1a2e'),
            'heading_font' => $this->post('heading_font', $theme['heading_font']),
            'body_font' => $this->post('body_font', $theme['body_font']),
            'layout_style' => $this->post('layout_style', $theme['layout_style']),
            'header_style' => $this->post('header_style', $theme['header_style']),
            'category_layout' => $this->post('category_layout', $theme['category_layout']),
            'sidebar_menu_mode' => $this->post('sidebar_menu_mode', $theme['sidebar_menu_mode'] ?? 'hover'),
            'product_grid_columns' => (int)$this->post('product_grid_columns', $theme['product_grid_columns']),
            'custom_css' => $this->post('custom_css', ''),
            'effect_settings' => json_encode($effectSettings)
        ];

        // Validate colors are valid hex
        foreach (['primary_color', 'secondary_color', 'accent_color', 'navbar_bg_color', 'navbar_text_color', 'glow_color', 'hero_bg_start', 'hero_bg_end'] as $colorField) {
            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $data[$colorField])) {
                $this->json(['error' => 'Invalid color format for ' . $colorField], 400);
                return;
            }
        }

        // If it's a preset theme, create a custom copy
        if ($theme['is_preset']) {
            try {
                $newId = $this->themeModel->createCustomFromPreset($themeId, $data);

                // Optionally activate the new theme
                if ($this->post('activate')) {
                    $this->themeModel->activate($newId);
                }

                $this->json([
                    'success' => true,
                    'theme_id' => $newId,
                    'message' => 'Custom theme created',
                    'redirect' => '/admin/themes'
                ]);
            } catch (\Exception $e) {
                $this->json(['error' => $e->getMessage()], 500);
            }
            return;
        }

        // Update existing custom theme
        $success = $this->themeModel->updateCustomTheme($themeId, $data);

        if ($success) {
            // Optionally activate
            if ($this->post('activate')) {
                $this->themeModel->activate($themeId);
            }

            $this->json([
                'success' => true,
                'message' => 'Theme updated',
                'redirect' => '/admin/themes'
            ]);
        } else {
            $this->json(['error' => 'Failed to update theme'], 500);
        }
    }

    /**
     * Delete a custom theme
     */
    public function delete(): void
    {
        $this->requireValidCSRF();

        $themeId = (int)$this->post('theme_id');

        if (!$themeId) {
            $this->json(['error' => 'Theme ID required'], 400);
            return;
        }

        $theme = $this->themeModel->find($themeId);

        if (!$theme) {
            $this->json(['error' => 'Theme not found'], 404);
            return;
        }

        if ($theme['is_preset']) {
            $this->json(['error' => 'Cannot delete preset themes'], 400);
            return;
        }

        $success = $this->themeModel->deleteCustomTheme($themeId);

        if ($success) {
            $this->json(['success' => true, 'message' => 'Theme deleted']);
        } else {
            $this->json(['error' => 'Failed to delete theme'], 500);
        }
    }

    /**
     * Generate preview CSS for live customization (AJAX)
     */
    public function previewCss(): void
    {
        $primary = $this->get('primary', '#FF68C5');
        $secondary = $this->get('secondary', '#FF94C8');
        $accent = $this->get('accent', '#FFE4F3');

        // Validate colors
        foreach ([$primary, $secondary, $accent] as $color) {
            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
                header('Content-Type: text/css');
                echo '/* Invalid color */';
                exit;
            }
        }

        $css = ThemeService::generatePreviewCss($primary, $secondary, $accent);

        header('Content-Type: text/css');
        header('Cache-Control: no-cache');
        echo $css;
        exit;
    }

    /**
     * Create a new custom theme from scratch
     */
    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Show creation form
            $fonts = $this->themeModel->getAvailableFonts();

            $this->render('admin.themes.create', [
                'title' => 'Create New Theme',
                'admin' => $this->admin,
                'fonts' => $fonts
            ], 'admin');
            return;
        }

        // Handle POST - create the theme
        $this->requireValidCSRF();

        $data = [
            'name' => $this->post('name', 'New Theme'),
            'description' => $this->post('description', ''),
            'primary_color' => $this->post('primary_color', '#FF68C5'),
            'secondary_color' => $this->post('secondary_color', '#FF94C8'),
            'accent_color' => $this->post('accent_color', '#FFE4F3'),
            'navbar_bg_color' => $this->post('navbar_bg_color', '#FFFFFF'),
            'navbar_text_color' => $this->post('navbar_text_color', '#1f2937'),
            'glow_color' => $this->post('glow_color', '#FF68C5'),
            'hero_bg_start' => $this->post('hero_bg_start', '#0d0d1a'),
            'hero_bg_end' => $this->post('hero_bg_end', '#1a1a2e'),
            'heading_font' => $this->post('heading_font', 'Playfair Display'),
            'body_font' => $this->post('body_font', 'Inter'),
            'layout_style' => $this->post('layout_style', 'sidebar'),
            'header_style' => $this->post('header_style', 'standard'),
            'category_layout' => $this->post('category_layout', 'grid'),
            'sidebar_menu_mode' => $this->post('sidebar_menu_mode', 'hover'),
            'product_grid_columns' => (int)$this->post('product_grid_columns', 4),
            'custom_css' => $this->post('custom_css', '')
        ];

        // Validate required fields
        if (empty($data['name'])) {
            $this->json(['error' => 'Theme name is required'], 400);
            return;
        }

        // Generate slug
        $data['slug'] = 'custom-' . time();
        $data['is_preset'] = 0;
        $data['is_active'] = 0;

        // Generate color variants
        $data['color_variants'] = json_encode(
            $this->themeModel->generateColorVariants(
                $data['primary_color'],
                $data['secondary_color'],
                $data['accent_color']
            )
        );

        // Add default effect settings
        $data['effect_settings'] = json_encode($this->themeModel->getDefaultEffectSettings());

        try {
            $newId = $this->themeModel->create($data);

            if ($this->post('activate')) {
                $this->themeModel->activate($newId);
            }

            $this->json([
                'success' => true,
                'theme_id' => $newId,
                'message' => 'Theme created',
                'redirect' => '/admin/themes'
            ]);
        } catch (\Exception $e) {
            $this->json(['error' => 'Failed to create theme: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Upload and install a theme package (ZIP)
     */
    public function upload(): void
    {
        $this->requireValidCSRF();

        if (!isset($_FILES['theme_file']) || $_FILES['theme_file']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'No file uploaded or upload error'], 400);
            return;
        }

        $file = $_FILES['theme_file'];
        $tmpPath = $file['tmp_name'];
        $fileName = $file['name'];

        // Validate file extension
        if (pathinfo($fileName, PATHINFO_EXTENSION) !== 'zip') {
            $this->json(['error' => 'Only ZIP files are allowed'], 400);
            return;
        }

        // Create themes directory if not exists
        $themesPath = dirname(__DIR__, 3) . '/content/themes';
        if (!is_dir($themesPath)) {
            mkdir($themesPath, 0755, true);
        }

        // Create temp directory for extraction
        $tempDir = sys_get_temp_dir() . '/apparix_theme_' . uniqid();
        mkdir($tempDir, 0755, true);

        // Extract ZIP
        $zip = new \ZipArchive();
        if ($zip->open($tmpPath) !== true) {
            $this->json(['error' => 'Failed to open ZIP file'], 400);
            return;
        }

        $zip->extractTo($tempDir);
        $zip->close();

        // Find theme.json (might be in root or in a subdirectory)
        $manifestPath = $this->findThemeManifest($tempDir);

        if (!$manifestPath) {
            $this->deleteDirectory($tempDir);
            $this->json(['error' => 'Invalid theme package: theme.json not found'], 400);
            return;
        }

        // Read manifest
        $manifest = json_decode(file_get_contents($manifestPath), true);
        if (!$manifest || !isset($manifest['slug']) || !isset($manifest['name'])) {
            $this->deleteDirectory($tempDir);
            $this->json(['error' => 'Invalid theme.json: missing required fields'], 400);
            return;
        }

        // Validate slug (alphanumeric and dashes only)
        if (!preg_match('/^[a-z0-9-]+$/', $manifest['slug'])) {
            $this->deleteDirectory($tempDir);
            $this->json(['error' => 'Invalid theme slug: must be lowercase alphanumeric with dashes'], 400);
            return;
        }

        // Security: Check for PHP files in the theme (themes should be templates only)
        $themeBaseDir = dirname($manifestPath);
        if ($this->containsPhpFiles($themeBaseDir)) {
            $this->deleteDirectory($tempDir);
            $this->json(['error' => 'Theme packages cannot contain PHP files for security reasons'], 400);
            return;
        }

        // Check if theme already exists
        $targetPath = $themesPath . '/' . $manifest['slug'];
        if (is_dir($targetPath)) {
            // Remove existing theme
            $this->deleteDirectory($targetPath);
        }

        // Move theme to final location
        rename($themeBaseDir, $targetPath);

        // Clean up temp directory
        $this->deleteDirectory($tempDir);

        $this->json([
            'success' => true,
            'message' => 'Theme "' . $manifest['name'] . '" installed successfully',
            'slug' => $manifest['slug']
        ]);
    }

    /**
     * Activate an installed theme from /content/themes
     */
    public function activateInstalled(): void
    {
        $this->requireValidCSRF();

        $slug = $this->post('theme_slug');

        if (!$slug || !preg_match('/^[a-z0-9-]+$/', $slug)) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Invalid theme slug'], 400);
            } else {
                redirectWithFlash('/admin/themes', 'error', 'Invalid theme slug');
            }
            return;
        }

        // Verify theme exists
        $themePath = dirname(__DIR__, 3) . '/content/themes/' . $slug;
        $manifestPath = $themePath . '/theme.json';

        if (!is_dir($themePath) || !file_exists($manifestPath)) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Theme not found'], 404);
            } else {
                redirectWithFlash('/admin/themes', 'error', 'Theme not found');
            }
            return;
        }

        // Read manifest
        $manifest = json_decode(file_get_contents($manifestPath), true);

        // Build theme data from manifest (including expanded settings)
        $settings = $manifest['settings'] ?? [];
        $effects = $manifest['effects'] ?? null;

        $themeData = [
            'slug' => $manifest['slug'],
            'name' => $manifest['name'],
            'description' => $manifest['description'] ?? '',
            'primary_color' => $manifest['colors']['primary'] ?? '#FF68C5',
            'secondary_color' => $manifest['colors']['secondary'] ?? '#FF94C8',
            'accent_color' => $manifest['colors']['accent'] ?? '#FFE4F3',
            'heading_font' => $manifest['fonts']['heading'] ?? 'Playfair Display',
            'body_font' => $manifest['fonts']['body'] ?? 'Inter',
            'navbar_bg_color' => $settings['navbar_bg_color'] ?? '#FFFFFF',
            'navbar_text_color' => $settings['navbar_text_color'] ?? '#1f2937',
            'glow_color' => $settings['glow_color'] ?? ($manifest['colors']['secondary'] ?? '#FF68C5'),
            'hero_bg_start' => $settings['hero_bg_start'] ?? '#0d0d1a',
            'hero_bg_end' => $settings['hero_bg_end'] ?? '#1a1a2e',
            'layout_style' => $settings['layout_style'] ?? 'standard',
            'header_style' => $settings['header_style'] ?? 'standard',
            'category_layout' => $settings['category_layout'] ?? 'grid',
            'product_grid_columns' => (int)($settings['product_grid_columns'] ?? 4),
            'is_preset' => 0,
            'source' => 'installed'
        ];

        if ($effects) {
            $themeData['effect_settings'] = json_encode($effects);
        }

        // Create or update theme in database to track active installed theme
        $existingTheme = $this->themeModel->findBySlug($slug);

        if ($existingTheme) {
            // Update existing record with latest manifest data then activate
            $this->themeModel->updateCustomTheme($existingTheme['id'], $themeData);
            $this->themeModel->activate($existingTheme['id']);
        } else {
            $newId = $this->themeModel->create($themeData);
            $this->themeModel->activate($newId);
        }

        if ($this->isAjax()) {
            $this->json(['success' => true, 'message' => 'Theme activated']);
        } else {
            redirectWithFlash('/admin/themes', 'success', 'Theme "' . $manifest['name'] . '" activated');
        }
    }

    /**
     * Delete an installed theme from /content/themes
     */
    public function deleteInstalled(): void
    {
        $this->requireValidCSRF();

        $slug = $this->post('theme_slug');

        if (!$slug || !preg_match('/^[a-z0-9-]+$/', $slug)) {
            $this->json(['error' => 'Invalid theme slug'], 400);
            return;
        }

        $themePath = dirname(__DIR__, 3) . '/content/themes/' . $slug;

        if (!is_dir($themePath)) {
            $this->json(['error' => 'Theme not found'], 404);
            return;
        }

        // Check if this theme is active
        $active = $this->themeModel->getActive();
        if ($active && $active['slug'] === $slug) {
            $this->json(['error' => 'Cannot delete active theme. Please activate another theme first.'], 400);
            return;
        }

        // Delete the theme directory
        $this->deleteDirectory($themePath);

        // Also delete from database if exists
        $dbTheme = $this->themeModel->findBySlug($slug);
        if ($dbTheme && !$dbTheme['is_preset']) {
            $this->themeModel->deleteCustomTheme($dbTheme['id']);
        }

        $this->json(['success' => true, 'message' => 'Theme deleted']);
    }

    /**
     * Find theme.json in extracted directory (may be in root or subdirectory)
     */
    private function findThemeManifest(string $dir): ?string
    {
        // Check root
        if (file_exists($dir . '/theme.json')) {
            return $dir . '/theme.json';
        }

        // Check one level of subdirectories
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $subdir = $dir . '/' . $item;
            if (is_dir($subdir) && file_exists($subdir . '/theme.json')) {
                return $subdir . '/theme.json';
            }
        }

        return null;
    }

    /**
     * Check if directory contains PHP files (security check)
     */
    private function containsPhpFiles(string $dir): bool
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                return true;
            }
        }

        return false;
    }

    /**
     * Recursively delete a directory
     */
    private function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }

        return rmdir($dir);
    }

    /**
     * Start a live preview — sets preview cookie and redirects with signed URL
     */
    public function startPreview(): void
    {
        $this->requireValidCSRF();

        $type = $this->post('type'); // 'db' or 'installed'
        $slug = null;

        if ($type === 'installed') {
            $slug = $this->post('theme_slug');
            if (!$slug || !preg_match('/^[a-z0-9-]+$/', $slug)) {
                redirectWithFlash('/admin/themes', 'error', 'Invalid theme slug');
                return;
            }
            $themesPath = dirname(__DIR__, 3) . '/content/themes/' . $slug;
            if (!is_dir($themesPath) || !file_exists($themesPath . '/theme.json')) {
                redirectWithFlash('/admin/themes', 'error', 'Theme not found');
                return;
            }
        } else {
            $themeId = (int)$this->post('theme_id');
            $theme = $this->themeModel->find($themeId);
            if (!$theme) {
                redirectWithFlash('/admin/themes', 'error', 'Theme not found');
                return;
            }
            $slug = $theme['slug'];
        }

        // Set a site-wide preview token cookie (admin_token is path=/admin, can't be read on /)
        $adminToken = $_COOKIE['admin_token'] ?? '';
        $previewToken = hash_hmac('sha256', 'theme-preview-cookie:' . $adminToken, $adminToken);
        setcookie('theme_preview_token', $previewToken, [
            'expires' => time() + 3600, // 1 hour
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        $url = ThemeService::generatePreviewUrl($slug, $previewToken);
        $this->redirect($url);
    }

    /**
     * Activate a theme from preview (POST with slug in body)
     */
    public function activatePreview(): void
    {
        $this->requireValidCSRF();

        $slug = $this->post('slug');
        if (!$slug || !preg_match('/^[a-z0-9-]+$/', $slug)) {
            redirectWithFlash('/admin/themes', 'error', 'Invalid theme');
            return;
        }

        // Clear preview cookie
        setcookie('theme_preview_token', '', ['expires' => time() - 3600, 'path' => '/']);

        // Try DB theme first
        $dbTheme = $this->themeModel->findBySlug($slug);
        if ($dbTheme) {
            $this->themeModel->activate($dbTheme['id']);
            redirectWithFlash('/admin/themes', 'success', 'Theme "' . $dbTheme['name'] . '" activated!');
            return;
        }

        // Try installed theme
        $themePath = dirname(__DIR__, 3) . '/content/themes/' . $slug;
        $manifestPath = $themePath . '/theme.json';
        if (is_dir($themePath) && file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            $settings = $manifest['settings'] ?? [];
            $effects = $manifest['effects'] ?? null;
            $themeData = [
                'slug' => $manifest['slug'],
                'name' => $manifest['name'],
                'description' => $manifest['description'] ?? '',
                'primary_color' => $manifest['colors']['primary'] ?? '#FF68C5',
                'secondary_color' => $manifest['colors']['secondary'] ?? '#FF94C8',
                'accent_color' => $manifest['colors']['accent'] ?? '#FFE4F3',
                'heading_font' => $manifest['fonts']['heading'] ?? 'Playfair Display',
                'body_font' => $manifest['fonts']['body'] ?? 'Inter',
                'navbar_bg_color' => $settings['navbar_bg_color'] ?? '#FFFFFF',
                'navbar_text_color' => $settings['navbar_text_color'] ?? '#1f2937',
                'glow_color' => $settings['glow_color'] ?? ($manifest['colors']['secondary'] ?? '#FF68C5'),
                'hero_bg_start' => $settings['hero_bg_start'] ?? '#0d0d1a',
                'hero_bg_end' => $settings['hero_bg_end'] ?? '#1a1a2e',
                'layout_style' => $settings['layout_style'] ?? 'standard',
                'header_style' => $settings['header_style'] ?? 'standard',
                'category_layout' => $settings['category_layout'] ?? 'grid',
                'product_grid_columns' => (int)($settings['product_grid_columns'] ?? 4),
                'is_preset' => 0,
                'source' => 'installed'
            ];
            if ($effects) {
                $themeData['effect_settings'] = json_encode($effects);
            }
            $newId = $this->themeModel->create($themeData);
            $this->themeModel->activate($newId);
            redirectWithFlash('/admin/themes', 'success', 'Theme "' . $manifest['name'] . '" activated!');
            return;
        }

        redirectWithFlash('/admin/themes', 'error', 'Theme not found');
    }

    /**
     * 404 Error Page customization
     */
    public function errorPage(): void
    {
        $settingModel = new Setting();
        $settings = [
            'error_404_heading' => $settingModel->get('error_404_heading', "Oops! This page doesn't exist"),
            'error_404_message' => $settingModel->get('error_404_message', "The page you're looking for doesn't exist or has been moved. Don't worry, let's help you find your way back!"),
            'error_404_image' => $settingModel->get('error_404_image', ''),
            'error_404_primary_btn_text' => $settingModel->get('error_404_primary_btn_text', 'Back to Home'),
            'error_404_primary_btn_url' => $settingModel->get('error_404_primary_btn_url', '/'),
            'error_404_secondary_btn_text' => $settingModel->get('error_404_secondary_btn_text', 'Browse Products'),
            'error_404_secondary_btn_url' => $settingModel->get('error_404_secondary_btn_url', '/products'),
            'error_404_show_search' => (bool)$settingModel->get('error_404_show_search', '1'),
        ];

        $this->render('admin.themes.error-page', [
            'title' => '404 Error Page',
            'admin' => $this->admin,
            'settings' => $settings
        ], 'admin');
    }

    /**
     * Save 404 Error Page settings
     */
    public function saveErrorPage(): void
    {
        $this->requireValidCSRF();
        $settingModel = new Setting();

        $heading = mb_substr(trim($this->post('error_404_heading', "Oops! This page doesn't exist")), 0, 100);
        $message = mb_substr(trim($this->post('error_404_message', '')), 0, 500);
        $primaryBtnText = mb_substr(trim($this->post('error_404_primary_btn_text', 'Back to Home')), 0, 50);
        $primaryBtnUrl = mb_substr(trim($this->post('error_404_primary_btn_url', '/')), 0, 255);
        $secondaryBtnText = mb_substr(trim($this->post('error_404_secondary_btn_text', 'Browse Products')), 0, 50);
        $secondaryBtnUrl = mb_substr(trim($this->post('error_404_secondary_btn_url', '/products')), 0, 255);
        $showSearch = $this->post('error_404_show_search') ? '1' : '0';

        $settingModel->set('error_404_heading', $heading, 'string', 'theme', true);
        $settingModel->set('error_404_message', $message, 'string', 'theme', true);
        $settingModel->set('error_404_primary_btn_text', $primaryBtnText, 'string', 'theme', true);
        $settingModel->set('error_404_primary_btn_url', $primaryBtnUrl, 'string', 'theme', true);
        $settingModel->set('error_404_secondary_btn_text', $secondaryBtnText, 'string', 'theme', true);
        $settingModel->set('error_404_secondary_btn_url', $secondaryBtnUrl, 'string', 'theme', true);
        $settingModel->set('error_404_show_search', $showSearch, 'boolean', 'theme', true);

        // Handle image upload
        if (!empty($_FILES['error_404_image']) && $_FILES['error_404_image']['error'] === UPLOAD_ERR_OK) {
            $this->handleErrorPageImageUpload($settingModel);
        }

        // Handle image removal checkbox
        if ($this->post('remove_image') === '1') {
            $currentImage = $settingModel->get('error_404_image');
            if ($currentImage) {
                $path = dirname(__DIR__, 3) . '/public' . $currentImage;
                if (file_exists($path) && is_file($path)) {
                    unlink($path);
                }
                $settingModel->set('error_404_image', '', 'file', 'theme', true);
            }
        }

        redirectWithFlash('/admin/themes/404', 'success', '404 page settings saved successfully');
    }

    /**
     * Handle 404 page image upload
     */
    private function handleErrorPageImageUpload(Setting $settingModel): void
    {
        $file = $_FILES['error_404_image'];

        $allowedTypes = ['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp', 'image/gif'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $allowedTypes) || $file['size'] > 2 * 1024 * 1024) {
            return;
        }

        $uploadDir = dirname(__DIR__, 3) . '/public/assets/images/branding/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Delete old image
        $oldImage = $settingModel->get('error_404_image');
        if ($oldImage) {
            $oldPath = dirname(__DIR__, 3) . '/public' . $oldImage;
            if (file_exists($oldPath) && is_file($oldPath)) {
                unlink($oldPath);
            }
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'png';
        $filename = 'error-404-' . time() . '.' . strtolower($ext);

        if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            // Convert to WebP (skip SVG)
            if ($mimeType !== 'image/svg+xml') {
                $webpPath = convertToWebp($uploadDir . $filename);
                $filename = basename($webpPath);
            }
            $settingModel->set('error_404_image', '/assets/images/branding/' . $filename, 'file', 'theme', true);
        }
    }

    /**
     * Remove 404 page custom image (AJAX)
     */
    public function removeErrorPageImage(): void
    {
        $this->requireValidCSRF();
        $settingModel = new Setting();

        $currentImage = $settingModel->get('error_404_image');
        if ($currentImage) {
            $path = dirname(__DIR__, 3) . '/public' . $currentImage;
            if (file_exists($path) && is_file($path)) {
                unlink($path);
            }
            $settingModel->set('error_404_image', '', 'file', 'theme', true);
        }

        $this->json(['success' => true, 'message' => '404 image removed']);
    }

    /**
     * Upload a theme-specific logo
     */
    public function uploadThemeLogo(): void
    {
        $this->requireValidCSRF();

        $themeId = (int)$this->post('theme_id');
        $theme = $this->themeModel->find($themeId);
        if (!$theme) {
            $this->json(['error' => 'Theme not found'], 404);
            return;
        }

        $file = $_FILES['theme_logo'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'No file uploaded'], 400);
            return;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        $allowedTypes = ['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp', 'image/gif'];
        if (!in_array($mimeType, $allowedTypes)) {
            $this->json(['error' => 'Invalid file type. Allowed: PNG, JPG, SVG, WebP, GIF'], 400);
            return;
        }

        if ($file['size'] > 2 * 1024 * 1024) {
            $this->json(['error' => 'File too large. Maximum 2MB.'], 400);
            return;
        }

        $slug = $theme['slug'];
        $uploadDir = PUBLIC_PATH . '/assets/images/themes/' . $slug . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'png';
        $filename = 'logo-' . time() . '.' . strtolower($ext);

        // Delete old logo if exists
        if (!empty($theme['theme_logo'])) {
            $oldPath = PUBLIC_PATH . $theme['theme_logo'];
            if (file_exists($oldPath) && is_file($oldPath)) {
                unlink($oldPath);
            }
        }

        if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            $webPath = '/assets/images/themes/' . $slug . '/' . $filename;
            $this->themeModel->updateCustomTheme($themeId, ['theme_logo' => $webPath]);

            $this->json([
                'success' => true,
                'path' => $webPath,
                'message' => 'Theme logo uploaded'
            ]);
        } else {
            $this->json(['error' => 'Failed to save file'], 500);
        }
    }

    /**
     * Remove theme-specific logo
     */
    public function removeThemeLogo(): void
    {
        $this->requireValidCSRF();

        $themeId = (int)$this->post('theme_id');
        $theme = $this->themeModel->find($themeId);
        if (!$theme) {
            $this->json(['error' => 'Theme not found'], 404);
            return;
        }

        if (!empty($theme['theme_logo'])) {
            $path = PUBLIC_PATH . $theme['theme_logo'];
            if (file_exists($path) && is_file($path)) {
                unlink($path);
            }
        }

        $this->themeModel->updateCustomTheme($themeId, ['theme_logo' => null]);
        $this->json(['success' => true, 'message' => 'Theme logo removed. Store default will be used.']);
    }

    /**
     * Upload a theme-specific hero background image
     */
    public function uploadThemeHeroImage(): void
    {
        $this->requireValidCSRF();

        $themeId = (int)$this->post('theme_id');
        $theme = $this->themeModel->find($themeId);
        if (!$theme) {
            $this->json(['error' => 'Theme not found'], 404);
            return;
        }

        $file = $_FILES['hero_image'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'No file uploaded'], 400);
            return;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        $allowedTypes = ['image/png', 'image/jpeg', 'image/webp'];
        if (!in_array($mimeType, $allowedTypes)) {
            $this->json(['error' => 'Invalid file type. Allowed: PNG, JPG, WebP'], 400);
            return;
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            $this->json(['error' => 'File too large. Maximum 5MB.'], 400);
            return;
        }

        $slug = $theme['slug'];
        $uploadDir = PUBLIC_PATH . '/assets/images/themes/' . $slug . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
        $filename = 'hero-' . time() . '.' . strtolower($ext);

        // Delete old hero image if exists
        if (!empty($theme['hero_image'])) {
            $oldPath = PUBLIC_PATH . $theme['hero_image'];
            if (file_exists($oldPath) && is_file($oldPath)) {
                unlink($oldPath);
            }
        }

        if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            $webPath = '/assets/images/themes/' . $slug . '/' . $filename;
            $this->themeModel->updateCustomTheme($themeId, ['hero_image' => $webPath]);

            $this->json([
                'success' => true,
                'path' => $webPath,
                'message' => 'Hero image uploaded'
            ]);
        } else {
            $this->json(['error' => 'Failed to save file'], 500);
        }
    }

    /**
     * Remove theme-specific hero image
     */
    public function removeThemeHeroImage(): void
    {
        $this->requireValidCSRF();

        $themeId = (int)$this->post('theme_id');
        $theme = $this->themeModel->find($themeId);
        if (!$theme) {
            $this->json(['error' => 'Theme not found'], 404);
            return;
        }

        if (!empty($theme['hero_image'])) {
            $path = PUBLIC_PATH . $theme['hero_image'];
            if (file_exists($path) && is_file($path)) {
                unlink($path);
            }
        }

        $this->themeModel->updateCustomTheme($themeId, ['hero_image' => null]);
        $this->json(['success' => true, 'message' => 'Hero image removed. Default gradient will be used.']);
    }

    private function buildHolidayHeroes(): array
    {
        $holidays = ['christmas', 'valentines', 'stpatricks', 'easter', 'independence', 'halloween', 'newyear'];
        $heroes = [];
        foreach ($holidays as $key) {
            $badge = trim($this->post("holiday_hero_{$key}_badge", ''));
            $heading = trim($this->post("holiday_hero_{$key}_heading", ''));
            $tagline = trim($this->post("holiday_hero_{$key}_tagline", ''));
            $ctaText = trim($this->post("holiday_hero_{$key}_cta_text", ''));
            $ctaUrl = trim($this->post("holiday_hero_{$key}_cta_url", ''));
            if ($badge || $heading || $tagline || $ctaText || $ctaUrl) {
                $heroes[$key] = array_filter([
                    'badge' => $badge,
                    'heading' => $heading,
                    'tagline' => $tagline,
                    'cta_text' => $ctaText,
                    'cta_url' => $ctaUrl,
                ]);
            }
        }
        return $heroes;
    }
}
