<?php

namespace App\Models;

use App\Core\Database;

class Referral
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Generate unique referral code for user
     */
    public function generateCode(int $userId): string
    {
        // Check if user already has a code
        $existing = $this->getCodeByUserId($userId);
        if ($existing) {
            return $existing['code'];
        }

        do {
            $code = 'REF-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        } while ($this->findByCode($code));

        $this->db->insert(
            "INSERT INTO referral_codes (user_id, code) VALUES (?, ?)",
            [$userId, $code]
        );

        return $code;
    }

    /**
     * Get referral code by user ID
     */
    public function getCodeByUserId(int $userId): ?array
    {
        $result = $this->db->selectOne(
            "SELECT * FROM referral_codes WHERE user_id = ? AND is_active = 1",
            [$userId]
        );
        return $result ?: null;
    }

    /**
     * Find by code
     */
    public function findByCode(string $code): ?array
    {
        $result = $this->db->selectOne(
            "SELECT rc.*, u.email, u.first_name
             FROM referral_codes rc
             JOIN users u ON rc.user_id = u.id
             WHERE rc.code = ? AND rc.is_active = 1",
            [strtoupper(trim($code))]
        );
        return $result ?: null;
    }

    /**
     * Record a referral use
     */
    public function recordUse(int $codeId, string $email, ?int $orderId = null): int
    {
        // Increment uses
        $this->db->update(
            "UPDATE referral_codes SET uses = uses + 1 WHERE id = ?",
            [$codeId]
        );

        // Record the use
        return $this->db->insert(
            "INSERT INTO referral_uses (referral_code_id, referred_email, order_id)
             VALUES (?, ?, ?)",
            [$codeId, strtolower($email), $orderId]
        );
    }

    /**
     * Get referral stats for a user
     */
    public function getUserStats(int $userId): array
    {
        $code = $this->getCodeByUserId($userId);

        if (!$code) {
            return [
                'code' => null,
                'total_referrals' => 0,
                'successful_orders' => 0,
                'total_credit' => 0,
                'pending_credit' => 0
            ];
        }

        $stats = $this->db->selectOne(
            "SELECT
                COUNT(*) as total_referrals,
                SUM(CASE WHEN order_id IS NOT NULL THEN 1 ELSE 0 END) as successful_orders,
                SUM(credit_amount) as total_credit,
                SUM(CASE WHEN credit_paid = 0 THEN credit_amount ELSE 0 END) as pending_credit
             FROM referral_uses
             WHERE referral_code_id = ?",
            [$code['id']]
        );

        return [
            'code' => $code['code'],
            'discount_percent' => $code['discount_percent'],
            'referrer_credit' => $code['referrer_credit'],
            'total_referrals' => (int)($stats['total_referrals'] ?? 0),
            'successful_orders' => (int)($stats['successful_orders'] ?? 0),
            'total_credit' => (float)($stats['total_credit'] ?? 0),
            'pending_credit' => (float)($stats['pending_credit'] ?? 0)
        ];
    }

    /**
     * Apply referral credit after successful order
     */
    public function applyCredit(int $useId, float $orderTotal): void
    {
        $use = $this->db->selectOne(
            "SELECT ru.*, rc.referrer_credit
             FROM referral_uses ru
             JOIN referral_codes rc ON ru.referral_code_id = rc.id
             WHERE ru.id = ?",
            [$useId]
        );

        if ($use) {
            $this->db->update(
                "UPDATE referral_uses SET credit_amount = ? WHERE id = ?",
                [$use['referrer_credit'], $useId]
            );
        }
    }

    /**
     * Check if email has already been referred
     */
    public function hasBeenReferred(string $email): bool
    {
        $result = $this->db->selectOne(
            "SELECT id FROM referral_uses WHERE referred_email = ?",
            [strtolower(trim($email))]
        );
        return $result !== false && $result !== null;
    }

    /**
     * Get recent referrals for admin
     */
    public function getRecentReferrals(int $limit = 50): array
    {
        return $this->db->select(
            "SELECT ru.*, rc.code, u.email as referrer_email, u.first_name as referrer_name
             FROM referral_uses ru
             JOIN referral_codes rc ON ru.referral_code_id = rc.id
             JOIN users u ON rc.user_id = u.id
             ORDER BY ru.created_at DESC
             LIMIT ?",
            [$limit]
        );
    }
}
