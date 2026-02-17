<?php

namespace App\Core;

use App\Models\Theme;

/**
 * ThemeService - Handles dynamic theme CSS generation and layout configuration
 */
class ThemeService
{
    private static ?ThemeService $instance = null;
    private ?array $activeTheme = null;
    private Theme $themeModel;

    public function __construct()
    {
        $this->themeModel = new Theme();
        $this->activeTheme = $this->themeModel->getActive();
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(): ThemeService
    {
        if (self::$instance === null) {
            self::$instance = new ThemeService();
        }
        return self::$instance;
    }

    /**
     * Get the currently active theme
     */
    public function getActiveTheme(): ?array
    {
        return $this->activeTheme;
    }

    /**
     * Generate CSS variables from active theme
     */
    public function generateCssVariables(): string
    {
        if (!$this->activeTheme) {
            return '';
        }

        $theme = $this->activeTheme;
        $variants = json_decode($theme['color_variants'] ?? '{}', true) ?: [];

        $css = ":root {\n";

        // Primary colors (using the existing CSS variable names for compatibility)
        $css .= "    --primary-pink: {$theme['primary_color']};\n";
        $css .= "    --secondary-pink: {$theme['secondary_color']};\n";
        $css .= "    --light-pink: {$theme['accent_color']};\n";

        // Navbar colors
        $navbarBg = $theme['navbar_bg_color'] ?? '#FFFFFF';
        $navbarText = $theme['navbar_text_color'] ?? '#1f2937';
        $css .= "    --navbar-bg: {$navbarBg};\n";
        $css .= "    --navbar-text: {$navbarText};\n";

        // Glow/effect color
        $glowColor = $theme['glow_color'] ?? $theme['primary_color'];
        $css .= "    --glow-color: {$glowColor};\n";
        // Generate glow rgba values
        $glowRgb = $this->hexToRgb($glowColor);
        if ($glowRgb) {
            $css .= "    --glow-color-rgb: {$glowRgb['r']}, {$glowRgb['g']}, {$glowRgb['b']};\n";
        }

        // Color variants
        if (!empty($variants['primary_light'])) {
            $css .= "    --primary-light: {$variants['primary_light']};\n";
        }
        if (!empty($variants['primary_dark'])) {
            $css .= "    --primary-dark: {$variants['primary_dark']};\n";
        }
        if (!empty($variants['primary_50'])) {
            $css .= "    --primary-50: {$variants['primary_50']};\n";
        }
        if (!empty($variants['secondary_light'])) {
            $css .= "    --secondary-light: {$variants['secondary_light']};\n";
        }
        if (!empty($variants['secondary_dark'])) {
            $css .= "    --secondary-dark: {$variants['secondary_dark']};\n";
        }
        if (!empty($variants['accent_light'])) {
            $css .= "    --accent-light: {$variants['accent_light']};\n";
        }
        if (!empty($variants['accent_dark'])) {
            $css .= "    --accent-dark: {$variants['accent_dark']};\n";
        }

        // Typography
        $headingFont = $theme['heading_font'] ?? 'Playfair Display';
        $bodyFont = $theme['body_font'] ?? 'Inter';
        $css .= "    --font-heading: '{$headingFont}', Georgia, serif;\n";
        $css .= "    --font-body: '{$bodyFont}', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;\n";

        // Layout-specific variables
        $gridColumns = $theme['product_grid_columns'] ?? 4;
        $css .= "    --product-grid-columns: {$gridColumns};\n";

        // Effect settings CSS variables
        $effects = $this->getEffectSettings();
        $css .= $this->generateEffectVariables($effects);

        $css .= "}\n";

        // Generate effect-specific CSS rules
        $css .= $this->generateEffectCss($effects);

        // Add custom CSS if defined
        if (!empty($theme['custom_css'])) {
            $css .= "\n/* Custom Theme CSS */\n";
            $css .= $theme['custom_css'] . "\n";
        }

        return $css;
    }

    /**
     * Get effect settings from active theme
     */
    public function getEffectSettings(): array
    {
        if (!$this->activeTheme) {
            return $this->themeModel->getDefaultEffectSettings();
        }

        $settings = $this->activeTheme['effect_settings'] ?? null;
        if (is_string($settings)) {
            $settings = json_decode($settings, true);
        }

        return is_array($settings) ? $settings : $this->themeModel->getDefaultEffectSettings();
    }

    /**
     * Generate CSS variables for effects
     */
    private function generateEffectVariables(array $effects): string
    {
        $css = "\n    /* Effect Variables */\n";

        // Transition speed
        $speed = $effects['hover_animations']['speed'] ?? 'normal';
        $transitionTime = match($speed) {
            'slow' => '0.4s',
            'fast' => '0.1s',
            default => '0.2s'
        };
        $css .= "    --transition-speed: {$transitionTime};\n";
        $css .= "    --transition: all {$transitionTime} ease;\n";

        // Border radius
        $borderRadius = $effects['border_radius'] ?? 'rounded';
        $radiusValue = match($borderRadius) {
            'sharp' => '0px',
            'slightly-rounded' => '4px',
            'pill' => '9999px',
            default => '8px'
        };
        $css .= "    --border-radius: {$radiusValue};\n";
        $css .= "    --border-radius-sm: " . ($borderRadius === 'sharp' ? '0px' : ($borderRadius === 'pill' ? '9999px' : '4px')) . ";\n";
        $css .= "    --border-radius-lg: " . ($borderRadius === 'sharp' ? '0px' : ($borderRadius === 'pill' ? '9999px' : '12px')) . ";\n";

        // Shadow intensity
        $shadowStyle = $effects['shadow_style'] ?? 'soft';
        $shadowValue = match($shadowStyle) {
            'none' => 'none',
            'subtle' => '0 1px 2px rgba(0,0,0,0.03)',
            'dramatic' => '0 10px 40px rgba(0,0,0,0.15)',
            default => '0 4px 15px rgba(0,0,0,0.08)'
        };
        $css .= "    --shadow: {$shadowValue};\n";

        // Button glow intensity
        $glowIntensity = $effects['button_glow']['intensity'] ?? 'medium';
        $glowOpacity = match($glowIntensity) {
            'subtle' => '0.2',
            'dramatic' => '0.5',
            default => '0.35'
        };
        $css .= "    --glow-opacity: {$glowOpacity};\n";

        return $css;
    }

    /**
     * Generate CSS rules for effects
     */
    private function generateEffectCss(array $effects): string
    {
        $css = "\n/* Theme Effect Styles */\n";

        // Disable hover animations if turned off
        if (empty($effects['hover_animations']['enabled'])) {
            $css .= ".product-card, .btn, a { transition: none !important; }\n";
        }

        // Disable button glow if turned off
        if (empty($effects['button_glow']['enabled'])) {
            $css .= ".btn-primary { box-shadow: none !important; }\n";
            $css .= ".btn-primary:hover { box-shadow: none !important; }\n";
        }

        // Background animation is handled via class in HTML, no CSS override needed when enabled

        // Disable page transitions if turned off
        if (empty($effects['page_transitions']['enabled']) || ($effects['page_transitions']['style'] ?? '') === 'none') {
            $css .= "@media (min-width: 769px) {\n";
            $css .= "    .fade-in-section, .product-card, .category-card { animation: none !important; }\n";
            $css .= "}\n";
        }

        // Disable shimmer effects if turned off
        if (empty($effects['shimmer_effects']['enabled'])) {
            $css .= ".hero-product::before, .shimmer-effect { display: none !important; }\n";
        }

        // Card hover effects
        $cardHover = $effects['card_hover'] ?? ['enabled' => true, 'style' => 'lift'];
        if (!empty($cardHover['enabled'])) {
            $hoverStyle = $cardHover['style'] ?? 'lift';
            switch ($hoverStyle) {
                case 'lift':
                    $css .= ".product-card:hover { transform: translateY(-8px); box-shadow: var(--shadow); }\n";
                    break;
                case 'glow':
                    $css .= ".product-card:hover { box-shadow: 0 0 20px rgba(var(--glow-color-rgb), var(--glow-opacity)); }\n";
                    break;
                case 'scale':
                    $css .= ".product-card:hover { transform: scale(1.03); }\n";
                    break;
            }
        } else {
            $css .= ".product-card:hover { transform: none !important; box-shadow: var(--shadow) !important; }\n";
        }

        // Shadow style overrides
        $shadowStyle = $effects['shadow_style'] ?? 'soft';
        if ($shadowStyle === 'none') {
            $css .= ".product-card, .card, .navbar { box-shadow: none !important; }\n";
        } elseif ($shadowStyle === 'dramatic') {
            $css .= ".product-card { box-shadow: 0 8px 30px rgba(0,0,0,0.12); }\n";
            $css .= ".navbar { box-shadow: 0 4px 20px rgba(0,0,0,0.1); }\n";
        }

        return $css;
    }

    /**
     * Get Google Fonts URL for current theme
     */
    public function getGoogleFontsUrl(): string
    {
        if (!$this->activeTheme) {
            return 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap';
        }

        $headingFont = $this->activeTheme['heading_font'] ?? 'Playfair Display';
        $bodyFont = $this->activeTheme['body_font'] ?? 'Inter';

        // Format font names for Google Fonts URL
        $fonts = [];

        if ($headingFont) {
            $fontName = str_replace(' ', '+', $headingFont);
            $fonts[] = "family={$fontName}:wght@400;500;600;700";
        }

        if ($bodyFont && $bodyFont !== $headingFont) {
            $fontName = str_replace(' ', '+', $bodyFont);
            $fonts[] = "family={$fontName}:wght@300;400;500;600;700";
        }

        if (empty($fonts)) {
            return 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap';
        }

        return 'https://fonts.googleapis.com/css2?' . implode('&', $fonts) . '&display=swap';
    }

    /**
     * Get layout style (sidebar or full-width)
     */
    public function getLayoutStyle(): string
    {
        return $this->activeTheme['layout_style'] ?? 'sidebar';
    }

    /**
     * Get header style (standard, centered, minimal)
     */
    public function getHeaderStyle(): string
    {
        return $this->activeTheme['header_style'] ?? 'standard';
    }

    /**
     * Get category/product listing layout (grid, list, masonry)
     */
    public function getCategoryLayout(): string
    {
        return $this->activeTheme['category_layout'] ?? 'grid';
    }

    /**
     * Get number of product grid columns
     */
    public function getProductGridColumns(): int
    {
        return (int)($this->activeTheme['product_grid_columns'] ?? 4);
    }

    /**
     * Get background animation style class
     * Returns the CSS class for the background animation, or empty if disabled
     */
    public function getBackgroundAnimationClass(): string
    {
        $effects = $this->getEffectSettings();

        if (empty($effects['background_animation']['enabled'])) {
            return 'bg-style-none';
        }

        $style = $effects['background_animation']['style'] ?? 'circles';
        return 'bg-style-' . $style;
    }

    /**
     * Check if background animation is enabled
     */
    public function isBackgroundAnimationEnabled(): bool
    {
        $effects = $this->getEffectSettings();
        return !empty($effects['background_animation']['enabled']);
    }

    /**
     * Get homepage layout configuration
     */
    public function getHomepageLayout(): array
    {
        if (!$this->activeTheme || empty($this->activeTheme['homepage_layout'])) {
            // Default homepage sections
            return [
                ['type' => 'hero', 'enabled' => true],
                ['type' => 'featured', 'enabled' => true],
                ['type' => 'categories', 'enabled' => true],
                ['type' => 'newsletter', 'enabled' => true]
            ];
        }

        $layout = json_decode($this->activeTheme['homepage_layout'], true);
        return is_array($layout) ? $layout : [];
    }

    /**
     * Check if layout is full-width
     */
    public function isFullWidth(): bool
    {
        return $this->getLayoutStyle() === 'full-width';
    }

    /**
     * Get body classes based on theme settings
     */
    public function getBodyClasses(): string
    {
        $classes = [];

        if ($this->activeTheme) {
            $classes[] = 'theme-' . ($this->activeTheme['slug'] ?? 'default');
            $classes[] = 'layout-' . $this->getLayoutStyle();
            $classes[] = 'header-' . $this->getHeaderStyle();
        }

        return implode(' ', $classes);
    }

    /**
     * Get theme meta color for browser theme
     */
    public function getThemeColor(): string
    {
        return $this->activeTheme['primary_color'] ?? '#FF68C5';
    }

    /**
     * Generate preview CSS for live customization
     */
    public static function generatePreviewCss(string $primary, string $secondary, string $accent): string
    {
        $themeModel = new Theme();
        $variants = $themeModel->generateColorVariants($primary, $secondary, $accent);

        $css = ":root {\n";
        $css .= "    --primary-pink: {$primary};\n";
        $css .= "    --secondary-pink: {$secondary};\n";
        $css .= "    --light-pink: {$accent};\n";

        foreach ($variants as $name => $color) {
            $varName = str_replace('_', '-', $name);
            $css .= "    --{$varName}: {$color};\n";
        }

        $css .= "}\n";

        return $css;
    }

    /**
     * Convert hex color to RGB array
     */
    private function hexToRgb(string $hex): ?array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) !== 6) {
            return null;
        }

        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2))
        ];
    }

    /**
     * Get theme colors as an array
     */
    public function getColors(): array
    {
        if (!$this->activeTheme) {
            return [
                'primary' => '#FF68C5',
                'secondary' => '#FF94C8',
                'accent' => '#FFF0F7'
            ];
        }

        return [
            'primary' => $this->activeTheme['primary_color'] ?? '#FF68C5',
            'secondary' => $this->activeTheme['secondary_color'] ?? '#FF94C8',
            'accent' => $this->activeTheme['accent_color'] ?? '#FFF0F7'
        ];
    }

    /**
     * Adjust color brightness
     * @param string $hex Hex color code
     * @param int $percent Percentage to adjust (-100 to 100)
     * @return string Adjusted hex color
     */
    public function adjustBrightness(string $hex, int $percent): string
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) !== 6) {
            return '#' . $hex;
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        if ($percent > 0) {
            // Lighten
            $r = min(255, $r + (255 - $r) * ($percent / 100));
            $g = min(255, $g + (255 - $g) * ($percent / 100));
            $b = min(255, $b + (255 - $b) * ($percent / 100));
        } else {
            // Darken
            $factor = (100 + $percent) / 100;
            $r = max(0, $r * $factor);
            $g = max(0, $g * $factor);
            $b = max(0, $b * $factor);
        }

        return sprintf('#%02x%02x%02x', (int)$r, (int)$g, (int)$b);
    }
}
