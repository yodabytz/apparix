<?php

namespace App\Models;

use App\Core\Database;

class Newsletter
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Subscribe an email to the newsletter
     */
    public function subscribe(string $email, ?string $firstName = null, ?int $userId = null, string $source = 'website'): array
    {
        $email = strtolower(trim($email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email address'];
        }

        // Check if already subscribed
        $existing = $this->findByEmail($email);

        if ($existing) {
            if ($existing['is_subscribed']) {
                return ['success' => false, 'error' => 'This email is already subscribed'];
            }

            // Resubscribe
            $token = bin2hex(random_bytes(32));
            $this->db->update(
                "UPDATE newsletter_subscribers SET is_subscribed = 1, token = ?, first_name = COALESCE(?, first_name), user_id = COALESCE(?, user_id), subscribed_at = NOW(), unsubscribed_at = NULL WHERE id = ?",
                [$token, $firstName, $userId, $existing['id']]
            );

            return ['success' => true, 'message' => 'You have been resubscribed to our newsletter!', 'token' => $token];
        }

        // New subscription
        $token = bin2hex(random_bytes(32));

        $id = $this->db->insert(
            "INSERT INTO newsletter_subscribers (email, token, first_name, user_id, source) VALUES (?, ?, ?, ?, ?)",
            [$email, $token, $firstName, $userId, $source]
        );

        // Send welcome email
        $this->sendWelcomeEmail($email, $firstName, $token);

        return ['success' => true, 'message' => 'Thank you for subscribing!', 'id' => $id, 'token' => $token];
    }

    /**
     * Send welcome email to new subscriber
     */
    private function sendWelcomeEmail(string $email, ?string $firstName, string $token): bool
    {
        $name = $firstName ?: $this->extractNameFromEmail($email);
        $unsubscribeUrl = '' . appUrl() . '/newsletter/unsubscribe?token=' . $token;

        $subject = "Welcome to Apparix!";

        // Load template
        $templatePath = BASE_PATH . '/newsletter/templates/newsletter.html';
        if (file_exists($templatePath)) {
            $html = file_get_contents($templatePath);
            $content = '<h2>Hello!</h2>
<p>Thank you for subscribing to our newsletter!</p>
<p>Stay tuned for exclusive deals, new arrivals, and special offers just for you.</p>';
            $html = str_replace('{{CONTENT}}', $content, $html);
            $html = str_replace('{{SUBJECT}}', htmlspecialchars($subject), $html);
            $html = str_replace('{{UNSUBSCRIBE_URL}}', $unsubscribeUrl, $html);
            $html = str_replace('{{SUBSCRIBER_NAME}}', htmlspecialchars($name), $html);
            $html = str_replace('{{SOCIAL_LINKS}}', $this->generateSocialLinksHtml(), $html);
        } else {
            $html = $this->getBasicTemplate('<h2>Welcome!</h2><p>Thank you for subscribing to our newsletter!</p>', $unsubscribeUrl);
        }

        return sendEmail($email, $subject, $html, ['html' => true]);
    }

    /**
     * Unsubscribe from newsletter
     */
    public function unsubscribe(string $token): array
    {
        $subscriber = $this->findByToken($token);

        if (!$subscriber) {
            return ['success' => false, 'error' => 'Invalid unsubscribe link'];
        }

        if (!$subscriber['is_subscribed']) {
            return ['success' => true, 'message' => 'You are already unsubscribed'];
        }

        $this->db->update(
            "UPDATE newsletter_subscribers SET is_subscribed = 0, unsubscribed_at = NOW() WHERE id = ?",
            [$subscriber['id']]
        );

        return ['success' => true, 'message' => 'You have been unsubscribed from our newsletter'];
    }

    /**
     * Find subscriber by email
     */
    public function findByEmail(string $email): ?array
    {
        $result = $this->db->selectOne(
            "SELECT * FROM newsletter_subscribers WHERE email = ?",
            [strtolower(trim($email))]
        );
        return $result ?: null;
    }

    /**
     * Find subscriber by token
     */
    public function findByToken(string $token): ?array
    {
        $result = $this->db->selectOne(
            "SELECT * FROM newsletter_subscribers WHERE token = ?",
            [$token]
        );
        return $result ?: null;
    }

    /**
     * Get all active subscribers
     */
    public function getActiveSubscribers(): array
    {
        return $this->db->select(
            "SELECT ns.*, u.first_name as user_first_name
             FROM newsletter_subscribers ns
             LEFT JOIN users u ON ns.user_id = u.id
             WHERE ns.is_subscribed = 1
             ORDER BY ns.subscribed_at DESC"
        );
    }

    /**
     * Get all subscribers for admin
     */
    public function getAllSubscribers(int $limit = 50, int $offset = 0): array
    {
        return $this->db->select(
            "SELECT ns.*, u.email as user_email, u.first_name as user_first_name
             FROM newsletter_subscribers ns
             LEFT JOIN users u ON ns.user_id = u.id
             ORDER BY ns.subscribed_at DESC
             LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }

    /**
     * Delete a subscriber by ID
     */
    public function deleteSubscriber(int $id): bool
    {
        return $this->db->delete("DELETE FROM newsletter_subscribers WHERE id = ?", [$id]);
    }

    /**
     * Get subscriber by ID
     */
    public function getSubscriberById(int $id): ?array
    {
        $result = $this->db->selectOne(
            "SELECT * FROM newsletter_subscribers WHERE id = ?",
            [$id]
        );
        return $result ?: null;
    }

    /**
     * Count subscribers
     */
    public function countSubscribers(bool $activeOnly = false): int
    {
        $sql = "SELECT COUNT(*) as count FROM newsletter_subscribers";
        if ($activeOnly) {
            $sql .= " WHERE is_subscribed = 1";
        }
        $result = $this->db->selectOne($sql);
        return (int)($result['count'] ?? 0);
    }

    /**
     * Create a new newsletter
     */
    public function createNewsletter(string $subject, string $content, int $sentBy): int
    {
        return $this->db->insert(
            "INSERT INTO newsletters (subject, content, sent_by, recipient_count) VALUES (?, ?, ?, 0)",
            [$subject, $content, $sentBy]
        );
    }

    /**
     * Update newsletter recipient count
     */
    public function updateRecipientCount(int $newsletterId, int $count): void
    {
        $this->db->update(
            "UPDATE newsletters SET recipient_count = ? WHERE id = ?",
            [$count, $newsletterId]
        );
    }

    /**
     * Get all sent newsletters
     */
    public function getSentNewsletters(int $limit = 20): array
    {
        return $this->db->select(
            "SELECT n.*, a.name as sent_by_name
             FROM newsletters n
             LEFT JOIN admin_users a ON n.sent_by = a.id
             ORDER BY n.sent_at DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Get newsletter by ID
     */
    public function getNewsletterById(int $id): ?array
    {
        return $this->db->selectOne(
            "SELECT n.*, a.name as sent_by_name
             FROM newsletters n
             LEFT JOIN admin_users a ON n.sent_by = a.id
             WHERE n.id = ?",
            [$id]
        );
    }

    /**
     * Delete a newsletter
     */
    public function deleteNewsletter(int $id): bool
    {
        return $this->db->delete("DELETE FROM newsletters WHERE id = ?", [$id]);
    }

    /**
     * Send newsletter to all active subscribers
     */
    public function sendNewsletter(int $newsletterId): array
    {
        $newsletter = $this->getNewsletterById($newsletterId);

        if (!$newsletter) {
            return ['success' => false, 'error' => 'Newsletter not found'];
        }

        $subscribers = $this->getActiveSubscribers();
        $sent = 0;
        $failed = 0;

        foreach ($subscribers as $subscriber) {
            $result = $this->sendEmail($subscriber, $newsletter);
            if ($result) {
                $sent++;
            } else {
                $failed++;
            }
            usleep(100000); // 0.1 second delay between emails
        }

        $this->updateRecipientCount($newsletterId, $sent);

        return [
            'success' => true,
            'sent' => $sent,
            'failed' => $failed,
            'total' => count($subscribers)
        ];
    }

    /**
     * Get subscriber's display name with smart fallbacks
     * Priority: 1) Subscriber first_name, 2) Linked user's first_name, 3) Extract from email
     */
    private function getSubscriberName(array $subscriber): string
    {
        // 1. Check if subscriber has a first_name directly (manually added)
        if (!empty($subscriber['first_name'])) {
            return $subscriber['first_name'];
        }

        // 2. Check if linked user account has a first_name (from purchases)
        if (!empty($subscriber['user_first_name'])) {
            return $subscriber['user_first_name'];
        }

        // 3. Extract name from email address
        if (!empty($subscriber['email'])) {
            $emailName = $this->extractNameFromEmail($subscriber['email']);
            if ($emailName) {
                return $emailName;
            }
        }

        // 4. Final fallback
        return 'there';
    }

    /**
     * Extract a usable name from an email address
     * e.g., sarah.jones@gmail.com → Sarah, john_smith@yahoo.com → John
     */
    private function extractNameFromEmail(string $email): ?string
    {
        // Get the part before @
        $localPart = explode('@', $email)[0] ?? '';

        if (empty($localPart)) {
            return null;
        }

        // Split by common separators (., _, -)
        $parts = preg_split('/[._\-]/', $localPart);
        $namePart = $parts[0] ?? '';

        // Remove trailing numbers only (keep the name readable)
        $namePart = preg_replace('/[0-9]+$/', '', $namePart);

        // Must be at least 2 characters
        if (strlen($namePart) < 2) {
            // Fall back to full local part if first segment is too short
            $namePart = preg_replace('/[0-9]+$/', '', $localPart);
            if (strlen($namePart) < 2) {
                return null;
            }
        }

        // Capitalize first letter
        return ucfirst(strtolower($namePart));
    }

    /**
     * Send email to a subscriber
     */
    private function sendEmail(array $subscriber, array $newsletter): bool
    {
        $unsubscribeUrl = '' . appUrl() . '/newsletter/unsubscribe?token=' . $subscriber['token'];

        // Get subscriber's name with smart fallbacks
        $subscriberName = $this->getSubscriberName($subscriber);

        // Load template
        $templatePath = BASE_PATH . '/newsletter/templates/newsletter.html';
        if (!file_exists($templatePath)) {
            // Fallback to basic HTML
            $html = $this->getBasicTemplate($newsletter['content'], $unsubscribeUrl);
        } else {
            $html = file_get_contents($templatePath);
            $html = str_replace('{{CONTENT}}', $newsletter['content'], $html);
            $html = str_replace('{{SUBJECT}}', htmlspecialchars($newsletter['subject']), $html);
            $html = str_replace('{{UNSUBSCRIBE_URL}}', $unsubscribeUrl, $html);
            $html = str_replace('{{SUBSCRIBER_NAME}}', htmlspecialchars($subscriberName), $html);
            $html = str_replace('{{SOCIAL_LINKS}}', $this->generateSocialLinksHtml(), $html);
        }

        return sendEmail($subscriber['email'], $newsletter['subject'], $html, ['html' => true]);
    }

    /**
     * Get basic email template
     */
    private function getBasicTemplate(string $content, string $unsubscribeUrl): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="text-align: center; margin-bottom: 20px;">
        <img src="' . appUrl() . '/assets/images/placeholder.png" alt="' . appName() . '" style="max-width: 200px;">
    </div>
    <div style="padding: 20px; background: #fff; border-radius: 8px;">
        ' . $content . '
    </div>
    <div style="text-align: center; margin-top: 20px; font-size: 12px; color: #666;">
        <p>You received this email because you subscribed to our newsletter.</p>
        <p><a href="' . $unsubscribeUrl . '" style="color: #FF68C5;">Unsubscribe</a></p>
    </div>
</body>
</html>';
    }

    /**
     * Generate social links HTML for email templates
     */
    private function generateSocialLinksHtml(): string
    {
        $links = [];
        $socialPlatforms = [
            'social_facebook' => 'Facebook',
            'social_instagram' => 'Instagram',
            'social_twitter' => 'X',
            'social_tiktok' => 'TikTok',
            'social_youtube' => 'YouTube',
            'social_etsy' => 'Etsy',
            'social_pinterest' => 'Pinterest',
            'social_linkedin' => 'LinkedIn',
        ];

        foreach ($socialPlatforms as $key => $label) {
            $url = setting($key);
            if (!empty($url)) {
                $links[] = '<td style="padding: 0 12px;"><a href="' . htmlspecialchars($url) . '" style="color: #FF68C5; font-size: 14px; text-decoration: none; font-weight: 500;">' . $label . '</a></td>';
            }
        }

        if (empty($links)) {
            return '';
        }

        // Join with bullet separators
        $separator = '<td style="color: #fce7f3; font-size: 14px;">•</td>';
        $linksHtml = implode($separator, $links);

        return '<table role="presentation" cellpadding="0" cellspacing="0" style="margin-bottom: 20px;"><tr>' . $linksHtml . '</tr></table>';
    }
}
