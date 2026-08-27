<div class="admin-header">
    <h1>Store Settings</h1>
</div>

<?php if ($flash = getFlash('success')): ?>
    <div class="alert alert-success"><?php echo escape($flash); ?></div>
<?php endif; ?>

<?php if ($flash = getFlash('error')): ?>
    <div class="alert alert-error"><?php echo escape($flash); ?></div>
<?php endif; ?>

<!-- Tabs Navigation -->
<div class="settings-tabs">
    <button class="settings-tab active" data-tab="general">General</button>
    <button class="settings-tab" data-tab="branding">Branding</button>
    <button class="settings-tab" data-tab="seo">SEO</button>
    <button class="settings-tab" data-tab="email">Email</button>
    <button class="settings-tab" data-tab="tax">Tax</button>
</div>

<!-- Tab: General -->
<div class="tab-panel active" id="tab-general">
    <div class="settings-card">
        <form id="store-info-form" class="settings-form">
            <?php echo csrfField(); ?>
            <input type="hidden" name="_form" value="store-info">

            <div class="form-group">
                <label for="store_name">Store Name</label>
                <input type="text" name="store_name" id="store_name"
                       value="<?php echo escape($settings['store_name']); ?>"
                       class="form-control" required>
                <span class="form-help">This appears in the header and page titles</span>
            </div>

            <div class="form-group">
                <label for="store_tagline">Tagline</label>
                <input type="text" name="store_tagline" id="store_tagline"
                       value="<?php echo escape($settings['store_tagline']); ?>"
                       class="form-control" placeholder="Your store's motto or description">
                <span class="form-help">Optional short description shown in some places</span>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="store_email">Contact Email</label>
                    <input type="email" name="store_email" id="store_email"
                           value="<?php echo escape($settings['store_email']); ?>"
                           class="form-control" placeholder="contact@yourstore.com">
                </div>
                <div class="form-group">
                    <label for="store_phone">Contact Phone</label>
                    <input type="tel" name="store_phone" id="store_phone"
                           value="<?php echo escape($settings['store_phone']); ?>"
                           class="form-control" placeholder="+1 (555) 123-4567">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="store_currency">Currency</label>
                    <select name="store_currency" id="store_currency" class="form-control">
                        <option value="USD" <?php echo ($settings['store_currency'] == 'USD') ? 'selected' : ''; ?>>USD - US Dollar</option>
                        <option value="EUR" <?php echo ($settings['store_currency'] == 'EUR') ? 'selected' : ''; ?>>EUR - Euro</option>
                        <option value="GBP" <?php echo ($settings['store_currency'] == 'GBP') ? 'selected' : ''; ?>>GBP - British Pound</option>
                        <option value="CAD" <?php echo ($settings['store_currency'] == 'CAD') ? 'selected' : ''; ?>>CAD - Canadian Dollar</option>
                        <option value="AUD" <?php echo ($settings['store_currency'] == 'AUD') ? 'selected' : ''; ?>>AUD - Australian Dollar</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="store_currency_symbol">Currency Symbol</label>
                    <input type="text" name="store_currency_symbol" id="store_currency_symbol"
                           value="<?php echo escape($settings['store_currency_symbol']); ?>"
                           class="form-control" maxlength="3" style="width: 80px;">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="free_shipping_threshold">Free Shipping Threshold ($)</label>
                    <input type="number" name="free_shipping_threshold" id="free_shipping_threshold"
                           value="<?php echo escape($settings['free_shipping_threshold'] ?? '0'); ?>"
                           class="form-control" min="0" step="0.01" style="width: 140px;">
                    <span class="form-help">Orders above this amount get free shipping. Set to 0 to disable the progress bar.</span>
                </div>
            </div>

            <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--admin-border);">
                <h3 style="font-size: 0.95rem; margin: 0 0 16px 0;">Site Options</h3>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="maintenance_mode" id="maintenance_mode" value="1"
                               <?php echo !empty($settings['maintenance_mode']) ? 'checked' : ''; ?>>
                        <span>Enable "Coming Soon" Mode</span>
                    </label>
                    <span class="form-help">Show a coming soon splash page to visitors. Preview with ?preview=1 on any URL.</span>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="maintenance_enabled" id="maintenance_enabled" value="1"
                               <?php echo !empty($settings['maintenance_enabled']) ? 'checked' : ''; ?>>
                        <span>Enable Maintenance Mode</span>
                    </label>
                    <span class="form-help">Show a maintenance page with HTTP 503 status. Admin panel remains accessible.</span>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="enable_downloads_section" id="enable_downloads_section" value="1"
                               <?php echo !empty($settings['enable_downloads_section']) ? 'checked' : ''; ?>>
                        <span>Enable Downloads Section</span>
                    </label>
                    <span class="form-help">Show the Downloads section in the admin panel for digital products.</span>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <?php $isFree = \App\Core\License::isFree(); ?>
                        <input type="checkbox" name="show_powered_by" id="show_powered_by" value="1"
                               <?php echo ($settings['show_powered_by'] ?? true) || $isFree ? 'checked' : ''; ?>
                               <?php echo $isFree ? 'disabled' : ''; ?>>
                        <span>Show "Powered by Apparix" in footer</span>
                    </label>
                    <?php if ($isFree): ?>
                    <span class="form-help" style="color: var(--warning-color, #f59e0b);">Always enabled on free tier. <a href="<?php echo \App\Core\License::getUpgradeUrl(); ?>" target="_blank">Upgrade</a> to disable.</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Tab: Branding -->
<div class="tab-panel" id="tab-branding">
    <div class="settings-card">
        <!-- Sub-tabs for branding -->
        <div class="sub-tabs">
            <button class="sub-tab active" data-subtab="logo">Logo</button>
            <button class="sub-tab" data-subtab="text-logo">Text Logo</button>
            <button class="sub-tab" data-subtab="favicon">Favicon</button>
        </div>

        <!-- Sub-tab: Logo -->
        <div class="sub-panel active" id="subtab-logo">
            <h3>Store Logo</h3>
            <p class="section-description">Upload your store logo. Recommended: 200-400px wide, 60-120px tall. Supports PNG, JPG, SVG, WebP.</p>

            <div class="logo-upload-area">
                <div class="current-logo" id="current-logo">
                    <?php if ($settings['store_logo']): ?>
                        <img src="<?php echo escape($settings['store_logo']); ?>" alt="Store Logo" id="logo-preview">
                        <button type="button" class="btn btn-danger btn-sm remove-logo" id="remove-logo-btn">Remove</button>
                    <?php else: ?>
                        <div class="no-logo"><span>No logo uploaded</span></div>
                    <?php endif; ?>
                </div>
                <div class="upload-controls">
                    <form id="logo-upload-form" enctype="multipart/form-data">
                        <?php echo csrfField(); ?>
                        <input type="file" name="logo" id="logo-input" accept="image/png,image/jpeg,image/svg+xml,image/webp,image/gif" style="display: none;">
                        <button type="button" class="btn btn-secondary" id="upload-logo-btn">
                            <?php echo $settings['store_logo'] ? 'Change Logo' : 'Upload Logo'; ?>
                        </button>
                    </form>
                    <div class="upload-hint">Max file size: 2MB</div>
                </div>
            </div>

            <form id="branding-form" class="settings-form" style="margin-top:20px;">
                <?php echo csrfField(); ?>
                <input type="hidden" name="_form" value="branding">

                <div class="form-group" style="max-width:200px;">
                    <label for="logo-height">Logo Height (px)</label>
                    <div class="input-group">
                        <input type="number" name="logo_height" id="logo-height" class="form-control" value="<?php echo escape(setting('logo_height') ?: ''); ?>" placeholder="52" min="20" max="500" step="1">
                        <span class="input-suffix">px</span>
                    </div>
                    <span class="form-help">Leave empty for default (52px)</span>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Logo Settings</button>
                </div>
            </form>
        </div>

        <!-- Sub-tab: Text Logo -->
        <div class="sub-panel" id="subtab-text-logo">
            <h3>Text Logo</h3>
            <p class="section-description">Use styled text instead of an image for your logo. If set, this overrides the image logo.</p>

            <form id="text-logo-form" class="settings-form">
                <?php echo csrfField(); ?>
                <input type="hidden" name="_form" value="branding">

                <div class="form-row" style="display:flex;gap:16px;align-items:flex-end;">
                    <div class="form-group" style="flex:1;">
                        <label for="logo_text">Logo Text</label>
                        <input type="text" name="logo_text" id="logo_text" class="form-control"
                               value="<?php echo escape(setting('logo_text') ?: ''); ?>"
                               placeholder="e.g. MYSTORE">
                        <span class="form-help">The full text to display as your logo</span>
                    </div>
                    <div class="form-group">
                        <label for="logo_text_color">Text Color</label>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <input type="color" name="logo_text_color" id="logo_text_color"
                                   value="<?php echo escape(setting('logo_text_color') ?: '#FFFFFF'); ?>"
                                   style="width:40px;height:36px;border:1px solid #ddd;border-radius:4px;cursor:pointer;">
                            <input type="text" id="logo_text_color_hex" class="form-control"
                                   value="<?php echo escape(setting('logo_text_color') ?: '#FFFFFF'); ?>"
                                   style="max-width:100px;font-family:monospace;">
                        </div>
                    </div>
                </div>

                <div class="form-row" style="display:flex;gap:16px;align-items:flex-end;">
                    <div class="form-group" style="flex:1;">
                        <label for="logo_text_highlight">Highlight Text <span style="color:#9ca3af;font-weight:normal;">(optional)</span></label>
                        <input type="text" name="logo_text_highlight" id="logo_text_highlight" class="form-control"
                               value="<?php echo escape(setting('logo_text_highlight') ?: ''); ?>"
                               placeholder="e.g. STORE">
                        <span class="form-help">Part of the text to show in a different color</span>
                    </div>
                    <div class="form-group">
                        <label for="logo_text_highlight_color">Highlight Color</label>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <input type="color" name="logo_text_highlight_color" id="logo_text_highlight_color"
                                   value="<?php echo escape(setting('logo_text_highlight_color') ?: '#C47B3A'); ?>"
                                   style="width:40px;height:36px;border:1px solid #ddd;border-radius:4px;cursor:pointer;">
                            <input type="text" id="logo_text_highlight_color_hex" class="form-control"
                                   value="<?php echo escape(setting('logo_text_highlight_color') ?: '#C47B3A'); ?>"
                                   style="max-width:100px;font-family:monospace;">
                        </div>
                    </div>
                </div>

                <div class="form-row" style="display:flex;gap:16px;align-items:flex-end;flex-wrap:wrap;">
                    <div class="form-group" style="flex:1;min-width:200px;max-width:300px;">
                        <label for="logo_text_font">Font</label>
                        <select name="logo_text_font" id="logo_text_font" class="form-control logo-preview-input">
                            <option value="">Use theme heading font</option>
                            <?php
                            $__logoFonts = [
                                'Barlow Condensed', 'Bebas Neue', 'Cinzel', 'Cormorant Garamond',
                                'Crimson Text', 'Dancing Script', 'Great Vibes', 'Inter',
                                'Josefin Sans', 'Lato', 'Libre Baskerville', 'Merriweather',
                                'Montserrat', 'Nunito', 'Orbitron', 'Oswald', 'Philosopher',
                                'Playfair Display', 'Poppins', 'Raleway', 'Righteous',
                                'Roboto', 'Sacramento', 'Source Serif Pro', 'Staatliches', 'Ubuntu',
                            ];
                            $__currentLogoFont = setting('logo_text_font') ?: '';
                            foreach ($__logoFonts as $f): ?>
                                <option value="<?php echo escape($f); ?>"<?php echo $__currentLogoFont === $f ? ' selected' : ''; ?>><?php echo escape($f); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="max-width:100px;">
                        <label for="logo_text_size">Size (px)</label>
                        <input type="number" name="logo_text_size" id="logo_text_size" class="form-control logo-preview-input"
                               value="<?php echo escape(setting('logo_text_size') ?: ''); ?>" placeholder="28" min="12" max="120" step="1">
                    </div>
                    <div class="form-group" style="max-width:140px;">
                        <label for="logo_text_weight">Weight</label>
                        <select name="logo_text_weight" id="logo_text_weight" class="form-control logo-preview-input">
                            <?php $__currentWeight = setting('logo_text_weight') ?: '700'; ?>
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
                        <select name="logo_text_stretch" id="logo_text_stretch" class="form-control logo-preview-input">
                            <?php $__currentStretch = setting('logo_text_stretch') ?: 'normal'; ?>
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
                        <select name="logo_text_spacing" id="logo_text_spacing" class="form-control logo-preview-input">
                            <?php $__currentSpacing = setting('logo_text_spacing') ?: '0.1em'; ?>
                            <option value="0"<?php echo $__currentSpacing === '0' ? ' selected' : ''; ?>>None</option>
                            <option value="0.05em"<?php echo $__currentSpacing === '0.05em' ? ' selected' : ''; ?>>Tight</option>
                            <option value="0.1em"<?php echo $__currentSpacing === '0.1em' ? ' selected' : ''; ?>>Normal</option>
                            <option value="0.2em"<?php echo $__currentSpacing === '0.2em' ? ' selected' : ''; ?>>Wide</option>
                            <option value="0.3em"<?php echo $__currentSpacing === '0.3em' ? ' selected' : ''; ?>>Extra Wide</option>
                        </select>
                    </div>
                </div>

                <!-- Live Preview -->
                <div id="text-logo-preview" style="margin-top:16px;padding:14px 24px;background:var(--navbar-bg, #1a1a2e);border-radius:6px;display:<?php echo setting('logo_text') ? 'inline-block' : 'none'; ?>;">
                    <span id="text-logo-preview-text" style="font-weight:700;letter-spacing:0.1em;text-transform:uppercase;"></span>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Text Logo</button>
                </div>
            </form>
        </div>

        <!-- Sub-tab: Favicon -->
        <div class="sub-panel" id="subtab-favicon">
            <h3>Favicon</h3>
            <p class="section-description">The small icon in browser tabs. Recommended: 32x32 or 64x64 pixels. PNG or ICO.</p>

            <div class="favicon-upload-area">
                <div class="current-favicon" id="current-favicon">
                    <?php if ($settings['store_favicon']): ?>
                        <img src="<?php echo escape($settings['store_favicon']); ?>" alt="Favicon" id="favicon-preview">
                    <?php else: ?>
                        <div class="no-favicon">No favicon</div>
                    <?php endif; ?>
                </div>
                <div class="upload-controls">
                    <form id="favicon-upload-form" enctype="multipart/form-data">
                        <?php echo csrfField(); ?>
                        <input type="file" name="favicon" id="favicon-input" accept="image/png,image/x-icon,.ico" style="display: none;">
                        <button type="button" class="btn btn-secondary btn-sm" id="upload-favicon-btn">
                            <?php echo $settings['store_favicon'] ? 'Change' : 'Upload'; ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tab: SEO -->
<div class="tab-panel" id="tab-seo">
    <div class="settings-card">
        <form id="seo-form" class="settings-form">
            <?php echo csrfField(); ?>

            <div class="form-group">
                <label for="seo_title">Meta Title</label>
                <input type="text" name="seo_title" id="seo_title"
                       value="<?php echo escape($settings['seo_title'] ?? ''); ?>"
                       class="form-control" maxlength="70"
                       placeholder="<?php echo escape($settings['store_name']); ?> - Your tagline here">
                <span class="form-help">Recommended: 50-60 characters. Appears in browser tabs and search results.</span>
                <div class="char-count"><span id="seo-title-count"><?php echo strlen($settings['seo_title'] ?? ''); ?></span>/70</div>
            </div>

            <div class="form-group">
                <label for="seo_description">Meta Description</label>
                <textarea name="seo_description" id="seo_description"
                          class="form-control" rows="3" maxlength="160"
                          placeholder="A compelling description of your store for search engines..."><?php echo escape($settings['seo_description'] ?? ''); ?></textarea>
                <span class="form-help">Recommended: 150-160 characters. Shown in search engine results.</span>
                <div class="char-count"><span id="seo-desc-count"><?php echo strlen($settings['seo_description'] ?? ''); ?></span>/160</div>
            </div>

            <div class="form-group">
                <label for="seo_keywords">Meta Keywords</label>
                <input type="text" name="seo_keywords" id="seo_keywords"
                       value="<?php echo escape($settings['seo_keywords'] ?? ''); ?>"
                       class="form-control" placeholder="online store, products, shopping">
                <span class="form-help">Comma-separated keywords.</span>
            </div>

            <div class="form-group">
                <label>Default Social Image (OG Image)</label>
                <p class="section-description">Image shown when your homepage is shared on social media. Recommended: 1200x630 pixels.</p>

                <div class="og-image-upload-area">
                    <div class="current-og-image" id="current-og-image">
                        <?php if (!empty($settings['seo_og_image'])): ?>
                            <img src="<?php echo escape($settings['seo_og_image']); ?>" alt="OG Image" id="og-image-preview">
                            <button type="button" class="btn btn-danger btn-sm remove-og-image" id="remove-og-image-btn">Remove</button>
                        <?php else: ?>
                            <div class="no-og-image"><span>No image set</span></div>
                        <?php endif; ?>
                    </div>
                    <div class="upload-controls">
                        <input type="file" name="og_image" id="og-image-input" accept="image/png,image/jpeg,image/webp" style="display: none;">
                        <button type="button" class="btn btn-secondary btn-sm" id="upload-og-image-btn">
                            <?php echo !empty($settings['seo_og_image']) ? 'Change Image' : 'Upload Image'; ?>
                        </button>
                        <div class="upload-hint">PNG, JPG, or WebP. Max 2MB.</div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save SEO Settings</button>
            </div>
        </form>
    </div>
</div>

<!-- Tab: Email -->
<div class="tab-panel" id="tab-email">
    <div class="settings-card">
        <form id="email-form" class="settings-form">
            <?php echo csrfField(); ?>

            <h3 style="font-size: 0.95rem; margin: 0 0 16px 0;">Sender Information</h3>

            <div class="form-row">
                <div class="form-group">
                    <label for="mail_from_email">From Email Address</label>
                    <input type="email" name="mail_from_email" id="mail_from_email"
                           value="<?php echo escape($settings['mail_from_email']); ?>"
                           class="form-control" placeholder="orders@yourstore.com">
                    <span class="form-help">Email address customers will see as the sender</span>
                </div>
                <div class="form-group">
                    <label for="mail_from_name">From Name</label>
                    <input type="text" name="mail_from_name" id="mail_from_name"
                           value="<?php echo escape($settings['mail_from_name']); ?>"
                           class="form-control" placeholder="Your Store Name">
                </div>
            </div>

            <div class="form-group">
                <label for="admin_notification_email">Admin Notification Email</label>
                <input type="email" name="admin_notification_email" id="admin_notification_email"
                       value="<?php echo escape($settings['admin_notification_email']); ?>"
                       class="form-control" placeholder="admin@yourstore.com">
                <span class="form-help">Where to send order notifications and admin alerts</span>
            </div>

            <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--admin-border);">
                <h3 style="font-size: 0.95rem; margin: 0 0 16px 0;">SMTP Settings <span style="font-weight: normal; color: var(--admin-text-light);">(Optional)</span></h3>
                <p class="section-description" style="margin-bottom: 16px;">Use an SMTP server for reliable email delivery. If not configured, PHP's built-in mail function is used.</p>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="smtp_enabled" id="smtp_enabled" value="1"
                               <?php echo $settings['smtp_enabled'] === '1' ? 'checked' : ''; ?>>
                        <span>Enable SMTP</span>
                    </label>
                </div>

                <div id="smtp-fields" style="<?php echo $settings['smtp_enabled'] !== '1' ? 'opacity: 0.5; pointer-events: none;' : ''; ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="smtp_host">SMTP Host</label>
                            <input type="text" name="smtp_host" id="smtp_host"
                                   value="<?php echo escape($settings['smtp_host']); ?>"
                                   class="form-control" placeholder="smtp.gmail.com">
                        </div>
                        <div class="form-group">
                            <label for="smtp_port">Port</label>
                            <input type="number" name="smtp_port" id="smtp_port"
                                   value="<?php echo escape($settings['smtp_port'] ?: '587'); ?>"
                                   class="form-control" min="1" max="65535" style="width: 100px;">
                        </div>
                        <div class="form-group">
                            <label for="smtp_encryption">Encryption</label>
                            <select name="smtp_encryption" id="smtp_encryption" class="form-control" style="width: auto;">
                                <option value="tls" <?php echo $settings['smtp_encryption'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                <option value="ssl" <?php echo $settings['smtp_encryption'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                <option value="none" <?php echo $settings['smtp_encryption'] === 'none' ? 'selected' : ''; ?>>None</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="smtp_username">SMTP Username</label>
                            <input type="text" name="smtp_username" id="smtp_username"
                                   value="<?php echo escape($settings['smtp_username']); ?>"
                                   class="form-control" placeholder="your-email@gmail.com" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label for="smtp_password">SMTP Password</label>
                            <input type="password" name="smtp_password" id="smtp_password"
                                   value="<?php echo !empty($settings['smtp_password']) ? '********' : ''; ?>"
                                   class="form-control" placeholder="App password" autocomplete="new-password">
                            <span class="form-help">For Gmail, use an <a href="https://support.google.com/accounts/answer/185833" target="_blank">App Password</a></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                <button type="submit" class="btn btn-primary">Save Email Settings</button>
                <div style="flex: 1; display: flex; gap: 8px; align-items: center;">
                    <input type="email" id="test_email_address" class="form-control" placeholder="Enter email to test..."
                           style="max-width: 250px; margin: 0;">
                    <button type="button" id="send-test-email" class="btn btn-secondary">Send Test</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tab: Tax -->
<div class="tab-panel" id="tab-tax">
    <div class="settings-card">
        <form id="tax-settings-form" class="settings-form">
            <?php echo csrfField(); ?>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="tax_enabled" id="tax_enabled" value="1"
                           <?php echo !empty($settings['tax_enabled']) && $settings['tax_enabled'] !== '0' ? 'checked' : ''; ?>>
                    <span>Enable Tax</span>
                </label>
                <span class="form-help">When enabled, tax will be calculated and added to orders at checkout.</span>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="tax_rate">Tax Rate (%)</label>
                    <input type="number" name="tax_rate" id="tax_rate"
                           value="<?php echo escape($settings['tax_rate'] ?? '0'); ?>"
                           class="form-control" min="0" max="100" step="0.01"
                           placeholder="e.g., 7.5" style="max-width: 150px;">
                    <span class="form-help">Enter as a percentage (e.g., 7.5 for 7.5%)</span>
                </div>
                <div class="form-group">
                    <label for="tax_label">Tax Label</label>
                    <input type="text" name="tax_label" id="tax_label"
                           value="<?php echo escape($settings['tax_label'] ?? 'Tax'); ?>"
                           class="form-control" maxlength="50" placeholder="e.g., Sales Tax, VAT, GST"
                           style="max-width: 200px;">
                    <span class="form-help">Label shown to customers on checkout and receipts</span>
                </div>
            </div>

            <div class="form-group">
                <label for="tax_region">Tax Region (State / Province)</label>
                <input type="text" name="tax_region" id="tax_region"
                       value="<?php echo escape($settings['tax_region'] ?? ''); ?>"
                       class="form-control" maxlength="100" placeholder="e.g., FL, CA, ON"
                       style="max-width: 200px;">
                <span class="form-help">Only charge tax when customer's shipping state matches. Leave blank for all orders.</span>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Tax Settings</button>
            </div>
        </form>
    </div>
</div>

<style>
/* Tabs */
.settings-tabs {
    display: flex;
    gap: 0;
    border-bottom: 2px solid var(--admin-border);
    margin-bottom: 24px;
    overflow-x: auto;
}

.settings-tab {
    padding: 10px 20px;
    border: none;
    background: none;
    color: var(--admin-text-light);
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    white-space: nowrap;
    transition: all 0.15s ease;
}

.settings-tab:hover {
    color: var(--admin-text);
    background: var(--admin-hover-bg, rgba(0,0,0,0.03));
}

.settings-tab.active {
    color: var(--admin-primary);
    border-bottom-color: var(--admin-primary);
}

.tab-panel {
    display: none;
}

.tab-panel.active {
    display: block;
}

/* Sub-tabs */
.sub-tabs {
    display: flex;
    gap: 0;
    border-bottom: 1px solid var(--admin-border);
    margin: -24px -24px 24px -24px;
    padding: 0 24px;
}

.sub-tab {
    padding: 10px 16px;
    border: none;
    background: none;
    color: var(--admin-text-light);
    font-size: 0.8rem;
    font-weight: 500;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
    white-space: nowrap;
    transition: all 0.15s ease;
}

.sub-tab:hover {
    color: var(--admin-text);
}

.sub-tab.active {
    color: var(--admin-primary);
    border-bottom-color: var(--admin-primary);
}

.sub-panel {
    display: none;
}

.sub-panel.active {
    display: block;
}

/* Card & Form */
.settings-card {
    background: var(--admin-card-bg);
    border-radius: var(--admin-radius);
    padding: 24px;
    margin-bottom: 24px;
}

.settings-card h3 {
    margin: 0 0 8px 0;
    font-size: 1rem;
}

.form-group {
    margin-bottom: 20px;
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

.form-help {
    display: block;
    font-size: 0.75rem;
    color: var(--admin-text-light);
    margin-top: 4px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.form-actions {
    margin-top: 24px;
    padding-top: 16px;
    border-top: 1px solid var(--admin-border);
}

.section-description {
    font-size: 0.875rem;
    color: var(--admin-text-light);
    margin-bottom: 16px;
}

.logo-upload-area {
    display: flex;
    gap: 24px;
    align-items: flex-start;
}

.current-logo {
    min-width: 200px;
    min-height: 80px;
    background: var(--admin-bg);
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 16px;
    position: relative;
}

.current-logo img { max-width: 200px; max-height: 100px; object-fit: contain; }
.remove-logo { margin-top: 8px; }
.no-logo { color: var(--admin-text-light); font-size: 0.875rem; }
.upload-controls { display: flex; flex-direction: column; gap: 8px; }
.upload-hint { font-size: 0.75rem; color: var(--admin-text-light); }

.favicon-upload-area { display: flex; gap: 16px; align-items: center; }
.current-favicon { width: 48px; height: 48px; background: var(--admin-bg); border-radius: 4px; display: flex; align-items: center; justify-content: center; }
.current-favicon img { max-width: 32px; max-height: 32px; }
.no-favicon { font-size: 0.625rem; color: var(--admin-text-light); text-align: center; }

.checkbox-label { display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 500; }
.checkbox-label input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; }

.char-count { font-size: 0.75rem; color: var(--admin-text-light); text-align: right; margin-top: 4px; }

.og-image-upload-area { display: flex; gap: 24px; align-items: flex-start; }
.current-og-image { min-width: 200px; min-height: 105px; background: var(--admin-bg); border-radius: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 16px; }
.current-og-image img { max-width: 200px; max-height: 105px; object-fit: cover; border-radius: 4px; }
.no-og-image { color: var(--admin-text-light); font-size: 0.875rem; }
.remove-og-image { margin-top: 8px; }

.alert { padding: 12px 16px; border-radius: var(--admin-radius); margin-bottom: 20px; }
.alert-success { background: #d1fae5; color: #065f46; }
.alert-error { background: #fee2e2; color: #991b1b; }

@media (max-width: 1024px) {
    .form-row { grid-template-columns: 1fr; }
}

@media (max-width: 600px) {
    .settings-tabs { gap: 0; }
    .settings-tab { padding: 8px 12px; font-size: 0.8rem; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '<?php echo csrfToken(); ?>';

    // Main tabs
    document.querySelectorAll('.settings-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.settings-tab').forEach(function(t) { t.classList.remove('active'); });
            document.querySelectorAll('.tab-panel').forEach(function(p) { p.classList.remove('active'); });
            this.classList.add('active');
            document.getElementById('tab-' + this.dataset.tab).classList.add('active');
            localStorage.setItem('settings_tab', this.dataset.tab);
        });
    });

    // Restore last tab
    var savedTab = localStorage.getItem('settings_tab');
    if (savedTab) {
        var tabBtn = document.querySelector('.settings-tab[data-tab="' + savedTab + '"]');
        if (tabBtn) tabBtn.click();
    }

    // Sub-tabs
    document.querySelectorAll('.sub-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            var parent = this.closest('.settings-card');
            parent.querySelectorAll('.sub-tab').forEach(function(t) { t.classList.remove('active'); });
            parent.querySelectorAll('.sub-panel').forEach(function(p) { p.classList.remove('active'); });
            this.classList.add('active');
            parent.querySelector('#subtab-' + this.dataset.subtab).classList.add('active');
        });
    });

    // Text logo color syncs
    [['logo_text_color', 'logo_text_color_hex'], ['logo_text_highlight_color', 'logo_text_highlight_color_hex']].forEach(function(pair) {
        var picker = document.getElementById(pair[0]);
        var hex = document.getElementById(pair[1]);
        if (picker && hex) {
            picker.addEventListener('input', function() { hex.value = this.value; });
            hex.addEventListener('input', function() { if (/^#[0-9a-fA-F]{6}$/.test(this.value)) picker.value = this.value; });
        }
    });

    // Live text logo preview — updates on any change
    var _loadedFonts = {};
    function loadGoogleFont(fontName, weight) {
        var key = fontName + ':' + weight;
        if (_loadedFonts[key]) return;
        _loadedFonts[key] = true;
        var weights = [weight, '400', '700'].filter(function(v, i, a) { return a.indexOf(v) === i; }).join(';');
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://fonts.googleapis.com/css2?family=' + fontName.replace(/ /g, '+') + ':wght@' + weights + '&display=swap';
        document.head.appendChild(link);
    }

    function updateTextLogoPreview() {
        var previewDiv = document.getElementById('text-logo-preview');
        var previewText = document.getElementById('text-logo-preview-text');
        var text = document.getElementById('logo_text').value;
        if (!text) { previewDiv.style.display = 'none'; return; }

        var color = document.getElementById('logo_text_color').value;
        var hl = document.getElementById('logo_text_highlight').value;
        var hlColor = document.getElementById('logo_text_highlight_color').value;
        var font = document.getElementById('logo_text_font').value;
        var size = document.getElementById('logo_text_size').value || '28';
        var weight = document.getElementById('logo_text_weight').value || '700';
        var stretch = document.getElementById('logo_text_stretch').value || 'normal';
        var spacing = document.getElementById('logo_text_spacing').value || '0.1em';

        if (font) loadGoogleFont(font, weight);

        var html = '';
        if (hl && text.indexOf(hl) !== -1) {
            var idx = text.indexOf(hl);
            html = escapeHtml(text.substring(0, idx)) +
                   '<span style="color:' + hlColor + '">' + escapeHtml(hl) + '</span>' +
                   escapeHtml(text.substring(idx + hl.length));
        } else {
            html = escapeHtml(text);
        }

        var fontFamily = font ? "'" + font + "'," : '';
        previewText.innerHTML = html;
        previewText.style.cssText = 'font-family:' + fontFamily + "var(--font-heading,'Inter',sans-serif);" +
            'font-size:' + size + 'px;font-weight:' + weight + ';font-stretch:' + stretch +
            ';letter-spacing:' + spacing + ';color:' + color + ';text-transform:uppercase;';
        previewDiv.style.display = 'inline-block';
    }

    // Attach live preview to all relevant inputs
    document.querySelectorAll('#subtab-text-logo .logo-preview-input, #logo_text, #logo_text_color, #logo_text_highlight, #logo_text_highlight_color').forEach(function(el) {
        el.addEventListener('input', updateTextLogoPreview);
        el.addEventListener('change', updateTextLogoPreview);
    });
    // Also on color hex inputs
    ['logo_text_color_hex', 'logo_text_highlight_color_hex'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('input', updateTextLogoPreview);
    });

    // Initial preview render
    updateTextLogoPreview();

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // Generic form submit helper
    function setupFormSubmit(formId, url, successMsg) {
        var form = document.getElementById(formId);
        if (!form) return;
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(form);
            var submitBtn = form.querySelector('button[type="submit"]');
            var originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';

            fetch(url, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new URLSearchParams(formData)
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    showNotification(successMsg, 'success');
                    if (formId === 'branding-form' || formId === 'text-logo-form') {
                        setTimeout(function() { window.location.reload(); }, 800);
                    }
                } else {
                    showNotification(data.error || 'Failed to save', 'error');
                }
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            })
            .catch(function(err) {
                console.error(err);
                showNotification('Error saving settings', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });
    }

    setupFormSubmit('store-info-form', '/admin/settings/update', 'Settings saved successfully');
    setupFormSubmit('branding-form', '/admin/settings/update', 'Logo settings saved');
    setupFormSubmit('text-logo-form', '/admin/settings/update', 'Text logo saved');
    setupFormSubmit('seo-form', '/admin/settings/update-seo', 'SEO settings saved');
    setupFormSubmit('email-form', '/admin/settings/update-email', 'Email settings saved');
    setupFormSubmit('tax-settings-form', '/admin/settings/update', 'Tax settings saved');

    // SMTP toggle
    var smtpCheckbox = document.getElementById('smtp_enabled');
    var smtpFields = document.getElementById('smtp-fields');
    if (smtpCheckbox) {
        smtpCheckbox.addEventListener('change', function() {
            smtpFields.style.opacity = this.checked ? '1' : '0.5';
            smtpFields.style.pointerEvents = this.checked ? 'auto' : 'none';
        });
    }

    // Upload logo
    document.getElementById('upload-logo-btn').addEventListener('click', function() {
        document.getElementById('logo-input').click();
    });

    document.getElementById('logo-input').addEventListener('change', function() {
        if (!this.files.length) return;
        if (this.files[0].size > 8 * 1024 * 1024) {
            showNotification('File is too large (max 8MB)', 'error');
            this.value = '';
            return;
        }
        var btn = document.getElementById('upload-logo-btn');
        var origText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Uploading...';
        var formData = new FormData();
        formData.append('logo', this.files[0]);
        formData.append('_csrf_token', csrfToken);
        fetch('/admin/settings/upload-logo', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(function(r) {
            if (!r.ok) {
                if (r.status === 413) throw new Error('File too large');
                throw new Error('Upload failed (HTTP ' + r.status + ')');
            }
            return r.json();
        })
        .then(function(data) {
            if (data.success) {
                updateLogoPreview(data.path);
                showNotification('Logo uploaded', 'success');
            } else {
                showNotification(data.error || 'Upload failed', 'error');
            }
        })
        .catch(function(err) { showNotification(err.message || 'Upload failed', 'error'); })
        .finally(function() { btn.disabled = false; btn.textContent = origText; });
        this.value = '';
    });

    // Upload favicon
    document.getElementById('upload-favicon-btn').addEventListener('click', function() {
        document.getElementById('favicon-input').click();
    });

    document.getElementById('favicon-input').addEventListener('change', function() {
        if (!this.files.length) return;
        if (this.files[0].size > 8 * 1024 * 1024) {
            showNotification('File is too large (max 8MB)', 'error');
            this.value = '';
            return;
        }
        var btn = document.getElementById('upload-favicon-btn');
        var origText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Uploading...';
        var formData = new FormData();
        formData.append('favicon', this.files[0]);
        formData.append('_csrf_token', csrfToken);
        fetch('/admin/settings/upload-favicon', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(function(r) {
            if (!r.ok) throw new Error('Upload failed (HTTP ' + r.status + ')');
            return r.json();
        })
        .then(function(data) {
            if (data.success) {
                updateFaviconPreview(data.path);
                showNotification('Favicon uploaded', 'success');
            } else {
                showNotification(data.error || 'Upload failed', 'error');
            }
        })
        .catch(function(err) { showNotification(err.message || 'Upload failed', 'error'); })
        .finally(function() { btn.disabled = false; btn.textContent = origText; });
        this.value = '';
    });

    // Remove logo
    document.getElementById('current-logo').addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-logo') || e.target.id === 'remove-logo-btn') {
            if (!confirm('Remove the logo?')) return;
            fetch('/admin/settings/remove-logo', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: '_csrf_token=' + encodeURIComponent(csrfToken)
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    var c = document.getElementById('current-logo');
                    c.innerHTML = '<div class="no-logo"><span>No logo uploaded</span></div>';
                    showNotification('Logo removed', 'success');
                }
            });
        }
    });

    // Upload & remove OG image
    document.getElementById('upload-og-image-btn').addEventListener('click', function() {
        document.getElementById('og-image-input').click();
    });

    document.getElementById('og-image-input').addEventListener('change', function() {
        if (!this.files.length) return;
        if (this.files[0].size > 8 * 1024 * 1024) {
            showNotification('File too large (max 8MB)', 'error');
            this.value = '';
            return;
        }
        var btn = document.getElementById('upload-og-image-btn');
        var origText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Uploading...';
        var formData = new FormData();
        formData.append('og_image', this.files[0]);
        formData.append('_csrf_token', csrfToken);
        fetch('/admin/settings/upload-og-image', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(function(r) {
            if (!r.ok) throw new Error('Upload failed (HTTP ' + r.status + ')');
            return r.json();
        })
        .then(function(data) {
            if (data.success) {
                updateOgImagePreview(data.path);
                showNotification('Social image uploaded', 'success');
            } else {
                showNotification(data.error || 'Upload failed', 'error');
            }
        })
        .catch(function(err) { showNotification(err.message || 'Upload failed', 'error'); })
        .finally(function() { btn.disabled = false; btn.textContent = origText; });
        this.value = '';
    });

    document.getElementById('current-og-image').addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-og-image') || e.target.id === 'remove-og-image-btn') {
            if (!confirm('Remove the social image?')) return;
            fetch('/admin/settings/remove-og-image', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: '_csrf_token=' + encodeURIComponent(csrfToken)
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    document.getElementById('current-og-image').innerHTML = '<div class="no-og-image"><span>No image set</span></div>';
                    showNotification('Social image removed', 'success');
                }
            });
        }
    });

    // Test email
    document.getElementById('send-test-email').addEventListener('click', function() {
        var testEmail = document.getElementById('test_email_address').value.trim();
        var btn = this;
        var origText = btn.textContent;
        if (!testEmail || !testEmail.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            showNotification('Enter a valid email address', 'error');
            return;
        }
        btn.disabled = true;
        btn.textContent = 'Sending...';
        fetch('/admin/settings/test-email', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: '_csrf_token=' + encodeURIComponent(csrfToken) + '&test_email=' + encodeURIComponent(testEmail)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            showNotification(data.success ? (data.message || 'Test email sent!') : (data.error || 'Failed'), data.success ? 'success' : 'error');
            btn.disabled = false;
            btn.textContent = origText;
        })
        .catch(function() { showNotification('Error sending test email', 'error'); btn.disabled = false; btn.textContent = origText; });
    });

    // Character counters
    var seoTitle = document.getElementById('seo_title');
    var seoDesc = document.getElementById('seo_description');
    if (seoTitle) seoTitle.addEventListener('input', function() { document.getElementById('seo-title-count').textContent = this.value.length; });
    if (seoDesc) seoDesc.addEventListener('input', function() { document.getElementById('seo-desc-count').textContent = this.value.length; });

    function updateLogoPreview(path) {
        var c = document.getElementById('current-logo');
        c.innerHTML = '';
        var img = document.createElement('img');
        img.src = path; img.alt = 'Store Logo'; img.id = 'logo-preview';
        c.appendChild(img);
        var btn = document.createElement('button');
        btn.type = 'button'; btn.className = 'btn btn-danger btn-sm remove-logo'; btn.id = 'remove-logo-btn'; btn.textContent = 'Remove';
        c.appendChild(btn);
    }

    function updateFaviconPreview(path) {
        var c = document.getElementById('current-favicon');
        c.innerHTML = '';
        var img = document.createElement('img');
        img.src = path; img.alt = 'Favicon'; img.id = 'favicon-preview';
        c.appendChild(img);
    }

    function updateOgImagePreview(path) {
        var c = document.getElementById('current-og-image');
        c.innerHTML = '';
        var img = document.createElement('img');
        img.src = path; img.alt = 'OG Image'; img.id = 'og-image-preview';
        c.appendChild(img);
        var btn = document.createElement('button');
        btn.type = 'button'; btn.className = 'btn btn-danger btn-sm remove-og-image'; btn.id = 'remove-og-image-btn'; btn.textContent = 'Remove';
        c.appendChild(btn);
    }

    function showNotification(message, type) {
        var existing = document.querySelector('.alert');
        if (existing) existing.remove();
        var alert = document.createElement('div');
        alert.className = 'alert alert-' + type;
        alert.textContent = message;
        document.querySelector('.admin-header').insertAdjacentElement('afterend', alert);
        setTimeout(function() { alert.remove(); }, 5000);
    }
});
</script>
