<div class="admin-header">
    <div class="header-left">
        <a href="/admin/themes" class="back-link">&larr; Back to Themes</a>
        <h1>Customize 404 Error Page</h1>
    </div>
</div>

<?php if ($flash = getFlash('success')): ?>
    <div class="alert alert-success"><?php echo escape($flash); ?></div>
<?php endif; ?>
<?php if ($flash = getFlash('error')): ?>
    <div class="alert alert-error"><?php echo escape($flash); ?></div>
<?php endif; ?>

<form method="POST" action="/admin/themes/404" enctype="multipart/form-data">
    <?php echo csrfField(); ?>

    <div class="error-page-layout">
        <!-- Left: Settings -->
        <div class="error-page-sidebar">
            <div class="ep-section">
                <h3>Content</h3>
                <div class="ep-field">
                    <label for="error_404_heading">Heading</label>
                    <input type="text" name="error_404_heading" id="error_404_heading"
                           value="<?php echo escape($settings['error_404_heading']); ?>"
                           class="form-control" maxlength="100"
                           placeholder="Oops! This page doesn't exist">
                </div>
                <div class="ep-field">
                    <label for="error_404_message">Message</label>
                    <textarea name="error_404_message" id="error_404_message"
                              class="form-control" rows="3" maxlength="500"
                              placeholder="The page you're looking for..."><?php echo escape($settings['error_404_message']); ?></textarea>
                </div>
            </div>

            <div class="ep-section">
                <h3>Custom Image</h3>
                <p class="ep-help">Upload an illustration or image. Recommended: PNG, SVG, or WebP. Max 2MB.</p>
                <?php if (!empty($settings['error_404_image'])): ?>
                    <div class="ep-current-image">
                        <img src="<?php echo escape($settings['error_404_image']); ?>" alt="404 Image">
                        <label class="ep-remove-check">
                            <input type="checkbox" name="remove_image" value="1"> Remove image
                        </label>
                    </div>
                <?php endif; ?>
                <input type="file" name="error_404_image" id="error_404_image"
                       accept="image/png,image/jpeg,image/webp,image/svg+xml,image/gif"
                       class="form-control">
            </div>

            <div class="ep-section">
                <h3>Primary Button</h3>
                <div class="ep-field">
                    <label for="error_404_primary_btn_text">Button Text</label>
                    <input type="text" name="error_404_primary_btn_text" id="error_404_primary_btn_text"
                           value="<?php echo escape($settings['error_404_primary_btn_text']); ?>"
                           class="form-control" maxlength="50" placeholder="Back to Home">
                </div>
                <div class="ep-field">
                    <label for="error_404_primary_btn_url">Button URL</label>
                    <input type="text" name="error_404_primary_btn_url" id="error_404_primary_btn_url"
                           value="<?php echo escape($settings['error_404_primary_btn_url']); ?>"
                           class="form-control" maxlength="255" placeholder="/">
                </div>
            </div>

            <div class="ep-section">
                <h3>Secondary Button</h3>
                <div class="ep-field">
                    <label for="error_404_secondary_btn_text">Button Text</label>
                    <input type="text" name="error_404_secondary_btn_text" id="error_404_secondary_btn_text"
                           value="<?php echo escape($settings['error_404_secondary_btn_text']); ?>"
                           class="form-control" maxlength="50" placeholder="Browse Products">
                    <span class="ep-help">Leave empty to hide this button.</span>
                </div>
                <div class="ep-field">
                    <label for="error_404_secondary_btn_url">Button URL</label>
                    <input type="text" name="error_404_secondary_btn_url" id="error_404_secondary_btn_url"
                           value="<?php echo escape($settings['error_404_secondary_btn_url']); ?>"
                           class="form-control" maxlength="255" placeholder="/products">
                </div>
            </div>

            <div class="ep-section">
                <h3>Search Box</h3>
                <label class="ep-toggle">
                    <input type="checkbox" name="error_404_show_search" value="1"
                           <?php echo $settings['error_404_show_search'] ? 'checked' : ''; ?>
                           id="error_404_show_search">
                    <span>Show search box on 404 page</span>
                </label>
            </div>

            <div class="ep-actions">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="/admin/themes" class="btn btn-secondary">Cancel</a>
            </div>
        </div>

        <!-- Right: Live Preview -->
        <div class="error-page-preview">
            <h3>Preview</h3>
            <div class="ep-preview-frame" id="previewFrame">
                <div class="ep-preview-inner">
                    <div class="ep-pv-code">404</div>
                    <div class="ep-pv-image" id="pvImage" style="<?php echo empty($settings['error_404_image']) ? 'display:none;' : ''; ?>">
                        <?php if (!empty($settings['error_404_image'])): ?>
                            <img src="<?php echo escape($settings['error_404_image']); ?>" alt="404" id="pvImageTag">
                        <?php else: ?>
                            <img src="" alt="404" id="pvImageTag" style="display:none;">
                        <?php endif; ?>
                    </div>
                    <div class="ep-pv-icon" id="pvIcon" style="<?php echo !empty($settings['error_404_image']) ? 'display:none;' : ''; ?>">&#x1F50D;</div>
                    <h2 class="ep-pv-heading" id="pvHeading"><?php echo escape($settings['error_404_heading']); ?></h2>
                    <p class="ep-pv-message" id="pvMessage"><?php echo escape($settings['error_404_message']); ?></p>
                    <div class="ep-pv-buttons">
                        <span class="ep-pv-btn-primary" id="pvPrimaryBtn"><?php echo escape($settings['error_404_primary_btn_text']); ?></span>
                        <span class="ep-pv-btn-secondary" id="pvSecondaryBtn" style="<?php echo empty($settings['error_404_secondary_btn_text']) ? 'display:none;' : ''; ?>"><?php echo escape($settings['error_404_secondary_btn_text']); ?></span>
                    </div>
                    <div class="ep-pv-search" id="pvSearch" style="<?php echo !$settings['error_404_show_search'] ? 'display:none;' : ''; ?>">
                        <span class="ep-pv-search-label">Or try searching for what you need:</span>
                        <div class="ep-pv-search-box">
                            <span class="ep-pv-search-input">Search products...</span>
                            <span class="ep-pv-search-btn">Search</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
.error-page-layout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    align-items: start;
}

.error-page-sidebar {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    padding: 24px;
}

.ep-section {
    margin-bottom: 24px;
    padding-bottom: 24px;
    border-bottom: 1px solid #f3f4f6;
}

.ep-section:last-of-type {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.ep-section h3 {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 12px;
}

.ep-field {
    margin-bottom: 14px;
}

.ep-field:last-child {
    margin-bottom: 0;
}

.ep-field label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 4px;
}

.ep-help {
    font-size: 0.8rem;
    color: #6b7280;
    margin-top: 4px;
}

.ep-current-image {
    margin-bottom: 12px;
    padding: 12px;
    background: #f9fafb;
    border-radius: 8px;
    text-align: center;
}

.ep-current-image img {
    max-width: 200px;
    max-height: 120px;
    border-radius: 6px;
    margin-bottom: 8px;
}

.ep-remove-check {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-size: 0.85rem;
    color: #dc2626;
    cursor: pointer;
}

.ep-toggle {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-size: 0.9rem;
    color: #374151;
}

.ep-actions {
    display: flex;
    gap: 12px;
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

/* Preview Panel */
.error-page-preview {
    position: sticky;
    top: 20px;
}

.error-page-preview h3 {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 12px;
}

.ep-preview-frame {
    background: linear-gradient(135deg, #f3f8fc 0%, #fff 50%, #f3f8fc 100%);
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    padding: 40px 24px;
    text-align: center;
    min-height: 400px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.ep-preview-inner {
    max-width: 350px;
}

.ep-pv-code {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 5rem;
    font-weight: 600;
    background: linear-gradient(135deg, var(--admin-primary, #3b82f6), #93c5fd);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    line-height: 1;
    margin-bottom: 8px;
}

.ep-pv-icon {
    font-size: 2.5rem;
    margin-bottom: 12px;
}

.ep-pv-image img {
    max-width: 180px;
    max-height: 120px;
    margin-bottom: 12px;
    border-radius: 6px;
}

.ep-pv-heading {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 1.2rem;
    color: #1f2937;
    margin-bottom: 8px;
    font-weight: 600;
}

.ep-pv-message {
    font-size: 0.85rem;
    color: #6b7280;
    line-height: 1.5;
    margin-bottom: 16px;
}

.ep-pv-buttons {
    display: flex;
    gap: 8px;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 16px;
}

.ep-pv-btn-primary {
    background: linear-gradient(135deg, var(--admin-primary, #3b82f6), #93c5fd);
    color: white;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 500;
}

.ep-pv-btn-secondary {
    background: white;
    color: var(--admin-primary, #3b82f6);
    border: 2px solid var(--admin-primary, #3b82f6);
    padding: 6px 14px;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 500;
}

.ep-pv-search {
    border-top: 1px solid #e5e7eb;
    padding-top: 14px;
    margin-top: 8px;
}

.ep-pv-search-label {
    font-size: 0.75rem;
    color: #6b7280;
    display: block;
    margin-bottom: 8px;
}

.ep-pv-search-box {
    display: flex;
    gap: 4px;
    max-width: 240px;
    margin: 0 auto;
}

.ep-pv-search-input {
    flex: 1;
    padding: 6px 10px;
    border: 2px solid #e5e5e5;
    border-radius: 6px;
    font-size: 0.75rem;
    color: #9ca3af;
    text-align: left;
}

.ep-pv-search-btn {
    padding: 6px 10px;
    background: var(--admin-primary, #3b82f6);
    color: white;
    border-radius: 6px;
    font-size: 0.75rem;
}

@media (max-width: 900px) {
    .error-page-layout {
        grid-template-columns: 1fr;
    }
    .error-page-preview {
        position: static;
    }
}
</style>

<script>
(function() {
    // Live preview updates
    const heading = document.getElementById('error_404_heading');
    const message = document.getElementById('error_404_message');
    const primaryBtnText = document.getElementById('error_404_primary_btn_text');
    const secondaryBtnText = document.getElementById('error_404_secondary_btn_text');
    const showSearch = document.getElementById('error_404_show_search');
    const imageInput = document.getElementById('error_404_image');

    const pvHeading = document.getElementById('pvHeading');
    const pvMessage = document.getElementById('pvMessage');
    const pvPrimaryBtn = document.getElementById('pvPrimaryBtn');
    const pvSecondaryBtn = document.getElementById('pvSecondaryBtn');
    const pvSearch = document.getElementById('pvSearch');
    const pvImage = document.getElementById('pvImage');
    const pvIcon = document.getElementById('pvIcon');
    const pvImageTag = document.getElementById('pvImageTag');

    if (heading) heading.addEventListener('input', function() {
        pvHeading.textContent = this.value || "Oops! This page doesn't exist";
    });

    if (message) message.addEventListener('input', function() {
        pvMessage.textContent = this.value || "The page you're looking for doesn't exist or has been moved.";
    });

    if (primaryBtnText) primaryBtnText.addEventListener('input', function() {
        pvPrimaryBtn.textContent = this.value || 'Back to Home';
    });

    if (secondaryBtnText) secondaryBtnText.addEventListener('input', function() {
        if (this.value) {
            pvSecondaryBtn.textContent = this.value;
            pvSecondaryBtn.style.display = '';
        } else {
            pvSecondaryBtn.style.display = 'none';
        }
    });

    if (showSearch) showSearch.addEventListener('change', function() {
        pvSearch.style.display = this.checked ? '' : 'none';
    });

    if (imageInput) imageInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                pvImageTag.src = e.target.result;
                pvImageTag.style.display = '';
                pvImage.style.display = '';
                pvIcon.style.display = 'none';
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
})();
</script>
