<div class="page-header">
    <h1>Edit Coupon: <?php echo escape($coupon['code']); ?></h1>
    <a href="/admin/coupons" class="btn btn-outline">Back to Coupons</a>
</div>

<form action="/admin/coupons/update" method="POST" class="admin-form">
    <?php echo csrfField(); ?>
    <input type="hidden" name="id" value="<?php echo $coupon['id']; ?>">

    <div class="admin-card">
        <h2>Coupon Details</h2>

        <div class="form-row">
            <div class="form-group">
                <label for="code">Coupon Code *</label>
                <input type="text" id="code" name="code" required
                       value="<?php echo escape($coupon['code']); ?>"
                       style="text-transform: uppercase; font-family: monospace; font-size: 1.1em;">
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <input type="text" id="description" name="description"
                       value="<?php echo escape($coupon['description'] ?? ''); ?>"
                       placeholder="e.g., Summer Sale 20% off">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="type">Discount Type *</label>
                <select id="type" name="type" required onchange="updateValueLabel()">
                    <option value="percentage" <?php echo $coupon['type'] === 'percentage' ? 'selected' : ''; ?>>Percentage (%)</option>
                    <option value="fixed" <?php echo $coupon['type'] === 'fixed' ? 'selected' : ''; ?>>Fixed Amount ($)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="value" id="valueLabel">Discount Value (<?php echo $coupon['type'] === 'percentage' ? '%' : '$'; ?>) *</label>
                <input type="number" id="value" name="value" required min="0.01" step="0.01"
                       value="<?php echo $coupon['value']; ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="min_purchase">Minimum Purchase ($)</label>
                <input type="number" id="min_purchase" name="min_purchase" min="0" step="0.01"
                       value="<?php echo $coupon['min_purchase'] ?: ''; ?>">
            </div>

            <div class="form-group">
                <label for="max_uses">Maximum Uses</label>
                <input type="number" id="max_uses" name="max_uses" min="1"
                       value="<?php echo $coupon['max_uses'] ?: ''; ?>" placeholder="Unlimited">
            </div>
        </div>

        <div class="usage-stats">
            <p><strong>Current Usage:</strong> <?php echo (int)$coupon['uses']; ?> times used</p>
        </div>
    </div>

    <div class="admin-card">
        <h2>Applies To</h2>

        <?php
        $appliesTo = $coupon['applies_to'] ?? 'all';
        $productIds = $coupon['product_ids'] ? explode(',', $coupon['product_ids']) : [];
        $categoryIds = $coupon['category_ids'] ? explode(',', $coupon['category_ids']) : [];
        ?>

        <div class="form-group">
            <label>Discount applies to:</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="applies_to" value="all" <?php echo $appliesTo === 'all' ? 'checked' : ''; ?> onchange="toggleAppliesTo()">
                    All products
                </label>
                <label class="radio-label">
                    <input type="radio" name="applies_to" value="products" <?php echo $appliesTo === 'products' ? 'checked' : ''; ?> onchange="toggleAppliesTo()">
                    Specific products
                </label>
                <label class="radio-label">
                    <input type="radio" name="applies_to" value="categories" <?php echo $appliesTo === 'categories' ? 'checked' : ''; ?> onchange="toggleAppliesTo()">
                    Specific categories
                </label>
            </div>
        </div>

        <div id="productsSelect" class="form-group" style="display: <?php echo $appliesTo === 'products' ? 'block' : 'none'; ?>;">
            <label>Select Products:</label>
            <select name="product_ids[]" multiple class="multi-select" size="8">
                <?php foreach ($products as $product): ?>
                    <option value="<?php echo $product['id']; ?>" <?php echo in_array($product['id'], $productIds) ? 'selected' : ''; ?>>
                        <?php echo escape($product['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="categoriesSelect" class="form-group" style="display: <?php echo $appliesTo === 'categories' ? 'block' : 'none'; ?>;">
            <label>Select Categories:</label>
            <select name="category_ids[]" multiple class="multi-select" size="6">
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" <?php echo in_array($category['id'], $categoryIds) ? 'selected' : ''; ?>>
                        <?php echo escape($category['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="admin-card">
        <h2>Restrictions</h2>

        <div class="form-row">
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="requires_account" value="1" <?php echo $coupon['requires_account'] ? 'checked' : ''; ?>>
                    Requires customer account
                </label>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="one_per_customer" value="1" <?php echo $coupon['one_per_customer'] ? 'checked' : ''; ?>>
                    One use per customer
                </label>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="starts_at">Start Date</label>
                <input type="datetime-local" id="starts_at" name="starts_at"
                       value="<?php echo $coupon['starts_at'] ? date('Y-m-d\TH:i', strtotime($coupon['starts_at'])) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="expires_at">Expiry Date</label>
                <input type="datetime-local" id="expires_at" name="expires_at"
                       value="<?php echo $coupon['expires_at'] ? date('Y-m-d\TH:i', strtotime($coupon['expires_at'])) : ''; ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" value="1" <?php echo $coupon['is_active'] ? 'checked' : ''; ?>>
                Active
            </label>
        </div>
    </div>

    <?php if (!empty($usageHistory)): ?>
    <div class="admin-card">
        <h2>Usage History</h2>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Order</th>
                    <th>Order Total</th>
                    <th>Used At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usageHistory as $usage): ?>
                    <tr>
                        <td>
                            <?php echo escape($usage['first_name'] . ' ' . $usage['last_name']); ?>
                            <br><small><?php echo escape($usage['email']); ?></small>
                        </td>
                        <td><a href="/admin/orders/view?id=<?php echo $usage['order_id']; ?>"><?php echo escape($usage['order_number']); ?></a></td>
                        <td>$<?php echo number_format($usage['total'], 2); ?></td>
                        <td><?php echo date('M j, Y g:i A', strtotime($usage['used_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary btn-large">Update Coupon</button>
        <a href="/admin/coupons" class="btn btn-outline">Cancel</a>
    </div>
</form>

<script>
function updateValueLabel() {
    const type = document.getElementById('type').value;
    const label = document.getElementById('valueLabel');
    label.textContent = type === 'percentage' ? 'Discount Value (%) *' : 'Discount Value ($) *';
}

function toggleAppliesTo() {
    const selected = document.querySelector('input[name="applies_to"]:checked').value;
    document.getElementById('productsSelect').style.display = selected === 'products' ? 'block' : 'none';
    document.getElementById('categoriesSelect').style.display = selected === 'categories' ? 'block' : 'none';
}
</script>

<style>
.radio-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-top: 0.5rem;
}
.radio-label, .checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}
.multi-select {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
}
.usage-stats {
    background: #f5f5f5;
    padding: 1rem;
    border-radius: 4px;
    margin-top: 1rem;
}
</style>
