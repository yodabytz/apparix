<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Review extends Model
{
    protected string $table = 'product_reviews';

    /**
     * Get approved reviews for a product
     */
    public function getProductReviews(int $productId, int $limit = 10, int $offset = 0): array
    {
        return $this->query(
            "SELECT r.*, u.first_name, u.last_name,
                    CONCAT(LEFT(u.first_name, 1), '. ', LEFT(u.last_name, 1), '.') as display_name
             FROM {$this->table} r
             JOIN users u ON r.user_id = u.id
             WHERE r.product_id = ? AND r.is_approved = 1
             ORDER BY r.is_featured DESC, r.created_at DESC
             LIMIT ? OFFSET ?",
            [$productId, $limit, $offset]
        );
    }

    /**
     * Get review statistics for a product
     */
    public function getProductStats(int $productId): array
    {
        $result = $this->queryOne(
            "SELECT
                COUNT(*) as total_reviews,
                COALESCE(AVG(rating), 0) as average_rating,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
             FROM {$this->table}
             WHERE product_id = ? AND is_approved = 1",
            [$productId]
        );

        return [
            'total' => (int) ($result['total_reviews'] ?? 0),
            'average' => round((float) ($result['average_rating'] ?? 0), 1),
            'distribution' => [
                5 => (int) ($result['five_star'] ?? 0),
                4 => (int) ($result['four_star'] ?? 0),
                3 => (int) ($result['three_star'] ?? 0),
                2 => (int) ($result['two_star'] ?? 0),
                1 => (int) ($result['one_star'] ?? 0),
            ]
        ];
    }

    /**
     * Check if user can review a product (must have purchased it)
     */
    public function canUserReview(int $userId, int $productId): array
    {
        $db = Database::getInstance();

        // Check if user has purchased this product
        $purchase = $db->selectOne(
            "SELECT o.id as order_id, o.order_number, o.status, oi.id as order_item_id
             FROM orders o
             JOIN order_items oi ON oi.order_id = o.id
             WHERE o.user_id = ?
               AND oi.product_id = ?
               AND o.status IN ('delivered', 'shipped', 'processing', 'pending')
               AND o.payment_status = 'paid'
             ORDER BY o.created_at DESC
             LIMIT 1",
            [$userId, $productId]
        );

        if (!$purchase) {
            return ['can_review' => false, 'reason' => 'purchase_required'];
        }

        // Check if user already reviewed this product for this order
        $existingReview = $db->selectOne(
            "SELECT id FROM {$this->table}
             WHERE user_id = ? AND product_id = ? AND order_id = ?",
            [$userId, $productId, $purchase['order_id']]
        );

        if ($existingReview) {
            return ['can_review' => false, 'reason' => 'already_reviewed'];
        }

        return [
            'can_review' => true,
            'order_id' => $purchase['order_id'],
            'order_number' => $purchase['order_number']
        ];
    }

    /**
     * Check if user has already reviewed a product (any order)
     */
    public function hasUserReviewedProduct(int $userId, int $productId): bool
    {
        $db = Database::getInstance();
        $result = $db->selectOne(
            "SELECT id FROM {$this->table} WHERE user_id = ? AND product_id = ?",
            [$userId, $productId]
        );
        return $result !== false;
    }

    /**
     * Submit a new review
     */
    public function submitReview(int $productId, int $userId, int $orderId, int $rating, ?string $title, ?string $reviewText): int
    {
        $db = Database::getInstance();

        return $db->insert(
            "INSERT INTO {$this->table} (product_id, user_id, order_id, rating, title, review_text, is_verified_purchase, is_approved)
             VALUES (?, ?, ?, ?, ?, ?, 1, 0)",
            [$productId, $userId, $orderId, $rating, $title, $reviewText]
        );
    }

    /**
     * Get all reviews for admin (with pagination)
     */
    public function getAllReviews(int $limit = 20, int $offset = 0, ?string $status = null): array
    {
        $params = [];
        $where = '';

        if ($status === 'pending') {
            $where = 'WHERE r.is_approved = 0';
        } elseif ($status === 'approved') {
            $where = 'WHERE r.is_approved = 1';
        }

        $params[] = $limit;
        $params[] = $offset;

        return $this->query(
            "SELECT r.*,
                    u.first_name, u.last_name, u.email,
                    p.name as product_name, p.slug as product_slug
             FROM {$this->table} r
             JOIN users u ON r.user_id = u.id
             JOIN products p ON r.product_id = p.id
             $where
             ORDER BY r.created_at DESC
             LIMIT ? OFFSET ?",
            $params
        );
    }

    /**
     * Count reviews by status
     */
    public function countByStatus(): array
    {
        $results = $this->query(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN is_approved = 0 THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved
             FROM {$this->table}"
        );

        return $results[0] ?? ['total' => 0, 'pending' => 0, 'approved' => 0];
    }

    /**
     * Approve a review
     */
    public function approve(int $reviewId): bool
    {
        $db = Database::getInstance();
        return $db->update(
            "UPDATE {$this->table} SET is_approved = 1 WHERE id = ?",
            [$reviewId]
        );
    }

    /**
     * Reject/delete a review
     */
    public function reject(int $reviewId): bool
    {
        $db = Database::getInstance();
        return $db->delete(
            "DELETE FROM {$this->table} WHERE id = ?",
            [$reviewId]
        );
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured(int $reviewId): bool
    {
        $db = Database::getInstance();
        return $db->update(
            "UPDATE {$this->table} SET is_featured = NOT is_featured WHERE id = ?",
            [$reviewId]
        );
    }

    /**
     * Get user's reviews
     */
    public function getUserReviews(int $userId): array
    {
        return $this->query(
            "SELECT r.*, p.name as product_name, p.slug as product_slug
             FROM {$this->table} r
             JOIN products p ON r.product_id = p.id
             WHERE r.user_id = ?
             ORDER BY r.created_at DESC",
            [$userId]
        );
    }

    /**
     * Create review request entries for an order
     */
    public function createReviewRequests(int $orderId): void
    {
        $db = Database::getInstance();

        // Get order info
        $order = $db->selectOne(
            "SELECT o.id, o.user_id, o.customer_email, u.email as user_email
             FROM orders o
             LEFT JOIN users u ON o.user_id = u.id
             WHERE o.id = ?",
            [$orderId]
        );

        if (!$order || !$order['user_id']) {
            return; // Only registered users can review
        }

        $email = $order['user_email'] ?? $order['customer_email'] ?? null;

        // Skip if no valid email address
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log("Review request skipped for order {$orderId}: no valid email");
            return;
        }

        // Get order items
        $items = $db->select(
            "SELECT id, product_id FROM order_items WHERE order_id = ?",
            [$orderId]
        );

        foreach ($items as $item) {
            // Check if request already exists
            $existing = $db->selectOne(
                "SELECT id FROM review_requests WHERE order_id = ? AND product_id = ?",
                [$orderId, $item['product_id']]
            );

            if (!$existing) {
                $token = bin2hex(random_bytes(32));
                $db->insert(
                    "INSERT INTO review_requests (order_id, order_item_id, product_id, user_id, email, token, status)
                     VALUES (?, ?, ?, ?, ?, ?, 'pending')",
                    [$orderId, $item['id'], $item['product_id'], $order['user_id'], $email, $token]
                );
            }
        }
    }

    /**
     * Get pending review requests ready to send
     * (order delivered OR 3 weeks since order placed)
     */
    public function getPendingReviewRequests(int $limit = 50): array
    {
        $db = Database::getInstance();

        return $db->select(
            "SELECT rr.*,
                    o.order_number, o.status as order_status, o.created_at as order_date,
                    o.shipped_at, o.tracking_number,
                    p.name as product_name, p.slug as product_slug,
                    u.first_name
             FROM review_requests rr
             JOIN orders o ON rr.order_id = o.id
             JOIN products p ON rr.product_id = p.id
             JOIN users u ON rr.user_id = u.id
             WHERE rr.status = 'pending'
               AND rr.sent_at IS NULL
               AND (
                   o.status = 'delivered'
                   OR o.created_at <= DATE_SUB(NOW(), INTERVAL 21 DAY)
               )
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Mark review request as sent
     */
    public function markRequestSent(int $requestId): bool
    {
        $db = Database::getInstance();
        return $db->update(
            "UPDATE review_requests SET status = 'sent', sent_at = NOW() WHERE id = ?",
            [$requestId]
        );
    }

    /**
     * Mark review request as reviewed
     */
    public function markRequestReviewed(int $requestId): bool
    {
        $db = Database::getInstance();
        return $db->update(
            "UPDATE review_requests SET status = 'reviewed', reviewed_at = NOW() WHERE id = ?",
            [$requestId]
        );
    }

    /**
     * Get review request by token
     */
    public function getRequestByToken(string $token): ?array
    {
        $db = Database::getInstance();
        return $db->selectOne(
            "SELECT rr.*, p.name as product_name, p.slug as product_slug
             FROM review_requests rr
             JOIN products p ON rr.product_id = p.id
             WHERE rr.token = ? AND rr.status IN ('pending', 'sent')",
            [$token]
        );
    }
}
