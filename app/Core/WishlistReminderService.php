<?php

namespace App\Core;

use App\Core\Database;

/**
 * Service to send reminder emails for items in user wishlists (favorites)
 */
class WishlistReminderService
{
    private Database $db;
    private string $siteName;
    private string $siteUrl;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->siteName = appName();
        $this->siteUrl = appUrl();
    }

    /**
     * Process and send wishlist reminder emails
     * Should be called via cron job (e.g., daily)
     */
    public function processReminders(): array
    {
        $results = [
            'processed' => 0,
            'sent' => 0,
            'errors' => []
        ];

        // Get users with favorites older than 7 days that haven't received a reminder
        // Only for logged-in users (have user_id)
        $usersWithFavorites = $this->db->select(
            "SELECT DISTINCT u.id, u.email, u.first_name, u.last_name
             FROM users u
             INNER JOIN favorites f ON f.user_id = u.id
             INNER JOIN products p ON f.product_id = p.id AND p.is_active = 1
             WHERE f.created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
               AND f.reminder_sent_at IS NULL
               AND u.email IS NOT NULL
               AND u.email != ''
             GROUP BY u.id
             HAVING COUNT(f.id) > 0"
        );

        foreach ($usersWithFavorites as $user) {
            $results['processed']++;

            try {
                // Get their favorite items that need reminders
                $favorites = $this->db->select(
                    "SELECT f.id as favorite_id, f.product_id, f.created_at,
                            p.name, p.slug, p.price, p.sale_price,
                            (SELECT pi.image_path FROM product_images pi
                             WHERE pi.product_id = p.id
                             ORDER BY pi.sort_order ASC, pi.id ASC LIMIT 1) as image
                     FROM favorites f
                     INNER JOIN products p ON f.product_id = p.id
                     WHERE f.user_id = ?
                       AND f.created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
                       AND f.reminder_sent_at IS NULL
                       AND p.is_active = 1
                     ORDER BY f.created_at DESC
                     LIMIT 6",
                    [$user['id']]
                );

                if (empty($favorites)) {
                    continue;
                }

                // Send reminder email
                $sent = $this->sendReminderEmail($user, $favorites);

                if ($sent) {
                    $results['sent']++;

                    // Mark these favorites as reminded
                    $favoriteIds = array_column($favorites, 'favorite_id');
                    $placeholders = implode(',', array_fill(0, count($favoriteIds), '?'));
                    $this->db->update(
                        "UPDATE favorites SET reminder_sent_at = NOW() WHERE id IN ($placeholders)",
                        $favoriteIds
                    );
                }
            } catch (\Exception $e) {
                $results['errors'][] = "User {$user['id']}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Send reminder email to user
     */
    private function sendReminderEmail(array $user, array $favorites): bool
    {
        $firstName = $user['first_name'] ?: 'Friend';
        $itemCount = count($favorites);

        $subject = "Still thinking about " . ($itemCount === 1
            ? $favorites[0]['name']
            : "your wishlist items") . "? They're waiting for you!";

        // Build product HTML
        $productsHtml = '';
        foreach ($favorites as $item) {
            $price = $item['sale_price'] ?: $item['price'];
            $imageUrl = $this->siteUrl . ($item['image'] ?: '/assets/images/placeholder.png');
            $productUrl = $this->siteUrl . '/products/' . $item['slug'];

            $productsHtml .= "
            <tr>
                <td style='padding: 15px;'>
                    <table cellpadding='0' cellspacing='0' border='0' width='100%'>
                        <tr>
                            <td width='100' style='padding-right: 15px;'>
                                <a href='{$productUrl}'>
                                    <img src='{$imageUrl}' alt='" . htmlspecialchars($item['name']) . "'
                                         width='100' height='100' style='border-radius: 8px; object-fit: cover;'>
                                </a>
                            </td>
                            <td style='vertical-align: middle;'>
                                <a href='{$productUrl}' style='color: #1f2937; text-decoration: none; font-weight: 600;'>
                                    " . htmlspecialchars($item['name']) . "
                                </a>
                                <p style='margin: 8px 0 0; color: #FF68C5; font-weight: 700; font-size: 16px;'>
                                    $" . number_format($price, 2) . "
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>";
        }

        $htmlBody = $this->getEmailTemplate($firstName, $productsHtml);
        $textBody = $this->getTextEmail($firstName, $favorites);

        return $this->sendEmailMessage($user['email'], $subject, $htmlBody, $textBody);
    }

    /**
     * Get HTML email template
     */
    private function getEmailTemplate(string $firstName, string $productsHtml): string
    {
        return "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
</head>
<body style='margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif; background-color: #1a1a2e;'>
    <table cellpadding='0' cellspacing='0' border='0' width='100%' style='background-color: #1a1a2e; padding: 40px 20px;'>
        <tr>
            <td align='center'>
                <table cellpadding='0' cellspacing='0' border='0' width='600' style='max-width: 600px; background-color: #ffffff; border-radius: 16px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);'>
                    <!-- Header -->
                    <tr>
                        <td style='padding: 30px; text-align: center; background: linear-gradient(135deg, #FF68C5, #ff8ad4); border-radius: 16px 16px 0 0;'>
                            <img src='{$this->siteUrl}/assets/images/placeholder.png' alt=\"Apparix\" width='180'>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style='padding: 30px;'>
                            <h1 style='margin: 0 0 20px; font-size: 24px; color: #1f2937; text-align: center;'>
                                Hey {$firstName}, don't forget about these!
                            </h1>

                            <p style='color: #4b5563; font-size: 16px; line-height: 1.6; text-align: center; margin-bottom: 25px;'>
                                You saved these items to your wishlist a while ago. They're still here waiting for you!
                            </p>

                            <!-- Products -->
                            <table cellpadding='0' cellspacing='0' border='0' width='100%' style='background-color: #f9fafb; border-radius: 12px; margin-bottom: 25px;'>
                                {$productsHtml}
                            </table>

                            <!-- CTA Button -->
                            <table cellpadding='0' cellspacing='0' border='0' width='100%'>
                                <tr>
                                    <td align='center'>
                                        <a href='{$this->siteUrl}/favorites'
                                           style='display: inline-block; background: linear-gradient(135deg, #FF68C5, #ff8ad4); color: white; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: 600; font-size: 16px;'>
                                            View My Wishlist
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style='padding: 25px; text-align: center; background-color: #f9fafb; border-radius: 0 0 16px 16px;'>
                            <p style='margin: 0 0 10px; color: #6b7280; font-size: 14px;'>
                                Have questions? Reply to this email - we'd love to hear from you!
                            </p>
                            <p style='margin: 0; color: #9ca3af; font-size: 12px;'>
                                © " . date('Y') . " {$this->siteName}. All rights reserved.<br>
                                <a href='{$this->siteUrl}/unsubscribe' style='color: #9ca3af;'>Unsubscribe</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>";
    }

    /**
     * Get plain text email
     */
    private function getTextEmail(string $firstName, array $favorites): string
    {
        $text = "Hey {$firstName},\n\n";
        $text .= "You saved these items to your wishlist a while ago - they're still waiting for you!\n\n";

        foreach ($favorites as $item) {
            $price = $item['sale_price'] ?: $item['price'];
            $text .= "- " . $item['name'] . " - $" . number_format($price, 2) . "\n";
            $text .= "  {$this->siteUrl}/products/{$item['slug']}\n\n";
        }

        $text .= "View your full wishlist: {$this->siteUrl}/favorites\n\n";
        $text .= "Questions? Just reply to this email!\n\n";
        $text .= "- {$this->siteName}";

        return $text;
    }

    /**
     * Send email using PHP mail()
     */
    private function sendEmailMessage(string $to, string $subject, string $htmlBody, string $textBody): bool
    {
        // Use the centralized sendEmail helper which supports SMTP if configured
        return sendEmail($to, $subject, $htmlBody, ['html' => true]);
    }
}
