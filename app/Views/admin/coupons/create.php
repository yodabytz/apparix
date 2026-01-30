<div class="page-header">
    <h1>Create Coupon</h1>
    <a href="/admin/coupons" class="btn btn-outline">Back to Coupons</a>
</div>

<form action="/admin/coupons/store" method="POST" class="admin-form">
    <?php echo csrfField(); ?>

    <div class="admin-card">
        <h2>Coupon Details</h2>

        <div class="form-row">
            <div class="form-group">
                <label for="code">Coupon Code *</label>
                <div class="input-group">
                    <input type="text" id="code" name="code" required
                           value="<?php echo escape($suggestedCode); ?>"
                           style="text-transform: uppercase; font-family: monospace; font-size: 1.1em;">
                    <button type="button" class="btn btn-outline" onclick="generateNewCode()">Generate</button>
                </div>
                <small class="form-help">Customers will enter this code at checkout</small>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <input type="text" id="description" name="description" placeholder="e.g., Summer Sale 20% off">
                <small class="form-help">Internal description (not shown to customers)</small>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="type">Discount Type *</label>
                <select id="type" name="type" required onchange="updateValueLabel()">
                    <option value="percentage">Percentage (%)</option>
                    <option value="fixed">Fixed Amount ($)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="value" id="valueLabel">Discount Value (%) *</label>
                <input type="number" id="value" name="value" required min="0.01" step="0.01" placeholder="10">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="min_purchase">Minimum Purchase ($)</label>
                <input type="number" id="min_purchase" name="min_purchase" min="0" step="0.01" placeholder="0.00">
                <small class="form-help">Leave empty or 0 for no minimum</small>
            </div>

            <div class="form-group">
                <label for="max_uses">Maximum Uses</label>
                <input type="number" id="max_uses" name="max_uses" min="1" placeholder="Unlimited">
                <small class="form-help">Leave empty for unlimited uses</small>
            </div>
        </div>
    </div>

    <div class="admin-card">
        <h2>Applies To</h2>

        <div class="form-group">
            <label>Discount applies to:</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="applies_to" value="all" checked onchange="toggleAppliesTo()">
                    All products
                </label>
                <label class="radio-label">
                    <input type="radio" name="applies_to" value="products" onchange="toggleAppliesTo()">
                    Specific products
                </label>
                <label class="radio-label">
                    <input type="radio" name="applies_to" value="categories" onchange="toggleAppliesTo()">
                    Specific categories
                </label>
            </div>
        </div>

        <div id="productsSelect" class="form-group" style="display: none;">
            <label>Select Products:</label>
            <select name="product_ids[]" multiple class="multi-select" size="8">
                <?php foreach ($products as $product): ?>
                    <option value="<?php echo $product['id']; ?>"><?php echo escape($product['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <small class="form-help">Hold Ctrl/Cmd to select multiple products</small>
        </div>

        <div id="categoriesSelect" class="form-group" style="display: none;">
            <label>Select Categories:</label>
            <select name="category_ids[]" multiple class="multi-select" size="6">
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>"><?php echo escape($category['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <small class="form-help">Hold Ctrl/Cmd to select multiple categories</small>
        </div>
    </div>

    <div class="admin-card">
        <h2>Restrictions</h2>

        <div class="form-row">
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="requires_account" value="1" checked>
                    Requires customer account
                </label>
                <small class="form-help">Customer must be logged in to use this coupon</small>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="one_per_customer" value="1" checked>
                    One use per customer
                </label>
                <small class="form-help">Each customer can only use this coupon once</small>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="starts_at">Start Date</label>
                <input type="datetime-local" id="starts_at" name="starts_at">
                <small class="form-help">Leave empty to start immediately</small>
            </div>

            <div class="form-group">
                <label for="expires_at">Expiry Date</label>
                <input type="datetime-local" id="expires_at" name="expires_at">
                <small class="form-help">Leave empty for no expiry</small>
            </div>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" value="1" checked>
                Active
            </label>
            <small class="form-help">Inactive coupons cannot be used</small>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary btn-large">Create Coupon</button>
        <a href="/admin/coupons" class="btn btn-outline">Cancel</a>
    </div>
</form>

<script>
function generateNewCode() {
    fetch('/admin/coupons/generate-code')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('code').value = data.code;
            }
        });
}

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
.input-group {
    display: flex;
    gap: 0.5rem;
}
.input-group input {
    flex: 1;
}
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
.form-help {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.85em;
    color: #666;
}
</style>
