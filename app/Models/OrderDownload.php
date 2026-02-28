<?php

namespace App\Models;

use App\Core\Model;

/**
 * OrderDownload model - handles digital download access for purchases
 */
class OrderDownload extends Model
{
    protected string $table = 'order_downloads';

    /**
     * Create a download token for an order item
     */
    public function createDownloadAccess(int $orderId, int $orderItemId, int $productId, ?int $maxDownloads = null, ?int $expiresInDays = 30): array
    {
        // Generate unique download token
        $token = bin2hex(random_bytes(32));

        // Calculate expiration
        $expiresAt = $expiresInDays ? date('Y-m-d H:i:s', strtotime("+{$expiresInDays} days")) : null;

        $id = $this->create([
            'order_id' => $orderId,
            'order_item_id' => $orderItemId,
            'product_id' => $productId,
            'download_token' => $token,
            'max_downloads' => $maxDownloads,
            'expires_at' => $expiresAt
        ]);

        return [
            'id' => $id,
            'token' => $token,
            'expires_at' => $expiresAt,
            'max_downloads' => $maxDownloads
        ];
    }

    /**
     * Get download by token (includes order user_id for ownership verification)
     */
    public function getByToken(string $token): ?array
    {
        return $this->query(
            "SELECT od.*, p.name as product_name, p.download_file, o.order_number, o.user_id
             FROM {$this->table} od
             JOIN products p ON od.product_id = p.id
             JOIN orders o ON od.order_id = o.id
             WHERE od.download_token = ?",
            [$token]
        )[0] ?? null;
    }

    /**
     * Check if download is valid (not expired, within limit)
     */
    public function isValidDownload(string $token): array
    {
        $download = $this->getByToken($token);

        if (!$download) {
            return ['valid' => false, 'error' => 'Invalid download link'];
        }

        // Check expiration
        if ($download['expires_at'] && strtotime($download['expires_at']) < time()) {
            return ['valid' => false, 'error' => 'Download link has expired'];
        }

        // Check download limit
        if ($download['max_downloads'] !== null && $download['download_count'] >= $download['max_downloads']) {
            return ['valid' => false, 'error' => 'Download limit reached'];
        }

        // Check if file exists
        if (empty($download['download_file'])) {
            return ['valid' => false, 'error' => 'Download file not configured'];
        }

        return ['valid' => true, 'download' => $download];
    }

    /**
     * Record a download
     */
    public function recordDownload(int $id, ?string $ipAddress = null): bool
    {
        return $this->db->update(
            "UPDATE {$this->table}
             SET download_count = download_count + 1,
                 last_download_at = NOW(),
                 ip_address = ?
             WHERE id = ?",
            [$ipAddress, $id]
        ) >= 0;
    }

    /**
     * Get downloads for an order
     */
    public function getByOrderId(int $orderId): array
    {
        return $this->query(
            "SELECT od.*, p.name as product_name, p.download_file
             FROM {$this->table} od
             JOIN products p ON od.product_id = p.id
             WHERE od.order_id = ?
             ORDER BY od.created_at ASC",
            [$orderId]
        );
    }

    /**
     * Get downloads for a user by user_id (via orders table)
     */
    public function getByUserId(int $userId): array
    {
        return $this->query(
            "SELECT od.*, p.name as product_name, p.download_file, p.slug as product_slug,
                    o.order_number, o.customer_email, o.created_at as order_date,
                    oi.price as item_price
             FROM {$this->table} od
             JOIN products p ON od.product_id = p.id
             JOIN orders o ON od.order_id = o.id
             JOIN order_items oi ON od.order_item_id = oi.id
             WHERE o.user_id = ?
             ORDER BY od.created_at DESC",
            [$userId]
        );
    }

    /**
     * Get downloads by customer email
     */
    public function getByCustomerEmail(string $email): array
    {
        return $this->query(
            "SELECT od.*, p.name as product_name, o.order_number
             FROM {$this->table} od
             JOIN products p ON od.product_id = p.id
             JOIN orders o ON od.order_id = o.id
             WHERE o.customer_email = ?
             ORDER BY od.created_at DESC",
            [$email]
        );
    }

    /**
     * Extend download expiration
     */
    public function extendExpiration(int $id, int $days): bool
    {
        return $this->db->update(
            "UPDATE {$this->table}
             SET expires_at = DATE_ADD(COALESCE(expires_at, NOW()), INTERVAL ? DAY)
             WHERE id = ?",
            [$days, $id]
        ) >= 0;
    }

    /**
     * Reset download count
     */
    public function resetDownloadCount(int $id): bool
    {
        return $this->update($id, ['download_count' => 0]);
    }

    /**
     * Log an individual download event
     */
    public function logDownloadEvent(?int $orderDownloadId, int $productId, string $ip, ?string $userAgent = null, ?string $referer = null): int
    {
        try {
            return (int)$this->db->insert(
                "INSERT INTO download_logs (order_download_id, product_id, ip_address, user_agent, referer, downloaded_at)
                 VALUES (?, ?, ?, ?, ?, NOW())",
                [
                    $orderDownloadId,
                    $productId,
                    $ip,
                    $userAgent ? mb_substr($userAgent, 0, 500) : null,
                    $referer ? mb_substr($referer, 0, 500) : null,
                ]
            );
        } catch (\Throwable $e) {
            error_log('logDownloadEvent error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get download logs for admin display (no tokens exposed)
     */
    public function getDownloadLogs(int $limit = 50, int $offset = 0): array
    {
        try {
            return $this->query(
                "SELECT dl.id, dl.ip_address, dl.user_agent, dl.referer, dl.downloaded_at,
                        p.name AS product_name, p.id AS product_id,
                        o.order_number, o.customer_email,
                        od.download_count, od.max_downloads, od.expires_at
                 FROM download_logs dl
                 LEFT JOIN products p ON dl.product_id = p.id
                 LEFT JOIN order_downloads od ON dl.order_download_id = od.id
                 LEFT JOIN orders o ON od.order_id = o.id
                 ORDER BY dl.downloaded_at DESC
                 LIMIT ? OFFSET ?",
                [$limit, $offset]
            );
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Count total download log entries
     */
    public function countDownloadLogs(): int
    {
        try {
            $result = $this->query("SELECT COUNT(*) AS cnt FROM download_logs");
            return (int)($result[0]['cnt'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Get download stats for admin dashboard
     */
    public function getDownloadStats(): array
    {
        try {
            $total = $this->query("SELECT COUNT(*) AS cnt FROM download_logs")[0]['cnt'] ?? 0;
            $uniqueIps = $this->query("SELECT COUNT(DISTINCT ip_address) AS cnt FROM download_logs")[0]['cnt'] ?? 0;
        } catch (\Throwable $e) {
            $total = 0;
            $uniqueIps = 0;
        }

        $activeLinks = $this->query(
            "SELECT COUNT(*) AS cnt FROM order_downloads
             WHERE (expires_at IS NULL OR expires_at > NOW())
               AND (max_downloads IS NULL OR download_count < max_downloads)"
        )[0]['cnt'] ?? 0;

        try {
            $topProducts = $this->query(
                "SELECT p.name, COUNT(dl.id) AS downloads
                 FROM download_logs dl
                 JOIN products p ON dl.product_id = p.id
                 GROUP BY dl.product_id, p.name
                 ORDER BY downloads DESC
                 LIMIT 5"
            );
        } catch (\Throwable $e) {
            $topProducts = [];
        }

        return [
            'total_downloads' => (int)$total,
            'unique_ips' => (int)$uniqueIps,
            'active_links' => (int)$activeLinks,
            'top_products' => $topProducts,
        ];
    }
}
