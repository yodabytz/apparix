<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\OrderDownload;
use App\Models\AdminUser;

class DownloadController extends Controller
{
    private OrderDownload $downloadModel;
    private AdminUser $adminModel;
    protected ?array $admin = null;

    public function __construct()
    {
        parent::__construct();
        $this->adminModel = new AdminUser();
        $this->downloadModel = new OrderDownload();
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
     * Downloads dashboard
     */
    public function index(): void
    {
        // Check if downloads section is enabled
        if (!setting('enable_downloads_section')) {
            $this->redirect('/admin');
            return;
        }

        $page = max(1, (int)$this->get('page', 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $logs = $this->downloadModel->getDownloadLogs($perPage, $offset);
        $totalLogs = $this->downloadModel->countDownloadLogs();
        $stats = $this->downloadModel->getDownloadStats();
        $totalPages = max(1, ceil($totalLogs / $perPage));

        $this->render('admin.downloads.index', [
            'title' => 'Downloads',
            'admin' => $this->admin,
            'logs' => $logs,
            'stats' => $stats,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalLogs' => $totalLogs,
        ], 'admin');
    }
}
