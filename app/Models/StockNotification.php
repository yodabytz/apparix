<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class StockNotification extends Model
{
    protected string $table = 'stock_notifications';

    /**
     * Subscribe to back-in-stock notification
     */
    public function subscribe(string $email, int $productId, ?int $variantId = null, ?string $variantName = null): array
    {
        $db = Database::getInstance();

        // Check if already subscribed (pending status)
        $existing = $db->selectOne(
            "SELECT id FROM {$this->table}
             WHERE email = ? AND product_id = ? AND variant_id <=> ? AND status = 'pending'",
            [$email, $productId, $variantId]
        );

        if ($existing) {
            return ['success' => false, 'message' => 'You are already subscribed for this item'];
        }

        // Insert new subscription
        $db->insert(
            "INSERT INTO {$this->table} (email, product_id, variant_id, variant_name, status, created_at)
             VALUES (?, ?, ?, ?, 'pending', NOW())",
            [$email, $productId, $variantId, $variantName]
        );

        return ['success' => true, 'message' => 'You will be notified when this item is back in stock'];
    }

    /**
     * Get pending notifications for a product/variant
     */
    public function getPendingForProduct(int $productId, ?int $variantId = null): array
    {
        $db = Database::getInstance();

        if ($variantId) {
            return $db->select(
                "SELECT * FROM {$this->table}
                 WHERE product_id = ? AND variant_id = ? AND status = 'pending'
                 ORDER BY created_at ASC",
                [$productId, $variantId]
            );
        }

        return $db->select(
            "SELECT * FROM {$this->table}
             WHERE product_id = ? AND variant_id IS NULL AND status = 'pending'
             ORDER BY created_at ASC",
            [$productId]
        );
    }

    /**
     * Mark notifications as sent
     */
    public function markNotified(array $ids): void
    {
        if (empty($ids)) return;

        $db = Database::getInstance();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $db->update(
            "UPDATE {$this->table} SET status = 'notified', notified_at = NOW() WHERE id IN ($placeholders)",
            $ids
        );
    }

    /**
     * Get all pending notifications (for admin)
     */
    public function getAllPending(): array
    {
        $db = Database::getInstance();

        return $db->select(
            "SELECT sn.*, p.name as product_name, p.slug as product_slug,
                    pv.sku as variant_sku
             FROM {$this->table} sn
             JOIN products p ON sn.product_id = p.id
             LEFT JOIN product_variants pv ON sn.variant_id = pv.id
             WHERE sn.status = 'pending'
             ORDER BY sn.created_at DESC"
        );
    }

    /**
     * Get notification stats (for admin dashboard)
     */
    public function getStats(): array
    {
        $db = Database::getInstance();

        $stats = $db->selectOne(
            "SELECT
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                COUNT(CASE WHEN status = 'notified' THEN 1 END) as notified_count,
                COUNT(DISTINCT email) as unique_emails,
                COUNT(DISTINCT product_id) as unique_products
             FROM {$this->table}"
        );

        return $stats ?: ['pending_count' => 0, 'notified_count' => 0, 'unique_emails' => 0, 'unique_products' => 0];
    }

    /**
     * Cancel a notification
     */
    public function cancel(int $id): bool
    {
        $db = Database::getInstance();
        return $db->update(
            "UPDATE {$this->table} SET status = 'cancelled' WHERE id = ?",
            [$id]
        ) > 0;
    }

    /**
     * Get notifications by email
     */
    public function getByEmail(string $email): array
    {
        $db = Database::getInstance();

        return $db->select(
            "SELECT sn.*, p.name as product_name, p.slug as product_slug
             FROM {$this->table} sn
             JOIN products p ON sn.product_id = p.id
             WHERE sn.email = ? AND sn.status = 'pending'
             ORDER BY sn.created_at DESC",
            [$email]
        );
    }
}
