<?php

namespace App\Core;

/**
 * Review Request Email Service
 *
 * Sends review request emails to customers after delivery or 3 weeks
 */
class ReviewEmailService
{
    /**
     * Send review request email
     */
    public function sendReviewRequest(array $request): bool
    {
        $fromEmail = $_ENV['MAIL_FROM'] ?? '' . storeEmail() . '';
        $fromName = $_ENV['MAIL_FROM_NAME'] ?? "Apparix";
        $siteName = $_ENV['SITE_NAME'] ?? "Apparix";
        $siteUrl = $_ENV['SITE_URL'] ?? '' . appUrl() . '';
        $b = ThemeService::getEmailBranding();

        // Get product image
        $db = Database::getInstance();
        $image = $db->selectOne(
            "SELECT image_path FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC LIMIT 1",
            [$request['product_id']]
        );
        $productImage = $image ? $siteUrl . $image['image_path'] : $b['logoUrl'];

        $reviewUrl = $siteUrl . '/review/' . $request['token'];
        $productUrl = $siteUrl . '/products/' . $request['product_slug'];

        $subject = "How did you like your {$request['product_name']}?";

        $html = $this->buildEmailHtml([
            'firstName' => $request['first_name'],
            'productName' => $request['product_name'],
            'productImage' => $productImage,
            'productUrl' => $productUrl,
            'reviewUrl' => $reviewUrl,
            'siteName' => $siteName,
            'siteUrl' => $siteUrl,
            'branding' => $b,
            'socialLinksHtml' => ThemeService::getEmailSocialLinks()
        ]);

        $sent = sendEmail($request['email'], $subject, $html, ['html' => true]);

        if ($sent) {
            error_log("Review request email sent to {$request['email']} for product {$request['product_id']}");
        } else {
            error_log("Failed to send review request email to {$request['email']}");
        }

        return $sent;
    }

    /**
     * Build the email HTML matching newsletter design
     */
    private function buildEmailHtml(array $data): string
    {
        $b = $data['branding'];
        $primary = $b['primary'];
        $primaryDark = $b['primaryDark'];
        $accent = $b['accent'];
        $btnShadowRgba = $b['btnShadowRgba'];
        $logoUrl = $b['logoUrl'];
        $tagline = $b['tagline'];

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #1a1a2e; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, {$accent} 0%, {$accent} 100%); padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background: #ffffff; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); overflow: hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 30px 40px 20px; text-align: center;">
                            <a href="{$data['siteUrl']}" style="text-decoration: none;">
                                <img src="{$logoUrl}" alt="{$data['siteName']}" style="max-width: 180px; height: auto;">
                            </a>
                            <p style="color: {$primary}; font-size: 14px; margin: 10px 0 0; font-style: italic;">{$tagline}</p>
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="padding: 0 40px;">
                            <div style="height: 3px; background: linear-gradient(90deg, transparent, {$primary}, transparent);"></div>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 30px 40px;">
                            <h1 style="margin: 0 0 20px; color: #333; font-size: 24px; text-align: center;">
                                Hi {$data['firstName']}, how was your purchase?
                            </h1>

                            <p style="color: #666; line-height: 1.6; text-align: center; margin-bottom: 30px;">
                                We hope you're loving your new item! Your feedback helps other shoppers and helps us improve. Would you take a moment to share your experience?
                            </p>

                            <!-- Product Card -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background: {$accent}; border-radius: 12px; overflow: hidden; margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 20px; text-align: center;">
                                        <a href="{$data['productUrl']}" style="text-decoration: none;">
                                            <img src="{$data['productImage']}" alt="{$data['productName']}" style="max-width: 200px; height: auto; border-radius: 8px; margin-bottom: 15px;">
                                        </a>
                                        <h3 style="margin: 0 0 10px; color: #333; font-size: 18px;">
                                            <a href="{$data['productUrl']}" style="color: #333; text-decoration: none;">{$data['productName']}</a>
                                        </h3>
                                    </td>
                                </tr>
                            </table>

                            <!-- Star Rating Preview -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding-bottom: 20px;">
                                        <p style="margin: 0 0 10px; color: #666; font-size: 14px;">How would you rate this product?</p>
                                        <span style="font-size: 32px; color: #fbbf24;">&#9733; &#9733; &#9733; &#9733; &#9733;</span>
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA Button -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <a href="{$data['reviewUrl']}" style="display: inline-block; background: linear-gradient(135deg, {$primary} 0%, {$primaryDark} 100%); color: #ffffff; text-decoration: none; padding: 16px 40px; border-radius: 30px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 15px {$btnShadowRgba};">
                                            Write Your Review
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="color: #999; font-size: 13px; text-align: center; margin-top: 30px;">
                                It only takes a minute and your honest feedback is greatly appreciated!
                            </p>
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="padding: 0 40px;">
                            <div style="height: 3px; background: linear-gradient(90deg, transparent, {$primary}, transparent);"></div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 30px 40px; text-align: center; background: {$accent};">
                            <!-- Social Links -->
                            {$data['socialLinksHtml']}

                            <p style="margin: 0 0 10px; color: {$primary}; font-style: italic;">{$tagline}</p>
                            <p style="margin: 0; color: #999; font-size: 12px;">
                                &copy; {$data['siteName']} | <a href="{$data['siteUrl']}" style="color: {$primary};">Shop Now</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Process pending review requests and send emails
     */
    public function processPendingRequests(int $limit = 50): int
    {
        $reviewModel = new \App\Models\Review();
        $requests = $reviewModel->getPendingReviewRequests($limit);

        $sent = 0;
        foreach ($requests as $request) {
            if ($this->sendReviewRequest($request)) {
                $reviewModel->markRequestSent($request['id']);
                $sent++;

                // Small delay to avoid overwhelming mail server
                usleep(100000); // 100ms
            }
        }

        return $sent;
    }

}
