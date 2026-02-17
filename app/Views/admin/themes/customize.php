<div class="admin-header">
    <div class="header-left">
        <a href="/admin/themes" class="back-link">&larr; Back to Themes</a>
        <h1><?php echo $isPreset ? 'Customize' : 'Edit'; ?>: <?php echo escape($theme['name']); ?></h1>
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

    <div class="customize-layout">
        <div class="customize-sidebar">
            <div class="form-section">
                <h3>Theme Name</h3>
                <input type="text" name="name" id="theme-name"
                       value="<?php echo escape($isPreset ? $theme['name'] . ' (Custom)' : $theme['name']); ?>"
                       class="form-control" required>
            </div>

            <div class="form-section">
                <h3>Colors</h3>
                <p class="section-help">Choose your primary colors. Shades will be generated automatically.</p>

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
            </div>

            <div class="form-section">
                <h3>Navigation Bar</h3>
                <p class="section-help">Customize the top menu bar appearance.</p>

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

            <div class="form-section">
                <h3>Effect Colors</h3>
                <p class="section-help">Set the glow and shadow color.</p>

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

            <?php
            // Parse effect settings
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
                    'card_hover' => ['enabled' => true, 'style' => 'lift']
                ];
            }
            ?>

            <div class="form-section">
                <h3>Visual Effects</h3>
                <p class="section-help">Configure animations, shadows, and hover effects.</p>

                <div class="effect-group">
                    <div class="effect-toggle">
                        <label class="toggle-label">
                            <input type="checkbox" name="effect_button_glow_enabled" id="effect-button-glow"
                                   <?php echo !empty($effectSettings['button_glow']['enabled']) ? 'checked' : ''; ?>>
                            <span class="toggle-text">Button Glow</span>
                        </label>
                        <select name="effect_button_glow_intensity" class="form-control effect-option"
                                id="effect-button-glow-intensity">
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
                        <select name="effect_hover_speed" class="form-control effect-option"
                                id="effect-hover-speed">
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
                            <span class="toggle-text">Background Animation</span>
                        </label>
                        <select name="effect_background_style" class="form-control effect-option"
                                id="effect-background-style">
                            <option value="circles" <?php echo ($effectSettings['background_animation']['style'] ?? 'circles') === 'circles' ? 'selected' : ''; ?>>Floating Circles</option>
                            <option value="gradient" <?php echo ($effectSettings['background_animation']['style'] ?? '') === 'gradient' ? 'selected' : ''; ?>>Gradient Orbs</option>
                            <option value="geometric" <?php echo ($effectSettings['background_animation']['style'] ?? '') === 'geometric' ? 'selected' : ''; ?>>Geometric</option>
                            <option value="dots" <?php echo ($effectSettings['background_animation']['style'] ?? '') === 'dots' ? 'selected' : ''; ?>>Dots Grid</option>
                            <option value="waves" <?php echo ($effectSettings['background_animation']['style'] ?? '') === 'waves' ? 'selected' : ''; ?>>Waves</option>
                            <option value="particles" <?php echo ($effectSettings['background_animation']['style'] ?? '') === 'particles' ? 'selected' : ''; ?>>Particles</option>
                        </select>
                    </div>
                    <span class="effect-description">Animated background decoration style</span>
                </div>

                <div class="effect-group">
                    <div class="effect-toggle">
                        <label class="toggle-label">
                            <input type="checkbox" name="effect_page_transitions_enabled" id="effect-page-transitions"
                                   <?php echo !empty($effectSettings['page_transitions']['enabled']) ? 'checked' : ''; ?>>
                            <span class="toggle-text">Page Transitions</span>
                        </label>
                        <select name="effect_page_transitions_style" class="form-control effect-option"
                                id="effect-page-transitions-style">
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
                        <select name="effect_card_hover_style" class="form-control effect-option"
                                id="effect-card-hover-style">
                            <option value="lift" <?php echo ($effectSettings['card_hover']['style'] ?? 'lift') === 'lift' ? 'selected' : ''; ?>>Lift</option>
                            <option value="glow" <?php echo ($effectSettings['card_hover']['style'] ?? '') === 'glow' ? 'selected' : ''; ?>>Glow</option>
                            <option value="scale" <?php echo ($effectSettings['card_hover']['style'] ?? '') === 'scale' ? 'selected' : ''; ?>>Scale</option>
                            <option value="none" <?php echo ($effectSettings['card_hover']['style'] ?? '') === 'none' ? 'selected' : ''; ?>>None</option>
                        </select>
                    </div>
                    <span class="effect-description">Product card hover behavior</span>
                </div>
            </div>

            <div class="form-section">
                <h3>Style Options</h3>
                <p class="section-help">Overall visual style settings.</p>

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

            <div class="form-section">
                <h3>Typography</h3>

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

            <div class="form-section">
                <h3>Layout</h3>

                <div class="form-group">
                    <label for="layout-style">Page Layout</label>
                    <select name="layout_style" id="layout-style" class="form-control">
                        <option value="sidebar" <?php echo ($theme['layout_style'] == 'sidebar') ? 'selected' : ''; ?>>
                            Sidebar - Category filter on left side
                        </option>
                        <option value="full-width" <?php echo ($theme['layout_style'] == 'full-width') ? 'selected' : ''; ?>>
                            Full Width - Maximum content space
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="header-style">Header Style</label>
                    <select name="header_style" id="header-style" class="form-control">
                        <option value="standard" <?php echo ($theme['header_style'] == 'standard') ? 'selected' : ''; ?>>
                            Standard - Logo left, navigation right
                        </option>
                        <option value="centered" <?php echo ($theme['header_style'] == 'centered') ? 'selected' : ''; ?>>
                            Centered - Logo center, navigation below
                        </option>
                        <option value="minimal" <?php echo ($theme['header_style'] == 'minimal') ? 'selected' : ''; ?>>
                            Minimal - Compact, clean header
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="category-layout">Product Listing</label>
                    <select name="category_layout" id="category-layout" class="form-control">
                        <option value="grid" <?php echo ($theme['category_layout'] == 'grid') ? 'selected' : ''; ?>>
                            Grid - Uniform card layout
                        </option>
                        <option value="list" <?php echo ($theme['category_layout'] == 'list') ? 'selected' : ''; ?>>
                            List - Horizontal product rows
                        </option>
                        <option value="masonry" <?php echo ($theme['category_layout'] == 'masonry') ? 'selected' : ''; ?>>
                            Masonry - Pinterest-style layout
                        </option>
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
            </div>

            <div class="form-section">
                <h3>Custom CSS (Advanced)</h3>
                <textarea name="custom_css" id="custom-css" class="form-control code-input"
                          rows="6" placeholder="/* Add custom CSS here */"><?php echo escape($theme['custom_css'] ?? ''); ?></textarea>
                <span class="section-help">Add custom CSS to override default styles.</span>
            </div>

            <div class="form-actions">
                <div class="action-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="activate" id="activate-theme" checked>
                        Activate theme after saving
                    </label>
                </div>
                <div class="button-group">
                    <button type="button" id="save-btn" class="btn btn-primary btn-lg">
                        Save
                    </button>
                    <button type="button" id="save-close-btn" class="btn btn-secondary btn-lg">
                        Save & Close
                    </button>
                </div>
            </div>
        </div>

        <div class="customize-preview">
            <h3>Live Preview</h3>
            <div class="preview-frame" id="preview-frame">
                <div class="preview-header" id="preview-header">
                    <div class="preview-logo">Your Store</div>
                    <div class="preview-nav">
                        <span>Shop</span>
                        <span>About</span>
                        <span>Contact</span>
                    </div>
                </div>
                <div class="preview-hero" id="preview-hero">
                    <h2>Welcome to Our Store</h2>
                    <p>Discover amazing products</p>
                    <button class="preview-btn" id="preview-btn">Shop Now</button>
                </div>
                <div class="preview-products" id="preview-products">
                    <div class="preview-product-card">
                        <div class="preview-product-image"></div>
                        <div class="preview-product-name">Product Name</div>
                        <div class="preview-product-price">$29.99</div>
                    </div>
                    <div class="preview-product-card">
                        <div class="preview-product-image"></div>
                        <div class="preview-product-name">Product Name</div>
                        <div class="preview-product-price">$39.99</div>
                    </div>
                    <div class="preview-product-card">
                        <div class="preview-product-image"></div>
                        <div class="preview-product-name">Product Name</div>
                        <div class="preview-product-price">$49.99</div>
                    </div>
                </div>
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

.back-link:hover {
    color: var(--admin-primary);
}

.header-left {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.alert-info {
    background: #dbeafe;
    color: #1e40af;
    padding: 12px 16px;
    border-radius: var(--admin-radius);
    margin-bottom: 24px;
}

.customize-layout {
    display: grid;
    grid-template-columns: 400px 1fr;
    gap: 32px;
    align-items: start;
}

.customize-sidebar {
    background: var(--admin-card-bg);
    border-radius: var(--admin-radius);
    padding: 24px;
}

.form-section {
    margin-bottom: 28px;
    padding-bottom: 28px;
    border-bottom: 1px solid var(--admin-border);
}

.form-section:last-of-type {
    border-bottom: none;
    margin-bottom: 0;
}

.form-section h3 {
    margin: 0 0 12px 0;
    font-size: 1rem;
    font-weight: 600;
}

.section-help {
    display: block;
    font-size: 0.75rem;
    color: var(--admin-text-light);
    margin-top: 4px;
}

.color-picker-group {
    margin-bottom: 16px;
}

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

.form-group {
    margin-bottom: 16px;
}

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

.code-input {
    font-family: monospace;
    font-size: 0.8rem;
}

/* Effect controls */
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

.form-actions {
    margin-top: 24px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

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
}

.btn-secondary:hover {
    background: #4b5563;
}

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

.save-notification.success {
    background: #10b981;
    color: white;
}

.save-notification.error {
    background: #ef4444;
    color: white;
}

.save-notification.fade-out {
    opacity: 0;
    transition: opacity 0.3s ease;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Preview styles */
.customize-preview {
    position: sticky;
    top: 20px;
}

.customize-preview h3 {
    margin: 0 0 16px 0;
}

.preview-frame {
    background: white;
    border-radius: var(--admin-radius);
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    border: 1px solid var(--admin-border);
}

.preview-header {
    padding: 16px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #eee;
}

.preview-logo {
    font-weight: 700;
    font-size: 1.25rem;
}

.preview-nav {
    display: flex;
    gap: 20px;
    font-size: 0.875rem;
}

.preview-hero {
    padding: 48px 24px;
    text-align: center;
}

.preview-hero h2 {
    margin: 0 0 8px 0;
    font-size: 1.75rem;
}

.preview-hero p {
    margin: 0 0 20px 0;
    color: #666;
}

.preview-btn {
    padding: 12px 32px;
    border: none;
    border-radius: 4px;
    font-weight: 600;
    cursor: pointer;
    color: white;
}

.preview-products {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    padding: 24px;
    background: #f9fafb;
}

.preview-product-card {
    background: white;
    border-radius: 8px;
    padding: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.preview-product-image {
    aspect-ratio: 1;
    background: #e5e7eb;
    border-radius: 4px;
    margin-bottom: 8px;
}

.preview-product-name {
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 4px;
}

.preview-product-price {
    font-weight: 600;
}

@media (max-width: 1024px) {
    .customize-layout {
        grid-template-columns: 1fr;
    }

    .customize-preview {
        position: static;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('theme-form');
    const primaryColor = document.getElementById('primary-color');
    const secondaryColor = document.getElementById('secondary-color');
    const accentColor = document.getElementById('accent-color');
    const navbarBgColor = document.getElementById('navbar-bg-color');
    const navbarTextColor = document.getElementById('navbar-text-color');
    const glowColor = document.getElementById('glow-color');
    const primaryHex = document.getElementById('primary-color-hex');
    const secondaryHex = document.getElementById('secondary-color-hex');
    const accentHex = document.getElementById('accent-color-hex');
    const navbarBgHex = document.getElementById('navbar-bg-color-hex');
    const navbarTextHex = document.getElementById('navbar-text-color-hex');
    const glowHex = document.getElementById('glow-color-hex');
    const saveBtn = document.getElementById('save-btn');
    const saveCloseBtn = document.getElementById('save-close-btn');

    // Sync color pickers with hex inputs
    function syncColorInputs(picker, hex) {
        picker.addEventListener('input', function() {
            hex.value = this.value.toUpperCase();
            updatePreview();
        });

        hex.addEventListener('input', function() {
            if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
                picker.value = this.value;
                updatePreview();
            }
        });
    }

    syncColorInputs(primaryColor, primaryHex);
    syncColorInputs(secondaryColor, secondaryHex);
    syncColorInputs(accentColor, accentHex);
    syncColorInputs(navbarBgColor, navbarBgHex);
    syncColorInputs(navbarTextColor, navbarTextHex);
    syncColorInputs(glowColor, glowHex);

    // Update preview
    function updatePreview() {
        const primary = primaryColor.value;
        const secondary = secondaryColor.value;
        const accent = accentColor.value;
        const navbarBg = navbarBgColor.value;
        const navbarText = navbarTextColor.value;

        // Update preview elements (with null checks)
        var el;
        if ((el = document.getElementById('preview-header'))) el.style.background = navbarBg;
        if ((el = document.getElementById('preview-logo'))) el.style.color = navbarText;
        if ((el = document.querySelector('.preview-nav'))) el.style.color = navbarText;
        if ((el = document.getElementById('preview-hero'))) el.style.background = 'linear-gradient(135deg, ' + accent + ' 0%, white 100%)';
        if ((el = document.getElementById('preview-btn'))) el.style.background = primary;

        // Update all product prices
        document.querySelectorAll('.preview-product-price').forEach(function(priceEl) {
            priceEl.style.color = primary;
        });
    }

    // Initial preview update
    updatePreview();

    // Listen to all inputs for preview updates
    form.querySelectorAll('input, select').forEach(input => {
        input.addEventListener('change', updatePreview);
    });

    // Save function
    function saveTheme(closeAfterSave) {
        const formData = new FormData(form);

        saveBtn.disabled = true;
        saveCloseBtn.disabled = true;
        saveBtn.textContent = 'Saving...';

        fetch('/admin/themes/save', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (closeAfterSave) {
                    window.location.href = '/admin/themes';
                } else {
                    // Show success message
                    showNotification('Theme saved successfully!', 'success');
                    // Update theme_id if a new theme was created from preset
                    if (data.theme_id) {
                        const themeIdInput = form.querySelector('input[name="theme_id"]');
                        if (themeIdInput) {
                            themeIdInput.value = data.theme_id;
                        }
                        // Update page URL without reload
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
        .catch(err => {
            console.error('Fetch error:', err);
            showNotification('Error saving theme', 'error');
            saveBtn.disabled = false;
            saveCloseBtn.disabled = false;
            saveBtn.textContent = 'Save';
        });
    }

    // Notification helper
    function showNotification(message, type) {
        // Remove existing notification
        const existing = document.querySelector('.save-notification');
        if (existing) existing.remove();

        const notification = document.createElement('div');
        notification.className = 'save-notification ' + type;
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.classList.add('fade-out');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Button handlers
    saveBtn.addEventListener('click', function() {
        saveTheme(false);
    });

    saveCloseBtn.addEventListener('click', function() {
        saveTheme(true);
    });
});
</script>
