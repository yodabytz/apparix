<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\PopupCoupon;

class PopupCouponController extends Controller
{
    private PopupCoupon $popupCoupon;

    public function __construct()
    {
        parent::__construct();
        $this->popupCoupon = new PopupCoupon();
    }

    /**
     * Generate a popup coupon for email
     */
    public function generate(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            return;
        }

        $email = trim($_POST['email'] ?? '');

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => 'Please enter a valid email address']);
            return;
        }

        try {
            // Generate coupon - 10% off, valid for 48 hours, no minimum
            $result = $this->popupCoupon->generate($email, 10, 48, 0);

            if ($result['success']) {
                // Also add to newsletter subscribers
                $this->addToNewsletter($email);

                $coupon = $result['coupon'];
                $expiresIn = $this->formatTimeRemaining($coupon['expires_at']);

                echo json_encode([
                    'success' => true,
                    'code' => $coupon['code'],
                    'discount' => $coupon['discount_percent'] . '%',
                    'expires_in' => $expiresIn,
                    'existing' => $result['existing'] ?? false,
                    'message' => $result['existing']
                        ? 'Welcome back! Here\'s your exclusive code again.'
                        : 'Your exclusive discount code has been created!'
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to generate coupon']);
            }
        } catch (\Exception $e) {
            error_log('Popup coupon error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'An error occurred. Please try again.']);
        }
    }

    /**
     * Add email to newsletter subscribers
     */
    private function addToNewsletter(string $email): void
    {
        try {
            $db = Database::getInstance();
            $email = strtolower(trim($email));

            // Check if already subscribed
            $existing = $db->selectOne(
                "SELECT id, is_subscribed FROM newsletter_subscribers WHERE email = ?",
                [$email]
            );

            if ($existing) {
                // If they unsubscribed before, resubscribe them
                if (!$existing['is_subscribed']) {
                    $db->update(
                        "UPDATE newsletter_subscribers SET is_subscribed = 1, subscribed_at = NOW(), source = 'exit_popup' WHERE id = ?",
                        [$existing['id']]
                    );
                }
            } else {
                // New subscriber
                $db->insert(
                    "INSERT INTO newsletter_subscribers (email, source, is_subscribed) VALUES (?, 'exit_popup', 1)",
                    [$email]
                );
            }
        } catch (\Exception $e) {
            // Log but don't fail the coupon generation
            error_log('Newsletter subscription error: ' . $e->getMessage());
        }
    }

    /**
     * Validate a popup coupon
     */
    public function validate(): void
    {
        header('Content-Type: application/json');

        $code = trim($_POST['code'] ?? $_GET['code'] ?? '');

        if (!$code) {
            echo json_encode(['valid' => false, 'error' => 'No code provided']);
            return;
        }

        $result = $this->popupCoupon->validate($code);

        if ($result['valid']) {
            echo json_encode([
                'valid' => true,
                'discount_percent' => $result['coupon']['discount_percent'],
                'min_order' => $result['coupon']['min_order']
            ]);
        } else {
            echo json_encode([
                'valid' => false,
                'error' => $result['error']
            ]);
        }
    }

    /**
     * Format time remaining
     */
    private function formatTimeRemaining(string $expiresAt): string
    {
        $expires = strtotime($expiresAt);
        $now = time();
        $diff = $expires - $now;

        if ($diff <= 0) {
            return 'expired';
        }

        $hours = floor($diff / 3600);
        $minutes = floor(($diff % 3600) / 60);

        if ($hours >= 24) {
            $days = floor($hours / 24);
            $hours = $hours % 24;
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ' . $hours . ' hour' . ($hours !== 1 ? 's' : '');
        }

        if ($hours > 0) {
            return $hours . ' hour' . ($hours !== 1 ? 's' : '') . ' ' . $minutes . ' minute' . ($minutes !== 1 ? 's' : '');
        }

        return $minutes . ' minute' . ($minutes !== 1 ? 's' : '');
    }
}
