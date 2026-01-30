<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\UpdateService;
use App\Models\AdminUser;

/**
 * Admin controller for checking and installing software updates
 */
class UpdateController extends Controller
{
    private AdminUser $adminModel;
    private UpdateService $updateService;
    private ?array $admin = null;

    public function __construct()
    {
        parent::__construct();
        $this->adminModel = new AdminUser();
        $this->updateService = new UpdateService();
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
     * Show updates page
     */
    public function index(): void
    {
        $versionInfo = $this->updateService->getVersionInfo();
        $backups = $this->updateService->getBackups();

        $this->render('admin.updates.index', [
            'title' => 'Software Updates',
            'admin' => $this->admin,
            'versionInfo' => $versionInfo,
            'backups' => $backups
        ], 'admin');
    }

    /**
     * Check for updates (AJAX)
     */
    public function check(): void
    {
        $result = $this->updateService->checkForUpdates();
        $this->json($result);
    }

    /**
     * Install update (AJAX)
     */
    public function install(): void
    {
        $this->requireValidCSRF();

        $targetVersion = $this->post('version');

        if (empty($targetVersion)) {
            $this->json(['success' => false, 'error' => 'No version specified']);
            return;
        }

        // Log the update attempt
        $this->adminModel->logActivity(
            $this->admin['admin_id'],
            'update_attempt',
            'system',
            null,
            "Attempting to update to v{$targetVersion}"
        );

        $result = $this->updateService->installUpdate($targetVersion);

        if ($result['success']) {
            $this->adminModel->logActivity(
                $this->admin['admin_id'],
                'update_success',
                'system',
                null,
                "Successfully updated to v{$targetVersion}"
            );
        } else {
            $this->adminModel->logActivity(
                $this->admin['admin_id'],
                'update_failed',
                'system',
                null,
                "Update to v{$targetVersion} failed: " . ($result['error'] ?? 'Unknown error')
            );
        }

        $this->json($result);
    }

    /**
     * Get current version info (AJAX)
     */
    public function version(): void
    {
        $this->json([
            'success' => true,
            'version' => $this->updateService->getVersionInfo()
        ]);
    }

    /**
     * Cleanup old backups
     */
    public function cleanupBackups(): void
    {
        $this->requireValidCSRF();

        $keepCount = (int) $this->post('keep', 5);
        $deleted = $this->updateService->cleanupBackups($keepCount);

        $this->json([
            'success' => true,
            'deleted' => $deleted,
            'message' => $deleted > 0 ? "Deleted {$deleted} old backup(s)" : 'No backups to delete'
        ]);
    }
}
