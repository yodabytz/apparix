<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Order extends Model
{
    protected string $table = 'orders';

    /**
     * Shipping carriers with their tracking URL patterns
     * {tracking} will be replaced with the actual tracking number
     */
    public static array $carriers = [
        'usps' => [
            'name' => 'USPS',
            'tracking_url' => 'https://tools.usps.com/go/TrackConfirmAction?tLabels={tracking}'
        ],
        'ups' => [
            'name' => 'UPS',
            'tracking_url' => 'https://www.ups.com/track?tracknum={tracking}'
        ],
        'fedex' => [
            'name' => 'FedEx',
            'tracking_url' => 'https://www.fedex.com/fedextrack/?trknbr={tracking}'
        ],
        'dhl' => [
            'name' => 'DHL',
            'tracking_url' => 'https://www.dhl.com/us-en/home/tracking/tracking-express.html?submit=1&tracking-id={tracking}'
        ],
        'dhl_express' => [
            'name' => 'DHL Express',
            'tracking_url' => 'https://www.dhl.com/us-en/home/tracking.html?tracking-id={tracking}'
        ],
        'amazon' => [
            'name' => 'Amazon Logistics',
            'tracking_url' => 'https://track.amazon.com/tracking/{tracking}'
        ],
        'ontrac' => [
            'name' => 'OnTrac',
            'tracking_url' => 'https://www.ontrac.com/tracking/?number={tracking}'
        ],
        'lasership' => [
            'name' => 'LaserShip',
            'tracking_url' => 'https://www.lasership.com/track/{tracking}'
        ],
        'an_post' => [
            'name' => 'An Post (Ireland)',
            'tracking_url' => 'https://track.anpost.ie/TrackingResults.aspx?ression=0&track={tracking}'
        ],
        'royal_mail' => [
            'name' => 'Royal Mail (UK)',
            'tracking_url' => 'https://www.royalmail.com/track-your-item#/tracking-results/{tracking}'
        ],
        'canada_post' => [
            'name' => 'Canada Post',
            'tracking_url' => 'https://www.canadapost-postescanada.ca/track-reperage/en#/search?searchFor={tracking}'
        ],
        'australia_post' => [
            'name' => 'Australia Post',
            'tracking_url' => 'https://auspost.com.au/mypost/track/#/details/{tracking}'
        ],
        'other' => [
            'name' => 'Other',
            'tracking_url' => null
        ]
    ];

    /**
     * Get all carriers for dropdown
     */
    public static function getCarriers(): array
    {
        $result = [];
        foreach (self::$carriers as $code => $carrier) {
            $result[$code] = $carrier['name'];
        }
        return $result;
    }

    /**
     * Get tracking URL for a carrier and tracking number
     */
    public static function getTrackingUrl(string $carrier, string $trackingNumber): ?string
    {
        if (!isset(self::$carriers[$carrier]) || !self::$carriers[$carrier]['tracking_url']) {
            return null;
        }
        return str_replace('{tracking}', urlencode($trackingNumber), self::$carriers[$carrier]['tracking_url']);
    }

    /**
     * Get carrier name
     */
    public static function getCarrierName(string $carrier): string
    {
        return self::$carriers[$carrier]['name'] ?? $carrier;
    }

    /**
     * Find order by ID
     */
    public function findById(int $id): ?array
    {
        return $this->queryOne(
            "SELECT o.*,
                    ba.first_name as billing_first_name, ba.last_name as billing_last_name,
                    ba.address_line1 as billing_address1, ba.address_line2 as billing_address2,
                    ba.city as billing_city, ba.state as billing_state,
                    ba.postal_code as billing_postal, ba.country as billing_country,
                    sa.first_name as shipping_first_name, sa.last_name as shipping_last_name,
                    sa.address_line1 as shipping_address1, sa.address_line2 as shipping_address2,
                    sa.city as shipping_city, sa.state as shipping_state,
                    sa.postal_code as shipping_postal, sa.country as shipping_country,
                    u.email as user_email, u.first_name as user_first_name, u.last_name as user_last_name
             FROM {$this->table} o
             LEFT JOIN addresses ba ON o.billing_address_id = ba.id
             LEFT JOIN addresses sa ON o.shipping_address_id = sa.id
             LEFT JOIN users u ON o.user_id = u.id
             WHERE o.id = ?",
            [$id]
        );
    }

    /**
     * Find order by order number
     */
    public function findByOrderNumber(string $orderNumber): ?array
    {
        return $this->queryOne(
            "SELECT o.*,
                    ba.first_name as billing_first_name, ba.last_name as billing_last_name,
                    sa.first_name as shipping_first_name, sa.last_name as shipping_last_name,
                    u.email as user_email
             FROM {$this->table} o
             LEFT JOIN addresses ba ON o.billing_address_id = ba.id
             LEFT JOIN addresses sa ON o.shipping_address_id = sa.id
             LEFT JOIN users u ON o.user_id = u.id
             WHERE o.order_number = ?",
            [$orderNumber]
        );
    }

    /**
     * Get all orders with pagination
     */
    public function getAllOrders(int $limit = 50, int $offset = 0, ?string $status = null, ?string $search = null): array
    {
        $params = [];
        $where = [];

        if ($status) {
            $where[] = "o.status = ?";
            $params[] = $status;
        }

        if ($search) {
            $where[] = "(o.order_number LIKE ? OR o.customer_email LIKE ? OR o.tracking_number LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $params[] = $limit;
        $params[] = $offset;

        return $this->query(
            "SELECT o.*,
                    u.first_name as user_first_name, u.last_name as user_last_name
             FROM {$this->table} o
             LEFT JOIN users u ON o.user_id = u.id
             $whereClause
             ORDER BY o.created_at DESC
             LIMIT ? OFFSET ?",
            $params
        );
    }

    /**
     * Count orders
     */
    public function countOrders(?string $status = null, ?string $search = null): int
    {
        $params = [];
        $where = [];

        if ($status) {
            $where[] = "status = ?";
            $params[] = $status;
        }

        if ($search) {
            $where[] = "(order_number LIKE ? OR customer_email LIKE ? OR tracking_number LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $result = $this->queryOne("SELECT COUNT(*) as total FROM {$this->table} $whereClause", $params);
        return $result['total'] ?? 0;
    }

    /**
     * Get order items
     */
    public function getOrderItems(int $orderId): array
    {
        return $this->query(
            "SELECT oi.*, p.slug as product_slug, p.manufacturer,
                    COALESCE(oi.cost, pv.cost, p.cost) as product_cost,
                    p.cost as base_product_cost,
                    pv.cost as variant_cost,
                    oi.cost as item_cost,
                    (SELECT pi.image_path FROM product_images pi
                     WHERE pi.product_id = oi.product_id
                     ORDER BY pi.is_primary DESC, pi.sort_order ASC LIMIT 1) as product_image
             FROM order_items oi
             LEFT JOIN products p ON oi.product_id = p.id
             LEFT JOIN product_variants pv ON pv.sku = oi.product_sku
             WHERE oi.order_id = ?
             ORDER BY oi.id",
            [$orderId]
        );
    }

    /**
     * Update order item cost
     */
    public function updateItemCost(int $itemId, float $cost): bool
    {
        $result = $this->db->update(
            "UPDATE order_items SET cost = ? WHERE id = ?",
            [$cost, $itemId]
        );
        return $result >= 0;
    }

    /**
     * Get order item by ID
     */
    public function getOrderItem(int $itemId): ?array
    {
        $result = $this->db->selectOne(
            "SELECT oi.*, o.id as order_id FROM order_items oi
             JOIN orders o ON oi.order_id = o.id
             WHERE oi.id = ?",
            [$itemId]
        );
        return $result ?: null;
    }

    /**
     * Update order status
     */
    public function updateStatus(int $orderId, string $status): bool
    {
        $result = $this->db->update(
            "UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id = ?",
            [$status, $orderId]
        );
        return $result >= 0;
    }

    /**
     * Add tracking information
     */
    public function addTracking(int $orderId, string $carrier, string $trackingNumber, ?string $estimatedDelivery = null): bool
    {
        $db = Database::getInstance();

        $result = $db->update(
            "UPDATE {$this->table}
             SET shipping_carrier = ?, tracking_number = ?, shipped_at = NOW(),
                 estimated_delivery = ?, status = 'shipped'
             WHERE id = ?",
            [$carrier, $trackingNumber, $estimatedDelivery, $orderId]
        );

        return $result !== false;
    }

    /**
     * Mark as delivered
     */
    public function markDelivered(int $orderId): bool
    {
        return $this->query(
            "UPDATE {$this->table} SET status = 'delivered' WHERE id = ?",
            [$orderId]
        );
    }

    /**
     * Get orders by status counts
     */
    public function getStatusCounts(): array
    {
        $results = $this->query(
            "SELECT status, COUNT(*) as count FROM {$this->table} GROUP BY status"
        );

        $counts = [
            'pending' => 0,
            'processing' => 0,
            'shipped' => 0,
            'delivered' => 0,
            'cancelled' => 0,
            'refunded' => 0,
            'total' => 0
        ];

        foreach ($results as $row) {
            $counts[$row['status']] = (int)$row['count'];
            $counts['total'] += (int)$row['count'];
        }

        return $counts;
    }

    /**
     * Get recent orders
     */
    public function getRecentOrders(int $limit = 10): array
    {
        return $this->query(
            "SELECT o.*, u.first_name, u.last_name
             FROM {$this->table} o
             LEFT JOIN users u ON o.user_id = u.id
             ORDER BY o.created_at DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Update order notes
     */
    public function updateNotes(int $orderId, string $notes): bool
    {
        $db = Database::getInstance();
        return $db->update(
            "UPDATE {$this->table} SET notes = ? WHERE id = ?",
            [$notes, $orderId]
        );
    }

    /**
     * Delete an order and its items
     */
    public function deleteOrder(int $orderId): bool
    {
        $db = Database::getInstance();

        // Delete order items first
        $db->update("DELETE FROM order_items WHERE order_id = ?", [$orderId]);

        // Delete the order
        $result = $db->update("DELETE FROM {$this->table} WHERE id = ?", [$orderId]);
        return $result >= 0;
    }
}
