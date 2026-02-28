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

            <!-- Theme Name — always visible -->
            <div class="form-section">
                <h3>Theme Name</h3>
                <input type="text" name="name" id="theme-name"
                       value="<?php echo escape($isPreset ? $theme['name'] . ' (Custom)' : $theme['name']); ?>"
                       class="form-control" required>
            </div>

            <!-- Colors -->
            <div class="form-section collapsible" data-section="colors">
                <h3 class="section-toggle">
                    <span>Colors</span>
                    <svg class="toggle-chevron" width="12" height="12" viewBox="0 0 12 12"><path d="M3 4.5L6 7.5L9 4.5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </h3>
                <div class="section-content">
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

            <!-- Hero Section -->
            <div class="form-section collapsible" data-section="hero-colors">
                <h3 class="section-toggle">
                    <span>Hero Section Colors</span>
                    <svg class="toggle-chevron" width="12" height="12" viewBox="0 0 12 12"><path d="M3 4.5L6 7.5L9 4.5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </h3>
                <div class="section-content">
                    <p class="section-help">Gradient colors for the hero banner background. Does not affect holiday themes.</p>
                    <div class="form-group">
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
                    <div class="form-group">
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

            <!-- Navigation -->
            <div class="form-section collapsible" data-section="navigation">
                <h3 class="section-toggle">
                    <span>Navigation Bar</span>
                    <svg class="toggle-chevron" width="12" height="12" viewBox="0 0 12 12"><path d="M3 4.5L6 7.5L9 4.5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </h3>
                <div class="section-content">
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
                    'card_hover' => ['enabled' => true, 'style' => 'lift'],
                    'holiday_effects' => ['enabled' => false, 'preview' => 'none']
                ];
            }
            ?>

            <!-- Visual Effects -->
            <div class="form-section collapsible" data-section="effects">
                <h3 class="section-toggle">
                    <span>Visual Effects</span>
                    <svg class="toggle-chevron" width="12" height="12" viewBox="0 0 12 12"><path d="M3 4.5L6 7.5L9 4.5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </h3>
                <div class="section-content">
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
                                <span class="toggle-text">Page Background Pattern</span>
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
                            <select name="effect_header_style" class="form-control effect-option"
                                    id="effect-header-style">
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

                        <div style="margin-top: 10px;">
                            <label for="effect-header-location" style="font-size: 0.85rem; color: var(--admin-text-light);">Location</label>
                            <select name="effect_header_location" class="form-control" id="effect-header-location" style="margin-top: 4px;">
                                <option value="navbar" <?php echo ($effectSettings['header_effect']['location'] ?? 'navbar') === 'navbar' ? 'selected' : ''; ?>>Navbar</option>
                                <option value="hero" <?php echo ($effectSettings['header_effect']['location'] ?? '') === 'hero' ? 'selected' : ''; ?>>Hero Section</option>
                            </select>
                        </div>

                        <div style="margin-top: 10px;">
                            <label for="effect-header-opacity" style="font-size: 0.85rem; color: var(--admin-text-light);">
                                Opacity: <span id="header-opacity-value"><?php echo ($effectSettings['header_effect']['opacity'] ?? 0.5); ?></span>
                            </label>
                            <input type="range" name="effect_header_opacity" id="effect-header-opacity"
                                   min="0.05" max="1" step="0.05"
                                   value="<?php echo ($effectSettings['header_effect']['opacity'] ?? 0.5); ?>"
                                   class="form-control" style="width: 100%;">
                        </div>
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
            </div>

            <!-- Holiday Effects -->
            <div class="form-section collapsible" data-section="holidays">
                <h3 class="section-toggle">
                    <span>Holiday Effects</span>
                    <svg class="toggle-chevron" width="12" height="12" viewBox="0 0 12 12"><path d="M3 4.5L6 7.5L9 4.5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </h3>
                <div class="section-content">
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

            <!-- Style & Layout -->
            <div class="form-section collapsible" data-section="style-layout">
                <h3 class="section-toggle">
                    <span>Style &amp; Layout</span>
                    <svg class="toggle-chevron" width="12" height="12" viewBox="0 0 12 12"><path d="M3 4.5L6 7.5L9 4.5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </h3>
                <div class="section-content">
                    <p class="section-help">Overall visual style and page layout settings.</p>

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

                    <div class="form-group">
                        <label for="sidebar-menu-mode">Sidebar Menu</label>
                        <select name="sidebar_menu_mode" id="sidebar-menu-mode" class="form-control">
                            <option value="click" <?php echo ($theme["sidebar_menu_mode"] ?? "hover") == "click" ? "selected" : ""; ?>>
                                Click - Tap to expand/collapse categories
                            </option>
                            <option value="hover" <?php echo ($theme["sidebar_menu_mode"] ?? "hover") == "hover" ? "selected" : ""; ?>>
                                Hover - Expand on hover, auto-collapse after 5s
                            </option>
                            <option value="expanded" <?php echo ($theme["sidebar_menu_mode"] ?? "hover") == "expanded" ? "selected" : ""; ?>>
                                Expanded - All categories always visible
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Typography -->
            <div class="form-section collapsible" data-section="typography">
                <h3 class="section-toggle">
                    <span>Typography</span>
                    <svg class="toggle-chevron" width="12" height="12" viewBox="0 0 12 12"><path d="M3 4.5L6 7.5L9 4.5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </h3>
                <div class="section-content">
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

            <!-- Custom CSS -->
            <div class="form-section collapsible" data-section="custom-css">
                <h3 class="section-toggle">
                    <span>Custom CSS</span>
                    <svg class="toggle-chevron" width="12" height="12" viewBox="0 0 12 12"><path d="M3 4.5L6 7.5L9 4.5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </h3>
                <div class="section-content">
                    <textarea name="custom_css" id="custom-css" class="form-control code-input"
                              rows="6" placeholder="/* Add custom CSS here */"><?php echo escape($theme['custom_css'] ?? ''); ?></textarea>
                    <span class="section-help">Add custom CSS to override default styles.</span>
                </div>
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
                <!-- Navbar -->
                <div class="preview-navbar" id="preview-navbar">
                    <div class="preview-navbar-left">
                        <div class="preview-logo" id="preview-logo"><?php echo escape(appName()); ?></div>
                        <div class="preview-nav-links" id="preview-nav-links">
                            <span>Shop</span>
                            <span>About</span>
                            <span>Contact</span>
                        </div>
                    </div>
                    <div class="preview-navbar-icons" id="preview-navbar-icons">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                        <div class="preview-cart-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                            <span class="preview-cart-badge" id="preview-cart-badge">2</span>
                        </div>
                    </div>
                </div>

                <!-- Hero Section -->
                <div class="preview-hero" id="preview-hero">
                    <h2 id="preview-hero-heading">Welcome to Our Store</h2>
                    <p>Discover amazing products curated just for you</p>
                    <button class="preview-btn" id="preview-btn">Shop Now</button>
                </div>

                <!-- Product Grid -->
                <div class="preview-products" id="preview-products">
                    <div class="preview-product-card">
                        <div class="preview-product-image" style="background: linear-gradient(135deg, #f0f0f0 0%, #e0e0e0 100%);">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#bbb" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                        </div>
                        <div class="preview-product-info">
                            <div class="preview-product-name">Handmade Candle</div>
                            <div class="preview-product-price">$29.99</div>
                            <button class="preview-add-to-cart" type="button">Add to Cart</button>
                        </div>
                    </div>
                    <div class="preview-product-card">
                        <div class="preview-product-image" style="background: linear-gradient(135deg, #f0f0f0 0%, #e0e0e0 100%);">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#bbb" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                        </div>
                        <div class="preview-product-info">
                            <div class="preview-product-name">Gift Box Set</div>
                            <div class="preview-product-price">$39.99</div>
                            <button class="preview-add-to-cart" type="button">Add to Cart</button>
                        </div>
                    </div>
                    <div class="preview-product-card">
                        <div class="preview-product-image" style="background: linear-gradient(135deg, #f0f0f0 0%, #e0e0e0 100%);">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#bbb" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                        </div>
                        <div class="preview-product-info">
                            <div class="preview-product-name">Artisan Soap</div>
                            <div class="preview-product-price">$19.99</div>
                            <button class="preview-add-to-cart" type="button">Add to Cart</button>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="preview-footer" id="preview-footer">
                    <span id="preview-footer-name">&copy; <?php echo escape(appName()); ?></span>
                    <div class="preview-footer-links">
                        <span>Privacy</span>
                        <span>Terms</span>
                        <span>Contact</span>
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
    margin-bottom: 4px;
    padding-bottom: 0;
    border-bottom: 1px solid var(--admin-border);
}

.form-section:last-of-type {
    border-bottom: none;
    margin-bottom: 0;
}

.form-section h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    padding: 14px 0;
}

/* Non-collapsible first section */
.form-section:not(.collapsible) {
    margin-bottom: 8px;
    padding-bottom: 16px;
}

.form-section:not(.collapsible) h3 {
    padding: 0 0 12px 0;
}

/* Collapsible sections */
.section-toggle {
    cursor: pointer;
    user-select: none;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 0;
    padding: 14px 0;
    transition: color 0.2s ease;
}

.section-toggle:hover {
    color: var(--admin-primary, #3b82f6);
}

.toggle-chevron {
    transition: transform 0.25s ease;
    opacity: 0.5;
    flex-shrink: 0;
}

.form-section.collapsed .toggle-chevron {
    transform: rotate(-90deg);
}

.section-content {
    overflow: hidden;
    max-height: 2000px;
    transition: max-height 0.3s ease, padding 0.3s ease, opacity 0.2s ease;
    padding-bottom: 16px;
    opacity: 1;
}

.form-section.collapsed .section-content {
    max-height: 0;
    padding-bottom: 0;
    opacity: 0;
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

/* Holiday preview section */
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

.holiday-radio:hover {
    background: rgba(0,0,0,0.03);
}

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

.btn-preview-holiday:hover {
    opacity: 0.85;
    color: #fff;
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

/* Navbar */
.preview-navbar {
    padding: 12px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.preview-navbar-left {
    display: flex;
    align-items: center;
    gap: 24px;
}

.preview-logo {
    font-weight: 700;
    font-size: 1.1rem;
    white-space: nowrap;
}

.preview-nav-links {
    display: flex;
    gap: 16px;
    font-size: 0.75rem;
    opacity: 0.85;
}

.preview-navbar-icons {
    display: flex;
    align-items: center;
    gap: 12px;
}

.preview-navbar-icons svg {
    opacity: 0.85;
}

.preview-cart-icon {
    position: relative;
    display: flex;
    align-items: center;
}

.preview-cart-badge {
    position: absolute;
    top: -6px;
    right: -8px;
    font-size: 9px;
    font-weight: 700;
    color: white;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
}

/* Hero */
.preview-hero {
    padding: 40px 24px;
    text-align: center;
}

.preview-hero h2 {
    margin: 0 0 8px 0;
    font-size: 1.5rem;
}

.preview-hero p {
    margin: 0 0 16px 0;
    font-size: 0.8rem;
    color: #666;
}

.preview-btn {
    padding: 10px 28px;
    border: none;
    border-radius: 4px;
    font-weight: 600;
    font-size: 0.8rem;
    cursor: pointer;
    color: white;
    letter-spacing: 0.3px;
}

/* Products */
.preview-products {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    padding: 20px;
    background: #f9fafb;
}

.preview-product-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.preview-product-image {
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.preview-product-info {
    padding: 10px;
}

.preview-product-name {
    font-size: 0.75rem;
    font-weight: 500;
    margin-bottom: 4px;
    color: #333;
}

.preview-product-price {
    font-weight: 700;
    font-size: 0.85rem;
    margin-bottom: 8px;
}

.preview-add-to-cart {
    width: 100%;
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 600;
    color: white;
    cursor: pointer;
    letter-spacing: 0.3px;
}

/* Footer */
.preview-footer {
    padding: 12px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.65rem;
    opacity: 0.9;
}

.preview-footer-links {
    display: flex;
    gap: 12px;
    opacity: 0.7;
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
    // ── Collapsible sections ──
    document.querySelectorAll('.section-toggle').forEach(function(toggle) {
        toggle.addEventListener('click', function() {
            var section = this.closest('.form-section');
            section.classList.toggle('collapsed');
            // Persist state
            var sectionId = section.getAttribute('data-section');
            if (sectionId) {
                var state = JSON.parse(localStorage.getItem('theme-customize-sections') || '{}');
                state[sectionId] = section.classList.contains('collapsed');
                localStorage.setItem('theme-customize-sections', JSON.stringify(state));
            }
        });
    });

    // Restore collapsed state from localStorage
    var savedState = JSON.parse(localStorage.getItem('theme-customize-sections') || '{}');
    Object.keys(savedState).forEach(function(sectionId) {
        if (savedState[sectionId]) {
            var section = document.querySelector('[data-section="' + sectionId + '"]');
            if (section) section.classList.add('collapsed');
        }
    });

    // ── Color pickers ──
    var form = document.getElementById('theme-form');
    var primaryColor = document.getElementById('primary-color');
    var secondaryColor = document.getElementById('secondary-color');
    var accentColor = document.getElementById('accent-color');
    var navbarBgColor = document.getElementById('navbar-bg-color');
    var navbarTextColor = document.getElementById('navbar-text-color');
    var glowColor = document.getElementById('glow-color');
    var primaryHex = document.getElementById('primary-color-hex');
    var secondaryHex = document.getElementById('secondary-color-hex');
    var accentHex = document.getElementById('accent-color-hex');
    var navbarBgHex = document.getElementById('navbar-bg-color-hex');
    var navbarTextHex = document.getElementById('navbar-text-color-hex');
    var glowHex = document.getElementById('glow-color-hex');
    var saveBtn = document.getElementById('save-btn');
    var saveCloseBtn = document.getElementById('save-close-btn');

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

    // ── Preview update ──
    function updatePreview() {
        var primary = primaryColor.value;
        var secondary = secondaryColor.value;
        var accent = accentColor.value;
        var navbarBg = navbarBgColor.value;
        var navbarText = navbarTextColor.value;
        var glow = glowColor.value;

        function hexToRgba(hex, alpha) {
            var r = parseInt(hex.slice(1,3), 16);
            var g = parseInt(hex.slice(3,5), 16);
            var b = parseInt(hex.slice(5,7), 16);
            return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
        }

        var headingFontSelect = document.getElementById('heading-font');
        var bodyFontSelect = document.getElementById('body-font');
        var headingFont = headingFontSelect ? headingFontSelect.value : 'Inter';
        var bodyFont = bodyFontSelect ? bodyFontSelect.value : 'Inter';

        var shadowSelect = document.getElementById('effect-shadow-style');
        var radiusSelect = document.getElementById('effect-border-radius');
        var shadowStyle = shadowSelect ? shadowSelect.value : 'soft';
        var borderRadius = radiusSelect ? radiusSelect.value : 'rounded';

        var radiusValue = '8px';
        if (borderRadius === 'sharp') radiusValue = '0';
        else if (borderRadius === 'slightly-rounded') radiusValue = '4px';
        else if (borderRadius === 'rounded') radiusValue = '8px';
        else if (borderRadius === 'pill') radiusValue = '20px';

        var shadowValue = '0 1px 3px rgba(0,0,0,0.05)';
        if (shadowStyle === 'none') shadowValue = 'none';
        else if (shadowStyle === 'subtle') shadowValue = '0 1px 3px rgba(0,0,0,0.06)';
        else if (shadowStyle === 'soft') shadowValue = '0 2px 8px rgba(0,0,0,0.08)';
        else if (shadowStyle === 'dramatic') shadowValue = '0 4px 16px rgba(0,0,0,0.12)';

        var el;

        if ((el = document.getElementById('preview-navbar'))) {
            el.style.background = navbarBg;
            el.style.borderBottom = '1px solid ' + hexToRgba(navbarText, 0.1);
        }
        if ((el = document.getElementById('preview-logo'))) {
            el.style.color = navbarText;
            el.style.fontFamily = headingFont + ', serif';
        }
        if ((el = document.getElementById('preview-nav-links'))) {
            el.style.color = navbarText;
        }
        if ((el = document.getElementById('preview-navbar-icons'))) {
            el.style.color = navbarText;
        }
        if ((el = document.getElementById('preview-cart-badge'))) {
            el.style.background = primary;
        }
        if ((el = document.getElementById('preview-hero'))) {
            el.style.background = 'linear-gradient(135deg, ' + hexToRgba(accent, 0.15) + ' 0%, white 100%)';
            el.style.fontFamily = bodyFont + ', sans-serif';
        }
        if ((el = document.getElementById('preview-hero-heading'))) {
            el.style.fontFamily = headingFont + ', serif';
            el.style.color = secondary;
        }
        if ((el = document.getElementById('preview-btn'))) {
            el.style.background = primary;
            el.style.borderRadius = radiusValue;
            el.style.boxShadow = '0 4px 15px ' + hexToRgba(glow, 0.3);
        }
        if ((el = document.getElementById('preview-products'))) {
            el.style.background = hexToRgba(accent, 0.06);
        }
        document.querySelectorAll('.preview-product-card').forEach(function(card) {
            card.style.borderRadius = radiusValue;
            card.style.boxShadow = shadowValue;
        });
        document.querySelectorAll('.preview-product-price').forEach(function(priceEl) {
            priceEl.style.color = primary;
        });
        document.querySelectorAll('.preview-product-name').forEach(function(nameEl) {
            nameEl.style.fontFamily = bodyFont + ', sans-serif';
        });
        document.querySelectorAll('.preview-add-to-cart').forEach(function(btn) {
            btn.style.background = primary;
            btn.style.borderRadius = radiusValue;
        });
        if ((el = document.getElementById('preview-footer'))) {
            el.style.background = navbarBg;
            el.style.color = navbarText;
        }
    }

    // Opacity slider displays
    var opacitySlider = document.getElementById('effect-background-opacity');
    var opacityLabel = document.getElementById('opacity-value');
    if (opacitySlider && opacityLabel) {
        opacitySlider.addEventListener('input', function() {
            opacityLabel.textContent = this.value;
        });
    }
    var headerOpacitySlider = document.getElementById('effect-header-opacity');
    var headerOpacityLabel = document.getElementById('header-opacity-value');
    if (headerOpacitySlider && headerOpacityLabel) {
        headerOpacitySlider.addEventListener('input', function() {
            headerOpacityLabel.textContent = this.value;
        });
    }

    // Initial preview
    updatePreview();

    // Listen to all inputs for preview updates
    form.querySelectorAll('input, select').forEach(function(input) {
        input.addEventListener('change', updatePreview);
        input.addEventListener('input', updatePreview);
    });

    // ── Holiday preview link ──
    var previewBtn = document.getElementById('preview-holiday-btn');
    var radios = document.querySelectorAll('input[name="holiday_preview_holiday"]');
    function updatePreviewLink() {
        var selected = document.querySelector('input[name="holiday_preview_holiday"]:checked');
        if (selected && selected.value !== 'none') {
            previewBtn.href = '/?holiday_preview=' + selected.value;
            previewBtn.style.display = 'inline-block';
        } else {
            previewBtn.href = '/';
            previewBtn.style.display = 'inline-block';
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
});
</script>
