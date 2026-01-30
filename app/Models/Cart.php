<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Cart extends Model
{
    protected string $table = 'cart';

    /**
     * Add item to cart (with optional variant)
     */
    public function addItem($productId, $quantity = 1, $sessionId = null, $userId = null, $variantId = null)
    {
        $db = Database::getInstance();

        // Build query based on whether we're checking by user or session, and whether variant exists
        if ($userId) {
            if ($variantId) {
                $existingItem = $db->selectOne(
                    "SELECT id, quantity FROM {$this->table} WHERE product_id = ? AND user_id = ? AND variant_id = ?",
                    [$productId, $userId, $variantId]
                );
            } else {
                $existingItem = $db->selectOne(
                    "SELECT id, quantity FROM {$this->table} WHERE product_id = ? AND user_id = ? AND variant_id IS NULL",
                    [$productId, $userId]
                );
            }
        } else {
            if ($variantId) {
                $existingItem = $db->selectOne(
                    "SELECT id, quantity FROM {$this->table} WHERE product_id = ? AND session_id = ? AND variant_id = ?",
                    [$productId, $sessionId, $variantId]
                );
            } else {
                $existingItem = $db->selectOne(
                    "SELECT id, quantity FROM {$this->table} WHERE product_id = ? AND session_id = ? AND variant_id IS NULL",
                    [$productId, $sessionId]
                );
            }
        }

        if ($existingItem) {
            // Update quantity
            $newQuantity = $existingItem['quantity'] + $quantity;
            return $db->update(
                "UPDATE {$this->table} SET quantity = ? WHERE id = ?",
                [$newQuantity, $existingItem['id']]
            );
        } else {
            // Insert new item
            return $db->insert(
                "INSERT INTO {$this->table} (product_id, variant_id, quantity, session_id, user_id) VALUES (?, ?, ?, ?, ?)",
                [$productId, $variantId, $quantity, $sessionId, $userId]
            );
        }
    }

    /**
     * Get all cart items for a session/user (with variant info)
     */
    public function getItems($sessionId = null, $userId = null)
    {
        $db = Database::getInstance();

        $query = "
            SELECT
                c.id,
                c.product_id,
                c.variant_id,
                c.quantity,
                p.name,
                p.slug,
                p.price,
                p.sale_price,
                p.inventory_count as product_inventory,
                p.weight_oz,
                p.ships_free,
                p.ships_free_us,
                p.shipping_price,
                p.is_digital,
                COALESCE(sc.handling_fee, 0) as handling_fee,
                pv.sku as variant_sku,
                pv.inventory_count as variant_inventory,
                pv.price_adjustment,
                (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) as image
            FROM {$this->table} c
            JOIN products p ON c.product_id = p.id
            LEFT JOIN product_variants pv ON c.variant_id = pv.id
            LEFT JOIN shipping_classes sc ON p.shipping_class_id = sc.id
            WHERE " . ($userId ? "c.user_id = ?" : "c.session_id = ?") . "
            ORDER BY c.created_at DESC
        ";

        $items = $db->select($query, [$userId ?? $sessionId]);

        // Add variant name and variant-specific image for items with variants
        foreach ($items as &$item) {
            if ($item['variant_id']) {
                // Get variant option values (for display name)
                $variantInfo = $db->selectOne(
                    "SELECT GROUP_CONCAT(pov.value_name ORDER BY po.sort_order SEPARATOR ' / ') as name,
                            GROUP_CONCAT(pov.id ORDER BY po.sort_order) as option_value_ids
                     FROM variant_option_values vov
                     JOIN product_option_values pov ON vov.option_value_id = pov.id
                     JOIN product_options po ON pov.option_id = po.id
                     WHERE vov.variant_id = ?",
                    [$item['variant_id']]
                );
                $item['variant_name'] = $variantInfo['name'] ?? '';
                $item['inventory_count'] = $item['variant_inventory'];
                $item['sku'] = $item['variant_sku'];

                // Try to find an image linked to this variant's color/option
                if (!empty($variantInfo['option_value_ids'])) {
                    $optionValueIds = explode(',', $variantInfo['option_value_ids']);
                    // Look for an image linked to any of the variant's option values
                    $variantImage = $db->selectOne(
                        "SELECT pi.image_path
                         FROM product_images pi
                         JOIN product_image_option_values piov ON pi.id = piov.image_id
                         WHERE pi.product_id = ? AND piov.option_value_id IN (" . implode(',', array_fill(0, count($optionValueIds), '?')) . ")
                         ORDER BY pi.is_primary DESC, pi.sort_order ASC
                         LIMIT 1",
                        array_merge([$item['product_id']], $optionValueIds)
                    );
                    if ($variantImage) {
                        $item['image'] = $variantImage['image_path'];
                    }
                }
            } else {
                $item['variant_name'] = '';
                $item['inventory_count'] = $item['product_inventory'];
                $item['sku'] = null;
            }
        }

        return $items;
    }

    /**
     * Update item quantity (with ownership validation)
     */
    public function updateQuantity($cartItemId, $quantity, $sessionId = null, $userId = null)
    {
        $db = Database::getInstance();

        // Build ownership condition to prevent unauthorized access
        $ownershipCondition = '';
        $params = [];

        if ($userId) {
            $ownershipCondition = ' AND (user_id = ? OR session_id = ?)';
            $params = [$userId, $sessionId];
        } elseif ($sessionId) {
            $ownershipCondition = ' AND session_id = ?';
            $params = [$sessionId];
        }

        if ($quantity <= 0) {
            // Remove item if quantity is 0 or less
            return $db->update(
                "DELETE FROM {$this->table} WHERE id = ?" . $ownershipCondition,
                array_merge([$cartItemId], $params)
            );
        }

        return $db->update(
            "UPDATE {$this->table} SET quantity = ? WHERE id = ?" . $ownershipCondition,
            array_merge([$quantity, $cartItemId], $params)
        );
    }

    /**
     * Remove item from cart (with ownership validation)
     */
    public function removeItem($cartItemId, $sessionId = null, $userId = null)
    {
        $db = Database::getInstance();

        // Build ownership condition to prevent unauthorized access
        $ownershipCondition = '';
        $params = [];

        if ($userId) {
            $ownershipCondition = ' AND (user_id = ? OR session_id = ?)';
            $params = [$userId, $sessionId];
        } elseif ($sessionId) {
            $ownershipCondition = ' AND session_id = ?';
            $params = [$sessionId];
        }

        return $db->update(
            "DELETE FROM {$this->table} WHERE id = ?" . $ownershipCondition,
            array_merge([$cartItemId], $params)
        );
    }

    /**
     * Get a single cart item by ID with ownership validation
     */
    public function getItemById($cartItemId, $sessionId = null, $userId = null)
    {
        $db = Database::getInstance();

        // Build ownership condition
        $ownershipCondition = '';
        $params = [$cartItemId];

        if ($userId) {
            $ownershipCondition = ' AND (c.user_id = ? OR c.session_id = ?)';
            $params[] = $userId;
            $params[] = $sessionId;
        } elseif ($sessionId) {
            $ownershipCondition = ' AND c.session_id = ?';
            $params[] = $sessionId;
        }

        return $db->selectOne(
            "SELECT c.*,
                    p.inventory_count as product_inventory,
                    v.inventory_count as variant_inventory
             FROM {$this->table} c
             LEFT JOIN products p ON c.product_id = p.id
             LEFT JOIN product_variants v ON c.variant_id = v.id
             WHERE c.id = ?" . $ownershipCondition,
            $params
        );
    }

    /**
     * Clear all items from cart
     */
    public function clear($sessionId = null, $userId = null)
    {
        $db = Database::getInstance();

        if ($userId) {
            return $db->update(
                "DELETE FROM {$this->table} WHERE user_id = ?",
                [$userId]
            );
        } else {
            return $db->update(
                "DELETE FROM {$this->table} WHERE session_id = ?",
                [$sessionId]
            );
        }
    }

    /**
     * Get cart count (number of items)
     */
    public function getCount($sessionId = null, $userId = null)
    {
        $items = $this->getItems($sessionId, $userId);
        $count = 0;
        foreach ($items as $item) {
            $count += $item['quantity'];
        }
        return $count;
    }

    /**
     * Get cart total
     */
    public function getTotal($sessionId = null, $userId = null)
    {
        $items = $this->getItems($sessionId, $userId);
        $total = 0;

        foreach ($items as $item) {
            $price = $item['sale_price'] ?? $item['price'];
            // Add price adjustment for variants
            if ($item['price_adjustment']) {
                $price += $item['price_adjustment'];
            }
            $total += $price * $item['quantity'];
        }

        return $total;
    }

    /**
     * Merge guest cart with user cart on login
     */
    public function mergeOnLogin($sessionId, $userId)
    {
        $db = Database::getInstance();

        // Get guest cart items
        $guestItems = $this->getItems($sessionId);

        foreach ($guestItems as $item) {
            // Check if user already has this product/variant in cart
            if ($item['variant_id']) {
                $existingItem = $db->selectOne(
                    "SELECT id, quantity FROM {$this->table} WHERE product_id = ? AND variant_id = ? AND user_id = ?",
                    [$item['product_id'], $item['variant_id'], $userId]
                );
            } else {
                $existingItem = $db->selectOne(
                    "SELECT id, quantity FROM {$this->table} WHERE product_id = ? AND variant_id IS NULL AND user_id = ?",
                    [$item['product_id'], $userId]
                );
            }

            if ($existingItem) {
                // Update quantity
                $newQuantity = $existingItem['quantity'] + $item['quantity'];
                $db->update(
                    "UPDATE {$this->table} SET quantity = ? WHERE id = ?",
                    [$newQuantity, $existingItem['id']]
                );
                // Remove the guest item
                $db->update(
                    "DELETE FROM {$this->table} WHERE id = ?",
                    [$item['id']]
                );
            } else {
                // Move guest item to user
                $db->update(
                    "UPDATE {$this->table} SET user_id = ?, session_id = NULL WHERE id = ?",
                    [$userId, $item['id']]
                );
            }
        }
    }

    /**
     * Check if item is in cart
     */
    public function hasItem($productId, $sessionId = null, $userId = null, $variantId = null)
    {
        $db = Database::getInstance();

        $query = "SELECT id FROM {$this->table} WHERE product_id = ? AND ";
        $params = [$productId];

        if ($userId) {
            $query .= "user_id = ?";
            $params[] = $userId;
        } else {
            $query .= "session_id = ?";
            $params[] = $sessionId;
        }

        if ($variantId) {
            $query .= " AND variant_id = ?";
            $params[] = $variantId;
        } else {
            $query .= " AND variant_id IS NULL";
        }

        return $db->selectOne($query, $params) !== false;
    }
}
