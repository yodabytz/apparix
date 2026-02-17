<div class="page-header">
    <h1>Coupon Codes</h1>
    <a href="/admin/coupons/create" class="btn btn-primary">+ Create Coupon</a>
</div>

<div class="admin-card">
    <?php if (empty($coupons)): ?>
        <div class="empty-state">
            <p>No coupon codes yet. Create your first coupon to offer discounts to customers.</p>
            <a href="/admin/coupons/create" class="btn btn-primary">Create Coupon</a>
        </div>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Description</th>
                    <th>Discount</th>
                    <th>Usage</th>
                    <th>Expires</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($coupons as $coupon): ?>
                    <tr>
                        <td>
                            <strong class="coupon-code"><?php echo escape($coupon['code']); ?></strong>
                            <?php if ($coupon['requires_account']): ?>
                                <span class="badge badge-info" title="Requires account">Account</span>
                            <?php endif; ?>
                            <?php if ($coupon['one_per_customer']): ?>
                                <span class="badge badge-warning" title="One per customer">1x</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo escape($coupon['description'] ?? '-'); ?></td>
                        <td>
                            <?php if ($coupon['type'] === 'percentage'): ?>
                                <?php echo (int)$coupon['value']; ?>% off
                            <?php else: ?>
                                $<?php echo number_format($coupon['value'], 2); ?> off
                            <?php endif; ?>
                            <?php if ($coupon['min_purchase'] > 0): ?>
                                <br><small class="text-muted">Min: $<?php echo number_format($coupon['min_purchase'], 2); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo (int)$coupon['times_used']; ?>
                            <?php if ($coupon['max_uses']): ?>
                                / <?php echo (int)$coupon['max_uses']; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($coupon['expires_at']): ?>
                                <?php
                                $expires = strtotime($coupon['expires_at']);
                                $isExpired = $expires < time();
                                ?>
                                <span class="<?php echo $isExpired ? 'text-danger' : ''; ?>">
                                    <?php echo date('M j, Y', $expires); ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">Never</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <label class="toggle-switch">
                                <input type="checkbox"
                                       <?php echo $coupon['is_active'] ? 'checked' : ''; ?>
                                       onchange="toggleCouponStatus(<?php echo $coupon['id']; ?>, this)">
                                <span class="toggle-slider"></span>
                            </label>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="/admin/coupons/<?php echo $coupon['id']; ?>/edit" class="btn btn-sm btn-outline">Edit</a>
                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteCoupon(<?php echo $coupon['id']; ?>, '<?php echo escape($coupon['code']); ?>')">Delete</button>
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

function toggleCouponStatus(id, checkbox) {
    fetch('/admin/coupons/toggle-status', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: '_csrf_token=' + encodeURIComponent(csrfToken) + '&id=' + id
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            alert(data.error || 'Failed to update status');
            checkbox.checked = !checkbox.checked;
        }
    })
    .catch(() => {
        alert('Failed to update status');
        checkbox.checked = !checkbox.checked;
    });
}

function deleteCoupon(id, code) {
    if (!confirm('Are you sure you want to delete coupon "' + code + '"?')) return;

    fetch('/admin/coupons/delete', {
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
            alert(data.error || 'Failed to delete coupon');
        }
    });
}
</script>

<style>
.coupon-code {
    font-family: monospace;
    font-size: 1.1em;
    background: #f5f5f5;
    padding: 2px 6px;
    border-radius: 3px;
}
.badge {
    display: inline-block;
    padding: 2px 6px;
    font-size: 0.7em;
    border-radius: 3px;
    margin-left: 4px;
    vertical-align: middle;
}
.badge-info { background: #17a2b8; color: white; }
.badge-warning { background: #ffc107; color: #333; }
.toggle-switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
}
.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}
.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .3s;
    border-radius: 24px;
}
.toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .3s;
    border-radius: 50%;
}
input:checked + .toggle-slider {
    background-color: #4CAF50;
}
input:checked + .toggle-slider:before {
    transform: translateX(20px);
}
</style>
