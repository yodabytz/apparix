<?php

namespace App\Models;

use App\Core\Database;

class Favorite
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Add a product to favorites
     */
    public function add(int $productId, ?int $userId = null, ?string $sessionId = null): bool
    {
        // Check if already favorited
        if ($this->isFavorited($productId, $userId, $sessionId)) {
            return true;
        }

        return $this->db->insert(
            "INSERT INTO favorites (product_id, user_id, session_id, created_at) VALUES (?, ?, ?, NOW())",
            [$productId, $userId, $sessionId]
        );
    }

    /**
     * Remove a product from favorites
     */
    public function remove(int $productId, ?int $userId = null, ?string $sessionId = null): bool
    {
        if ($userId) {
            $this->db->update(
                "DELETE FROM favorites WHERE product_id = ? AND user_id = ?",
                [$productId, $userId]
            );
        } else {
            $this->db->update(
                "DELETE FROM favorites WHERE product_id = ? AND session_id = ?",
                [$productId, $sessionId]
            );
        }
        return true;
    }

    /**
     * Toggle favorite status
     */
    public function toggle(int $productId, ?int $userId = null, ?string $sessionId = null): array
    {
        if ($this->isFavorited($productId, $userId, $sessionId)) {
            $this->remove($productId, $userId, $sessionId);
            return ['favorited' => false];
        } else {
            $this->add($productId, $userId, $sessionId);
            return ['favorited' => true];
        }
    }

    /**
     * Check if a product is favorited
     */
    public function isFavorited(int $productId, ?int $userId = null, ?string $sessionId = null): bool
    {
        if ($userId) {
            $result = $this->db->selectOne(
                "SELECT id FROM favorites WHERE product_id = ? AND user_id = ?",
                [$productId, $userId]
            );
        } else {
            $result = $this->db->selectOne(
                "SELECT id FROM favorites WHERE product_id = ? AND session_id = ?",
                [$productId, $sessionId]
            );
        }
        return !empty($result);
    }

    /**
     * Get all favorites for a user or session
     */
    public function getAll(?int $userId = null, ?string $sessionId = null): array
    {
        if ($userId) {
            return $this->db->select(
                "SELECT f.*, p.name, p.slug, p.price, p.sale_price, p.sku,
                        (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.sort_order ASC, pi.id ASC LIMIT 1) as primary_image
                 FROM favorites f
                 JOIN products p ON f.product_id = p.id
                 WHERE f.user_id = ? AND p.is_active = 1
                 ORDER BY f.created_at DESC",
                [$userId]
            );
        } else {
            return $this->db->select(
                "SELECT f.*, p.name, p.slug, p.price, p.sale_price, p.sku,
                        (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.sort_order ASC, pi.id ASC LIMIT 1) as primary_image
                 FROM favorites f
                 JOIN products p ON f.product_id = p.id
                 WHERE f.session_id = ? AND p.is_active = 1
                 ORDER BY f.created_at DESC",
                [$sessionId]
            );
        }
    }

    /**
     * Get favorite product IDs for quick lookup
     */
    public function getFavoriteIds(?int $userId = null, ?string $sessionId = null): array
    {
        if ($userId) {
            $results = $this->db->select(
                "SELECT product_id FROM favorites WHERE user_id = ?",
                [$userId]
            );
        } else {
            $results = $this->db->select(
                "SELECT product_id FROM favorites WHERE session_id = ?",
                [$sessionId]
            );
        }
        return array_map('intval', array_column($results, 'product_id'));
    }

    /**
     * Get count of favorites
     */
    public function getCount(?int $userId = null, ?string $sessionId = null): int
    {
        if ($userId) {
            $result = $this->db->selectOne(
                "SELECT COUNT(*) as count FROM favorites WHERE user_id = ?",
                [$userId]
            );
        } else {
            $result = $this->db->selectOne(
                "SELECT COUNT(*) as count FROM favorites WHERE session_id = ?",
                [$sessionId]
            );
        }
        return (int)($result['count'] ?? 0);
    }

    /**
     * Merge guest favorites into user account
     */
    public function mergeGuestFavorites(string $sessionId, int $userId): void
    {
        // Get guest favorites
        $guestFavorites = $this->db->select(
            "SELECT product_id FROM favorites WHERE session_id = ? AND user_id IS NULL",
            [$sessionId]
        );

        foreach ($guestFavorites as $fav) {
            // Only add if not already in user's favorites
            if (!$this->isFavorited($fav['product_id'], $userId, null)) {
                $this->db->insert(
                    "INSERT INTO favorites (product_id, user_id, created_at) VALUES (?, ?, NOW())",
                    [$fav['product_id'], $userId]
                );
            }
        }

        // Delete guest favorites
        $this->db->update(
            "DELETE FROM favorites WHERE session_id = ? AND user_id IS NULL",
            [$sessionId]
        );
    }
}
