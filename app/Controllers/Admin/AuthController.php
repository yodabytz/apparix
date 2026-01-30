<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
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
     * Show login form
     */
    public function login(): void
    {
        // If already logged in, redirect to dashboard
        if ($this->getAdmin()) {
            $this->redirect('/admin');
            return;
        }

        $this->render('admin.auth.login', [
            'title' => 'Admin Login'
        ], 'admin');
    }

    /**
     * Handle login POST
     */
    public function doLogin(): void
    {
        $this->requireValidCSRF();

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

        // Clear rate limiting on successful login
        $this->rateLimiter->clearAttempts($ip, $email, 'admin');

        // Create session
        $token = $this->adminModel->createSession(
            $admin['id'],
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

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

        setFlash('success', 'Welcome back, ' . $admin['name'] . '!');
        $this->redirect('/admin');
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
