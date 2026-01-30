<?php

namespace App\Models;

use App\Core\Database;

class Bundle
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new bundle
     */
    public function create(array $data): int
    {
        $slug = $this->generateSlug($data['name']);

        return $this->db->insert(
            "INSERT INTO product_bundles (name, slug, description, discount_type, discount_value, is_active)
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                $data['name'],
                $slug,
                $data['description'] ?? null,
                $data['discount_type'] ?? 'percentage',
                $data['discount_value'] ?? 10,
                $data['is_active'] ?? 1
            ]
        );
    }

    /**
     * Generate unique slug
     */
    private function generateSlug(string $name): string
    {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
        $slug = trim($slug, '-');

        $original = $slug;
        $count = 1;

        while ($this->findBySlug($slug)) {
            $slug = $original . '-' . $count++;
        }

        return $slug;
    }

    /**
     * Find by slug
     */
    public function findBySlug(string $slug): ?array
    {
        $result = $this->db->selectOne(
            "SELECT * FROM product_bundles WHERE slug = ?",
            [$slug]
        );
        return $result ?: null;
    }

    /**
     * Find by ID
     */
    public function findById(int $id): ?array
    {
        $result = $this->db->selectOne(
            "SELECT * FROM product_bundles WHERE id = ?",
            [$id]
        );
        return $result ?: null;
    }

    /**
     * Add product to bundle
     */
    public function addProduct(int $bundleId, int $productId, int $quantity = 1): void
    {
        $this->db->insert(
            "INSERT INTO bundle_products (bundle_id, product_id, quantity)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE quantity = ?",
            [$bundleId, $productId, $quantity, $quantity]
        );
    }

    /**
     * Remove product from bundle
     */
    public function removeProduct(int $bundleId, int $productId): void
    {
        $this->db->delete(
            "DELETE FROM bundle_products WHERE bundle_id = ? AND product_id = ?",
            [$bundleId, $productId]
        );
    }

    /**
     * Get bundle products with details
     */
    public function getProducts(int $bundleId): array
    {
        return $this->db->select(
            "SELECT bp.*, p.name, p.slug, p.price, p.sale_price,
                    (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, sort_order LIMIT 1) as image
             FROM bundle_products bp
             JOIN products p ON bp.product_id = p.id
             WHERE bp.bundle_id = ? AND p.is_active = 1",
            [$bundleId]
        );
    }

    /**
     * Get bundle with full details
     */
    public function getWithProducts(int $bundleId): ?array
    {
        $bundle = $this->findById($bundleId);
        if (!$bundle) return null;

        $bundle['products'] = $this->getProducts($bundleId);
        $bundle['original_price'] = 0;
        $bundle['bundle_price'] = 0;

        foreach ($bundle['products'] as $product) {
            $bundle['original_price'] += $product['price'] * $product['quantity'];
        }

        if ($bundle['discount_type'] === 'percentage') {
            $bundle['bundle_price'] = $bundle['original_price'] * (1 - $bundle['discount_value'] / 100);
        } else {
            $bundle['bundle_price'] = $bundle['original_price'] - $bundle['discount_value'];
        }

        $bundle['savings'] = $bundle['original_price'] - $bundle['bundle_price'];

        return $bundle;
    }

    /**
     * Get all active bundles
     */
    public function getActive(): array
    {
        $bundles = $this->db->select(
            "SELECT * FROM product_bundles WHERE is_active = 1 ORDER BY created_at DESC"
        );

        foreach ($bundles as &$bundle) {
            $full = $this->getWithProducts($bundle['id']);
            $bundle = array_merge($bundle, [
                'products' => $full['products'] ?? [],
                'original_price' => $full['original_price'] ?? 0,
                'bundle_price' => $full['bundle_price'] ?? 0,
                'savings' => $full['savings'] ?? 0
            ]);
        }

        return $bundles;
    }

    /**
     * Get all bundles for admin
     */
    public function getAll(): array
    {
        return $this->db->select(
            "SELECT pb.*,
                    (SELECT COUNT(*) FROM bundle_products WHERE bundle_id = pb.id) as product_count
             FROM product_bundles pb
             ORDER BY pb.created_at DESC"
        );
    }

    /**
     * Update bundle
     */
    public function update(int $id, array $data): void
    {
        $fields = [];
        $values = [];

        foreach (['name', 'description', 'discount_type', 'discount_value', 'is_active'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }

        if (!empty($fields)) {
            $values[] = $id;
            $this->db->update(
                "UPDATE product_bundles SET " . implode(', ', $fields) . " WHERE id = ?",
                $values
            );
        }
    }

    /**
     * Delete bundle
     */
    public function delete(int $id): void
    {
        $this->db->delete("DELETE FROM product_bundles WHERE id = ?", [$id]);
    }
}
