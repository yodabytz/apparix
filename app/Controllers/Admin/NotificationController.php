<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\StockNotificationService;
use App\Models\AdminUser;
use App\Models\StockNotification;

class NotificationController extends Controller
{
    private AdminUser $adminModel;
    private ?array $admin = null;
    private StockNotification $notificationModel;

    public function __construct()
    {
        parent::__construct();
        $this->adminModel = new AdminUser();
        $this->notificationModel = new StockNotification();
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
     * List all pending stock notifications
     */
    public function index(): void
    {
        $notifications = $this->notificationModel->getAllPending();
        $stats = $this->notificationModel->getStats();

        $this->render('admin.notifications.index', [
            'title' => 'Stock Notifications',
            'admin' => $this->admin,
            'notifications' => $notifications,
            'stats' => $stats
        ], 'admin');
    }

    /**
     * Cancel a notification (AJAX)
     */
    public function cancel(): void
    {
        $this->requireValidCSRF();

        $id = (int)$this->post('id', 0);

        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid notification ID']);
            return;
        }

        $result = $this->notificationModel->cancel($id);

        $this->json(['success' => $result]);
    }

    /**
     * Manually trigger notifications for a product (AJAX)
     */
    public function trigger(): void
    {
        $this->requireValidCSRF();

        $productId = (int)$this->post('product_id', 0);

        if ($productId <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid product ID']);
            return;
        }

        $service = new StockNotificationService();
        $results = $service->triggerNotificationsForProduct($productId);

        $totalSent = 0;
        foreach ($results as $result) {
            $totalSent += $result['sent'];
        }

        $this->json([
            'success' => true,
            'sent' => $totalSent,
            'message' => $totalSent > 0 ? "Sent {$totalSent} notification(s)" : "No notifications to send"
        ]);
    }
}
