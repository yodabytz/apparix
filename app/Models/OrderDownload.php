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
     * Get download by token
     */
    public function getByToken(string $token): ?array
    {
        return $this->query(
            "SELECT od.*, p.name as product_name, p.download_file, o.order_number
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
        return $this->query(
            "UPDATE {$this->table}
             SET download_count = download_count + 1,
                 last_download_at = NOW(),
                 ip_address = ?
             WHERE id = ?",
            [$ipAddress, $id]
        ) !== false;
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
        return $this->query(
            "UPDATE {$this->table}
             SET expires_at = DATE_ADD(COALESCE(expires_at, NOW()), INTERVAL ? DAY)
             WHERE id = ?",
            [$days, $id]
        ) !== false;
    }

    /**
     * Reset download count
     */
    public function resetDownloadCount(int $id): bool
    {
        return $this->update($id, ['download_count' => 0]);
    }
}
