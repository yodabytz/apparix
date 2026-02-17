<div class="page-header">
    <h1>Add New Product</h1>
    <a href="/admin/products" class="btn btn-outline">Cancel</a>
</div>

<form action="/admin/products/store" method="POST">
    <?php echo csrfField(); ?>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
        <div>
            <div class="card">
                <h3 class="card-title" style="margin-bottom: 1rem;">Product Information</h3>

                <div class="form-group">
                    <label class="form-label" for="name">Product Name *</label>
                    <input type="text" id="name" name="name" class="form-input" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="sku">SKU</label>
                        <input type="text" id="sku" name="sku" class="form-input" placeholder="e.g., PROD-001">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="price">Price *</label>
                        <input type="number" id="price" name="price" class="form-input" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="sale_price">Sale Price</label>
                        <input type="number" id="sale_price" name="sale_price" class="form-input" step="0.01" min="0">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="manufacturer">Manufacturer/Supplier <small style="color: var(--admin-text-light);">(admin only)</small></label>
                    <input type="text" id="manufacturer" name="manufacturer" class="form-input" placeholder="e.g., Aran Sweater Market">
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Description</label>
                    <textarea id="description" name="description" class="form-textarea" rows="6"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="inventory_count">Stock Quantity</label>
                    <input type="number" id="inventory_count" name="inventory_count" class="form-input" value="0" min="0">
                </div>
            </div>
        </div>

        <div>
            <div class="card">
                <h3 class="card-title" style="margin-bottom: 1rem;">Status</h3>

                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" name="is_active" value="1" checked>
                        <span>Active (visible on store)</span>
                    </label>
                </div>

                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" name="featured" value="1">
                        <span>Featured product</span>
                    </label>
                </div>
            </div>

            <div class="card">
                <h3 class="card-title" style="margin-bottom: 1rem;">Digital Product</h3>

                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" name="is_digital" value="1" id="is_digital" onchange="toggleDigitalFields()">
                        <span>This is a digital product</span>
                    </label>
                </div>

                <div id="digital-fields" style="display: none;">
                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" name="is_license_product" value="1">
                            <span>Generates license key on purchase</span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="download_file">Download File Path</label>
                        <input type="text" id="download_file" name="download_file" class="form-input" placeholder="e.g., apparix-v1.0.0.tar.gz">
                        <small style="color: var(--admin-text-light);">Relative to /storage/downloads/</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="download_limit">Download Limit</label>
                        <input type="number" id="download_limit" name="download_limit" class="form-input" value="5" min="1">
                        <small style="color: var(--admin-text-light);">Max downloads per order</small>
                    </div>
                </div>
            </div>

            <script>
            function toggleDigitalFields() {
                var isDigital = document.getElementById('is_digital').checked;
                document.getElementById('digital-fields').style.display = isDigital ? 'block' : 'none';
            }
            </script>

            <div class="card">
                <h3 class="card-title" style="margin-bottom: 1rem;">Categories</h3>

                <?php if (!empty($categories)): ?>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <?php foreach ($categories as $cat): ?>
                            <label class="form-checkbox">
                                <input type="checkbox" name="categories[]" value="<?php echo $cat['id']; ?>">
                                <span><?php echo escape($cat['name']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: var(--admin-text-light); font-size: 0.875rem;">No categories yet</p>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Create Product
            </button>
            <p style="text-align: center; margin-top: 0.75rem; font-size: 0.875rem; color: var(--admin-text-light);">
                You can add images and variants after creating
            </p>
        </div>
    </div>
</form>
