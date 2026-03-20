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
             FROM orders WHERE DATE(created_at) = CURDATE() AND payment_status = 'paid' AND customer_email NOT LIKE '%@fake.local'"
        );

        $weekSales = $db->selectOne(
            "SELECT COALESCE(SUM(total), 0) as total, COUNT(*) as count
             FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND payment_status = 'paid' AND customer_email NOT LIKE '%@fake.local'"
        );

        $monthSales = $db->selectOne(
            "SELECT COALESCE(SUM(total), 0) as total, COUNT(*) as count
             FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND payment_status = 'paid' AND customer_email NOT LIKE '%@fake.local'"
        );

        $allTimeSales = $db->selectOne(
            "SELECT COALESCE(SUM(total), 0) as total, COUNT(*) as count
             FROM orders WHERE payment_status = 'paid' AND customer_email NOT LIKE '%@fake.local'"
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
            "SELECT * FROM orders WHERE customer_email NOT LIKE '%@fake.local' ORDER BY created_at DESC LIMIT 10"
        );

        // Get order status breakdown
        $ordersByStatus = $db->select(
            "SELECT status, COUNT(*) as count FROM orders WHERE customer_email NOT LIKE '%@fake.local' GROUP BY status"
        );

        // Get recent activity
        $recentActivity = $this->adminModel->getRecentActivity(10);

        // Daily sales for chart (last 30 days)
        $dailySales = $db->select(
            "SELECT DATE(created_at) as date, COALESCE(SUM(total), 0) as total, COUNT(*) as count
             FROM orders
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND payment_status = 'paid' AND customer_email NOT LIKE '%@fake.local'
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
                   AND ord.actual_shipping_cost IS NOT NULL
                   AND ord.customer_email NOT LIKE '%@fake.local') as product_costs
             FROM orders o
             WHERE o.payment_status = 'paid'
               AND o.actual_shipping_cost IS NOT NULL
               AND o.customer_email NOT LIKE '%@fake.local'"
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
               AND o.customer_email NOT LIKE '%@fake.local'
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
               AND o.customer_email NOT LIKE '%@fake.local'
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
               AND o.customer_email NOT LIKE '%@fake.local'
             ORDER BY o.created_at DESC
             LIMIT 10"
        );

        $ordersMissingShippingCount = $db->selectOne(
            "SELECT COUNT(*) as count FROM orders
             WHERE payment_status = 'paid'
               AND actual_shipping_cost IS NULL
               AND status NOT IN ('cancelled', 'refunded')
               AND customer_email NOT LIKE '%@fake.local'"
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

        // === ABANDONED CART TRACKING ===
        $abandonedCartStats = [];
        try {
            // Active abandoned carts (have items, no order placed, not recovered)
            $abandonedCartStats['active'] = $db->selectOne(
                "SELECT COUNT(DISTINCT c.id) as count,
                        COALESCE(SUM(ci.quantity * ci.price), 0) as total_value
                 FROM carts c
                 JOIN cart_items ci ON ci.cart_id = c.id
                 WHERE c.recovered = 0
                   AND c.updated_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)"
            );
            // Emails sent this month
            $abandonedCartStats['emails_sent'] = $db->selectOne(
                "SELECT COUNT(*) as count FROM carts
                 WHERE abandoned_email_sent = 1
                   AND abandoned_email_sent_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
            );
            // Recovered this month
            $abandonedCartStats['recovered'] = $db->selectOne(
                "SELECT COUNT(*) as count FROM carts
                 WHERE recovered = 1
                   AND updated_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
            );
            // Recent abandoned carts with items
            $abandonedCartStats['recent'] = $db->select(
                "SELECT c.id, c.email, c.abandoned_email_sent, c.abandoned_email_sent_at, c.updated_at,
                        COUNT(ci.id) as item_count,
                        SUM(ci.quantity * ci.price) as cart_value
                 FROM carts c
                 JOIN cart_items ci ON ci.cart_id = c.id
                 WHERE c.recovered = 0
                 GROUP BY c.id
                 ORDER BY c.updated_at DESC
                 LIMIT 5"
            );
        } catch (\Throwable $e) {
            $abandonedCartStats = [
                'active' => ['count' => 0, 'total_value' => 0],
                'emails_sent' => ['count' => 0],
                'recovered' => ['count' => 0],
                'recent' => []
            ];
        }

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

        // === SYSTEM HEALTH & SETUP CHECKLIST ===
        $systemHealth = [];
        $setupTasks = [];
        $settingModel = new \App\Models\Setting();

        // --- PHP Configuration ---
        $uploadMax = $this->parsePhpSize(ini_get('upload_max_filesize'));
        $postMax = $this->parsePhpSize(ini_get('post_max_size'));

        if ($uploadMax < 8 * 1024 * 1024) {
            $systemHealth[] = [
                'type' => 'warning',
                'title' => 'Low Upload Limit',
                'message' => 'PHP upload_max_filesize is ' . ini_get('upload_max_filesize') . '. Recommended: at least 8M for image uploads. Edit your php.ini and restart PHP-FPM.'
            ];
        }
        if ($postMax < 10 * 1024 * 1024) {
            $systemHealth[] = [
                'type' => 'warning',
                'title' => 'Low POST Limit',
                'message' => 'PHP post_max_size is ' . ini_get('post_max_size') . '. Recommended: at least 10M. This limits the maximum data you can submit in forms and uploads.'
            ];
        }

        // Check required PHP extensions
        if (!extension_loaded('gd') && !extension_loaded('imagick')) {
            $systemHealth[] = [
                'type' => 'warning',
                'title' => 'No Image Processing',
                'message' => 'Neither GD nor Imagick PHP extensions are installed. Image resizing will not work. Install php-gd: sudo apt install php' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '-gd'
            ];
        }
        if (!extension_loaded('curl')) {
            $systemHealth[] = [
                'type' => 'error',
                'title' => 'cURL Extension Missing',
                'message' => 'The PHP cURL extension is required for payment processing, update checks, and external API calls.'
            ];
        }
        if (!extension_loaded('mbstring')) {
            $systemHealth[] = [
                'type' => 'warning',
                'title' => 'mbstring Extension Missing',
                'message' => 'The PHP mbstring extension is recommended for proper Unicode/UTF-8 text handling.'
            ];
        }

        // --- Directory Permissions ---
        $basePath = dirname(PUBLIC_PATH);
        $dirs = [
            'Branding uploads' => PUBLIC_PATH . '/assets/images/branding',
            'Product images' => PUBLIC_PATH . '/assets/images/products',
            'Storage logs' => $basePath . '/storage/logs',
            'Storage sessions' => $basePath . '/storage/sessions',
        ];
        foreach ($dirs as $label => $dir) {
            if (!is_dir($dir)) {
                $systemHealth[] = [
                    'type' => 'error',
                    'title' => $label . ' Directory Missing',
                    'message' => 'The directory ' . str_replace($basePath, '', $dir) . ' does not exist. Create it and set ownership to your web server user (e.g. www-data).'
                ];
            } elseif (!is_writable($dir)) {
                $systemHealth[] = [
                    'type' => 'error',
                    'title' => $label . ' Not Writable',
                    'message' => 'The directory ' . str_replace($basePath, '', $dir) . ' is not writable. Run: sudo chown -R www-data:www-data ' . $dir
                ];
            }
        }

        // Check .env is not web-accessible (basic check)
        $envFile = $basePath . '/.env';
        if (file_exists($envFile) && strpos(realpath(PUBLIC_PATH), realpath($basePath)) === 0) {
            // .env exists and public dir is inside base - that's normal
            // But check if .env is inside public dir
            if (file_exists(PUBLIC_PATH . '/.env')) {
                $systemHealth[] = [
                    'type' => 'error',
                    'title' => '.env File in Public Directory',
                    'message' => 'Your .env file is inside the public web root. This is a serious security risk. Move it one directory above the public folder.'
                ];
            }
        }

        // --- Store Setup Checklist ---
        $storeName = $settingModel->get('store_name', '');
        if (empty($storeName) || $storeName === 'My Store') {
            $setupTasks[] = [
                'label' => 'Set your store name',
                'link' => '/admin/settings',
                'description' => 'Give your store a name that appears in page titles and throughout the site.'
            ];
        }

        if (empty($settingModel->get('store_email', ''))) {
            $setupTasks[] = [
                'label' => 'Add a contact email',
                'link' => '/admin/settings',
                'description' => 'Set an email address so customers can reach you.'
            ];
        }

        if (empty($settingModel->get('store_logo', ''))) {
            $setupTasks[] = [
                'label' => 'Upload your logo',
                'link' => '/admin/settings',
                'description' => 'Add a logo that appears in the navbar and emails.'
            ];
        }

        if (empty($settingModel->get('store_favicon', ''))) {
            $setupTasks[] = [
                'label' => 'Upload a favicon',
                'link' => '/admin/settings',
                'description' => 'The small icon shown in browser tabs. Upload one under Settings > Branding.'
            ];
        }

        $seoTitle = $settingModel->get('seo_title', '');
        $seoDesc = $settingModel->get('seo_description', '');
        if (empty($seoTitle) && empty($seoDesc)) {
            $setupTasks[] = [
                'label' => 'Configure homepage SEO',
                'link' => '/admin/settings',
                'description' => 'Set a meta title and description so search engines display your site correctly.'
            ];
        }

        if (empty($settingModel->get('seo_og_image', ''))) {
            $setupTasks[] = [
                'label' => 'Upload a social sharing image',
                'link' => '/admin/settings',
                'description' => 'Upload an OG image (1200x630px) that appears when your site is shared on social media.'
            ];
        }

        $stripeKey = $_ENV['STRIPE_SECRET_KEY'] ?? '';
        if (empty($stripeKey)) {
            $setupTasks[] = [
                'label' => 'Configure payment processing',
                'link' => '/admin/settings/payments',
                'description' => 'Add your Stripe API keys to accept payments.'
            ];
        }

        $mailFrom = $settingModel->get('mail_from_email', '');
        if (empty($mailFrom)) {
            $setupTasks[] = [
                'label' => 'Set up email settings',
                'link' => '/admin/settings',
                'description' => 'Configure a sender email address for order confirmations and notifications.'
            ];
        }

        if ((int)$productCount['count'] === 0) {
            $setupTasks[] = [
                'label' => 'Add your first product',
                'link' => '/admin/products/create',
                'description' => 'Start building your catalog by adding products.'
            ];
        }

        // === SECURITY STATUS ===
        $securityStatus = $this->getSecurityStatus();

        // === BACKUP HEALTH ===
        $backupNotifications = [];
        try {
            $backupPluginRow = $db->selectOne(
                "SELECT * FROM plugins WHERE slug = 'backup' AND is_active = 1"
            );
            if ($backupPluginRow) {
                require_once $basePath . '/content/plugins/backup/BackupPlugin.php';
                $bkPlugin = new \App\Plugins\BackupPlugin();
                $bkPlugin->initialize();
                $backupNotifications = $bkPlugin->getNotifications();
            }
        } catch (\Exception $e) {
            // Backup plugin not available — skip silently
        }

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
            'abandonedCartStats' => $abandonedCartStats,
            'licenseInfo' => $licenseInfo,
            'systemHealth' => $systemHealth,
            'setupTasks' => $setupTasks,
            'securityStatus' => $securityStatus,
            'backupNotifications' => $backupNotifications,
        ], 'admin');
    }

    /**
     * Detect security tools (fail2ban, ModSecurity, SecuNX)
     * Uses process list detection (ps/pgrep) so it works without root permissions.
     */
    private function getSecurityStatus(): array
    {
        $tools = [];
        $canExec = \function_exists('shell_exec') && !in_array('shell_exec', array_map('trim', explode(',', ini_get('disable_functions'))));

        // --- Fail2Ban ---
        $f2bInstalled = false;
        $f2bActive = false;
        $f2bDetails = '';

        // Check process list first (works without root)
        if ($canExec) {
            $ps = @\shell_exec('pgrep -x fail2ban-server 2>/dev/null');
            if ($ps && trim($ps) !== '') {
                $f2bInstalled = true;
                $f2bActive = true;
                $f2bDetails = 'Service running';
            }
            // Try to get jail count for more detail
            if ($f2bActive) {
                $output = @\shell_exec('fail2ban-client status 2>/dev/null');
                if ($output && preg_match('/Number of jail:\s*(\d+)/i', $output, $m)) {
                    $f2bDetails = $m[1] . ' active jail' . ((int)$m[1] !== 1 ? 's' : '');
                }
            }
        }

        // Fallback: check known file paths
        if (!$f2bInstalled) {
            $f2bInstalled = file_exists('/usr/bin/fail2ban-client') || file_exists('/usr/sbin/fail2ban-client');
        }
        if ($f2bInstalled && !$f2bActive) {
            // Check PID file
            $f2bPid = @file_get_contents('/var/run/fail2ban/fail2ban.pid');
            if ($f2bPid && is_dir('/proc/' . trim($f2bPid))) {
                $f2bActive = true;
                $f2bDetails = 'Service running';
            }
            // Check socket
            if (!$f2bActive && file_exists('/var/run/fail2ban/fail2ban.sock')) {
                $f2bActive = true;
                $f2bDetails = 'Service running';
            }
            if (!$f2bActive && $f2bInstalled) {
                $f2bDetails = 'Installed but not responding';
            }
        }

        $tools[] = [
            'name' => 'fail2ban',
            'label' => 'Fail2Ban',
            'description' => 'Intrusion prevention — bans IPs after repeated failed login attempts.',
            'installed' => $f2bInstalled,
            'active' => $f2bActive,
            'details' => $f2bDetails,
            'url' => 'https://github.com/fail2ban/fail2ban',
        ];

        // --- ModSecurity ---
        $modsecInstalled = false;
        $modsecActive = false;
        $modsecDetails = '';

        // Check if nginx has modsecurity module loaded (works without root)
        if ($canExec) {
            $nginxV = @\shell_exec('nginx -V 2>&1');
            if ($nginxV && stripos($nginxV, 'modsecurity') !== false) {
                $modsecInstalled = true;
                $modsecActive = true;
                $modsecDetails = 'WAF module loaded in nginx';
            }
        }

        // Fallback: check known file paths
        if (!$modsecInstalled) {
            $modsecInstalled = file_exists('/etc/nginx/modsecurity_includes.conf')
                || file_exists('/etc/modsecurity/modsecurity.conf')
                || file_exists('/usr/lib/nginx/modules/ngx_http_modsecurity_module.so');
        }
        if ($modsecInstalled && !$modsecActive) {
            // Check nginx configs for modsecurity on
            $siteConfigs = glob('/etc/nginx/sites-enabled/*');
            $httpConfigs = glob('/etc/nginx/http.d/*');
            $allConfigs = array_merge($siteConfigs ?: [], $httpConfigs ?: []);
            foreach ($allConfigs as $conf) {
                $content = @file_get_contents($conf);
                if ($content && preg_match('/modsecurity\s+on\s*;/i', $content)) {
                    $modsecActive = true;
                    $modsecDetails = 'WAF enabled in nginx';
                    break;
                }
            }
            if (!$modsecActive) {
                $modsecDetails = 'Installed but not enabled';
            }
        }

        $tools[] = [
            'name' => 'modsecurity',
            'label' => 'ModSecurity',
            'description' => 'Web Application Firewall — blocks SQL injection, XSS, and other attacks.',
            'installed' => $modsecInstalled,
            'active' => $modsecActive,
            'details' => $modsecDetails,
            'url' => 'https://github.com/owasp-modsecurity/ModSecurity',
        ];

        // --- SecuNX ---
        $secunxInstalled = false;
        $secunxActive = false;
        $secunxDetails = '';

        // Check nginx config for secuNX includes (works without root via nginx -T)
        if ($canExec) {
            $nginxT = @\shell_exec('nginx -T 2>/dev/null');
            if ($nginxT && (stripos($nginxT, 'secunx') !== false || stripos($nginxT, 'secuNX') !== false)) {
                $secunxInstalled = true;
                $secunxActive = true;
                // Count deny rules from the dumped config
                $denyCount = substr_count($nginxT, 'deny ');
                if ($denyCount > 0) {
                    $secunxDetails = number_format($denyCount) . ' IPs blocked';
                } else {
                    $secunxDetails = 'Active in nginx';
                }
            }
        }

        // Fallback: check known file paths
        if (!$secunxInstalled) {
            $secunxInstalled = file_exists('/etc/nginx/secuNX/blocklist.conf')
                || file_exists('/etc/nginx/snippets/secunx.conf');
        }
        if ($secunxInstalled && !$secunxActive) {
            $siteConfigs = $siteConfigs ?? glob('/etc/nginx/sites-enabled/*');
            $httpConfigs = glob('/etc/nginx/http.d/*');
            $allConfigs = array_merge($siteConfigs ?: [], $httpConfigs ?: []);
            foreach ($allConfigs as $conf) {
                $content = @file_get_contents($conf);
                if ($content && (str_contains($content, 'secunx') || str_contains($content, 'secuNX'))) {
                    $secunxActive = true;
                    break;
                }
            }
            $blocklistFile = '/etc/nginx/secuNX/blocklist.conf';
            if (file_exists($blocklistFile)) {
                $blockContent = @file_get_contents($blocklistFile);
                if ($blockContent) {
                    $denyCount = substr_count($blockContent, 'deny ');
                    $secunxDetails = $secunxActive
                        ? number_format($denyCount) . ' IPs blocked'
                        : 'Installed but not included in site config';
                }
            }
        }

        $tools[] = [
            'name' => 'secunx',
            'label' => 'SecuNX',
            'description' => 'IP blocklist — blocks known malicious IPs and bot networks.',
            'installed' => $secunxInstalled,
            'active' => $secunxActive,
            'details' => $secunxDetails,
            'url' => 'https://apparix.app/plugins',
        ];

        return $tools;
    }

    /**
     * Parse PHP size string (e.g., '2M', '128K') to bytes
     */
    private function parsePhpSize(string $size): int
    {
        $size = trim($size);
        $last = strtolower($size[strlen($size) - 1]);
        $value = (int)$size;
        switch ($last) {
            case 'g': $value *= 1024;
            case 'm': $value *= 1024;
            case 'k': $value *= 1024;
        }
        return $value;
    }
}
