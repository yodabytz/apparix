<div class="page-header">
    <h1>Product Bundles</h1>
    <a href="/admin/bundles/create" class="btn btn-primary">+ Create Bundle</a>
</div>

<div class="admin-card" style="margin-bottom: 1.5rem; padding: 1rem 1.25rem;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
        <div>
            <strong>Allow discount codes with bundle/tier discounts</strong>
            <p style="margin: 0.25rem 0 0; font-size: 0.85rem; color: var(--admin-text-light);">When disabled, customers cannot combine coupon codes with bundle or quantity tier discounts.</p>
        </div>
        <label class="toggle-switch">
            <input type="checkbox" id="allowCouponWithBundle" <?php echo setting('allow_coupon_with_bundle', false) ? 'checked' : ''; ?> onchange="toggleBundleCouponSetting(this.checked)">
            <span class="toggle-slider"></span>
        </label>
    </div>
</div>

<div class="admin-card">
    <?php if (empty($bundles)): ?>
        <div class="empty-state">
            <p>No bundles yet. Create your first bundle to offer product discounts.</p>
            <a href="/admin/bundles/create" class="btn btn-primary">Create Bundle</a>
        </div>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Products</th>
                    <th>Discount</th>
                    <th>Expires</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bundles as $bundle): ?>
                    <tr>
                        <td>
                            <strong><?php echo escape($bundle['name']); ?></strong>
                            <?php if (!empty($bundle['description'])): ?>
                                <br><small style="color: var(--admin-text-light);"><?php echo escape(substr($bundle['description'], 0, 60)); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-info"><?php echo (int)$bundle['product_count']; ?> products</span>
                        </td>
                        <td>
                            <?php if ($bundle['discount_type'] === 'percentage'): ?>
                                <?php echo number_format($bundle['discount_value'], 0); ?>% off
                            <?php else: ?>
                                $<?php echo number_format($bundle['discount_value'], 2); ?> off
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($bundle['expires_at']): ?>
                                <?php
                                $expires = strtotime($bundle['expires_at']);
                                $isExpired = $expires < time();
                                ?>
                                <span class="<?php echo $isExpired ? 'text-danger' : ''; ?>">
                                    <?php echo date('M j, Y', $expires); ?>
                                </span>
                            <?php else: ?>
                                <span style="color: var(--admin-text-light);">Never</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($bundle['is_active']): ?>
                                <span style="color: #22c55e; font-weight: 600;">Active</span>
                            <?php else: ?>
                                <span style="color: var(--admin-text-light);">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="/admin/bundles/<?php echo $bundle['id']; ?>/edit" class="btn btn-sm btn-outline">Edit</a>
                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteBundle(<?php echo $bundle['id']; ?>, '<?php echo escape($bundle['name']); ?>')">Delete</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
const csrfToken = '<?php echo csrfToken(); ?>';

function toggleBundleCouponSetting(enabled) {
    fetch('/admin/bundles/setting', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: '_csrf_token=' + encodeURIComponent(csrfToken) + '&key=allow_coupon_with_bundle&value=' + (enabled ? '1' : '0')
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            alert(data.error || 'Failed to save setting');
            document.getElementById('allowCouponWithBundle').checked = !enabled;
        }
    });
}

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
            location.reload();
        } else {
            alert(data.error || 'Failed to delete bundle');
        }
    });
}
</script>

<style>
.badge {
    display: inline-block;
    padding: 2px 8px;
    font-size: 0.75em;
    border-radius: 3px;
    vertical-align: middle;
}
.badge-info { background: #17a2b8; color: white; }
.text-danger { color: #ef4444; }
.toggle-switch { position: relative; display: inline-block; width: 48px; height: 26px; flex-shrink: 0; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background: #ccc; border-radius: 26px; transition: 0.3s; }
.toggle-slider:before { content: ""; position: absolute; height: 20px; width: 20px; left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: 0.3s; }
.toggle-switch input:checked + .toggle-slider { background: #22c55e; }
.toggle-switch input:checked + .toggle-slider:before { transform: translateX(22px); }
</style>
