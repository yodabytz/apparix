<div class="page-header">
    <h1>Stock Notifications</h1>
</div>

<!-- Stats Cards -->
<div class="stats-grid" style="margin-bottom: 2rem;">
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
        </div>
        <div class="stat-content">
            <span class="stat-value"><?php echo $stats['pending_count']; ?></span>
            <span class="stat-label">Pending Notifications</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
        </div>
        <div class="stat-content">
            <span class="stat-value"><?php echo $stats['notified_count']; ?></span>
            <span class="stat-label">Notifications Sent</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="8.5" cy="7" r="4"/>
                <line x1="20" y1="8" x2="20" y2="14"/>
                <line x1="23" y1="11" x2="17" y2="11"/>
            </svg>
        </div>
        <div class="stat-content">
            <span class="stat-value"><?php echo $stats['unique_emails']; ?></span>
            <span class="stat-label">Unique Subscribers</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #FF68C5 0%, #ff4db8 100%);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
            </svg>
        </div>
        <div class="stat-content">
            <span class="stat-value"><?php echo $stats['unique_products']; ?></span>
            <span class="stat-label">Products Watched</span>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h2 style="margin: 0;">Pending Notifications</h2>
        <p style="margin: 0; color: var(--admin-text-light); font-size: 0.9rem;">
            Customers waiting for products to be back in stock
        </p>
    </div>

    <?php if (!empty($notifications)): ?>
        <div class="table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Product</th>
                        <th>Variant</th>
                        <th>Requested</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notifications as $notification): ?>
                        <tr id="notification-<?php echo $notification['id']; ?>">
                            <td>
                                <a href="mailto:<?php echo escape($notification['email']); ?>" style="color: var(--admin-primary);">
                                    <?php echo escape($notification['email']); ?>
                                </a>
                            </td>
                            <td>
                                <a href="/admin/products/<?php echo $notification['product_id']; ?>/edit" style="color: var(--admin-primary);">
                                    <?php echo escape($notification['product_name']); ?>
                                </a>
                            </td>
                            <td>
                                <?php if ($notification['variant_name']): ?>
                                    <span class="badge badge-gray"><?php echo escape($notification['variant_name']); ?></span>
                                <?php elseif ($notification['variant_sku']): ?>
                                    <span class="badge badge-gray"><?php echo escape($notification['variant_sku']); ?></span>
                                <?php else: ?>
                                    <span style="color: var(--admin-text-light);">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span title="<?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>">
                                    <?php echo timeAgo($notification['created_at']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button type="button" class="btn btn-sm btn-primary" onclick="triggerNotification(<?php echo $notification['product_id']; ?>, <?php echo $notification['variant_id'] ?: 'null'; ?>)" title="Send notification now">
                                        Send
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline" onclick="cancelNotification(<?php echo $notification['id']; ?>)" title="Cancel notification">
                                        Cancel
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state" style="padding: 3rem; text-align: center;">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5" style="margin-bottom: 1rem;">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            <h3 style="margin: 0 0 0.5rem; color: #666;">No pending notifications</h3>
            <p style="margin: 0; color: #999;">
                When customers sign up for back-in-stock alerts, they'll appear here.
            </p>
        </div>
    <?php endif; ?>
</div>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.stat-content {
    display: flex;
    flex-direction: column;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 600;
    color: #333;
    line-height: 1;
}

.stat-label {
    font-size: 0.85rem;
    color: #666;
    margin-top: 4px;
}

.card-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #eee;
}
</style>

<script>
const csrfToken = '<?php echo csrf_token(); ?>';

async function cancelNotification(id) {
    if (!confirm('Cancel this notification? The customer will not be notified.')) return;

    try {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('_csrf_token', csrfToken);

        const response = await fetch('/admin/notifications/cancel', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        const data = await response.json();

        if (data.success) {
            const row = document.getElementById('notification-' + id);
            row.style.transition = 'opacity 0.3s';
            row.style.opacity = '0';
            setTimeout(() => row.remove(), 300);
        } else {
            alert(data.message || 'Failed to cancel notification');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred');
    }
}

async function triggerNotification(productId, variantId) {
    if (!confirm('Send notification emails now for this product?')) return;

    try {
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('_csrf_token', csrfToken);

        const response = await fetch('/admin/notifications/trigger', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        const data = await response.json();

        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message || 'Failed to trigger notifications');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred');
    }
}
</script>

<?php
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';

    return date('M j', $time);
}
?>
