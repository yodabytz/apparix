<?php $isEdit = !empty($bundle); ?>
<div class="page-header">
    <h1><?php echo $isEdit ? 'Edit Bundle' : 'Create Bundle'; ?></h1>
    <a href="/admin/bundles" class="btn btn-outline">Back to Bundles</a>
</div>

<form action="/admin/bundles/<?php echo $isEdit ? 'update' : 'store'; ?>" method="POST">
    <?php echo csrfField(); ?>
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?php echo $bundle['id']; ?>">
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
        <div>
            <div class="card">
                <h3 class="card-title" style="margin-bottom: 1rem;">Bundle Details</h3>

                <div class="form-group">
                    <label class="form-label" for="name">Bundle Name *</label>
                    <input type="text" id="name" name="name" class="form-input"
                           value="<?php echo escape($isEdit ? $bundle['name'] : ''); ?>" required
                           placeholder="e.g., Couples Ring Set">
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Description</label>
                    <textarea id="description" name="description" class="form-textarea" rows="3"
                              placeholder="Optional description shown to customers"><?php echo escape($isEdit ? ($bundle['description'] ?? '') : ''); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="discount_type">Discount Type</label>
                        <select id="discount_type" name="discount_type" class="form-input" onchange="updateDiscountLabel()">
                            <option value="percentage" <?php echo ($isEdit && $bundle['discount_type'] === 'percentage') || !$isEdit ? 'selected' : ''; ?>>Percentage (%)</option>
                            <option value="fixed" <?php echo ($isEdit && $bundle['discount_type'] === 'fixed') ? 'selected' : ''; ?>>Fixed Amount ($)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="discount_value" id="discountLabel">Discount Value (%)</label>
                        <input type="number" id="discount_value" name="discount_value" class="form-input"
                               step="0.01" min="0.01"
                               value="<?php echo $isEdit ? $bundle['discount_value'] : ''; ?>"
                               required placeholder="e.g., 10">
                    </div>
                </div>
            </div>

            <div class="card" style="margin-top: 1.5rem;">
                <h3 class="card-title" style="margin-bottom: 1rem;">Bundle Products</h3>
                <p style="font-size: 0.875rem; color: var(--admin-text-light); margin-bottom: 1rem;">
                    Add at least 2 products. When all products are in a customer's cart, the bundle discount applies automatically.
                </p>

                <div class="form-group">
                    <input type="text" id="productSearch" class="form-input"
                           placeholder="Search products by name or SKU..."
                           autocomplete="off">
                    <div id="searchResults" style="display: none; position: absolute; z-index: 100; background: white; border: 1px solid #ddd; border-radius: 6px; max-height: 250px; overflow-y: auto; width: calc(100% - 2rem); box-shadow: 0 4px 12px rgba(0,0,0,0.1);"></div>
                </div>

                <div id="selectedProducts">
                    <?php if (!empty($bundleProducts)): ?>
                        <?php foreach ($bundleProducts as $product): ?>
                            <div class="bundle-product-row" data-id="<?php echo $product['id']; ?>">
                                <input type="hidden" name="product_ids[]" value="<?php echo $product['id']; ?>">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <?php if (!empty($product['image'])): ?>
                                        <img src="<?php echo escape($product['image']); ?>" alt="" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                                    <?php else: ?>
                                        <div style="width: 40px; height: 40px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 1.2em;">&#128722;</div>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?php echo escape($product['name']); ?></strong>
                                        <br><small style="color: var(--admin-text-light);">$<?php echo number_format($product['sale_price'] ?: $product['price'], 2); ?></small>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-danger" onclick="removeProduct(this)">Remove</button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div id="noProductsMsg" style="padding: 2rem; text-align: center; color: var(--admin-text-light); <?php echo !empty($bundleProducts) ? 'display:none;' : ''; ?>">
                    No products added yet. Search and add at least 2 products.
                </div>
            </div>
        </div>

        <div>
            <div class="card">
                <h3 class="card-title" style="margin-bottom: 1rem;">Status</h3>

                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" name="is_active" value="1"
                               <?php echo (!$isEdit || $bundle['is_active']) ? 'checked' : ''; ?>>
                        <span>Active</span>
                    </label>
                </div>

                <div class="form-group" style="margin-top: 1rem;">
                    <label class="form-label" for="expires_at">Expiry Date (optional)</label>
                    <input type="datetime-local" id="expires_at" name="expires_at" class="form-input"
                           value="<?php echo $isEdit && $bundle['expires_at'] ? date('Y-m-d\TH:i', strtotime($bundle['expires_at'])) : ''; ?>">
                    <small style="color: var(--admin-text-light);">Leave blank for no expiry</small>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                <?php echo $isEdit ? 'Update Bundle' : 'Create Bundle'; ?>
            </button>

            <?php if ($isEdit): ?>
            <div class="card" style="margin-top: 1rem; border: 1px solid var(--admin-danger);">
                <h3 class="card-title" style="color: var(--admin-danger); margin-bottom: 0.5rem;">Danger Zone</h3>
                <p style="font-size: 0.875rem; color: var(--admin-text-light); margin-bottom: 1rem;">
                    Permanently delete this bundle.
                </p>
                <button type="button" class="btn btn-danger btn-sm" onclick="deleteBundle(<?php echo $bundle['id']; ?>, '<?php echo escape($bundle['name']); ?>')">
                    Delete Bundle
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
</form>

<script>
const csrfToken = '<?php echo csrfToken(); ?>';
let searchTimeout;

const searchInput = document.getElementById('productSearch');
const searchResults = document.getElementById('searchResults');
const selectedProducts = document.getElementById('selectedProducts');
const noProductsMsg = document.getElementById('noProductsMsg');

searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const query = this.value.trim();

    if (query.length < 2) {
        searchResults.style.display = 'none';
        return;
    }

    searchTimeout = setTimeout(function() {
        const excludeIds = getSelectedProductIds().join(',');
        fetch('/admin/bundles/search-products?q=' + encodeURIComponent(query) + '&exclude=' + excludeIds, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            searchResults.textContent = '';
            if (data.products && data.products.length > 0) {
                data.products.forEach(function(product) {
                    const item = document.createElement('div');
                    item.style.cssText = 'padding: 0.75rem 1rem; cursor: pointer; display: flex; align-items: center; gap: 0.75rem; border-bottom: 1px solid #f0f0f0;';
                    item.addEventListener('mouseenter', function() { this.style.background = '#f7f7f7'; });
                    item.addEventListener('mouseleave', function() { this.style.background = ''; });

                    if (product.image) {
                        const img = document.createElement('img');
                        img.src = product.image;
                        img.alt = '';
                        img.style.cssText = 'width: 32px; height: 32px; object-fit: cover; border-radius: 4px;';
                        item.appendChild(img);
                    }

                    const info = document.createElement('div');
                    const nameEl = document.createElement('strong');
                    nameEl.textContent = product.name;
                    info.appendChild(nameEl);

                    const priceEl = document.createElement('small');
                    priceEl.style.color = '#888';
                    priceEl.textContent = ' - $' + parseFloat(product.price).toFixed(2);
                    info.appendChild(priceEl);

                    item.appendChild(info);

                    item.addEventListener('click', function() {
                        addProduct(product);
                        searchResults.style.display = 'none';
                        searchInput.value = '';
                    });

                    searchResults.appendChild(item);
                });
                searchResults.style.display = 'block';
            } else {
                const noResult = document.createElement('div');
                noResult.style.cssText = 'padding: 1rem; text-align: center; color: #888;';
                noResult.textContent = 'No products found';
                searchResults.appendChild(noResult);
                searchResults.style.display = 'block';
            }
        });
    }, 300);
});

document.addEventListener('click', function(e) {
    if (!searchResults.contains(e.target) && e.target !== searchInput) {
        searchResults.style.display = 'none';
    }
});

function getSelectedProductIds() {
    const inputs = selectedProducts.querySelectorAll('input[name="product_ids[]"]');
    return Array.from(inputs).map(function(i) { return i.value; });
}

function addProduct(product) {
    if (getSelectedProductIds().includes(String(product.id))) return;

    const row = document.createElement('div');
    row.className = 'bundle-product-row';
    row.setAttribute('data-id', product.id);

    const hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = 'product_ids[]';
    hidden.value = product.id;
    row.appendChild(hidden);

    const infoWrap = document.createElement('div');
    infoWrap.style.cssText = 'display: flex; align-items: center; gap: 0.75rem;';

    if (product.image) {
        const img = document.createElement('img');
        img.src = product.image;
        img.alt = '';
        img.style.cssText = 'width: 40px; height: 40px; object-fit: cover; border-radius: 4px;';
        infoWrap.appendChild(img);
    } else {
        const placeholder = document.createElement('div');
        placeholder.style.cssText = 'width: 40px; height: 40px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 1.2em;';
        placeholder.textContent = '\u{1F6D2}';
        infoWrap.appendChild(placeholder);
    }

    const details = document.createElement('div');
    const nameEl = document.createElement('strong');
    nameEl.textContent = product.name;
    details.appendChild(nameEl);
    details.appendChild(document.createElement('br'));

    const priceEl = document.createElement('small');
    priceEl.style.color = 'var(--admin-text-light)';
    priceEl.textContent = '$' + parseFloat(product.price).toFixed(2);
    details.appendChild(priceEl);

    infoWrap.appendChild(details);
    row.appendChild(infoWrap);

    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'btn btn-sm btn-danger';
    removeBtn.textContent = 'Remove';
    removeBtn.addEventListener('click', function() { removeProduct(this); });
    row.appendChild(removeBtn);

    selectedProducts.appendChild(row);
    noProductsMsg.style.display = 'none';
}

function removeProduct(btn) {
    btn.closest('.bundle-product-row').remove();
    if (selectedProducts.querySelectorAll('.bundle-product-row').length === 0) {
        noProductsMsg.style.display = '';
    }
}

function updateDiscountLabel() {
    const type = document.getElementById('discount_type').value;
    const label = document.getElementById('discountLabel');
    label.textContent = type === 'percentage' ? 'Discount Value (%)' : 'Discount Value ($)';
}

<?php if ($isEdit): ?>
function deleteBundle(id, name) {
    if (!confirm('Are you sure you want to delete bundle "' + name + '"?')) return;

    fetch('/admin/bundles/delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: '_csrf_token=' + encodeURIComponent(csrfToken) + '&id=' + id
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.href = '/admin/bundles';
        } else {
            alert(data.error || 'Failed to delete bundle');
        }
    });
}
<?php endif; ?>

updateDiscountLabel();
</script>

<style>
.bundle-product-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    margin-bottom: 0.5rem;
    background: #fafafa;
}
.form-group {
    position: relative;
}
</style>
