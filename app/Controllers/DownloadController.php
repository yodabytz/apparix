<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\OrderDownload;
use App\Models\OrderLicense;

/**
 * DownloadController - handles digital product downloads
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
     * Download a file by token
     */
    public function download(string $token): void
    {
        $result = $this->downloadModel->isValidDownload($token);

        if (!$result['valid']) {
            $this->render('download.error', [
                'title' => 'Download Error',
                'error' => $result['error']
            ]);
            return;
        }

        $download = $result['download'];
        $filePath = BASE_PATH . '/storage/downloads/' . $download['download_file'];

        if (!file_exists($filePath)) {
            $this->render('download.error', [
                'title' => 'Download Error',
                'error' => 'File not found. Please contact support.'
            ]);
            return;
        }

        // Record the download
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
        $this->downloadModel->recordDownload($download['id'], $ip);

        // Serve the file
        $filename = basename($download['download_file']);
        $filesize = filesize($filePath);
        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Send headers
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . $filesize);
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Expires: 0');

        // Send file
        readfile($filePath);
        exit;
    }

    /**
     * Show download page with license info
     */
    public function show(string $token): void
    {
        $result = $this->downloadModel->isValidDownload($token);

        if (!$result['valid']) {
            $this->render('download.error', [
                'title' => 'Download Error',
                'error' => $result['error']
            ]);
            return;
        }

        $download = $result['download'];

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
     * Customer license lookup by email
     */
    public function lookup(): void
    {
        $email = $this->post('email') ?? $this->get('email');

        if (!$email) {
            $this->render('download.lookup', [
                'title' => 'License Lookup'
            ]);
            return;
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->render('download.lookup', [
                'title' => 'License Lookup',
                'error' => 'Please enter a valid email address'
            ]);
            return;
        }

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
