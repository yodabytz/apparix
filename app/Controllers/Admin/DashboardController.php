<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\License;
use App\Models\AdminUser;
use App\Models\Visitor;

class DashboardController extends Controller
{
    private AdminUser $adminModel;
    private ?array $admin = null;

    public function __construct()
    {
        parent::__construct();
        $this->adminModel = new AdminUser();
        $this->requireAdmin();
    }

    /**
     * Require admin authentication (overrides base controller)
     */
    protected function requireAdmin(): void
    {
        $token = $_COOKIE['admin_token'] ?? null;
        if (!$token) {
            $this->redirect('/admin/login');
            exit;
        }

        $session = $this->adminModel->validateSession($token);
        if (!$session) {
            // Clear invalid cookie
            setcookie('admin_token', '', ['expires' => time() - 3600, 'path' => '/admin']);
            $this->redirect('/admin/login');
            exit;
        }

        $this->admin = $session;
    }

    /**
     * Get current admin
     */
    protected function getAdmin(): array
    {
        return $this->admin;
    }

    /**
     * Dashboard home
     */
    public function index(): void
    {
        $db = Database::getInstance();

        // Get sales statistics
        $todaySales = $db->selectOne(
            "SELECT COALESCE(SUM(total), 0) as total, COUNT(*) as count
             FROM orders WHERE DATE(created_at) = CURDATE() AND payment_status = 'paid'"
        );

        $weekSales = $db->selectOne(
            "SELECT COALESCE(SUM(total), 0) as total, COUNT(*) as count
             FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND payment_status = 'paid'"
        );

        $monthSales = $db->selectOne(
            "SELECT COALESCE(SUM(total), 0) as total, COUNT(*) as count
             FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND payment_status = 'paid'"
        );

        $allTimeSales = $db->selectOne(
            "SELECT COALESCE(SUM(total), 0) as total, COUNT(*) as count
             FROM orders WHERE payment_status = 'paid'"
        );

        // Get product counts
        $productCount = $db->selectOne("SELECT COUNT(*) as count FROM products WHERE is_active = 1");
        $lowStockCount = $db->selectOne(
            "SELECT COUNT(DISTINCT p.id) as count FROM products p
             LEFT JOIN product_variants pv ON p.id = pv.product_id
             WHERE p.is_active = 1 AND (p.inventory_count <= 5 OR pv.inventory_count <= 5)"
        );

        // Get recent orders
        $recentOrders = $db->select(
            "SELECT * FROM orders ORDER BY created_at DESC LIMIT 10"
        );

        // Get order status breakdown
        $ordersByStatus = $db->select(
            "SELECT status, COUNT(*) as count FROM orders GROUP BY status"
        );

        // Get recent activity
        $recentActivity = $this->adminModel->getRecentActivity(10);

        // Daily sales for chart (last 30 days)
        $dailySales = $db->select(
            "SELECT DATE(created_at) as date, COALESCE(SUM(total), 0) as total, COUNT(*) as count
             FROM orders
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND payment_status = 'paid'
             GROUP BY DATE(created_at)
             ORDER BY date ASC"
        );

        // === PROFIT TRACKING ===
        // Get orders with complete cost data for profit calculation
        $profitData = $db->selectOne(
            "SELECT
                COALESCE(SUM(o.total), 0) as total_revenue,
                COALESCE(SUM(o.shipping_cost), 0) as shipping_charged,
                COALESCE(SUM(o.actual_shipping_cost), 0) as shipping_paid,
                (SELECT COALESCE(SUM(oi.quantity * p.cost), 0)
                 FROM order_items oi
                 JOIN products p ON oi.product_id = p.id
                 JOIN orders ord ON oi.order_id = ord.id
                 WHERE ord.payment_status = 'paid'
                   AND p.cost IS NOT NULL
                   AND ord.actual_shipping_cost IS NOT NULL) as product_costs
             FROM orders o
             WHERE o.payment_status = 'paid'
               AND o.actual_shipping_cost IS NOT NULL"
        );

        // Calculate all-time profit metrics for orders with COMPLETE data
        // An order is complete if all items have product cost and shipping cost is entered
        $completeOrders = $db->selectOne(
            "SELECT
                COUNT(DISTINCT o.id) as order_count,
                COALESCE(SUM(o.total), 0) as revenue,
                COALESCE(SUM(o.actual_shipping_cost), 0) as shipping_paid
             FROM orders o
             WHERE o.payment_status = 'paid'
               AND o.actual_shipping_cost IS NOT NULL
               AND NOT EXISTS (
                   SELECT 1 FROM order_items oi
                   JOIN products p ON oi.product_id = p.id
                   WHERE oi.order_id = o.id AND p.cost IS NULL
               )"
        );

        // Get product costs for complete orders
        $productCostsForCompleteOrders = $db->selectOne(
            "SELECT COALESCE(SUM(oi.quantity * p.cost), 0) as total
             FROM order_items oi
             JOIN products p ON oi.product_id = p.id
             JOIN orders o ON oi.order_id = o.id
             WHERE o.payment_status = 'paid'
               AND o.actual_shipping_cost IS NOT NULL
               AND NOT EXISTS (
                   SELECT 1 FROM order_items oi2
                   JOIN products p2 ON oi2.product_id = p2.id
                   WHERE oi2.order_id = o.id AND p2.cost IS NULL
               )"
        );

        $totalProductCosts = (float)($productCostsForCompleteOrders['total'] ?? 0);
        $totalShippingPaid = (float)($completeOrders['shipping_paid'] ?? 0);
        $totalRevenue = (float)($completeOrders['revenue'] ?? 0);
        $totalCosts = $totalProductCosts + $totalShippingPaid;
        $totalProfit = $totalRevenue - $totalCosts;
        $profitMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;

        $profitStats = [
            'complete_order_count' => (int)($completeOrders['order_count'] ?? 0),
            'total_revenue' => $totalRevenue,
            'product_costs' => $totalProductCosts,
            'shipping_paid' => $totalShippingPaid,
            'total_costs' => $totalCosts,
            'total_profit' => $totalProfit,
            'profit_margin' => $profitMargin
        ];

        // === NEEDS ATTENTION ===
        // Products missing cost data:
        // - Products with variants: any variant missing cost (fallback to product cost)
        // - Products without variants: base product cost is NULL
        // - Excludes products marked as cost_not_applicable (e.g., digital downloads)
        $productsNeedingCost = $db->select(
            "SELECT p.id, p.name, p.slug, p.price,
                    (SELECT COUNT(*) FROM product_variants pv WHERE pv.product_id = p.id) as variant_count,
                    (SELECT COUNT(*) FROM product_variants pv WHERE pv.product_id = p.id AND pv.cost IS NULL AND p.cost IS NULL) as variants_missing_cost,
                    (SELECT COUNT(*) FROM order_items oi
                     JOIN orders o ON oi.order_id = o.id
                     WHERE oi.product_id = p.id AND o.payment_status = 'paid') as times_sold
             FROM products p
             WHERE p.is_active = 1
               AND p.cost_not_applicable = 0
               AND (
                   -- Products with variants: check if any variant is missing cost (no variant cost AND no fallback product cost)
                   EXISTS (SELECT 1 FROM product_variants pv WHERE pv.product_id = p.id AND pv.cost IS NULL AND p.cost IS NULL)
                   OR
                   -- Products without variants: base product cost is NULL
                   (NOT EXISTS (SELECT 1 FROM product_variants pv WHERE pv.product_id = p.id) AND p.cost IS NULL)
               )
             ORDER BY times_sold DESC
             LIMIT 10"
        );

        $productsMissingCostCount = $db->selectOne(
            "SELECT COUNT(*) as count FROM products p
             WHERE p.is_active = 1
               AND p.cost_not_applicable = 0
               AND (
                   EXISTS (SELECT 1 FROM product_variants pv WHERE pv.product_id = p.id AND pv.cost IS NULL AND p.cost IS NULL)
                   OR
                   (NOT EXISTS (SELECT 1 FROM product_variants pv WHERE pv.product_id = p.id) AND p.cost IS NULL)
               )"
        );

        // Orders without actual shipping cost
        $ordersNeedingShippingCost = $db->select(
            "SELECT o.id, o.order_number, o.total, o.shipping_cost, o.created_at,
                    o.customer_email
             FROM orders o
             WHERE o.payment_status = 'paid'
               AND o.actual_shipping_cost IS NULL
               AND o.status NOT IN ('cancelled', 'refunded')
             ORDER BY o.created_at DESC
             LIMIT 10"
        );

        $ordersMissingShippingCount = $db->selectOne(
            "SELECT COUNT(*) as count FROM orders
             WHERE payment_status = 'paid'
               AND actual_shipping_cost IS NULL
               AND status NOT IN ('cancelled', 'refunded')"
        );

        $needsAttention = [
            'products_needing_cost' => $productsNeedingCost,
            'products_missing_cost_count' => (int)($productsMissingCostCount['count'] ?? 0),
            'orders_needing_shipping' => $ordersNeedingShippingCost,
            'orders_missing_shipping_count' => (int)($ordersMissingShippingCount['count'] ?? 0)
        ];

        // === FAVORITES TRACKING ===
        $favoritesStats = [
            'today' => $db->selectOne(
                "SELECT COUNT(*) as count, COUNT(DISTINCT COALESCE(user_id, session_id)) as unique_users
                 FROM favorites WHERE DATE(created_at) = CURDATE()"
            ),
            'week' => $db->selectOne(
                "SELECT COUNT(*) as count, COUNT(DISTINCT COALESCE(user_id, session_id)) as unique_users
                 FROM favorites WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
            ),
            'month' => $db->selectOne(
                "SELECT COUNT(*) as count, COUNT(DISTINCT COALESCE(user_id, session_id)) as unique_users
                 FROM favorites WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
            ),
            'all' => $db->selectOne(
                "SELECT COUNT(*) as count, COUNT(DISTINCT COALESCE(user_id, session_id)) as unique_users
                 FROM favorites"
            )
        ];

        // === VISITOR TRACKING ===
        try {
            $visitorModel = new Visitor();
            $visitorStats = $visitorModel->getStatsSummary();
            $visitorsByCountry = $visitorModel->getByCountry('month', 10);
            $topReferrers = $visitorModel->getTopReferrers('month', 10);
            $topPages = $visitorModel->getTopPages('month', 10);
        } catch (\Exception $e) {
            $visitorStats = null;
            $visitorsByCountry = [];
            $topReferrers = [];
            $topPages = [];
            error_log('Visitor tracking error in dashboard: ' . $e->getMessage());
        }

        // Get license info
        $licenseInfo = License::getEditionInfo();
        $licenseInfo['product_count'] = (int)$productCount['count'];
        $licenseInfo['product_remaining'] = License::getRemainingCount('max_products', (int)$productCount['count']);

        $this->render('admin.dashboard.index', [
            'title' => 'Admin Dashboard',
            'admin' => $this->admin,
            'todaySales' => $todaySales,
            'weekSales' => $weekSales,
            'monthSales' => $monthSales,
            'allTimeSales' => $allTimeSales,
            'productCount' => $productCount['count'],
            'lowStockCount' => $lowStockCount['count'],
            'recentOrders' => $recentOrders,
            'ordersByStatus' => $ordersByStatus,
            'recentActivity' => $recentActivity,
            'dailySales' => $dailySales,
            'profitStats' => $profitStats,
            'needsAttention' => $needsAttention,
            'visitorStats' => $visitorStats,
            'visitorsByCountry' => $visitorsByCountry,
            'topReferrers' => $topReferrers,
            'topPages' => $topPages,
            'favoritesStats' => $favoritesStats,
            'licenseInfo' => $licenseInfo
        ], 'admin');
    }
}
