<?php

namespace App\Models;

use App\Core\Database;

class ShippingOrigin
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all active shipping origins
     */
    public function getAll(): array
    {
        return $this->db->select(
            "SELECT * FROM shipping_origins WHERE is_active = 1 ORDER BY is_default DESC, name ASC"
        );
    }

    /**
     * Get all origins including inactive
     */
    public function getAllAdmin(): array
    {
        return $this->db->select(
            "SELECT * FROM shipping_origins ORDER BY is_default DESC, name ASC"
        );
    }

    /**
     * Get origin by ID
     */
    public function findById(int $id): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM shipping_origins WHERE id = ?",
            [$id]
        );
    }

    /**
     * Get default origin
     */
    public function getDefault(): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM shipping_origins WHERE is_default = 1 AND is_active = 1 LIMIT 1"
        );
    }

    /**
     * Create a new origin
     */
    public function create(array $data): int
    {
        // If this is the default, clear other defaults
        if (!empty($data['is_default'])) {
            $this->db->update("UPDATE shipping_origins SET is_default = 0");
        }

        $this->db->insert(
            "INSERT INTO shipping_origins
             (name, address_line1, address_line2, city, state, postal_code, country, phone, is_default, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['name'],
                $data['address_line1'],
                $data['address_line2'] ?? null,
                $data['city'],
                $data['state'],
                $data['postal_code'],
                $data['country'] ?? 'US',
                $data['phone'] ?? null,
                $data['is_default'] ?? 0,
                $data['is_active'] ?? 1
            ]
        );

        return (int) $this->db->selectOne("SELECT LAST_INSERT_ID() as id")['id'];
    }

    /**
     * Update an origin
     */
    public function update(int $id, array $data): bool
    {
        // If this is the default, clear other defaults
        if (!empty($data['is_default'])) {
            $this->db->update("UPDATE shipping_origins SET is_default = 0 WHERE id != ?", [$id]);
        }

        return $this->db->update(
            "UPDATE shipping_origins SET
             name = ?, address_line1 = ?, address_line2 = ?, city = ?,
             state = ?, postal_code = ?, country = ?, phone = ?,
             is_default = ?, is_active = ?
             WHERE id = ?",
            [
                $data['name'],
                $data['address_line1'],
                $data['address_line2'] ?? null,
                $data['city'],
                $data['state'],
                $data['postal_code'],
                $data['country'] ?? 'US',
                $data['phone'] ?? null,
                $data['is_default'] ?? 0,
                $data['is_active'] ?? 1,
                $id
            ]
        );
    }

    /**
     * Delete an origin
     */
    public function delete(int $id): bool
    {
        // Don't delete if products are using this origin
        $products = $this->db->selectOne(
            "SELECT COUNT(*) as count FROM products WHERE origin_id = ?",
            [$id]
        );

        if ($products['count'] > 0) {
            return false;
        }

        return $this->db->update("DELETE FROM shipping_origins WHERE id = ?", [$id]);
    }

    /**
     * Get formatted address for an origin
     */
    public function getFormattedAddress(int $id): string
    {
        $origin = $this->findById($id);

        if (!$origin) {
            return '';
        }

        $lines = [
            $origin['name'],
            $origin['address_line1'],
        ];

        if ($origin['address_line2']) {
            $lines[] = $origin['address_line2'];
        }

        $lines[] = sprintf(
            '%s, %s %s',
            $origin['city'],
            $origin['state'],
            $origin['postal_code']
        );

        if ($origin['country'] !== 'US') {
            $lines[] = $origin['country'];
        }

        return implode("\n", $lines);
    }
}
