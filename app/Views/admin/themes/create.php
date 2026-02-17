<div class="admin-header">
    <div class="header-left">
        <a href="/admin/themes" class="back-link">&larr; Back to Themes</a>
        <h1>Create New Theme</h1>
    </div>
</div>

<form id="theme-form" class="theme-customize-form">
    <?php echo csrfField(); ?>

    <div class="customize-layout">
        <div class="customize-sidebar">
            <div class="form-section">
                <h3>Theme Name</h3>
                <input type="text" name="name" id="theme-name" value="My Custom Theme"
                       class="form-control" required placeholder="Enter theme name">
                <input type="text" name="description" id="theme-description"
                       class="form-control" style="margin-top: 8px;"
                       placeholder="Brief description (optional)">
            </div>

            <div class="form-section">
                <h3>Colors</h3>
                <p class="section-help">Choose your brand colors. Lighter and darker shades will be generated automatically.</p>

                <div class="color-picker-group">
                    <label>Primary Color</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="primary_color" id="primary-color" value="#FF68C5" class="color-picker">
                        <input type="text" id="primary-color-hex" value="#FF68C5" class="color-hex-input">
                    </div>
                    <span class="color-description">Main brand color - buttons, links, accents</span>
                </div>

                <div class="color-picker-group">
                    <label>Secondary Color</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="secondary_color" id="secondary-color" value="#FF94C8" class="color-picker">
                        <input type="text" id="secondary-color-hex" value="#FF94C8" class="color-hex-input">
                    </div>
                    <span class="color-description">Hover states, gradients, highlights</span>
                </div>

                <div class="color-picker-group">
                    <label>Accent Color</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="accent_color" id="accent-color" value="#FFE4F3" class="color-picker">
                        <input type="text" id="accent-color-hex" value="#FFE4F3" class="color-hex-input">
                    </div>
                    <span class="color-description">Subtle backgrounds, borders</span>
                </div>
            </div>

            <div class="form-section">
                <h3>Typography</h3>

                <div class="form-group">
                    <label for="heading-font">Heading Font</label>
                    <select name="heading_font" id="heading-font" class="form-control">
                        <?php foreach ($fonts['heading'] as $font => $label): ?>
                            <option value="<?php echo escape($font); ?>"
                                <?php echo ($font == 'Playfair Display') ? 'selected' : ''; ?>>
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
                                <?php echo ($font == 'Inter') ? 'selected' : ''; ?>>
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
                        <option value="sidebar">Sidebar - Category filter on left</option>
                        <option value="full-width">Full Width - Maximum content</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="header-style">Header Style</label>
                    <select name="header_style" id="header-style" class="form-control">
                        <option value="standard">Standard - Logo left</option>
                        <option value="centered">Centered - Logo center</option>
                        <option value="minimal">Minimal - Compact</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="category-layout">Product Listing</label>
                    <select name="category_layout" id="category-layout" class="form-control">
                        <option value="grid">Grid</option>
                        <option value="list">List</option>
                        <option value="masonry">Masonry</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="product-grid-columns">Grid Columns</label>
                    <select name="product_grid_columns" id="product-grid-columns" class="form-control">
                        <option value="3">3 Columns</option>
                        <option value="4" selected>4 Columns</option>
                        <option value="5">5 Columns</option>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <label class="checkbox-label">
                    <input type="checkbox" name="activate" id="activate-theme" checked>
                    Activate theme after creating
                </label>
                <button type="submit" class="btn btn-primary btn-lg">Create Theme</button>
            </div>
        </div>

        <div class="customize-preview">
            <h3>Live Preview</h3>
            <div class="preview-frame" id="preview-frame">
                <div class="preview-header" id="preview-header">
                    <div class="preview-logo" id="preview-logo">Your Store</div>
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
.back-link { color: var(--admin-text-light); text-decoration: none; font-size: 0.875rem; }
.back-link:hover { color: var(--admin-primary); }
.header-left { display: flex; flex-direction: column; gap: 4px; }
.customize-layout { display: grid; grid-template-columns: 400px 1fr; gap: 32px; align-items: start; }
.customize-sidebar { background: var(--admin-card-bg); border-radius: var(--admin-radius); padding: 24px; }
.form-section { margin-bottom: 28px; padding-bottom: 28px; border-bottom: 1px solid var(--admin-border); }
.form-section:last-of-type { border-bottom: none; margin-bottom: 0; }
.form-section h3 { margin: 0 0 12px 0; font-size: 1rem; font-weight: 600; }
.section-help { display: block; font-size: 0.75rem; color: var(--admin-text-light); margin-top: 4px; margin-bottom: 12px; }
.color-picker-group { margin-bottom: 16px; }
.color-picker-group label { display: block; font-weight: 500; margin-bottom: 6px; }
.color-input-wrapper { display: flex; gap: 8px; align-items: center; }
.color-picker { width: 50px; height: 40px; padding: 2px; border: 1px solid var(--admin-border); border-radius: 4px; cursor: pointer; }
.color-hex-input { flex: 1; padding: 8px 12px; border: 1px solid var(--admin-border); border-radius: 4px; font-family: monospace; text-transform: uppercase; }
.color-description { display: block; font-size: 0.75rem; color: var(--admin-text-light); margin-top: 4px; }
.form-group { margin-bottom: 16px; }
.form-group label { display: block; font-weight: 500; margin-bottom: 6px; }
.form-control { width: 100%; padding: 10px 12px; border: 1px solid var(--admin-border); border-radius: 4px; font-size: 0.875rem; }
.form-actions { margin-top: 24px; display: flex; flex-direction: column; gap: 16px; }
.checkbox-label { display: flex; align-items: center; gap: 8px; cursor: pointer; }
.btn-lg { padding: 14px 24px; font-size: 1rem; }
.customize-preview { position: sticky; top: 20px; }
.customize-preview h3 { margin: 0 0 16px 0; }
.preview-frame { background: white; border-radius: var(--admin-radius); overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); border: 1px solid var(--admin-border); }
.preview-header { padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; }
.preview-logo { font-weight: 700; font-size: 1.25rem; }
.preview-nav { display: flex; gap: 20px; font-size: 0.875rem; }
.preview-hero { padding: 48px 24px; text-align: center; }
.preview-hero h2 { margin: 0 0 8px 0; font-size: 1.75rem; }
.preview-hero p { margin: 0 0 20px 0; color: #666; }
.preview-btn { padding: 12px 32px; border: none; border-radius: 4px; font-weight: 600; cursor: pointer; color: white; }
.preview-products { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; padding: 24px; background: #f9fafb; }
.preview-product-card { background: white; border-radius: 8px; padding: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
.preview-product-image { aspect-ratio: 1; background: #e5e7eb; border-radius: 4px; margin-bottom: 8px; }
.preview-product-name { font-size: 0.875rem; font-weight: 500; margin-bottom: 4px; }
.preview-product-price { font-weight: 600; }
@media (max-width: 1024px) { .customize-layout { grid-template-columns: 1fr; } .customize-preview { position: static; } }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('theme-form');
    const primaryColor = document.getElementById('primary-color');
    const secondaryColor = document.getElementById('secondary-color');
    const accentColor = document.getElementById('accent-color');
    const primaryHex = document.getElementById('primary-color-hex');
    const secondaryHex = document.getElementById('secondary-color-hex');
    const accentHex = document.getElementById('accent-color-hex');

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

    function updatePreview() {
        const primary = primaryColor.value;
        const secondary = secondaryColor.value;
        const accent = accentColor.value;
        document.getElementById('preview-logo').style.color = primary;
        document.getElementById('preview-hero').style.background = `linear-gradient(135deg, ${accent} 0%, white 100%)`;
        document.getElementById('preview-btn').style.background = primary;
        document.querySelectorAll('.preview-product-price').forEach(el => { el.style.color = primary; });
    }

    updatePreview();

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Creating...';

        fetch('/admin/themes/create', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new URLSearchParams(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect || '/admin/themes';
            } else {
                alert(data.error || 'Failed to create theme');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error creating theme');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    });
});
</script>
