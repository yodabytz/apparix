<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class User extends Model
{
    protected string $table = 'users';

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?array
    {
        $result = $this->queryOne(
            "SELECT * FROM {$this->table} WHERE email = ?",
            [strtolower(trim($email))]
        );
        return $result ?: null;
    }

    /**
     * Find user by ID
     */
    public function findById(int $id): ?array
    {
        $result = $this->find($id);
        return $result ?: null;
    }

    /**
     * Create a new user
     */
    public function createUser(string $email, string $password, ?string $firstName = null, ?string $lastName = null, bool $newsletterSubscribed = false): ?int
    {
        $email = strtolower(trim($email));

        // Check if email already exists
        if ($this->findByEmail($email)) {
            return null;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $id = $this->db->insert(
            "INSERT INTO {$this->table} (email, password_hash, first_name, last_name, newsletter_subscribed) VALUES (?, ?, ?, ?, ?)",
            [$email, $passwordHash, $firstName, $lastName, $newsletterSubscribed ? 1 : 0]
        );

        return (int)$id;
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Update user profile
     */
    public function updateProfile(int $id, array $data): bool
    {
        $allowed = ['first_name', 'last_name', 'phone', 'newsletter_subscribed'];
        $updates = [];
        $values = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowed)) {
                $updates[] = "{$key} = ?";
                $values[] = $value;
            }
        }

        if (empty($updates)) {
            return false;
        }

        $values[] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $updates) . " WHERE id = ?";

        return $this->db->update($sql, $values) > 0;
    }

    /**
     * Update password
     */
    public function updatePassword(int $id, string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        return $this->db->update(
            "UPDATE {$this->table} SET password_hash = ? WHERE id = ?",
            [$hash, $id]
        ) > 0;
    }

    /**
     * Set remember token for "remember me" functionality
     */
    public function setRememberToken(int $id): string
    {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));

        $this->db->update(
            "UPDATE {$this->table} SET remember_token = ?, remember_token_expires = ? WHERE id = ?",
            [$token, $expires, $id]
        );

        return $token;
    }

    /**
     * Find user by remember token
     */
    public function findByRememberToken(string $token): ?array
    {
        $result = $this->queryOne(
            "SELECT * FROM {$this->table} WHERE remember_token = ? AND remember_token_expires > NOW()",
            [$token]
        );
        return $result ?: null;
    }

    /**
     * Clear remember token
     */
    public function clearRememberToken(int $id): void
    {
        $this->db->update(
            "UPDATE {$this->table} SET remember_token = NULL, remember_token_expires = NULL WHERE id = ?",
            [$id]
        );
    }

    /**
     * Get user's orders
     */
    public function getOrders(int $userId, int $limit = 20): array
    {
        return $this->db->select(
            "SELECT o.*,
                    (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
             FROM orders o
             WHERE o.user_id = ?
             ORDER BY o.created_at DESC
             LIMIT ?",
            [$userId, $limit]
        );
    }

    /**
     * Get user's addresses
     */
    public function getAddresses(int $userId): array
    {
        return $this->db->select(
            "SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, type, created_at DESC",
            [$userId]
        );
    }

    /**
     * Get user's used coupons
     */
    public function getUsedCoupons(int $userId): array
    {
        return $this->db->select(
            "SELECT cu.*, dc.code, dc.type, dc.value, dc.description
             FROM coupon_usage cu
             JOIN discount_codes dc ON cu.discount_code_id = dc.id
             WHERE cu.user_id = ?
             ORDER BY cu.used_at DESC",
            [$userId]
        );
    }

    /**
     * Get all users with order count
     */
    public function getAllUsers(int $limit = 100, int $offset = 0, ?string $search = null): array
    {
        $params = [];
        $where = "";

        if ($search) {
            $where = "WHERE u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?";
            $searchTerm = "%{$search}%";
            $params = [$searchTerm, $searchTerm, $searchTerm];
        }

        $params[] = $limit;
        $params[] = $offset;

        return $this->db->select(
            "SELECT u.id, u.email, u.first_name, u.last_name, u.phone,
                    u.newsletter_subscribed, u.created_at,
                    COUNT(DISTINCT o.id) as order_count,
                    COALESCE(SUM(o.total), 0) as total_spent
             FROM {$this->table} u
             LEFT JOIN orders o ON u.id = o.user_id
             {$where}
             GROUP BY u.id
             ORDER BY u.created_at DESC
             LIMIT ? OFFSET ?",
            $params
        );
    }

    /**
     * Count total users
     */
    public function countUsers(?string $search = null): int
    {
        $params = [];
        $where = "";

        if ($search) {
            $where = "WHERE email LIKE ? OR first_name LIKE ? OR last_name LIKE ?";
            $searchTerm = "%{$search}%";
            $params = [$searchTerm, $searchTerm, $searchTerm];
        }

        $result = $this->queryOne(
            "SELECT COUNT(*) as count FROM {$this->table} {$where}",
            $params
        );
        return (int)($result['count'] ?? 0);
    }

    /**
     * Delete user and related data
     */
    public function deleteUser(int $id): bool
    {
        // Delete related data first
        $this->db->update("DELETE FROM favorites WHERE user_id = ?", [$id]);
        $this->db->update("DELETE FROM addresses WHERE user_id = ?", [$id]);
        $this->db->update("DELETE FROM coupon_usage WHERE user_id = ?", [$id]);
        $this->db->update("DELETE FROM stock_notifications WHERE user_id = ?", [$id]);

        // Set orders to have null user_id (keep order history)
        $this->db->update("UPDATE orders SET user_id = NULL WHERE user_id = ?", [$id]);

        // Delete the user
        return $this->db->update("DELETE FROM {$this->table} WHERE id = ?", [$id]) > 0;
    }
}
