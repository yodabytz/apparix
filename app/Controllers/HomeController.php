<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Product;

class HomeController extends Controller
{
    /**
     * Display homepage
     */
    public function index(): void
    {
        $productModel = new Product();
        $db = Database::getInstance();

        // Get categories with product counts
        $categories = $db->select(
            "SELECT c.*, COUNT(pc.product_id) as product_count
             FROM categories c
             LEFT JOIN product_categories pc ON c.id = pc.category_id
             LEFT JOIN products p ON pc.product_id = p.id AND p.is_active = 1
             WHERE c.parent_id IS NULL
             GROUP BY c.id
             ORDER BY c.sort_order ASC"
        );

        $data = [
            'featured_products' => $productModel->getFeatured(12),
            'categories' => $categories,
            // Homepage SEO (title, description, keywords) loaded from Admin > Settings in layout
        ];

        $this->render('home.index', $data);
    }

    /**
     * Get recent purchases for social proof popup (AJAX)
     */
    public function recentPurchases(): void
    {
        $db = Database::getInstance();

        // Check for test mode (only in dev or with secret param)
        $testMode = isset($_GET['test']) && $_GET['test'] === 'socialproof2024';

        if ($testMode) {
            // Return fake test data
            $this->json([
                'success' => true,
                'purchase' => [
                    'first_name' => 'Sarah',
                    'last_initial' => 'M',
                    'product_name' => 'Test Product',
                    'city' => 'New York',
                    'state' => 'NY',
                    'time_ago' => 'just now'
                ]
            ]);
            return;
        }

        // Get a random recent purchase from last 5 hours only
        // Only completed orders (not cancelled/refunded)
        $purchase = $db->selectOne(
            "SELECT
                a.first_name,
                LEFT(a.last_name, 1) as last_initial,
                a.city,
                a.state,
                oi.product_name,
                o.created_at
             FROM orders o
             JOIN addresses a ON o.shipping_address_id = a.id
             JOIN order_items oi ON o.id = oi.order_id
             WHERE o.status NOT IN ('cancelled', 'refunded')
               AND o.created_at >= DATE_SUB(NOW(), INTERVAL 5 HOUR)
             ORDER BY RAND()
             LIMIT 1"
        );

        if (!$purchase) {
            $this->json(['success' => false, 'message' => 'No recent purchases']);
            return;
        }

        // Calculate time ago
        $createdAt = new \DateTime($purchase['created_at']);
        $now = new \DateTime();
        $diff = $now->diff($createdAt);

        if ($diff->days > 0) {
            $timeAgo = $diff->days . ' day' . ($diff->days > 1 ? 's' : '') . ' ago';
        } elseif ($diff->h > 0) {
            $timeAgo = $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        } elseif ($diff->i > 0) {
            $timeAgo = $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        } else {
            $timeAgo = 'just now';
        }

        // Truncate product name if too long
        $productName = $purchase['product_name'];
        if (strlen($productName) > 40) {
            $productName = substr($productName, 0, 37) . '...';
        }

        $this->json([
            'success' => true,
            'purchase' => [
                'first_name' => $purchase['first_name'],
                'last_initial' => $purchase['last_initial'],
                'product_name' => $productName,
                'city' => $purchase['city'],
                'state' => $purchase['state'],
                'time_ago' => $timeAgo
            ]
        ]);
    }
}
