<?php

namespace App\Models;

use App\Core\Model;

/**
 * Theme model for managing pre-built and custom themes
 */
class Theme extends Model
{
    protected string $table = 'themes';

    /**
     * Cached active theme
     */
    private static ?array $activeTheme = null;

    /**
     * Get the currently active theme
     */
    public function getActive(): ?array
    {
        if (self::$activeTheme !== null) {
            return self::$activeTheme;
        }

        self::$activeTheme = $this->queryOne(
            "SELECT * FROM {$this->table} WHERE is_active = 1 LIMIT 1"
        ) ?: null;

        return self::$activeTheme;
    }

    /**
     * Activate a theme by ID (deactivates all others)
     */
    public function activate(int $id): bool
    {
        $theme = $this->find($id);
        if (!$theme) {
            return false;
        }

        // Deactivate all themes
        $this->db->update("UPDATE {$this->table} SET is_active = 0", []);

        // Activate the selected theme
        $this->update($id, ['is_active' => 1]);

        // Clear cache
        self::$activeTheme = null;

        return true;
    }

    /**
     * Get all preset themes
     */
    public function getPresets(): array
    {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE is_preset = 1 ORDER BY name"
        );
    }

    /**
     * Get all custom (user-created) themes
     */
    public function getCustomThemes(): array
    {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE is_preset = 0 ORDER BY created_at DESC"
        );
    }

    /**
     * Find theme by slug
     */
    public function findBySlug(string $slug): array|false
    {
        return $this->findBy('slug', $slug);
    }

    /**
     * Create a custom theme based on a preset
     */
    public function createCustomFromPreset(int $presetId, array $customizations): int|string
    {
        $preset = $this->find($presetId);
        if (!$preset) {
            throw new \InvalidArgumentException('Preset theme not found');
        }

        // Generate unique slug
        $slug = 'custom-' . time();

        $data = [
            'slug' => $slug,
            'name' => $customizations['name'] ?? $preset['name'] . ' (Custom)',
            'description' => $customizations['description'] ?? 'Custom theme based on ' . $preset['name'],
            'is_preset' => 0,
            'is_active' => 0,
            'primary_color' => $customizations['primary_color'] ?? $preset['primary_color'],
            'secondary_color' => $customizations['secondary_color'] ?? $preset['secondary_color'],
            'accent_color' => $customizations['accent_color'] ?? $preset['accent_color'],
            'navbar_bg_color' => $customizations['navbar_bg_color'] ?? $preset['navbar_bg_color'] ?? '#FFFFFF',
            'navbar_text_color' => $customizations['navbar_text_color'] ?? $preset['navbar_text_color'] ?? '#1f2937',
            'glow_color' => $customizations['glow_color'] ?? $preset['glow_color'] ?? $preset['primary_color'],
            'heading_font' => $customizations['heading_font'] ?? $preset['heading_font'],
            'body_font' => $customizations['body_font'] ?? $preset['body_font'],
            'layout_style' => $customizations['layout_style'] ?? $preset['layout_style'],
            'header_style' => $customizations['header_style'] ?? $preset['header_style'],
            'category_layout' => $customizations['category_layout'] ?? $preset['category_layout'],
            'product_grid_columns' => $customizations['product_grid_columns'] ?? $preset['product_grid_columns'],
            'custom_css' => $customizations['custom_css'] ?? '',
            'effect_settings' => $customizations['effect_settings'] ?? $preset['effect_settings'] ?? json_encode($this->getDefaultEffectSettings())
        ];

        // Generate color variants
        $data['color_variants'] = json_encode($this->generateColorVariants(
            $data['primary_color'],
            $data['secondary_color'],
            $data['accent_color']
        ));

        return $this->create($data);
    }

    /**
     * Update a custom theme (cannot update presets)
     */
    public function updateCustomTheme(int $id, array $data): bool
    {
        $theme = $this->find($id);
        if (!$theme || $theme['is_preset']) {
            return false;
        }

        // Allowed fields to update
        $allowed = [
            'name', 'description', 'primary_color', 'secondary_color', 'accent_color',
            'navbar_bg_color', 'navbar_text_color', 'glow_color',
            'heading_font', 'body_font', 'layout_style', 'header_style',
            'category_layout', 'product_grid_columns', 'custom_css', 'effect_settings'
        ];

        $updateData = array_intersect_key($data, array_flip($allowed));

        // Regenerate color variants if colors changed
        if (isset($data['primary_color']) || isset($data['secondary_color']) || isset($data['accent_color'])) {
            $updateData['color_variants'] = json_encode($this->generateColorVariants(
                $data['primary_color'] ?? $theme['primary_color'],
                $data['secondary_color'] ?? $theme['secondary_color'],
                $data['accent_color'] ?? $theme['accent_color']
            ));
        }

        $this->update($id, $updateData);

        // Clear cache if this was the active theme
        if ($theme['is_active']) {
            self::$activeTheme = null;
        }

        return true;
    }

    /**
     * Delete a custom theme (cannot delete presets)
     */
    public function deleteCustomTheme(int $id): bool
    {
        $theme = $this->find($id);
        if (!$theme || $theme['is_preset']) {
            return false;
        }

        // If deleting active theme, activate first preset
        if ($theme['is_active']) {
            $preset = $this->queryOne(
                "SELECT id FROM {$this->table} WHERE is_preset = 1 ORDER BY id LIMIT 1"
            );
            if ($preset) {
                $this->activate($preset['id']);
            }
        }

        $this->delete($id);
        return true;
    }

    /**
     * Generate lighter and darker color variants from base colors
     */
    public function generateColorVariants(string $primary, string $secondary, string $accent): array
    {
        return [
            'primary_light' => $this->adjustBrightness($primary, 20),
            'primary_dark' => $this->adjustBrightness($primary, -20),
            'primary_50' => $this->adjustBrightness($primary, 45),
            'secondary_light' => $this->adjustBrightness($secondary, 20),
            'secondary_dark' => $this->adjustBrightness($secondary, -20),
            'accent_light' => $this->adjustBrightness($accent, 10),
            'accent_dark' => $this->adjustBrightness($accent, -30),
        ];
    }

    /**
     * Adjust color brightness by percentage
     */
    public function adjustBrightness(string $hex, int $percent): string
    {
        $hex = ltrim($hex, '#');

        // Handle shorthand hex (e.g., #FFF)
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        // Adjust brightness
        $r = max(0, min(255, $r + ($r * $percent / 100)));
        $g = max(0, min(255, $g + ($g * $percent / 100)));
        $b = max(0, min(255, $b + ($b * $percent / 100)));

        return sprintf('#%02X%02X%02X', (int)$r, (int)$g, (int)$b);
    }

    /**
     * Get available Google Fonts for themes
     */
    public function getAvailableFonts(): array
    {
        return [
            'heading' => [
                'Playfair Display' => 'Playfair Display (Elegant Serif)',
                'Inter' => 'Inter (Modern Sans)',
                'Lora' => 'Lora (Classic Serif)',
                'Montserrat' => 'Montserrat (Clean Sans)',
                'Cormorant Garamond' => 'Cormorant Garamond (Refined Serif)',
                'Poppins' => 'Poppins (Geometric Sans)',
                'Libre Baskerville' => 'Libre Baskerville (Traditional Serif)',
                'Raleway' => 'Raleway (Elegant Sans)',
            ],
            'body' => [
                'Inter' => 'Inter (Highly Readable)',
                'Open Sans' => 'Open Sans (Friendly)',
                'Roboto' => 'Roboto (Neutral)',
                'Lato' => 'Lato (Warm)',
                'Source Sans Pro' => 'Source Sans Pro (Professional)',
                'Nunito' => 'Nunito (Rounded)',
                'Work Sans' => 'Work Sans (Contemporary)',
            ]
        ];
    }

    /**
     * Clear the cached active theme
     */
    public function clearCache(): void
    {
        self::$activeTheme = null;
    }

    /**
     * Get default effect settings
     */
    public function getDefaultEffectSettings(): array
    {
        return [
            'button_glow' => [
                'enabled' => true,
                'intensity' => 'medium'  // subtle, medium, dramatic
            ],
            'hover_animations' => [
                'enabled' => true,
                'speed' => 'normal'  // slow, normal, fast
            ],
            'background_animation' => [
                'enabled' => true,
                'style' => 'circles'  // circles, gradient, geometric, dots, waves, particles
            ],
            'page_transitions' => [
                'enabled' => true,
                'style' => 'fade-up'  // none, fade, fade-up, slide
            ],
            'shimmer_effects' => [
                'enabled' => true
            ],
            'shadow_style' => 'soft',  // none, subtle, soft, dramatic
            'border_radius' => 'rounded',  // sharp, slightly-rounded, rounded, pill
            'card_hover' => [
                'enabled' => true,
                'style' => 'lift'  // none, lift, glow, scale
            ]
        ];
    }

    /**
     * Get available effect options for UI
     */
    public function getEffectOptions(): array
    {
        return [
            'button_glow_intensity' => [
                'subtle' => 'Subtle - Soft glow effect',
                'medium' => 'Medium - Balanced glow',
                'dramatic' => 'Dramatic - Bold glow effect'
            ],
            'hover_speed' => [
                'slow' => 'Slow - Relaxed transitions (0.4s)',
                'normal' => 'Normal - Standard speed (0.2s)',
                'fast' => 'Fast - Quick transitions (0.1s)'
            ],
            'background_style' => [
                'none' => 'None - No background animation',
                'floating-shapes' => 'Floating Shapes - Animated decorative elements',
                'gradient-shift' => 'Gradient Shift - Slow color transitions',
                'particles' => 'Particles - Subtle moving particles'
            ],
            'page_transition_style' => [
                'none' => 'None - No page transitions',
                'fade' => 'Fade - Simple fade in',
                'fade-up' => 'Fade Up - Fade in with upward motion',
                'slide' => 'Slide - Slide in from side'
            ],
            'shadow_style' => [
                'none' => 'None - No shadows',
                'subtle' => 'Subtle - Barely visible shadows',
                'soft' => 'Soft - Gentle drop shadows',
                'dramatic' => 'Dramatic - Bold, prominent shadows'
            ],
            'border_radius' => [
                'sharp' => 'Sharp - No rounding (0px)',
                'slightly-rounded' => 'Slightly Rounded - Subtle curves (4px)',
                'rounded' => 'Rounded - Standard rounding (8px)',
                'pill' => 'Pill - Fully rounded (9999px)'
            ],
            'card_hover_style' => [
                'none' => 'None - No hover effect',
                'lift' => 'Lift - Cards rise on hover',
                'glow' => 'Glow - Glowing border on hover',
                'scale' => 'Scale - Cards enlarge on hover'
            ]
        ];
    }
}
