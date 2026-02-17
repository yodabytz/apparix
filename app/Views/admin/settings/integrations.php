<div class="admin-header">
    <h1>Analytics & Integrations</h1>
    <p class="admin-subtitle">Connect your tracking pixels and analytics services</p>
</div>

<?php if ($flash = getFlash('success')): ?>
    <div class="alert alert-success"><?php echo escape($flash); ?></div>
<?php endif; ?>

<?php if ($flash = getFlash('error')): ?>
    <div class="alert alert-error"><?php echo escape($flash); ?></div>
<?php endif; ?>

<form id="integrations-form" class="integrations-form">
    <?php echo csrfField(); ?>

    <div class="integrations-layout">
        <div class="integrations-main">
            <!-- Google Services -->
            <div class="settings-card">
                <div class="card-header">
                    <div class="card-icon google-icon">
                        <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                    </div>
                    <h2>Google Services</h2>
                </div>

                <div class="form-group">
                    <label for="google_tag_manager_id">Google Tag Manager ID</label>
                    <input type="text" name="google_tag_manager_id" id="google_tag_manager_id"
                           value="<?php echo escape($settings['google_tag_manager_id']); ?>"
                           class="form-control" placeholder="GTM-XXXXXXX">
                    <span class="form-help">Found in your GTM dashboard. Format: GTM-XXXXXXX</span>
                </div>

                <div class="form-group">
                    <label for="google_analytics_id">Google Analytics 4 ID</label>
                    <input type="text" name="google_analytics_id" id="google_analytics_id"
                           value="<?php echo escape($settings['google_analytics_id']); ?>"
                           class="form-control" placeholder="G-XXXXXXXXXX">
                    <span class="form-help">Measurement ID from GA4. Format: G-XXXXXXXXXX</span>
                </div>

                <div class="form-group">
                    <label for="google_adsense_id">Google AdSense Publisher ID</label>
                    <input type="text" name="google_adsense_id" id="google_adsense_id"
                           value="<?php echo escape($settings['google_adsense_id']); ?>"
                           class="form-control" placeholder="ca-pub-XXXXXXXXXXXXXXXX">
                    <span class="form-help">Publisher ID from AdSense. Format: ca-pub-XXXXXXXXXXXXXXXX</span>
                </div>

                <div class="form-group">
                    <label for="google_ads_conversion_id">Google Ads Conversion ID</label>
                    <input type="text" name="google_ads_conversion_id" id="google_ads_conversion_id"
                           value="<?php echo escape($settings['google_ads_conversion_id']); ?>"
                           class="form-control" placeholder="AW-XXXXXXXXXX">
                    <span class="form-help">Conversion ID from Google Ads. Format: AW-XXXXXXXXXX</span>
                </div>
            </div>

            <!-- Social Media Pixels -->
            <div class="settings-card">
                <div class="card-header">
                    <div class="card-icon social-icon">
                        <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                            <path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/>
                        </svg>
                    </div>
                    <h2>Social Media Pixels</h2>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="facebook_pixel_id">Facebook Pixel ID</label>
                        <input type="text" name="facebook_pixel_id" id="facebook_pixel_id"
                               value="<?php echo escape($settings['facebook_pixel_id']); ?>"
                               class="form-control" placeholder="XXXXXXXXXXXXXXXX">
                        <span class="form-help">16-digit ID from Meta Business Suite</span>
                    </div>

                    <div class="form-group">
                        <label for="tiktok_pixel_id">TikTok Pixel ID</label>
                        <input type="text" name="tiktok_pixel_id" id="tiktok_pixel_id"
                               value="<?php echo escape($settings['tiktok_pixel_id']); ?>"
                               class="form-control" placeholder="XXXXXXXXXXXXXXXXXX">
                        <span class="form-help">Pixel ID from TikTok Ads Manager</span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="pinterest_tag_id">Pinterest Tag ID</label>
                        <input type="text" name="pinterest_tag_id" id="pinterest_tag_id"
                               value="<?php echo escape($settings['pinterest_tag_id']); ?>"
                               class="form-control" placeholder="XXXXXXXXXXXX">
                        <span class="form-help">Tag ID from Pinterest Ads</span>
                    </div>

                    <div class="form-group">
                        <label for="snapchat_pixel_id">Snapchat Pixel ID</label>
                        <input type="text" name="snapchat_pixel_id" id="snapchat_pixel_id"
                               value="<?php echo escape($settings['snapchat_pixel_id']); ?>"
                               class="form-control" placeholder="XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX">
                        <span class="form-help">Pixel ID from Snapchat Ads Manager</span>
                    </div>
                </div>
            </div>

            <!-- Other Tracking -->
            <div class="settings-card">
                <div class="card-header">
                    <div class="card-icon other-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
                            <path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            <path d="M9 12l2 2 4-4"/>
                        </svg>
                    </div>
                    <h2>Other Tracking</h2>
                </div>

                <div class="form-group">
                    <label for="microsoft_uet_tag_id">Microsoft UET Tag ID</label>
                    <input type="text" name="microsoft_uet_tag_id" id="microsoft_uet_tag_id"
                           value="<?php echo escape($settings['microsoft_uet_tag_id']); ?>"
                           class="form-control" placeholder="XXXXXXXXX">
                    <span class="form-help">Universal Event Tracking tag from Microsoft Ads</span>
                </div>
            </div>

            <!-- Custom Scripts -->
            <div class="settings-card">
                <div class="card-header">
                    <div class="card-icon code-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
                            <polyline points="16 18 22 12 16 6"/>
                            <polyline points="8 6 2 12 8 18"/>
                        </svg>
                    </div>
                    <h2>Custom Scripts</h2>
                </div>

                <div class="form-group">
                    <label for="custom_head_scripts">Custom Head Scripts</label>
                    <textarea name="custom_head_scripts" id="custom_head_scripts"
                              class="form-control code-textarea" rows="6"
                              placeholder="<!-- Add any custom scripts to be placed in the <head> section -->"><?php echo escape($settings['custom_head_scripts']); ?></textarea>
                    <span class="form-help">Scripts placed before &lt;/head&gt;. Include full &lt;script&gt; tags.</span>
                </div>

                <div class="form-group">
                    <label for="custom_body_scripts">Custom Body Scripts</label>
                    <textarea name="custom_body_scripts" id="custom_body_scripts"
                              class="form-control code-textarea" rows="6"
                              placeholder="<!-- Add any custom scripts to be placed before </body> -->"><?php echo escape($settings['custom_body_scripts']); ?></textarea>
                    <span class="form-help">Scripts placed before &lt;/body&gt;. Include full &lt;script&gt; tags.</span>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save All Settings</button>
            </div>
        </div>

        <div class="integrations-sidebar">
            <div class="settings-card">
                <h3>Quick Links</h3>
                <ul class="quick-links">
                    <li><a href="/admin/settings">Store Settings</a></li>
                    <li><a href="/admin/themes">Theme Settings</a></li>
                </ul>
            </div>

            <div class="settings-card tip-card">
                <h3>Pro Tips</h3>
                <ul class="tips-list">
                    <li><strong>GTM vs GA4:</strong> If using Google Tag Manager, you can manage GA4 through GTM and leave the GA4 ID field empty here.</li>
                    <li><strong>Testing:</strong> Use your browser's developer tools (Network tab) to verify pixels are firing correctly.</li>
                    <li><strong>Privacy:</strong> Remember to update your privacy policy when adding new tracking services.</li>
                </ul>
            </div>

            <div class="settings-card warning-card">
                <h3>Important</h3>
                <p>Changes take effect immediately on all pages. Test thoroughly after making changes.</p>
            </div>
        </div>
    </div>
</form>

<style>
.admin-subtitle {
    color: var(--admin-text-light);
    font-size: 0.9rem;
    margin: 4px 0 0 0;
}

.integrations-layout {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 24px;
    align-items: start;
}

.settings-card {
    background: var(--admin-card-bg);
    border-radius: var(--admin-radius);
    padding: 24px;
    margin-bottom: 24px;
}

.card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--admin-border);
}

.card-header h2 {
    margin: 0;
    font-size: 1.1rem;
}

.card-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.google-icon {
    background: linear-gradient(135deg, #4285f4, #34a853);
    color: white;
}

.social-icon {
    background: linear-gradient(135deg, #1877f2, #0866ff);
    color: white;
}

.other-icon {
    background: linear-gradient(135deg, #00bcf2, #0078d4);
    color: white;
}

.code-icon {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white;
}

.settings-card h3 {
    margin: 0 0 12px 0;
    font-size: 1rem;
}

.form-group {
    margin-bottom: 20px;
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-group label {
    display: block;
    font-weight: 500;
    margin-bottom: 6px;
    font-size: 0.875rem;
}

.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--admin-border);
    border-radius: 4px;
    font-size: 0.875rem;
    background: var(--admin-bg);
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: var(--admin-primary);
    box-shadow: 0 0 0 3px rgba(var(--admin-primary-rgb), 0.1);
}

.code-textarea {
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 0.8rem;
    line-height: 1.5;
    resize: vertical;
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
    margin-top: 8px;
    padding-top: 16px;
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

.tip-card {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
}

.tip-card h3 {
    color: #166534;
}

.tips-list {
    list-style: none;
    padding: 0;
    margin: 0;
    font-size: 0.8rem;
    color: #166534;
    line-height: 1.5;
}

.tips-list li {
    margin-bottom: 12px;
    padding-left: 16px;
    position: relative;
}

.tips-list li::before {
    content: "";
    position: absolute;
    left: 0;
    top: 6px;
    width: 6px;
    height: 6px;
    background: #22c55e;
    border-radius: 50%;
}

.tips-list li:last-child {
    margin-bottom: 0;
}

.warning-card {
    background: #fef3c7;
    border: 1px solid #fcd34d;
}

.warning-card h3 {
    color: #92400e;
}

.warning-card p {
    font-size: 0.8rem;
    color: #92400e;
    margin: 0;
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

@media (max-width: 1024px) {
    .integrations-layout {
        grid-template-columns: 1fr;
    }

    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('integrations-form');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;

        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving...';

        const formData = new FormData(form);

        fetch('/admin/settings/integrations/update', {
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

    function showNotification(message, type) {
        const existing = document.querySelector('.alert');
        if (existing) existing.remove();

        const alert = document.createElement('div');
        alert.className = 'alert alert-' + type;
        alert.textContent = message;
        document.querySelector('.admin-header').insertAdjacentElement('afterend', alert);

        setTimeout(function() { alert.remove(); }, 5000);
    }
});
</script>
