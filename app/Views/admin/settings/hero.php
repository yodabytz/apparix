<div class="admin-header">
    <h1>Hero Section Settings</h1>
    <p class="admin-subtitle">Customize your homepage hero section content and effects</p>
</div>

<?php if ($flash = getFlash('success')): ?>
    <div class="alert alert-success"><?php echo escape($flash); ?></div>
<?php endif; ?>

<?php if ($flash = getFlash('error')): ?>
    <div class="alert alert-error"><?php echo escape($flash); ?></div>
<?php endif; ?>

<div class="hero-settings-layout">
    <div class="hero-settings-main">
        <form id="hero-form">
            <?php echo csrfField(); ?>

            <!-- Text Content -->
            <div class="settings-card">
                <div class="card-header">
                    <h2>Text Content</h2>
                </div>

                <div class="form-group">
                    <label for="hero_heading">Heading</label>
                    <input type="text" name="hero_heading" id="hero_heading"
                           value="<?php echo escape($settings['hero_heading']); ?>"
                           class="form-control" placeholder="Welcome to {store_name}">
                    <span class="form-help">Use <code>{store_name}</code> to insert your store name dynamically</span>
                </div>

                <div class="form-group">
                    <label for="hero_taglines">Taglines</label>
                    <textarea name="hero_taglines" id="hero_taglines" class="form-control" rows="5"
                              placeholder="One tagline per line..."><?php echo escape(implode("\n", $settings['hero_taglines'])); ?></textarea>
                    <span class="form-help">Enter one tagline per line. These will rotate if enabled below.</span>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="hero_cta_text">Button Text</label>
                        <input type="text" name="hero_cta_text" id="hero_cta_text"
                               value="<?php echo escape($settings['hero_cta_text']); ?>"
                               class="form-control" placeholder="Shop Now">
                    </div>

                    <div class="form-group">
                        <label for="hero_cta_url">Button URL</label>
                        <input type="text" name="hero_cta_url" id="hero_cta_url"
                               value="<?php echo escape($settings['hero_cta_url']); ?>"
                               class="form-control" placeholder="/products">
                    </div>
                </div>
            </div>

            <!-- Visual Effects -->
            <div class="settings-card">
                <div class="card-header">
                    <h2>Visual Effects</h2>
                </div>

                <div class="form-group">
                    <label for="hero_background_style">Background Style</label>
                    <select name="hero_background_style" id="hero_background_style" class="form-control">
                        <?php foreach ($backgroundStyles as $value => $label): ?>
                        <option value="<?php echo escape($value); ?>" <?php echo $settings['hero_background_style'] === $value ? 'selected' : ''; ?>>
                            <?php echo escape($label); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group image-upload-group" id="imageUploadGroup" style="<?php echo $settings['hero_background_style'] !== 'image' ? 'display:none;' : ''; ?>">
                    <label>Background Image</label>
                    <div class="image-upload-area">
                        <?php $heroImage = setting('hero_background_image'); ?>
                        <div class="image-preview" id="heroImagePreview">
                            <?php if ($heroImage): ?>
                            <img src="<?php echo escape($heroImage); ?>" alt="Hero Background" id="heroPreviewImg">
                            <?php else: ?>
                            <div class="no-image" id="noImagePlaceholder">No image uploaded</div>
                            <?php endif; ?>
                        </div>
                        <div class="upload-controls">
                            <input type="file" id="heroImageInput" accept="image/jpeg,image/png,image/webp" style="display:none;">
                            <button type="button" class="btn btn-secondary btn-sm" id="uploadHeroImageBtn">
                                <?php echo $heroImage ? 'Change Image' : 'Upload Image'; ?>
                            </button>
                            <span class="form-help">Recommended: 1920x600px, max 5MB</span>
                        </div>
                    </div>
                </div>

                <div class="effects-grid">
                    <div class="effect-toggle">
                        <label class="toggle-label">
                            <input type="checkbox" name="hero_show_glow" value="1" <?php echo $settings['hero_show_glow'] ? 'checked' : ''; ?>>
                            <span class="toggle-switch"></span>
                            <span class="toggle-text">Ambient Glow Effect</span>
                        </label>
                        <span class="effect-description">Subtle colored glow behind the content</span>
                    </div>

                    <div class="effect-toggle">
                        <label class="toggle-label">
                            <input type="checkbox" name="hero_show_shimmer" value="1" <?php echo $settings['hero_show_shimmer'] ? 'checked' : ''; ?>>
                            <span class="toggle-switch"></span>
                            <span class="toggle-text">Shimmer Animation</span>
                        </label>
                        <span class="effect-description">Moving light reflection effect</span>
                    </div>

                    <div class="effect-toggle">
                        <label class="toggle-label">
                            <input type="checkbox" name="hero_rotate_taglines" value="1" <?php echo $settings['hero_rotate_taglines'] ? 'checked' : ''; ?>>
                            <span class="toggle-switch"></span>
                            <span class="toggle-text">Rotate Taglines</span>
                        </label>
                        <span class="effect-description">Automatically cycle through taglines</span>
                    </div>
                </div>

                <div class="form-row" id="taglineIntervalRow" style="<?php echo !$settings['hero_rotate_taglines'] ? 'display:none;' : ''; ?>">
                    <div class="form-group">
                        <label for="hero_tagline_interval">Tagline Rotation Interval</label>
                        <div class="input-with-suffix">
                            <input type="number" name="hero_tagline_interval" id="hero_tagline_interval"
                                   value="<?php echo escape($settings['hero_tagline_interval']); ?>"
                                   class="form-control" min="3" max="30" step="1">
                            <span class="input-suffix">seconds</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="hero_overlay_opacity">Glow Intensity</label>
                        <select name="hero_overlay_opacity" id="hero_overlay_opacity" class="form-control">
                            <option value="0.06" <?php echo $settings['hero_overlay_opacity'] === '0.06' ? 'selected' : ''; ?>>Subtle</option>
                            <option value="0.12" <?php echo $settings['hero_overlay_opacity'] === '0.12' ? 'selected' : ''; ?>>Medium (Default)</option>
                            <option value="0.18" <?php echo $settings['hero_overlay_opacity'] === '0.18' ? 'selected' : ''; ?>>Strong</option>
                            <option value="0.25" <?php echo $settings['hero_overlay_opacity'] === '0.25' ? 'selected' : ''; ?>>Intense</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="/" target="_blank" class="btn btn-secondary">Preview Homepage</a>
            </div>
        </form>
    </div>

    <div class="hero-settings-sidebar">
        <div class="settings-card preview-card">
            <h3>Live Preview</h3>
            <div class="hero-preview" id="heroPreview">
                <div class="preview-content">
                    <h1 id="previewHeading"><?php echo escape(str_replace('{store_name}', appName(), $settings['hero_heading'])); ?></h1>
                    <p id="previewTagline"><?php echo escape($settings['hero_taglines'][0] ?? 'Your tagline here'); ?></p>
                    <span class="preview-btn" id="previewBtn"><?php echo escape($settings['hero_cta_text']); ?></span>
                </div>
            </div>
        </div>

        <div class="settings-card">
            <h3>Quick Links</h3>
            <ul class="quick-links">
                <li><a href="/admin/settings">Store Settings</a></li>
                <li><a href="/admin/themes">Theme Settings</a></li>
            </ul>
        </div>

        <div class="settings-card tip-card">
            <h3>Tips</h3>
            <ul class="tips-list">
                <li>Keep your heading short and impactful</li>
                <li>Use 3-5 taglines for variety</li>
                <li>Make your CTA button action-oriented</li>
            </ul>
        </div>
    </div>
</div>

<style>
.admin-subtitle {
    color: var(--admin-text-light);
    font-size: 0.9rem;
    margin: 4px 0 0 0;
}

.hero-settings-layout {
    display: grid;
    grid-template-columns: 1fr 350px;
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
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--admin-border);
}

.card-header h2 {
    margin: 0;
    font-size: 1.1rem;
}

.settings-card h3 {
    margin: 0 0 16px 0;
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
}

.form-control:focus {
    outline: none;
    border-color: var(--admin-primary);
}

.form-help {
    display: block;
    font-size: 0.75rem;
    color: var(--admin-text-light);
    margin-top: 4px;
}

.form-help code {
    background: var(--admin-bg);
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 0.7rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 8px;
}

/* Effects Grid */
.effects-grid {
    display: grid;
    gap: 16px;
    margin-bottom: 20px;
}

.effect-toggle {
    background: var(--admin-bg);
    padding: 16px;
    border-radius: 8px;
    border: 1px solid var(--admin-border);
}

.toggle-label {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    font-weight: 500;
}

.toggle-label input {
    display: none;
}

.toggle-switch {
    width: 44px;
    height: 24px;
    background: #d1d5db;
    border-radius: 12px;
    position: relative;
    transition: background 0.2s;
    flex-shrink: 0;
}

.toggle-switch::after {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    background: white;
    border-radius: 50%;
    top: 2px;
    left: 2px;
    transition: transform 0.2s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

.toggle-label input:checked + .toggle-switch {
    background: var(--admin-primary);
}

.toggle-label input:checked + .toggle-switch::after {
    transform: translateX(20px);
}

.effect-description {
    display: block;
    font-size: 0.75rem;
    color: var(--admin-text-light);
    margin-top: 8px;
    margin-left: 56px;
}

/* Input with suffix */
.input-with-suffix {
    display: flex;
    align-items: center;
    gap: 8px;
}

.input-with-suffix .form-control {
    width: 80px;
}

.input-suffix {
    font-size: 0.875rem;
    color: var(--admin-text-light);
}

/* Image Upload */
.image-upload-area {
    display: flex;
    gap: 16px;
    align-items: flex-start;
}

.image-preview {
    width: 200px;
    height: 80px;
    background: var(--admin-bg);
    border-radius: 8px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-image {
    font-size: 0.75rem;
    color: var(--admin-text-light);
}

/* Hero Preview */
.preview-card {
    position: sticky;
    top: 24px;
}

.hero-preview {
    background: linear-gradient(135deg, #0d0d1a 0%, #1a1a2e 50%, #0d0d1a 100%);
    border-radius: 8px;
    padding: 32px 20px;
    text-align: center;
    position: relative;
    overflow: hidden;
    min-height: 180px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.hero-preview::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at 50% 50%, rgba(255,104,197,0.15) 0%, transparent 60%);
    pointer-events: none;
}

.preview-content {
    position: relative;
    z-index: 1;
}

.hero-preview h1 {
    color: white;
    font-size: 1.1rem;
    margin: 0 0 8px 0;
    font-family: var(--font-heading, 'Playfair Display', serif);
}

.hero-preview p {
    color: rgba(255,255,255,0.8);
    font-size: 0.8rem;
    margin: 0 0 12px 0;
}

.preview-btn {
    display: inline-block;
    background: var(--admin-primary);
    color: white;
    padding: 6px 16px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

/* Quick Links */
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

/* Tip Card */
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
}

.tips-list li {
    margin-bottom: 8px;
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

/* Alerts */
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
    .hero-settings-layout {
        grid-template-columns: 1fr;
    }

    .form-row {
        grid-template-columns: 1fr;
    }

    .preview-card {
        position: static;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('hero-form');
    var csrfToken = '<?php echo csrfToken(); ?>';

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        var submitBtn = form.querySelector('button[type="submit"]');
        var originalText = submitBtn.textContent;

        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving...';

        var formData = new FormData(form);

        fetch('/admin/settings/hero/update', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new URLSearchParams(formData)
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                showNotification('Hero settings saved successfully', 'success');
            } else {
                showNotification(data.error || 'Failed to save settings', 'error');
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

    // Toggle tagline interval visibility
    var rotateToggle = document.querySelector('input[name="hero_rotate_taglines"]');
    var intervalRow = document.getElementById('taglineIntervalRow');

    rotateToggle.addEventListener('change', function() {
        intervalRow.style.display = this.checked ? '' : 'none';
    });

    // Toggle image upload visibility
    var bgStyleSelect = document.getElementById('hero_background_style');
    var imageGroup = document.getElementById('imageUploadGroup');

    bgStyleSelect.addEventListener('change', function() {
        imageGroup.style.display = this.value === 'image' ? '' : 'none';
    });

    // Image upload
    document.getElementById('uploadHeroImageBtn').addEventListener('click', function() {
        document.getElementById('heroImageInput').click();
    });

    document.getElementById('heroImageInput').addEventListener('change', function() {
        if (!this.files.length) return;

        var formData = new FormData();
        formData.append('hero_image', this.files[0]);
        formData.append('_csrf_token', csrfToken);

        fetch('/admin/settings/hero/upload-image', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                var preview = document.getElementById('heroImagePreview');
                var placeholder = document.getElementById('noImagePlaceholder');
                var existingImg = document.getElementById('heroPreviewImg');

                if (placeholder) {
                    placeholder.remove();
                }

                if (existingImg) {
                    existingImg.src = data.path;
                } else {
                    var img = document.createElement('img');
                    img.src = data.path;
                    img.alt = 'Hero Background';
                    img.id = 'heroPreviewImg';
                    preview.appendChild(img);
                }

                showNotification('Image uploaded', 'success');
            } else {
                showNotification(data.error || 'Upload failed', 'error');
            }
        })
        .catch(function(err) {
            console.error(err);
            showNotification('Error uploading image', 'error');
        });

        this.value = '';
    });

    // Live preview updates
    var headingInput = document.getElementById('hero_heading');
    var taglinesInput = document.getElementById('hero_taglines');
    var ctaTextInput = document.getElementById('hero_cta_text');

    var previewHeading = document.getElementById('previewHeading');
    var previewTagline = document.getElementById('previewTagline');
    var previewBtn = document.getElementById('previewBtn');

    var storeName = '<?php echo escape(appName()); ?>';

    headingInput.addEventListener('input', function() {
        previewHeading.textContent = this.value.replace('{store_name}', storeName);
    });

    taglinesInput.addEventListener('input', function() {
        var lines = this.value.split('\n').filter(function(line) { return line.trim(); });
        previewTagline.textContent = lines[0] || 'Your tagline here';
    });

    ctaTextInput.addEventListener('input', function() {
        previewBtn.textContent = this.value || 'Shop Now';
    });

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
