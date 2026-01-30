<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\StockNotification;

class StockNotificationController extends Controller
{
    private StockNotification $notificationModel;

    public function __construct()
    {
        parent::__construct();
        $this->notificationModel = new StockNotification();
    }

    /**
     * Subscribe to back-in-stock notification (AJAX)
     */
    public function subscribe(): void
    {
        $this->requireValidCSRF();

        $email = trim($this->post('email', ''));
        $productId = (int)$this->post('product_id', 0);
        $variantId = $this->post('variant_id') ? (int)$this->post('variant_id') : null;
        $variantName = trim($this->post('variant_name', ''));
        $subscribeNewsletter = (bool)$this->post('subscribe_newsletter', false);

        // Validate email
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['success' => false, 'message' => 'Please enter a valid email address']);
            return;
        }

        // Validate product
        if ($productId <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid product']);
            return;
        }

        // Subscribe to stock notification
        $result = $this->notificationModel->subscribe($email, $productId, $variantId, $variantName ?: null);

        // Also subscribe to newsletter if requested
        if ($subscribeNewsletter && $result['success']) {
            $this->subscribeToNewsletter($email);
        }

        $this->json($result);
    }

    /**
     * Subscribe email to newsletter
     */
    private function subscribeToNewsletter(string $email): void
    {
        $db = Database::getInstance();

        try {
            // Check if already subscribed
            $existing = $db->selectOne(
                "SELECT id, is_subscribed FROM newsletter_subscribers WHERE email = ?",
                [$email]
            );

            if (!$existing) {
                // New subscriber
                $token = bin2hex(random_bytes(32));
                $db->insert(
                    "INSERT INTO newsletter_subscribers (email, token, is_subscribed, source, subscribed_at) VALUES (?, ?, 1, 'stock_alert', NOW())",
                    [$email, $token]
                );
            } elseif (!$existing['is_subscribed']) {
                // Re-subscribe
                $db->update(
                    "UPDATE newsletter_subscribers SET is_subscribed = 1, subscribed_at = NOW(), unsubscribed_at = NULL WHERE id = ?",
                    [$existing['id']]
                );
            }
        } catch (\Exception $e) {
            // Don't fail the stock notification if newsletter subscription fails
            error_log("Newsletter subscription failed for {$email}: " . $e->getMessage());
        }
    }

    /**
     * Unsubscribe from notification (via email link)
     */
    public function unsubscribe(): void
    {
        $token = $this->get('token', '');
        $id = (int)$this->get('id', 0);

        if ($id <= 0) {
            setFlash('error', 'Invalid unsubscribe link');
            $this->redirect('/');
            return;
        }

        // Simple verification - in production you'd use a proper token
        $this->notificationModel->cancel($id);

        setFlash('success', 'You have been unsubscribed from this notification');
        $this->redirect('/');
    }

    /**
     * Check if user is already subscribed (AJAX)
     */
    public function check(): void
    {
        $email = trim($this->get('email', ''));
        $productId = (int)$this->get('product_id', 0);
        $variantId = $this->get('variant_id') ? (int)$this->get('variant_id') : null;

        if (empty($email) || $productId <= 0) {
            $this->json(['subscribed' => false]);
            return;
        }

        $db = Database::getInstance();
        $existing = $db->selectOne(
            "SELECT id FROM stock_notifications
             WHERE email = ? AND product_id = ? AND variant_id <=> ? AND status = 'pending'",
            [$email, $productId, $variantId]
        );

        $this->json(['subscribed' => (bool)$existing]);
    }
}
