<div class="page-header">
    <div class="header-left">
        <a href="/admin/orders" class="back-link">&larr; Back to Orders</a>
        <h1>Order <?= htmlspecialchars($order['order_number']) ?></h1>
    </div>
    <div class="header-actions">
        <span class="status-badge status-<?= $order['status'] ?> large">
            <?= ucfirst($order['status']) ?>
        </span>
    </div>
</div>

<div class="order-grid">
    <!-- Left Column -->
    <div class="order-main">
        <!-- Order Items -->
        <div class="admin-card">
            <h3>Order Items</h3>
            <div class="order-items">
                <?php foreach ($orderItems as $item): ?>
                    <div class="order-item">
                        <div class="item-image">
                            <?php $image = $item['product_image'] ?? '/assets/images/placeholder.png'; ?>
                            <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
                        </div>
                        <div class="item-details">
                            <div class="item-name"><?= htmlspecialchars($item['product_name']) ?></div>
                            <?php if (!empty($item['variant_name'])): ?>
                                <div class="item-variant"><?= htmlspecialchars($item['variant_name']) ?></div>
                            <?php endif; ?>
                            <div class="item-sku">SKU: <?= htmlspecialchars($item['sku'] ?? 'N/A') ?></div>
                            <?php if (!empty($item['manufacturer'])): ?>
                                <div class="item-manufacturer">From: <?= htmlspecialchars($item['manufacturer']) ?></div>
                            <?php endif; ?>
                            <div class="item-cost-edit">
                                <label>Cost: $</label>
                                <input type="number" step="0.01" min="0"
                                       class="item-cost-input"
                                       data-item-id="<?= $item['id'] ?>"
                                       data-order-id="<?= $order['id'] ?>"
                                       value="<?= $item['item_cost'] !== null ? number_format($item['item_cost'], 2, '.', '') : '' ?>"
                                       placeholder="<?= $item['product_cost'] !== null ? number_format($item['product_cost'], 2, '.', '') : '0.00' ?>">
                            </div>
                        </div>
                        <div class="item-quantity">
                            &times; <?= $item['quantity'] ?>
                        </div>
                        <div class="item-price">
                            $<?= number_format($item['price'], 2) ?>
                        </div>
                        <div class="item-total">
                            $<?= number_format($item['total'], 2) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="order-totals">
                <div class="total-row">
                    <span>Subtotal</span>
                    <span>$<?= number_format($order['subtotal'], 2) ?></span>
                </div>
                <?php if ($order['discount_amount'] > 0): ?>
                    <div class="total-row discount">
                        <span>Discount</span>
                        <span>-$<?= number_format($order['discount_amount'], 2) ?></span>
                    </div>
                <?php endif; ?>
                <div class="total-row">
                    <span>Shipping</span>
                    <span><?= $order['shipping_cost'] > 0 ? '$' . number_format($order['shipping_cost'], 2) : 'FREE' ?></span>
                </div>
                <div class="total-row">
                    <span>Tax</span>
                    <span>$<?= number_format($order['tax'], 2) ?></span>
                </div>
                <div class="total-row grand-total">
                    <span>Total</span>
                    <span>$<?= number_format($order['total'], 2) ?></span>
                </div>
            </div>
        </div>

        <!-- Shipping & Tracking -->
        <div class="admin-card">
            <h3>Shipping & Tracking</h3>

            <?php if ($order['tracking_number']): ?>
                <div class="tracking-info">
                    <div class="tracking-details">
                        <div class="tracking-row">
                            <span class="label">Carrier:</span>
                            <span class="value"><?= htmlspecialchars(\App\Models\Order::getCarrierName($order['shipping_carrier'])) ?></span>
                        </div>
                        <div class="tracking-row">
                            <span class="label">Tracking Number:</span>
                            <span class="value tracking-number"><?= htmlspecialchars($order['tracking_number']) ?></span>
                        </div>
                        <?php if ($order['shipped_at']): ?>
                            <div class="tracking-row">
                                <span class="label">Shipped:</span>
                                <span class="value"><?= date('M j, Y g:i A', strtotime($order['shipped_at'])) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($order['estimated_delivery']): ?>
                            <div class="tracking-row">
                                <span class="label">Est. Delivery:</span>
                                <span class="value"><?= htmlspecialchars($order['estimated_delivery']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php
                    $trackingUrl = \App\Models\Order::getTrackingUrl($order['shipping_carrier'], $order['tracking_number']);
                    if ($trackingUrl):
                    ?>
                        <a href="<?= htmlspecialchars($trackingUrl) ?>" target="_blank" class="btn btn-primary">
                            Track Package &rarr;
                        </a>
                    <?php endif; ?>
                </div>

                <button type="button" class="btn btn-outline btn-sm" onclick="toggleTrackingForm()">Update Tracking</button>
            <?php endif; ?>

            <form id="trackingForm" class="tracking-form <?= $order['tracking_number'] ? 'hidden' : '' ?>">
                <input type="hidden" name="_csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label for="carrier">Shipping Carrier *</label>
                        <select id="carrier" name="carrier" required>
                            <option value="">Select carrier...</option>
                            <?php foreach ($carriers as $code => $name): ?>
                                <option value="<?= $code ?>" <?= $order['shipping_carrier'] === $code ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="tracking_number">Tracking Number *</label>
                        <input type="text" id="tracking_number" name="tracking_number"
                               value="<?= htmlspecialchars($order['tracking_number'] ?? '') ?>"
                               placeholder="Enter tracking number" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="estimated_delivery">Estimated Delivery (optional)</label>
                    <input type="text" id="estimated_delivery" name="estimated_delivery"
                           value="<?= htmlspecialchars($order['estimated_delivery'] ?? '') ?>"
                           placeholder="e.g., December 28-30, 2025">
                </div>

                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" name="notify_customer" value="1" checked>
                        Send shipping notification email to customer
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="addTrackingBtn">
                        <?= $order['tracking_number'] ? 'Update Tracking' : 'Add Tracking & Mark Shipped' ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Notes -->
        <div class="admin-card">
            <h3>Order Notes</h3>
            <form action="/admin/orders/notes" method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="id" value="<?= $order['id'] ?>">
                <div class="form-group">
                    <textarea name="notes" rows="4" placeholder="Add internal notes about this order..."><?= htmlspecialchars($order['notes'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-outline btn-sm">Save Notes</button>
            </form>
        </div>
    </div>

    <!-- Right Column -->
    <div class="order-sidebar">
        <!-- Customer Info -->
        <div class="admin-card">
            <h3>Customer</h3>
            <div class="customer-details">
                <div class="customer-email">
                    <a href="mailto:<?= htmlspecialchars($order['customer_email']) ?>">
                        <?= htmlspecialchars($order['customer_email']) ?>
                    </a>
                </div>
                <?php if ($order['user_first_name']): ?>
                    <div class="customer-name">
                        <?= htmlspecialchars($order['user_first_name'] . ' ' . $order['user_last_name']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Shipping Address -->
        <div class="admin-card">
            <h3>Shipping Address</h3>
            <div class="address">
                <p>
                    <?= htmlspecialchars($order['shipping_first_name'] . ' ' . $order['shipping_last_name']) ?><br>
                    <?= htmlspecialchars($order['shipping_address1']) ?>
                    <?php if ($order['shipping_address2']): ?>
                        <br><?= htmlspecialchars($order['shipping_address2']) ?>
                    <?php endif; ?>
                    <br><?= htmlspecialchars($order['shipping_city']) ?>, <?= htmlspecialchars($order['shipping_state']) ?> <?= htmlspecialchars($order['shipping_postal']) ?>
                    <br><?= htmlspecialchars($order['shipping_country']) ?>
                </p>
            </div>
        </div>

        <!-- Billing Address -->
        <div class="admin-card">
            <h3>Billing Address</h3>
            <div class="address">
                <p>
                    <?= htmlspecialchars($order['billing_first_name'] . ' ' . $order['billing_last_name']) ?><br>
                    <?= htmlspecialchars($order['billing_address1']) ?>
                    <?php if ($order['billing_address2']): ?>
                        <br><?= htmlspecialchars($order['billing_address2']) ?>
                    <?php endif; ?>
                    <br><?= htmlspecialchars($order['billing_city']) ?>, <?= htmlspecialchars($order['billing_state']) ?> <?= htmlspecialchars($order['billing_postal']) ?>
                    <br><?= htmlspecialchars($order['billing_country']) ?>
                </p>
            </div>
        </div>

        <!-- Order Status -->
        <div class="admin-card">
            <h3>Update Status</h3>
            <form action="/admin/orders/status" method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="id" value="<?= $order['id'] ?>">
                <div class="form-group">
                    <select name="status" class="status-select">
                        <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                        <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                        <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                        <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        <option value="refunded" <?= $order['status'] === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-outline btn-block">Update Status</button>
            </form>
        </div>

        <!-- Order Info -->
        <div class="admin-card">
            <h3>Order Information</h3>
            <div class="info-list">
                <div class="info-row">
                    <span class="label">Order Date:</span>
                    <span class="value"><?= date('M j, Y g:i A', strtotime($order['created_at'])) ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Payment:</span>
                    <span class="value">
                        <span class="payment-badge payment-<?= $order['payment_status'] ?>">
                            <?= ucfirst($order['payment_status']) ?>
                        </span>
                    </span>
                </div>
                <?php if ($order['shipping_method']): ?>
                    <div class="info-row">
                        <span class="label">Shipping Method:</span>
                        <span class="value"><?= htmlspecialchars($order['shipping_method']) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($order['stripe_payment_intent_id']): ?>
                    <div class="info-row">
                        <span class="label">Stripe ID:</span>
                        <span class="value mono"><?= htmlspecialchars(substr($order['stripe_payment_intent_id'], 0, 20)) ?>...</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Costs & Profit -->
        <div class="admin-card costs-card">
            <h3>Costs & Profit</h3>
            <?php
            // Calculate product costs from order items
            $productCost = 0;
            $hasMissingCosts = false;
            foreach ($orderItems as $item) {
                if (isset($item['product_cost']) && $item['product_cost'] !== null) {
                    $productCost += $item['product_cost'] * $item['quantity'];
                } else {
                    $hasMissingCosts = true;
                }
            }
            $actualShipping = $order['actual_shipping_cost'] ?? null;
            $shippingCharged = $order['shipping_cost'] ?? 0;
            $revenue = $order['total'];

            // Calculate profit if we have all data
            $canCalculateProfit = !$hasMissingCosts && $actualShipping !== null;
            $totalCost = $productCost + ($actualShipping ?? 0);
            $profit = $revenue - $totalCost;
            $profitMargin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;
            ?>

            <div class="costs-summary">
                <div class="cost-row">
                    <span class="label">Revenue (Order Total):</span>
                    <span class="value">$<?= number_format($revenue, 2) ?></span>
                </div>
                <div class="cost-row">
                    <span class="label">Product Cost:</span>
                    <span class="value <?= $hasMissingCosts ? 'incomplete' : '' ?>" id="productCostValue">
                        $<?= number_format($productCost, 2) ?>
                        <?php if ($hasMissingCosts): ?>
                            <span class="warning-icon" title="Some products are missing cost data">&#9888;</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="cost-row">
                    <span class="label">Shipping Charged:</span>
                    <span class="value"><?= $shippingCharged > 0 ? '$' . number_format($shippingCharged, 2) : 'FREE' ?></span>
                </div>
            </div>

            <form action="/admin/orders/update-shipping-cost" method="POST" class="actual-shipping-form">
                <?= csrfField() ?>
                <input type="hidden" name="id" value="<?= $order['id'] ?>">
                <div class="form-group">
                    <label for="actual_shipping_cost">Actual Shipping Cost</label>
                    <div class="input-with-button">
                        <span class="currency-prefix">$</span>
                        <input type="number" id="actual_shipping_cost" name="actual_shipping_cost"
                               step="0.01" min="0"
                               value="<?= $actualShipping !== null ? number_format($actualShipping, 2, '.', '') : '' ?>"
                               placeholder="0.00">
                        <button type="submit" class="btn btn-sm btn-outline">Save</button>
                    </div>
                </div>
            </form>

            <div class="profit-summary" style="<?= $canCalculateProfit ? '' : 'display:none;' ?>">
                <div class="profit-row total-cost">
                    <span class="label">Total Cost:</span>
                    <span class="value" id="totalCostValue">$<?= number_format($totalCost, 2) ?></span>
                </div>
                <div class="profit-row profit <?= $profit >= 0 ? 'positive' : 'negative' ?>">
                    <span class="label">Profit:</span>
                    <span class="value">
                        <span id="profitValue"><?= $profit >= 0 ? '' : '-' ?>$<?= number_format(abs($profit), 2) ?></span>
                        <span class="margin" id="marginValue">(<?= number_format($profitMargin, 1) ?>%)</span>
                    </span>
                </div>
            </div>
            <?php if (!$canCalculateProfit): ?>
                <?php if (!$hasMissingCosts && $actualShipping === null): ?>
                    <p class="help-text">Enter actual shipping cost to see profit.</p>
                <?php elseif ($hasMissingCosts): ?>
                    <p class="help-text warning">Some products are missing cost data. <a href="/admin/products?needs_cost=1">View products needing costs</a></p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleTrackingForm() {
    const form = document.getElementById('trackingForm');
    form.classList.toggle('hidden');
}

document.getElementById('trackingForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const btn = document.getElementById('addTrackingBtn');
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Saving...';

    try {
        const formData = new FormData(this);

        const response = await fetch('/admin/orders/tracking', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert(data.error || 'Failed to add tracking');
            btn.disabled = false;
            btn.textContent = originalText;
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
        btn.disabled = false;
        btn.textContent = originalText;
    }
});

// Item cost update handling
document.querySelectorAll('.item-cost-input').forEach(input => {
    let timeout;

    input.addEventListener('input', function() {
        clearTimeout(timeout);
        this.classList.remove('saved');
        this.classList.add('saving');

        timeout = setTimeout(() => updateItemCost(this), 800);
    });

    input.addEventListener('blur', function() {
        clearTimeout(timeout);
        updateItemCost(this);
    });
});

async function updateItemCost(input) {
    const itemId = input.dataset.itemId;
    const orderId = input.dataset.orderId;
    const cost = input.value;

    input.classList.add('saving');

    try {
        const formData = new FormData();
        formData.append('item_id', itemId);
        formData.append('order_id', orderId);
        formData.append('cost', cost);
        formData.append('_csrf_token', '<?= csrfToken() ?>');

        const response = await fetch('/admin/orders/update-item-cost', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        const data = await response.json();

        input.classList.remove('saving');

        if (data.success) {
            input.classList.add('saved');
            setTimeout(() => input.classList.remove('saved'), 2000);

            // Update profit display if available
            updateProfitDisplay(data);
        } else {
            alert(data.error || 'Failed to update cost');
        }
    } catch (error) {
        input.classList.remove('saving');
        console.error('Error updating cost:', error);
    }
}

function updateProfitDisplay(data) {
    const productCostEl = document.getElementById('productCostValue');
    const totalCostEl = document.getElementById('totalCostValue');
    const profitEl = document.getElementById('profitValue');
    const marginEl = document.getElementById('marginValue');
    const profitSummary = document.querySelector('.profit-summary');
    const profitRow = document.querySelector('.profit-row.profit');
    const helpText = document.querySelector('.costs-card .help-text');

    if (productCostEl) {
        productCostEl.textContent = '$' + data.productCost.toFixed(2);
    }

    if (data.canCalculateProfit && profitSummary) {
        profitSummary.style.display = 'block';
        if (totalCostEl) totalCostEl.textContent = '$' + data.totalCost.toFixed(2);
        if (profitEl) {
            profitEl.textContent = (data.profit >= 0 ? '' : '-') + '$' + Math.abs(data.profit).toFixed(2);
        }
        if (marginEl) {
            marginEl.textContent = '(' + data.profitMargin.toFixed(1) + '%)';
        }
        if (profitRow) {
            profitRow.classList.remove('positive', 'negative');
            profitRow.classList.add(data.profit >= 0 ? 'positive' : 'negative');
        }
        if (helpText) helpText.style.display = 'none';
    }
}
</script>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
}

.header-left {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.back-link {
    color: #6b7280;
    text-decoration: none;
    font-size: 14px;
}

.back-link:hover {
    color: #FF68C5;
}

.status-badge.large {
    font-size: 14px;
    padding: 8px 16px;
}

.order-grid {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 1.5rem;
}

.admin-card h3 {
    margin: 0 0 1rem 0;
    font-size: 16px;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: 0.75rem;
}

/* Order Items */
.order-items {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.order-item {
    display: grid;
    grid-template-columns: 60px 1fr auto auto auto;
    gap: 1rem;
    align-items: center;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 8px;
}

.item-image img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 6px;
}

.item-name {
    font-weight: 500;
    color: #374151;
}

.item-variant {
    font-size: 13px;
    color: #6b7280;
}

.item-sku {
    font-size: 12px;
    color: #9ca3af;
    font-family: monospace;
}

.item-manufacturer {
    font-size: 12px;
    color: #FF68C5;
    font-weight: 500;
}

.item-cost-edit {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    margin-top: 0.5rem;
    font-size: 12px;
}

.item-cost-edit label {
    color: #6b7280;
}

.item-cost-input {
    width: 70px;
    padding: 0.25rem 0.5rem;
    border: 1px solid #e5e7eb;
    border-radius: 4px;
    font-size: 12px;
    text-align: right;
}

.item-cost-input:focus {
    outline: none;
    border-color: #FF68C5;
}

.item-cost-input.saving {
    background: #fef3c7;
}

.item-cost-input.saved {
    background: #d1fae5;
}

.item-quantity {
    color: #6b7280;
}

.item-price, .item-total {
    font-weight: 500;
    color: #374151;
}

.item-total {
    font-weight: 600;
}

/* Order Totals */
.order-totals {
    border-top: 1px solid #e5e7eb;
    padding-top: 1rem;
}

.total-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    color: #6b7280;
}

.total-row.discount {
    color: #059669;
}

.total-row.grand-total {
    font-size: 18px;
    font-weight: 600;
    color: #374151;
    border-top: 1px solid #e5e7eb;
    margin-top: 0.5rem;
    padding-top: 1rem;
}

/* Tracking */
.tracking-info {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 1rem;
    background: #ecfdf5;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.tracking-details {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.tracking-row {
    display: flex;
    gap: 0.5rem;
}

.tracking-row .label {
    color: #6b7280;
    font-size: 14px;
}

.tracking-row .value {
    font-weight: 500;
    color: #374151;
}

.tracking-number {
    font-family: monospace;
    background: #d1fae5;
    padding: 2px 8px;
    border-radius: 4px;
}

.tracking-form {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
}

.tracking-form.hidden {
    display: none;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-size: 14px;
    font-weight: 500;
    color: #374151;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 14px;
}

.form-group textarea {
    resize: vertical;
}

.checkbox-group label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.checkbox-group input[type="checkbox"] {
    width: auto;
}

.form-actions {
    margin-top: 1rem;
}

/* Sidebar */
.customer-details {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.customer-email a {
    color: #FF68C5;
    text-decoration: none;
}

.customer-name {
    color: #6b7280;
    font-size: 14px;
}

.address p {
    margin: 0;
    line-height: 1.6;
    color: #4b5563;
}

.info-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.info-row {
    display: flex;
    justify-content: space-between;
    font-size: 14px;
}

.info-row .label {
    color: #6b7280;
}

.info-row .value {
    color: #374151;
    font-weight: 500;
}

.info-row .mono {
    font-family: monospace;
    font-size: 12px;
}

.payment-badge {
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 12px;
}

.payment-paid { background: #d1fae5; color: #065f46; }
.payment-pending { background: #fef3c7; color: #92400e; }
.payment-failed { background: #fee2e2; color: #991b1b; }
.payment-refunded { background: #f3e8ff; color: #6b21a8; }

.status-select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
}

.btn-block {
    display: block;
    width: 100%;
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

/* Costs & Profit Card */
.costs-card .costs-summary {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.costs-card .cost-row,
.costs-card .profit-row {
    display: flex;
    justify-content: space-between;
    font-size: 14px;
}

.costs-card .cost-row .label {
    color: #6b7280;
}

.costs-card .cost-row .value {
    font-weight: 500;
    color: #374151;
}

.costs-card .value.incomplete {
    color: #f59e0b;
}

.costs-card .warning-icon {
    color: #f59e0b;
    cursor: help;
}

.actual-shipping-form {
    margin-bottom: 1rem;
}

.actual-shipping-form .form-group {
    margin-bottom: 0;
}

.input-with-button {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.input-with-button .currency-prefix {
    color: #6b7280;
    font-weight: 500;
}

.input-with-button input {
    flex: 1;
    padding: 0.5rem 0.75rem;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 14px;
    width: auto;
}

.profit-summary {
    background: #f9fafb;
    padding: 1rem;
    border-radius: 8px;
    margin-top: 1rem;
}

.profit-summary .profit-row {
    display: flex;
    justify-content: space-between;
    font-size: 14px;
    padding: 0.25rem 0;
}

.profit-summary .profit-row.total-cost {
    padding-bottom: 0.5rem;
    margin-bottom: 0.5rem;
    border-bottom: 1px dashed #e5e7eb;
}

.profit-summary .profit-row.profit {
    font-weight: 600;
    font-size: 16px;
}

.profit-summary .profit-row.profit.positive .value {
    color: #059669;
}

.profit-summary .profit-row.profit.negative .value {
    color: #dc2626;
}

.profit-summary .margin {
    font-size: 12px;
    font-weight: normal;
    color: #6b7280;
}

.costs-card .help-text {
    font-size: 13px;
    color: #6b7280;
    margin: 0;
    padding: 0.75rem;
    background: #f9fafb;
    border-radius: 6px;
}

.costs-card .help-text.warning {
    background: #fffbeb;
    color: #92400e;
}

.costs-card .help-text a {
    color: #FF68C5;
    text-decoration: none;
}

.costs-card .help-text a:hover {
    text-decoration: underline;
}

@media (max-width: 1024px) {
    .order-grid {
        grid-template-columns: 1fr;
    }

    .order-item {
        grid-template-columns: 60px 1fr;
    }

    .item-quantity, .item-price, .item-total {
        grid-column: 2;
        justify-self: start;
    }
}
</style>
