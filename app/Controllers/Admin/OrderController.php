<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\OrderStatusEmailService;
use App\Core\AfterShipService;
use App\Models\Order;
use App\Models\AdminUser;

class OrderController extends Controller
{
    private Order $orderModel;
    private AdminUser $adminModel;
    protected ?array $admin = null;

    public function __construct()
    {
        parent::__construct();
        $this->adminModel = new AdminUser();
        $this->orderModel = new Order();
        $this->requireAdmin();
    }

    protected function requireAdmin(): void
    {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        $token = $_COOKIE['admin_token'] ?? null;
        if (!$token) {
            if ($isAjax) {
                $this->json(['error' => 'Not authenticated'], 401);
                exit;
            }
            $this->redirect('/admin/login');
            exit;
        }

        $session = $this->adminModel->validateSession($token);
        if (!$session) {
            setcookie('admin_token', '', ['expires' => time() - 3600, 'path' => '/admin']);
            if ($isAjax) {
                $this->json(['error' => 'Session expired'], 401);
                exit;
            }
            $this->redirect('/admin/login');
            exit;
        }

        $this->admin = $session;
    }

    /**
     * Orders list
     */
    public function index(): void
    {
        $page = max(1, (int)$this->get('page', 1));
        $status = $this->get('status');
        $search = $this->get('search');
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        $orders = $this->orderModel->getAllOrders($perPage, $offset, $status, $search);
        $totalOrders = $this->orderModel->countOrders($status, $search);
        $statusCounts = $this->orderModel->getStatusCounts();

        $this->render('admin.orders.index', [
            'title' => 'Orders',
            'admin' => $this->admin,
            'orders' => $orders,
            'statusCounts' => $statusCounts,
            'currentStatus' => $status,
            'search' => $search,
            'page' => $page,
            'perPage' => $perPage,
            'totalOrders' => $totalOrders,
            'totalPages' => ceil($totalOrders / $perPage)
        ], 'admin');
    }

    /**
     * View order details
     */
    public function view(): void
    {
        $id = (int)$this->get('id');
        $order = $this->orderModel->findById($id);

        if (!$order) {
            setFlash('error', 'Order not found');
            $this->redirect('/admin/orders');
            return;
        }

        $orderItems = $this->orderModel->getOrderItems($id);
        $carriers = Order::getCarriers();

        $this->render('admin.orders.view', [
            'title' => 'Order ' . $order['order_number'],
            'admin' => $this->admin,
            'order' => $order,
            'orderItems' => $orderItems,
            'carriers' => $carriers
        ], 'admin');
    }

    /**
     * Update order status
     */
    public function updateStatus(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/orders');
            return;
        }

        $this->requireValidCSRF();

        $id = (int)$this->post('id');
        $status = $this->post('status');

        $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
        if (!in_array($status, $validStatuses)) {
            setFlash('error', 'Invalid status');
            $this->redirect('/admin/orders/view?id=' . $id);
            return;
        }

        // Get order before updating to check if status changed
        $order = $this->orderModel->findById($id);
        $oldStatus = $order['status'] ?? '';

        error_log("Status change: Order #{$order['order_number']} from '{$oldStatus}' to '{$status}', email: {$order['customer_email']}");

        $this->orderModel->updateStatus($id, $status);

        // Send email notification if status changed and order has email
        $emailSent = false;
        if ($order && $oldStatus !== $status && !empty($order['customer_email'])) {
            try {
                $emailService = new OrderStatusEmailService();
                $emailSent = $emailService->sendStatusEmail($order, $status);
                error_log("Email send result: " . ($emailSent ? 'SUCCESS' : 'FAILED'));
            } catch (\Throwable $e) {
                error_log("Order status email failed: " . $e->getMessage());
            }
        } else {
            error_log("Email skipped: order=" . ($order ? 'yes' : 'no') . ", statusChanged=" . ($oldStatus !== $status ? 'yes' : 'no') . ", hasEmail=" . (!empty($order['customer_email']) ? 'yes' : 'no'));
        }

        $message = $emailSent ? 'Order status updated and customer notified' : 'Order status updated';
        setFlash('success', $message);
        $this->redirect('/admin/orders/view?id=' . $id);
    }

    /**
     * Add tracking information
     */
    public function addTracking(): void
    {
        // Clear output buffers for clean JSON
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            exit;
        }

        try {
            $this->requireValidCSRF();

            $orderId = (int)$this->post('order_id');
            $carrier = $this->post('carrier');
            $trackingNumber = trim($this->post('tracking_number', ''));
            $estimatedDelivery = $this->post('estimated_delivery') ?: null;
            $notifyCustomer = $this->post('notify_customer') === '1';

            if (empty($carrier) || empty($trackingNumber)) {
                echo json_encode(['success' => false, 'error' => 'Carrier and tracking number are required']);
                exit;
            }

            // Validate carrier
            $carriers = Order::getCarriers();
            if (!isset($carriers[$carrier])) {
                echo json_encode(['success' => false, 'error' => 'Invalid carrier']);
                exit;
            }

            // Get order
            $order = $this->orderModel->findById($orderId);
            if (!$order) {
                echo json_encode(['success' => false, 'error' => 'Order not found']);
                exit;
            }

            // Update tracking
            $result = $this->orderModel->addTracking($orderId, $carrier, $trackingNumber, $estimatedDelivery);

            if (!$result) {
                echo json_encode(['success' => false, 'error' => 'Failed to update tracking']);
                exit;
            }

            // Register with AfterShip for automatic delivery tracking
            $afterShipResult = null;
            try {
                $afterShip = new AfterShipService();
                if ($afterShip->isConfigured()) {
                    $afterShipResult = $afterShip->createTracking($trackingNumber, $carrier, [
                        'order_id' => $orderId,
                        'order_number' => $order['order_number'],
                        'customer_email' => $order['customer_email'],
                        'customer_name' => $order['shipping_first_name'] ?? $order['billing_first_name'] ?? ''
                    ]);
                    if (!$afterShipResult['success'] && empty($afterShipResult['already_exists'])) {
                        error_log("AfterShip registration failed: " . ($afterShipResult['error'] ?? 'Unknown'));
                    }
                }
            } catch (\Throwable $e) {
                error_log("AfterShip error: " . $e->getMessage());
            }

            // Send notification email if requested
            if ($notifyCustomer) {
                $this->sendTrackingEmail($order, $carrier, $trackingNumber, $estimatedDelivery);
            }

            $trackingUrl = Order::getTrackingUrl($carrier, $trackingNumber);

            echo json_encode([
                'success' => true,
                'message' => 'Tracking information added successfully' . ($notifyCustomer ? ' and customer notified' : ''),
                'tracking_url' => $trackingUrl,
                'aftership' => $afterShipResult['success'] ?? false
            ]);

        } catch (\Throwable $e) {
            error_log('Add tracking error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'An error occurred: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Send tracking notification email
     */
    private function sendTrackingEmail(array $order, string $carrier, string $trackingNumber, ?string $estimatedDelivery): bool
    {
        $carrierName = Order::getCarrierName($carrier);
        $trackingUrl = Order::getTrackingUrl($carrier, $trackingNumber);

        $customerEmail = $order['customer_email'];
        $customerName = $order['shipping_first_name'] ?? $order['billing_first_name'] ?? 'Customer';
        $orderNumber = $order['order_number'];

        $subject = "Your Order {$orderNumber} Has Shipped!";

        $html = $this->getShippingEmailTemplate([
            'customer_name' => $customerName,
            'order_number' => $orderNumber,
            'carrier_name' => $carrierName,
            'tracking_number' => $trackingNumber,
            'tracking_url' => $trackingUrl,
            'estimated_delivery' => $estimatedDelivery
        ]);

        return sendEmail($customerEmail, $subject, $html, ['html' => true]);
    }

    /**
     * Get shipping notification email template
     */
    private function getShippingEmailTemplate(array $data): string
    {
        $b = \App\Core\ThemeService::getEmailBranding();
        $socialHtml = \App\Core\ThemeService::getEmailSocialLinks();

        $trackButton = '';
        if ($data['tracking_url']) {
            $trackButton = '<a href="' . htmlspecialchars($data['tracking_url']) . '" style="display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, ' . $b['primary'] . ' 0%, ' . $b['primaryDark'] . ' 100%); color: #ffffff !important; text-decoration: none !important; border-radius: 50px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 15px ' . $b['btnShadowRgba'] . ';">Track Your Package</a>';
        }

        $deliveryInfo = '';
        if ($data['estimated_delivery']) {
            $deliveryInfo = '<tr>
                            <td style="padding: 12px 0; color: #6b7280; font-size: 15px; border-top: 1px solid #f3f4f6;">Estimated Delivery:</td>
                            <td style="padding: 12px 0; color: #1f2937; font-size: 15px; font-weight: 600; text-align: right; border-top: 1px solid #f3f4f6;">' . htmlspecialchars($data['estimated_delivery']) . '</td>
                        </tr>';
        }

        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Your Order Has Shipped!</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style type="text/css">
        @import url(\'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap\');
        body { margin: 0; padding: 0; }
        table { border-spacing: 0; }
        td { padding: 0; }
        img { border: 0; }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: ' . $b['accent'] . '; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">

    <!-- Wrapper Table -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: ' . $b['accent'] . ';">
        <tr>
            <td align="center" style="padding: 40px 20px;">

                <!-- Main Container -->
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px ' . $b['shadowRgba'] . ';">

                    <!-- HEADER with decorative circles -->
                    <tr>
                        <td style="position: relative; background: linear-gradient(135deg, ' . $b['accent'] . ' 0%, #FFFFFF 40%, #FFFFFF 60%, ' . $b['accent'] . ' 100%); padding: 0; overflow: hidden;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <!-- Decorative circles (using positioned divs) -->
                                <tr>
                                    <td style="position: relative; height: 0;">
                                        <div style="position: absolute; width: 150px; height: 150px; background: ' . $b['primary'] . '; opacity: 0.08; border-radius: 50%; top: -50px; left: -30px;"></div>
                                        <div style="position: absolute; width: 100px; height: 100px; background: ' . $b['secondary'] . '; opacity: 0.08; border-radius: 50%; top: 20px; right: -20px;"></div>
                                        <div style="position: absolute; width: 80px; height: 80px; background: ' . $b['primary'] . '; opacity: 0.06; border-radius: 50%; bottom: -40px; left: 40%;"></div>
                                    </td>
                                </tr>
                                <!-- Logo area -->
                                <tr>
                                    <td align="center" style="padding: 40px 40px 20px 40px;">
                                        <a href="' . appUrl() . '" style="text-decoration: none;">
                                            <img src="' . $b['logoUrl'] . '" alt="' . htmlspecialchars(appName()) . '" width="280" style="max-width: 280px; width: 100%; height: auto; display: block;">
                                        </a>
                                    </td>
                                </tr>
                                <!-- Tagline -->
                                <tr>
                                    <td align="center" style="padding: 0 40px 30px 40px;">
                                        <p style="margin: 0; font-family: \'Playfair Display\', Georgia, serif; font-size: 14px; font-style: italic; color: ' . $b['primary'] . '; letter-spacing: 0.5px;">
                                            ' . $b['tagline'] . '
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Pink divider line -->
                    <tr>
                        <td style="height: 3px; background: linear-gradient(90deg, ' . $b['accent'] . ', ' . $b['primary'] . ', ' . $b['accent'] . ');"></td>
                    </tr>

                    <!-- TITLE BANNER -->
                    <tr>
                        <td style="background: linear-gradient(135deg, ' . $b['primary'] . ' 0%, ' . $b['primaryDark'] . ' 100%); padding: 22px 40px; text-align: center;">
                            <h1 style="margin: 0; font-family: \'Playfair Display\', Georgia, serif; font-size: 26px; font-weight: 600; color: #ffffff; letter-spacing: 0.5px;">
                                Your Order Has Shipped!
                            </h1>
                        </td>
                    </tr>

                    <!-- MAIN CONTENT -->
                    <tr>
                        <td style="padding: 40px;">

                            <!-- Greeting -->
                            <p style="margin: 0 0 20px 0; font-size: 18px; color: #1f2937; line-height: 1.5;">
                                Hi <strong style="color: ' . $b['primary'] . ';">' . htmlspecialchars($data['customer_name']) . '</strong>,
                            </p>

                            <p style="margin: 0 0 30px 0; font-size: 16px; color: #4b5563; line-height: 1.7;">
                                Great news! Your order <strong style="color: #1f2937;">' . htmlspecialchars($data['order_number']) . '</strong> is on its way to you.
                            </p>

                            <!-- Shipping Details Box -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb; margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 20px 25px; border-bottom: 2px solid ' . $b['primary'] . ';">
                                        <h2 style="margin: 0; font-family: \'Playfair Display\', Georgia, serif; font-size: 18px; font-weight: 600; color: #1f2937;">
                                            Shipping Details
                                        </h2>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 20px 25px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding: 12px 0; color: #6b7280; font-size: 15px;">Carrier:</td>
                                                <td style="padding: 12px 0; color: #1f2937; font-size: 15px; font-weight: 600; text-align: right;">' . htmlspecialchars($data['carrier_name']) . '</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 12px 0; color: #6b7280; font-size: 15px; border-top: 1px solid #f3f4f6;">Tracking Number:</td>
                                                <td style="padding: 12px 0; color: #1f2937; font-size: 15px; font-weight: 600; text-align: right; font-family: \'Courier New\', monospace; border-top: 1px solid #f3f4f6;">' . htmlspecialchars($data['tracking_number']) . '</td>
                                            </tr>
                                            ' . $deliveryInfo . '
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA Button -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 10px 0 30px 0;">
                                        ' . $trackButton . '
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0; font-size: 14px; color: #6b7280; line-height: 1.6; text-align: center;">
                                You can also copy the tracking number and paste it on the carrier\'s website.
                            </p>

                        </td>
                    </tr>

                    <!-- FOOTER -->
                    <tr>
                        <td style="background-color: ' . $b['accent'] . '; padding: 30px 40px; text-align: center; border-top: 1px solid ' . $b['accent'] . ';">
                            <p style="margin: 0 0 15px 0; font-family: \'Playfair Display\', Georgia, serif; font-size: 16px; font-style: italic; color: ' . $b['primary'] . ';">
                                Thank you for shopping with us!
                            </p>
                            ' . $socialHtml . '
                            <p style="margin: 0; font-size: 13px; color: #9ca3af; line-height: 1.6;">
                                Questions? Contact us at <a href="mailto:' . storeEmail() . '" style="color: ' . $b['primary'] . '; text-decoration: none;">' . storeEmail() . '</a>
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>';
    }

    /**
     * Update order notes
     */
    public function updateNotes(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/orders');
            return;
        }

        $this->requireValidCSRF();

        $id = (int)$this->post('id');
        $notes = $this->post('notes', '');

        $this->orderModel->updateNotes($id, $notes);
        setFlash('success', 'Notes updated');
        $this->redirect('/admin/orders/view?id=' . $id);
    }

    /**
     * Delete an order
     */
    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/orders');
            return;
        }

        $this->requireValidCSRF();

        $id = (int) $this->post('id');

        if ($this->orderModel->deleteOrder($id)) {
            setFlash('success', 'Order deleted');
        } else {
            setFlash('error', 'Failed to delete order');
        }

        $this->redirect('/admin/orders');
    }

    /**
     * Quick status update (AJAX)
     */
    public function quickStatus(): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
            exit;
        }

        try {
            $this->requireValidCSRF();

            $id = (int) $this->post('id');
            $status = $this->post('status');

            $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
            if (!in_array($status, $validStatuses)) {
                echo json_encode(['success' => false, 'error' => 'Invalid status']);
                exit;
            }

            // Get order before updating to check if status changed
            $order = $this->orderModel->findById($id);
            $oldStatus = $order['status'] ?? '';

            $this->orderModel->updateStatus($id, $status);

            // Send email notification if status changed and order has email
            $emailSent = false;
            if ($order && $oldStatus !== $status && !empty($order['customer_email'])) {
                try {
                    $emailService = new OrderStatusEmailService();
                    $emailSent = $emailService->sendStatusEmail($order, $status);
                } catch (\Throwable $e) {
                    error_log("Order status email failed: " . $e->getMessage());
                }
            }

            $message = $emailSent ? 'Status updated and customer notified' : 'Status updated';
            echo json_encode(['success' => true, 'message' => $message, 'email_sent' => $emailSent]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Profits report page
     */
    public function profits(): void
    {
        $db = Database::getInstance();

        $period = $this->get('period', 'monthly');
        $year = (int)$this->get('year', date('Y'));

        if (!in_array($period, ['daily', 'weekly', 'monthly', 'yearly'])) {
            $period = 'monthly';
        }
        if ($year < 2000 || $year > 2099) {
            $year = (int)date('Y');
        }

        $excludeStatuses = "'cancelled','refunded'";

        // Build period-based queries
        $periodData = [];

        if ($period === 'monthly') {
            // Generate all 12 months for the selected year
            for ($m = 1; $m <= 12; $m++) {
                $monthStart = sprintf('%04d-%02d-01', $year, $m);
                $monthEnd = date('Y-m-t', strtotime($monthStart));

                $row = $db->selectOne(
                    "SELECT
                        COALESCE(SUM(o.total), 0) AS revenue,
                        COALESCE(SUM(o.actual_shipping_cost), 0) AS shipping_cost,
                        COUNT(o.id) AS order_count
                     FROM orders o
                     WHERE o.status NOT IN ({$excludeStatuses})
                       AND o.created_at >= ? AND o.created_at < DATE_ADD(?, INTERVAL 1 DAY)",
                    [$monthStart, $monthEnd]
                );

                $cogs = $db->selectOne(
                    "SELECT COALESCE(SUM(COALESCE(oi.cost, pv.cost, p.cost, 0) * oi.quantity), 0) AS cogs
                     FROM order_items oi
                     JOIN orders o ON o.id = oi.order_id
                     LEFT JOIN product_variants pv ON pv.id = oi.variant_id
                     LEFT JOIN products p ON p.id = oi.product_id
                     WHERE o.status NOT IN ({$excludeStatuses})
                       AND o.created_at >= ? AND o.created_at < DATE_ADD(?, INTERVAL 1 DAY)",
                    [$monthStart, $monthEnd]
                );

                $shippingCostOnly = $db->selectOne(
                    "SELECT COALESCE(SUM(o.actual_shipping_cost), 0) AS shipping_cost
                     FROM orders o
                     WHERE o.status NOT IN ({$excludeStatuses})
                       AND o.actual_shipping_cost IS NOT NULL
                       AND o.created_at >= ? AND o.created_at < DATE_ADD(?, INTERVAL 1 DAY)",
                    [$monthStart, $monthEnd]
                );

                $revenue = (float)$row['revenue'];
                $cogsVal = (float)$cogs['cogs'];
                $shipCost = (float)$shippingCostOnly['shipping_cost'];
                $profit = $revenue - $cogsVal - $shipCost;
                $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

                $periodData[] = [
                    'label' => date('M', strtotime($monthStart)),
                    'revenue' => $revenue,
                    'cogs' => $cogsVal,
                    'shipping_cost' => $shipCost,
                    'profit' => $profit,
                    'margin' => $margin,
                    'order_count' => (int)$row['order_count'],
                ];
            }
        } elseif ($period === 'yearly') {
            $currentYear = (int)date('Y');
            for ($y = $currentYear - 4; $y <= $currentYear; $y++) {
                $yearStart = sprintf('%04d-01-01', $y);
                $yearEnd = sprintf('%04d-12-31', $y);

                $row = $db->selectOne(
                    "SELECT
                        COALESCE(SUM(o.total), 0) AS revenue,
                        COUNT(o.id) AS order_count
                     FROM orders o
                     WHERE o.status NOT IN ({$excludeStatuses})
                       AND o.created_at >= ? AND o.created_at < DATE_ADD(?, INTERVAL 1 DAY)",
                    [$yearStart, $yearEnd]
                );

                $cogs = $db->selectOne(
                    "SELECT COALESCE(SUM(COALESCE(oi.cost, pv.cost, p.cost, 0) * oi.quantity), 0) AS cogs
                     FROM order_items oi
                     JOIN orders o ON o.id = oi.order_id
                     LEFT JOIN product_variants pv ON pv.id = oi.variant_id
                     LEFT JOIN products p ON p.id = oi.product_id
                     WHERE o.status NOT IN ({$excludeStatuses})
                       AND o.created_at >= ? AND o.created_at < DATE_ADD(?, INTERVAL 1 DAY)",
                    [$yearStart, $yearEnd]
                );

                $shippingCostOnly = $db->selectOne(
                    "SELECT COALESCE(SUM(o.actual_shipping_cost), 0) AS shipping_cost
                     FROM orders o
                     WHERE o.status NOT IN ({$excludeStatuses})
                       AND o.actual_shipping_cost IS NOT NULL
                       AND o.created_at >= ? AND o.created_at < DATE_ADD(?, INTERVAL 1 DAY)",
                    [$yearStart, $yearEnd]
                );

                $revenue = (float)$row['revenue'];
                $cogsVal = (float)$cogs['cogs'];
                $shipCost = (float)$shippingCostOnly['shipping_cost'];
                $profit = $revenue - $cogsVal - $shipCost;
                $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

                $periodData[] = [
                    'label' => (string)$y,
                    'revenue' => $revenue,
                    'cogs' => $cogsVal,
                    'shipping_cost' => $shipCost,
                    'profit' => $profit,
                    'margin' => $margin,
                    'order_count' => (int)$row['order_count'],
                ];
            }
        } elseif ($period === 'daily') {
            // Show last 30 days
            for ($d = 29; $d >= 0; $d--) {
                $date = date('Y-m-d', strtotime("-{$d} days"));

                $row = $db->selectOne(
                    "SELECT
                        COALESCE(SUM(o.total), 0) AS revenue,
                        COUNT(o.id) AS order_count
                     FROM orders o
                     WHERE o.status NOT IN ({$excludeStatuses})
                       AND DATE(o.created_at) = ?",
                    [$date]
                );

                $cogs = $db->selectOne(
                    "SELECT COALESCE(SUM(COALESCE(oi.cost, pv.cost, p.cost, 0) * oi.quantity), 0) AS cogs
                     FROM order_items oi
                     JOIN orders o ON o.id = oi.order_id
                     LEFT JOIN product_variants pv ON pv.id = oi.variant_id
                     LEFT JOIN products p ON p.id = oi.product_id
                     WHERE o.status NOT IN ({$excludeStatuses})
                       AND DATE(o.created_at) = ?",
                    [$date]
                );

                $shippingCostOnly = $db->selectOne(
                    "SELECT COALESCE(SUM(o.actual_shipping_cost), 0) AS shipping_cost
                     FROM orders o
                     WHERE o.status NOT IN ({$excludeStatuses})
                       AND o.actual_shipping_cost IS NOT NULL
                       AND DATE(o.created_at) = ?",
                    [$date]
                );

                $revenue = (float)$row['revenue'];
                $cogsVal = (float)$cogs['cogs'];
                $shipCost = (float)$shippingCostOnly['shipping_cost'];
                $profit = $revenue - $cogsVal - $shipCost;
                $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

                $periodData[] = [
                    'label' => date('M j', strtotime($date)),
                    'revenue' => $revenue,
                    'cogs' => $cogsVal,
                    'shipping_cost' => $shipCost,
                    'profit' => $profit,
                    'margin' => $margin,
                    'order_count' => (int)$row['order_count'],
                ];
            }
        } elseif ($period === 'weekly') {
            // Show last 12 weeks
            for ($w = 11; $w >= 0; $w--) {
                $weekStart = date('Y-m-d', strtotime("monday -{$w} weeks"));
                $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));

                $row = $db->selectOne(
                    "SELECT
                        COALESCE(SUM(o.total), 0) AS revenue,
                        COUNT(o.id) AS order_count
                     FROM orders o
                     WHERE o.status NOT IN ({$excludeStatuses})
                       AND o.created_at >= ? AND o.created_at < DATE_ADD(?, INTERVAL 1 DAY)",
                    [$weekStart, $weekEnd]
                );

                $cogs = $db->selectOne(
                    "SELECT COALESCE(SUM(COALESCE(oi.cost, pv.cost, p.cost, 0) * oi.quantity), 0) AS cogs
                     FROM order_items oi
                     JOIN orders o ON o.id = oi.order_id
                     LEFT JOIN product_variants pv ON pv.id = oi.variant_id
                     LEFT JOIN products p ON p.id = oi.product_id
                     WHERE o.status NOT IN ({$excludeStatuses})
                       AND o.created_at >= ? AND o.created_at < DATE_ADD(?, INTERVAL 1 DAY)",
                    [$weekStart, $weekEnd]
                );

                $shippingCostOnly = $db->selectOne(
                    "SELECT COALESCE(SUM(o.actual_shipping_cost), 0) AS shipping_cost
                     FROM orders o
                     WHERE o.status NOT IN ({$excludeStatuses})
                       AND o.actual_shipping_cost IS NOT NULL
                       AND o.created_at >= ? AND o.created_at < DATE_ADD(?, INTERVAL 1 DAY)",
                    [$weekStart, $weekEnd]
                );

                $revenue = (float)$row['revenue'];
                $cogsVal = (float)$cogs['cogs'];
                $shipCost = (float)$shippingCostOnly['shipping_cost'];
                $profit = $revenue - $cogsVal - $shipCost;
                $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

                $periodData[] = [
                    'label' => date('M j', strtotime($weekStart)),
                    'revenue' => $revenue,
                    'cogs' => $cogsVal,
                    'shipping_cost' => $shipCost,
                    'profit' => $profit,
                    'margin' => $margin,
                    'order_count' => (int)$row['order_count'],
                ];
            }
        }

        // Aggregate totals for the summary cards
        $totalRevenue = array_sum(array_column($periodData, 'revenue'));
        $totalCogs = array_sum(array_column($periodData, 'cogs'));
        $totalShippingCost = array_sum(array_column($periodData, 'shipping_cost'));
        $totalProfit = $totalRevenue - $totalCogs - $totalShippingCost;
        $totalMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;
        $totalOrders = array_sum(array_column($periodData, 'order_count'));

        // Top 10 products by revenue (all time, excluding cancelled/refunded)
        $topProducts = $db->select(
            "SELECT
                oi.product_name,
                oi.variant_name,
                SUM(oi.quantity) AS total_qty,
                SUM(oi.total) AS total_revenue,
                SUM(COALESCE(oi.cost, pv.cost, p.cost, 0) * oi.quantity) AS total_cost,
                SUM(oi.total) - SUM(COALESCE(oi.cost, pv.cost, p.cost, 0) * oi.quantity) AS total_profit
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             LEFT JOIN product_variants pv ON pv.id = oi.variant_id
             LEFT JOIN products p ON p.id = oi.product_id
             WHERE o.status NOT IN ({$excludeStatuses})
             GROUP BY oi.product_id, oi.variant_id, oi.product_name, oi.variant_name
             ORDER BY total_revenue DESC
             LIMIT 10"
        );

        // Cost coverage: how many order items have cost data vs NULL
        $costCoverage = $db->selectOne(
            "SELECT
                COUNT(*) AS total_items,
                SUM(CASE WHEN COALESCE(oi.cost, pv.cost, p.cost) IS NOT NULL THEN 1 ELSE 0 END) AS items_with_cost,
                SUM(CASE WHEN COALESCE(oi.cost, pv.cost, p.cost) IS NULL THEN 1 ELSE 0 END) AS items_without_cost
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             LEFT JOIN product_variants pv ON pv.id = oi.variant_id
             LEFT JOIN products p ON p.id = oi.product_id
             WHERE o.status NOT IN ({$excludeStatuses})"
        );

        // Shipping cost coverage
        $shippingCoverage = $db->selectOne(
            "SELECT
                COUNT(*) AS total_orders,
                SUM(CASE WHEN actual_shipping_cost IS NOT NULL THEN 1 ELSE 0 END) AS orders_with_shipping_cost,
                SUM(CASE WHEN actual_shipping_cost IS NULL THEN 1 ELSE 0 END) AS orders_without_shipping_cost
             FROM orders
             WHERE status NOT IN ({$excludeStatuses})"
        );

        $this->render('admin.orders.profits', [
            'title' => 'Profits',
            'admin' => $this->admin,
            'period' => $period,
            'year' => $year,
            'periodData' => $periodData,
            'totalRevenue' => $totalRevenue,
            'totalCogs' => $totalCogs,
            'totalShippingCost' => $totalShippingCost,
            'totalProfit' => $totalProfit,
            'totalMargin' => $totalMargin,
            'totalOrders' => $totalOrders,
            'topProducts' => $topProducts,
            'costCoverage' => $costCoverage,
            'shippingCoverage' => $shippingCoverage,
        ], 'admin');
    }

    /**
     * Check if request is AJAX
     */
    protected function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Update actual shipping cost for profit tracking
     */
    public function updateShippingCost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/orders');
            return;
        }

        $this->requireValidCSRF();

        $id = (int)$this->post('id');
        $actualShippingCost = $this->post('actual_shipping_cost');

        // Allow null/empty to clear the value
        if ($actualShippingCost === '' || $actualShippingCost === null) {
            $costValue = null;
        } else {
            $costValue = floatval($actualShippingCost);
        }

        $db = Database::getInstance();
        $db->update(
            "UPDATE orders SET actual_shipping_cost = ? WHERE id = ?",
            [$costValue, $id]
        );

        setFlash('success', 'Actual shipping cost updated');
        $this->redirect('/admin/orders/view?id=' . $id);
    }

    /**
     * Update item cost for profit tracking (AJAX)
     */
    public function updateItemCost(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid method']);
            return;
        }

        $this->requireValidCSRF();

        $itemId = (int)$this->post('item_id');
        $cost = $this->post('cost');
        $orderId = (int)$this->post('order_id');

        if (!$itemId) {
            echo json_encode(['success' => false, 'error' => 'Invalid item ID']);
            return;
        }

        // Allow null/empty to clear the value
        if ($cost === '' || $cost === null) {
            $costValue = null;
        } else {
            $costValue = floatval($cost);
        }

        $db = Database::getInstance();
        $db->update(
            "UPDATE order_items SET cost = ? WHERE id = ?",
            [$costValue, $itemId]
        );

        // Recalculate profit for this order
        $orderItems = $this->orderModel->getOrderItems($orderId);
        $order = $this->orderModel->getById($orderId);

        // Handle case where order is not found
        if (!$order) {
            echo json_encode(['success' => false, 'error' => 'Order not found']);
            return;
        }

        $productCost = 0;
        $hasMissingCosts = false;
        foreach ($orderItems as $item) {
            if ($item['product_cost'] !== null) {
                $productCost += $item['product_cost'] * $item['quantity'];
            } else {
                $hasMissingCosts = true;
            }
        }

        $orderTotal = (float)($order['total'] ?? 0);
        $actualShipping = $order['actual_shipping_cost'] ?? null;
        $totalCost = $productCost + ($actualShipping ?? 0);
        $profit = $orderTotal - $totalCost;
        $profitMargin = $orderTotal > 0 ? ($profit / $orderTotal) * 100 : 0;

        echo json_encode([
            'success' => true,
            'productCost' => $productCost,
            'totalCost' => $totalCost,
            'profit' => $profit,
            'profitMargin' => $profitMargin,
            'hasMissingCosts' => $hasMissingCosts,
            'canCalculateProfit' => !$hasMissingCosts && $actualShipping !== null
        ]);
    }
}
