<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\SecurityEmailService;
use App\Models\AdminUser;
use App\Models\RateLimiter;

class AuthController extends Controller
{
    private AdminUser $adminModel;
    private RateLimiter $rateLimiter;

    public function __construct()
    {
        parent::__construct();
        $this->adminModel = new AdminUser();
        $this->rateLimiter = new RateLimiter();
    }

    /**
     * Show login form (or 2FA verification form if pending)
     */
    public function login(): void
    {
        // If already logged in, redirect to dashboard
        if ($this->getAdmin()) {
            $this->redirect('/admin');
            return;
        }

        $pending2fa = !empty($_SESSION['pending_2fa_admin']);

        $this->render('admin.auth.login', [
            'title' => 'Admin Login',
            'pending_2fa' => $pending2fa,
        ], 'admin');
    }

    /**
     * Handle login POST
     */
    public function doLogin(): void
    {
        $this->requireValidCSRF();

        // Check if this is a 2FA verification submission
        if (!empty($_SESSION['pending_2fa_admin']) && !empty($_POST['two_factor_code'])) {
            $this->verify2FA();
            return;
        }

        $email = $this->post('email', '');
        // Use raw password - don't HTML-encode it (it's never output to HTML, only hashed)
        $password = $_POST['password'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        // Check rate limiting before processing
        if (!$this->rateLimiter->isAllowed($ip, $email, 'admin')) {
            $message = $this->rateLimiter->getLockoutMessage($ip, $email, 'admin');
            setFlash('error', $message);
            $this->redirect('/admin/login');
            return;
        }

        if (empty($email) || empty($password)) {
            setFlash('error', 'Please enter email and password');
            $this->redirect('/admin/login');
            return;
        }

        $admin = $this->adminModel->findByEmail($email);

        if (!$admin || !$this->adminModel->verifyPassword($password, $admin['password_hash'])) {
            // Log for fail2ban
            error_log("Apparix admin login failed for {$email} from {$ip}");
            // Record failed attempt and apply progressive delay
            $this->rateLimiter->recordFailedAttempt($ip, $email, 'admin');
            $delay = $this->rateLimiter->getDelay($ip, $email, 'admin');
            if ($delay > 0) {
                sleep($delay);
            }
            setFlash('error', 'Invalid email or password');
            $this->redirect('/admin/login');
            return;
        }

        // Clear rate limiting on successful password verification
        $this->rateLimiter->clearAttempts($ip, $email, 'admin');

        // Check if 2FA is enabled for this admin
        if ($this->adminModel->has2FAEnabled($admin['id'])) {
            // Store pending 2FA data in session
            $_SESSION['pending_2fa_admin'] = [
                'admin_id' => $admin['id'],
                'email' => $admin['email'],
                'name' => $admin['name'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'timestamp' => time(),
            ];

            $this->redirect('/admin/login');
            return;
        }

        // No 2FA - complete login directly
        $this->completeLogin($admin);
    }

    /**
     * Verify 2FA code during login
     */
    private function verify2FA(): void
    {
        $pending = $_SESSION['pending_2fa_admin'] ?? null;

        if (!$pending) {
            setFlash('error', 'No pending two-factor verification. Please log in again.');
            $this->redirect('/admin/login');
            return;
        }

        // Check 5-minute timeout
        if (time() - $pending['timestamp'] > 300) {
            unset($_SESSION['pending_2fa_admin']);
            setFlash('error', 'Two-factor verification timed out. Please log in again.');
            $this->redirect('/admin/login');
            return;
        }

        // Rate limit 2FA attempts
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!$this->rateLimiter->isAllowed($ip, $pending['email'], 'admin_2fa')) {
            $message = $this->rateLimiter->getLockoutMessage($ip, $pending['email'], 'admin_2fa');
            setFlash('error', $message);
            $this->redirect('/admin/login');
            return;
        }

        $code = trim($_POST['two_factor_code'] ?? '');

        if (empty($code)) {
            setFlash('error', 'Please enter the verification code.');
            $this->redirect('/admin/login');
            return;
        }

        $result = $this->adminModel->verify2FA($pending['admin_id'], $code);

        if ($result === false) {
            $this->rateLimiter->recordFailedAttempt($ip, $pending['email'], 'admin_2fa');
            setFlash('error', 'Invalid verification code. Please try again.');
            $this->redirect('/admin/login');
            return;
        }

        // Clear pending state
        unset($_SESSION['pending_2fa_admin']);

        // If backup code was used, warn the user
        if ($result === 'backup') {
            $remaining = $this->adminModel->getRemainingBackupCodesCount($pending['admin_id']);
            setFlash('success', "Welcome back, {$pending['name']}! (Backup code used - {$remaining} remaining)");
        }

        // Load admin data for completeLogin
        $admin = $this->adminModel->findByEmail($pending['email']);
        if (!$admin) {
            setFlash('error', 'Account not found. Please log in again.');
            $this->redirect('/admin/login');
            return;
        }

        $this->completeLogin($admin);
    }

    /**
     * Complete the login process (create session, set cookies, device fingerprinting)
     */
    private function completeLogin(array $admin): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Device fingerprinting and new device alerts (only if 2FA is enabled)
        if ($this->adminModel->has2FAEnabled($admin['id'])) {
            $fingerprint = $this->adminModel->generateDeviceFingerprint($ip, $userAgent);
            $existingDeviceCount = $this->adminModel->getDeviceCount($admin['id']);

            if (!$this->adminModel->isKnownDevice($admin['id'], $fingerprint)) {
                // Register new device
                $this->adminModel->registerDevice($admin['id'], $fingerprint, $ip, $userAgent);

                // Send new device alert (only if this isn't the first device)
                if ($existingDeviceCount > 0) {
                    SecurityEmailService::sendNewDeviceNotification($admin['email'], $admin['name'], $ip, $userAgent);
                }
            }
        }

        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);

        // Create session
        $token = $this->adminModel->createSession($admin['id'], $ip, $userAgent);

        // Set cookie (24 hours, HTTP only, secure, same-site strict)
        setcookie('admin_token', $token, [
            'expires' => time() + 86400,
            'path' => '/admin',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        // Set bypass cookie for splash page (path '/' so it works on homepage)
        setcookie('bypass_splash', '1', [
            'expires' => time() + 86400,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        // Log activity
        $this->adminModel->logActivity($admin['id'], 'login', null, null, 'Admin logged in');

        if (empty($_SESSION['flash']['success'])) {
            setFlash('success', 'Welcome back, ' . $admin['name'] . '!');
        }
        $this->redirect('/admin');
    }

    /**
     * Cancel 2FA verification and return to login
     */
    public function cancel2FA(): void
    {
        unset($_SESSION['pending_2fa_admin']);
        $this->redirect('/admin/login');
    }

    /**
     * Logout
     */
    public function logout(): void
    {
        $token = $_COOKIE['admin_token'] ?? null;

        if ($token) {
            $session = $this->adminModel->validateSession($token);
            if ($session) {
                $this->adminModel->logActivity($session['admin_id'], 'logout', null, null, 'Admin logged out');
            }
            $this->adminModel->destroySession($token);
        }

        // Clear cookies
        setcookie('admin_token', '', [
            'expires' => time() - 3600,
            'path' => '/admin',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        setcookie('bypass_splash', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        setFlash('success', 'You have been logged out');
        $this->redirect('/admin/login');
    }

    /**
     * Get current admin from session
     */
    private function getAdmin(): array|false
    {
        $token = $_COOKIE['admin_token'] ?? null;
        if (!$token) {
            return false;
        }
        return $this->adminModel->validateSession($token);
    }
}
