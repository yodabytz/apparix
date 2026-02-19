<?php

namespace App\Models;

use App\Core\Database;

class AbandonedCart
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get abandoned carts from the active cart table
     * Groups cart rows by session_id, filters for email present, updated 2-72 hours ago
     */
    public function getAbandonedCarts(int $hoursOld = 2, int $maxAge = 72): array
    {
        return $this->db->select(
            "SELECT c.session_id,
                    c.email,
                    c.user_id,
                    MAX(c.updated_at) as last_updated,
                    COUNT(*) as item_count,
                    SUM(
                        COALESCE(
                            (SELECT p.price + COALESCE(pv.price_adjustment, 0)
                             FROM products p
                             LEFT JOIN product_variants pv ON pv.id = c.variant_id
                             WHERE p.id = c.product_id),
                            0
                        ) * c.quantity
                    ) as cart_total
             FROM cart c
             WHERE c.email IS NOT NULL
               AND c.email != ''
               AND c.updated_at < DATE_SUB(NOW(), INTERVAL ? HOUR)
               AND c.updated_at > DATE_SUB(NOW(), INTERVAL ? HOUR)
               AND NOT EXISTS (
                   SELECT 1 FROM carts t
                   WHERE t.session_id = c.session_id
                   AND (t.abandoned_email_sent = 1 OR t.recovered = 1)
               )
             GROUP BY c.session_id, c.email, c.user_id
             ORDER BY last_updated DESC",
            [$hoursOld, $maxAge]
        );
    }

    /**
     * Get cart items with product details by session_id
     */
    public function getCartItems(string $sessionId): array
    {
        return $this->db->select(
            "SELECT c.id, c.product_id, c.variant_id, c.quantity,
                    p.name, p.slug, p.price,
                    COALESCE(pv.price_adjustment, 0) as price_adjustment,
                    (p.price + COALESCE(pv.price_adjustment, 0)) as item_price,
                    pv.name as variant_name,
                    (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, sort_order LIMIT 1) as image
             FROM cart c
             JOIN products p ON c.product_id = p.id
             LEFT JOIN product_variants pv ON pv.id = c.variant_id
             WHERE c.session_id = ?",
            [$sessionId]
        );
    }

    /**
     * Mark email as sent for a session (inserts/updates tracking record in carts table)
     */
    public function markEmailSent(string $sessionId, string $email): void
    {
        $existing = $this->db->selectOne(
            "SELECT id FROM carts WHERE session_id = ?",
            [$sessionId]
        );

        if ($existing) {
            $this->db->update(
                "UPDATE carts SET abandoned_email_sent = 1, abandoned_email_sent_at = NOW(), email = ? WHERE session_id = ?",
                [$email, $sessionId]
            );
        } else {
            $this->db->insert(
                "INSERT INTO carts (session_id, email, abandoned_email_sent, abandoned_email_sent_at) VALUES (?, ?, 1, NOW())",
                [$sessionId, $email]
            );
        }
    }

    /**
     * Mark cart as recovered
     */
    public function markRecovered(string $sessionId): void
    {
        $existing = $this->db->selectOne(
            "SELECT id FROM carts WHERE session_id = ?",
            [$sessionId]
        );

        if ($existing) {
            $this->db->update(
                "UPDATE carts SET recovered = 1 WHERE session_id = ?",
                [$sessionId]
            );
        }
    }

    /**
     * Stamp email on all cart rows for a session
     */
    public function captureEmail(string $sessionId, string $email): void
    {
        $this->db->update(
            "UPDATE cart SET email = ? WHERE session_id = ?",
            [$email, $sessionId]
        );

        // Also update or create tracking record
        $existing = $this->db->selectOne(
            "SELECT id FROM carts WHERE session_id = ?",
            [$sessionId]
        );

        if ($existing) {
            $this->db->update(
                "UPDATE carts SET email = ? WHERE session_id = ?",
                [$email, $sessionId]
            );
        } else {
            $this->db->insert(
                "INSERT INTO carts (session_id, email) VALUES (?, ?)",
                [$sessionId, $email]
            );
        }
    }

    /**
     * Send abandoned cart email
     */
    public function sendAbandonedCartEmail(array $cart, array $items): bool
    {
        $email = $cart['email'];
        if (!$email) return false;

        $firstName = $this->extractNameFromEmail($email);

        // Look up user name if we have user_id
        if (!empty($cart['user_id'])) {
            $user = $this->db->selectOne("SELECT first_name FROM users WHERE id = ?", [$cart['user_id']]);
            if ($user && !empty($user['first_name'])) {
                $firstName = $user['first_name'];
            }
        }

        $cartTotal = number_format($cart['cart_total'], 2);
        $appUrl = appUrl();

        // Build items HTML
        $itemsHtml = '';
        foreach ($items as $item) {
            $imageUrl = $item['image'] ?? '';
            // Prepend domain for relative paths
            if ($imageUrl && strpos($imageUrl, 'http') !== 0) {
                $imageUrl = $appUrl . '/' . ltrim($imageUrl, '/');
            }
            if (!$imageUrl) {
                $imageUrl = $appUrl . '/assets/images/placeholder.png';
            }

            $itemName = htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8');
            $variantText = !empty($item['variant_name']) ? ' - ' . htmlspecialchars($item['variant_name'], ENT_QUOTES, 'UTF-8') : '';
            $itemPrice = number_format($item['item_price'], 2);
            $imageUrl = htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8');

            $itemsHtml .= "
            <tr>
                <td style='padding: 15px; border-bottom: 1px solid #f3e8f1;'>
                    <table role='presentation' width='100%' cellpadding='0' cellspacing='0'>
                        <tr>
                            <td width='80' style='padding-right: 15px;'>
                                <img src='{$imageUrl}' alt='' width='80' height='80' style='border-radius: 8px; object-fit: cover;'>
                            </td>
                            <td style='vertical-align: top;'>
                                <p style='margin: 0 0 5px 0; font-weight: 600; color: #1f2937;'>{$itemName}{$variantText}</p>
                                <p style='margin: 0; color: #6b7280;'>Qty: {$item['quantity']} &times; \${$itemPrice}</p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>";
        }

        $sig = hash_hmac('sha256', $cart['session_id'], $_ENV['APP_SECRET'] ?? '');
        $recoveryUrl = $appUrl . '/cart?recover=' . urlencode($cart['session_id']) . '&sig=' . $sig;
        $storeName = htmlspecialchars(appName(), ENT_QUOTES, 'UTF-8');
        $storeLogo = storeLogo() ?: '/assets/images/apparix-logo.png';
        if (strpos($storeLogo, 'http') !== 0) {
            $storeLogo = $appUrl . '/' . ltrim($storeLogo, '/');
        }

        $html = $this->getEmailTemplate($firstName, $itemsHtml, $cartTotal, $recoveryUrl, $storeName, $storeLogo);

        return sendEmail($email, "You left something behind!", $html, ['html' => true]);
    }

    /**
     * Extract name from email
     */
    private function extractNameFromEmail(string $email): string
    {
        $localPart = explode('@', $email)[0] ?? '';
        $parts = preg_split('/[._\-]/', $localPart);
        $name = preg_replace('/[0-9]+$/', '', $parts[0] ?? '');
        return strlen($name) >= 2 ? ucfirst(strtolower($name)) : 'there';
    }

    /**
     * Get email template
     */
    private function getEmailTemplate(string $name, string $itemsHtml, string $total, string $recoveryUrl, string $storeName, string $storeLogo): string
    {
        $storeEmail = storeEmail();
        return "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
</head>
<body style='margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;'>
    <table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='background-color: #f3f4f6;'>
        <tr>
            <td align='center' style='padding: 40px 20px;'>
                <table role='presentation' width='600' cellpadding='0' cellspacing='0' style='max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08);'>
                    <tr>
                        <td align='center' style='background-color: #f8fafc; padding: 30px 40px;'>
                            <img src='{$storeLogo}' alt='{$storeName}' width='180' style='max-width: 180px;'>
                        </td>
                    </tr>
                    <tr>
                        <td style='padding: 40px;'>
                            <h1 style='margin: 0 0 20px 0; font-size: 24px; color: #1f2937;'>Hi {$name}!</h1>
                            <p style='margin: 0 0 25px 0; font-size: 16px; color: #4b5563; line-height: 1.6;'>
                                You left some items in your cart! We saved them for you, but they won't be around forever.
                            </p>
                            <table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='margin-bottom: 25px; background: #f9fafb; border-radius: 12px;'>
                                {$itemsHtml}
                                <tr>
                                    <td style='padding: 15px; text-align: right;'>
                                        <strong style='font-size: 18px; color: #1f2937;'>Total: \${$total}</strong>
                                    </td>
                                </tr>
                            </table>
                            <table role='presentation' width='100%' cellpadding='0' cellspacing='0'>
                                <tr>
                                    <td align='center'>
                                        <a href='{$recoveryUrl}' style='display: inline-block; padding: 16px 40px; background: #2563eb; color: #ffffff !important; text-decoration: none; border-radius: 50px; font-weight: 600; font-size: 16px;'>
                                            Complete My Order
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style='background-color: #1e293b; padding: 25px 40px; text-align: center;'>
                            <p style='margin: 0; font-size: 13px; color: #9ca3af;'>
                                Questions? Contact us at {$storeEmail}
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
     * Get stats for admin
     */
    public function getStats(): array
    {
        $total = $this->db->selectOne("SELECT COUNT(DISTINCT session_id) as count FROM cart WHERE email IS NOT NULL AND email != ''")['count'] ?? 0;
        $abandoned = $this->db->selectOne("SELECT COUNT(*) as count FROM carts WHERE abandoned_email_sent = 1")['count'] ?? 0;
        $recovered = $this->db->selectOne("SELECT COUNT(*) as count FROM carts WHERE recovered = 1")['count'] ?? 0;

        return [
            'total_carts' => $total,
            'emails_sent' => $abandoned,
            'recovered' => $recovered,
            'recovery_rate' => $abandoned > 0 ? round(($recovered / $abandoned) * 100, 1) : 0
        ];
    }
}
