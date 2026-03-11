<?php

namespace App\Models;

use App\Core\Database;

class Bundle
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ========== Bundle CRUD ==========

    /**
     * Get all bundles with product counts (admin)
     */
    public function getAll(): array
    {
        return $this->db->select(
            "SELECT b.*,
                    (SELECT COUNT(*) FROM product_bundle_items WHERE bundle_id = b.id) as product_count
             FROM product_bundles b
             ORDER BY b.created_at DESC"
        );
    }

    /**
     * Find bundle by ID
     */
    public function findById(int $id): ?array
    {
        $result = $this->db->selectOne(
            "SELECT * FROM product_bundles WHERE id = ?",
            [$id]
        );
        return $result ?: null;
    }

    /**
     * Get bundle by ID with products
     */
    public function getById(int $id): ?array
    {
        $bundle = $this->findById($id);
        if (!$bundle) return null;

        $bundle['products'] = $this->db->select(
            "SELECT p.id, p.name, p.slug, p.price, p.sale_price,
                    (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, sort_order LIMIT 1) as image
             FROM product_bundle_items bi
             JOIN products p ON bi.product_id = p.id
             WHERE bi.bundle_id = ?
             ORDER BY p.name",
            [$id]
        );

        return $bundle;
    }

    /**
     * Create a bundle
     */
    public function createBundle(array $data, array $productIds): int
    {
        $slug = $this->generateSlug($data['name']);

        $id = $this->db->insert(
            "INSERT INTO product_bundles (name, slug, description, discount_type, discount_value, is_active, allow_coupon, expires_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['name'],
                $slug,
                $data['description'] ?? null,
                $data['discount_type'],
                $data['discount_value'],
                $data['is_active'] ?? 1,
                $data['allow_coupon'] ?? 0,
                !empty($data['expires_at']) ? $data['expires_at'] : null
            ]
        );

        foreach ($productIds as $productId) {
            $this->db->insert(
                "INSERT INTO product_bundle_items (bundle_id, product_id) VALUES (?, ?)",
                [(int)$id, (int)$productId]
            );
        }

        return (int)$id;
    }

    /**
     * Update a bundle
     */
    public function updateBundle(int $id, array $data, array $productIds): void
    {
        $this->db->update(
            "UPDATE product_bundles SET name = ?, description = ?, discount_type = ?, discount_value = ?, is_active = ?, allow_coupon = ?, expires_at = ?, updated_at = NOW()
             WHERE id = ?",
            [
                $data['name'],
                $data['description'] ?? null,
                $data['discount_type'],
                $data['discount_value'],
                $data['is_active'] ?? 1,
                $data['allow_coupon'] ?? 0,
                !empty($data['expires_at']) ? $data['expires_at'] : null,
                $id
            ]
        );

        // Replace product list
        $this->db->update("DELETE FROM product_bundle_items WHERE bundle_id = ?", [$id]);
        foreach ($productIds as $productId) {
            $this->db->insert(
                "INSERT INTO product_bundle_items (bundle_id, product_id) VALUES (?, ?)",
                [$id, (int)$productId]
            );
        }
    }

    /**
     * Delete a bundle
     */
    public function deleteBundle(int $id): void
    {
        $this->db->update("DELETE FROM product_bundle_items WHERE bundle_id = ?", [$id]);
        $this->db->update("DELETE FROM product_bundles WHERE id = ?", [$id]);
    }

    /**
     * Generate unique slug
     */
    private function generateSlug(string $name): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
        $original = $slug;
        $counter = 1;

        while ($this->db->selectOne("SELECT id FROM product_bundles WHERE slug = ?", [$slug])) {
            $slug = $original . '-' . $counter++;
        }

        return $slug;
    }

    // ========== Quantity Tiers ==========

    /**
     * Get quantity discount tiers for a product
     */
    public function getQuantityTiers(int $productId): array
    {
        return $this->db->select(
            "SELECT * FROM quantity_discounts WHERE product_id = ? ORDER BY min_quantity ASC",
            [$productId]
        );
    }

    /**
     * Save quantity discount tiers for a product
     */
    public function saveQuantityTiers(int $productId, array $tiers): void
    {
        $this->db->update("DELETE FROM quantity_discounts WHERE product_id = ?", [$productId]);

        foreach ($tiers as $tier) {
            $minQty = (int)($tier['min_quantity'] ?? 0);
            $discount = (float)($tier['discount_percent'] ?? 0);

            if ($minQty >= 2 && $discount > 0 && $discount <= 50) {
                $this->db->insert(
                    "INSERT INTO quantity_discounts (product_id, min_quantity, discount_percent) VALUES (?, ?, ?)",
                    [$productId, $minQty, $discount]
                );
            }
        }
    }

    // ========== Bundles for Product Display ==========

    /**
     * Get active bundles containing a specific product
     */
    public function getBundlesForProduct(int $productId): array
    {
        $bundles = $this->db->select(
            "SELECT b.*
             FROM product_bundles b
             JOIN product_bundle_items bi ON b.id = bi.bundle_id
             WHERE bi.product_id = ?
               AND b.is_active = 1
               AND (b.expires_at IS NULL OR b.expires_at > NOW())
             ORDER BY b.discount_value DESC",
            [$productId]
        );

        // Load products for each bundle
        foreach ($bundles as &$bundle) {
            $bundle['products'] = $this->db->select(
                "SELECT p.id, p.name, p.slug, p.price, p.sale_price,
                        (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, sort_order LIMIT 1) as image
                 FROM product_bundle_items bi
                 JOIN products p ON bi.product_id = p.id
                 WHERE bi.bundle_id = ? AND p.disabled = 0
                 ORDER BY p.name",
                [$bundle['id']]
            );
        }

        return $bundles;
    }

    // ========== Cart Discount Calculation ==========

    /**
     * Calculate ALL auto-discounts for cart items
     * Returns array of discount line items
     */
    public function calculateCartDiscounts(array $cartItems): array
    {
        $discounts = [];

        // 1. Quantity discounts
        $qtyDiscounts = $this->getCartQuantityDiscounts($cartItems);
        foreach ($qtyDiscounts as $d) {
            $discounts[] = $d;
        }

        // 2. Bundle discounts
        $bundleDiscounts = $this->getCartBundleDiscounts($cartItems);
        foreach ($bundleDiscounts as $d) {
            $discounts[] = $d;
        }

        return $discounts;
    }

    /**
     * Calculate quantity discounts for cart items
     */
    private function getCartQuantityDiscounts(array $cartItems): array
    {
        $discounts = [];

        // Group quantities by product_id (across variants)
        $productQty = [];
        $productSubtotals = [];
        $productNames = [];

        foreach ($cartItems as $item) {
            $pid = $item['product_id'];
            $qty = (int)$item['quantity'];
            $price = (float)($item['sale_price'] ?: $item['price']);
            if (!empty($item['price_adjustment'])) {
                $price += (float)$item['price_adjustment'];
            }

            $productQty[$pid] = ($productQty[$pid] ?? 0) + $qty;
            $productSubtotals[$pid] = ($productSubtotals[$pid] ?? 0) + ($price * $qty);
            $productNames[$pid] = $item['name'] ?? $item['product_name'] ?? 'Product';
        }

        // Check each product for applicable quantity tiers
        foreach ($productQty as $pid => $totalQty) {
            $tier = $this->db->selectOne(
                "SELECT * FROM quantity_discounts
                 WHERE product_id = ? AND min_quantity <= ?
                 ORDER BY min_quantity DESC LIMIT 1",
                [$pid, $totalQty]
            );

            if ($tier) {
                $discountAmount = round($productSubtotals[$pid] * ($tier['discount_percent'] / 100), 2);
                if ($discountAmount > 0) {
                    $discounts[] = [
                        'type' => 'quantity',
                        'label' => "Buy {$totalQty}+ " . $productNames[$pid] . ": {$tier['discount_percent']}% off",
                        'amount' => $discountAmount,
                        'product_id' => $pid,
                    ];
                }
            }
        }

        return $discounts;
    }

    /**
     * Calculate bundle discounts for cart items
     */
    private function getCartBundleDiscounts(array $cartItems): array
    {
        $discounts = [];

        // Get unique product IDs in cart
        $cartProductIds = array_unique(array_column($cartItems, 'product_id'));
        if (count($cartProductIds) < 2) return $discounts;

        // Build product price map (use first variant/line found)
        $productPrices = [];
        foreach ($cartItems as $item) {
            $pid = $item['product_id'];
            if (!isset($productPrices[$pid])) {
                $price = (float)($item['sale_price'] ?: $item['price']);
                if (!empty($item['price_adjustment'])) {
                    $price += (float)$item['price_adjustment'];
                }
                $productPrices[$pid] = $price;
            }
        }

        // Find active bundles where ALL products are in the cart
        $placeholders = implode(',', array_fill(0, count($cartProductIds), '?'));
        $bundles = $this->db->select(
            "SELECT b.*, COUNT(bi.product_id) as matched,
                    (SELECT COUNT(*) FROM product_bundle_items WHERE bundle_id = b.id) as total_products
             FROM product_bundles b
             JOIN product_bundle_items bi ON b.id = bi.bundle_id AND bi.product_id IN ({$placeholders})
             WHERE b.is_active = 1
               AND (b.expires_at IS NULL OR b.expires_at > NOW())
             GROUP BY b.id
             HAVING matched = total_products",
            $cartProductIds
        );

        foreach ($bundles as $bundle) {
            // Get bundle product IDs
            $bundleProducts = $this->db->select(
                "SELECT product_id FROM product_bundle_items WHERE bundle_id = ?",
                [$bundle['id']]
            );

            // Calculate bundle subtotal (1 unit of each at its price)
            $bundleSubtotal = 0;
            foreach ($bundleProducts as $bp) {
                $bundleSubtotal += $productPrices[$bp['product_id']] ?? 0;
            }

            // Calculate discount
            $discountAmount = 0;
            if ($bundle['discount_type'] === 'fixed') {
                $discountAmount = min((float)$bundle['discount_value'], $bundleSubtotal);
            } else {
                $pct = min((float)$bundle['discount_value'], 100);
                $discountAmount = round($bundleSubtotal * ($pct / 100), 2);
            }

            if ($discountAmount > 0) {
                $label = $bundle['name'] . ': ';
                if ($bundle['discount_type'] === 'fixed') {
                    $label .= '$' . number_format($bundle['discount_value'], 2) . ' off';
                } else {
                    $label .= $bundle['discount_value'] . '% off';
                }

                $discounts[] = [
                    'type' => 'bundle',
                    'label' => $label,
                    'amount' => $discountAmount,
                    'bundle_id' => $bundle['id'],
                    'allow_coupon' => (int)($bundle['allow_coupon'] ?? 0),
                ];
            }
        }

        return $discounts;
    }

    // ========== Admin Search ==========

    /**
     * Search products for bundle builder (AJAX)
     */
    public function searchProducts(string $query, array $excludeIds = []): array
    {
        $params = ['%' . $query . '%', '%' . $query . '%'];
        $excludeClause = '';

        if (!empty($excludeIds)) {
            $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
            $excludeClause = "AND p.id NOT IN ({$placeholders})";
            $params = array_merge($params, $excludeIds);
        }

        return $this->db->select(
            "SELECT p.id, p.name, p.price, p.slug,
                    (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, sort_order LIMIT 1) as image
             FROM products p
             WHERE p.disabled = 0 AND (p.name LIKE ? OR p.sku LIKE ?) {$excludeClause}
             ORDER BY p.name
             LIMIT 10",
            $params
        );
    }
}
