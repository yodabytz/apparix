<?php

namespace App\Models;

use App\Core\Database;

class GiftCard
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Generate unique gift card code
     */
    public function generateCode(): string
    {
        do {
            $code = 'GC-' . strtoupper(substr(bin2hex(random_bytes(6)), 0, 12));
        } while ($this->findByCode($code));
        return $code;
    }

    /**
     * Create a new gift card
     */
    public function create(array $data): array
    {
        $code = $data['code'] ?? $this->generateCode();
        $amount = $data['amount'];
        $expiresAt = $data['expires_at'] ?? date('Y-m-d', strtotime('+1 year'));

        $id = $this->db->insert(
            "INSERT INTO gift_cards (code, initial_balance, current_balance, purchaser_email, recipient_email, recipient_name, personal_message, order_id, expires_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $code,
                $amount,
                $amount,
                $data['purchaser_email'] ?? null,
                $data['recipient_email'] ?? null,
                $data['recipient_name'] ?? null,
                $data['message'] ?? null,
                $data['order_id'] ?? null,
                $expiresAt
            ]
        );

        return [
            'success' => true,
            'gift_card' => [
                'id' => $id,
                'code' => $code,
                'amount' => $amount,
                'recipient_email' => $data['recipient_email'] ?? null,
                'recipient_name' => $data['recipient_name'] ?? null,
                'expires_at' => $expiresAt
            ]
        ];
    }

    /**
     * Find gift card by code
     */
    public function findByCode(string $code): ?array
    {
        $result = $this->db->selectOne(
            "SELECT * FROM gift_cards WHERE code = ? AND is_active = 1",
            [strtoupper(trim($code))]
        );
        return $result ?: null;
    }

    /**
     * Get gift card by ID
     */
    public function findById(int $id): ?array
    {
        $result = $this->db->selectOne("SELECT * FROM gift_cards WHERE id = ?", [$id]);
        return $result ?: null;
    }

    /**
     * Check if gift card is valid and has balance
     */
    public function isValid(string $code): array
    {
        $card = $this->findByCode($code);

        if (!$card) {
            return ['valid' => false, 'error' => 'Gift card not found'];
        }

        if (!$card['is_active']) {
            return ['valid' => false, 'error' => 'Gift card is no longer active'];
        }

        if ($card['expires_at'] && strtotime($card['expires_at']) < time()) {
            return ['valid' => false, 'error' => 'Gift card has expired'];
        }

        if ($card['current_balance'] <= 0) {
            return ['valid' => false, 'error' => 'Gift card has no remaining balance'];
        }

        return ['valid' => true, 'card' => $card];
    }

    /**
     * Redeem gift card (deduct balance)
     */
    public function redeem(int $cardId, float $amount, int $orderId): bool
    {
        $card = $this->findById($cardId);
        if (!$card || $card['current_balance'] < $amount) {
            return false;
        }

        $newBalance = $card['current_balance'] - $amount;

        $this->db->update(
            "UPDATE gift_cards SET current_balance = ? WHERE id = ?",
            [$newBalance, $cardId]
        );

        // Log transaction
        $this->db->insert(
            "INSERT INTO gift_card_transactions (gift_card_id, order_id, amount, balance_after, transaction_type)
             VALUES (?, ?, ?, ?, 'redemption')",
            [$cardId, $orderId, $amount, $newBalance]
        );

        return true;
    }

    /**
     * Send gift card email
     */
    public function sendGiftCardEmail(int $cardId): bool
    {
        $card = $this->findById($cardId);
        if (!$card || !$card['recipient_email']) {
            return false;
        }

        $html = $this->getEmailTemplate($card);

        $sent = sendEmail(
            $card['recipient_email'],
            "You've received a Gift Card from " . appName() . "!",
            $html,
            ['html' => true]
        );

        if ($sent) {
            $this->db->update("UPDATE gift_cards SET sent_at = NOW() WHERE id = ?", [$cardId]);
        }

        return $sent;
    }

    /**
     * Get email template
     */
    private function getEmailTemplate(array $card): string
    {
        $amount = number_format($card['initial_balance'], 2);
        $recipientName = $card['recipient_name'] ?: 'there';
        $message = $card['personal_message'] ? htmlspecialchars($card['personal_message']) : '';
        $expiresAt = $card['expires_at'] ? date('F j, Y', strtotime($card['expires_at'])) : 'Never';

        $messageHtml = $message ? "
            <div style='background: #fff5fa; border-left: 4px solid #FF68C5; padding: 15px 20px; margin: 20px 0; border-radius: 0 8px 8px 0;'>
                <p style='margin: 0; font-style: italic; color: #4b5563;'>\"{$message}\"</p>
            </div>" : '';

        return "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
</head>
<body style='margin: 0; padding: 0; background-color: #1a1a2e; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif;'>
    <table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='background-color: #1a1a2e;'>
        <tr>
            <td align='center' style='padding: 40px 20px;'>
                <table role='presentation' width='600' cellpadding='0' cellspacing='0' style='max-width: 600px; width: 100%; background: #ffffff; border-radius: 16px; overflow: hidden;'>

                    <tr>
                        <td align='center' style='background-color: #FFF5FA; padding: 30px;'>
                            <img src='' . appUrl() . '/assets/images/placeholder.png' alt='' . appName() . '' width='180'>
                        </td>
                    </tr>

                    <tr>
                        <td style='padding: 40px;'>
                            <h1 style='margin: 0 0 20px 0; font-size: 28px; color: #1f2937; text-align: center;'>
                                🎁 You've Received a Gift Card!
                            </h1>

                            <p style='margin: 0 0 25px 0; font-size: 16px; color: #4b5563; text-align: center; line-height: 1.6;'>
                                Hi {$recipientName}! Someone special wanted to treat you to something beautiful.
                            </p>

                            {$messageHtml}

                            <!-- Gift Card Display -->
                            <div style='background: linear-gradient(135deg, #FF68C5, #ff4db8); border-radius: 16px; padding: 30px; text-align: center; margin: 30px 0;'>
                                <p style='margin: 0 0 10px 0; color: rgba(255,255,255,0.9); font-size: 14px; text-transform: uppercase; letter-spacing: 1px;'>Your Gift Card</p>
                                <p style='margin: 0 0 15px 0; color: #ffffff; font-size: 42px; font-weight: 700;'>\${$amount}</p>
                                <p style='margin: 0; background: rgba(255,255,255,0.2); display: inline-block; padding: 10px 20px; border-radius: 8px; color: #ffffff; font-family: monospace; font-size: 18px; letter-spacing: 2px;'>{$card['code']}</p>
                            </div>

                            <p style='margin: 0 0 25px 0; font-size: 14px; color: #6b7280; text-align: center;'>
                                Valid until: {$expiresAt}
                            </p>

                            <table role='presentation' width='100%' cellpadding='0' cellspacing='0'>
                                <tr>
                                    <td align='center'>
                                        <a href='' . appUrl() . '/products' style='display: inline-block; padding: 16px 40px; background: #1f2937; color: #ffffff !important; text-decoration: none; border-radius: 50px; font-weight: 600;'>
                                            Start Shopping
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style='margin: 30px 0 0 0; font-size: 14px; color: #9ca3af; text-align: center;'>
                                Enter your gift card code at checkout to redeem.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style='background-color: #1a1a2e; padding: 25px 40px; text-align: center;'>
                            <p style='margin: 0; font-size: 13px; color: #9ca3af;'>
                                Apparix - Unique Handmade Gifts
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
     * Get all gift cards for admin
     */
    public function getAll(int $limit = 50, int $offset = 0): array
    {
        return $this->db->select(
            "SELECT * FROM gift_cards ORDER BY created_at DESC LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }

    /**
     * Get gift card transactions
     */
    public function getTransactions(int $cardId): array
    {
        return $this->db->select(
            "SELECT * FROM gift_card_transactions WHERE gift_card_id = ? ORDER BY created_at DESC",
            [$cardId]
        );
    }
}
