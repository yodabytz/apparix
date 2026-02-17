<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\ReCaptcha;
use App\Models\User;
use App\Models\Newsletter;
use App\Models\Favorite;
use App\Models\Cart;
use App\Models\RateLimiter;

class UserController extends Controller
{
    private User $userModel;
    private Newsletter $newsletterModel;
    private Favorite $favoriteModel;
    private Cart $cartModel;
    private RateLimiter $rateLimiter;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->newsletterModel = new Newsletter();
        $this->favoriteModel = new Favorite();
        $this->cartModel = new Cart();
        $this->rateLimiter = new RateLimiter();
    }

    /**
     * Show login form
     */
    public function loginForm(): void
    {
        if (auth()) {
            $this->redirect('/account');
            return;
        }

        $this->render('user/login', [
            'title' => 'Login'
        ]);
    }

    /**
     * Process login
     */
    public function login(): void
    {
        $this->requireValidCSRF();

        $email = trim($this->post('email', ''));
        $password = $this->post('password', '');
        $remember = $this->post('remember', '') === '1';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        // Check rate limiting before processing
        if (!$this->rateLimiter->isAllowed($ip, $email, 'user')) {
            $message = $this->rateLimiter->getLockoutMessage($ip, $email, 'user');
            setFlash('error', $message);
            $this->redirect('/login');
            return;
        }

        // Verify reCAPTCHA
        $recaptchaToken = $this->post('recaptcha_token', '');
        if (!empty($recaptchaToken)) {
            $recaptchaResult = ReCaptcha::verify($recaptchaToken, 'login');
            if (!$recaptchaResult['success']) {
                setFlash('error', 'Security verification failed. Please try again.');
                $this->redirect('/login');
                return;
            }
        }

        if (empty($email) || empty($password)) {
            setFlash('error', 'Please enter your email and password');
            $this->redirect('/login');
            return;
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user || !$user['password_hash']) {
            // Record failed attempt
            $this->rateLimiter->recordFailedAttempt($ip, $email, 'user');
            $delay = $this->rateLimiter->getDelay($ip, $email, 'user');
            if ($delay > 0) {
                sleep($delay);
            }
            setFlash('error', 'Invalid email or password');
            $this->redirect('/login');
            return;
        }

        if (!$this->userModel->verifyPassword($password, $user['password_hash'])) {
            // Record failed attempt
            $this->rateLimiter->recordFailedAttempt($ip, $email, 'user');
            $delay = $this->rateLimiter->getDelay($ip, $email, 'user');
            if ($delay > 0) {
                sleep($delay);
            }
            setFlash('error', 'Invalid email or password');
            $this->redirect('/login');
            return;
        }

        // Clear rate limiting on successful login
        $this->rateLimiter->clearAttempts($ip, $email, 'user');

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['first_name'] ?: explode('@', $user['email'])[0];

        // Set remember me cookie if requested
        if ($remember) {
            $token = $this->userModel->setRememberToken($user['id']);
            setcookie('remember_token', $token, [
                'expires' => time() + (30 * 24 * 60 * 60),
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }

        // Merge guest cart with user cart if applicable
        $this->mergeGuestCart($user['id']);

        // Merge guest favorites with user favorites
        $this->favoriteModel->mergeGuestFavorites(session_id(), $user['id']);

        setFlash('success', 'Welcome back' . ($user['first_name'] ? ', ' . $user['first_name'] : '') . '!');

        // Redirect to intended page or account
        $intended = $_SESSION['intended_url'] ?? '/account';
        unset($_SESSION['intended_url']);
        $this->redirect($intended);
    }

    /**
     * Show registration form
     */
    public function registerForm(): void
    {
        if (auth()) {
            $this->redirect('/account');
            return;
        }

        $this->render('user/register', [
            'title' => 'Create Account'
        ]);
    }

    /**
     * Process registration
     */
    public function register(): void
    {
        $this->requireValidCSRF();

        // Verify reCAPTCHA
        $recaptchaToken = $this->post('recaptcha_token', '');
        if (!empty($recaptchaToken)) {
            $recaptchaResult = ReCaptcha::verify($recaptchaToken, 'register');
            if (!$recaptchaResult['success']) {
                setFlash('error', 'Security verification failed. Please try again.');
                $this->redirect('/register');
                return;
            }
        }

        $email = trim($this->post('email', ''));
        $password = $this->post('password', '');
        $confirmPassword = $this->post('confirm_password', '');
        $firstName = trim($this->post('first_name', ''));
        $lastName = trim($this->post('last_name', ''));
        $newsletter = $this->post('newsletter', '') === '1';

        // Validation
        $errors = [];

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address';
        }

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }

        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        }

        if (!empty($errors)) {
            setFlash('error', implode('<br>', $errors));
            $this->redirect('/register');
            return;
        }

        // Check if email exists
        if ($this->userModel->findByEmail($email)) {
            setFlash('error', 'An account with this email already exists. <a href="/login">Login instead?</a>');
            $this->redirect('/register');
            return;
        }

        // Create user
        $userId = $this->userModel->createUser($email, $password, $firstName ?: null, $lastName ?: null, $newsletter);

        if (!$userId) {
            setFlash('error', 'Failed to create account. Please try again.');
            $this->redirect('/register');
            return;
        }

        // Subscribe to newsletter if requested
        if ($newsletter) {
            $this->newsletterModel->subscribe($email, $firstName ?: null, $userId, 'registration');
        }

        // Send welcome email
        $this->sendWelcomeEmail($email, $firstName ?: null);

        // Auto-login
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $firstName ?: explode('@', $email)[0];

        // Merge guest cart
        $this->mergeGuestCart($userId);

        // Merge guest favorites
        $this->favoriteModel->mergeGuestFavorites(session_id(), $userId);

        setFlash('success', 'Account created successfully! Welcome to ' . appName() . '.');
        $this->redirect('/account');
    }

    /**
     * Logout
     */
    public function logout(): void
    {
        // Clear remember token
        if (auth()) {
            $this->userModel->clearRememberToken(auth()['id']);
        }

        // Clear session
        unset($_SESSION['user_id']);
        unset($_SESSION['user_email']);
        unset($_SESSION['user_name']);

        // Clear remember cookie
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }

        setFlash('success', 'You have been logged out');
        $this->redirect('/');
    }

    /**
     * Account dashboard
     */
    public function dashboard(): void
    {
        $this->requireAuth();

        $user = $this->userModel->findById(auth()['id']);
        $recentOrders = $this->userModel->getOrders(auth()['id'], 5);
        $addresses = $this->userModel->getAddresses(auth()['id']);

        $this->render('user/dashboard', [
            'title' => 'My Account',
            'user' => $user,
            'recentOrders' => $recentOrders,
            'addresses' => $addresses
        ]);
    }

    /**
     * Order history
     */
    public function orders(): void
    {
        $this->requireAuth();

        $userId = auth()['id'];
        $orders = $this->userModel->getOrders($userId, 50);

        // Fetch order items for each order and check review status
        $orderModel = new \App\Models\Order();
        $reviewModel = new \App\Models\Review();

        foreach ($orders as &$order) {
            $order['items'] = $orderModel->getOrderItems($order['id']);

            // Check which products have been reviewed
            foreach ($order['items'] as &$item) {
                if (!empty($item['product_id'])) {
                    $item['has_reviewed'] = $reviewModel->hasUserReviewedProduct($userId, (int)$item['product_id']);
                } else {
                    $item['has_reviewed'] = false;
                }
            }
        }

        $this->render('user/orders', [
            'title' => 'Order History',
            'orders' => $orders
        ]);
    }

    /**
     * Update profile (AJAX)
     */
    public function updateProfile(): void
    {
        $this->requireAuth();
        $this->requireValidCSRF();

        $userId = auth()['id'];
        $userEmail = auth()['email'];
        $user = $this->userModel->findById($userId);
        $wasSubscribed = (bool)($user['newsletter_subscribed'] ?? false);
        $wantsSubscribed = $this->post('newsletter', '') === '1';

        $data = [
            'first_name' => trim($this->post('first_name', '')),
            'last_name' => trim($this->post('last_name', '')),
            'phone' => trim($this->post('phone', '')),
            'newsletter_subscribed' => $wantsSubscribed ? 1 : 0
        ];

        $this->userModel->updateProfile($userId, $data);

        // Handle newsletter subscription changes
        if ($wantsSubscribed && !$wasSubscribed) {
            // New subscription - add to newsletter and send welcome email
            $this->newsletterModel->subscribe($userEmail, $data['first_name'] ?: null, $userId, 'account');
        } elseif (!$wantsSubscribed && $wasSubscribed) {
            // Unsubscribe - find and unsubscribe
            $subscriber = $this->newsletterModel->findByEmail($userEmail);
            if ($subscriber && $subscriber['token']) {
                $this->newsletterModel->unsubscribe($subscriber['token']);
            }
        }

        // Update session name
        $_SESSION['user_name'] = $data['first_name'] ?: explode('@', auth()['email'])[0];

        setFlash('success', 'Profile updated successfully');
        $this->redirect('/account');
    }

    /**
     * Change password
     */
    public function changePassword(): void
    {
        $this->requireAuth();
        $this->requireValidCSRF();

        $currentPassword = $this->post('current_password', '');
        $newPassword = $this->post('new_password', '');
        $confirmPassword = $this->post('confirm_password', '');

        $user = $this->userModel->findById(auth()['id']);

        if (!$this->userModel->verifyPassword($currentPassword, $user['password_hash'])) {
            setFlash('error', 'Current password is incorrect');
            $this->redirect('/account');
            return;
        }

        if (strlen($newPassword) < 8) {
            setFlash('error', 'New password must be at least 8 characters');
            $this->redirect('/account');
            return;
        }

        if ($newPassword !== $confirmPassword) {
            setFlash('error', 'New passwords do not match');
            $this->redirect('/account');
            return;
        }

        $this->userModel->updatePassword(auth()['id'], $newPassword);

        setFlash('success', 'Password changed successfully');
        $this->redirect('/account');
    }

    /**
     * Require authentication
     */
    protected function requireAuth(): void
    {
        if (!auth()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            setFlash('error', 'Please login to access this page');
            $this->redirect('/login');
            exit;
        }
    }

    /**
     * Merge guest cart with user cart
     */
    private function mergeGuestCart(int $userId): void
    {
        $sessionId = session_id();
        $this->cartModel->mergeOnLogin($sessionId, $userId);
    }

    /**
     * Send welcome email to new user
     */
    private function sendWelcomeEmail(string $email, ?string $firstName): void
    {
        try {
            $storeName = appName();
            $storeUrl = appUrl();
            $name = $firstName ?: 'there';

            $subject = "Welcome to {$storeName}!";

            $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($subject) . '</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; background-color: #f7f7f7;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f7f7f7;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
                    <!-- Header -->
                    <tr>
                        <td align="center" style="padding: 40px 40px 20px;">
                            <h1 style="margin: 0; font-size: 28px; color: #333;">Welcome to ' . htmlspecialchars($storeName) . '!</h1>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding: 20px 40px;">
                            <p style="margin: 0 0 20px; font-size: 16px; line-height: 1.6; color: #555;">
                                Hi ' . htmlspecialchars($name) . ',
                            </p>
                            <p style="margin: 0 0 20px; font-size: 16px; line-height: 1.6; color: #555;">
                                Thank you for creating an account with us! We\'re excited to have you as part of our community.
                            </p>
                            <p style="margin: 0 0 20px; font-size: 16px; line-height: 1.6; color: #555;">
                                With your new account, you can:
                            </p>
                            <ul style="margin: 0 0 20px; padding-left: 20px; font-size: 16px; line-height: 1.8; color: #555;">
                                <li>Track your orders and view order history</li>
                                <li>Save your favorite items for later</li>
                                <li>Enjoy faster checkout with saved addresses</li>
                                <li>Receive exclusive offers and updates</li>
                            </ul>
                            <p style="margin: 0 0 30px; font-size: 16px; line-height: 1.6; color: #555;">
                                Start exploring our products and find something you\'ll love!
                            </p>
                            <!-- CTA Button -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <a href="' . $storeUrl . '/products" style="display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, var(--primary-color, #2186c4) 0%, var(--secondary-color, #83b1ec) 100%); background-color: #2186c4; color: #ffffff; text-decoration: none; border-radius: 50px; font-weight: 600; font-size: 16px;">Shop Now</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding: 30px 40px; border-top: 1px solid #eee;">
                            <p style="margin: 0; font-size: 14px; color: #999;">
                                If you have any questions, feel free to <a href="' . $storeUrl . '/contact" style="color: #2186c4; text-decoration: none;">contact us</a>.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';

            sendEmail($email, $subject, $html, ['html' => true]);
        } catch (\Exception $e) {
            // Log error but don't fail registration
            error_log("Failed to send welcome email: " . $e->getMessage());
        }
    }
}
