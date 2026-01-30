<?php

namespace App\Core;

/**
 * Order Notification Service
 *
 * Sends notifications when new orders are placed:
 * - Push notification via Ntfy (with ca-ching sound)
 * - Email notification to admin
 */
class OrderNotificationService
{
    private NtfyService $ntfy;
    private Database $db;

    public function __construct()
    {
        $this->ntfy = new NtfyService();
        $this->db = Database::getInstance();
    }

    /**
     * Send all notifications for a new order
     */
    public function notifyNewOrder(int $orderId): void
    {
        try {
            // Get order details
            $order = $this->db->selectOne(
                "SELECT o.*,
                        sa.first_name as ship_first_name, sa.last_name as ship_last_name,
                        sa.address_line1 as ship_address1, sa.address_line2 as ship_address2,
                        sa.city as ship_city, sa.state as ship_state,
                        sa.postal_code as ship_postal, sa.country as ship_country,
                        sa.phone as ship_phone
                 FROM orders o
                 LEFT JOIN addresses sa ON o.shipping_address_id = sa.id
                 WHERE o.id = ?",
                [$orderId]
            );

            if (!$order) {
                error_log("OrderNotificationService: Order {$orderId} not found");
                return;
            }

            // Get order items with variant option details
            $items = $this->db->select(
                "SELECT oi.*,
                        p.name as base_product_name,
                        (SELECT GROUP_CONCAT(CONCAT(po.option_name, ': ', pov.value_name) SEPARATOR ', ')
                         FROM product_variants pv
                         JOIN variant_option_values vov ON pv.id = vov.variant_id
                         JOIN product_option_values pov ON vov.option_value_id = pov.id
                         JOIN product_options po ON pov.option_id = po.id
                         WHERE pv.sku = oi.product_sku) as variant_options
                 FROM order_items oi
                 LEFT JOIN products p ON oi.product_id = p.id
                 WHERE oi.order_id = ?",
                [$orderId]
            );

            // Build shipping address array
            $shippingAddress = [
                'first_name' => $order['ship_first_name'],
                'last_name' => $order['ship_last_name'],
                'address_line1' => $order['ship_address1'],
                'address_line2' => $order['ship_address2'],
                'city' => $order['ship_city'],
                'state' => $order['ship_state'],
                'postal_code' => $order['ship_postal'],
                'country' => $order['ship_country'],
                'phone' => $order['ship_phone']
            ];

            // Enhance items with variant info in product name if not already included
            foreach ($items as &$item) {
                if (!empty($item['variant_options']) && strpos($item['product_name'], ' - ') === false) {
                    $item['product_name'] .= ' - ' . $item['variant_options'];
                }
            }

            // Send push notification (non-blocking - don't let it fail the order)
            $this->sendPushNotification($order, $items, $shippingAddress);

            // Send email notification
            $this->sendEmailNotification($order, $items, $shippingAddress);

        } catch (\Exception $e) {
            // Log but don't throw - notifications shouldn't break orders
            error_log("OrderNotificationService error: " . $e->getMessage());
        }
    }

    /**
     * Send push notification via Ntfy
     */
    private function sendPushNotification(array $order, array $items, array $shippingAddress): void
    {
        try {
            $success = $this->ntfy->sendOrderNotification($order, $items, $shippingAddress);
            if ($success) {
                error_log("Ntfy notification sent for order {$order['order_number']}");
            }
        } catch (\Exception $e) {
            error_log("Ntfy notification failed: " . $e->getMessage());
        }
    }

    /**
     * Send email notification to admin
     */
    private function sendEmailNotification(array $order, array $items, array $shippingAddress): void
    {
        $adminEmail = adminNotificationEmail();
        $siteName = appName();
        $siteUrl = appUrl();

        $customerName = trim($shippingAddress['first_name'] . ' ' . $shippingAddress['last_name']);

        // Build items HTML
        $itemsHtml = '';
        foreach ($items as $item) {
            $itemsHtml .= "<tr>
                <td style='padding: 12px; border-bottom: 1px solid #f0f0f0;'>
                    <strong>{$item['product_name']}</strong><br>
                    <span style='color: #666; font-size: 13px;'>SKU: {$item['product_sku']}</span>
                </td>
                <td style='padding: 12px; border-bottom: 1px solid #f0f0f0; text-align: center;'>{$item['quantity']}</td>
                <td style='padding: 12px; border-bottom: 1px solid #f0f0f0; text-align: right;'>$" . number_format($item['price'], 2) . "</td>
                <td style='padding: 12px; border-bottom: 1px solid #f0f0f0; text-align: right;'>$" . number_format($item['total'], 2) . "</td>
            </tr>";
        }

        // Build address HTML
        $addressHtml = htmlspecialchars($shippingAddress['first_name'] . ' ' . $shippingAddress['last_name']) . "<br>";
        $addressHtml .= htmlspecialchars($shippingAddress['address_line1']) . "<br>";
        if (!empty($shippingAddress['address_line2'])) {
            $addressHtml .= htmlspecialchars($shippingAddress['address_line2']) . "<br>";
        }
        $addressHtml .= htmlspecialchars($shippingAddress['city'] . ', ' . $shippingAddress['state'] . ' ' . $shippingAddress['postal_code']) . "<br>";
        $addressHtml .= htmlspecialchars($shippingAddress['country']);
        if (!empty($shippingAddress['phone'])) {
            $addressHtml .= "<br>Phone: " . htmlspecialchars($shippingAddress['phone']);
        }

        // Discount row if applicable
        $discountRow = '';
        if ($order['discount_amount'] > 0) {
            $discountRow = "<tr>
                <td style='padding: 8px 0; color: #16a34a;'>Discount:</td>
                <td style='padding: 8px 0; text-align: right; color: #16a34a;'>-$" . number_format($order['discount_amount'], 2) . "</td>
            </tr>";
        }

        $subject = "New Order! {$order['order_number']} - $" . number_format($order['total'], 2);

        $html = "<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
</head>
<body style='margin: 0; padding: 0; background-color: #1a1a2e; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif;'>
    <table width='100%' cellpadding='0' cellspacing='0' style='background: linear-gradient(135deg, #fdf2f8 0%, #fce7f3 100%); padding: 40px 20px;'>
        <tr>
            <td align='center'>
                <table width='600' cellpadding='0' cellspacing='0' style='background: #ffffff; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); overflow: hidden;'>
                    <!-- Header -->
                    <tr>
                        <td style='background: linear-gradient(135deg, #ec4899 0%, #f472b6 100%); padding: 30px; text-align: center;'>
                            <h1 style='margin: 0; color: #ffffff; font-size: 28px;'>New Order!</h1>
                            <p style='margin: 10px 0 0; color: rgba(255,255,255,0.9); font-size: 16px;'>{$order['order_number']}</p>
                        </td>
                    </tr>

                    <!-- Order Summary -->
                    <tr>
                        <td style='padding: 30px;'>
                            <table width='100%' cellpadding='0' cellspacing='0' style='background: #fef3c7; border-radius: 8px; padding: 15px; margin-bottom: 25px;'>
                                <tr>
                                    <td>
                                        <strong style='color: #92400e; font-size: 18px;'>Total: $" . number_format($order['total'], 2) . "</strong>
                                    </td>
                                    <td style='text-align: right;'>
                                        <span style='color: #666;'>" . date('M j, Y g:i A', strtotime($order['created_at'])) . "</span>
                                    </td>
                                </tr>
                            </table>

                            <!-- Customer Info -->
                            <h3 style='margin: 0 0 15px; color: #333; border-bottom: 2px solid #ec4899; padding-bottom: 8px;'>Customer</h3>
                            <p style='margin: 0 0 20px; color: #333;'>
                                <strong>{$customerName}</strong><br>
                                <a href='mailto:{$order['customer_email']}' style='color: #ec4899;'>{$order['customer_email']}</a>
                            </p>

                            <!-- Items -->
                            <h3 style='margin: 0 0 15px; color: #333; border-bottom: 2px solid #ec4899; padding-bottom: 8px;'>Items Ordered</h3>
                            <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom: 25px;'>
                                <tr style='background: #f9fafb;'>
                                    <th style='padding: 10px; text-align: left; font-size: 13px; color: #666;'>Product</th>
                                    <th style='padding: 10px; text-align: center; font-size: 13px; color: #666;'>Qty</th>
                                    <th style='padding: 10px; text-align: right; font-size: 13px; color: #666;'>Price</th>
                                    <th style='padding: 10px; text-align: right; font-size: 13px; color: #666;'>Total</th>
                                </tr>
                                {$itemsHtml}
                            </table>

                            <!-- Totals -->
                            <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom: 25px;'>
                                <tr>
                                    <td width='60%'></td>
                                    <td>
                                        <table width='100%' cellpadding='0' cellspacing='0'>
                                            <tr>
                                                <td style='padding: 8px 0;'>Subtotal:</td>
                                                <td style='padding: 8px 0; text-align: right;'>$" . number_format($order['subtotal'], 2) . "</td>
                                            </tr>
                                            {$discountRow}
                                            <tr>
                                                <td style='padding: 8px 0;'>Shipping ({$order['shipping_method']}):</td>
                                                <td style='padding: 8px 0; text-align: right;'>$" . number_format($order['shipping_cost'], 2) . "</td>
                                            </tr>
                                            <tr style='border-top: 2px solid #333;'>
                                                <td style='padding: 12px 0; font-weight: bold; font-size: 18px;'>Total:</td>
                                                <td style='padding: 12px 0; text-align: right; font-weight: bold; font-size: 18px; color: #ec4899;'>$" . number_format($order['total'], 2) . "</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Shipping Address -->
                            <h3 style='margin: 0 0 15px; color: #333; border-bottom: 2px solid #ec4899; padding-bottom: 8px;'>Ship To</h3>
                            <p style='margin: 0 0 20px; color: #333; line-height: 1.6;'>
                                {$addressHtml}
                            </p>

                            <!-- Action Button -->
                            <table width='100%' cellpadding='0' cellspacing='0'>
                                <tr>
                                    <td align='center' style='padding: 20px 0;'>
                                        <a href='{$siteUrl}/admin/orders/view?id={$order['id']}' style='display: inline-block; background: linear-gradient(135deg, #ec4899 0%, #f472b6 100%); color: #ffffff; text-decoration: none; padding: 14px 35px; border-radius: 25px; font-weight: 600; font-size: 16px;'>View Order in Admin</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style='background: #fdf2f8; padding: 20px; text-align: center;'>
                            <p style='margin: 0; color: #666; font-size: 14px;'>{$siteName}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>";

        // Send email using configured method (SMTP or PHP mail)
        $sent = sendEmail($adminEmail, $subject, $html, [
            'html' => true,
            'replyTo' => $order['customer_email']
        ]);

        if ($sent) {
            error_log("Order notification email sent to {$adminEmail} for {$order['order_number']}");
        } else {
            error_log("Failed to send order notification email for {$order['order_number']}");
        }
    }
}
