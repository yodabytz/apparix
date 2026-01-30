<?php

namespace App\Models;

use App\Core\Model;
use App\Core\License;

/**
 * OrderLicense model - handles license key generation and management for purchases
 */
class OrderLicense extends Model
{
    protected string $table = 'order_licenses';

    /**
     * Generate a license key for an order item
     */
    public function generateForOrder(int $orderId, int $orderItemId, int $productId, string $editionCode, ?string $domain = null): array
    {
        // Generate the license key
        $licenseKey = License::generate($editionCode, $domain);

        // Store in database
        $id = $this->create([
            'order_id' => $orderId,
            'order_item_id' => $orderItemId,
            'product_id' => $productId,
            'license_key' => $licenseKey,
            'edition_code' => $editionCode,
            'domain' => $domain
        ]);

        return [
            'id' => $id,
            'license_key' => $licenseKey,
            'edition_code' => $editionCode,
            'domain' => $domain
        ];
    }

    /**
     * Get all licenses for an order
     */
    public function getByOrderId(int $orderId): array
    {
        return $this->query(
            "SELECT ol.*, p.name as product_name
             FROM {$this->table} ol
             JOIN products p ON ol.product_id = p.id
             WHERE ol.order_id = ?
             ORDER BY ol.created_at ASC",
            [$orderId]
        );
    }

    /**
     * Get license by key
     */
    public function getByKey(string $licenseKey): ?array
    {
        return $this->findBy('license_key', $licenseKey);
    }

    /**
     * Activate a license (set domain)
     */
    public function activate(int $id, string $domain): bool
    {
        return $this->update($id, [
            'domain' => $domain,
            'activated_at' => date('Y-m-d H:i:s'),
            'is_active' => 1
        ]);
    }

    /**
     * Deactivate a license
     */
    public function deactivate(int $id): bool
    {
        return $this->update($id, [
            'is_active' => 0
        ]);
    }

    /**
     * Get licenses by customer email
     */
    public function getByCustomerEmail(string $email): array
    {
        return $this->query(
            "SELECT ol.*, p.name as product_name, o.order_number, o.customer_email
             FROM {$this->table} ol
             JOIN products p ON ol.product_id = p.id
             JOIN orders o ON ol.order_id = o.id
             WHERE o.customer_email = ?
             ORDER BY ol.created_at DESC",
            [$email]
        );
    }

    /**
     * Check if license key is valid and active
     */
    public function isValidKey(string $licenseKey): bool
    {
        $license = $this->getByKey($licenseKey);
        return $license && $license['is_active'];
    }

    /**
     * Get edition name from code
     */
    public static function getEditionName(string $code): string
    {
        $editions = [
            'S' => 'Standard',
            'P' => 'Professional',
            'E' => 'Enterprise',
            'D' => 'Developer',
            'U' => 'Unlimited'
        ];
        return $editions[$code] ?? 'Unknown';
    }
}
