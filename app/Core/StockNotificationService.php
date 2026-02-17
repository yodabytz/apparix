<?php

namespace App\Core;

use App\Models\StockNotification;

class StockNotificationService
{
    private StockNotification $notificationModel;
    private string $siteName;
    private string $siteUrl;

    public function __construct()
    {
        $this->notificationModel = new StockNotification();
        $this->siteName = appName();
        $this->siteUrl = appUrl();
    }

    /**
     * Check and send notifications when product/variant is back in stock
     */
    public function checkAndNotify(int $productId, ?int $variantId = null, int $newStock = 0): int
    {
        // Only proceed if there's stock now
        if ($newStock <= 0) {
            return 0;
        }

        // Get pending notifications for this product/variant
        $pending = $this->notificationModel->getPendingForProduct($productId, $variantId);

        if (empty($pending)) {
            return 0;
        }

        // Get product info for the email
        $db = Database::getInstance();
        $product = $db->selectOne(
            "SELECT id, name, slug, price, sale_price FROM products WHERE id = ?",
            [$productId]
        );

        if (!$product) {
            return 0;
        }

        // Get primary image
        $image = $db->selectOne(
            "SELECT image_path FROM product_images WHERE product_id = ? AND is_primary = 1 LIMIT 1",
            [$productId]
        );

        // Get variant info if applicable
        $variantName = null;
        if ($variantId) {
            $variant = $db->selectOne(
                "SELECT sku FROM product_variants WHERE id = ?",
                [$variantId]
            );
            $variantName = $variant['sku'] ?? null;
        }

        $sentCount = 0;
        $sentIds = [];

        foreach ($pending as $notification) {
            $success = $this->sendNotificationEmail(
                $notification['email'],
                $product,
                $image['image_path'] ?? null,
                $notification['variant_name'] ?? $variantName,
                $notification['id']
            );

            if ($success) {
                $sentIds[] = $notification['id'];
                $sentCount++;
            }
        }

        // Mark notifications as sent
        if (!empty($sentIds)) {
            $this->notificationModel->markNotified($sentIds);
        }

        return $sentCount;
    }

    /**
     * Send back-in-stock email notification
     */
    private function sendNotificationEmail(
        string $email,
        array $product,
        ?string $imagePath,
        ?string $variantName,
        int $notificationId
    ): bool {
        $productUrl = $this->siteUrl . '/products/' . $product['slug'];
        $imageUrl = $imagePath ? $this->siteUrl . $imagePath : $this->siteUrl . '/assets/images/placeholder.png';
        $price = $product['sale_price'] ?: $product['price'];
        $formattedPrice = '$' . number_format($price, 2);
        $productName = $product['name'] . ($variantName ? " - {$variantName}" : '');

        $subject = "Great news! {$product['name']} is back in stock!";

        $htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Back in Stock!</title>
    <style type="text/css">
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap');
        body, table, td, p, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        body { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        h1, h2, h3 { font-family: 'Playfair Display', Georgia, serif; font-weight: 600; color: #1f2937; margin: 0 0 16px 0; }
        @media screen and (max-width: 600px) {
            .wrapper { padding: 20px 15px !important; }
            .main-content { padding: 30px 25px !important; }
            .header-logo { width: 220px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #1a1a2e; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">

    <!-- Wrapper Table -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #1a1a2e;">
        <tr>
            <td align="center" class="wrapper" style="padding: 40px 20px;">

                <!-- Main Container -->
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(255, 104, 197, 0.15);">

                    <!-- HEADER with decorative background -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #FFE4F3 0%, #FFFFFF 40%, #FFFFFF 60%, #FFE4F3 100%); padding: 0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <!-- Logo area -->
                                <tr>
                                    <td align="center" style="padding: 40px 40px 20px 40px;">
                                        <a href="{$this->siteUrl}" style="text-decoration: none;">
                                            <img src="{$this->siteUrl}/assets/images/placeholder.png" alt="{$this->siteName}" width="280" class="header-logo" style="max-width: 280px; width: 100%; height: auto; display: block;">
                                        </a>
                                    </td>
                                </tr>
                                <!-- Tagline -->
                                <tr>
                                    <td align="center" style="padding: 0 40px 30px 40px;">
                                        <p style="margin: 0; font-family: 'Playfair Display', Georgia, serif; font-size: 14px; font-style: italic; color: #FF68C5; letter-spacing: 0.5px;">
                                            {$this->getTagline()}
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

                    <!-- MAIN CONTENT -->
                    <tr>
                        <td class="main-content" style="padding: 35px 40px;">
                            <!-- Bell Icon -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding-bottom: 20px;">
                                        <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #FF68C5 0%, #ff4db8 100%); border-radius: 50%; display: inline-block; text-align: center; line-height: 60px;">
                                            <img src="{$this->siteUrl}/assets/images/bell-icon.png" alt="" width="28" style="vertical-align: middle;" onerror="this.style.display='none'">
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <h1 style="margin: 0 0 15px; font-family: 'Playfair Display', Georgia, serif; font-size: 26px; color: #1f2937; text-align: center;">It's Back in Stock!</h1>

                            <p style="margin: 0 0 30px; color: #4b5563; font-size: 16px; line-height: 1.7; text-align: center;">
                                Great news! The item you've been waiting for is available again.
                            </p>

                            <!-- Product Card -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, #fdf2f8 0%, #fff0f7 100%); border-radius: 12px; overflow: hidden; margin-bottom: 30px; border: 1px solid #fce7f3;">
                                <tr>
                                    <td style="padding: 25px; text-align: center;">
                                        <img src="{$imageUrl}" alt="{$product['name']}" style="max-width: 180px; height: auto; border-radius: 10px; margin-bottom: 18px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                                        <h2 style="margin: 0 0 10px; font-family: 'Playfair Display', Georgia, serif; color: #1f2937; font-size: 20px;">{$productName}</h2>
                                        <p style="margin: 0; color: #FF68C5; font-size: 22px; font-weight: 700;">{$formattedPrice}</p>
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA Button -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <a href="{$productUrl}" style="display: inline-block; padding: 16px 45px; background: linear-gradient(135deg, #FF68C5 0%, #ff4db8 100%); color: #ffffff !important; text-decoration: none !important; font-size: 16px; font-weight: 600; border-radius: 50px; box-shadow: 0 4px 15px rgba(255, 104, 197, 0.4);">
                                            Shop Now
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 25px 0 0; color: #9ca3af; font-size: 14px; text-align: center; font-style: italic;">
                                Hurry! Popular items sell out quickly.
                            </p>
                        </td>
                    </tr>

                    <!-- DIVIDER -->
                    <tr>
                        <td style="padding: 0 40px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="height: 1px; background: linear-gradient(90deg, transparent, #fce7f3, #FF68C5, #fce7f3, transparent);"></td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- FOOTER -->
                    <tr>
                        <td style="background-color: #fdf2f8; padding: 0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 35px 40px;">

                                        <!-- Social Links -->
                                        {$this->getSocialLinksHtml()}

                                        <p style="margin: 0 0 15px 0; font-family: 'Playfair Display', Georgia, serif; font-size: 15px; font-style: italic; color: #FF68C5;">
                                            Crafted with love, just for you
                                        </p>

                                        <!-- Footer Links -->
                                        <table role="presentation" cellpadding="0" cellspacing="0" style="margin-bottom: 25px;">
                                            <tr>
                                                <td style="padding: 0 10px;">
                                                    <a href="{$this->siteUrl}" style="color: #6b7280; font-size: 13px; text-decoration: none;">Website</a>
                                                </td>
                                                <td style="padding: 0 10px;">
                                                    <a href="{$this->siteUrl}/products" style="color: #6b7280; font-size: 13px; text-decoration: none;">Shop</a>
                                                </td>
                                                <td style="padding: 0 10px;">
                                                    <a href="{$this->siteUrl}/contact" style="color: #6b7280; font-size: 13px; text-decoration: none;">Contact</a>
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Unsubscribe -->
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="border-top: 1px solid #fce7f3; padding-top: 20px;">
                                                    <p style="margin: 0; font-size: 12px; color: #9ca3af; line-height: 1.6;">
                                                        You received this email because you signed up for back-in-stock notifications.<br>
                                                        &copy; " . date('Y') . " {$this->siteName}. All rights reserved.
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

            </td>
        </tr>
    </table>

</body>
</html>
HTML;

        $textBody = <<<TEXT
Great news! {$productName} is back in stock!

The item you've been waiting for is available again at {$this->siteName}.

Product: {$productName}
Price: {$formattedPrice}

Shop now: {$productUrl}

Hurry! Popular items sell out quickly.

---
{$this->siteName}
{$this->siteUrl}

You received this email because you signed up for back-in-stock notifications.
TEXT;

        return $this->sendEmailMessage($email, $subject, $htmlBody, $textBody);
    }

    /**
     * Send email using configured method (SMTP or PHP mail)
     */
    private function sendEmailMessage(string $to, string $subject, string $htmlBody, string $textBody): bool
    {
        // Use the centralized sendEmail helper which supports SMTP if configured
        return sendEmail($to, $subject, $htmlBody, ['html' => true]);
    }

    /**
     * Manually trigger notifications for a product (admin use)
     */
    public function triggerNotificationsForProduct(int $productId): array
    {
        $db = Database::getInstance();

        // Check product stock
        $product = $db->selectOne(
            "SELECT inventory_count FROM products WHERE id = ?",
            [$productId]
        );

        if (!$product || $product['inventory_count'] <= 0) {
            // Check variant stock
            $variants = $db->select(
                "SELECT id, inventory_count FROM product_variants WHERE product_id = ? AND is_active = 1",
                [$productId]
            );

            $results = [];
            foreach ($variants as $variant) {
                if ($variant['inventory_count'] > 0) {
                    $sent = $this->checkAndNotify($productId, $variant['id'], $variant['inventory_count']);
                    $results[] = ['variant_id' => $variant['id'], 'sent' => $sent];
                }
            }
            return $results;
        }

        // Product has stock
        $sent = $this->checkAndNotify($productId, null, $product['inventory_count']);
        return [['variant_id' => null, 'sent' => $sent]];
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

        if ($pinterest = setting('social_pinterest')) {
            $links[] = '<a href="' . htmlspecialchars($pinterest) . '" style="color: #FF68C5; font-size: 14px; text-decoration: none; font-weight: 500;">Pinterest</a>';
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
