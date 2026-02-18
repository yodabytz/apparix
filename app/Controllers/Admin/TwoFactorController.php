<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\TOTP;
use App\Core\SecurityEmailService;
use App\Models\AdminUser;

class TwoFactorController extends Controller
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
            setcookie('admin_token', '', ['expires' => time() - 3600, 'path' => '/admin']);
            $this->redirect('/admin/login');
            exit;
        }

        $this->admin = $session;
    }

    /**
     * Show 2FA status page
     */
    public function index(): void
    {
        $adminId = $this->admin['admin_id'];
        $enabled = $this->adminModel->has2FAEnabled($adminId);
        $backupCodesCount = $enabled ? $this->adminModel->getRemainingBackupCodesCount($adminId) : 0;
        $deviceCount = $this->adminModel->getDeviceCount($adminId);

        $this->render('admin.two-factor.index', [
            'title' => 'Two-Factor Authentication',
            'admin' => $this->admin,
            'enabled' => $enabled,
            'backupCodesCount' => $backupCodesCount,
            'deviceCount' => $deviceCount,
        ], 'admin');
    }

    /**
     * Show 2FA setup page (generate secret, QR code, backup codes)
     */
    public function setup(): void
    {
        $adminId = $this->admin['admin_id'];

        if ($this->adminModel->has2FAEnabled($adminId)) {
            setFlash('error', 'Two-factor authentication is already enabled.');
            $this->redirect('/admin/2fa');
            return;
        }

        // Generate new secret and backup codes
        $setupData = $this->adminModel->init2FASetup($adminId);
        $secret = $setupData['secret'];
        $backupCodes = $setupData['backup_codes'];

        $provisioningUri = TOTP::getProvisioningUri($secret, $this->admin['email']);
        $qrCodeUrl = TOTP::getQRCodeUrl($provisioningUri);

        $this->render('admin.two-factor.setup', [
            'title' => 'Enable Two-Factor Authentication',
            'admin' => $this->admin,
            'secret' => $secret,
            'qrCodeUrl' => $qrCodeUrl,
            'backupCodes' => $backupCodes,
        ], 'admin');
    }

    /**
     * Enable 2FA after verifying code from authenticator app
     */
    public function enable(): void
    {
        $this->requireValidCSRF();

        $adminId = $this->admin['admin_id'];
        $code = trim($this->post('code', ''));

        if (empty($code)) {
            setFlash('error', 'Please enter the verification code from your authenticator app.');
            $this->redirect('/admin/2fa/setup');
            return;
        }

        if ($this->adminModel->enable2FA($adminId, $code)) {
            // Register current device
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $fingerprint = $this->adminModel->generateDeviceFingerprint($ip, $userAgent);
            $this->adminModel->registerDevice($adminId, $fingerprint, $ip, $userAgent);

            // Log activity
            $this->adminModel->logActivity($adminId, '2fa_enabled', null, null, 'Two-factor authentication enabled');

            // Send notification email
            SecurityEmailService::send2FAEnabledNotification($this->admin['email'], $this->admin['name']);

            setFlash('success', 'Two-factor authentication has been enabled successfully.');
            $this->redirect('/admin/2fa');
        } else {
            setFlash('error', 'Invalid verification code. Please try again.');
            $this->redirect('/admin/2fa/setup');
        }
    }

    /**
     * Disable 2FA (requires password confirmation)
     */
    public function disable(): void
    {
        $this->requireValidCSRF();

        $adminId = $this->admin['admin_id'];
        $password = $_POST['password'] ?? '';

        if (empty($password)) {
            setFlash('error', 'Please enter your password to disable two-factor authentication.');
            $this->redirect('/admin/2fa');
            return;
        }

        // Verify password
        $admin = $this->adminModel->findByEmail($this->admin['email']);
        if (!$admin || !$this->adminModel->verifyPassword($password, $admin['password_hash'])) {
            setFlash('error', 'Incorrect password. Two-factor authentication was not disabled.');
            $this->redirect('/admin/2fa');
            return;
        }

        $this->adminModel->disable2FA($adminId);

        // Log activity
        $this->adminModel->logActivity($adminId, '2fa_disabled', null, null, 'Two-factor authentication disabled');

        // Send notification email
        SecurityEmailService::send2FADisabledNotification($this->admin['email'], $this->admin['name']);

        setFlash('success', 'Two-factor authentication has been disabled.');
        $this->redirect('/admin/2fa');
    }

    /**
     * Regenerate backup codes
     */
    public function regenerateBackupCodes(): void
    {
        $this->requireValidCSRF();

        $adminId = $this->admin['admin_id'];

        if (!$this->adminModel->has2FAEnabled($adminId)) {
            setFlash('error', 'Two-factor authentication is not enabled.');
            $this->redirect('/admin/2fa');
            return;
        }

        $codes = $this->adminModel->regenerateBackupCodes($adminId);

        // Log activity
        $this->adminModel->logActivity($adminId, '2fa_backup_codes_regenerated', null, null, 'Backup codes regenerated');

        $this->render('admin.two-factor.backup-codes', [
            'title' => 'New Backup Codes',
            'admin' => $this->admin,
            'backupCodes' => $codes,
        ], 'admin');
    }
}
