# Product Personalization Feature - Implementation Guide

This document describes the product personalization feature implemented on lilyspadstudio.com. Apply equivalent changes to this codebase.

---

## Overview

Allows admin to enable per-product personalization (e.g., name engraving, custom text). Customers enter text on the product page, it flows through cart/checkout, and is saved on orders.

---

## 1. Database Migrations

Run these SQL statements:

```sql
ALTER TABLE products ADD COLUMN allow_personalization TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE products ADD COLUMN personalization_label VARCHAR(255) DEFAULT NULL;
ALTER TABLE products ADD COLUMN personalization_max_length INT DEFAULT NULL;

ALTER TABLE cart ADD COLUMN personalization_text TEXT DEFAULT NULL;

ALTER TABLE order_items ADD COLUMN personalization_text TEXT DEFAULT NULL;
```

---

## 2. Admin Product Forms (Create & Edit)

### Controller (store/update methods)

When saving a product, read these 3 new POST fields:

```php
$allowPersonalization = $this->post('allow_personalization') ? 1 : 0;
$personalizationLabel = $allowPersonalization ? trim($this->post('personalization_label', '')) ?: null : null;
$personalizationMaxLength = $allowPersonalization && $this->post('personalization_max_length') ? intval($this->post('personalization_max_length')) : null;
```

Include them in your INSERT/UPDATE queries for the products table.

### Views (create.php and edit.php)

Add a "Personalization" card in the sidebar with:

```html
<div class="card">
    <h3 class="card-title" style="margin-bottom: 1rem;">Personalization</h3>

    <div class="form-group">
        <label class="form-checkbox">
            <input type="checkbox" name="allow_personalization" value="1" id="allowPersonalization"
                   <?php echo !empty($product['allow_personalization']) ? 'checked' : ''; ?>
                   onchange="togglePersonalizationFields()">
            <span>Allow Personalization</span>
        </label>
    </div>

    <div id="personalizationFields" style="<?php echo !empty($product['allow_personalization']) ? '' : 'display: none;'; ?>">
        <div class="form-group">
            <label class="form-label" for="personalization_label">Personalization Label</label>
            <input type="text" id="personalization_label" name="personalization_label" class="form-input"
                   value="<?php echo escape($product['personalization_label'] ?? ''); ?>"
                   placeholder="e.g., Enter name to embroider">
        </div>

        <div class="form-group">
            <label class="form-label" for="personalization_max_length">Max Characters</label>
            <input type="number" id="personalization_max_length" name="personalization_max_length" class="form-input" min="1"
                   value="<?php echo $product['personalization_max_length'] ?? ''; ?>"
                   placeholder="e.g., 50">
        </div>
    </div>
</div>

<script>
function togglePersonalizationFields() {
    const cb = document.getElementById('allowPersonalization');
    document.getElementById('personalizationFields').style.display = cb.checked ? 'block' : 'none';
}
</script>
```

For the **create** form, remove the `value=` and `checked` PHP lines since there's no existing product data.

---

## 3. Product Detail Page (show.php / product view)

Inside the add-to-cart form, **before the quantity selector**, add:

```html
<?php if (!empty($product['allow_personalization'])): ?>
    <div class="personalization-section" style="margin-bottom: 1rem;">
        <label for="personalization_text" style="display: block; font-weight: 600; margin-bottom: 0.5rem;">
            <?php echo escape($product['personalization_label'] ?: 'Personalization'); ?>
        </label>
        <input type="text" id="personalization_text" name="personalization_text"
               <?php if (!empty($product['personalization_max_length'])): ?>
                   maxlength="<?php echo (int)$product['personalization_max_length']; ?>"
               <?php endif; ?>
               placeholder="Enter your custom text"
               style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem;">
        <?php if (!empty($product['personalization_max_length'])): ?>
            <small style="display: block; margin-top: 0.25rem; color: #6b7280; font-size: 0.8rem;">
                <span id="personalizationCount">0</span> / <?php echo (int)$product['personalization_max_length']; ?> characters
            </small>
        <?php endif; ?>
    </div>
<?php endif; ?>
```

Add this JS anywhere in the page script:

```js
(function() {
    const input = document.getElementById('personalization_text');
    const counter = document.getElementById('personalizationCount');
    if (input && counter) {
        input.addEventListener('input', function() {
            counter.textContent = this.value.length;
        });
    }
})();
```

---

## 4. Cart Controller (add method)

When adding to cart, read and validate personalization text from POST **after** the product is loaded:

```php
// Personalization text - validate against product settings
$personalizationText = null;
$rawPersonalization = trim($this->post('personalization_text', ''));
if ($rawPersonalization !== '') {
    if (empty($product['allow_personalization'])) {
        $rawPersonalization = '';
    } else {
        $maxLen = $product['personalization_max_length'] ?? null;
        if ($maxLen && mb_strlen($rawPersonalization) > (int)$maxLen) {
            $rawPersonalization = mb_substr($rawPersonalization, 0, (int)$maxLen);
        }
        $personalizationText = htmlspecialchars(trim(strip_tags($rawPersonalization)), ENT_QUOTES, 'UTF-8');
        if ($personalizationText === '') {
            $personalizationText = null;
        }
    }
}
```

Pass `$personalizationText` to the cart model's `addItem()` method.

---

## 5. Cart Model

### addItem() - items with different personalization text stay separate

Add `$personalizationText = null` as a new parameter. When checking for existing items, also match on personalization_text:

- If personalization is NULL, use `personalization_text IS NULL`
- If personalization has a value, use `personalization_text = ?`

Only merge quantities when product_id + variant_id + personalization_text all match.

INSERT must include the `personalization_text` column.

### getItems() - include personalization_text in SELECT

Add `c.personalization_text` to the SELECT query.

### mergeOnLogin() - respect personalization differences

When merging guest cart into user cart, treat items with different `personalization_text` as separate. Add the same NULL/value condition as in addItem().

---

## 6. Cart View

Below variant info display, show personalization text if present:

```html
<?php if (!empty($item['personalization_text'])): ?>
    <small style="display: block; color: #6b7280; font-style: italic;">
        Personalization: <?php echo escape($item['personalization_text']); ?>
    </small>
<?php endif; ?>
```

---

## 7. Checkout View

Same display as cart view - show personalization below variant info in the order summary sidebar.

---

## 8. Checkout Controller (process/place order)

When inserting into `order_items`, include `personalization_text`:

```php
// In the order_items INSERT, add personalization_text column and value:
$item['personalization_text'] ?? null
```

---

## 9. Admin Order View

Below variant display in order items, show personalization:

```html
<?php if (!empty($item['personalization_text'])): ?>
    <div style="font-size: 0.85rem; color: #7c3aed; font-style: italic; margin-top: 0.25rem;">
        Personalization: <?= htmlspecialchars($item['personalization_text']) ?>
    </div>
<?php endif; ?>
```

---

## Security Notes

- **Server-side validation**: Always check `allow_personalization` on the product before accepting text
- **Server-side max length**: Enforce `personalization_max_length` on the server, not just client `maxlength`
- **Sanitization**: Use `strip_tags()` then `htmlspecialchars()` before storing
- **Normalize empties**: Convert empty string to NULL for consistent SQL IS NULL comparisons
- **Output escaping**: Always use `escape()` / `htmlspecialchars()` when displaying personalization text

---
---

# Option-Value Inventory Tracking (Shared Inventory Pools)

## Overview

Tracks inventory at the **option value** level for products that have options but NO variants. For example, a monogram cake topper with "Letter 1" (A-Z) and "Letter 2" (A-Z) would need 676 variants. Instead, both "Letter 1 = A" and "Letter 2 = A" share the same physical letter "A" stock via **shared inventory groups**.

This feature does NOT affect products that use variants — it only applies to products with options and no variants.

---

## 1. Database Migrations

```sql
-- Add inventory tracking to option values
ALTER TABLE product_option_values ADD COLUMN inventory_count INT DEFAULT NULL;
ALTER TABLE product_option_values ADD COLUMN shared_inventory_group VARCHAR(100) DEFAULT NULL;

-- Track which options were selected when adding to cart (for non-variant products)
CREATE TABLE cart_item_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    option_id INT NOT NULL,
    option_value_id INT NOT NULL,
    FOREIGN KEY (cart_id) REFERENCES cart(id) ON DELETE CASCADE,
    FOREIGN KEY (option_id) REFERENCES product_options(id) ON DELETE CASCADE,
    FOREIGN KEY (option_value_id) REFERENCES product_option_values(id) ON DELETE CASCADE
);

-- Track options selected on completed orders
CREATE TABLE order_item_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_item_id INT NOT NULL,
    option_name VARCHAR(255) NOT NULL,
    value_name VARCHAR(255) NOT NULL,
    option_value_id INT DEFAULT NULL,
    FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE
);

-- Hash for cart item deduplication (same product + same option selections = merge)
ALTER TABLE cart ADD COLUMN selected_options_hash VARCHAR(64) DEFAULT NULL;
```

**Shared inventory groups**: Values with the same `shared_inventory_group` string share a physical pool. Inventory is the **minimum** across all group members. When an order decrements inventory, ALL members of the group are decremented.

---

## 2. Product Model

Add two new methods:

### `getOptionValueInventory(int $productId): array`

Returns `[optionValueId => effectiveInventory]` for values with non-NULL `inventory_count`. Resolves shared groups by returning the minimum inventory across all group members.

```php
public function getOptionValueInventory(int $productId): array
{
    $values = $this->query(
        "SELECT pov.id, pov.inventory_count, pov.shared_inventory_group
         FROM product_option_values pov
         JOIN product_options po ON pov.option_id = po.id
         WHERE po.product_id = ? AND pov.inventory_count IS NOT NULL",
        [$productId]
    );

    if (empty($values)) return [];

    // Resolve shared group minimums
    $groupMinimums = [];
    foreach ($values as $val) {
        if ($val['shared_inventory_group']) {
            $group = $val['shared_inventory_group'];
            if (!isset($groupMinimums[$group]) || $val['inventory_count'] < $groupMinimums[$group]) {
                $groupMinimums[$group] = (int)$val['inventory_count'];
            }
        }
    }

    $result = [];
    foreach ($values as $val) {
        if ($val['shared_inventory_group'] && isset($groupMinimums[$val['shared_inventory_group']])) {
            $result[(int)$val['id']] = $groupMinimums[$val['shared_inventory_group']];
        } else {
            $result[(int)$val['id']] = (int)$val['inventory_count'];
        }
    }
    return $result;
}
```

### `hasOptionValueInventory(int $productId): bool`

Returns true if any option value for this product has non-NULL `inventory_count`.

```php
public function hasOptionValueInventory(int $productId): bool
{
    $result = $this->queryOne(
        "SELECT COUNT(*) as cnt FROM product_option_values pov
         JOIN product_options po ON pov.option_id = po.id
         WHERE po.product_id = ? AND pov.inventory_count IS NOT NULL",
        [$productId]
    );
    return ($result['cnt'] ?? 0) > 0;
}
```

---

## 3. Product Controller (`show()`)

After loading the full product, fetch option-value inventory and pass to view:

```php
$optionValueInventory = $this->productModel->getOptionValueInventory($product['id']);

// Add to $data array:
'optionValueInventory' => $optionValueInventory,
```

---

## 4. Product Detail Page (show.php)

### PHP data injection — add JS constants after existing `const variants = ...`:

```js
const optionValueInventory = <?php echo json_encode($optionValueInventory ?? new \stdClass()); ?>;
const hasOptionValueInventory = <?php echo !empty($optionValueInventory) ? 'true' : 'false'; ?>;
```

### Initial stock status — for products with option-value inventory, show "Select Options":

```php
<?php $hasOptionInventory = !empty($optionValueInventory); ?>
// In stock status display:
<?php if ($hasVariants): ?>
    <?php echo $initialStock > 0 ? 'Select Options' : 'Out of Stock'; ?>
<?php elseif ($hasOptionInventory): ?>
    Select Options
<?php elseif ($initialStock > 0): ?>
    In Stock
<?php else: ?>
    Out of Stock
<?php endif; ?>
```

### Add to Cart button — also disable for option-value inventory products initially:

```php
<button ... id="addToCartBtn" <?php echo ($hasVariants || $hasOptionInventory || $initialStock <= 0) ? 'disabled' : ''; ?>>
    <?php echo ($hasVariants || $hasOptionInventory) ? 'Select Options' : ($initialStock > 0 ? 'Add to Cart' : 'Out of Stock'); ?>
</button>
```

### JS: Add option-value inventory branch in `updateVariantSelection()`

Insert this **after** the `if (!allSelected) { return; }` block and **before** the `const matchingVariant = variants.find(...)`:

```js
// Option-value inventory path (products with options but no variants)
if (variants.length === 0 && hasOptionValueInventory) {
    let minStock = Infinity;
    let allInStock = true;
    selectedValues.forEach(valId => {
        const stock = optionValueInventory[valId];
        if (stock !== undefined) {
            if (stock <= 0) allInStock = false;
            if (stock < minStock) minStock = stock;
        }
    });

    if (allInStock && minStock > 0) {
        stockStatus.textContent = minStock <= 3 ? `Only ${minStock} left!` : 'In Stock';
        stockStatus.className = minStock <= 3 ? 'low-stock' : 'in-stock';
        btn.textContent = 'Add to Cart';
        btn.disabled = false;
        document.getElementById('quantity').max = minStock;
    } else {
        stockStatus.textContent = 'Out of Stock';
        stockStatus.className = 'out-of-stock';
        btn.textContent = 'Out of Stock';
        btn.disabled = true;
    }
    return; // Skip variant matching
}
```

### JS: Update form submit handler

Allow submission when `hasOptionValueInventory` and all options selected (not just when variant selected):

```js
document.getElementById('addToCartForm').addEventListener('submit', function(e) {
    const hasVariants = <?php echo $hasVariants ? 'true' : 'false'; ?>;
    const variantSelected = !!document.getElementById('variantId').value;

    if (hasVariants && !variantSelected) {
        e.preventDefault();
        alert('Please select all options before adding to cart.');
    } else if (!hasVariants && hasOptionValueInventory) {
        const selects = document.querySelectorAll('.option-select');
        let allSelected = true;
        selects.forEach(s => { if (!s.value) allSelected = false; });
        if (!allSelected) {
            e.preventDefault();
            alert('Please select all options before adding to cart.');
            return;
        }
        if (typeof gtag_report_conversion === 'function') gtag_report_conversion();
    } else {
        if (typeof gtag_report_conversion === 'function') gtag_report_conversion();
    }
});
```

---

## 5. Cart Controller (`add()`)

After personalization handling and before calling `addItem()`, add option-value inventory handling for products with **no variant** but **option-value inventory**:

```php
$selectedOptionsHash = null;
$selectedOptions = [];
if (!$variantId && !empty($_POST['options'])) {
    $hasOptionInventory = $this->productModel->hasOptionValueInventory($productId);
    if ($hasOptionInventory) {
        $db = \App\Core\Database::getInstance();

        // Validate each selected option value belongs to the product
        foreach ($_POST['options'] as $optionId => $valueId) {
            $optionId = (int)$optionId;
            $valueId = (int)$valueId;
            if (!$valueId) continue;

            $valid = $db->selectOne(
                "SELECT pov.id FROM product_option_values pov
                 JOIN product_options po ON pov.option_id = po.id
                 WHERE pov.id = ? AND po.id = ? AND po.product_id = ?",
                [$valueId, $optionId, $productId]
            );
            if (!$valid) {
                http_response_code(400);
                return $this->json(['error' => 'Invalid option selection']);
            }
            $selectedOptions[$optionId] = $valueId;
        }

        if (empty($selectedOptions)) {
            http_response_code(400);
            return $this->json(['error' => 'Please select all options']);
        }

        // Check inventory per selected option value
        $optionValueInventory = $this->productModel->getOptionValueInventory($productId);
        foreach ($selectedOptions as $optId => $valId) {
            if (isset($optionValueInventory[$valId]) && $optionValueInventory[$valId] < $quantity) {
                http_response_code(400);
                return $this->json(['error' => 'Insufficient inventory for selected options']);
            }
        }

        // Hash for deduplication
        $sortedValues = array_values($selectedOptions);
        sort($sortedValues);
        $selectedOptionsHash = md5(json_encode($sortedValues));
    }
}
```

Pass `$selectedOptionsHash` to `addItem()`. After `addItem()` returns the cart item ID, store `cart_item_options`:

```php
$cartItemId = $this->cartModel->addItem($productId, $quantity, $sessionId, $userId, $variantId, $personalizationText, $selectedOptionsHash);

if (!empty($selectedOptions) && $cartItemId) {
    $db = \App\Core\Database::getInstance();
    $db->update("DELETE FROM cart_item_options WHERE cart_id = ?", [$cartItemId]);
    foreach ($selectedOptions as $optionId => $valueId) {
        $db->insert(
            "INSERT INTO cart_item_options (cart_id, option_id, option_value_id) VALUES (?, ?, ?)",
            [$cartItemId, $optionId, $valueId]
        );
    }
}
```

### Cart Controller `update()` — validate option-value inventory on quantity change

In the inventory check section, add a branch for option-value inventory items:

```php
if (!$cartItem['variant_id']) {
    $db = \App\Core\Database::getInstance();
    $cartItemOptions = $db->select(
        "SELECT cio.option_value_id FROM cart_item_options cio WHERE cio.cart_id = ?",
        [$cartItemId]
    );
    if (!empty($cartItemOptions)) {
        $optionValueInventory = $this->productModel->getOptionValueInventory($cartItem['product_id']);
        $minStock = PHP_INT_MAX;
        foreach ($cartItemOptions as $cio) {
            $valId = (int)$cio['option_value_id'];
            if (isset($optionValueInventory[$valId])) {
                $minStock = min($minStock, $optionValueInventory[$valId]);
            }
        }
        if ($minStock !== PHP_INT_MAX) {
            $availableStock = $minStock;
        }
    }
}
```

---

## 6. Cart Model

### `addItem()` — add `$selectedOptionsHash = null` parameter

- Add `$hashCondition` / `$hashParams` matching (same pattern as personalization: `IS NULL` or `= ?`)
- Include `selected_options_hash` in the INSERT
- **Return the cart item ID** (both for existing item update and new insert)

### `getItems()` — load option names for non-variant items

In the `else` branch (no variant_id), query `cart_item_options`:

```php
$selectedOptions = $db->select(
    "SELECT cio.option_id, po.option_name, pov.value_name
     FROM cart_item_options cio
     JOIN product_options po ON cio.option_id = po.id
     JOIN product_option_values pov ON cio.option_value_id = pov.id
     WHERE cio.cart_id = ?
     ORDER BY po.sort_order",
    [$item['id']]
);
if ($selectedOptions) {
    $item['variant_name'] = implode(' / ', array_column($selectedOptions, 'value_name'));
}
```

### `mergeOnLogin()` — include `selected_options_hash` in merge matching

Read the hash from the cart row and add the same `IS NULL` / `= ?` condition to both the variant and non-variant merge queries.

---

## 7. Checkout Controller

### Inventory locking (inside the transaction, after variant/product lock branches)

Add a **third branch** for option-value inventory. For non-variant items, check `cart_item_options`:

```php
$cartItemOptions = $db->select(
    "SELECT cio.option_value_id FROM cart_item_options cio WHERE cio.cart_id = ?",
    [$item['id']]
);

if (!empty($cartItemOptions)) {
    foreach ($cartItemOptions as $cio) {
        $lockedOV = $db->selectOne(
            "SELECT id, inventory_count, shared_inventory_group
             FROM product_option_values WHERE id = ? FOR UPDATE",
            [$cio['option_value_id']]
        );
        if ($lockedOV && $lockedOV['inventory_count'] !== null && $lockedOV['inventory_count'] < $item['quantity']) {
            $db->rollback();
            // Return insufficient inventory error
        }
        // Also lock shared group members (ORDER BY id to prevent deadlocks)
        if ($lockedOV && $lockedOV['shared_inventory_group']) {
            $groupMembers = $db->select(
                "SELECT id, inventory_count FROM product_option_values
                 WHERE shared_inventory_group = ? AND id != ? ORDER BY id FOR UPDATE",
                [$lockedOV['shared_inventory_group'], $cio['option_value_id']]
            );
            // Validate each group member has enough inventory
        }
    }
} else {
    // Standard product-level inventory lock (existing code)
}
```

### Order item options — save after order_items INSERT

The order_items INSERT must capture `$orderItemId` (the return value of `$db->insert()`). Then:

```php
$cartItemOptions = $db->select(
    "SELECT cio.option_id, po.option_name, pov.value_name, cio.option_value_id
     FROM cart_item_options cio
     JOIN product_options po ON cio.option_id = po.id
     JOIN product_option_values pov ON cio.option_value_id = pov.id
     WHERE cio.cart_id = ?",
    [$item['id']]
);
foreach ($cartItemOptions as $opt) {
    $db->insert(
        "INSERT INTO order_item_options (order_item_id, option_name, value_name, option_value_id)
         VALUES (?, ?, ?, ?)",
        [$orderItemId, $opt['option_name'], $opt['value_name'], $opt['option_value_id']]
    );
}
```

### Inventory decrement — third branch for option-value inventory

After the variant/product decrement branches, add:

```php
} elseif (!empty($cartItemOptions)) {
    $decrementedGroups = [];
    foreach ($cartItemOptions as $cio) {
        $ov = $db->selectOne(
            "SELECT shared_inventory_group FROM product_option_values WHERE id = ? FOR UPDATE",
            [$cio['option_value_id']]
        );
        if ($ov && $ov['shared_inventory_group']) {
            // Only decrement each shared group once
            if (in_array($ov['shared_inventory_group'], $decrementedGroups)) continue;
            $decrementedGroups[] = $ov['shared_inventory_group'];
            $db->update(
                "UPDATE product_option_values SET inventory_count = inventory_count - ?
                 WHERE shared_inventory_group = ? AND inventory_count >= ?",
                [$item['quantity'], $ov['shared_inventory_group'], $item['quantity']]
            );
        } else {
            $db->update(
                "UPDATE product_option_values SET inventory_count = inventory_count - ?
                 WHERE id = ? AND inventory_count >= ?",
                [$item['quantity'], $cio['option_value_id'], $item['quantity']]
            );
        }
    }
}
```

---

## 8. Admin Order View

Load and display option selections below product name:

```php
$itemOptions = \App\Core\Database::getInstance()->select(
    "SELECT option_name, value_name FROM order_item_options WHERE order_item_id = ?",
    [$item['id']]
);
if (!empty($itemOptions)) {
    $optionParts = [];
    foreach ($itemOptions as $io) {
        $optionParts[] = htmlspecialchars($io['option_name']) . ': ' . htmlspecialchars($io['value_name']);
    }
    echo '<div style="font-size: 0.85rem; color: #2563eb; margin-top: 0.25rem;">' . implode(' / ', $optionParts) . '</div>';
}
```

---

## 9. Admin Product Edit — Inventory Inputs per Option Value

### View changes

Next to each option value pill, show an inventory input if tracking is enabled:

```html
<span class="option-value" data-value-id="<?php echo $value['id']; ?>" style="display: inline-flex; align-items: center; gap: 4px;">
    <?php echo escape($value['value_name']); ?>
    <?php if ($value['inventory_count'] !== null): ?>
        <input type="number" min="0" value="<?php echo (int)$value['inventory_count']; ?>"
               style="width: 50px; padding: 2px 4px; font-size: 0.75rem; border: 1px solid var(--admin-border); border-radius: 3px; text-align: center;"
               onchange="updateOptionValue(<?php echo $value['id']; ?>, 'inventory_count', this.value)"
               title="Inventory count">
    <?php endif; ?>
    <!-- delete button -->
</span>
```

Add an "Enable Inventory" button per option (only when no values have inventory yet):

```html
<?php if (!$hasInventoryTracking): ?>
    <button type="button" class="btn btn-sm btn-outline" onclick="enableOptionInventory(<?php echo $option['id']; ?>)" style="font-size: 0.7rem;">Enable Inventory</button>
<?php endif; ?>
```

### JS functions

```js
function updateOptionValue(valueId, field, value) {
    fetch('/admin/products/update-option-value', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        credentials: 'same-origin',
        body: `_csrf_token=${csrfToken}&value_id=${valueId}&field=${field}&value=${encodeURIComponent(value)}`
    })
    .then(r => r.json())
    .then(data => { if (!data.success) alert(data.error || 'Failed to update'); });
}

function enableOptionInventory(optionId) {
    if (!confirm('Enable inventory tracking for all values in this option? Sets initial stock to 0.')) return;
    // Find the option section, get all value pills, POST inventory_count=0 for each, then reload
}
```

### Admin ProductController — new endpoint

```php
public function updateOptionValue(): void
{
    $this->requireValidCSRF();
    $valueId = (int)$this->post('value_id');
    $field = $this->post('field');
    $value = $this->post('value');

    if (!$valueId || !in_array($field, ['inventory_count', 'shared_inventory_group'])) {
        $this->json(['error' => 'Invalid request'], 400);
        return;
    }

    $db = Database::getInstance();
    if ($field === 'inventory_count') {
        $value = $value === '' || $value === null ? null : (int)$value;
    }

    $db->update(
        "UPDATE product_option_values SET {$field} = ? WHERE id = ?",
        [$value, $valueId]
    );
    $this->json(['success' => true]);
}
```

### Route

```php
$router->post('/admin/products/update-option-value', 'Admin\\ProductController', 'updateOptionValue');
```

---

## 10. Cart & Checkout Views

No changes needed — `variant_name` is already displayed for cart items. The `getItems()` change in step 6 populates `variant_name` from `cart_item_options` for non-variant items.

---

## Verification Checklist

1. **Database**: Run migrations, verify new columns/tables exist
2. **Seed data**: Set `inventory_count` and `shared_inventory_group` on option values for the target product
3. **Admin edit**: Product edit page > Options tab shows inventory inputs next to each value
4. **Frontend**: Select options — button shows "Add to Cart" with correct stock. Out-of-stock value shows "Out of Stock"
5. **Cart**: Add to cart with options. Cart shows option values as variant name (e.g., "A / B")
6. **Cart dedup**: Same product + same options = quantities merge. Different options = separate items
7. **Checkout**: Complete order. Admin order view shows "Letter 1: A / Letter 2: B"
8. **Inventory decrement**: After order, shared group members are all decremented
9. **No regression**: Products with variants still work exactly as before
10. **Admin views**: Fake/test customers and orders are hidden
