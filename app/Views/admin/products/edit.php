<div class="page-header">
    <h1>Edit Product</h1>
    <div class="action-buttons">
        <a href="/products/<?php echo escape($product['slug']); ?>" target="_blank" class="btn btn-outline">View on Store</a>
        <a href="/admin/products" class="btn btn-outline">Back to Products</a>
    </div>
</div>

<!-- Tabs -->
<div class="tabs">
    <a href="#details" class="tab active" onclick="showTab('details', this)">Details</a>
    <a href="#images" class="tab" onclick="showTab('images', this)">Images (<?php echo count($images); ?>)</a>
    <a href="#options" class="tab" onclick="showTab('options', this)">Options & Variants</a>
    <a href="#shipping" class="tab" onclick="showTab('shipping', this)">Shipping</a>
</div>

<!-- Details Tab -->
<div id="tab-details" class="tab-content active">
    <form action="/admin/products/update" method="POST">
        <?php echo csrfField(); ?>
        <input type="hidden" name="id" value="<?php echo $product['id']; ?>">

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
            <div>
                <div class="card">
                    <h3 class="card-title" style="margin-bottom: 1rem;">Product Information</h3>

                    <div class="form-group">
                        <label class="form-label" for="name">Product Name *</label>
                        <input type="text" id="name" name="name" class="form-input" value="<?php echo escape($product['name']); ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="sku">SKU</label>
                            <input type="text" id="sku" name="sku" class="form-input" value="<?php echo escape($product['sku'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="price">Base Price *</label>
                            <input type="number" id="price" name="price" class="form-input" step="0.01" min="0" value="<?php echo $product['price']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="sale_price">Sale Price</label>
                            <input type="number" id="sale_price" name="sale_price" class="form-input" step="0.01" min="0" value="<?php echo $product['sale_price'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="cost">Your Cost</label>
                            <input type="number" id="cost" name="cost" class="form-input" step="0.01" min="0" value="<?php echo $product['cost'] ?? ''; ?>" placeholder="What you paid" <?php echo ($product['cost_not_applicable'] ?? 0) ? 'disabled' : ''; ?>>
                            <label style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem; font-size: 0.875rem; color: var(--admin-text-light); cursor: pointer;">
                                <input type="checkbox" name="cost_not_applicable" value="1" <?php echo ($product['cost_not_applicable'] ?? 0) ? 'checked' : ''; ?> onchange="document.getElementById('cost').disabled = this.checked; if(this.checked) document.getElementById('cost').value = '';">
                                No cost (digital download, free item, etc.)
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="manufacturer">Manufacturer/Supplier <small style="color: var(--admin-text-light);">(admin only)</small></label>
                        <input type="text" id="manufacturer" name="manufacturer" class="form-input" value="<?php echo escape($product['manufacturer'] ?? ''); ?>" placeholder="e.g., Aran Sweater Market">
                    </div>

                    <?php if ($product['price_min'] && $product['price_max']): ?>
                        <div style="background: var(--admin-bg); padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1rem;">
                            <strong>Price Range:</strong>
                            <?php echo formatPrice($product['price_min']); ?>
                            <?php if ($product['price_min'] != $product['price_max']): ?>
                                - <?php echo formatPrice($product['price_max']); ?>
                            <?php endif; ?>
                            <span style="color: var(--admin-text-light); font-size: 0.875rem;">(based on variant price adjustments)</span>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" class="form-textarea" rows="8"><?php echo escape($product['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="card" style="margin: 1.5rem 0; background: #f8f9fa;">
                        <h3 class="card-title" style="margin-bottom: 1rem; color: var(--admin-primary);">SEO Settings</h3>

                        <div class="form-group">
                            <label class="form-label" for="meta_keywords">SEO Keywords/Tags</label>
                            <input type="text" id="meta_keywords" name="meta_keywords" class="form-input"
                                   value="<?php echo escape($product['meta_keywords'] ?? ''); ?>"
                                   placeholder="handmade, custom, gift, personalized">
                            <small style="color: var(--admin-text-light);">Comma-separated keywords for search engines (max 500 chars)</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="meta_description">SEO Meta Description</label>
                            <textarea id="meta_description" name="meta_description" class="form-textarea" rows="3"
                                      maxlength="320" placeholder="A brief description of this product for search results..."><?php echo escape($product['meta_description'] ?? ''); ?></textarea>
                            <small style="color: var(--admin-text-light);">Brief description for search results (max 320 chars). <span id="metaDescCount">0</span>/320</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="inventory_count">Base Stock Quantity</label>
                        <input type="number" id="inventory_count" name="inventory_count" class="form-input" value="<?php echo $product['inventory_count']; ?>" min="0">
                        <small style="color: var(--admin-text-light);">For products with variants, stock is managed per variant.</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="processing_time">Processing Time</label>
                        <input type="text" id="processing_time" name="processing_time" class="form-input" value="<?php echo escape($product['processing_time'] ?? ''); ?>" placeholder="e.g., 3-5 business days">
                        <small style="color: var(--admin-text-light);">Time to prepare order before shipping. Leave blank to use default.</small>
                    </div>
                </div>
            </div>

            <div>
                <div class="card">
                    <h3 class="card-title" style="margin-bottom: 1rem;">Status</h3>

                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" name="is_active" value="1" <?php echo $product['is_active'] ? 'checked' : ''; ?>>
                            <span>Active (visible on store)</span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" name="featured" value="1" <?php echo $product['featured'] ? 'checked' : ''; ?>>
                            <span>Featured product</span>
                        </label>
                    </div>

                    <div class="form-group" style="margin-top: 1rem;">
                        <label class="form-label" for="sort_order">Display Order</label>
                        <input type="number" id="sort_order" name="sort_order" class="form-input" value="<?php echo $product['sort_order'] ?? 0; ?>" min="0">
                        <small style="color: var(--admin-text-light);">Lower numbers appear first (0 = default)</small>
                    </div>
                </div>

                <div class="card">
                    <h3 class="card-title" style="margin-bottom: 1rem;">Categories</h3>

                    <?php if (!empty($categories)): ?>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <?php foreach ($categories as $cat): ?>
                                <label class="form-checkbox">
                                    <input type="checkbox" name="categories[]" value="<?php echo $cat['id']; ?>"
                                           <?php echo in_array($cat['id'], $productCategoryIds) ? 'checked' : ''; ?>>
                                    <span><?php echo escape($cat['name']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="color: var(--admin-text-light); font-size: 0.875rem;">No categories yet</p>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <h3 class="card-title" style="margin-bottom: 1rem;">Digital Product</h3>

                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" name="is_digital" value="1" id="is_digital"
                                   <?php echo ($product['is_digital'] ?? 0) ? 'checked' : ''; ?>
                                   onchange="toggleDigitalFields()">
                            <span>This is a digital product</span>
                        </label>
                    </div>

                    <div id="digital-fields" style="display: <?php echo ($product['is_digital'] ?? 0) ? 'block' : 'none'; ?>;">
                        <div class="form-group">
                            <label class="form-checkbox">
                                <input type="checkbox" name="is_license_product" value="1"
                                       <?php echo ($product['is_license_product'] ?? 0) ? 'checked' : ''; ?>>
                                <span>Generates license key</span>
                            </label>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="download_file">Download File</label>
                            <input type="text" id="download_file" name="download_file" class="form-input"
                                   value="<?php echo escape($product['download_file'] ?? ''); ?>"
                                   placeholder="e.g., apparix.tar.gz">
                            <small style="color: var(--admin-text-light);">In /storage/downloads/</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="download_limit">Download Limit</label>
                            <input type="number" id="download_limit" name="download_limit" class="form-input"
                                   value="<?php echo $product['download_limit'] ?? 5; ?>" min="1">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Save Changes
                </button>

                <div class="card" style="margin-top: 1rem; border: 1px solid var(--admin-danger);">
                    <h3 class="card-title" style="color: var(--admin-danger); margin-bottom: 0.5rem;">Danger Zone</h3>
                    <p style="font-size: 0.875rem; color: var(--admin-text-light); margin-bottom: 1rem;">
                        Permanently delete this product and all its images.
                    </p>
                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                        Delete Product
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Images Tab -->
<div id="tab-images" class="tab-content" style="display: none;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Product Images</h3>
            <span style="color: var(--admin-text-light);">
                <?php echo $primaryImageCount; ?> / 40 main images
                <?php if ($totalSubImages > 0): ?>
                    &nbsp;|&nbsp; <?php echo $totalSubImages; ?> sub-images
                <?php endif; ?>
            </span>
        </div>

        <!-- Upload Area for Primary Images -->
        <div class="dropzone" id="imageDropzone" onclick="document.getElementById('imageInput').click()">
            <input type="file" id="imageInput" multiple accept="image/*,video/mp4,video/webm,video/quicktime" style="display: none;" onchange="uploadImages(this.files)">
            <p class="dropzone-text">
                <strong>Click to upload images or video</strong> or drag and drop<br>
                <small>Images: PNG, JPG, WebP, AVIF up to 15MB | Video: MP4, WebM up to 50MB</small>
            </p>
        </div>

        <p style="color: var(--admin-text-light); font-size: 0.875rem; margin-top: 0.5rem;">
            <strong>Tip:</strong> Each main image can have up to 5 sub-images (detail shots, angles). Click the <strong>+ Sub</strong> button to add them.
        </p>

        <!-- Mass Action Controls -->
        <div id="massDeleteControls" style="display: none; margin-top: 1rem; padding: 0.75rem 1rem; background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 6px;">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
                <span><strong id="selectedCount">0</strong> images selected</span>
                <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                    <!-- Move to Sub-images -->
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <select id="moveToParentSelect" class="form-input" style="font-size: 0.8rem; padding: 0.4rem 0.5rem; min-width: 180px;">
                            <option value="">Move to sub-images of...</option>
                            <?php foreach ($images as $idx => $img): ?>
                                <option value="<?php echo $img['id']; ?>">Image #<?php echo $idx + 1; ?><?php echo $img['is_primary'] ? ' (Primary)' : ''; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-sm btn-primary" onclick="moveSelectedToSubImages()">Move</button>
                    </div>
                    <span style="color: #94a3b8;">|</span>
                    <button type="button" class="btn btn-sm btn-outline" onclick="clearImageSelection()">Clear</button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteSelectedImages()">Delete</button>
                </div>
            </div>
        </div>

        <!-- Hierarchical Image Display -->
        <div class="image-hierarchy" id="imageHierarchy" style="margin-top: 1.5rem;">
            <?php if (empty($images)): ?>
                <div class="empty-state" style="padding: 2rem; text-align: center; color: var(--admin-text-light);">
                    No images yet. Upload main product images above.
                </div>
            <?php else: ?>
                <?php foreach ($images as $idx => $image): ?>
                    <div class="primary-image-card" data-id="<?php echo $image['id']; ?>" draggable="false" style="border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 1rem; overflow: visible; transition: transform 0.2s, box-shadow 0.2s; position: relative;">
                        <!-- Primary Image Header -->
                        <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #f9fafb;">
                            <div class="drag-handle" style="cursor: grab; color: #333; font-size: 1.5rem; padding: 0.75rem 1rem; background: linear-gradient(to bottom, #f0f0f0, #d0d0d0); border-radius: 6px; user-select: none; font-weight: bold; border: 1px solid #aaa; box-shadow: 0 1px 3px rgba(0,0,0,0.2); opacity: 1; position: relative;" title="⬍ Drag to reorder">☰</div>
                            <input type="checkbox" class="image-checkbox" data-image-id="<?php echo $image['id']; ?>" onchange="updateImageSelection()" style="width: 18px; height: 18px; cursor: pointer;">
                            <div style="width: 80px; height: 80px; flex-shrink: 0; position: relative;">
                                <?php if (!empty($image['is_video'])): ?>
                                    <video src="<?php echo escape($image['image_path']); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px;"></video>
                                    <span style="position: absolute; bottom: 2px; left: 2px; background: rgba(0,0,0,0.7); color: white; font-size: 0.6rem; padding: 2px 4px; border-radius: 2px;">VIDEO</span>
                                <?php else: ?>
                                    <img src="<?php echo escape($image['image_path']); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px;">
                                <?php endif; ?>
                            </div>
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                    <?php if ($image['is_primary']): ?>
                                        <span class="badge badge-success">Primary</span>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-outline" onclick="setImagePrimary(<?php echo $image['id']; ?>)" title="Set as primary image" style="font-size: 0.7rem; padding: 0.2rem 0.5rem;">★ Set Primary</button>
                                    <?php endif; ?>
                                    <span style="font-weight: 500;">Image #<?php echo $idx + 1; ?></span>
                                    <?php if (!empty($image['color_name'])): ?>
                                        <span style="color: var(--admin-text-light); font-size: 0.875rem;">- <?php echo escape($image['color_name']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 0.75rem; color: var(--admin-text-light);">
                                    <?php echo count($image['sub_images'] ?? []); ?> / 5 sub-images
                                </div>
                            </div>
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                                <?php if (!empty($colorOptions)): ?>
                                    <?php
                                    // Get linked option value IDs for this image
                                    $linkedIds = array_column($image['linked_options'] ?? [], 'option_value_id');
                                    ?>
                                    <div class="variant-link-wrapper" style="position: relative;">
                                        <button type="button" class="btn btn-sm btn-outline variant-link-btn"
                                                onclick="toggleVariantDropdown(<?php echo $image['id']; ?>)"
                                                style="font-size: 0.75rem; padding: 0.4rem 0.6rem;">
                                            <?php if (!empty($image['linked_options_display'])): ?>
                                                <span style="color: var(--admin-primary);">✓ <?php echo escape($image['linked_options_display']); ?></span>
                                            <?php else: ?>
                                                Link to Variants
                                            <?php endif; ?>
                                            ▾
                                        </button>
                                        <div id="variantDropdown-<?php echo $image['id']; ?>" class="variant-dropdown" style="display: none; position: absolute; top: 100%; left: 0; background: white; border: 1px solid #d1d5db; border-radius: 6px; padding: 0.75rem; min-width: 220px; z-index: 9999; box-shadow: 0 4px 12px rgba(0,0,0,0.15); max-height: 300px; overflow-y: auto;">
                                            <?php
                                            $currentOption = null;
                                            foreach ($colorOptions as $opt):
                                                if ($currentOption !== $opt['option_name']):
                                                    if ($currentOption !== null) echo '</div>';
                                                    $currentOption = $opt['option_name'];
                                            ?>
                                                <div style="font-weight: 600; font-size: 0.7rem; color: #666; margin: 0.5rem 0 0.25rem 0; text-transform: uppercase;"><?php echo escape($opt['option_name']); ?></div>
                                                <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                            <?php endif; ?>
                                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.25rem; border-radius: 4px; font-size: 0.8rem;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">
                                                    <input type="checkbox" class="variant-checkbox-<?php echo $image['id']; ?>"
                                                           value="<?php echo $opt['id']; ?>"
                                                           <?php echo in_array($opt['id'], $linkedIds) ? 'checked' : ''; ?>
                                                           style="width: 14px; height: 14px;">
                                                    <?php echo escape($opt['value_name']); ?>
                                                </label>
                                            <?php endforeach; ?>
                                            <?php if ($currentOption !== null) echo '</div>'; ?>
                                            <div style="margin-top: 0.75rem; padding-top: 0.5rem; border-top: 1px solid #e5e7eb; display: flex; gap: 0.5rem;">
                                                <button type="button" class="btn btn-sm btn-primary" onclick="saveVariantLinks(<?php echo $image['id']; ?>)" style="flex: 1; font-size: 0.7rem;">Save</button>
                                                <button type="button" class="btn btn-sm btn-outline" onclick="toggleVariantDropdown(<?php echo $image['id']; ?>)" style="font-size: 0.7rem;">Cancel</button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if (count($image['sub_images'] ?? []) < 5): ?>
                                    <button type="button" class="btn btn-sm btn-outline" onclick="uploadSubImage(<?php echo $image['id']; ?>)" title="Add sub-images">+ Sub</button>
                                <?php endif; ?>
                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteImage(<?php echo $image['id']; ?>)" title="Delete">&#128465;</button>
                            </div>
                        </div>

                        <!-- Sub-images -->
                        <?php if (!empty($image['sub_images'])): ?>
                            <div style="padding: 0.75rem 1rem; background: #fff; border-top: 1px solid #e5e7eb;">
                                <div style="font-size: 0.75rem; color: var(--admin-text-light); margin-bottom: 0.5rem;">Sub-images (detail shots, angles):</div>
                                <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                                    <?php foreach ($image['sub_images'] as $subImg): ?>
                                        <div class="sub-image-item" style="position: relative; width: 60px;">
                                            <input type="checkbox" class="image-checkbox" data-image-id="<?php echo $subImg['id']; ?>" onchange="updateImageSelection()" style="position: absolute; top: -8px; left: -8px; width: 16px; height: 16px; cursor: pointer; z-index: 2;">
                                            <img src="<?php echo escape($subImg['image_path']); ?>" alt="" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #e5e7eb;">
                                            <button type="button" onclick="deleteImage(<?php echo $subImg['id']; ?>)"
                                                    style="position: absolute; top: -6px; right: -6px; width: 18px; height: 18px; border-radius: 50%; background: #ef4444; color: white; border: none; cursor: pointer; font-size: 10px; line-height: 1;">&times;</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Hidden input for sub-image uploads -->
        <input type="file" id="subImageInput" multiple accept="image/*" style="display: none;" onchange="handleSubImageUpload(this.files)">
    </div>
</div>

<!-- Options & Variants Tab -->
<div id="tab-options" class="tab-content" style="display: none;">
    <!-- Save Status Bar -->
    <div id="saveStatusBar" style="background: #d1fae5; color: #065f46; padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1rem; display: none; align-items: center; justify-content: space-between;">
        <span id="saveStatusText">All changes saved!</span>
        <span id="lastSaveTime" style="font-size: 0.8rem; opacity: 0.8;"></span>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
        <!-- Options -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Product Options</h3>
                <button type="button" class="btn btn-sm btn-primary" onclick="showAddOptionForm()">+ Add Option</button>
            </div>

            <!-- Add Option Form (hidden by default) -->
            <div id="addOptionForm" style="display: none; margin-bottom: 1rem; padding: 1rem; background: var(--admin-bg); border-radius: 6px;">
                <div class="form-group">
                    <label class="form-label">Option Name (e.g., Color, Size)</label>
                    <input type="text" id="newOptionName" class="form-input" placeholder="Color">
                </div>
                <div class="form-group">
                    <label class="form-label">Values (comma separated)</label>
                    <input type="text" id="newOptionValues" class="form-input" placeholder="Red, Blue, Green">
                </div>
                <div class="action-buttons">
                    <button type="button" class="btn btn-primary btn-sm" onclick="addOption()">Add Option</button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="hideAddOptionForm()">Cancel</button>
                </div>
            </div>

            <!-- Existing Options -->
            <?php if (!empty($options)): ?>
                <?php foreach ($options as $option): ?>
                    <div class="option-section">
                        <div class="option-header">
                            <strong><?php echo escape($option['option_name']); ?></strong>
                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteOption(<?php echo $option['id']; ?>)">Delete</button>
                        </div>
                        <div class="option-values">
                            <?php foreach ($option['values'] as $value): ?>
                                <span class="option-value" data-value-id="<?php echo $value['id']; ?>">
                                    <?php echo escape($value['value_name']); ?>
                                    <button type="button" class="option-value-delete" onclick="deleteOptionValue(<?php echo $value['id']; ?>, '<?php echo escape($value['value_name']); ?>')" title="Delete">&times;</button>
                                </span>
                            <?php endforeach; ?>
                            <button type="button" class="btn btn-sm btn-outline" onclick="showAddValueForm(<?php echo $option['id']; ?>)">+ Add</button>
                        </div>
                        <div id="addValueForm-<?php echo $option['id']; ?>" style="display: none; margin-top: 0.5rem;">
                            <div style="display: flex; gap: 0.5rem;">
                                <input type="text" class="form-input" id="newValue-<?php echo $option['id']; ?>" placeholder="New value" style="flex: 1;">
                                <button type="button" class="btn btn-primary btn-sm" onclick="addOptionValue(<?php echo $option['id']; ?>)">Add</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <button type="button" class="btn btn-primary" onclick="generateVariants()" style="margin-top: 1rem;">
                    Generate All Variants
                </button>
                <p style="font-size: 0.75rem; color: var(--admin-text-light); margin-top: 0.5rem;">
                    Creates new variant combinations. <strong>Existing variant settings are preserved.</strong>
                </p>

                <!-- Mass Price Adjustment -->
                <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--admin-border);">
                    <h4 style="font-size: 0.9rem; margin-bottom: 0.75rem;">Bulk Update Variants</h4>
                    <p style="font-size: 0.75rem; color: var(--admin-text-light); margin-bottom: 0.75rem;">
                        Update all variants matching a specific option value (e.g., all "Large" sizes).
                    </p>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: end;">
                        <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 150px;">
                            <label class="form-label" style="font-size: 0.75rem;">Option Value</label>
                            <select id="massOptionValue" class="form-input" style="font-size: 0.875rem;">
                                <option value="">Select option value...</option>
                                <?php foreach ($options as $option): ?>
                                    <optgroup label="<?php echo escape($option['option_name']); ?>">
                                        <?php foreach ($option['values'] as $value): ?>
                                            <option value="<?php echo $value['id']; ?>"><?php echo escape($value['value_name']); ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0; width: 90px;">
                            <label class="form-label" style="font-size: 0.75rem;">Price +/-</label>
                            <input type="number" id="massPriceAdjustment" class="form-input" step="0.01" placeholder="0.00" style="font-size: 0.875rem;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0; width: 90px;">
                            <label class="form-label" style="font-size: 0.75rem;">Your Cost</label>
                            <input type="number" id="massCost" class="form-input" step="0.01" min="0" placeholder="0.00" style="font-size: 0.875rem;">
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" onclick="applyMassUpdate()">Apply</button>
                    </div>
                    <p style="font-size: 0.7rem; color: var(--admin-text-light); margin-top: 0.5rem;">
                        Leave a field blank to skip updating it.
                    </p>
                </div>
            <?php else: ?>
                <div class="empty-state" style="padding: 2rem;">
                    <p>No options yet. Add options like "Color" or "Size" to create variants.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Variants -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Variants (<?php echo count($variants); ?>)</h3>
            </div>

            <?php if (!empty($variants)): ?>
                <div style="max-height: 600px; overflow-y: auto;">
                    <?php foreach ($variants as $variant): ?>
                        <div class="variant-card" data-variant-id="<?php echo $variant['id']; ?>">
                            <div class="variant-header"><?php echo escape($variant['options_display'] ?: 'Variant #' . $variant['id']); ?></div>
                            <div class="form-row" style="margin-bottom: 0;">
                                <div class="form-group" style="margin-bottom: 0.5rem;">
                                    <label class="form-label" style="font-size: 0.75rem;">SKU</label>
                                    <input type="text" class="form-input" data-field="sku" value="<?php echo escape($variant['sku']); ?>"
                                           onchange="updateVariant(<?php echo $variant['id']; ?>, 'sku', this.value)" style="font-size: 0.875rem;">
                                </div>
                                <div class="form-group" style="margin-bottom: 0.5rem;">
                                    <label class="form-label" style="font-size: 0.75rem;">Price +/-</label>
                                    <input type="number" class="form-input" data-field="price_adjustment" step="0.01" value="<?php echo $variant['price_adjustment']; ?>"
                                           onchange="updateVariant(<?php echo $variant['id']; ?>, 'price_adjustment', this.value)" style="font-size: 0.875rem;">
                                </div>
                                <div class="form-group" style="margin-bottom: 0.5rem;">
                                    <label class="form-label" style="font-size: 0.75rem;">Your Cost</label>
                                    <input type="number" class="form-input" data-field="cost" step="0.01" min="0" value="<?php echo $variant['cost'] ?? ''; ?>"
                                           onchange="updateVariant(<?php echo $variant['id']; ?>, 'cost', this.value)" style="font-size: 0.875rem;" placeholder="Cost">
                                </div>
                                <div class="form-group" style="margin-bottom: 0.5rem;">
                                    <label class="form-label" style="font-size: 0.75rem;">Stock</label>
                                    <input type="number" class="form-input" data-field="inventory_count" min="0" value="<?php echo $variant['inventory_count']; ?>"
                                           onchange="updateVariant(<?php echo $variant['id']; ?>, 'inventory_count', this.value)" style="font-size: 0.875rem;">
                                </div>
                                <?php if ($product['is_license_product'] ?? 0): ?>
                                <div class="form-group" style="margin-bottom: 0.5rem;">
                                    <label class="form-label" style="font-size: 0.75rem;">License Edition</label>
                                    <select class="form-input" data-field="license_edition"
                                            onchange="updateVariant(<?php echo $variant['id']; ?>, 'license_edition', this.value)" style="font-size: 0.875rem;">
                                        <option value="S" <?php echo ($variant['license_edition'] ?? 'S') === 'S' ? 'selected' : ''; ?>>Standard</option>
                                        <option value="P" <?php echo ($variant['license_edition'] ?? '') === 'P' ? 'selected' : ''; ?>>Professional</option>
                                        <option value="E" <?php echo ($variant['license_edition'] ?? '') === 'E' ? 'selected' : ''; ?>>Enterprise</option>
                                        <option value="U" <?php echo ($variant['license_edition'] ?? '') === 'U' ? 'selected' : ''; ?>>Unlimited</option>
                                    </select>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.5rem;">
                                <label class="form-checkbox" style="font-size: 0.875rem;">
                                    <input type="checkbox" <?php echo $variant['is_active'] ? 'checked' : ''; ?>
                                           onchange="updateVariant(<?php echo $variant['id']; ?>, 'is_active', this.checked ? 1 : 0)">
                                    <span>Active</span>
                                </label>
                                <span style="font-size: 0.875rem; color: var(--admin-text-light);">
                                    Final: <?php
                                    $base = $product['sale_price'] ?: $product['price'];
                                    echo formatPrice($base + $variant['price_adjustment']);
                                    ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state" style="padding: 2rem;">
                    <p>No variants yet. Add options first, then generate variants.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Save All Button -->
    <div style="margin-top: 1.5rem; padding: 1.5rem; background: var(--admin-bg); border-radius: 8px; text-align: center;">
        <button type="button" class="btn btn-primary" id="saveAllVariantsBtn" onclick="saveAllVariants()" style="padding: 0.875rem 2.5rem; font-size: 1rem;">
            Save All Changes
        </button>
        <p style="font-size: 0.8rem; color: var(--admin-text-light); margin-top: 0.75rem;">
            Changes to variants are saved automatically, but click here to confirm all changes are saved.
        </p>
    </div>
</div>

<!-- Shipping Tab -->
<div id="tab-shipping" class="tab-content" style="display: none;">
    <form action="/admin/products/update" method="POST">
        <?php echo csrfField(); ?>
        <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
        <input type="hidden" name="update_shipping" value="1">

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <!-- Weight & Dimensions -->
            <div class="card">
                <h3 class="card-title" style="margin-bottom: 1rem;">Weight & Dimensions</h3>
                <p style="color: var(--admin-text-light); font-size: 0.875rem; margin-bottom: 1rem;">
                    Used for calculating shipping rates. Leave blank if not applicable.
                </p>

                <div class="form-group">
                    <label class="form-label" for="weight_oz">Weight (oz)</label>
                    <input type="number" id="weight_oz" name="weight_oz" class="form-input" step="0.01" min="0"
                           value="<?php echo $product['weight_oz'] ?? ''; ?>"
                           placeholder="e.g., 8.5">
                    <small style="color: var(--admin-text-light);">Weight in ounces. 16 oz = 1 lb</small>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label" for="length_in">Length (in)</label>
                        <input type="number" id="length_in" name="length_in" class="form-input" step="0.1" min="0"
                               value="<?php echo $product['length_in'] ?? ''; ?>"
                               placeholder="10">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="width_in">Width (in)</label>
                        <input type="number" id="width_in" name="width_in" class="form-input" step="0.1" min="0"
                               value="<?php echo $product['width_in'] ?? ''; ?>"
                               placeholder="6">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="height_in">Height (in)</label>
                        <input type="number" id="height_in" name="height_in" class="form-input" step="0.1" min="0"
                               value="<?php echo $product['height_in'] ?? ''; ?>"
                               placeholder="2">
                    </div>
                </div>
            </div>

            <!-- Shipping Options -->
            <div class="card">
                <h3 class="card-title" style="margin-bottom: 1rem;">Shipping Options</h3>

                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" name="ships_free" value="1" <?php echo !empty($product['ships_free']) ? 'checked' : ''; ?> onchange="toggleShippingPrice(this)">
                        <span>This product always ships FREE (worldwide)</span>
                    </label>
                    <small style="color: var(--admin-text-light); display: block; margin-top: 0.25rem;">
                        Overrides zone shipping rates. Useful for digital products or promotional items.
                    </small>
                </div>

                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" name="ships_free_us" value="1" <?php echo !empty($product['ships_free_us']) ? 'checked' : ''; ?>>
                        <span>Free shipping to US only</span>
                    </label>
                    <small style="color: var(--admin-text-light); display: block; margin-top: 0.25rem;">
                        This product ships free to US addresses. Other destinations use normal rates.
                    </small>
                </div>

                <div class="form-group" id="shippingPriceGroup" style="margin-top: 1rem;<?php echo !empty($product['ships_free']) ? ' display: none;' : ''; ?>">
                    <label class="form-label" for="shipping_price">Fixed Shipping Price ($)</label>
                    <input type="number" id="shipping_price" name="shipping_price" class="form-input" step="0.01" min="0"
                           value="<?php echo $product['shipping_price'] ?? ''; ?>"
                           placeholder="Leave blank to use zone rates"
                           style="max-width: 200px;">
                    <small style="color: var(--admin-text-light);">
                        Set a fixed shipping price for this product. Overrides zone rates but adds handling fees from shipping class.
                    </small>
                </div>

                <div class="form-group" style="margin-top: 1.5rem;">
                    <label class="form-label" for="shipping_class_id">Shipping Class</label>
                    <select id="shipping_class_id" name="shipping_class_id" class="form-input">
                        <option value="">Standard (no extra fees)</option>
                        <?php if (!empty($shippingClasses)): ?>
                            <?php foreach ($shippingClasses as $class): ?>
                                <option value="<?php echo $class['id']; ?>"
                                        <?php echo ($product['shipping_class_id'] ?? '') == $class['id'] ? 'selected' : ''; ?>>
                                    <?php echo escape($class['name']); ?>
                                    <?php if ($class['handling_fee'] > 0): ?>
                                        (+$<?php echo number_format($class['handling_fee'], 2); ?> handling)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <small style="color: var(--admin-text-light);">
                        Shipping classes add handling fees (e.g., Fragile +$2, Oversized +$5)
                    </small>
                </div>

                <div class="form-group" style="margin-top: 1.5rem;">
                    <label class="form-label" for="origin_id">Ships From</label>
                    <select id="origin_id" name="origin_id" class="form-input">
                        <option value="">Default warehouse</option>
                        <?php if (!empty($shippingOrigins)): ?>
                            <?php foreach ($shippingOrigins as $origin): ?>
                                <option value="<?php echo $origin['id']; ?>"
                                        <?php echo ($product['origin_id'] ?? '') == $origin['id'] ? 'selected' : ''; ?>>
                                    <?php echo escape($origin['name']); ?> - <?php echo escape($origin['city']); ?>, <?php echo escape($origin['state']); ?>
                                    <?php if ($origin['is_default']): ?>(Default)<?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <small style="color: var(--admin-text-light);">
                        Select warehouse/location this product ships from
                    </small>
                </div>
            </div>
        </div>

        <!-- Shipping Info Box -->
        <div class="card" style="margin-top: 1.5rem; background: linear-gradient(135deg, rgba(255, 104, 197, 0.05), rgba(255, 104, 197, 0.02));">
            <h3 class="card-title" style="margin-bottom: 0.5rem; color: var(--admin-primary);">Shipping Rates Info</h3>
            <p style="color: var(--admin-text-light); font-size: 0.875rem; margin-bottom: 0.5rem;">
                Current shipping is calculated by <strong>flat rates per zone</strong>. Orders $100+ qualify for free standard shipping.
            </p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
                <div style="padding: 0.75rem; background: white; border-radius: 6px; border: 1px solid #e5e7eb;">
                    <strong style="font-size: 0.8rem; color: #666;">Domestic US</strong>
                    <div style="font-size: 0.9rem;">$5.99 / $12.99 Express</div>
                </div>
                <div style="padding: 0.75rem; background: white; border-radius: 6px; border: 1px solid #e5e7eb;">
                    <strong style="font-size: 0.8rem; color: #666;">Canada</strong>
                    <div style="font-size: 0.9rem;">$9.99 / $19.99 Express</div>
                </div>
                <div style="padding: 0.75rem; background: white; border-radius: 6px; border: 1px solid #e5e7eb;">
                    <strong style="font-size: 0.8rem; color: #666;">UK & Ireland</strong>
                    <div style="font-size: 0.9rem;">$12.99 / $24.99 Express</div>
                </div>
                <div style="padding: 0.75rem; background: white; border-radius: 6px; border: 1px solid #e5e7eb;">
                    <strong style="font-size: 0.8rem; color: #666;">Europe</strong>
                    <div style="font-size: 0.9rem;">$14.99 / $29.99 Express</div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top: 1.5rem;">
            Save Shipping Settings
        </button>
    </form>
</div>

<script>
const productId = <?php echo $product['id']; ?>;
const csrfToken = '<?php echo csrf_token(); ?>';

function showTab(tabName, element) {
    document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + tabName).style.display = 'block';
    element.classList.add('active');
    // Save active tab to localStorage
    localStorage.setItem('productEditTab_' + productId, tabName);
}

// Restore active tab on page load
document.addEventListener('DOMContentLoaded', function() {
    const savedTab = localStorage.getItem('productEditTab_' + productId);
    if (savedTab) {
        const tabElement = document.querySelector('.tab[onclick*="' + savedTab + '"]');
        if (tabElement) {
            showTab(savedTab, tabElement);
        }
    }
});

// Digital product toggle
function toggleDigitalFields() {
    var isDigital = document.getElementById('is_digital').checked;
    document.getElementById('digital-fields').style.display = isDigital ? 'block' : 'none';
}

// Image functions
let currentParentImageId = null;

function uploadImages(files) {
    const formData = new FormData();
    formData.append('_csrf_token', csrfToken);
    formData.append('product_id', productId);
    for (let file of files) {
        formData.append('images[]', file);
    }

    // Show loading state
    const dropzone = document.getElementById('imageDropzone');
    const dropzoneText = dropzone.querySelector('.dropzone-text');
    const originalText = dropzoneText ? dropzoneText.textContent : '';
    if (dropzoneText) dropzoneText.textContent = 'Uploading...';

    fetch('/admin/products/upload-images', {
        method: 'POST',
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        credentials: 'same-origin',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.failed && data.failed.length > 0) {
            // Show failed uploads
            const failedMsg = 'Upload issues:\n\n' + data.failed.join('\n');
            alert(failedMsg);
        }
        if (data.uploadedCount > 0) {
            location.reload();
        } else if (!data.success) {
            alert(data.error || data.message || 'Upload failed');
            if (dropzoneText) dropzoneText.textContent = originalText;
        } else {
            if (dropzoneText) dropzoneText.textContent = originalText;
        }
    })
    .catch(err => {
        alert('Upload error: ' + err.message);
        if (dropzoneText) dropzoneText.textContent = originalText;
    });
}

function uploadSubImage(parentImageId) {
    currentParentImageId = parentImageId;
    document.getElementById('subImageInput').click();
}

function handleSubImageUpload(files) {
    if (!currentParentImageId || !files.length) return;

    const formData = new FormData();
    formData.append('_csrf_token', csrfToken);
    formData.append('product_id', productId);
    formData.append('parent_image_id', currentParentImageId);
    for (let file of files) {
        formData.append('images[]', file);
    }

    fetch('/admin/products/upload-images', {
        method: 'POST',
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        credentials: 'same-origin',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.failed && data.failed.length > 0) {
            const failedMsg = 'Upload issues:\n\n' + data.failed.join('\n');
            alert(failedMsg);
        }
        if (data.uploadedCount > 0) {
            location.reload();
        } else if (!data.success) {
            alert(data.error || data.message || 'Upload failed');
        }
    })
    .catch(err => {
        alert('Upload error: ' + err.message);
    })
    .finally(() => {
        currentParentImageId = null;
    });
}

function setImagePrimary(imageId) {
    fetch('/admin/products/update-image', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        credentials: 'same-origin',
        body: `_csrf_token=${csrfToken}&image_id=${imageId}&action=set_primary`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) location.reload();
    });
}

function toggleVariantDropdown(imageId) {
    const dropdown = document.getElementById('variantDropdown-' + imageId);
    if (dropdown) {
        // Close all other dropdowns and reset z-index
        document.querySelectorAll('.variant-dropdown').forEach(d => {
            if (d.id !== 'variantDropdown-' + imageId) {
                d.style.display = 'none';
            }
        });
        document.querySelectorAll('.primary-image-card').forEach(card => {
            card.style.zIndex = '1';
        });

        const isOpening = dropdown.style.display === 'none';
        dropdown.style.display = isOpening ? 'block' : 'none';

        // Bring parent card to front when dropdown is open
        if (isOpening) {
            const parentCard = dropdown.closest('.primary-image-card');
            if (parentCard) {
                parentCard.style.zIndex = '1000';
            }
        }
    }
}

function saveVariantLinks(imageId) {
    const checkboxes = document.querySelectorAll('.variant-checkbox-' + imageId + ':checked');
    const optionValueIds = Array.from(checkboxes).map(cb => cb.value).join(',');

    fetch('/admin/products/update-image', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        credentials: 'same-origin',
        body: `_csrf_token=${csrfToken}&image_id=${imageId}&action=link_color&option_value_ids=${optionValueIds}`
    })
    .then(r => {
        if (!r.ok) {
            throw new Error('Server error: ' + r.status);
        }
        return r.json();
    })
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to save variant links: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(err => {
        console.error('Save variant links error:', err);
        alert('Error saving: ' + err.message);
    });
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.variant-link-wrapper')) {
        document.querySelectorAll('.variant-dropdown').forEach(d => d.style.display = 'none');
        document.querySelectorAll('.primary-image-card').forEach(card => card.style.zIndex = '1');
    }
});

function deleteImage(imageId) {
    if (!confirm('Delete this image?')) return;
    fetch('/admin/products/delete-image', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        credentials: 'same-origin',
        body: `_csrf_token=${csrfToken}&image_id=${imageId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) location.reload();
    });
}

// Mass delete functionality
function updateImageSelection() {
    const checkboxes = document.querySelectorAll('.image-checkbox:checked');
    const count = checkboxes.length;
    const controls = document.getElementById('massDeleteControls');
    const countDisplay = document.getElementById('selectedCount');

    if (count > 0) {
        controls.style.display = 'block';
        countDisplay.textContent = count;
    } else {
        controls.style.display = 'none';
    }
}

function clearImageSelection() {
    document.querySelectorAll('.image-checkbox').forEach(cb => cb.checked = false);
    updateImageSelection();
}

function deleteSelectedImages() {
    const checkboxes = document.querySelectorAll('.image-checkbox:checked');
    const imageIds = Array.from(checkboxes).map(cb => cb.dataset.imageId);

    if (imageIds.length === 0) return;

    if (!confirm(`Delete ${imageIds.length} selected images? This cannot be undone.`)) return;

    fetch('/admin/products/delete-images', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        credentials: 'same-origin',
        body: `_csrf_token=${csrfToken}&image_ids=${imageIds.join(',')}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to delete images');
        }
    });
}

function moveSelectedToSubImages() {
    const parentId = document.getElementById('moveToParentSelect').value;
    if (!parentId) {
        alert('Please select a parent image to move the selected images under.');
        return;
    }

    const checkboxes = document.querySelectorAll('.image-checkbox:checked');
    const imageIds = Array.from(checkboxes).map(cb => cb.dataset.imageId);

    if (imageIds.length === 0) {
        alert('No images selected.');
        return;
    }

    // Don't allow moving the parent to itself
    if (imageIds.includes(parentId)) {
        alert('Cannot move an image to be a sub-image of itself. Deselect the target image first.');
        return;
    }

    if (!confirm(`Move ${imageIds.length} image(s) to be sub-images of the selected parent?`)) return;

    fetch('/admin/products/move-images-to-sub', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        credentials: 'same-origin',
        body: `_csrf_token=${csrfToken}&product_id=${productId}&parent_image_id=${parentId}&image_ids=${imageIds.join(',')}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to move images');
        }
    })
    .catch(err => {
        alert('Error: ' + err.message);
    });
}

// Option functions
function showAddOptionForm() {
    document.getElementById('addOptionForm').style.display = 'block';
}

function hideAddOptionForm() {
    document.getElementById('addOptionForm').style.display = 'none';
}

function addOption() {
    const name = document.getElementById('newOptionName').value.trim();
    const values = document.getElementById('newOptionValues').value.trim();
    if (!name) return alert('Option name is required');

    console.log('Adding option:', name, values);
    console.log('CSRF Token:', csrfToken);
    console.log('Product ID:', productId);

    fetch('/admin/products/add-option', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        body: `_csrf_token=${csrfToken}&product_id=${productId}&option_name=${encodeURIComponent(name)}&values=${encodeURIComponent(values)}`
    })
    .then(r => {
        console.log('Response status:', r.status, 'redirected:', r.redirected);
        if (r.redirected || r.status === 302) {
            throw new Error('Session expired. Please refresh the page and try again.');
        }
        if (!r.ok) {
            throw new Error('Server error: ' + r.status);
        }
        return r.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to add option');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert(err.message || 'Network error');
    });
}

function showAddValueForm(optionId) {
    document.getElementById('addValueForm-' + optionId).style.display = 'block';
}

function addOptionValue(optionId) {
    const value = document.getElementById('newValue-' + optionId).value;
    if (!value) return;

    fetch('/admin/products/add-option-value', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        credentials: 'same-origin',
        body: `_csrf_token=${csrfToken}&option_id=${optionId}&value_name=${encodeURIComponent(value)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) location.reload();
        else alert(data.error);
    });
}

function deleteOption(optionId) {
    if (!confirm('Delete this option and all its values? This will also delete related variants.')) return;

    fetch('/admin/products/delete-option', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        credentials: 'same-origin',
        body: `_csrf_token=${csrfToken}&option_id=${optionId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) location.reload();
    });
}

function deleteOptionValue(valueId, valueName) {
    if (!confirm(`Delete "${valueName}"? This may affect existing variants.`)) return;

    fetch('/admin/products/delete-option-value', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        credentials: 'same-origin',
        body: `_csrf_token=${csrfToken}&value_id=${valueId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) location.reload();
        else alert(data.error || 'Failed to delete');
    });
}

function generateVariants() {
    if (!confirm('Generate all variant combinations? Existing variant settings will be preserved.')) return;

    fetch('/admin/products/generate-variants', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        credentials: 'same-origin',
        body: `_csrf_token=${csrfToken}&product_id=${productId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            let msg = `Variants updated!\n`;
            msg += `• ${data.created} new variants created\n`;
            msg += `• ${data.kept} existing variants preserved\n`;
            if (data.removed > 0) {
                msg += `• ${data.removed} orphaned variants removed`;
            }
            alert(msg);
            location.reload();
        } else {
            alert(data.error);
        }
    });
}

function applyMassPrice() {
    // Legacy function - redirect to new one
    applyMassUpdate();
}

function applyMassUpdate() {
    const optionValueId = document.getElementById('massOptionValue').value;
    const priceAdjustment = document.getElementById('massPriceAdjustment').value;
    const cost = document.getElementById('massCost').value;

    if (!optionValueId) {
        alert('Please select an option value');
        return;
    }

    if (priceAdjustment === '' && cost === '') {
        alert('Please enter at least one value to update (Price +/- or Cost)');
        return;
    }

    const selectedOption = document.getElementById('massOptionValue');
    const selectedText = selectedOption.options[selectedOption.selectedIndex].text;

    let updateParts = [];
    if (priceAdjustment !== '') updateParts.push(`Price +/- $${priceAdjustment}`);
    if (cost !== '') updateParts.push(`Cost $${cost}`);

    if (!confirm(`Update all "${selectedText}" variants with:\n${updateParts.join('\n')}?`)) return;

    let body = `_csrf_token=${csrfToken}&product_id=${productId}&option_value_id=${optionValueId}`;
    if (priceAdjustment !== '') body += `&price_adjustment=${priceAdjustment}`;
    if (cost !== '') body += `&cost=${cost}`;

    fetch('/admin/products/mass-update-variant-prices', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        credentials: 'same-origin',
        body: body
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(`Updated ${data.updated} variants`);
            location.reload(true); // Force hard refresh to bypass cache
        } else {
            alert(data.error || 'Failed to update variants');
        }
    });
}

function showSaveStatus(message, isSuccess = true) {
    const bar = document.getElementById('saveStatusBar');
    const text = document.getElementById('saveStatusText');
    const time = document.getElementById('lastSaveTime');

    if (bar && text) {
        bar.style.display = 'flex';
        bar.style.background = isSuccess ? '#d1fae5' : '#fee2e2';
        bar.style.color = isSuccess ? '#065f46' : '#991b1b';
        text.textContent = message;
        time.textContent = new Date().toLocaleTimeString();

        // Hide after 5 seconds
        setTimeout(() => {
            bar.style.display = 'none';
        }, 5000);
    }
}

function updateVariant(variantId, field, value) {
    const formData = new FormData();
    formData.append('_csrf_token', csrfToken);
    formData.append('variant_id', variantId);
    formData.append(field, value);

    // Get all fields for the variant using data-field attributes
    const card = event.target.closest('.variant-card');
    formData.append('sku', card.querySelector('[data-field="sku"]').value);
    formData.append('price_adjustment', card.querySelector('[data-field="price_adjustment"]').value);
    formData.append('cost', card.querySelector('[data-field="cost"]').value);
    formData.append('inventory_count', card.querySelector('[data-field="inventory_count"]').value);
    formData.append('is_active', card.querySelector('input[type="checkbox"]').checked ? 1 : 0);

    fetch('/admin/products/update-variant', {
        method: 'POST',
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        credentials: 'same-origin',
        body: new URLSearchParams(formData)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showSaveStatus('Changes saved automatically');
        } else {
            showSaveStatus('Failed to save changes', false);
        }
    });
}

function saveAllVariants() {
    const btn = document.getElementById('saveAllVariantsBtn');
    btn.disabled = true;
    btn.textContent = 'Saving...';

    const variantCards = document.querySelectorAll('.variant-card');
    let saveCount = 0;
    let errorCount = 0;
    const total = variantCards.length;

    if (total === 0) {
        showSaveStatus('No variants to save');
        btn.disabled = false;
        btn.textContent = 'Save All Changes';
        return;
    }

    variantCards.forEach(card => {
        const variantId = card.dataset.variantId;

        const formData = new FormData();
        formData.append('_csrf_token', csrfToken);
        formData.append('variant_id', variantId);
        formData.append('sku', card.querySelector('[data-field="sku"]').value);
        formData.append('price_adjustment', card.querySelector('[data-field="price_adjustment"]').value);
        formData.append('cost', card.querySelector('[data-field="cost"]').value);
        formData.append('inventory_count', card.querySelector('[data-field="inventory_count"]').value);
        formData.append('is_active', card.querySelector('input[type="checkbox"]').checked ? 1 : 0);

        fetch('/admin/products/update-variant', {
            method: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            credentials: 'same-origin',
            body: new URLSearchParams(formData)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) saveCount++;
            else errorCount++;
        })
        .catch(() => errorCount++)
        .finally(() => {
            if (saveCount + errorCount === total) {
                btn.disabled = false;
                btn.textContent = 'Save All Changes';
                if (errorCount === 0) {
                    showSaveStatus(`All ${saveCount} variants saved successfully!`);
                } else {
                    showSaveStatus(`Saved ${saveCount} variants. ${errorCount} failed.`, false);
                }
            }
        });
    });
}

function deleteProduct(id) {
    if (!confirm('Are you sure you want to delete this product? This cannot be undone.')) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/admin/products/delete';
    form.innerHTML = `<input type="hidden" name="_csrf_token" value="${csrfToken}"><input type="hidden" name="id" value="${id}">`;
    document.body.appendChild(form);
    form.submit();
}

// SEO meta description character counter
const metaDescInput = document.getElementById('meta_description');
const metaDescCount = document.getElementById('metaDescCount');
if (metaDescInput && metaDescCount) {
    metaDescCount.textContent = metaDescInput.value.length;
    metaDescInput.addEventListener('input', function() {
        metaDescCount.textContent = this.value.length;
    });
}

// Drag and drop for images
const dropzone = document.getElementById('imageDropzone');
if (dropzone) {
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(evt => {
        dropzone.addEventListener(evt, e => {
            e.preventDefault();
            e.stopPropagation();
        });
    });

    ['dragenter', 'dragover'].forEach(evt => {
        dropzone.addEventListener(evt, () => dropzone.classList.add('dragover'));
    });

    ['dragleave', 'drop'].forEach(evt => {
        dropzone.addEventListener(evt, () => dropzone.classList.remove('dragover'));
    });

    dropzone.addEventListener('drop', e => {
        uploadImages(e.dataTransfer.files);
    });
}

// Drag and drop reordering for image grid
const imageGrid = document.getElementById('imageGrid');
if (imageGrid) {
    let draggedItem = null;

    imageGrid.querySelectorAll('.image-item').forEach(item => {
        item.addEventListener('dragstart', function(e) {
            draggedItem = this;
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        });

        item.addEventListener('dragend', function() {
            this.classList.remove('dragging');
            draggedItem = null;
            // Update order after drag ends
            saveImageOrder();
        });

        item.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            const rect = this.getBoundingClientRect();
            const midX = rect.left + rect.width / 2;
            if (e.clientX < midX) {
                this.classList.add('drag-before');
                this.classList.remove('drag-after');
            } else {
                this.classList.add('drag-after');
                this.classList.remove('drag-before');
            }
        });

        item.addEventListener('dragleave', function() {
            this.classList.remove('drag-before', 'drag-after');
        });

        item.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-before', 'drag-after');
            if (draggedItem && draggedItem !== this) {
                const rect = this.getBoundingClientRect();
                const midX = rect.left + rect.width / 2;
                if (e.clientX < midX) {
                    imageGrid.insertBefore(draggedItem, this);
                } else {
                    imageGrid.insertBefore(draggedItem, this.nextSibling);
                }
                updatePrimaryBadge();
            }
        });
    });

    function updatePrimaryBadge() {
        // Remove all primary classes and badges
        imageGrid.querySelectorAll('.image-item').forEach((item, idx) => {
            item.classList.remove('primary');
            const badge = item.querySelector('.badge-success');
            if (badge) badge.remove();
        });
        // Add primary to first item
        const firstItem = imageGrid.querySelector('.image-item');
        if (firstItem) {
            firstItem.classList.add('primary');
            const badge = document.createElement('span');
            badge.className = 'badge badge-success image-badge';
            badge.textContent = 'Primary';
            firstItem.insertBefore(badge, firstItem.querySelector('.image-overlay'));
        }
    }

    function saveImageOrder() {
        const imageIds = [];
        imageGrid.querySelectorAll('.image-item').forEach(item => {
            imageIds.push(item.dataset.id);
        });

        fetch('/admin/products/reorder-images', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
            credentials: 'same-origin',
            body: `_csrf_token=${csrfToken}&product_id=${productId}&image_ids=${imageIds.join(',')}`
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                alert('Failed to save image order');
            }
        });
    }
}

// Drag and Drop for Hierarchical Image Cards
(function() {
    const container = document.getElementById('imageHierarchy');
    if (!container) return;

    let draggedItem = null;
    let placeholder = null;
    let isDragHandleActive = false;

    // Create placeholder element
    function createPlaceholder() {
        const el = document.createElement('div');
        el.className = 'drag-placeholder';
        el.style.cssText = 'border: 2px dashed #FF68C5; border-radius: 8px; margin-bottom: 1rem; min-height: 100px; background: rgba(255, 104, 197, 0.1);';
        return el;
    }

    // Enable drag only when mousedown on handle
    container.addEventListener('mousedown', function(e) {
        const handle = e.target.closest('.drag-handle');
        if (handle) {
            const card = handle.closest('.primary-image-card');
            if (card) {
                card.draggable = true;
                isDragHandleActive = true;
                handle.style.cursor = 'grabbing';
                handle.style.background = '#d1d5db';
            }
        }
    });

    // Disable drag when mouseup
    document.addEventListener('mouseup', function() {
        if (isDragHandleActive) {
            container.querySelectorAll('.primary-image-card').forEach(card => {
                card.draggable = false;
            });
            container.querySelectorAll('.drag-handle').forEach(handle => {
                handle.style.cursor = 'grab';
                handle.style.background = 'linear-gradient(to bottom, #f0f0f0, #d0d0d0)';
                handle.style.opacity = '1';
            });
            isDragHandleActive = false;
        }
    });

    container.addEventListener('dragstart', function(e) {
        const card = e.target.closest('.primary-image-card');
        if (!card || !isDragHandleActive) {
            e.preventDefault();
            return;
        }

        draggedItem = card;
        placeholder = createPlaceholder();

        // Set drag image
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', card.dataset.id);

        // Style the dragged item
        setTimeout(() => {
            card.style.opacity = '0.5';
            card.style.transform = 'scale(0.98)';
        }, 0);
    });

    container.addEventListener('dragend', function(e) {
        if (!draggedItem) return;

        draggedItem.style.opacity = '';
        draggedItem.style.transform = '';
        draggedItem.draggable = false;

        // Remove placeholder
        if (placeholder && placeholder.parentNode) {
            placeholder.parentNode.removeChild(placeholder);
        }

        // Save new order
        saveHierarchyOrder();

        draggedItem = null;
        placeholder = null;
        isDragHandleActive = false;
    });

    container.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        const afterElement = getDragAfterElement(container, e.clientY);

        if (placeholder && placeholder.parentNode) {
            placeholder.parentNode.removeChild(placeholder);
        }

        if (afterElement == null) {
            container.appendChild(placeholder);
        } else {
            container.insertBefore(placeholder, afterElement);
        }
    });

    container.addEventListener('drop', function(e) {
        e.preventDefault();

        if (!draggedItem || !placeholder) return;

        // Insert dragged item where placeholder is
        if (placeholder.parentNode) {
            placeholder.parentNode.insertBefore(draggedItem, placeholder);
            placeholder.parentNode.removeChild(placeholder);
        }
    });

    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.primary-image-card:not(.dragging)')];

        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;

            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    function saveHierarchyOrder() {
        const imageIds = [];
        container.querySelectorAll('.primary-image-card').forEach((card, index) => {
            imageIds.push(card.dataset.id);
        });

        if (imageIds.length === 0) return;

        fetch('/admin/products/reorder-images', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
            credentials: 'same-origin',
            body: `_csrf_token=${csrfToken}&product_id=${productId}&image_ids=${imageIds.join(',')}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Update image numbers
                container.querySelectorAll('.primary-image-card').forEach((card, index) => {
                    const label = card.querySelector('span[style*="font-weight: 500"]');
                    if (label) {
                        label.textContent = 'Image #' + (index + 1);
                    }
                });
                showSaveStatus('Image order saved');
            } else {
                alert('Failed to save image order');
            }
        });
    }
})();

// Toggle shipping price field when "ships free" is checked
function toggleShippingPrice(checkbox) {
    const priceGroup = document.getElementById('shippingPriceGroup');
    if (checkbox.checked) {
        priceGroup.style.display = 'none';
        document.getElementById('shipping_price').value = '';
    } else {
        priceGroup.style.display = 'block';
    }
}
</script>
