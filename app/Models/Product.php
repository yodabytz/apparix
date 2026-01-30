<?php

namespace App\Models;

use App\Core\Model;

class Product extends Model
{
    protected string $table = 'products';

    /**
     * Get featured products with primary image and video info
     */
    public function getFeatured(int $limit = 6): array
    {
        return $this->query(
            "SELECT p.*, pi.image_path as primary_image, pi.is_video,
                    (SELECT image_path FROM product_images WHERE product_id = p.id AND is_video = 1 ORDER BY sort_order LIMIT 1) as video_path,
                    (SELECT COUNT(*) FROM product_variants WHERE product_id = p.id) as variant_count,
                    (SELECT COALESCE(SUM(inventory_count), 0) FROM product_variants WHERE product_id = p.id AND is_active = 1) as variant_stock
             FROM {$this->table} p
             LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
             WHERE p.is_active = 1 AND p.disabled = 0 AND p.featured = 1
             ORDER BY p.featured_order ASC, p.id DESC LIMIT ?",
            [$limit]
        );
    }

    /**
     * Get products by category
     */
    public function getByCategory(string $slug, int $limit = 12): array
    {
        return $this->query(
            "SELECT p.* FROM {$this->table} p
             JOIN product_categories pc ON p.id = pc.product_id
             JOIN categories c ON pc.category_id = c.id
             WHERE p.is_active = 1 AND p.disabled = 0 AND c.slug = ?
             ORDER BY pc.sort_order ASC, p.created_at DESC LIMIT ?",
            [$slug, $limit]
        );
    }

    /**
     * Paginate products with primary image
     */
    public function paginateWithImages(int $page = 1, int $perPage = 12, string $sort = 'newest'): array
    {
        $offset = ($page - 1) * $perPage;

        // Determine sort order - default uses admin-defined sort_order
        $orderBy = match($sort) {
            'price-low' => 'COALESCE(p.sale_price, p.price) ASC',
            'price-high' => 'COALESCE(p.sale_price, p.price) DESC',
            'name-az' => 'p.name ASC',
            'name-za' => 'p.name DESC',
            'newest' => 'p.created_at DESC',
            default => 'p.sort_order ASC, p.created_at DESC', // admin-defined order
        };

        return $this->query(
            "SELECT p.*, pi.image_path as primary_image, pi.is_video,
                    (SELECT image_path FROM product_images WHERE product_id = p.id AND is_video = 1 ORDER BY sort_order LIMIT 1) as video_path,
                    (SELECT COUNT(*) FROM product_variants WHERE product_id = p.id) as variant_count,
                    (SELECT COALESCE(SUM(inventory_count), 0) FROM product_variants WHERE product_id = p.id AND is_active = 1) as variant_stock
             FROM {$this->table} p
             LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
             WHERE p.is_active = 1 AND p.disabled = 0
             ORDER BY {$orderBy}
             LIMIT ? OFFSET ?",
            [$perPage, $offset]
        );
    }

    /**
     * Count active products
     */
    public function countActive(): int
    {
        $result = $this->queryOne("SELECT COUNT(*) as count FROM {$this->table} WHERE is_active = 1 AND disabled = 0");
        return (int)($result['count'] ?? 0);
    }

    /**
     * Search products
     */
    public function search(string $query): array
    {
        $searchTerm = '%' . $query . '%';
        return $this->query(
            "SELECT p.*, pi.image_path as primary_image
             FROM {$this->table} p
             LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
             WHERE p.is_active = 1 AND p.disabled = 0 AND (p.name LIKE ? OR p.description LIKE ?)
             ORDER BY p.id DESC",
            [$searchTerm, $searchTerm]
        );
    }

    /**
     * Get product with images
     */
    public function getWithImages(int $id): array|false
    {
        $product = $this->find($id);
        if ($product) {
            $images = $this->query(
                "SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC",
                [$id]
            );
            $product['images'] = $images;
        }
        return $product;
    }

    /**
     * Get related products
     */
    public function getRelated(int $productId, int $limit = 4): array
    {
        return $this->query(
            "SELECT DISTINCT p.*,
                    (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC LIMIT 1) as primary_image,
                    (SELECT COUNT(*) FROM product_variants WHERE product_id = p.id) as variant_count,
                    (SELECT COALESCE(SUM(inventory_count), 0) FROM product_variants WHERE product_id = p.id AND is_active = 1) as variant_stock
             FROM {$this->table} p
             JOIN product_categories pc ON p.id = pc.product_id
             WHERE pc.category_id IN (
                 SELECT category_id FROM product_categories WHERE product_id = ?
             )
             AND p.id != ?
             AND p.is_active = 1 AND p.disabled = 0
             ORDER BY RAND()
             LIMIT ?",
            [$productId, $productId, $limit]
        );
    }

    /**
     * Get product options with their values
     */
    public function getOptions(int $productId): array
    {
        $options = $this->query(
            "SELECT * FROM product_options WHERE product_id = ? ORDER BY sort_order",
            [$productId]
        );

        foreach ($options as &$option) {
            $option['values'] = $this->query(
                "SELECT * FROM product_option_values WHERE option_id = ? ORDER BY sort_order",
                [$option['id']]
            );
        }

        return $options;
    }

    /**
     * Get all variants for a product
     */
    public function getVariants(int $productId): array
    {
        return $this->query(
            "SELECT pv.*,
                    GROUP_CONCAT(pov.value_name ORDER BY po.sort_order SEPARATOR ' / ') as variant_name,
                    GROUP_CONCAT(vov.option_value_id ORDER BY po.sort_order) as option_value_ids
             FROM product_variants pv
             LEFT JOIN variant_option_values vov ON pv.id = vov.variant_id
             LEFT JOIN product_option_values pov ON vov.option_value_id = pov.id
             LEFT JOIN product_options po ON pov.option_id = po.id
             WHERE pv.product_id = ?
             GROUP BY pv.id
             ORDER BY pv.id",
            [$productId]
        );
    }

    /**
     * Find variant by selected option values
     */
    public function findVariant(int $productId, array $optionValueIds): array|false
    {
        if (empty($optionValueIds)) {
            return false;
        }

        $placeholders = implode(',', array_fill(0, count($optionValueIds), '?'));
        $params = array_merge([$productId], $optionValueIds, [count($optionValueIds)]);

        return $this->queryOne(
            "SELECT pv.* FROM product_variants pv
             JOIN variant_option_values vov ON pv.id = vov.variant_id
             WHERE pv.product_id = ?
             AND vov.option_value_id IN ($placeholders)
             AND pv.is_active = 1
             GROUP BY pv.id
             HAVING COUNT(DISTINCT vov.option_value_id) = ?",
            $params
        );
    }

    /**
     * Get product with all data (images, options, variants)
     */
    public function getFullProduct(int $id): array|false
    {
        $product = $this->find($id);
        if ($product) {
            // Get primary images (no parent)
            $primaryImages = $this->query(
                "SELECT * FROM product_images WHERE product_id = ? AND parent_image_id IS NULL ORDER BY is_primary DESC, sort_order ASC",
                [$id]
            );

            // Get all sub-images
            $subImages = $this->query(
                "SELECT * FROM product_images WHERE product_id = ? AND parent_image_id IS NOT NULL ORDER BY parent_image_id, sort_order ASC",
                [$id]
            );

            // Get all image-to-option-value links from junction table
            $imageOptionLinks = $this->query(
                "SELECT piov.image_id, piov.option_value_id
                 FROM product_image_option_values piov
                 WHERE piov.image_id IN (SELECT id FROM product_images WHERE product_id = ?)",
                [$id]
            );

            // Group option value IDs by image_id
            $optionValuesByImage = [];
            foreach ($imageOptionLinks as $link) {
                $optionValuesByImage[$link['image_id']][] = (int)$link['option_value_id'];
            }

            // Group sub-images by parent and attach linked option values
            $subImagesByParent = [];
            foreach ($subImages as $sub) {
                $sub['linked_option_value_ids'] = $optionValuesByImage[$sub['id']] ?? [];
                $subImagesByParent[$sub['parent_image_id']][] = $sub;
            }

            // Attach sub-images and linked option values to their primary images
            foreach ($primaryImages as &$primary) {
                $primary['sub_images'] = $subImagesByParent[$primary['id']] ?? [];
                $primary['linked_option_value_ids'] = $optionValuesByImage[$primary['id']] ?? [];
            }

            $product['images'] = $primaryImages;
            $product['options'] = $this->getOptions($id);
            $product['variants'] = $this->getVariants($id);
        }
        return $product;
    }

    /**
     * Get primary image for a product
     */
    public function getPrimaryImage(int $productId): string
    {
        $image = $this->queryOne(
            "SELECT image_path FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC LIMIT 1",
            [$productId]
        );
        return $image ? $image['image_path'] : '/assets/images/placeholder.png';
    }

    /**
     * Get "customers also bought" recommendations based on order history
     */
    public function getAlsoBought(int $productId, int $limit = 4): array
    {
        return $this->query(
            "SELECT p.*,
                    (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC LIMIT 1) as primary_image,
                    (SELECT COUNT(*) FROM product_variants WHERE product_id = p.id) as variant_count,
                    (SELECT COALESCE(SUM(inventory_count), 0) FROM product_variants WHERE product_id = p.id AND is_active = 1) as variant_stock,
                    COUNT(DISTINCT o.id) as times_bought_together
             FROM {$this->table} p
             JOIN order_items oi2 ON p.id = oi2.product_id
             JOIN orders o ON oi2.order_id = o.id
             JOIN order_items oi1 ON o.id = oi1.order_id AND oi1.product_id = ?
             WHERE p.id != ?
             AND p.is_active = 1 AND p.disabled = 0
             AND o.status NOT IN ('cancelled', 'refunded')
             GROUP BY p.id
             ORDER BY times_bought_together DESC, p.id DESC
             LIMIT ?",
            [$productId, $productId, $limit]
        );
    }

    /**
     * Get product recommendations (combines also-bought and related)
     */
    public function getRecommendations(int $productId, int $limit = 4): array
    {
        // First try "customers also bought"
        $alsoBought = $this->getAlsoBought($productId, $limit);

        // If not enough, fill with related products
        if (count($alsoBought) < $limit) {
            $excludeIds = array_column($alsoBought, 'id');
            $excludeIds[] = $productId;
            $needed = $limit - count($alsoBought);

            $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
            $params = array_merge([$productId], $excludeIds, [$needed]);

            $related = $this->query(
                "SELECT DISTINCT p.*,
                        (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC LIMIT 1) as primary_image,
                        (SELECT COUNT(*) FROM product_variants WHERE product_id = p.id) as variant_count,
                        (SELECT COALESCE(SUM(inventory_count), 0) FROM product_variants WHERE product_id = p.id AND is_active = 1) as variant_stock
                 FROM {$this->table} p
                 JOIN product_categories pc ON p.id = pc.product_id
                 WHERE pc.category_id IN (
                     SELECT category_id FROM product_categories WHERE product_id = ?
                 )
                 AND p.id NOT IN ($placeholders)
                 AND p.is_active = 1 AND p.disabled = 0
                 ORDER BY RAND()
                 LIMIT ?",
                $params
            );

            $alsoBought = array_merge($alsoBought, $related);
        }

        return $alsoBought;
    }
}
