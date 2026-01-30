<?php

namespace App\Models;

use App\Core\Database;

class PopupCoupon
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Generate a popup coupon for email
     */
    public function generate(string $email, float $discountPercent = 10, int $validHours = 48, float $minOrder = 0): array
    {
        $email = strtolower(trim($email));

        // Check if this email already has an active coupon
        $existing = $this->getActiveForEmail($email);
        if ($existing) {
            return ['success' => true, 'coupon' => $existing, 'existing' => true];
        }

        // Generate unique code
        $code = 'SAVE' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$validHours} hours"));

        $id = $this->db->insert(
            "INSERT INTO popup_coupons (code, email, discount_percent, min_order, expires_at)
             VALUES (?, ?, ?, ?, ?)",
            [$code, $email, $discountPercent, $minOrder, $expiresAt]
        );

        return [
            'success' => true,
            'coupon' => [
                'id' => $id,
                'code' => $code,
                'discount_percent' => $discountPercent,
                'min_order' => $minOrder,
                'expires_at' => $expiresAt
            ],
            'existing' => false
        ];
    }

    /**
     * Get active coupon for email
     */
    public function getActiveForEmail(string $email): ?array
    {
        $result = $this->db->selectOne(
            "SELECT * FROM popup_coupons
             WHERE email = ? AND used = 0 AND expires_at > NOW()
             ORDER BY created_at DESC
             LIMIT 1",
            [strtolower(trim($email))]
        );
        return $result ?: null;
    }

    /**
     * Validate and get coupon
     */
    public function validate(string $code): array
    {
        $coupon = $this->db->selectOne(
            "SELECT * FROM popup_coupons WHERE code = ?",
            [strtoupper(trim($code))]
        );

        if (!$coupon) {
            return ['valid' => false, 'error' => 'Invalid coupon code'];
        }

        if ($coupon['used']) {
            return ['valid' => false, 'error' => 'This coupon has already been used'];
        }

        if (strtotime($coupon['expires_at']) < time()) {
            return ['valid' => false, 'error' => 'This coupon has expired'];
        }

        return ['valid' => true, 'coupon' => $coupon];
    }

    /**
     * Mark coupon as used
     */
    public function markUsed(int $couponId, int $orderId): void
    {
        $this->db->update(
            "UPDATE popup_coupons SET used = 1, used_at = NOW(), order_id = ? WHERE id = ?",
            [$orderId, $couponId]
        );
    }

    /**
     * Check if code is a popup coupon (for preventing stacking)
     * Note: selectOne returns false when no row found, not null
     */
    public function isPopupCoupon(string $code): bool
    {
        $result = $this->db->selectOne(
            "SELECT id FROM popup_coupons WHERE code = ?",
            [strtoupper(trim($code))]
        );
        return $result !== false;
    }

    /**
     * Clean expired coupons
     */
    public function cleanExpired(int $daysOld = 30): int
    {
        return $this->db->delete(
            "DELETE FROM popup_coupons WHERE expires_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$daysOld]
        );
    }

    /**
     * Get stats for admin
     */
    public function getStats(): array
    {
        $total = $this->db->selectOne("SELECT COUNT(*) as count FROM popup_coupons")['count'] ?? 0;
        $used = $this->db->selectOne("SELECT COUNT(*) as count FROM popup_coupons WHERE used = 1")['count'] ?? 0;
        $active = $this->db->selectOne("SELECT COUNT(*) as count FROM popup_coupons WHERE used = 0 AND expires_at > NOW()")['count'] ?? 0;

        return [
            'total_generated' => $total,
            'used' => $used,
            'active' => $active,
            'conversion_rate' => $total > 0 ? round(($used / $total) * 100, 1) : 0
        ];
    }
}
