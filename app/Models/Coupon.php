<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Coupon extends Model
{
    protected string $table = 'discount_codes';

    /**
     * Find coupon by code
     */
    public function findByCode(string $code): ?array
    {
        $result = $this->queryOne(
            "SELECT * FROM {$this->table} WHERE code = ? AND is_active = 1",
            [strtoupper(trim($code))]
        );
        return $result ?: null;
    }

    /**
     * Find coupon by ID
     */
    public function findById(int $id): ?array
    {
        $result = $this->queryOne(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id]
        );
        return $result ?: null;
    }

    /**
     * Validate coupon for use
     */
    public function validateCoupon(string $code, ?int $userId = null, float $cartTotal = 0, array $cartItems = []): array
    {
        $coupon = $this->findByCode($code);

        if (!$coupon) {
            return ['valid' => false, 'error' => 'Invalid coupon code'];
        }

        // Check if active
        if (!$coupon['is_active']) {
            return ['valid' => false, 'error' => 'This coupon is no longer active'];
        }

        // Check start date
        if ($coupon['starts_at'] && strtotime($coupon['starts_at']) > time()) {
            return ['valid' => false, 'error' => 'This coupon is not yet active'];
        }

        // Check expiry
        if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) {
            return ['valid' => false, 'error' => 'This coupon has expired'];
        }

        // Check max uses
        if ($coupon['max_uses'] && $coupon['uses'] >= $coupon['max_uses']) {
            return ['valid' => false, 'error' => 'This coupon has reached its usage limit'];
        }

        // Check minimum purchase
        if ($coupon['min_purchase'] && $cartTotal < $coupon['min_purchase']) {
            return ['valid' => false, 'error' => 'Minimum purchase of $' . number_format($coupon['min_purchase'], 2) . ' required'];
        }

        // Check if account required
        if ($coupon['requires_account'] && !$userId) {
            return ['valid' => false, 'error' => 'You must be logged in to use this coupon'];
        }

        // Check one per customer
        if ($coupon['one_per_customer'] && $userId) {
            $used = $this->queryOne(
                "SELECT id FROM coupon_usage WHERE discount_code_id = ? AND user_id = ?",
                [$coupon['id'], $userId]
            );
            if ($used) {
                return ['valid' => false, 'error' => 'You have already used this coupon'];
            }
        }

        // Check product/category restrictions
        if ($coupon['applies_to'] !== 'all' && !empty($cartItems)) {
            $applicableItems = $this->getApplicableItems($coupon, $cartItems);
            if (empty($applicableItems)) {
                return ['valid' => false, 'error' => 'This coupon does not apply to any items in your cart'];
            }
        }

        // Calculate discount
        $discount = $this->calculateDiscount($coupon, $cartTotal, $cartItems);

        return [
            'valid' => true,
            'coupon' => $coupon,
            'discount' => $discount
        ];
    }

    /**
     * Calculate discount amount
     */
    public function calculateDiscount(array $coupon, float $cartTotal, array $cartItems = []): float
    {
        $applicableTotal = $cartTotal;

        // If restricted to specific products/categories, calculate applicable total
        if ($coupon['applies_to'] !== 'all' && !empty($cartItems)) {
            $applicableItems = $this->getApplicableItems($coupon, $cartItems);
            $applicableTotal = 0;
            foreach ($applicableItems as $item) {
                $price = $item['sale_price'] ?? $item['price'] ?? 0;
                if (!empty($item['price_adjustment'])) {
                    $price += $item['price_adjustment'];
                }
                $qty = $item['quantity'] ?? 1;
                $applicableTotal += ($price * $qty);
            }
        }

        // Exclude sale items from discount calculation
        if (!empty($cartItems)) {
            $itemsToCheck = ($coupon['applies_to'] !== 'all' && !empty($cartItems))
                ? $this->getApplicableItems($coupon, $cartItems)
                : $cartItems;

            $nonSaleTotal = 0;
            foreach ($itemsToCheck as $item) {
                if (empty($item['sale_price'])) {
                    $price = $item['price'] ?? 0;
                    if (!empty($item['price_adjustment'])) {
                        $price += $item['price_adjustment'];
                    }
                    $qty = $item['quantity'] ?? 1;
                    $nonSaleTotal += ($price * $qty);
                }
            }
            $applicableTotal = min($applicableTotal, $nonSaleTotal);
        }

        if ($applicableTotal <= 0) {
            return 0;
        }

        if ($coupon['type'] === 'percentage') {
            $discount = $applicableTotal * ($coupon['value'] / 100);
        } else {
            $discount = min($coupon['value'], $applicableTotal);
        }

        return round($discount, 2);
    }

    /**
     * Get cart items that coupon applies to
     */
    private function getApplicableItems(array $coupon, array $cartItems): array
    {
        $applicable = [];

        if (($coupon['applies_to'] ?? '') === 'products' && !empty($coupon['product_ids'])) {
            $productIds = array_map('intval', explode(',', $coupon['product_ids']));
            foreach ($cartItems as $item) {
                if (!empty($item['product_id']) && in_array((int)$item['product_id'], $productIds)) {
                    $applicable[] = $item;
                }
            }
        } elseif (($coupon['applies_to'] ?? '') === 'categories' && !empty($coupon['category_ids'])) {
            $categoryIds = array_map('intval', explode(',', $coupon['category_ids']));
            $db = Database::getInstance();

            foreach ($cartItems as $item) {
                if (empty($item['product_id'])) {
                    continue;
                }
                $productCategories = $db->select(
                    "SELECT category_id FROM product_categories WHERE product_id = ?",
                    [$item['product_id']]
                );
                $productCatIds = array_column($productCategories, 'category_id');

                if (array_intersect($categoryIds, $productCatIds)) {
                    $applicable[] = $item;
                }
            }
        } else {
            return $cartItems;
        }

        return $applicable;
    }

    /**
     * Record coupon usage
     */
    public function recordUsage(int $couponId, int $userId, int $orderId): bool
    {
        $db = Database::getInstance();

        // Insert usage record
        $db->insert(
            "INSERT INTO coupon_usage (discount_code_id, user_id, order_id) VALUES (?, ?, ?)",
            [$couponId, $userId, $orderId]
        );

        // Increment uses count
        $db->update(
            "UPDATE {$this->table} SET uses = uses + 1 WHERE id = ?",
            [$couponId]
        );

        return true;
    }

    /**
     * Get all coupons for admin
     */
    public function getAllForAdmin(): array
    {
        return $this->query(
            "SELECT dc.*,
                    (SELECT COUNT(*) FROM coupon_usage WHERE discount_code_id = dc.id) as times_used
             FROM {$this->table} dc
             ORDER BY dc.created_at DESC"
        );
    }

    /**
     * Get coupon usage history
     */
    public function getUsageHistory(int $couponId): array
    {
        return $this->query(
            "SELECT cu.*, u.email, u.first_name, u.last_name, o.order_number, o.total
             FROM coupon_usage cu
             JOIN users u ON cu.user_id = u.id
             JOIN orders o ON cu.order_id = o.id
             WHERE cu.discount_code_id = ?
             ORDER BY cu.used_at DESC",
            [$couponId]
        );
    }

    /**
     * Generate unique coupon code
     */
    public function generateCode(int $length = 8): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
        } while ($this->findByCode($code));

        return $code;
    }

    /**
     * Create new coupon
     */
    public function createCoupon(array $data): int
    {
        $db = Database::getInstance();

        return $db->insert(
            "INSERT INTO {$this->table}
             (code, description, type, value, min_purchase, max_uses, applies_to, product_ids, category_ids, requires_account, one_per_customer, starts_at, expires_at, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                strtoupper($data['code']),
                $data['description'] ?? null,
                $data['type'],
                $data['value'],
                $data['min_purchase'] ?? 0,
                $data['max_uses'] ?? null,
                $data['applies_to'] ?? 'all',
                $data['product_ids'] ?? null,
                $data['category_ids'] ?? null,
                $data['requires_account'] ?? 1,
                $data['one_per_customer'] ?? 1,
                $data['starts_at'] ?? null,
                $data['expires_at'] ?? null,
                $data['is_active'] ?? 1
            ]
        );
    }

    /**
     * Update coupon
     */
    public function updateCoupon(int $id, array $data): bool
    {
        $db = Database::getInstance();

        return $db->update(
            "UPDATE {$this->table} SET
             code = ?, description = ?, type = ?, value = ?, min_purchase = ?, max_uses = ?,
             applies_to = ?, product_ids = ?, category_ids = ?, requires_account = ?,
             one_per_customer = ?, starts_at = ?, expires_at = ?, is_active = ?
             WHERE id = ?",
            [
                strtoupper($data['code']),
                $data['description'] ?? null,
                $data['type'],
                $data['value'],
                $data['min_purchase'] ?? 0,
                $data['max_uses'] ?? null,
                $data['applies_to'] ?? 'all',
                $data['product_ids'] ?? null,
                $data['category_ids'] ?? null,
                $data['requires_account'] ?? 1,
                $data['one_per_customer'] ?? 1,
                $data['starts_at'] ?? null,
                $data['expires_at'] ?? null,
                $data['is_active'] ?? 1,
                $id
            ]
        );
    }

    /**
     * Delete coupon
     */
    public function deleteCoupon(int $id): bool
    {
        $db = Database::getInstance();
        return $db->delete("DELETE FROM {$this->table} WHERE id = ?", [$id]);
    }
}
