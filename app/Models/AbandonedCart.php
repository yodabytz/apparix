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
     * Get abandoned carts (not updated in X hours, has items, has email)
     */
    public function getAbandonedCarts(int $hoursOld = 2, int $maxAge = 72): array
    {
        return $this->db->select(
            "SELECT c.*, u.email as user_email, u.first_name,
                    (SELECT COUNT(*) FROM cart_items ci WHERE ci.cart_id = c.id) as item_count,
                    (SELECT SUM(ci.price * ci.quantity) FROM cart_items ci WHERE ci.cart_id = c.id) as cart_total
             FROM carts c
             LEFT JOIN users u ON c.user_id = u.id
             WHERE c.updated_at < DATE_SUB(NOW(), INTERVAL ? HOUR)
               AND c.updated_at > DATE_SUB(NOW(), INTERVAL ? HOUR)
               AND c.abandoned_email_sent = 0
               AND c.recovered = 0
               AND (c.email IS NOT NULL OR u.email IS NOT NULL)
               AND EXISTS (SELECT 1 FROM cart_items ci WHERE ci.cart_id = c.id)
             ORDER BY c.updated_at DESC",
            [$hoursOld, $maxAge]
        );
    }

    /**
     * Get cart items with product details
     */
    public function getCartItems(int $cartId): array
    {
        return $this->db->select(
            "SELECT ci.*, p.name, p.slug, p.price as current_price,
                    (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, sort_order LIMIT 1) as image,
                    pv.name as variant_name
             FROM cart_items ci
             JOIN products p ON ci.product_id = p.id
             LEFT JOIN product_variants pv ON ci.variant_id = pv.id
             WHERE ci.cart_id = ?",
            [$cartId]
        );
    }

    /**
     * Mark cart as email sent
     */
    public function markEmailSent(int $cartId): void
    {
        $this->db->update(
            "UPDATE carts SET abandoned_email_sent = 1, abandoned_email_sent_at = NOW() WHERE id = ?",
            [$cartId]
        );
    }

    /**
     * Mark cart as recovered
     */
    public function markRecovered(int $cartId): void
    {
        $this->db->update(
            "UPDATE carts SET recovered = 1 WHERE id = ?",
            [$cartId]
        );
    }

    /**
     * Update cart email
     */
    public function updateCartEmail(int $cartId, string $email): void
    {
        $this->db->update(
            "UPDATE carts SET email = ? WHERE id = ?",
            [$email, $cartId]
        );
    }

    /**
     * Get cart by session ID
     */
    public function getCartBySession(string $sessionId): ?array
    {
        $result = $this->db->selectOne(
            "SELECT * FROM carts WHERE session_id = ?",
            [$sessionId]
        );
        return $result ?: null;
    }

    /**
     * Send abandoned cart email
     */
    public function sendAbandonedCartEmail(array $cart, array $items): bool
    {
        $email = $cart['email'] ?? $cart['user_email'];
        if (!$email) return false;

        $firstName = $cart['first_name'] ?? $this->extractNameFromEmail($email);
        $cartTotal = number_format($cart['cart_total'], 2);

        // Build items HTML
        $itemsHtml = '';
        foreach ($items as $item) {
            $imageUrl = $item['image'] ?? '' . appUrl() . '/assets/images/placeholder.png';
            $variantText = $item['variant_name'] ? " - {$item['variant_name']}" : '';
            $itemsHtml .= "
            <tr>
                <td style='padding: 15px; border-bottom: 1px solid #f3e8f1;'>
                    <table role='presentation' width='100%' cellpadding='0' cellspacing='0'>
                        <tr>
                            <td width='80' style='padding-right: 15px;'>
                                <img src='{$imageUrl}' alt='' width='80' height='80' style='border-radius: 8px; object-fit: cover;'>
                            </td>
                            <td style='vertical-align: top;'>
                                <p style='margin: 0 0 5px 0; font-weight: 600; color: #1f2937;'>{$item['name']}{$variantText}</p>
                                <p style='margin: 0; color: #6b7280;'>Qty: {$item['quantity']} × \${$item['price']}</p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>";
        }

        $recoveryUrl = '' . appUrl() . '/cart?recover=' . urlencode($cart['session_id']);

        $html = $this->getEmailTemplate($firstName, $itemsHtml, $cartTotal, $recoveryUrl);

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
    private function getEmailTemplate(string $name, string $itemsHtml, string $total, string $recoveryUrl): string
    {
        return "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
</head>
<body style='margin: 0; padding: 0; background-color: #1a1a2e; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;'>
    <table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='background-color: #1a1a2e;'>
        <tr>
            <td align='center' style='padding: 40px 20px;'>
                <table role='presentation' width='600' cellpadding='0' cellspacing='0' style='max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 16px; overflow: hidden;'>

                    <!-- Header -->
                    <tr>
                        <td align='center' style='background-color: #FFF5FA; padding: 30px 40px;'>
                            <img src='' . appUrl() . '/assets/images/placeholder.png' alt='' . appName() . '' width='200' style='max-width: 200px;'>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style='padding: 40px;'>
                            <h1 style='margin: 0 0 20px 0; font-size: 24px; color: #1f2937;'>Hi {$name}!</h1>
                            <p style='margin: 0 0 25px 0; font-size: 16px; color: #4b5563; line-height: 1.6;'>
                                You left some beautiful items in your cart! We saved them for you, but they won't be around forever.
                            </p>

                            <!-- Cart Items -->
                            <table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='margin-bottom: 25px; background: #fdf2f8; border-radius: 12px;'>
                                {$itemsHtml}
                                <tr>
                                    <td style='padding: 15px; text-align: right;'>
                                        <strong style='font-size: 18px; color: #1f2937;'>Total: \${$total}</strong>
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA Button -->
                            <table role='presentation' width='100%' cellpadding='0' cellspacing='0'>
                                <tr>
                                    <td align='center'>
                                        <a href='{$recoveryUrl}' style='display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #FF68C5, #ff4db8); color: #ffffff !important; text-decoration: none; border-radius: 50px; font-weight: 600; font-size: 16px;'>
                                            Complete My Order
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style='background-color: #1a1a2e; padding: 25px 40px; text-align: center;'>
                            <p style='margin: 0; font-size: 13px; color: #9ca3af;'>
                                Questions? Reply to this email or contact us at ' . storeEmail() . '
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
        $total = $this->db->selectOne("SELECT COUNT(*) as count FROM carts WHERE EXISTS (SELECT 1 FROM cart_items ci WHERE ci.cart_id = carts.id)")['count'] ?? 0;
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
