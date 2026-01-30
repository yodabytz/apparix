<div class="admin-header">
    <h1>Store Settings</h1>
</div>

<?php if ($flash = getFlash('success')): ?>
    <div class="alert alert-success"><?php echo escape($flash); ?></div>
<?php endif; ?>

<?php if ($flash = getFlash('error')): ?>
    <div class="alert alert-error"><?php echo escape($flash); ?></div>
<?php endif; ?>

<div class="settings-layout">
    <div class="settings-main">
        <!-- Store Information -->
        <div class="settings-card">
            <h2>Store Information</h2>
            <form id="store-info-form" class="settings-form">
                <?php echo csrfField(); ?>

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

                <div class="form-group" style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--admin-border);">
                    <label class="checkbox-label">
                        <input type="checkbox" name="maintenance_mode" id="maintenance_mode" value="1"
                               <?php echo !empty($settings['maintenance_mode']) ? 'checked' : ''; ?>>
                        <span>Enable "Coming Soon" Mode</span>
                    </label>
                    <span class="form-help">Show a coming soon splash page to visitors. You can preview the site by adding ?preview=1 to any URL or clicking the year in the splash footer.</span>
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
                    <span class="form-help" style="color: var(--warning-color, #f59e0b);">This option is always enabled on the free tier. <a href="<?php echo \App\Core\License::getUpgradeUrl(); ?>" target="_blank">Upgrade</a> to disable branding.</span>
                    <?php else: ?>
                    <span class="form-help">Display a small credit link in the footer. You can disable this if you prefer a cleaner footer.</span>
                    <?php endif; ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>

        <!-- Branding -->
        <div class="settings-card">
            <h2>Branding</h2>

            <!-- Logo Upload -->
            <div class="branding-section">
                <h3>Store Logo</h3>
                <p class="section-description">Upload your store logo. Recommended size: 200-400px wide, 60-120px tall. Supports PNG, JPG, SVG, WebP.</p>

                <div class="logo-upload-area">
                    <div class="current-logo" id="current-logo">
                        <?php if ($settings['store_logo']): ?>
                            <img src="<?php echo escape($settings['store_logo']); ?>" alt="Store Logo" id="logo-preview">
                            <button type="button" class="btn btn-danger btn-sm remove-logo" id="remove-logo-btn">Remove</button>
                        <?php else: ?>
                            <div class="no-logo">
                                <span>No logo uploaded</span>
                            </div>
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
            </div>

            <!-- Favicon Upload -->
            <div class="branding-section">
                <h3>Favicon</h3>
                <p class="section-description">The small icon shown in browser tabs. Recommended: 32x32 or 64x64 pixels. PNG or ICO format.</p>

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

        <!-- SEO Settings -->
        <div class="settings-card">
            <h2>Homepage SEO</h2>
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
                           class="form-control"
                           placeholder="online store, products, shopping">
                    <span class="form-help">Comma-separated keywords. Less important for modern SEO but still used by some engines.</span>
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
                                <div class="no-og-image">
                                    <span>No image set</span>
                                </div>
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

        <!-- Email Settings -->
        <div class="settings-card">
            <h2>Email Settings</h2>
            <p class="section-description">Configure how your store sends emails. These settings are used for order confirmations, newsletters, and other notifications.</p>

            <form id="email-form" class="settings-form">
                <?php echo csrfField(); ?>

                <h3 style="font-size: 0.95rem; margin: 0 0 16px 0; color: var(--admin-text);">Sender Information</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label for="mail_from_email">From Email Address</label>
                        <input type="email" name="mail_from_email" id="mail_from_email"
                               value="<?php echo escape($settings['mail_from_email']); ?>"
                               class="form-control" placeholder="orders@yourstore.com">
                        <span class="form-help">Email address that customers will see as the sender</span>
                    </div>

                    <div class="form-group">
                        <label for="mail_from_name">From Name</label>
                        <input type="text" name="mail_from_name" id="mail_from_name"
                               value="<?php echo escape($settings['mail_from_name']); ?>"
                               class="form-control" placeholder="Your Store Name">
                        <span class="form-help">The name that will appear in the "From" field</span>
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
                    <h3 style="font-size: 0.95rem; margin: 0 0 16px 0; color: var(--admin-text);">SMTP Settings <span style="font-weight: normal; color: var(--admin-text-light);">(Optional)</span></h3>
                    <p class="section-description" style="margin-bottom: 16px;">Use an SMTP server for reliable email delivery. If not configured, the system will use PHP's built-in mail function.</p>

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
                                    <option value="tls" <?php echo $settings['smtp_encryption'] === 'tls' ? 'selected' : ''; ?>>TLS (Recommended)</option>
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
                                       class="form-control" placeholder="App password or SMTP password" autocomplete="new-password">
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

    <div class="settings-sidebar">
        <div class="settings-card">
            <h3>Quick Links</h3>
            <ul class="quick-links">
                <li><a href="/admin/settings/integrations">Analytics & Integrations</a></li>
                <li><a href="/admin/themes">Theme Settings</a></li>
                <li><a href="/admin/shipping">Shipping Settings</a></li>
                <li><a href="/admin/newsletter">Email Marketing</a></li>
            </ul>
        </div>

        <div class="settings-card">
            <h3>Need Help?</h3>
            <p class="help-text">Configure your store's basic information and email delivery settings here. For payment processing, visit the <a href="/admin/settings/payments" style="color: var(--admin-primary);">Payments</a> settings page.</p>
        </div>
    </div>
</div>

<style>
.settings-layout {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 24px;
    align-items: start;
}

.settings-card {
    background: var(--admin-card-bg);
    border-radius: var(--admin-radius);
    padding: 24px;
    margin-bottom: 24px;
}

.settings-card h2 {
    margin: 0 0 20px 0;
    font-size: 1.25rem;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--admin-border);
}

.settings-card h3 {
    margin: 0 0 12px 0;
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

.branding-section {
    margin-bottom: 32px;
    padding-bottom: 24px;
    border-bottom: 1px solid var(--admin-border);
}

.branding-section:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
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

.current-logo img {
    max-width: 200px;
    max-height: 100px;
    object-fit: contain;
}

.remove-logo {
    margin-top: 8px;
}

.no-logo {
    color: var(--admin-text-light);
    font-size: 0.875rem;
}

.upload-controls {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.upload-hint {
    font-size: 0.75rem;
    color: var(--admin-text-light);
}

.favicon-upload-area {
    display: flex;
    gap: 16px;
    align-items: center;
}

.current-favicon {
    width: 48px;
    height: 48px;
    background: var(--admin-bg);
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.current-favicon img {
    max-width: 32px;
    max-height: 32px;
}

.no-favicon {
    font-size: 0.625rem;
    color: var(--admin-text-light);
    text-align: center;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    font-weight: 500;
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.quick-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.quick-links li {
    margin-bottom: 8px;
}

.quick-links a {
    color: var(--admin-primary);
    text-decoration: none;
}

.quick-links a:hover {
    text-decoration: underline;
}

.help-text {
    font-size: 0.875rem;
    color: var(--admin-text-light);
    line-height: 1.5;
}

.alert {
    padding: 12px 16px;
    border-radius: var(--admin-radius);
    margin-bottom: 20px;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
}

.char-count {
    font-size: 0.75rem;
    color: var(--admin-text-light);
    text-align: right;
    margin-top: 4px;
}

.og-image-upload-area {
    display: flex;
    gap: 24px;
    align-items: flex-start;
}

.current-og-image {
    min-width: 200px;
    min-height: 105px;
    background: var(--admin-bg);
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 16px;
}

.current-og-image img {
    max-width: 200px;
    max-height: 105px;
    object-fit: cover;
    border-radius: 4px;
}

.no-og-image {
    color: var(--admin-text-light);
    font-size: 0.875rem;
}

.remove-og-image {
    margin-top: 8px;
}

@media (max-width: 1024px) {
    .settings-layout {
        grid-template-columns: 1fr;
    }

    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '<?php echo csrfToken(); ?>';

    // Store info form
    const storeForm = document.getElementById('store-info-form');
    storeForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(storeForm);
        const submitBtn = storeForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;

        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving...';

        fetch('/admin/settings/update', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new URLSearchParams(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Settings saved successfully', 'success');
            } else {
                showNotification(data.error || 'Failed to save settings', 'error');
            }
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        })
        .catch(err => {
            console.error(err);
            showNotification('Error saving settings', 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    });

    // Upload logo button
    document.getElementById('upload-logo-btn').addEventListener('click', function() {
        document.getElementById('logo-input').click();
    });

    // Upload favicon button
    document.getElementById('upload-favicon-btn').addEventListener('click', function() {
        document.getElementById('favicon-input').click();
    });

    // Logo upload
    const logoInput = document.getElementById('logo-input');
    logoInput.addEventListener('change', function() {
        if (!this.files.length) return;

        const formData = new FormData();
        formData.append('logo', this.files[0]);
        formData.append('_csrf_token', csrfToken);

        fetch('/admin/settings/upload-logo', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateLogoPreview(data.path);
                showNotification('Logo uploaded successfully', 'success');
                if (data.warning) {
                    showNotification(data.warning, 'warning');
                }
            } else {
                showNotification(data.error || 'Failed to upload logo', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showNotification('Error uploading logo', 'error');
        });

        this.value = '';
    });

    // Favicon upload
    const faviconInput = document.getElementById('favicon-input');
    faviconInput.addEventListener('change', function() {
        if (!this.files.length) return;

        const formData = new FormData();
        formData.append('favicon', this.files[0]);
        formData.append('_csrf_token', csrfToken);

        fetch('/admin/settings/upload-favicon', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateFaviconPreview(data.path);
                showNotification('Favicon uploaded successfully', 'success');
            } else {
                showNotification(data.error || 'Failed to upload favicon', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showNotification('Error uploading favicon', 'error');
        });

        this.value = '';
    });

    // Remove logo button (use event delegation since it might not exist initially)
    document.getElementById('current-logo').addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-logo') || e.target.id === 'remove-logo-btn') {
            removeLogo();
        }
    });

    function updateLogoPreview(path) {
        const container = document.getElementById('current-logo');
        // Clear existing content
        while (container.firstChild) {
            container.removeChild(container.firstChild);
        }

        // Create image element safely
        const img = document.createElement('img');
        img.src = path;
        img.alt = 'Store Logo';
        img.id = 'logo-preview';
        container.appendChild(img);

        // Create remove button
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-danger btn-sm remove-logo';
        btn.id = 'remove-logo-btn';
        btn.textContent = 'Remove';
        container.appendChild(btn);
    }

    function updateFaviconPreview(path) {
        const container = document.getElementById('current-favicon');
        // Clear existing content
        while (container.firstChild) {
            container.removeChild(container.firstChild);
        }

        // Create image element safely
        const img = document.createElement('img');
        img.src = path;
        img.alt = 'Favicon';
        img.id = 'favicon-preview';
        container.appendChild(img);
    }

    function removeLogo() {
        if (!confirm('Are you sure you want to remove the logo?')) return;

        fetch('/admin/settings/remove-logo', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: '_csrf_token=' + encodeURIComponent(csrfToken)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const container = document.getElementById('current-logo');
                // Clear existing content
                while (container.firstChild) {
                    container.removeChild(container.firstChild);
                }

                // Create no-logo placeholder
                const div = document.createElement('div');
                div.className = 'no-logo';
                const span = document.createElement('span');
                span.textContent = 'No logo uploaded';
                div.appendChild(span);
                container.appendChild(div);

                showNotification('Logo removed', 'success');
            } else {
                showNotification(data.error || 'Failed to remove logo', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showNotification('Error removing logo', 'error');
        });
    }

    // SEO form
    const seoForm = document.getElementById('seo-form');
    seoForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(seoForm);
        const submitBtn = seoForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;

        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving...';

        fetch('/admin/settings/update-seo', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new URLSearchParams(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('SEO settings saved successfully', 'success');
            } else {
                showNotification(data.error || 'Failed to save SEO settings', 'error');
            }
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        })
        .catch(err => {
            console.error(err);
            showNotification('Error saving SEO settings', 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    });

    // Character counters
    document.getElementById('seo_title').addEventListener('input', function() {
        document.getElementById('seo-title-count').textContent = this.value.length;
    });
    document.getElementById('seo_description').addEventListener('input', function() {
        document.getElementById('seo-desc-count').textContent = this.value.length;
    });

    // Upload OG image button
    document.getElementById('upload-og-image-btn').addEventListener('click', function() {
        document.getElementById('og-image-input').click();
    });

    // OG image upload
    const ogImageInput = document.getElementById('og-image-input');
    ogImageInput.addEventListener('change', function() {
        if (!this.files.length) return;

        const formData = new FormData();
        formData.append('og_image', this.files[0]);
        formData.append('_csrf_token', csrfToken);

        fetch('/admin/settings/upload-og-image', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateOgImagePreview(data.path);
                showNotification('Social image uploaded successfully', 'success');
            } else {
                showNotification(data.error || 'Failed to upload image', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showNotification('Error uploading image', 'error');
        });

        this.value = '';
    });

    // Remove OG image
    document.getElementById('current-og-image').addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-og-image') || e.target.id === 'remove-og-image-btn') {
            removeOgImage();
        }
    });

    function updateOgImagePreview(path) {
        const container = document.getElementById('current-og-image');
        while (container.firstChild) {
            container.removeChild(container.firstChild);
        }

        const img = document.createElement('img');
        img.src = path;
        img.alt = 'OG Image';
        img.id = 'og-image-preview';
        container.appendChild(img);

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-danger btn-sm remove-og-image';
        btn.id = 'remove-og-image-btn';
        btn.textContent = 'Remove';
        container.appendChild(btn);
    }

    function removeOgImage() {
        if (!confirm('Are you sure you want to remove the social image?')) return;

        fetch('/admin/settings/remove-og-image', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: '_csrf_token=' + encodeURIComponent(csrfToken)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const container = document.getElementById('current-og-image');
                while (container.firstChild) {
                    container.removeChild(container.firstChild);
                }

                const div = document.createElement('div');
                div.className = 'no-og-image';
                const span = document.createElement('span');
                span.textContent = 'No image set';
                div.appendChild(span);
                container.appendChild(div);

                showNotification('Social image removed', 'success');
            } else {
                showNotification(data.error || 'Failed to remove image', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showNotification('Error removing image', 'error');
        });
    }

    function showNotification(message, type) {
        const existing = document.querySelector('.alert');
        if (existing) existing.remove();

        const alert = document.createElement('div');
        alert.className = 'alert alert-' + type;
        alert.textContent = message;
        document.querySelector('.admin-header').insertAdjacentElement('afterend', alert);

        setTimeout(function() { alert.remove(); }, 5000);
    }

    // Email Settings Form
    const emailForm = document.getElementById('email-form');
    const smtpEnabledCheckbox = document.getElementById('smtp_enabled');
    const smtpFields = document.getElementById('smtp-fields');

    // Toggle SMTP fields visibility
    smtpEnabledCheckbox.addEventListener('change', function() {
        if (this.checked) {
            smtpFields.style.opacity = '1';
            smtpFields.style.pointerEvents = 'auto';
        } else {
            smtpFields.style.opacity = '0.5';
            smtpFields.style.pointerEvents = 'none';
        }
    });

    // Submit email settings
    emailForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(emailForm);
        const submitBtn = emailForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;

        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving...';

        fetch('/admin/settings/update-email', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new URLSearchParams(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Email settings saved successfully', 'success');
            } else {
                showNotification(data.error || 'Failed to save email settings', 'error');
            }
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        })
        .catch(err => {
            console.error(err);
            showNotification('Error saving email settings', 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    });

    // Test email button
    document.getElementById('send-test-email').addEventListener('click', function() {
        const testEmailInput = document.getElementById('test_email_address');
        const testEmail = testEmailInput.value.trim();
        const btn = this;
        const originalText = btn.textContent;

        if (!testEmail) {
            showNotification('Please enter an email address to test', 'error');
            testEmailInput.focus();
            return;
        }

        if (!testEmail.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            showNotification('Please enter a valid email address', 'error');
            testEmailInput.focus();
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Sending...';

        fetch('/admin/settings/test-email', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: '_csrf_token=' + encodeURIComponent(csrfToken) + '&test_email=' + encodeURIComponent(testEmail)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message || 'Test email sent successfully!', 'success');
            } else {
                showNotification(data.error || 'Failed to send test email', 'error');
            }
            btn.disabled = false;
            btn.textContent = originalText;
        })
        .catch(err => {
            console.error(err);
            showNotification('Error sending test email', 'error');
            btn.disabled = false;
            btn.textContent = originalText;
        });
    });
});
</script>
