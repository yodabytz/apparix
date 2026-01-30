<div class="page-header">
    <h1>Orders</h1>
</div>

<!-- Status Tabs -->
<div class="status-tabs">
    <a href="/admin/orders" class="status-tab <?= empty($currentStatus) ? 'active' : '' ?>">
        All <span class="count"><?= $statusCounts['total'] ?></span>
    </a>
    <a href="/admin/orders?status=pending" class="status-tab <?= $currentStatus === 'pending' ? 'active' : '' ?>">
        Pending <span class="count"><?= $statusCounts['pending'] ?></span>
    </a>
    <a href="/admin/orders?status=processing" class="status-tab <?= $currentStatus === 'processing' ? 'active' : '' ?>">
        Processing <span class="count"><?= $statusCounts['processing'] ?></span>
    </a>
    <a href="/admin/orders?status=shipped" class="status-tab <?= $currentStatus === 'shipped' ? 'active' : '' ?>">
        Shipped <span class="count"><?= $statusCounts['shipped'] ?></span>
    </a>
    <a href="/admin/orders?status=delivered" class="status-tab <?= $currentStatus === 'delivered' ? 'active' : '' ?>">
        Delivered <span class="count"><?= $statusCounts['delivered'] ?></span>
    </a>
    <a href="/admin/orders?status=cancelled" class="status-tab <?= $currentStatus === 'cancelled' ? 'active' : '' ?>">
        Cancelled <span class="count"><?= $statusCounts['cancelled'] ?></span>
    </a>
</div>

<!-- Search -->
<div class="admin-card">
    <form method="GET" action="/admin/orders" class="search-form">
        <?php if ($currentStatus): ?>
            <input type="hidden" name="status" value="<?= htmlspecialchars($currentStatus) ?>">
        <?php endif; ?>
        <div class="search-row">
            <input type="text" name="search" placeholder="Search orders by number, email, or tracking..."
                   value="<?= htmlspecialchars($search ?? '') ?>" class="search-input">
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if ($search): ?>
                <a href="/admin/orders<?= $currentStatus ? '?status=' . $currentStatus : '' ?>" class="btn btn-outline">Clear</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Orders Table -->
<div class="admin-card">
    <?php if (empty($orders)): ?>
        <div class="empty-state">
            <p>No orders found.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Tracking</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <a href="/admin/orders/view?id=<?= $order['id'] ?>" class="order-number">
                                    <?= htmlspecialchars($order['order_number']) ?>
                                </a>
                            </td>
                            <td class="date-cell">
                                <?= date('M j, Y', strtotime($order['created_at'])) ?>
                                <span class="time"><?= date('g:i A', strtotime($order['created_at'])) ?></span>
                            </td>
                            <td>
                                <div class="customer-info">
                                    <?php if ($order['user_first_name']): ?>
                                        <span class="customer-name"><?= htmlspecialchars($order['user_first_name'] . ' ' . $order['user_last_name']) ?></span>
                                    <?php endif; ?>
                                    <span class="customer-email"><?= htmlspecialchars($order['customer_email']) ?></span>
                                </div>
                            </td>
                            <td>
                                <select class="status-select status-<?= $order['status'] ?>" data-order-id="<?= $order['id'] ?>" onchange="updateOrderStatus(this)">
                                    <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                    <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                    <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                    <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    <option value="refunded" <?= $order['status'] === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                                </select>
                            </td>
                            <td class="total-cell">
                                $<?= number_format($order['total'], 2) ?>
                            </td>
                            <td class="tracking-cell">
                                <?php if ($order['tracking_number']): ?>
                                    <span class="tracking-number" title="<?= htmlspecialchars($order['shipping_carrier']) ?>">
                                        <?= htmlspecialchars($order['tracking_number']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="no-tracking">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions-cell">
                                <a href="/admin/orders/view?id=<?= $order['id'] ?>" class="btn btn-sm">View</a>
                                <form method="POST" action="/admin/orders/delete" class="inline-form" onsubmit="return confirm('Delete this order? This cannot be undone.');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= $order['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php
                $baseUrl = '/admin/orders?';
                if ($currentStatus) $baseUrl .= 'status=' . urlencode($currentStatus) . '&';
                if ($search) $baseUrl .= 'search=' . urlencode($search) . '&';
                ?>

                <?php if ($page > 1): ?>
                    <a href="<?= $baseUrl ?>page=<?= $page - 1 ?>" class="btn btn-outline btn-sm">Previous</a>
                <?php endif; ?>

                <span class="page-info">Page <?= $page ?> of <?= $totalPages ?></span>

                <?php if ($page < $totalPages): ?>
                    <a href="<?= $baseUrl ?>page=<?= $page + 1 ?>" class="btn btn-outline btn-sm">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.status-tabs {
    display: flex;
    gap: 0;
    margin-bottom: 1.5rem;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.status-tab {
    padding: 1rem 1.5rem;
    text-decoration: none;
    color: #6b7280;
    font-weight: 500;
    border-bottom: 2px solid transparent;
    transition: all 0.2s;
}

.status-tab:hover {
    background: #f9fafb;
    color: #374151;
}

.status-tab.active {
    color: #FF68C5;
    border-bottom-color: #FF68C5;
    background: #fdf2f8;
}

.status-tab .count {
    display: inline-block;
    padding: 2px 8px;
    background: #e5e7eb;
    border-radius: 10px;
    font-size: 12px;
    margin-left: 4px;
}

.status-tab.active .count {
    background: #FF68C5;
    color: #fff;
}

.search-form {
    margin: 0;
}

.search-row {
    display: flex;
    gap: 0.75rem;
}

.search-input {
    flex: 1;
    padding: 0.75rem 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 14px;
}

.order-number {
    font-family: monospace;
    font-weight: 600;
    color: #FF68C5;
    text-decoration: none;
}

.order-number:hover {
    text-decoration: underline;
}

.date-cell {
    white-space: nowrap;
}

.date-cell .time {
    display: block;
    font-size: 12px;
    color: #9ca3af;
}

.customer-info {
    display: flex;
    flex-direction: column;
}

.customer-name {
    font-weight: 500;
    color: #374151;
}

.customer-email {
    font-size: 13px;
    color: #6b7280;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}

.status-pending { background: #fef3c7; color: #92400e; }
.status-processing { background: #dbeafe; color: #1e40af; }
.status-shipped { background: #e0e7ff; color: #4338ca; }
.status-delivered { background: #d1fae5; color: #065f46; }
.status-cancelled { background: #fee2e2; color: #991b1b; }
.status-refunded { background: #f3e8ff; color: #6b21a8; }

.total-cell {
    font-weight: 600;
    color: #374151;
}

.tracking-cell {
    font-family: monospace;
    font-size: 12px;
}

.no-tracking {
    color: #9ca3af;
}

.pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
}

.page-info {
    color: #6b7280;
    font-size: 14px;
}

.empty-state {
    padding: 3rem;
    text-align: center;
    color: #6b7280;
}

.status-select {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
    border: none;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23666' d='M6 8L2 4h8z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 6px center;
    padding-right: 20px;
}

.status-select.status-pending { background-color: #fef3c7; color: #92400e; }
.status-select.status-processing { background-color: #dbeafe; color: #1e40af; }
.status-select.status-shipped { background-color: #e0e7ff; color: #4338ca; }
.status-select.status-delivered { background-color: #d1fae5; color: #065f46; }
.status-select.status-cancelled { background-color: #fee2e2; color: #991b1b; }
.status-select.status-refunded { background-color: #f3e8ff; color: #6b21a8; }

.actions-cell {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.inline-form {
    display: inline;
    margin: 0;
}

.btn-danger {
    background: #ef4444;
    color: white;
    border: none;
}

.btn-danger:hover {
    background: #dc2626;
}

@media (max-width: 768px) {
    .status-tabs {
        flex-wrap: wrap;
    }

    .status-tab {
        flex: 1;
        min-width: 80px;
        text-align: center;
        padding: 0.75rem 0.5rem;
        font-size: 13px;
    }

    .search-row {
        flex-direction: column;
    }

    .actions-cell {
        flex-direction: column;
        gap: 0.25rem;
    }
}
</style>

<script>
const csrfToken = '<?= csrfToken() ?>';

async function updateOrderStatus(select) {
    const orderId = select.dataset.orderId;
    const status = select.value;
    const originalClass = select.className;

    // Update visual immediately
    select.className = 'status-select status-' + status;

    try {
        const formData = new FormData();
        formData.append('id', orderId);
        formData.append('status', status);
        formData.append('_csrf_token', csrfToken);

        const response = await fetch('/admin/orders/quick-status', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (!data.success) {
            alert('Failed to update status: ' + (data.error || 'Unknown error'));
            select.className = originalClass;
            location.reload();
        }
    } catch (error) {
        alert('Error updating status');
        select.className = originalClass;
        location.reload();
    }
}
</script>
