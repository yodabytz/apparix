<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\OrderDownload;
use App\Models\OrderLicense;

/**
 * DownloadController - handles digital product downloads
 * All downloads require authentication and ownership verification.
 */
class DownloadController extends Controller
{
    private OrderDownload $downloadModel;
    private OrderLicense $licenseModel;

    public function __construct()
    {
        parent::__construct();
        $this->downloadModel = new OrderDownload();
        $this->licenseModel = new OrderLicense();
    }

    /**
     * Require user authentication and return user data.
     * Redirects to login with return URL if not authenticated.
     */
    private function requireDownloadAuth(string $returnPath): ?array
    {
        $user = auth();
        if (!$user) {
            $_SESSION['intended_url'] = $returnPath;
            setFlash('error', 'Please log in to access your downloads.');
            $this->redirect('/login');
            return null;
        }
        return $user;
    }

    /**
     * Verify the authenticated user owns the download (via order.user_id).
     */
    private function verifyOwnership(array $download, int $userId): bool
    {
        return !empty($download['user_id']) && (int)$download['user_id'] === $userId;
    }

    /**
     * Download a file by token
     */
    public function download(): void
    {
        $token = $_GET['token'] ?? '';

        // Require authentication
        $user = $this->requireDownloadAuth('/account/downloads');
        if (!$user) {
            return;
        }

        $result = $this->downloadModel->isValidDownload($token);

        if (!$result['valid']) {
            $this->render('download.error', [
                'title' => 'Download Error',
                'error' => $result['error']
            ]);
            return;
        }

        $download = $result['download'];

        // Verify ownership
        if (!$this->verifyOwnership($download, $user['id'])) {
            $this->render('download.error', [
                'title' => 'Download Error',
                'error' => 'This download belongs to a different account.'
            ]);
            return;
        }

        $safeName = basename($download['download_file']);
        $downloadsDir = BASE_PATH . '/storage/downloads';

        // Ensure downloads directory exists
        if (!is_dir($downloadsDir)) {
            @mkdir($downloadsDir, 0750, true);
        }

        $filePath = $downloadsDir . '/' . $safeName;

        // Verify file is within allowed directory (prevent path traversal)
        $realPath = realpath($filePath);
        $allowedDir = realpath($downloadsDir);
        if ($realPath === false || $allowedDir === false || strpos($realPath, $allowedDir) !== 0) {
            $this->render('download.error', [
                'title' => 'Download Error',
                'error' => 'File not found. Please contact support.'
            ]);
            return;
        }

        if (!file_exists($filePath)) {
            $this->render('download.error', [
                'title' => 'Download Error',
                'error' => 'File not found. Please contact support.'
            ]);
            return;
        }

        // Record the download (wrapped in try-catch so logging never blocks the download)
        try {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
            $this->downloadModel->recordDownload($download['id'], $ip);

            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $referer = $_SERVER['HTTP_REFERER'] ?? null;
            $this->downloadModel->logDownloadEvent($download['id'], $download['product_id'], $ip ?? '', $userAgent, $referer);
        } catch (\Throwable $e) {
            error_log('Download log error: ' . $e->getMessage());
        }

        // Serve the file
        $filename = basename($download['download_file']);
        $filesize = filesize($filePath);
        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Send headers — prevent caching and URL sharing
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . $filesize);
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Send file
        readfile($filePath);
        exit;
    }

    /**
     * Show download page with license info
     */
    public function show(): void
    {
        $token = $_GET['token'] ?? '';

        // Require authentication
        $user = $this->requireDownloadAuth('/download/' . $token);
        if (!$user) {
            return;
        }

        $result = $this->downloadModel->isValidDownload($token);

        if (!$result['valid']) {
            $this->render('download.error', [
                'title' => 'Download Error',
                'error' => $result['error']
            ]);
            return;
        }

        $download = $result['download'];

        // Verify ownership
        if (!$this->verifyOwnership($download, $user['id'])) {
            $this->render('download.error', [
                'title' => 'Download Error',
                'error' => 'This download belongs to a different account.'
            ]);
            return;
        }

        // Get any licenses for this order
        $licenses = $this->licenseModel->getByOrderId($download['order_id']);

        $this->render('download.show', [
            'title' => 'Your Download',
            'download' => $download,
            'licenses' => $licenses,
            'token' => $token
        ]);
    }

    /**
     * Customer license lookup — requires login, only shows own downloads
     */
    public function lookup(): void
    {
        $user = auth();
        if (!$user) {
            $_SESSION['intended_url'] = '/licenses/lookup';
            setFlash('error', 'Please log in to view your licenses and downloads.');
            $this->redirect('/login');
            return;
        }

        // Always use the authenticated user's email — ignore any email parameter
        $email = $user['email'];

        $licenses = $this->licenseModel->getByCustomerEmail($email);
        $downloads = $this->downloadModel->getByCustomerEmail($email);

        $this->render('download.lookup', [
            'title' => 'Your Licenses',
            'email' => $email,
            'licenses' => $licenses,
            'downloads' => $downloads
        ]);
    }
}
