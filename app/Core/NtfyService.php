<?php

namespace App\Core;

/**
 * Ntfy.sh Push Notification Service
 *
 * Sends push notifications to the Ntfy app for instant sale alerts
 * with the classic cash register "ca-ching" sound.
 */
class NtfyService
{
    private string $topic;
    private string $server;

    public function __construct()
    {
        // Use a unique topic name - can be configured in .env
        $this->topic = $_ENV['NTFY_TOPIC'] ?? 'store-sales';
        $this->server = $_ENV['NTFY_SERVER'] ?? 'https://ntfy.sh';
    }

    /**
     * Send a new order notification
     */
    public function sendOrderNotification(array $order, array $items, array $shippingAddress): bool
    {
        $customerName = trim($shippingAddress['first_name'] . ' ' . $shippingAddress['last_name']);

        // Build item details
        $itemLines = [];
        foreach ($items as $item) {
            $line = "• {$item['product_name']}";
            if (!empty($item['product_sku'])) {
                $line .= " (SKU: {$item['product_sku']})";
            }
            $line .= " x{$item['quantity']} - $" . number_format($item['total'], 2);
            $itemLines[] = $line;
        }

        // Build full address
        $address = $shippingAddress['address_line1'];
        if (!empty($shippingAddress['address_line2'])) {
            $address .= "\n" . $shippingAddress['address_line2'];
        }
        $address .= "\n{$shippingAddress['city']}, {$shippingAddress['state']} {$shippingAddress['postal_code']}";
        $address .= "\n{$shippingAddress['country']}";

        // Build message body
        $body = "Customer: {$customerName}\n";
        $body .= "Email: {$order['customer_email']}\n";
        $body .= "\n--- Items ---\n";
        $body .= implode("\n", $itemLines);
        $body .= "\n\n--- Totals ---\n";
        $body .= "Subtotal: $" . number_format($order['subtotal'], 2) . "\n";
        if ($order['discount_amount'] > 0) {
            $body .= "Discount: -$" . number_format($order['discount_amount'], 2) . "\n";
        }
        $body .= "Shipping: $" . number_format($order['shipping_cost'], 2) . "\n";
        $body .= "Total: $" . number_format($order['total'], 2) . "\n";
        $body .= "\n--- Ship To ---\n";
        $body .= $address;

        if (!empty($shippingAddress['phone'])) {
            $body .= "\nPhone: {$shippingAddress['phone']}";
        }

        return $this->send(
            title: "New Order! {$order['order_number']}",
            message: $body,
            tags: ['moneybag', 'shopping_cart'],
            priority: 'max',
            sound: 'cashregister'
        );
    }

    /**
     * Send a generic notification
     */
    public function send(
        string $title,
        string $message,
        array $tags = [],
        string $priority = 'default',
        ?string $sound = null,
        ?string $click = null
    ): bool {
        $url = "{$this->server}/{$this->topic}";

        $headers = [
            'Title: ' . $title,
            'Priority: ' . $priority,
        ];

        if (!empty($tags)) {
            $headers[] = 'Tags: ' . implode(',', $tags);
        }

        // Ntfy supports custom sounds on Android
        // Options: default, ping, alarm, cashregister, pop, etc.
        if ($sound) {
            $headers[] = 'X-Sound: ' . $sound;
        }

        if ($click) {
            $headers[] = 'Click: ' . $click;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $message,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("Ntfy notification failed: {$error}");
            return false;
        }

        if ($httpCode !== 200) {
            error_log("Ntfy notification failed with HTTP {$httpCode}: {$response}");
            return false;
        }

        return true;
    }

    /**
     * Get subscription URL for the topic
     */
    public function getSubscriptionUrl(): string
    {
        return "{$this->server}/{$this->topic}";
    }

    /**
     * Get the topic name
     */
    public function getTopic(): string
    {
        return $this->topic;
    }
}
