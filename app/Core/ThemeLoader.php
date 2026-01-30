<?php

namespace App\Core;

/**
 * Theme Loader
 *
 * Handles loading theme partials, components, and configuration.
 * Falls back to built-in defaults when theme doesn't override.
 */
class ThemeLoader
{
    private static ?array $activeTheme = null;
    private static ?array $themeManifest = null;
    private static string $basePath;
    private static string $themesPath;
    private static string $partialsPath;
    private static string $componentsPath;

    /**
     * Initialize the ThemeLoader
     */
    public static function init(): void
    {
        self::$basePath = dirname(__DIR__, 2);
        self::$themesPath = self::$basePath . '/content/themes';
        self::$partialsPath = self::$basePath . '/app/Views/layouts/partials';
        self::$componentsPath = self::$basePath . '/app/Views/components';
    }

    /**
     * Get the active theme info
     */
    public static function getActiveTheme(): ?array
    {
        if (self::$activeTheme !== null) {
            return self::$activeTheme;
        }

        // Get active theme from ThemeService
        $themeService = new ThemeService();
        $activeTheme = $themeService->getActiveTheme();

        if ($activeTheme) {
            self::$activeTheme = [
                'slug' => $activeTheme['slug'],
                'name' => $activeTheme['name'],
                'source' => $activeTheme['source'] ?? 'preset',
                'path' => self::$themesPath . '/' . $activeTheme['slug']
            ];

            // Load manifest if it's an installed theme
            if (self::$activeTheme['source'] === 'installed') {
                self::loadManifest(self::$activeTheme['slug']);
            }
        }

        return self::$activeTheme;
    }

    /**
     * Load theme manifest (theme.json)
     */
    private static function loadManifest(string $slug): void
    {
        $manifestPath = self::$themesPath . '/' . $slug . '/theme.json';

        if (file_exists($manifestPath)) {
            $content = file_get_contents($manifestPath);
            self::$themeManifest = json_decode($content, true);
        }
    }

    /**
     * Get the path to a partial
     *
     * @param string $name Partial name (e.g., 'header', 'footer', 'navbar')
     * @return string Absolute path to the partial file
     */
    public static function getPartial(string $name): string
    {
        self::init();
        $theme = self::getActiveTheme();

        // Check if theme overrides this partial
        if ($theme && $theme['source'] === 'installed') {
            $themePath = $theme['path'] . '/layouts/partials/' . $name . '.php';
            if (file_exists($themePath)) {
                return $themePath;
            }
        }

        // Fall back to built-in partial
        $builtInPath = self::$partialsPath . '/' . $name . '.php';
        if (file_exists($builtInPath)) {
            return $builtInPath;
        }

        // Return empty string if not found (caller should handle)
        return '';
    }

    /**
     * Get the path to a component
     *
     * @param string $name Component name (e.g., 'product-card', 'category-card')
     * @return string Absolute path to the component file
     */
    public static function getComponent(string $name): string
    {
        self::init();
        $theme = self::getActiveTheme();

        // Check if theme overrides this component
        if ($theme && $theme['source'] === 'installed') {
            $themePath = $theme['path'] . '/components/' . $name . '.php';
            if (file_exists($themePath)) {
                return $themePath;
            }
        }

        // Fall back to built-in component
        $builtInPath = self::$componentsPath . '/' . $name . '.php';
        if (file_exists($builtInPath)) {
            return $builtInPath;
        }

        return '';
    }

    /**
     * Get the path to a layout
     *
     * @param string $name Layout name (e.g., 'main', 'checkout')
     * @return string|null Absolute path to the layout file, or null to use default
     */
    public static function getLayout(string $name): ?string
    {
        self::init();
        $theme = self::getActiveTheme();

        // Check if theme overrides this layout
        if ($theme && $theme['source'] === 'installed') {
            $themePath = $theme['path'] . '/layouts/' . $name . '.php';
            if (file_exists($themePath)) {
                return $themePath;
            }
        }

        // Return null to indicate use default layout
        return null;
    }

    /**
     * Check if theme overrides a specific partial/component/layout
     *
     * @param string $type Type: 'partial', 'component', 'layout'
     * @param string $name Name of the file
     * @return bool
     */
    public static function hasOverride(string $type, string $name): bool
    {
        self::init();
        $theme = self::getActiveTheme();

        if (!$theme || $theme['source'] !== 'installed') {
            return false;
        }

        $subdir = match ($type) {
            'partial' => 'layouts/partials',
            'component' => 'components',
            'layout' => 'layouts',
            default => ''
        };

        if (empty($subdir)) {
            return false;
        }

        $path = $theme['path'] . '/' . $subdir . '/' . $name . '.php';
        return file_exists($path);
    }

    /**
     * Get menu configuration from theme
     *
     * @param string $menuName Menu name (e.g., 'main', 'footer')
     * @return array Menu items
     */
    public static function getMenu(string $menuName = 'main'): array
    {
        self::init();

        // First check theme manifest
        if (self::$themeManifest && isset(self::$themeManifest['menus'][$menuName])) {
            return self::$themeManifest['menus'][$menuName];
        }

        // Check for separate menus.json in theme
        $theme = self::getActiveTheme();
        if ($theme && $theme['source'] === 'installed') {
            $menusPath = $theme['path'] . '/config/menus.json';
            if (file_exists($menusPath)) {
                $menus = json_decode(file_get_contents($menusPath), true);
                if (isset($menus[$menuName])) {
                    return $menus[$menuName];
                }
            }
        }

        // Return default menu structure
        return self::getDefaultMenu($menuName);
    }

    /**
     * Get default menu structure
     */
    private static function getDefaultMenu(string $menuName): array
    {
        if ($menuName === 'main') {
            return [
                ['label' => 'Shop', 'url' => '/products'],
                ['label' => 'Categories', 'url' => '/categories'],
                ['label' => 'About', 'url' => '/about'],
                ['label' => 'Contact', 'url' => '/contact']
            ];
        }

        if ($menuName === 'footer') {
            return [
                'shop' => [
                    ['label' => 'All Products', 'url' => '/products'],
                    ['label' => 'Categories', 'url' => '/categories'],
                    ['label' => 'New Arrivals', 'url' => '/products?sort=newest']
                ],
                'support' => [
                    ['label' => 'Contact Us', 'url' => '/contact'],
                    ['label' => 'Shipping Info', 'url' => '/shipping'],
                    ['label' => 'Returns', 'url' => '/returns']
                ]
            ];
        }

        return [];
    }

    /**
     * Get theme-specific CSS file path
     */
    public static function getThemeCss(): ?string
    {
        self::init();
        $theme = self::getActiveTheme();

        if ($theme && $theme['source'] === 'installed') {
            $cssPath = '/content/themes/' . $theme['slug'] . '/assets/css/theme.css';
            $fullPath = self::$basePath . $cssPath;

            if (file_exists($fullPath)) {
                // Add cache buster
                return $cssPath . '?v=' . filemtime($fullPath);
            }
        }

        return null;
    }

    /**
     * Get theme-specific JS file path
     */
    public static function getThemeJs(): ?string
    {
        self::init();
        $theme = self::getActiveTheme();

        if ($theme && $theme['source'] === 'installed') {
            $jsPath = '/content/themes/' . $theme['slug'] . '/assets/js/theme.js';
            $fullPath = self::$basePath . $jsPath;

            if (file_exists($fullPath)) {
                return $jsPath . '?v=' . filemtime($fullPath);
            }
        }

        return null;
    }

    /**
     * Get theme screenshot path
     */
    public static function getThemeScreenshot(string $slug, string $type = 'thumbnail'): ?string
    {
        self::init();

        $filename = $type === 'thumbnail' ? 'thumbnail.png' : 'screenshot.png';
        $path = '/content/themes/' . $slug . '/' . $filename;
        $fullPath = self::$basePath . $path;

        if (file_exists($fullPath)) {
            return $path;
        }

        // Try alternative extensions
        foreach (['jpg', 'jpeg', 'webp'] as $ext) {
            $altFilename = str_replace('.png', '.' . $ext, $filename);
            $altPath = '/content/themes/' . $slug . '/' . $altFilename;
            if (file_exists(self::$basePath . $altPath)) {
                return $altPath;
            }
        }

        return null;
    }

    /**
     * Get all installed themes (from /content/themes)
     */
    public static function getInstalledThemes(): array
    {
        self::init();
        $themes = [];

        if (!is_dir(self::$themesPath)) {
            return $themes;
        }

        $dirs = scandir(self::$themesPath);

        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..' || $dir === '.gitkeep') {
                continue;
            }

            $themePath = self::$themesPath . '/' . $dir;
            $manifestPath = $themePath . '/theme.json';

            if (is_dir($themePath) && file_exists($manifestPath)) {
                $manifest = json_decode(file_get_contents($manifestPath), true);

                if ($manifest) {
                    $themes[] = [
                        'slug' => $manifest['slug'] ?? $dir,
                        'name' => $manifest['name'] ?? $dir,
                        'version' => $manifest['version'] ?? '1.0.0',
                        'description' => $manifest['description'] ?? '',
                        'author' => $manifest['author'] ?? 'Unknown',
                        'source' => 'installed',
                        'screenshot' => self::getThemeScreenshot($dir, 'screenshot'),
                        'thumbnail' => self::getThemeScreenshot($dir, 'thumbnail'),
                        'colors' => $manifest['colors'] ?? [],
                        'fonts' => $manifest['fonts'] ?? [],
                        'overrides' => $manifest['overrides'] ?? []
                    ];
                }
            }
        }

        return $themes;
    }

    /**
     * Render a partial directly
     *
     * @param string $name Partial name
     * @param array $data Data to pass to the partial
     * @return string Rendered HTML
     */
    public static function renderPartial(string $name, array $data = []): string
    {
        $path = self::getPartial($name);

        if (empty($path) || !file_exists($path)) {
            return '';
        }

        extract($data);
        ob_start();
        include $path;
        return ob_get_clean();
    }

    /**
     * Render a component directly
     *
     * @param string $name Component name
     * @param array $data Data to pass to the component
     * @return string Rendered HTML
     */
    public static function renderComponent(string $name, array $data = []): string
    {
        $path = self::getComponent($name);

        if (empty($path) || !file_exists($path)) {
            return '';
        }

        extract($data);
        ob_start();
        include $path;
        return ob_get_clean();
    }

    /**
     * Get theme configuration value
     *
     * @param string $key Configuration key (dot notation supported)
     * @param mixed $default Default value
     * @return mixed
     */
    public static function getConfig(string $key, mixed $default = null): mixed
    {
        self::init();

        if (!self::$themeManifest) {
            return $default;
        }

        $keys = explode('.', $key);
        $value = self::$themeManifest;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Reset cached data (useful for testing or after theme switch)
     */
    public static function reset(): void
    {
        self::$activeTheme = null;
        self::$themeManifest = null;
    }
}
