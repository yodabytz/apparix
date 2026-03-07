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

        // Wrap entire install in try/catch to ALWAYS return valid JSON
        try {
            // Log the update attempt
            $this->adminModel->logActivity(
                $this->admin['admin_id'],
                'update_attempt',
                'system',
                null,
                "Attempting to update to v{$targetVersion}"
            );

            $result = $this->updateService->installUpdate($targetVersion);

            // Clear OPcache so the new code takes effect immediately
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }

            // Log result — wrapped separately so a poisoned DB connection
            // from migrations can't prevent the JSON response
            try {
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
            } catch (\Throwable $logErr) {
                error_log("Post-update activity log failed: " . $logErr->getMessage());
            }

            $this->json($result);
        } catch (\Throwable $e) {
            error_log("Update install fatal error: " . $e->getMessage());
            $this->json([
                'success' => false,
                'error' => 'Update failed: ' . $e->getMessage()
            ]);
        }
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

    /**
     * Download a backup file
     */
    public function download(): void
    {
        $filename = basename($this->get('file', ''));
        if (empty($filename) || !preg_match('/^backup-[\w.\-]+\.tar\.gz$/', $filename)) {
            http_response_code(400);
            echo 'Invalid backup filename';
            return;
        }

        $path = BASE_PATH . '/storage/backups/' . $filename;
        if (!file_exists($path)) {
            http_response_code(404);
            echo 'Backup not found';
            return;
        }

        header('Content-Type: application/gzip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    /**
     * Restore from a backup (AJAX)
     */
    public function restore(): void
    {
        $this->requireValidCSRF();

        $filename = basename($this->post('file', ''));
        $confirmation = $this->post('confirmation', '');

        if ($confirmation !== 'ROLLBACK') {
            $this->json(['success' => false, 'error' => 'Confirmation required. Type ROLLBACK to proceed.']);
            return;
        }

        if (empty($filename) || !preg_match('/^backup-[\w.\-]+\.tar\.gz$/', $filename)) {
            $this->json(['success' => false, 'error' => 'Invalid backup filename']);
            return;
        }

        $path = BASE_PATH . '/storage/backups/' . $filename;
        if (!file_exists($path)) {
            $this->json(['success' => false, 'error' => 'Backup file not found']);
            return;
        }

        try {
            $this->adminModel->logActivity(
                $this->admin['admin_id'],
                'rollback_attempt',
                'system',
                null,
                "Attempting rollback from backup: {$filename}"
            );
        } catch (\Throwable $logErr) {
            error_log("Pre-rollback activity log failed: " . $logErr->getMessage());
        }

        try {
            $phar = new \PharData($path);
            $tempDir = BASE_PATH . '/storage/updates_temp/rollback_' . time();
            mkdir($tempDir, 0755, true);
            $phar->extractTo($tempDir, null, true);

            // Restore files from backup (same directories the backup covers)
            $restoreDirs = ['app', 'public/index.php', 'public/assets/css', 'public/assets/js', 'database/migrations', 'config', 'version.php'];
            foreach ($restoreDirs as $item) {
                $src = $tempDir . '/' . $item;
                $dst = BASE_PATH . '/' . $item;
                if (is_dir($src)) {
                    $this->recursiveCopy($src, $dst);
                } elseif (is_file($src)) {
                    copy($src, $dst);
                }
            }

            // Cleanup temp
            $this->recursiveDelete($tempDir);

            // Clear OPcache if available
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }

            try {
                $this->adminModel->logActivity(
                    $this->admin['admin_id'],
                    'rollback_success',
                    'system',
                    null,
                    "Successfully rolled back from backup: {$filename}"
                );
            } catch (\Throwable $logErr) {
                error_log("Post-rollback activity log failed: " . $logErr->getMessage());
            }

            $this->json(['success' => true, 'message' => 'Rollback complete. The site has been restored to this backup version. Please reload the page.']);
        } catch (\Throwable $e) {
            try {
                $this->adminModel->logActivity(
                    $this->admin['admin_id'],
                    'rollback_failed',
                    'system',
                    null,
                    "Rollback failed: " . $e->getMessage()
                );
            } catch (\Throwable $logErr) {
                error_log("Rollback failure activity log failed: " . $logErr->getMessage());
            }
            $this->json(['success' => false, 'error' => 'Rollback failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Recursively copy directory
     */
    private function recursiveCopy(string $src, string $dst): void
    {
        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }
        $dir = opendir($src);
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') continue;
            $srcPath = $src . '/' . $file;
            $dstPath = $dst . '/' . $file;
            if (is_dir($srcPath)) {
                $this->recursiveCopy($srcPath, $dstPath);
            } else {
                copy($srcPath, $dstPath);
            }
        }
        closedir($dir);
    }

    /**
     * Recursively delete directory
     */
    private function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->recursiveDelete($path) : unlink($path);
        }
        rmdir($dir);
    }
}
