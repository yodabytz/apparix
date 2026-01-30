<?php

namespace App\Models;

use App\Core\Database;

class ShippingMethod
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all active shipping methods for a zone
     */
    public function getByZone(int $zoneId): array
    {
        return $this->db->select(
            "SELECT * FROM shipping_methods
             WHERE zone_id = ? AND is_active = 1
             ORDER BY sort_order ASC",
            [$zoneId]
        );
    }

    /**
     * Get method by ID
     */
    public function findById(int $id): ?array
    {
        return $this->db->selectOne(
            "SELECT sm.*, sz.name as zone_name
             FROM shipping_methods sm
             JOIN shipping_zones sz ON sm.zone_id = sz.id
             WHERE sm.id = ?",
            [$id]
        );
    }

    /**
     * Get all methods with zone info
     */
    public function getAll(): array
    {
        return $this->db->select(
            "SELECT sm.*, sz.name as zone_name
             FROM shipping_methods sm
             JOIN shipping_zones sz ON sm.zone_id = sz.id
             ORDER BY sz.sort_order ASC, sm.sort_order ASC"
        );
    }

    /**
     * Calculate shipping rate for a method
     */
    public function calculateRate(int $methodId, float $subtotal, float $weight = 0, int $quantity = 0): ?array
    {
        $method = $this->findById($methodId);

        if (!$method) {
            return null;
        }

        $rate = 0;
        $isFree = false;

        switch ($method['rate_type']) {
            case 'free':
                $rate = 0;
                $isFree = true;
                break;

            case 'flat':
                // Check if qualifies for free shipping
                if ($method['min_order_free'] && $subtotal >= $method['min_order_free']) {
                    $rate = 0;
                    $isFree = true;
                } else {
                    $rate = (float) $method['flat_rate'];
                }
                break;

            case 'table':
                $rate = $this->getTableRate($methodId, $subtotal, $weight, $quantity);
                // Check free shipping threshold even for table rates
                if ($method['min_order_free'] && $subtotal >= $method['min_order_free']) {
                    $rate = 0;
                    $isFree = true;
                }
                break;

            case 'live':
                // Live rates would be fetched from carrier API
                // For now, fall back to flat rate
                $rate = (float) $method['flat_rate'];
                break;
        }

        // Add handling fee if not free
        if (!$isFree) {
            $rate += (float) $method['handling_fee'];
        }

        return [
            'method_id' => $method['id'],
            'name' => $method['name'],
            'carrier' => $method['carrier'],
            'rate' => round($rate, 2),
            'is_free' => $isFree,
            'delivery_estimate' => $method['delivery_estimate'],
            'description' => $method['description'],
            'min_order_free' => $method['min_order_free']
        ];
    }

    /**
     * Get table-based rate
     */
    private function getTableRate(int $methodId, float $subtotal, float $weight, int $quantity): float
    {
        // First try weight-based
        $rate = $this->db->selectOne(
            "SELECT rate FROM shipping_rates
             WHERE method_id = ? AND condition_type = 'weight'
             AND min_value <= ? AND (max_value IS NULL OR max_value >= ?)
             ORDER BY min_value DESC LIMIT 1",
            [$methodId, $weight, $weight]
        );

        if ($rate) {
            return (float) $rate['rate'];
        }

        // Try subtotal-based
        $rate = $this->db->selectOne(
            "SELECT rate FROM shipping_rates
             WHERE method_id = ? AND condition_type = 'subtotal'
             AND min_value <= ? AND (max_value IS NULL OR max_value >= ?)
             ORDER BY min_value DESC LIMIT 1",
            [$methodId, $subtotal, $subtotal]
        );

        if ($rate) {
            return (float) $rate['rate'];
        }

        // Try quantity-based
        $rate = $this->db->selectOne(
            "SELECT rate FROM shipping_rates
             WHERE method_id = ? AND condition_type = 'quantity'
             AND min_value <= ? AND (max_value IS NULL OR max_value >= ?)
             ORDER BY min_value DESC LIMIT 1",
            [$methodId, $quantity, $quantity]
        );

        return $rate ? (float) $rate['rate'] : 0;
    }

    /**
     * Create a new method
     */
    public function create(array $data): int
    {
        $this->db->insert(
            "INSERT INTO shipping_methods
             (zone_id, carrier, method_code, name, description, delivery_estimate,
              rate_type, flat_rate, min_order_free, handling_fee, is_active, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['zone_id'],
                $data['carrier'],
                $data['method_code'],
                $data['name'],
                $data['description'] ?? null,
                $data['delivery_estimate'] ?? null,
                $data['rate_type'] ?? 'flat',
                $data['flat_rate'] ?? null,
                $data['min_order_free'] ?? null,
                $data['handling_fee'] ?? 0,
                $data['is_active'] ?? 1,
                $data['sort_order'] ?? 0
            ]
        );

        return (int) $this->db->selectOne("SELECT LAST_INSERT_ID() as id")['id'];
    }

    /**
     * Update a method
     */
    public function update(int $id, array $data): bool
    {
        return $this->db->update(
            "UPDATE shipping_methods SET
             zone_id = ?, carrier = ?, method_code = ?, name = ?, description = ?,
             delivery_estimate = ?, rate_type = ?, flat_rate = ?, min_order_free = ?,
             handling_fee = ?, is_active = ?, sort_order = ?
             WHERE id = ?",
            [
                $data['zone_id'],
                $data['carrier'],
                $data['method_code'],
                $data['name'],
                $data['description'] ?? null,
                $data['delivery_estimate'] ?? null,
                $data['rate_type'] ?? 'flat',
                $data['flat_rate'] ?? null,
                $data['min_order_free'] ?? null,
                $data['handling_fee'] ?? 0,
                $data['is_active'] ?? 1,
                $data['sort_order'] ?? 0,
                $id
            ]
        );
    }

    /**
     * Delete a method
     */
    public function delete(int $id): bool
    {
        return $this->db->update("DELETE FROM shipping_methods WHERE id = ?", [$id]);
    }
}
