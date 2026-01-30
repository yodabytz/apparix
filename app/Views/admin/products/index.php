<div class="page-header">
    <h1><?php echo escape($title); ?> <span style="color: var(--admin-text-light); font-weight: normal;">(<?php echo $totalCount; ?>)</span></h1>
    <div class="action-buttons">
        <a href="/admin/products/featured" class="btn btn-outline">Reorder Featured</a>
        <a href="/admin/products/create" class="btn btn-primary">+ Add Product</a>
    </div>
</div>

<?php if (!empty($filter)): ?>
<div class="card" style="margin-bottom: 1rem; padding: 0.75rem 1rem; background: #fff3cd; border-left: 4px solid #ffc107;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <span>
            <strong>Filter active:</strong>
            <?php if ($filter === 'missing_cost'): ?>
                Products missing cost data for profit tracking
            <?php endif; ?>
        </span>
        <a href="/admin/products" class="btn btn-sm btn-outline">Clear Filter</a>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($categoryId)): ?>
<div class="card" style="margin-bottom: 1rem; padding: 0.75rem 1rem; background: #e0f2fe; border-left: 4px solid #0ea5e9;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <span>
            <strong>Category view:</strong> Drag to reorder products within this category. Each category has its own sort order.
        </span>
    </div>
</div>
<?php endif; ?>

<!-- Search & Filter Bar -->
<div class="card" style="margin-bottom: 1rem; padding: 1rem;">
    <form action="/admin/products" method="GET" class="admin-search-form" style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
        <?php if (!empty($filter)): ?>
            <input type="hidden" name="filter" value="<?php echo escape($filter); ?>">
        <?php endif; ?>

        <!-- Category Filter -->
        <div>
            <select name="category" class="form-control" onchange="this.form.submit()" style="min-width: 180px;">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo ($categoryId ?? 0) == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo escape($cat['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="flex: 1; max-width: 400px;">
            <input type="text"
                   name="search"
                   value="<?php echo escape($search ?? ''); ?>"
                   placeholder="Search by name, SKU, or description..."
                   class="form-control"
                   style="width: 100%;">
        </div>
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if (!empty($search) || !empty($categoryId)): ?>
            <a href="/admin/products<?php echo !empty($filter) ? '?filter=' . escape($filter) : ''; ?>" class="btn btn-outline">Clear</a>
        <?php endif; ?>
    </form>
    <?php if (!empty($search)): ?>
        <p style="margin-top: 0.75rem; margin-bottom: 0; color: var(--admin-text-light);">
            Showing results for "<strong><?php echo escape($search); ?></strong>"
        </p>
    <?php endif; ?>
</div>

<div class="card">
    <?php if (!empty($products)): ?>
        <!-- Bulk Actions Bar -->
        <div class="bulk-actions-bar" id="bulkActionsBar" style="display: none; padding: 1rem; background: var(--admin-bg); border-bottom: 1px solid var(--admin-border); align-items: center; gap: 1rem;">
            <span id="selectedCount">0 selected</span>
            <select id="bulkAction" class="form-control" style="width: auto; display: inline-block;">
                <option value="">Select Action...</option>
                <optgroup label="Visibility">
                    <option value="activate">Activate (visible on store)</option>
                    <option value="deactivate">Deactivate (hidden draft)</option>
                    <option value="disable">Disable (hidden, 404)</option>
                    <option value="enable">Enable (remove disabled)</option>
                </optgroup>
                <optgroup label="Featured">
                    <option value="feature">Mark as Featured</option>
                    <option value="unfeature">Remove from Featured</option>
                </optgroup>
                <optgroup label="Other">
                    <option value="delete">Delete Products</option>
                </optgroup>
            </select>
            <button type="button" onclick="applyBulkAction()" class="btn btn-primary btn-sm">Apply</button>
            <button type="button" onclick="clearSelection()" class="btn btn-outline btn-sm">Cancel</button>
        </div>

        <div class="table-container">
            <table class="admin-table" id="productsTable">
                <thead>
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)">
                        </th>
                        <th style="width: 30px;" title="Drag to reorder"></th>
                        <th style="width: 60px;"></th>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Price</th>
                        <th style="min-width: 100px;">Variants</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="productsList">
                    <?php foreach ($products as $product): ?>
                        <tr data-id="<?php echo $product['id']; ?>">
                            <td>
                                <input type="checkbox" class="product-checkbox" value="<?php echo $product['id']; ?>" onclick="updateSelection()">
                            </td>
                            <td class="drag-handle" style="cursor: grab; color: var(--admin-text-light);">&#9776;</td>
                            <td>
                                <?php if ($product['image']): ?>
                                    <img src="<?php echo escape($product['image']); ?>" alt="" class="product-thumb">
                                <?php else: ?>
                                    <div style="width: 50px; height: 50px; background: var(--admin-bg); border-radius: 6px;"></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="/admin/products/<?php echo $product['id']; ?>/edit" style="color: var(--admin-text); font-weight: 500;">
                                    <?php echo escape($product['name']); ?>
                                </a>
                                <?php if ($product['featured']): ?>
                                    <span class="badge badge-info" style="margin-left: 0.5rem;">Featured</span>
                                <?php endif; ?>
                            </td>
                            <td style="color: var(--admin-text-light);"><?php echo escape($product['sku'] ?: '-'); ?></td>
                            <td>
                                <?php if ($product['price_min'] && $product['price_max'] && $product['price_min'] != $product['price_max']): ?>
                                    <?php echo formatPrice($product['price_min']); ?> - <?php echo formatPrice($product['price_max']); ?>
                                <?php elseif ($product['sale_price']): ?>
                                    <span style="color: var(--admin-danger);"><?php echo formatPrice($product['sale_price']); ?></span>
                                    <del style="color: var(--admin-text-light); font-size: 0.875rem;"><?php echo formatPrice($product['price']); ?></del>
                                <?php else: ?>
                                    <?php echo formatPrice($product['price']); ?>
                                <?php endif; ?>
                            </td>
                            <td style="white-space: nowrap;">
                                <?php if ($product['variant_count'] > 0): ?>
                                    <span class="badge badge-gray" style="white-space: nowrap;"><?php echo $product['variant_count']; ?> variants</span>
                                <?php else: ?>
                                    <span style="color: var(--admin-text-light);">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                // Use variant stock if product has variants, otherwise use base inventory
                                $stock = ($product['variant_count'] > 0) ? (int)$product['variant_stock'] : (int)$product['inventory_count'];
                                ?>
                                <?php if ($stock <= 0): ?>
                                    <span class="badge badge-danger">Out of stock</span>
                                <?php elseif ($stock <= 5): ?>
                                    <span class="badge badge-warning"><?php echo $stock; ?> left</span>
                                <?php else: ?>
                                    <?php echo $stock; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($product['disabled'])): ?>
                                    <span class="badge badge-danger" title="Product returns 404 on storefront">Disabled</span>
                                <?php elseif ($product['is_active']): ?>
                                    <span class="badge badge-success" title="Visible on storefront">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-gray" title="Hidden from storefront">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button type="button" class="btn btn-sm btn-outline" onclick="showProductStats(<?php echo $product['id']; ?>, '<?php echo escape(addslashes($product['name'])); ?>')" title="View Stats">Stats</button>
                                    <a href="/admin/products/<?php echo $product['id']; ?>/edit" class="btn btn-sm btn-outline">Edit</a>
                                    <a href="/products/<?php echo escape($product['slug']); ?>" target="_blank" class="btn btn-sm btn-outline" title="View on store">View</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <?php
            $queryParams = [];
            if (!empty($search)) $queryParams[] = 'search=' . urlencode($search);
            if (!empty($filter)) $queryParams[] = 'filter=' . urlencode($filter);
            if (!empty($categoryId)) $queryParams[] = 'category=' . urlencode($categoryId);
            $extraParams = !empty($queryParams) ? '&' . implode('&', $queryParams) : '';
            ?>
            <div class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="/admin/products?page=1<?php echo $extraParams; ?>">First</a>
                    <a href="/admin/products?page=<?php echo $currentPage - 1; ?><?php echo $extraParams; ?>">Prev</a>
                <?php endif; ?>

                <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                    <?php if ($i == $currentPage): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="/admin/products?page=<?php echo $i; ?><?php echo $extraParams; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="/admin/products?page=<?php echo $currentPage + 1; ?><?php echo $extraParams; ?>">Next</a>
                    <a href="/admin/products?page=<?php echo $totalPages; ?><?php echo $extraParams; ?>">Last</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="empty-state">
            <?php if (!empty($search)): ?>
                <h3>No products found</h3>
                <p>No products match "<strong><?php echo escape($search); ?></strong>"</p>
                <a href="/admin/products" class="btn btn-outline" style="margin-top: 1rem;">Clear Search</a>
            <?php else: ?>
                <h3>No products yet</h3>
                <p>Add your first product to get started.</p>
                <a href="/admin/products/create" class="btn btn-primary" style="margin-top: 1rem;">+ Add Product</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
const csrfToken = '<?php echo csrfToken(); ?>';
const currentCategoryId = <?php echo (int)($categoryId ?? 0); ?>;

// Drag-drop sorting for products
document.addEventListener('DOMContentLoaded', function() {
    const productsList = document.getElementById('productsList');
    if (productsList) {
        new Sortable(productsList, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function(evt) {
                saveProductOrder();
            }
        });
    }
});

function saveProductOrder() {
    const rows = document.querySelectorAll('#productsList tr');
    const ids = Array.from(rows).map(row => row.dataset.id);

    let body = '_csrf_token=' + encodeURIComponent(csrfToken) + '&ids=' + ids.join(',');
    if (currentCategoryId > 0) {
        body += '&category_id=' + currentCategoryId;
    }

    fetch('/admin/products/reorder', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: body
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Show subtle success indicator
            console.log('Order saved' + (currentCategoryId > 0 ? ' for category' : ''));
        }
    });
}

// Selection management
function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.product-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    updateSelection();
}

function updateSelection() {
    const checkboxes = document.querySelectorAll('.product-checkbox:checked');
    const bulkBar = document.getElementById('bulkActionsBar');
    const countEl = document.getElementById('selectedCount');
    const selectAll = document.getElementById('selectAll');

    if (checkboxes.length > 0) {
        bulkBar.style.display = 'flex';
        countEl.textContent = checkboxes.length + ' selected';
    } else {
        bulkBar.style.display = 'none';
    }

    // Update select all checkbox state
    const total = document.querySelectorAll('.product-checkbox').length;
    selectAll.checked = checkboxes.length === total && total > 0;
    selectAll.indeterminate = checkboxes.length > 0 && checkboxes.length < total;
}

function clearSelection() {
    document.querySelectorAll('.product-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked = false;
    updateSelection();
}

function applyBulkAction() {
    const action = document.getElementById('bulkAction').value;
    if (!action) {
        alert('Please select an action');
        return;
    }

    const checkboxes = document.querySelectorAll('.product-checkbox:checked');
    const ids = Array.from(checkboxes).map(cb => cb.value);

    if (ids.length === 0) {
        alert('Please select at least one product');
        return;
    }

    if (action === 'delete') {
        if (!confirm('Are you sure you want to delete ' + ids.length + ' product(s)? This cannot be undone.')) {
            return;
        }
    } else if (action === 'disable') {
        if (!confirm('Disable ' + ids.length + ' product(s)? Disabled products return 404 on the storefront.')) {
            return;
        }
    }

    fetch('/admin/products/bulk-action', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: '_csrf_token=' + encodeURIComponent(csrfToken) + '&action=' + action + '&ids=' + ids.join(',')
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Action failed');
        }
    });
}

// Product Stats Modal
function showProductStats(productId, productName) {
    // Create modal overlay
    const overlay = document.createElement('div');
    overlay.id = 'statsModal';
    overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:9999';
    overlay.onclick = function(e) { if (e.target === overlay) closeStatsModal(); };

    // Create modal content
    const modal = document.createElement('div');
    modal.style.cssText = 'background:#fff;border-radius:12px;padding:1.5rem;max-width:500px;width:90%;max-height:90vh;overflow-y:auto;box-shadow:0 4px 20px rgba(0,0,0,0.15)';

    // Header
    const header = document.createElement('div');
    header.style.cssText = 'display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;padding-bottom:1rem;border-bottom:1px solid #e5e7eb';

    const title = document.createElement('h3');
    title.style.cssText = 'margin:0;font-size:1.25rem';
    title.textContent = 'Stats: ' + productName;

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.style.cssText = 'background:none;border:none;font-size:1.5rem;cursor:pointer;color:#6b7280';
    closeBtn.textContent = '\u00d7';
    closeBtn.onclick = closeStatsModal;

    header.appendChild(title);
    header.appendChild(closeBtn);
    modal.appendChild(header);

    // Loading state
    const loading = document.createElement('div');
    loading.id = 'statsLoading';
    loading.style.cssText = 'text-align:center;padding:2rem;color:#6b7280';
    loading.textContent = 'Loading stats...';
    modal.appendChild(loading);

    // Stats content (hidden initially)
    const content = document.createElement('div');
    content.id = 'statsContent';
    content.style.display = 'none';
    modal.appendChild(content);

    overlay.appendChild(modal);
    document.body.appendChild(overlay);

    // Fetch stats
    fetch('/admin/products/stats?id=' + productId, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        loading.style.display = 'none';
        content.style.display = 'block';

        if (data.success) {
            const s = data.stats;
            renderStatsContent(content, s);
        } else {
            content.textContent = 'Error loading stats: ' + (data.error || 'Unknown error');
        }
    })
    .catch(err => {
        loading.style.display = 'none';
        content.style.display = 'block';
        content.textContent = 'Error loading stats: ' + err.message;
    });
}

function renderStatsContent(container, s) {
    container.replaceChildren();

    // Create stat rows helper
    function createStatRow(label, value, subValue) {
        const row = document.createElement('div');
        row.style.cssText = 'display:flex;justify-content:space-between;align-items:center;padding:0.75rem 0;border-bottom:1px solid #f3f4f6';

        const labelEl = document.createElement('span');
        labelEl.style.color = '#6b7280';
        labelEl.textContent = label;

        const valueContainer = document.createElement('div');
        valueContainer.style.textAlign = 'right';

        const mainValue = document.createElement('span');
        mainValue.style.cssText = 'font-weight:600;font-size:1.1rem';
        mainValue.textContent = value;
        valueContainer.appendChild(mainValue);

        if (subValue) {
            const sub = document.createElement('div');
            sub.style.cssText = 'font-size:0.75rem;color:#9ca3af';
            sub.textContent = subValue;
            valueContainer.appendChild(sub);
        }

        row.appendChild(labelEl);
        row.appendChild(valueContainer);
        return row;
    }

    // Format currency
    function formatCurrency(amount) {
        return '$' + amount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    // Add stats
    container.appendChild(createStatRow('Page Views (30 days)', s.views_30_days.toLocaleString(), s.views_all_time.toLocaleString() + ' all time'));
    container.appendChild(createStatRow('Units Sold', s.units_sold.toLocaleString(), s.units_sold_30_days + ' in last 30 days'));
    container.appendChild(createStatRow('Revenue', formatCurrency(s.revenue), formatCurrency(s.revenue_30_days) + ' in last 30 days'));
    container.appendChild(createStatRow('Total Orders', s.order_count.toLocaleString()));
    container.appendChild(createStatRow('Conversion Rate', s.conversion_rate + '%', 'orders / views'));

    // Remove border from last row
    container.lastChild.style.borderBottom = 'none';
}

function closeStatsModal() {
    const modal = document.getElementById('statsModal');
    if (modal) modal.remove();
}
</script>
