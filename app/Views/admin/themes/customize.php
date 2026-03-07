<div class="admin-header">
    <div class="header-left">
        <a href="/admin/themes" class="back-link">&larr; Back to Themes</a>
        <h1><?php echo $isPreset ? 'Customize' : 'Edit'; ?>: <?php echo escape($theme['name']); ?></h1>
    </div>
    <div class="header-actions">
        <button type="button" id="preview-btn" class="btn btn-outline" title="Preview theme on site">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            Preview
        </button>
        <button type="button" id="save-btn" class="btn btn-primary">Save</button>
        <button type="button" id="save-close-btn" class="btn btn-secondary">Save &amp; Close</button>
    </div>
</div>

<?php if ($isPreset): ?>
<div class="alert alert-info">
    <strong>Note:</strong> This is a preset theme. Saving will create a custom copy with your changes.
</div>
<?php endif; ?>

<form id="theme-form" class="theme-customize-form">
    <?php echo csrfField(); ?>
    <input type="hidden" name="theme_id" value="<?php echo $theme['id']; ?>">

    <!-- Tab Navigation -->
    <div class="theme-tabs">
        <button type="button" class="theme-tab active" data-tab="general">General</button>
        <button type="button" class="theme-tab" data-tab="logo">Logo &amp; Images</button>
        <button type="button" class="theme-tab" data-tab="colors">Colors</button>
        <button type="button" class="theme-tab" data-tab="layout">Layout</button>
        <button type="button" class="theme-tab" data-tab="typography">Typography</button>
        <button type="button" class="theme-tab" data-tab="effects">Effects</button>
        <button type="button" class="theme-tab" data-tab="holidays">Holidays</button>
        <button type="button" class="theme-tab" data-tab="css">Custom CSS</button>
    </div>

    <div class="theme-tab-content">

        <!-- ═══════════════ GENERAL ═══════════════ -->
        <div class="tab-pane active" id="tab-general">
            <div class="settings-card">
                <h3>Theme Name</h3>
                <div class="form-group">
                    <input type="text" name="name" id="theme-name"
                           value="<?php echo escape($isPreset ? $theme['name'] . ' (Custom)' : $theme['name']); ?>"
                           class="form-control" required placeholder="My Custom Theme">
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="activate" id="activate-theme" checked>
                        Activate theme after saving
                    </label>
                </div>
            </div>
        </div>

        <!-- ═══════════════ LOGO & IMAGES ═══════════════ -->
        <div class="tab-pane" id="tab-logo">
            <div class="settings-card">
                <h3>Theme Logo</h3>
                <p class="section-help">Upload a custom logo for this theme. If none is set, the store logo from Site Settings will be used.</p>

                <div class="theme-image-upload" id="themeLogoUpload">
                    <div class="image-preview-box" id="themeLogoPreview">
                        <?php if (!empty($theme['theme_logo'])): ?>
                            <img src="<?php echo escape($theme['theme_logo']); ?>" alt="Theme Logo" style="max-height:80px;">
                        <?php else: ?>
                            <span class="placeholder-text">Using store default logo</span>
                        <?php endif; ?>
                    </div>
                    <div class="upload-actions">
                        <label class="btn btn-secondary btn-sm">
                            Upload Logo
                            <input type="file" id="themeLogoFile" accept="image/png,image/jpeg,image/svg+xml,image/webp,image/gif" style="display:none;">
                        </label>
                        <?php if (!empty($theme['theme_logo'])): ?>
                        <button type="button" class="btn btn-danger btn-sm" id="removeThemeLogo">Remove</button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-row" style="margin-top:16px;">
                    <div class="form-group" style="max-width:200px;">
                        <label for="themeLogoHeight">Logo Height (px)</label>
                        <input type="number" id="themeLogoHeight" name="logo_height" class="form-control" value="<?php echo escape($theme['logo_height'] ?? ''); ?>" placeholder="52" min="20" max="500" step="1">
                        <span class="form-help">Leave empty for default (52px)</span>
                    </div>
                </div>

                <div class="form-row" style="display:flex;gap:16px;margin-top:12px;flex-wrap:wrap;">
                    <div class="form-group" style="flex:1;min-width:200px;max-width:300px;">
                        <label for="logo_text_font">Logo Text Font</label>
                        <select name="logo_text_font" id="logo_text_font" class="form-control">
                            <option value="">Use heading font</option>
                            <?php
                            $__currentLogoFont = $theme['logo_text_font'] ?? '';
                            foreach ($fonts['heading'] as $fontKey => $fontLabel): ?>
                                <option value="<?php echo escape($fontKey); ?>"<?php echo $__currentLogoFont === $fontKey ? ' selected' : ''; ?>><?php echo escape($fontLabel); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="form-help">Theme default (Store Settings can override)</span>
                    </div>

                    <div class="form-group" style="max-width:120px;">
                        <label for="logo_text_size">Size (px)</label>
                        <input type="number" name="logo_text_size" id="logo_text_size" class="form-control" value="<?php echo escape($theme['logo_text_size'] ?? ''); ?>" placeholder="28" min="12" max="120" step="1">
                    </div>

                    <div class="form-group" style="max-width:140px;">
                        <label for="logo_text_weight">Weight</label>
                        <?php $__currentWeight = $theme['logo_text_weight'] ?? ''; ?>
                        <select name="logo_text_weight" id="logo_text_weight" class="form-control">
                            <option value=""<?php echo $__currentWeight === '' ? ' selected' : ''; ?>>Default</option>
                            <option value="300"<?php echo $__currentWeight === '300' ? ' selected' : ''; ?>>Light</option>
                            <option value="400"<?php echo $__currentWeight === '400' ? ' selected' : ''; ?>>Regular</option>
                            <option value="500"<?php echo $__currentWeight === '500' ? ' selected' : ''; ?>>Medium</option>
                            <option value="600"<?php echo $__currentWeight === '600' ? ' selected' : ''; ?>>Semi-Bold</option>
                            <option value="700"<?php echo $__currentWeight === '700' ? ' selected' : ''; ?>>Bold</option>
                            <option value="800"<?php echo $__currentWeight === '800' ? ' selected' : ''; ?>>Extra Bold</option>
                            <option value="900"<?php echo $__currentWeight === '900' ? ' selected' : ''; ?>>Black</option>
                        </select>
                    </div>

                    <div class="form-group" style="max-width:160px;">
                        <label for="logo_text_stretch">Stretch</label>
                        <?php $__currentStretch = $theme['logo_text_stretch'] ?? ''; ?>
                        <select name="logo_text_stretch" id="logo_text_stretch" class="form-control">
                            <option value=""<?php echo $__currentStretch === '' ? ' selected' : ''; ?>>Default</option>
                            <option value="ultra-condensed"<?php echo $__currentStretch === 'ultra-condensed' ? ' selected' : ''; ?>>Ultra Condensed</option>
                            <option value="condensed"<?php echo $__currentStretch === 'condensed' ? ' selected' : ''; ?>>Condensed</option>
                            <option value="semi-condensed"<?php echo $__currentStretch === 'semi-condensed' ? ' selected' : ''; ?>>Semi Condensed</option>
                            <option value="normal"<?php echo $__currentStretch === 'normal' ? ' selected' : ''; ?>>Normal</option>
                            <option value="semi-expanded"<?php echo $__currentStretch === 'semi-expanded' ? ' selected' : ''; ?>>Semi Expanded</option>
                            <option value="expanded"<?php echo $__currentStretch === 'expanded' ? ' selected' : ''; ?>>Expanded</option>
                        </select>
                    </div>

                    <div class="form-group" style="max-width:140px;">
                        <label for="logo_text_spacing">Letter Spacing</label>
                        <?php $__currentSpacing = $theme['logo_text_spacing'] ?? ''; ?>
                        <select name="logo_text_spacing" id="logo_text_spacing" class="form-control">
                            <option value=""<?php echo $__currentSpacing === '' ? ' selected' : ''; ?>>Default</option>
                            <option value="0"<?php echo $__currentSpacing === '0' ? ' selected' : ''; ?>>None</option>
                            <option value="0.05em"<?php echo $__currentSpacing === '0.05em' ? ' selected' : ''; ?>>Tight</option>
                            <option value="0.1em"<?php echo $__currentSpacing === '0.1em' ? ' selected' : ''; ?>>Normal</option>
                            <option value="0.2em"<?php echo $__currentSpacing === '0.2em' ? ' selected' : ''; ?>>Wide</option>
                            <option value="0.3em"<?php echo $__currentSpacing === '0.3em' ? ' selected' : ''; ?>>Extra Wide</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="settings-card">
                <h3>Hero Background Image</h3>
                <p class="section-help">Upload a background image for the hero section. If none is set, the theme's gradient colors will be used.</p>

                <div class="theme-image-upload" id="heroImageUpload">
                    <div class="image-preview-box image-preview-wide" id="heroImagePreview">
                        <?php if (!empty($theme['hero_image'])): ?>
                            <img src="<?php echo escape($theme['hero_image']); ?>" alt="Hero Image" style="max-height:120px;width:100%;object-fit:cover;">
                        <?php else: ?>
                            <span class="placeholder-text">Using default gradient</span>
                        <?php endif; ?>
                    </div>
                    <div class="upload-actions">
                        <label class="btn btn-secondary btn-sm">
                            Upload Image
                            <input type="file" id="heroImageFile" accept="image/png,image/jpeg,image/webp" style="display:none;">
                        </label>
                        <?php if (!empty($theme['hero_image'])): ?>
                        <button type="button" class="btn btn-danger btn-sm" id="removeHeroImage">Remove</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════ COLORS ═══════════════ -->
        <div class="tab-pane" id="tab-colors">
            <div class="settings-card">
                <h3>Brand Colors</h3>
                <p class="section-help">Choose your primary colors. Shades will be generated automatically.</p>

                <div class="color-grid">
                    <div class="color-picker-group">
                        <label>Primary Color</label>
                        <div class="color-input-wrapper">
                            <input type="color" name="primary_color" id="primary-color"
                                   value="<?php echo escape($theme['primary_color']); ?>"
                                   class="color-picker">
                            <input type="text" id="primary-color-hex"
                                   value="<?php echo escape($theme['primary_color']); ?>"
                                   class="color-hex-input" pattern="^#[0-9A-Fa-f]{6}$">
                        </div>
                        <span class="color-description">Main brand color - buttons, links, accents</span>
                    </div>

                    <div class="color-picker-group">
                        <label>Secondary Color</label>
                        <div class="color-input-wrapper">
                            <input type="color" name="secondary_color" id="secondary-color"
                                   value="<?php echo escape($theme['secondary_color']); ?>"
                                   class="color-picker">
                            <input type="text" id="secondary-color-hex"
                                   value="<?php echo escape($theme['secondary_color']); ?>"
                                   class="color-hex-input" pattern="^#[0-9A-Fa-f]{6}$">
                        </div>
                        <span class="color-description">Hover states, gradients, highlights</span>
                    </div>

                    <div class="color-picker-group">
                        <label>Accent Color</label>
                        <div class="color-input-wrapper">
                            <input type="color" name="accent_color" id="accent-color"
                                   value="<?php echo escape($theme['accent_color']); ?>"
                                   class="color-picker">
                            <input type="text" id="accent-color-hex"
                                   value="<?php echo escape($theme['accent_color']); ?>"
                                   class="color-hex-input" pattern="^#[0-9A-Fa-f]{6}$">
                        </div>
                        <span class="color-description">Subtle backgrounds, borders, light fills</span>
                    </div>

                    <div class="color-picker-group">
                        <label>Glow Color</label>
                        <div class="color-input-wrapper">
                            <input type="color" name="glow_color" id="glow-color"
                                   value="<?php echo escape($theme['glow_color'] ?? $theme['primary_color']); ?>"
                                   class="color-picker">
                            <input type="text" id="glow-color-hex"
                                   value="<?php echo escape($theme['glow_color'] ?? $theme['primary_color']); ?>"
                                   class="color-hex-input" pattern="^#[0-9A-Fa-f]{6}$">
                        </div>
                        <span class="color-description">Color for button glows, shadows, and hover effects</span>
                    </div>
                </div>
            </div>

            <div class="settings-card">
                <h3>Hero Section Colors</h3>
                <p class="section-help">Gradient colors for the hero banner background. Does not affect holiday themes.</p>

                <div class="color-grid">
                    <div class="color-picker-group">
                        <label>Gradient Start</label>
                        <div class="color-input-wrapper">
                            <input type="color" name="hero_bg_start" id="hero-bg-start"
                                   value="<?php echo escape($theme['hero_bg_start'] ?? '#0d0d1a'); ?>"
                                   class="color-picker">
                            <input type="text" id="hero-bg-start-hex"
                                   value="<?php echo escape($theme['hero_bg_start'] ?? '#0d0d1a'); ?>"
                                   class="color-hex-input" pattern="^#[0-9A-Fa-f]{6}$">
                        </div>
                        <span class="color-description">Dark outer edges of the hero gradient</span>
                    </div>

                    <div class="color-picker-group">
                        <label>Gradient End</label>
                        <div class="color-input-wrapper">
                            <input type="color" name="hero_bg_end" id="hero-bg-end"
                                   value="<?php echo escape($theme['hero_bg_end'] ?? '#1a1a2e'); ?>"
                                   class="color-picker">
                            <input type="text" id="hero-bg-end-hex"
                                   value="<?php echo escape($theme['hero_bg_end'] ?? '#1a1a2e'); ?>"
                                   class="color-hex-input" pattern="^#[0-9A-Fa-f]{6}$">
                        </div>
                        <span class="color-description">Inner color of the hero gradient</span>
                    </div>
                </div>
            </div>

            <div class="settings-card">
                <h3>Navigation Bar</h3>
                <p class="section-help">Customize the top menu bar appearance.</p>

                <div class="color-grid">
                    <div class="color-picker-group">
                        <label>Navbar Background</label>
                        <div class="color-input-wrapper">
                            <input type="color" name="navbar_bg_color" id="navbar-bg-color"
                                   value="<?php echo escape($theme['navbar_bg_color'] ?? '#FFFFFF'); ?>"
                                   class="color-picker">
                            <input type="text" id="navbar-bg-color-hex"
                                   value="<?php echo escape($theme['navbar_bg_color'] ?? '#FFFFFF'); ?>"
                                   class="color-hex-input" pattern="^#[0-9A-Fa-f]{6}$">
                        </div>
                        <span class="color-description">Background color of the navigation bar</span>
                    </div>

                    <div class="color-picker-group">
                        <label>Navbar Text</label>
                        <div class="color-input-wrapper">
                            <input type="color" name="navbar_text_color" id="navbar-text-color"
                                   value="<?php echo escape($theme['navbar_text_color'] ?? '#1f2937'); ?>"
                                   class="color-picker">
                            <input type="text" id="navbar-text-color-hex"
                                   value="<?php echo escape($theme['navbar_text_color'] ?? '#1f2937'); ?>"
                                   class="color-hex-input" pattern="^#[0-9A-Fa-f]{6}$">
                        </div>
                        <span class="color-description">Text and link color in the navigation</span>
                    </div>
                </div>

                <!-- Navbar Background Image -->
                <div class="theme-image-upload" id="navbarBgImageUpload" style="margin-top:1rem;">
                    <label style="font-weight:600;margin-bottom:0.5rem;display:block;">Background Image</label>
                    <p class="section-help" style="margin-top:0;">Optional background image for the navigation bar.</p>
                    <div class="image-preview-box image-preview-wide" id="navbarBgImagePreview">
                        <?php if (!empty($theme['navbar_bg_image'])): ?>
                            <img src="<?php echo escape($theme['navbar_bg_image']); ?>" alt="Navbar Background" style="max-height:60px;width:100%;object-fit:cover;">
                        <?php else: ?>
                            <span class="placeholder-text">No background image</span>
                        <?php endif; ?>
                    </div>
                    <div class="upload-actions">
                        <label class="btn btn-secondary btn-sm">
                            Upload Image
                            <input type="file" id="navbarBgImageFile" accept="image/png,image/jpeg,image/webp" style="display:none;">
                        </label>
                        <?php if (!empty($theme['navbar_bg_image'])): ?>
                        <button type="button" class="btn btn-danger btn-sm" id="removeNavbarBgImage">Remove</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="settings-card">
                <h3>Footer</h3>
                <p class="section-help">Customize the footer appearance.</p>

                <div class="color-grid">
                    <div class="color-picker-group">
                        <label>Footer Background</label>
                        <div class="color-input-wrapper">
                            <input type="color" name="footer_bg_color" id="footer-bg-color"
                                   value="<?php echo escape($theme['footer_bg_color'] ?? '#12121f'); ?>"
                                   class="color-picker">
                            <input type="text" id="footer-bg-color-hex"
                                   value="<?php echo escape($theme['footer_bg_color'] ?? '#12121f'); ?>"
                                   class="color-hex-input" pattern="^#[0-9A-Fa-f]{6}$">
                        </div>
                        <span class="color-description">Background color of the footer</span>
                    </div>

                    <div class="color-picker-group">
                        <label>Footer Text</label>
                        <div class="color-input-wrapper">
                            <input type="color" name="footer_text_color" id="footer-text-color"
                                   value="<?php echo escape($theme['footer_text_color'] ?? '#FFFFFF'); ?>"
                                   class="color-picker">
                            <input type="text" id="footer-text-color-hex"
                                   value="<?php echo escape($theme['footer_text_color'] ?? '#FFFFFF'); ?>"
                                   class="color-hex-input" pattern="^#[0-9A-Fa-f]{6}$">
                        </div>
                        <span class="color-description">Text and link color in the footer</span>
                    </div>
                </div>

                <!-- Footer Background Image -->
                <div class="theme-image-upload" id="footerBgImageUpload" style="margin-top:1rem;">
                    <label style="font-weight:600;margin-bottom:0.5rem;display:block;">Background Image</label>
                    <p class="section-help" style="margin-top:0;">Optional background image for the footer area.</p>
                    <div class="image-preview-box image-preview-wide" id="footerBgImagePreview">
                        <?php if (!empty($theme['footer_bg_image'])): ?>
                            <img src="<?php echo escape($theme['footer_bg_image']); ?>" alt="Footer Background" style="max-height:60px;width:100%;object-fit:cover;">
                        <?php else: ?>
                            <span class="placeholder-text">No background image</span>
                        <?php endif; ?>
                    </div>
                    <div class="upload-actions">
                        <label class="btn btn-secondary btn-sm">
                            Upload Image
                            <input type="file" id="footerBgImageFile" accept="image/png,image/jpeg,image/webp" style="display:none;">
                        </label>
                        <?php if (!empty($theme['footer_bg_image'])): ?>
                        <button type="button" class="btn btn-danger btn-sm" id="removeFooterBgImage">Remove</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════ LAYOUT ═══════════════ -->
        <div class="tab-pane" id="tab-layout">
            <?php
            $effectSettings = $theme['effect_settings'] ?? null;
            if (is_string($effectSettings)) {
                $effectSettings = json_decode($effectSettings, true);
            }
            if (!is_array($effectSettings)) {
                $effectSettings = [
                    'button_glow' => ['enabled' => true, 'intensity' => 'medium'],
                    'hover_animations' => ['enabled' => true, 'speed' => 'normal'],
                    'background_animation' => ['enabled' => true, 'style' => 'floating-shapes'],
                    'page_transitions' => ['enabled' => true, 'style' => 'fade-up'],
                    'shimmer_effects' => ['enabled' => true],
                    'shadow_style' => 'soft',
                    'border_radius' => 'rounded',
                    'card_hover' => ['enabled' => true, 'style' => 'lift'],
                    'holiday_effects' => ['enabled' => false, 'preview' => 'none']
                ];
            }
            ?>

            <div class="settings-card">
                <h3>Style</h3>
                <div class="form-row-2col">
                    <div class="form-group">
                        <label for="effect-shadow-style">Shadow Style</label>
                        <select name="effect_shadow_style" id="effect-shadow-style" class="form-control">
                            <option value="none" <?php echo ($effectSettings['shadow_style'] ?? '') === 'none' ? 'selected' : ''; ?>>None - No shadows</option>
                            <option value="subtle" <?php echo ($effectSettings['shadow_style'] ?? '') === 'subtle' ? 'selected' : ''; ?>>Subtle - Light shadows</option>
                            <option value="soft" <?php echo ($effectSettings['shadow_style'] ?? 'soft') === 'soft' ? 'selected' : ''; ?>>Soft - Gentle shadows</option>
                            <option value="dramatic" <?php echo ($effectSettings['shadow_style'] ?? '') === 'dramatic' ? 'selected' : ''; ?>>Dramatic - Bold shadows</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="effect-border-radius">Border Radius</label>
                        <select name="effect_border_radius" id="effect-border-radius" class="form-control">
                            <option value="sharp" <?php echo ($effectSettings['border_radius'] ?? '') === 'sharp' ? 'selected' : ''; ?>>Sharp - Square corners</option>
                            <option value="slightly-rounded" <?php echo ($effectSettings['border_radius'] ?? '') === 'slightly-rounded' ? 'selected' : ''; ?>>Slightly Rounded - Subtle curves</option>
                            <option value="rounded" <?php echo ($effectSettings['border_radius'] ?? 'rounded') === 'rounded' ? 'selected' : ''; ?>>Rounded - Standard curves</option>
                            <option value="pill" <?php echo ($effectSettings['border_radius'] ?? '') === 'pill' ? 'selected' : ''; ?>>Pill - Fully rounded</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="settings-card">
                <h3>Page Layout</h3>
                <div class="form-row-2col">
                    <div class="form-group">
                        <label for="layout-style">Layout</label>
                        <select name="layout_style" id="layout-style" class="form-control">
                            <option value="sidebar" <?php echo ($theme['layout_style'] == 'sidebar') ? 'selected' : ''; ?>>Sidebar - Category filter on left</option>
                            <option value="full-width" <?php echo ($theme['layout_style'] == 'full-width') ? 'selected' : ''; ?>>Full Width - Maximum content space</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="header-style">Header Style</label>
                        <select name="header_style" id="header-style" class="form-control">
                            <option value="standard" <?php echo ($theme['header_style'] == 'standard') ? 'selected' : ''; ?>>Standard - Logo left, nav right</option>
                            <option value="centered" <?php echo ($theme['header_style'] == 'centered') ? 'selected' : ''; ?>>Centered - Logo center, nav below</option>
                            <option value="minimal" <?php echo ($theme['header_style'] == 'minimal') ? 'selected' : ''; ?>>Minimal - Compact, clean</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="category-layout">Product Listing</label>
                        <select name="category_layout" id="category-layout" class="form-control">
                            <option value="grid" <?php echo ($theme['category_layout'] == 'grid') ? 'selected' : ''; ?>>Grid - Uniform cards</option>
                            <option value="list" <?php echo ($theme['category_layout'] == 'list') ? 'selected' : ''; ?>>List - Horizontal rows</option>
                            <option value="masonry" <?php echo ($theme['category_layout'] == 'masonry') ? 'selected' : ''; ?>>Masonry - Pinterest-style</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="product-grid-columns">Grid Columns</label>
                        <select name="product_grid_columns" id="product-grid-columns" class="form-control">
                            <option value="3" <?php echo ($theme['product_grid_columns'] == 3) ? 'selected' : ''; ?>>3 Columns</option>
                            <option value="4" <?php echo ($theme['product_grid_columns'] == 4) ? 'selected' : ''; ?>>4 Columns</option>
                            <option value="5" <?php echo ($theme['product_grid_columns'] == 5) ? 'selected' : ''; ?>>5 Columns</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="sidebar-menu-mode">Sidebar Menu</label>
                        <select name="sidebar_menu_mode" id="sidebar-menu-mode" class="form-control">
                            <option value="click" <?php echo ($theme["sidebar_menu_mode"] ?? "hover") == "click" ? "selected" : ""; ?>>Click - Tap to expand</option>
                            <option value="hover" <?php echo ($theme["sidebar_menu_mode"] ?? "hover") == "hover" ? "selected" : ""; ?>>Hover - Expand on hover</option>
                            <option value="expanded" <?php echo ($theme["sidebar_menu_mode"] ?? "hover") == "expanded" ? "selected" : ""; ?>>Expanded - Always visible</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════ TYPOGRAPHY ═══════════════ -->
        <div class="tab-pane" id="tab-typography">
            <div class="settings-card">
                <h3>Fonts</h3>
                <div class="form-row-2col">
                    <div class="form-group">
                        <label for="heading-font">Heading Font</label>
                        <select name="heading_font" id="heading-font" class="form-control">
                            <?php foreach ($fonts['heading'] as $font => $label): ?>
                                <option value="<?php echo escape($font); ?>"
                                    <?php echo ($theme['heading_font'] == $font) ? 'selected' : ''; ?>>
                                    <?php echo escape($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="body-font">Body Font</label>
                        <select name="body_font" id="body-font" class="form-control">
                            <?php foreach ($fonts['body'] as $font => $label): ?>
                                <option value="<?php echo escape($font); ?>"
                                    <?php echo ($theme['body_font'] == $font) ? 'selected' : ''; ?>>
                                    <?php echo escape($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════ EFFECTS ═══════════════ -->
        <div class="tab-pane" id="tab-effects">
            <div class="settings-card">
                <h3>Animations &amp; Effects</h3>

                <div class="effect-group">
                    <div class="effect-toggle">
                        <label class="toggle-label">
                            <input type="checkbox" name="effect_button_glow_enabled" id="effect-button-glow"
                                   <?php echo !empty($effectSettings['button_glow']['enabled']) ? 'checked' : ''; ?>>
                            <span class="toggle-text">Button Glow</span>
                        </label>
                        <select name="effect_button_glow_intensity" class="form-control effect-option" id="effect-button-glow-intensity">
                            <option value="subtle" <?php echo ($effectSettings['button_glow']['intensity'] ?? '') === 'subtle' ? 'selected' : ''; ?>>Subtle</option>
                            <option value="medium" <?php echo ($effectSettings['button_glow']['intensity'] ?? 'medium') === 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="dramatic" <?php echo ($effectSettings['button_glow']['intensity'] ?? '') === 'dramatic' ? 'selected' : ''; ?>>Dramatic</option>
                        </select>
                    </div>
                    <span class="effect-description">Glowing effect on buttons</span>
                </div>

                <div class="effect-group">
                    <div class="effect-toggle">
                        <label class="toggle-label">
                            <input type="checkbox" name="effect_hover_enabled" id="effect-hover"
                                   <?php echo !empty($effectSettings['hover_animations']['enabled']) ? 'checked' : ''; ?>>
                            <span class="toggle-text">Hover Animations</span>
                        </label>
                        <select name="effect_hover_speed" class="form-control effect-option" id="effect-hover-speed">
                            <option value="slow" <?php echo ($effectSettings['hover_animations']['speed'] ?? '') === 'slow' ? 'selected' : ''; ?>>Slow</option>
                            <option value="normal" <?php echo ($effectSettings['hover_animations']['speed'] ?? 'normal') === 'normal' ? 'selected' : ''; ?>>Normal</option>
                            <option value="fast" <?php echo ($effectSettings['hover_animations']['speed'] ?? '') === 'fast' ? 'selected' : ''; ?>>Fast</option>
                        </select>
                    </div>
                    <span class="effect-description">Smooth transitions on hover</span>
                </div>

                <div class="effect-group">
                    <div class="effect-toggle">
                        <label class="toggle-label">
                            <input type="checkbox" name="effect_background_enabled" id="effect-background"
                                   <?php echo !empty($effectSettings['background_animation']['enabled']) ? 'checked' : ''; ?>>
                            <span class="toggle-text">Page Background Pattern</span>
                        </label>
                        <select name="effect_background_style" class="form-control effect-option" id="effect-background-style">
                            <option value="circles" <?php echo ($effectSettings['background_animation']['style'] ?? 'circles') === 'circles' ? 'selected' : ''; ?>>Floating Circles</option>
                            <option value="gradient" <?php echo ($effectSettings['background_animation']['style'] ?? '') === 'gradient' ? 'selected' : ''; ?>>Gradient Orbs</option>
                            <option value="geometric" <?php echo ($effectSettings['background_animation']['style'] ?? '') === 'geometric' ? 'selected' : ''; ?>>Geometric</option>
                            <option value="dots" <?php echo ($effectSettings['background_animation']['style'] ?? '') === 'dots' ? 'selected' : ''; ?>>Dots Grid</option>
                            <option value="waves" <?php echo ($effectSettings['background_animation']['style'] ?? '') === 'waves' ? 'selected' : ''; ?>>Waves</option>
                            <option value="particles" <?php echo ($effectSettings['background_animation']['style'] ?? '') === 'particles' ? 'selected' : ''; ?>>Particles</option>
                        </select>
                    </div>
                    <span class="effect-description">Subtle CSS pattern behind page content</span>
                    <div style="margin-top: 10px;">
                        <label for="effect-background-opacity" style="font-size: 0.85rem; color: var(--admin-text-light);">
                            Opacity: <span id="opacity-value"><?php echo ($effectSettings['background_animation']['opacity'] ?? 0.5); ?></span>
                        </label>
                        <input type="range" name="effect_background_opacity" id="effect-background-opacity"
                               min="0.05" max="1" step="0.05"
                               value="<?php echo ($effectSettings['background_animation']['opacity'] ?? 0.5); ?>"
                               class="form-control" style="width: 100%;">
                    </div>
                </div>

                <div class="effect-group">
                    <div class="effect-toggle">
                        <label class="toggle-label">
                            <input type="checkbox" name="effect_header_enabled" id="effect-header"
                                   <?php echo !empty($effectSettings['header_effect']['enabled']) ? 'checked' : ''; ?>>
                            <span class="toggle-text">Canvas Effect</span>
                        </label>
                        <select name="effect_header_style" class="form-control effect-option" id="effect-header-style">
                            <option value="swirl" <?php echo ($effectSettings['header_effect']['style'] ?? 'swirl') === 'swirl' ? 'selected' : ''; ?>>Swirl</option>
                            <option value="stars" <?php echo ($effectSettings['header_effect']['style'] ?? '') === 'stars' ? 'selected' : ''; ?>>Fairy Lights</option>
                            <option value="aurora" <?php echo ($effectSettings['header_effect']['style'] ?? '') === 'aurora' ? 'selected' : ''; ?>>Aurora</option>
                            <option value="coalesce" <?php echo ($effectSettings['header_effect']['style'] ?? '') === 'coalesce' ? 'selected' : ''; ?>>Coalesce</option>
                            <option value="fireflies" <?php echo ($effectSettings['header_effect']['style'] ?? '') === 'fireflies' ? 'selected' : ''; ?>>Fireflies</option>
                            <option value="constellation" <?php echo ($effectSettings['header_effect']['style'] ?? '') === 'constellation' ? 'selected' : ''; ?>>Constellation</option>
                            <option value="matrix" <?php echo ($effectSettings['header_effect']['style'] ?? '') === 'matrix' ? 'selected' : ''; ?>>Matrix Rain</option>
                            <option value="bokeh" <?php echo ($effectSettings['header_effect']['style'] ?? '') === 'bokeh' ? 'selected' : ''; ?>>Bokeh</option>
                            <option value="wavemesh" <?php echo ($effectSettings['header_effect']['style'] ?? '') === 'wavemesh' ? 'selected' : ''; ?>>Wave Mesh</option>
                            <option value="fireworks" <?php echo ($effectSettings['header_effect']['style'] ?? '') === 'fireworks' ? 'selected' : ''; ?>>Fireworks</option>
                            <option value="snowfall" <?php echo ($effectSettings['header_effect']['style'] ?? '') === 'snowfall' ? 'selected' : ''; ?>>Snowfall</option>
                            <option value="haunted" <?php echo ($effectSettings['header_effect']['style'] ?? '') === 'haunted' ? 'selected' : ''; ?>>Haunted</option>
                        </select>
                    </div>
                    <span class="effect-description">Animated canvas overlay on your navbar or hero section</span>
                    <div style="margin-top: 10px; display: flex; gap: 16px;">
                        <div style="flex:1;">
                            <label for="effect-header-location" style="font-size: 0.85rem; color: var(--admin-text-light);">Location</label>
                            <select name="effect_header_location" class="form-control" id="effect-header-location" style="margin-top: 4px;">
                                <option value="navbar" <?php echo ($effectSettings['header_effect']['location'] ?? 'navbar') === 'navbar' ? 'selected' : ''; ?>>Navbar</option>
                                <option value="hero" <?php echo ($effectSettings['header_effect']['location'] ?? '') === 'hero' ? 'selected' : ''; ?>>Hero Section</option>
                            </select>
                        </div>
                        <div style="flex:1;">
                            <label for="effect-header-opacity" style="font-size: 0.85rem; color: var(--admin-text-light);">
                                Opacity: <span id="header-opacity-value"><?php echo ($effectSettings['header_effect']['opacity'] ?? 0.5); ?></span>
                            </label>
                            <input type="range" name="effect_header_opacity" id="effect-header-opacity"
                                   min="0.05" max="1" step="0.05"
                                   value="<?php echo ($effectSettings['header_effect']['opacity'] ?? 0.5); ?>"
                                   class="form-control" style="width: 100%; margin-top: 4px;">
                        </div>
                    </div>
                </div>

                <div class="effect-group">
                    <div class="effect-toggle">
                        <label class="toggle-label">
                            <input type="checkbox" name="effect_page_transitions_enabled" id="effect-page-transitions"
                                   <?php echo !empty($effectSettings['page_transitions']['enabled']) ? 'checked' : ''; ?>>
                            <span class="toggle-text">Page Transitions</span>
                        </label>
                        <select name="effect_page_transitions_style" class="form-control effect-option" id="effect-page-transitions-style">
                            <option value="fade-up" <?php echo ($effectSettings['page_transitions']['style'] ?? 'fade-up') === 'fade-up' ? 'selected' : ''; ?>>Fade Up</option>
                            <option value="fade" <?php echo ($effectSettings['page_transitions']['style'] ?? '') === 'fade' ? 'selected' : ''; ?>>Fade</option>
                            <option value="slide" <?php echo ($effectSettings['page_transitions']['style'] ?? '') === 'slide' ? 'selected' : ''; ?>>Slide</option>
                            <option value="none" <?php echo ($effectSettings['page_transitions']['style'] ?? '') === 'none' ? 'selected' : ''; ?>>None</option>
                        </select>
                    </div>
                    <span class="effect-description">Content fade-in animations</span>
                </div>

                <div class="effect-group">
                    <div class="effect-toggle">
                        <label class="toggle-label">
                            <input type="checkbox" name="effect_shimmer_enabled" id="effect-shimmer"
                                   <?php echo !empty($effectSettings['shimmer_effects']['enabled']) ? 'checked' : ''; ?>>
                            <span class="toggle-text">Shimmer Effects</span>
                        </label>
                    </div>
                    <span class="effect-description">Subtle shine effects on cards</span>
                </div>

                <div class="effect-group">
                    <div class="effect-toggle">
                        <label class="toggle-label">
                            <input type="checkbox" name="effect_card_hover_enabled" id="effect-card-hover"
                                   <?php echo !empty($effectSettings['card_hover']['enabled']) ? 'checked' : ''; ?>>
                            <span class="toggle-text">Card Hover Effect</span>
                        </label>
                        <select name="effect_card_hover_style" class="form-control effect-option" id="effect-card-hover-style">
                            <option value="lift" <?php echo ($effectSettings['card_hover']['style'] ?? 'lift') === 'lift' ? 'selected' : ''; ?>>Lift</option>
                            <option value="glow" <?php echo ($effectSettings['card_hover']['style'] ?? '') === 'glow' ? 'selected' : ''; ?>>Glow</option>
                            <option value="scale" <?php echo ($effectSettings['card_hover']['style'] ?? '') === 'scale' ? 'selected' : ''; ?>>Scale</option>
                            <option value="none" <?php echo ($effectSettings['card_hover']['style'] ?? '') === 'none' ? 'selected' : ''; ?>>None</option>
                        </select>
                    </div>
                    <span class="effect-description">Product card hover behavior</span>
                </div>
            </div>
        </div>

        <!-- ═══════════════ HOLIDAYS ═══════════════ -->
        <div class="tab-pane" id="tab-holidays">
            <div class="settings-card">
                <h3>Holiday Effects</h3>
                <p class="section-help">Seasonal animated particles in the hero section. Effects auto-activate 12 days before each holiday and stop at midnight after.</p>

                <div class="effect-group">
                    <div class="effect-toggle">
                        <label class="toggle-label">
                            <input type="checkbox" name="effect_holiday_enabled" id="effect-holiday"
                                   <?php echo !empty($effectSettings['holiday_effects']['enabled']) ? 'checked' : ''; ?>>
                            <span class="toggle-text">Enable Holiday Effects</span>
                        </label>
                    </div>
                    <span class="effect-description">When enabled, seasonal particles appear automatically during holiday windows</span>
                </div>

                <?php $currentPreview = $effectSettings['holiday_effects']['preview'] ?? 'none'; ?>

                <div class="holiday-preview-section">
                    <label style="display: block; font-weight: 500; margin-bottom: 10px; font-size: 0.9rem;">Preview Holiday</label>
                    <p class="section-help" style="margin-bottom: 12px;">Select a holiday to preview its effects on the live site right now. Set to "None" for normal schedule-based behavior.</p>

                    <div class="holiday-radio-group">
                        <label class="holiday-radio">
                            <input type="radio" name="holiday_preview_holiday" value="none"
                                   <?php echo $currentPreview === 'none' ? 'checked' : ''; ?>>
                            <span class="holiday-radio-label">None <span class="holiday-radio-hint">(auto schedule)</span></span>
                        </label>
                        <label class="holiday-radio">
                            <input type="radio" name="holiday_preview_holiday" value="christmas"
                                   <?php echo $currentPreview === 'christmas' ? 'checked' : ''; ?>>
                            <span class="holiday-radio-label">Christmas <span class="holiday-radio-hint">Dec 13 - Dec 25</span></span>
                        </label>
                        <label class="holiday-radio">
                            <input type="radio" name="holiday_preview_holiday" value="valentines"
                                   <?php echo $currentPreview === 'valentines' ? 'checked' : ''; ?>>
                            <span class="holiday-radio-label">Valentine's Day <span class="holiday-radio-hint">Feb 2 - Feb 14</span></span>
                        </label>
                        <label class="holiday-radio">
                            <input type="radio" name="holiday_preview_holiday" value="stpatricks"
                                   <?php echo $currentPreview === 'stpatricks' ? 'checked' : ''; ?>>
                            <span class="holiday-radio-label">St. Patrick's Day <span class="holiday-radio-hint">Mar 5 - Mar 17</span></span>
                        </label>
                        <label class="holiday-radio">
                            <input type="radio" name="holiday_preview_holiday" value="easter"
                                   <?php echo $currentPreview === 'easter' ? 'checked' : ''; ?>>
                            <span class="holiday-radio-label">Easter <span class="holiday-radio-hint">12 days before - Easter</span></span>
                        </label>
                        <label class="holiday-radio">
                            <input type="radio" name="holiday_preview_holiday" value="independence"
                                   <?php echo $currentPreview === 'independence' ? 'checked' : ''; ?>>
                            <span class="holiday-radio-label">Independence Day <span class="holiday-radio-hint">Jun 22 - Jul 4</span></span>
                        </label>
                        <label class="holiday-radio">
                            <input type="radio" name="holiday_preview_holiday" value="halloween"
                                   <?php echo $currentPreview === 'halloween' ? 'checked' : ''; ?>>
                            <span class="holiday-radio-label">Halloween <span class="holiday-radio-hint">Oct 19 - Oct 31</span></span>
                        </label>
                        <label class="holiday-radio">
                            <input type="radio" name="holiday_preview_holiday" value="newyear"
                                   <?php echo $currentPreview === 'newyear' ? 'checked' : ''; ?>>
                            <span class="holiday-radio-label">New Year's <span class="holiday-radio-hint">Dec 20 - Jan 1</span></span>
                        </label>
                    </div>

                    <a href="/" target="_blank" class="btn-preview-holiday" id="preview-holiday-btn">
                        View on Site &rarr;
                    </a>
                </div>

                <?php
                $holidayHeroes = $effectSettings['holiday_effects']['heroes'] ?? [];
                $holidayList = [
                    'christmas' => 'Christmas',
                    'valentines' => "Valentine's Day",
                    'stpatricks' => "St. Patrick's Day",
                    'easter' => 'Easter',
                    'independence' => 'Independence Day',
                    'halloween' => 'Halloween',
                    'newyear' => "New Year's"
                ];
                ?>
                <div class="holiday-hero-config" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 16px;">
                    <label style="display: block; font-weight: 500; margin-bottom: 6px; font-size: 0.9rem;">Holiday Hero Content</label>
                    <p class="section-help" style="margin-bottom: 12px;">Customize the homepage hero for each holiday. Leave blank to keep your default hero.</p>

                    <?php foreach ($holidayList as $hKey => $hName): ?>
                    <?php $hConf = $holidayHeroes[$hKey] ?? []; ?>
                    <div class="holiday-hero-block" data-holiday-hero="<?php echo $hKey; ?>" style="display: <?php echo $currentPreview === $hKey ? 'block' : 'none'; ?>; background: rgba(255,255,255,0.03); border-radius: 8px; padding: 12px; margin-bottom: 8px;">
                        <div style="font-weight: 500; font-size: 0.85rem; margin-bottom: 8px; color: rgba(255,255,255,0.7);"><?php echo escape($hName); ?> Hero</div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                            <div>
                                <label style="font-size: 0.75rem; color: rgba(255,255,255,0.5); display: block; margin-bottom: 2px;">Badge Text</label>
                                <input type="text" name="holiday_hero_<?php echo $hKey; ?>_badge" value="<?php echo escape($hConf['badge'] ?? ''); ?>" placeholder="e.g. <?php echo escape($hName); ?> Collection" class="form-control" style="font-size: 0.85rem; padding: 6px 10px;">
                            </div>
                            <div>
                                <label style="font-size: 0.75rem; color: rgba(255,255,255,0.5); display: block; margin-bottom: 2px;">Heading</label>
                                <input type="text" name="holiday_hero_<?php echo $hKey; ?>_heading" value="<?php echo escape($hConf['heading'] ?? ''); ?>" placeholder="Hero heading override" class="form-control" style="font-size: 0.85rem; padding: 6px 10px;">
                            </div>
                            <div>
                                <label style="font-size: 0.75rem; color: rgba(255,255,255,0.5); display: block; margin-bottom: 2px;">Tagline</label>
                                <input type="text" name="holiday_hero_<?php echo $hKey; ?>_tagline" value="<?php echo escape($hConf['tagline'] ?? ''); ?>" placeholder="Hero tagline override" class="form-control" style="font-size: 0.85rem; padding: 6px 10px;">
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                <div>
                                    <label style="font-size: 0.75rem; color: rgba(255,255,255,0.5); display: block; margin-bottom: 2px;">Button Text</label>
                                    <input type="text" name="holiday_hero_<?php echo $hKey; ?>_cta_text" value="<?php echo escape($hConf['cta_text'] ?? ''); ?>" placeholder="Shop Now" class="form-control" style="font-size: 0.85rem; padding: 6px 10px;">
                                </div>
                                <div>
                                    <label style="font-size: 0.75rem; color: rgba(255,255,255,0.5); display: block; margin-bottom: 2px;">Button URL</label>
                                    <input type="text" name="holiday_hero_<?php echo $hKey; ?>_cta_url" value="<?php echo escape($hConf['cta_url'] ?? ''); ?>" placeholder="/products" class="form-control" style="font-size: 0.85rem; padding: 6px 10px;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ═══════════════ CUSTOM CSS ═══════════════ -->
        <div class="tab-pane" id="tab-css">
            <div class="settings-card">
                <h3>Custom CSS</h3>
                <p class="section-help">Add custom CSS to override default styles. This CSS is loaded after the theme styles.</p>
                <textarea name="custom_css" id="custom-css" class="form-control code-input"
                          rows="20" placeholder="/* Add custom CSS here */"><?php echo escape($theme['custom_css'] ?? ''); ?></textarea>
            </div>
        </div>

    </div>
</form>

<style>
.back-link {
    color: var(--admin-text-light);
    text-decoration: none;
    font-size: 0.875rem;
}
.back-link:hover { color: var(--admin-primary); }

.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 24px;
}
.header-left {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.header-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}
.btn-outline {
    background: transparent;
    border: 1px solid var(--admin-border);
    color: var(--admin-text);
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.15s ease;
}
.btn-outline:hover {
    border-color: var(--admin-primary);
    color: var(--admin-primary);
}

.alert-info {
    background: #dbeafe;
    color: #1e40af;
    padding: 12px 16px;
    border-radius: var(--admin-radius);
    margin-bottom: 24px;
}

/* ── Tab Navigation ── */
.theme-tabs {
    display: flex;
    gap: 0;
    border-bottom: 2px solid var(--admin-border);
    margin-bottom: 24px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.theme-tab {
    padding: 12px 20px;
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    color: var(--admin-text-light);
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.15s ease;
}
.theme-tab:hover {
    color: var(--admin-text);
    border-bottom-color: rgba(59, 130, 246, 0.3);
}
.theme-tab.active {
    color: var(--admin-primary, #3b82f6);
    border-bottom-color: var(--admin-primary, #3b82f6);
}

/* ── Tab Content ── */
.tab-pane { display: none; }
.tab-pane.active { display: block; }

.settings-card {
    background: var(--admin-card-bg);
    border-radius: var(--admin-radius);
    padding: 24px;
    margin-bottom: 20px;
}
.settings-card h3 {
    margin: 0 0 16px 0;
    font-size: 1rem;
    font-weight: 600;
}

.section-help {
    display: block;
    font-size: 0.75rem;
    color: var(--admin-text-light);
    margin-top: 4px;
    margin-bottom: 12px;
}

/* ── Color Pickers ── */
.color-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}
.color-picker-group { margin-bottom: 0; }
.color-picker-group label {
    display: block;
    font-weight: 500;
    margin-bottom: 6px;
}
.color-input-wrapper {
    display: flex;
    gap: 8px;
    align-items: center;
}
.color-picker {
    width: 50px;
    height: 40px;
    padding: 2px;
    border: 1px solid var(--admin-border);
    border-radius: 4px;
    cursor: pointer;
}
.color-hex-input {
    flex: 1;
    padding: 8px 12px;
    border: 1px solid var(--admin-border);
    border-radius: 4px;
    font-family: monospace;
    text-transform: uppercase;
}
.color-description {
    display: block;
    font-size: 0.75rem;
    color: var(--admin-text-light);
    margin-top: 4px;
}

/* ── Form Elements ── */
.form-group { margin-bottom: 16px; }
.form-group label {
    display: block;
    font-weight: 500;
    margin-bottom: 6px;
}
.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--admin-border);
    border-radius: 4px;
    font-size: 0.875rem;
}
.form-help {
    display: block;
    font-size: 0.75rem;
    color: var(--admin-text-light);
    margin-top: 4px;
}
.form-row-2col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
.code-input {
    font-family: monospace;
    font-size: 0.8rem;
    min-height: 300px;
    resize: vertical;
}

/* ── Effect Controls ── */
.effect-group {
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--admin-border);
}
.effect-group:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}
.effect-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.toggle-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    flex: 1;
}
.toggle-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--admin-primary, #3b82f6);
    cursor: pointer;
}
.toggle-text {
    font-weight: 500;
    font-size: 0.9rem;
}
.effect-option {
    width: 130px;
    padding: 6px 10px;
    font-size: 0.8rem;
}
.effect-description {
    display: block;
    font-size: 0.7rem;
    color: var(--admin-text-light);
    margin-top: 4px;
    margin-left: 28px;
}

/* ── Holiday ── */
.holiday-preview-section {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--admin-border);
}
.holiday-radio-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.holiday-radio {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    padding: 6px 10px;
    border-radius: 6px;
    transition: background 0.15s ease;
}
.holiday-radio:hover { background: rgba(0,0,0,0.03); }
.holiday-radio input[type="radio"] {
    width: 16px;
    height: 16px;
    accent-color: var(--admin-primary, #3b82f6);
    cursor: pointer;
    flex-shrink: 0;
}
.holiday-radio-label {
    font-size: 0.875rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
}
.holiday-radio-hint {
    font-size: 0.7rem;
    color: var(--admin-text-light);
    font-weight: 400;
}
.btn-preview-holiday {
    display: inline-block;
    margin-top: 14px;
    padding: 8px 18px;
    background: var(--admin-primary, #3b82f6);
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: opacity 0.15s ease;
}
.btn-preview-holiday:hover { opacity: 0.85; color: #fff; }

/* ── Checkbox ── */
.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

/* ── Buttons ── */
.btn-lg {
    padding: 14px 24px;
    font-size: 1rem;
}
.button-group {
    display: flex;
    gap: 12px;
}
.btn-secondary {
    background: #6b7280;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    padding: 8px 16px;
    font-size: 0.875rem;
}
.btn-secondary:hover { background: #4b5563; }

/* ── Notifications ── */
.save-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 14px 24px;
    border-radius: 8px;
    font-weight: 500;
    z-index: 10000;
    animation: slideIn 0.3s ease;
}
.save-notification.success { background: #10b981; color: white; }
.save-notification.error { background: #ef4444; color: white; }
.save-notification.fade-out { opacity: 0; transition: opacity 0.3s ease; }

@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

/* ── Image Uploads ── */
.theme-image-upload {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.image-preview-box {
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 16px;
    text-align: center;
    min-height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.image-preview-box img { border-radius: 4px; }
.image-preview-wide { min-height: 80px; }
.placeholder-text {
    color: #999;
    font-size: 0.85rem;
    font-style: italic;
}
.upload-actions {
    display: flex;
    gap: 8px;
    align-items: center;
}

@media (max-width: 768px) {
    .color-grid { grid-template-columns: 1fr; }
    .form-row-2col { grid-template-columns: 1fr; }
    .admin-header { flex-direction: column; gap: 12px; }
    .header-actions { flex-wrap: wrap; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('theme-form');
    var saveBtn = document.getElementById('save-btn');
    var saveCloseBtn = document.getElementById('save-close-btn');

    // ── Tab switching ──
    var tabs = document.querySelectorAll('.theme-tab');
    var panes = document.querySelectorAll('.tab-pane');

    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            var target = this.getAttribute('data-tab');
            tabs.forEach(function(t) { t.classList.remove('active'); });
            panes.forEach(function(p) { p.classList.remove('active'); });
            this.classList.add('active');
            document.getElementById('tab-' + target).classList.add('active');
            localStorage.setItem('theme-customize-tab', target);
        });
    });

    // Restore last active tab
    var savedTab = localStorage.getItem('theme-customize-tab');
    if (savedTab) {
        var tabBtn = document.querySelector('.theme-tab[data-tab="' + savedTab + '"]');
        if (tabBtn) tabBtn.click();
    }

    // ── Color pickers ──
    function syncColorInputs(picker, hex) {
        if (!picker || !hex) return;
        picker.addEventListener('input', function() { hex.value = this.value.toUpperCase(); });
        hex.addEventListener('input', function() {
            if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) picker.value = this.value;
        });
    }

    var colorPairs = [
        ['primary-color', 'primary-color-hex'],
        ['secondary-color', 'secondary-color-hex'],
        ['accent-color', 'accent-color-hex'],
        ['navbar-bg-color', 'navbar-bg-color-hex'],
        ['navbar-text-color', 'navbar-text-color-hex'],
        ['glow-color', 'glow-color-hex'],
        ['footer-bg-color', 'footer-bg-color-hex'],
        ['footer-text-color', 'footer-text-color-hex'],
        ['hero-bg-start', 'hero-bg-start-hex'],
        ['hero-bg-end', 'hero-bg-end-hex']
    ];
    colorPairs.forEach(function(pair) {
        syncColorInputs(document.getElementById(pair[0]), document.getElementById(pair[1]));
    });

    // ── Opacity sliders ──
    var opacitySlider = document.getElementById('effect-background-opacity');
    var opacityLabel = document.getElementById('opacity-value');
    if (opacitySlider && opacityLabel) {
        opacitySlider.addEventListener('input', function() { opacityLabel.textContent = this.value; });
    }
    var headerOpacitySlider = document.getElementById('effect-header-opacity');
    var headerOpacityLabel = document.getElementById('header-opacity-value');
    if (headerOpacitySlider && headerOpacityLabel) {
        headerOpacitySlider.addEventListener('input', function() { headerOpacityLabel.textContent = this.value; });
    }

    // ── Holiday preview link ──
    var previewHolidayBtn = document.getElementById('preview-holiday-btn');
    var radios = document.querySelectorAll('input[name="holiday_preview_holiday"]');
    function updatePreviewLink() {
        var selected = document.querySelector('input[name="holiday_preview_holiday"]:checked');
        if (selected && selected.value !== 'none') {
            previewHolidayBtn.href = '/?holiday_preview=' + selected.value;
        } else {
            previewHolidayBtn.href = '/';
        }
    }
    function updateHolidayHeroBlocks() {
        var selected = document.querySelector('input[name="holiday_preview_holiday"]:checked');
        var val = selected ? selected.value : 'none';
        document.querySelectorAll('.holiday-hero-block').forEach(function(block) {
            block.style.display = block.getAttribute('data-holiday-hero') === val ? 'block' : 'none';
        });
    }
    radios.forEach(function(r) {
        r.addEventListener('change', updatePreviewLink);
        r.addEventListener('change', updateHolidayHeroBlocks);
    });
    updatePreviewLink();
    updateHolidayHeroBlocks();

    // ── Preview button — posts form data to create a temp preview, then opens it ──
    document.getElementById('preview-btn').addEventListener('click', function() {
        var btn = this;
        btn.disabled = true;
        btn.textContent = 'Loading...';

        var formData = new FormData(form);

        fetch('/admin/themes/quick-preview', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new URLSearchParams(formData)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> Preview';
            if (data.success && data.preview_url) {
                window.open(data.preview_url, '_blank');
            } else {
                showNotification(data.error || 'Preview failed', 'error');
            }
        })
        .catch(function(err) {
            btn.disabled = false;
            btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> Preview';
            showNotification('Preview failed: ' + err.message, 'error');
        });
    });

    // ── Save function ──
    function saveTheme(closeAfterSave) {
        var formData = new FormData(form);
        saveBtn.disabled = true;
        saveCloseBtn.disabled = true;
        saveBtn.textContent = 'Saving...';

        fetch('/admin/themes/save', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new URLSearchParams(formData)
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                if (closeAfterSave) {
                    window.location.href = '/admin/themes';
                } else {
                    showNotification('Theme saved successfully!', 'success');
                    if (data.theme_id) {
                        var themeIdInput = form.querySelector('input[name="theme_id"]');
                        if (themeIdInput) themeIdInput.value = data.theme_id;
                        history.replaceState(null, '', '/admin/themes/customize?id=' + data.theme_id);
                    }
                    saveBtn.disabled = false;
                    saveCloseBtn.disabled = false;
                    saveBtn.textContent = 'Save';
                }
            } else {
                showNotification(data.error || 'Failed to save theme', 'error');
                saveBtn.disabled = false;
                saveCloseBtn.disabled = false;
                saveBtn.textContent = 'Save';
            }
        })
        .catch(function(err) {
            console.error('Fetch error:', err);
            showNotification('Error saving theme', 'error');
            saveBtn.disabled = false;
            saveCloseBtn.disabled = false;
            saveBtn.textContent = 'Save';
        });
    }

    function showNotification(message, type) {
        var existing = document.querySelector('.save-notification');
        if (existing) existing.remove();
        var notification = document.createElement('div');
        notification.className = 'save-notification ' + type;
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(function() {
            notification.classList.add('fade-out');
            setTimeout(function() { notification.remove(); }, 300);
        }, 3000);
    }

    saveBtn.addEventListener('click', function() { saveTheme(false); });
    saveCloseBtn.addEventListener('click', function() { saveTheme(true); });

    // ── Image Upload Handlers ──
    function setupImageUpload(fileId, endpoint, previewId, removeBtnId, removeEndpoint, placeholderText, imgStyle) {
        var fileInput = document.getElementById(fileId);
        if (!fileInput) return;

        fileInput.addEventListener('change', function() {
            if (!this.files || !this.files[0]) return;
            var formData = new FormData();
            formData.append(fileInput.name || fileId.replace('File', '').replace(/([A-Z])/g, '_$1').toLowerCase(), this.files[0]);
            formData.append('theme_id', document.querySelector('input[name="theme_id"]').value);
            formData.append('_csrf_token', document.querySelector('input[name="_csrf_token"]').value);

            // Determine the field name from the endpoint
            var fieldName = endpoint.split('/').pop().replace(/-/g, '_').replace('upload_', '');
            var fd2 = new FormData();
            fd2.append(fieldName, this.files[0]);
            fd2.append('theme_id', document.querySelector('input[name="theme_id"]').value);
            fd2.append('_csrf_token', document.querySelector('input[name="_csrf_token"]').value);

            showNotification('Uploading...', 'success');

            fetch(endpoint, { method: 'POST', body: fd2 })
                .then(function(r) {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.json();
                })
                .then(function(data) {
                    if (data.success) {
                        var preview = document.getElementById(previewId);
                        preview.innerHTML = '<img src="' + data.path + '?v=' + Date.now() + '" alt="Upload" style="' + imgStyle + '">';
                        if (removeBtnId && !document.getElementById(removeBtnId)) {
                            var actions = preview.nextElementSibling;
                            var rmBtn = document.createElement('button');
                            rmBtn.type = 'button';
                            rmBtn.className = 'btn btn-danger btn-sm';
                            rmBtn.id = removeBtnId;
                            rmBtn.textContent = 'Remove';
                            actions.appendChild(rmBtn);
                            bindRemove(rmBtn, removeEndpoint, previewId, placeholderText);
                        }
                        showNotification(data.message || 'Uploaded!', 'success');
                    } else {
                        showNotification(data.error || 'Upload failed', 'error');
                    }
                })
                .catch(function(err) { showNotification('Upload failed: ' + err.message, 'error'); });
            this.value = '';
        });

        var rmBtn = document.getElementById(removeBtnId);
        if (rmBtn) bindRemove(rmBtn, removeEndpoint, previewId, placeholderText);
    }

    function bindRemove(btn, endpoint, previewId, placeholderText) {
        btn.addEventListener('click', function() {
            var fd = new FormData();
            fd.append('theme_id', document.querySelector('input[name="theme_id"]').value);
            fd.append('_csrf_token', document.querySelector('input[name="_csrf_token"]').value);

            fetch(endpoint, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        document.getElementById(previewId).innerHTML = '<span class="placeholder-text">' + placeholderText + '</span>';
                        btn.remove();
                        showNotification(data.message || 'Removed', 'success');
                    }
                });
        });
    }

    setupImageUpload('themeLogoFile', '/admin/themes/upload-logo', 'themeLogoPreview', 'removeThemeLogo', '/admin/themes/remove-logo', 'Using store default logo', 'max-height:80px;');
    setupImageUpload('heroImageFile', '/admin/themes/upload-hero-image', 'heroImagePreview', 'removeHeroImage', '/admin/themes/remove-hero-image', 'Using default gradient', 'max-height:120px;width:100%;object-fit:cover;');
    setupImageUpload('navbarBgImageFile', '/admin/themes/upload-navbar-bg-image', 'navbarBgImagePreview', 'removeNavbarBgImage', '/admin/themes/remove-navbar-bg-image', 'No background image', 'max-height:60px;width:100%;object-fit:cover;');
    setupImageUpload('footerBgImageFile', '/admin/themes/upload-footer-bg-image', 'footerBgImagePreview', 'removeFooterBgImage', '/admin/themes/remove-footer-bg-image', 'No background image', 'max-height:60px;width:100%;object-fit:cover;');
});
</script>
