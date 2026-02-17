<?php

namespace App\Core;

use App\Models\Order;

/**
 * Service for sending order status notification emails
 * Matches the newsletter design style
 */
class OrderStatusEmailService
{
    private string $fromEmail;
    private string $fromName;
    private string $siteUrl;
    private string $siteName;

    public function __construct()
    {
        $this->fromEmail = storeEmail();
        $this->fromName = appName();
        $this->siteUrl = appUrl();
        $this->siteName = appName();
    }

    /**
     * Send status change notification email
     */
    public function sendStatusEmail(array $order, string $newStatus, ?array $trackingInfo = null): bool
    {
        // Don't send email for pending status (initial order confirmation handles this)
        if ($newStatus === 'pending') {
            return true;
        }

        $customerEmail = $order['customer_email'];
        $customerName = $order['shipping_first_name'] ?? $order['billing_first_name'] ?? 'Valued Customer';
        $orderNumber = $order['order_number'];

        $emailData = $this->getEmailContent($newStatus, $customerName, $orderNumber, $order, $trackingInfo);

        if (!$emailData) {
            return false;
        }

        $html = $this->buildEmailTemplate($emailData);

        return sendEmail($customerEmail, $emailData['subject'], $html, ['html' => true]);
    }

    /**
     * Get email content based on status
     */
    private function getEmailContent(string $status, string $customerName, string $orderNumber, array $order, ?array $trackingInfo): ?array
    {
        $orderUrl = '' . $this->siteUrl . '/account/orders';

        switch ($status) {
            case 'processing':
                return [
                    'subject' => "We're Working on Your Order #{$orderNumber}!",
                    'customer_name' => $customerName,
                    'title' => "Your Order is Being Prepared",
                    'icon' => '&#128230;', // Package emoji
                    'icon_color' => '#FF68C5',
                    'greeting' => "Great news! We've started working on your order.",
                    'message' => "Our team is carefully preparing your items with love and attention to detail. We'll notify you as soon as your order ships.",
                    'details' => [
                        ['label' => 'Order Number', 'value' => $orderNumber],
                        ['label' => 'Status', 'value' => 'Processing']
                    ],
                    'cta_text' => 'View Order Details',
                    'cta_url' => $orderUrl,
                    'footer_message' => 'Thank you for your patience!'
                ];

            case 'shipped':
                $carrierName = '';
                $trackingNumber = '';
                $trackingUrl = '';
                $estimatedDelivery = '';

                if ($trackingInfo) {
                    $carrierName = Order::getCarrierName($trackingInfo['carrier'] ?? '');
                    $trackingNumber = $trackingInfo['tracking_number'] ?? '';
                    $trackingUrl = Order::getTrackingUrl($trackingInfo['carrier'] ?? '', $trackingNumber);
                    $estimatedDelivery = $trackingInfo['estimated_delivery'] ?? '';
                } elseif (!empty($order['tracking_carrier'])) {
                    $carrierName = Order::getCarrierName($order['tracking_carrier']);
                    $trackingNumber = $order['tracking_number'] ?? '';
                    $trackingUrl = Order::getTrackingUrl($order['tracking_carrier'], $trackingNumber);
                    $estimatedDelivery = $order['estimated_delivery'] ?? '';
                }

                $details = [
                    ['label' => 'Order Number', 'value' => $orderNumber],
                    ['label' => 'Status', 'value' => 'Shipped']
                ];

                if ($carrierName) {
                    $details[] = ['label' => 'Carrier', 'value' => $carrierName];
                }
                if ($trackingNumber) {
                    $details[] = ['label' => 'Tracking Number', 'value' => $trackingNumber, 'mono' => true];
                }
                if ($estimatedDelivery) {
                    $details[] = ['label' => 'Estimated Delivery', 'value' => $estimatedDelivery];
                }

                return [
                    'subject' => "Your Order #{$orderNumber} Has Shipped!",
                    'customer_name' => $customerName,
                    'title' => "Your Order is On Its Way!",
                    'icon' => '&#128666;', // Delivery truck emoji
                    'icon_color' => '#10b981',
                    'greeting' => "Exciting news! Your order is on its way to you.",
                    'message' => $trackingNumber
                        ? "You can track your package using the tracking number below. We hope you love your items!"
                        : "Your order has been handed off to the carrier. We hope you love your items!",
                    'details' => $details,
                    'cta_text' => $trackingUrl ? 'Track Your Package' : 'View Order Details',
                    'cta_url' => $trackingUrl ?: $orderUrl,
                    'footer_message' => 'Your package is on its way!'
                ];

            case 'delivered':
                return [
                    'subject' => "Your Order #{$orderNumber} Has Been Delivered!",
                    'customer_name' => $customerName,
                    'title' => "Your Order Has Arrived!",
                    'icon' => '&#127881;', // Party popper emoji
                    'icon_color' => '#10b981',
                    'greeting' => "Great news! Your order has been delivered.",
                    'message' => "We hope you absolutely love your items! If you have a moment, we'd really appreciate a review. Your feedback helps other customers and means the world to us.",
                    'details' => [
                        ['label' => 'Order Number', 'value' => $orderNumber],
                        ['label' => 'Status', 'value' => 'Delivered']
                    ],
                    'cta_text' => 'Leave a Review',
                    'cta_url' => $orderUrl,
                    'footer_message' => 'Thank you for shopping with us!'
                ];

            case 'cancelled':
                return [
                    'subject' => "Order #{$orderNumber} Has Been Cancelled",
                    'customer_name' => $customerName,
                    'title' => "Order Cancelled",
                    'icon' => '&#10060;', // X emoji
                    'icon_color' => '#ef4444',
                    'greeting' => "We're sorry to inform you that your order has been cancelled.",
                    'message' => "If you didn't request this cancellation or have any questions, please don't hesitate to contact us. We're here to help!",
                    'details' => [
                        ['label' => 'Order Number', 'value' => $orderNumber],
                        ['label' => 'Status', 'value' => 'Cancelled']
                    ],
                    'cta_text' => 'Contact Us',
                    'cta_url' => '' . $this->siteUrl . '/contact',
                    'footer_message' => 'We hope to serve you again soon.'
                ];

            case 'refunded':
                return [
                    'subject' => "Refund Processed for Order #{$orderNumber}",
                    'customer_name' => $customerName,
                    'title' => "Refund Processed",
                    'icon' => '&#128176;', // Money bag emoji
                    'icon_color' => '#10b981',
                    'greeting' => "Your refund has been processed.",
                    'message' => "The refund for your order has been initiated. Please allow 5-10 business days for the funds to appear in your account, depending on your bank or payment provider.",
                    'details' => [
                        ['label' => 'Order Number', 'value' => $orderNumber],
                        ['label' => 'Status', 'value' => 'Refunded'],
                        ['label' => 'Refund Amount', 'value' => '$' . number_format($order['total'] ?? 0, 2)]
                    ],
                    'cta_text' => 'View Order Details',
                    'cta_url' => $orderUrl,
                    'footer_message' => 'Thank you for your understanding.'
                ];

            default:
                return null;
        }
    }

    /**
     * Build the email HTML template matching newsletter design
     */
    private function buildEmailTemplate(array $data): string
    {
        // Build details table rows
        $detailsHtml = '';
        foreach ($data['details'] as $index => $detail) {
            $borderTop = $index > 0 ? 'border-top: 1px solid #f3f4f6;' : '';
            $fontFamily = !empty($detail['mono']) ? "font-family: 'Courier New', monospace;" : '';
            $detailsHtml .= '
                <tr>
                    <td style="padding: 12px 0; color: #6b7280; font-size: 15px; ' . $borderTop . '">' . htmlspecialchars($detail['label']) . ':</td>
                    <td style="padding: 12px 0; color: #1f2937; font-size: 15px; font-weight: 600; text-align: right; ' . $borderTop . $fontFamily . '">' . htmlspecialchars($detail['value']) . '</td>
                </tr>';
        }

        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>' . htmlspecialchars($data['subject']) . '</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style type="text/css">
        @import url(\'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap\');
        body, table, td, p, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        body { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        @media screen and (max-width: 600px) {
            .wrapper { padding: 20px 15px !important; }
            .main-content { padding: 30px 25px !important; }
            .header-logo { width: 220px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #1a1a2e; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">

    <!-- Wrapper Table -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #1a1a2e;">
        <tr>
            <td align="center" class="wrapper" style="padding: 40px 20px;">

                <!-- Main Container -->
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(255, 104, 197, 0.15);">

                    <!-- HEADER with decorative circles -->
                    <tr>
                        <td style="position: relative; background: linear-gradient(135deg, #FFE4F3 0%, #FFFFFF 40%, #FFFFFF 60%, #FFE4F3 100%); padding: 0; overflow: hidden;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="position: relative; height: 0;">
                                        <div style="position: absolute; width: 150px; height: 150px; background: #FF68C5; opacity: 0.08; border-radius: 50%; top: -50px; left: -30px;"></div>
                                        <div style="position: absolute; width: 100px; height: 100px; background: #FF94C8; opacity: 0.08; border-radius: 50%; top: 20px; right: -20px;"></div>
                                        <div style="position: absolute; width: 80px; height: 80px; background: #FF68C5; opacity: 0.06; border-radius: 50%; bottom: -40px; left: 40%;"></div>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding: 40px 40px 20px 40px;">
                                        <a href="' . $this->siteUrl . '" style="text-decoration: none;">
                                            <img src="' . $this->siteUrl . '/assets/images/placeholder.png" alt="' . htmlspecialchars($this->siteName) . '" width="280" class="header-logo" style="max-width: 280px; width: 100%; height: auto; display: block;">
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding: 0 40px 30px 40px;">
                                        <p style="margin: 0; font-family: \'Playfair Display\', Georgia, serif; font-size: 14px; font-style: italic; color: #FF68C5; letter-spacing: 0.5px;">
                                            ' . htmlspecialchars($this->getTagline()) . '
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Pink divider line -->
                    <tr>
                        <td style="height: 3px; background: linear-gradient(90deg, #FFE4F3, #FF68C5, #FFE4F3);"></td>
                    </tr>

                    <!-- STATUS ICON & TITLE -->
                    <tr>
                        <td align="center" style="padding: 35px 40px 20px 40px;">
                            <div style="font-size: 48px; margin-bottom: 15px;">' . $data['icon'] . '</div>
                            <h1 style="margin: 0; font-family: \'Playfair Display\', Georgia, serif; font-size: 26px; font-weight: 600; color: #1f2937; letter-spacing: 0.3px;">
                                ' . htmlspecialchars($data['title']) . '
                            </h1>
                        </td>
                    </tr>

                    <!-- GREETING & MESSAGE -->
                    <tr>
                        <td class="main-content" style="padding: 0 40px 30px 40px;">
                            <p style="margin: 0 0 20px 0; font-size: 18px; color: #1f2937; line-height: 1.5;">
                                Hi <strong style="color: #FF68C5;">' . htmlspecialchars($data['customer_name']) . '</strong>,
                            </p>
                            <p style="margin: 0 0 15px 0; font-size: 16px; color: #4b5563; line-height: 1.7;">
                                ' . htmlspecialchars($data['greeting']) . '
                            </p>
                            <p style="margin: 0; font-size: 16px; color: #4b5563; line-height: 1.7;">
                                ' . htmlspecialchars($data['message']) . '
                            </p>
                        </td>
                    </tr>

                    <!-- ORDER DETAILS BOX -->
                    <tr>
                        <td style="padding: 0 40px 30px 40px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb;">
                                <tr>
                                    <td style="padding: 20px 25px; border-bottom: 2px solid #FF68C5;">
                                        <h2 style="margin: 0; font-family: \'Playfair Display\', Georgia, serif; font-size: 18px; font-weight: 600; color: #1f2937;">
                                            Order Details
                                        </h2>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 20px 25px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            ' . $detailsHtml . '
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- CTA BUTTON -->
                    <tr>
                        <td align="center" style="padding: 0 40px 40px 40px;">
                            <a href="' . htmlspecialchars($data['cta_url']) . '" style="display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #FF68C5 0%, #ff4db8 100%); color: #ffffff !important; text-decoration: none !important; border-radius: 50px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 15px rgba(255, 104, 197, 0.4);">
                                ' . htmlspecialchars($data['cta_text']) . '
                            </a>
                        </td>
                    </tr>

                    <!-- FOOTER -->
                    <tr>
                        <td style="background-color: #1a1a2e; padding: 0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 35px 40px;">
                                        <p style="margin: 0 0 15px 0; font-family: \'Playfair Display\', Georgia, serif; font-size: 15px; font-style: italic; color: #FF68C5;">
                                            ' . htmlspecialchars($data['footer_message']) . '
                                        </p>
                                        ' . $this->getSocialLinksHtml() . '
                                        <p style="margin: 0; font-size: 13px; color: #9ca3af; line-height: 1.6;">
                                            Questions? Contact us at <a href="mailto:' . $this->fromEmail . '" style="color: #FF68C5; text-decoration: none;">' . $this->fromEmail . '</a>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>';
    }

    /**
     * Get store tagline from settings
     */
    private function getTagline(): string
    {
        return setting('store_tagline') ?: 'Quality Products, Great Prices';
    }

    /**
     * Generate social links HTML for email footer
     */
    private function getSocialLinksHtml(): string
    {
        $links = [];

        if ($instagram = setting('social_instagram')) {
            $links[] = '<a href="' . htmlspecialchars($instagram) . '" style="color: #FF68C5; font-size: 14px; text-decoration: none; font-weight: 500;">Instagram</a>';
        }

        if ($facebook = setting('social_facebook')) {
            $links[] = '<a href="' . htmlspecialchars($facebook) . '" style="color: #FF68C5; font-size: 14px; text-decoration: none; font-weight: 500;">Facebook</a>';
        }

        if ($twitter = setting('social_twitter')) {
            $links[] = '<a href="' . htmlspecialchars($twitter) . '" style="color: #FF68C5; font-size: 14px; text-decoration: none; font-weight: 500;">Twitter</a>';
        }

        if (empty($links)) {
            return '';
        }

        return '<table role="presentation" cellpadding="0" cellspacing="0" style="margin-bottom: 20px;">
            <tr>
                <td style="padding: 0 12px;">' . implode('</td><td style="color: #fce7f3; font-size: 14px;">•</td><td style="padding: 0 12px;">', $links) . '</td>
            </tr>
        </table>';
    }
}
