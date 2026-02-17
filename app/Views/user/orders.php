<!-- Order History Page -->
<section class="orders-section">
    <div class="container">
        <div class="page-header">
            <h1>Order History</h1>
            <a href="/account" class="back-link">Back to Account</a>
        </div>

        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <h2>No Orders Yet</h2>
                <p>You haven't placed any orders yet. Start shopping to see your order history here.</p>
                <a href="/products" class="btn btn-primary">Browse Products</a>
            </div>
        <?php else: ?>
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-info">
                                <span class="order-number">Order #<?php echo escape($order['order_number']); ?></span>
                                <span class="order-date">Placed on <?php echo date('F j, Y', strtotime($order['created_at'])); ?></span>
                            </div>
                            <div class="order-status">
                                <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                        </div>

                        <div class="order-items">
                            <?php foreach ($order['items'] as $item): ?>
                                <div class="order-item">
                                    <div class="item-image">
                                        <img src="<?php echo escape($item['product_image'] ?? '/assets/images/placeholder.png'); ?>" alt="<?php echo escape($item['product_name'] ?? 'Product'); ?>">
                                    </div>
                                    <div class="item-details">
                                        <?php if (!empty($item['product_slug'])): ?>
                                            <a href="/products/<?php echo escape($item['product_slug']); ?>" class="item-name"><?php echo escape($item['product_name'] ?? 'Product'); ?></a>
                                        <?php else: ?>
                                            <span class="item-name"><?php echo escape($item['product_name'] ?? 'Product'); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($item['variant_name'])): ?>
                                            <span class="item-variant"><?php echo escape($item['variant_name']); ?></span>
                                        <?php endif; ?>
                                        <span class="item-qty">Qty: <?php echo $item['quantity']; ?></span>
                                    </div>
                                    <div class="item-price"><?php echo formatPrice($item['price']); ?></div>
                                    <div class="item-actions">
                                        <?php if ($order['status'] === 'delivered' && !empty($item['product_slug'])): ?>
                                            <?php if ($item['has_reviewed']): ?>
                                                <span class="reviewed-badge">Reviewed</span>
                                            <?php else: ?>
                                                <a href="/products/<?php echo escape($item['product_slug']); ?>#reviewsSection" class="btn btn-sm btn-review">Leave Review</a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="order-footer">
                            <div class="order-total">
                                <span class="label">Order Total:</span>
                                <span class="value"><?php echo formatPrice($order['total']); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.orders-section {
    padding: 40px 0 80px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.page-header h1 {
    margin: 0;
}

.back-link {
    color: #FF68C5;
    text-decoration: none;
    font-weight: 500;
}

.back-link:hover {
    text-decoration: underline;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}

.empty-state h2 {
    color: #333;
    margin-bottom: 12px;
}

.empty-state p {
    color: #666;
    margin-bottom: 24px;
}

.orders-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.order-card {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
    padding-bottom: 16px;
    border-bottom: 1px solid #eee;
}

.order-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.order-number {
    font-weight: 600;
    font-size: 1.1rem;
    color: #333;
}

.order-date {
    color: #666;
    font-size: 0.9rem;
}

.status-badge {
    display: inline-block;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-processing { background: #cce5ff; color: #004085; }
.status-shipped { background: #d4edda; color: #155724; }
.status-delivered { background: #d4edda; color: #155724; }
.status-cancelled { background: #f8d7da; color: #721c24; }

.order-summary {
    display: flex;
    gap: 40px;
}

.summary-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.summary-item .label {
    font-size: 0.85rem;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.summary-item .value {
    font-weight: 500;
    color: #333;
}

.summary-item .value.total {
    font-size: 1.1rem;
    color: #FF68C5;
}

.order-items {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 16px 0;
    border-bottom: 1px solid #eee;
}

.order-item {
    display: flex;
    align-items: center;
    gap: 16px;
}

.item-image {
    width: 70px;
    height: 70px;
    flex-shrink: 0;
}

.item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 8px;
}

.item-details {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.item-name {
    font-weight: 500;
    color: #333;
    text-decoration: none;
}

.item-name:hover {
    color: #FF68C5;
}

.item-variant {
    font-size: 0.85rem;
    color: #666;
}

.item-qty {
    font-size: 0.85rem;
    color: #888;
}

.item-price {
    font-weight: 600;
    color: #333;
    min-width: 80px;
    text-align: right;
}

.item-actions {
    min-width: 120px;
    text-align: right;
}

.btn-sm {
    padding: 8px 16px;
    font-size: 0.85rem;
    border-radius: 6px;
    text-decoration: none;
    display: inline-block;
}

.btn-review {
    background: #FF68C5;
    color: #fff;
}

.btn-review:hover {
    background: #e055ad;
}

.reviewed-badge {
    display: inline-block;
    padding: 6px 12px;
    background: #d4edda;
    color: #155724;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 500;
}

.order-footer {
    padding-top: 16px;
    display: flex;
    justify-content: flex-end;
}

.order-total {
    display: flex;
    gap: 12px;
    align-items: center;
}

.order-total .label {
    font-weight: 500;
    color: #666;
}

.order-total .value {
    font-size: 1.2rem;
    font-weight: 600;
    color: #FF68C5;
}

@media (max-width: 600px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .order-header {
        flex-direction: column;
        gap: 12px;
    }

    .order-item {
        flex-wrap: wrap;
    }

    .item-image {
        width: 60px;
        height: 60px;
    }

    .item-details {
        min-width: calc(100% - 80px);
    }

    .item-price {
        margin-left: 76px;
    }

    .item-actions {
        width: 100%;
        margin-left: 76px;
        margin-top: 8px;
        text-align: left;
    }
}
</style>
