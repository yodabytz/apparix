<?php

namespace App\Models;

use App\Core\Database;

class ShippingZone
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all active shipping zones
     */
    public function getAll(): array
    {
        return $this->db->select(
            "SELECT * FROM shipping_zones WHERE is_active = 1 ORDER BY sort_order ASC"
        );
    }

    /**
     * Get zone by ID
     */
    public function findById(int $id): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM shipping_zones WHERE id = ?",
            [$id]
        );
    }

    /**
     * Find zone by country code
     */
    public function findByCountry(string $countryCode): ?array
    {
        $zones = $this->getAll();

        foreach ($zones as $zone) {
            $countries = json_decode($zone['countries'], true) ?: [];

            // Check for wildcard (Rest of World)
            if (in_array('*', $countries)) {
                continue; // Skip wildcard zones, check specific first
            }

            if (in_array($countryCode, $countries)) {
                return $zone;
            }
        }

        // If no specific zone found, look for wildcard zone
        foreach ($zones as $zone) {
            $countries = json_decode($zone['countries'], true) ?: [];
            if (in_array('*', $countries)) {
                return $zone;
            }
        }

        return null;
    }

    /**
     * Find zone by country and state
     */
    public function findByLocation(string $countryCode, ?string $stateCode = null): ?array
    {
        $zones = $this->getAll();

        foreach ($zones as $zone) {
            $countries = json_decode($zone['countries'], true) ?: [];
            $states = $zone['states'] ? json_decode($zone['states'], true) : null;

            // Skip wildcard zones in first pass
            if (in_array('*', $countries)) {
                continue;
            }

            if (in_array($countryCode, $countries)) {
                // If zone has specific states, check them
                if ($states && $stateCode) {
                    if (in_array($stateCode, $states)) {
                        return $zone;
                    }
                } else if (!$states) {
                    // Zone doesn't restrict by state
                    return $zone;
                }
            }
        }

        // Fallback to wildcard zone
        foreach ($zones as $zone) {
            $countries = json_decode($zone['countries'], true) ?: [];
            if (in_array('*', $countries)) {
                return $zone;
            }
        }

        return null;
    }

    /**
     * Create a new zone
     */
    public function create(array $data): int
    {
        $this->db->insert(
            "INSERT INTO shipping_zones (name, countries, states, is_active, sort_order)
             VALUES (?, ?, ?, ?, ?)",
            [
                $data['name'],
                json_encode($data['countries']),
                $data['states'] ? json_encode($data['states']) : null,
                $data['is_active'] ?? 1,
                $data['sort_order'] ?? 0
            ]
        );

        return (int) $this->db->selectOne("SELECT LAST_INSERT_ID() as id")['id'];
    }

    /**
     * Update a zone
     */
    public function update(int $id, array $data): bool
    {
        return $this->db->update(
            "UPDATE shipping_zones SET name = ?, countries = ?, states = ?, is_active = ?, sort_order = ? WHERE id = ?",
            [
                $data['name'],
                json_encode($data['countries']),
                $data['states'] ? json_encode($data['states']) : null,
                $data['is_active'] ?? 1,
                $data['sort_order'] ?? 0,
                $id
            ]
        );
    }

    /**
     * Delete a zone
     */
    public function delete(int $id): bool
    {
        return $this->db->update("DELETE FROM shipping_zones WHERE id = ?", [$id]);
    }
}
