<?php
/**
 * Email Template Preview
 * Access at: /email-preview.php?template=shipping or /email-preview.php?template=newsletter
 */

$template = $_GET['template'] ?? 'shipping';

if ($template === 'newsletter') {
    // Load newsletter template
    $html = file_get_contents(__DIR__ . '/../newsletter/templates/newsletter.html');

    // Replace placeholders with sample data
    $html = str_replace('{{SUBJECT}}', 'New Arrivals This Week!', $html);
    $html = str_replace('{{SUBSCRIBER_NAME}}', 'Sarah', $html);
    $html = str_replace('{{CONTENT}}', '<p>We\'re thrilled to announce some exciting new additions to our collection! This week, we\'ve added beautiful hand-painted ceramic mugs, custom embroidered tote bags, and adorable personalized baby blankets.</p><p>Each piece is crafted with love and attention to detail, making them perfect gifts for your loved ones or a special treat for yourself.</p><h2>Featured This Week</h2><p>Our new line of <strong>Spring Garden</strong> ceramics is here! These gorgeous pieces feature delicate floral patterns hand-painted by our talented artists.</p>', $html);
    $html = str_replace('{{UNSUBSCRIBE_URL}}', '#', $html);

} else {
    // Shipping template
    $data = [
        'customer_name' => 'Sarah',
        'order_number' => 'LPS-ABC12345',
        'carrier_name' => 'USPS',
        'tracking_number' => '9400111899223456789012',
        'tracking_url' => 'https://tools.usps.com/go/TrackConfirmAction?tLabels=9400111899223456789012',
        'estimated_delivery' => 'December 28, 2024'
    ];

    $trackButton = '<a href="' . htmlspecialchars($data['tracking_url']) . '" style="display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #FF68C5 0%, #ff4db8 100%); color: #ffffff !important; text-decoration: none !important; border-radius: 50px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 15px rgba(255, 104, 197, 0.4);">Track Your Package</a>';

    $deliveryInfo = '<tr>
                        <td style="padding: 12px 0; color: #6b7280; font-size: 15px; border-top: 1px solid #f3f4f6;">Estimated Delivery:</td>
                        <td style="padding: 12px 0; color: #1f2937; font-size: 15px; font-weight: 600; text-align: right; border-top: 1px solid #f3f4f6;">' . htmlspecialchars($data['estimated_delivery']) . '</td>
                    </tr>';

    $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Your Order Has Shipped!</title>
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
        body { margin: 0; padding: 0; }
        table { border-spacing: 0; }
        td { padding: 0; }
        img { border: 0; }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #fdf2f8; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">

    <!-- Wrapper Table -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #fdf2f8;">
        <tr>
            <td align="center" style="padding: 40px 20px;">

                <!-- Main Container -->
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(255, 104, 197, 0.15);">

                    <!-- HEADER with decorative circles -->
                    <tr>
                        <td style="position: relative; background: linear-gradient(135deg, #FFE4F3 0%, #FFFFFF 40%, #FFFFFF 60%, #FFE4F3 100%); padding: 0; overflow: hidden;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <!-- Decorative circles -->
                                <tr>
                                    <td style="position: relative; height: 0;">
                                        <div style="position: absolute; width: 150px; height: 150px; background: #FF68C5; opacity: 0.08; border-radius: 50%; top: -50px; left: -30px;"></div>
                                        <div style="position: absolute; width: 100px; height: 100px; background: #FF94C8; opacity: 0.08; border-radius: 50%; top: 20px; right: -20px;"></div>
                                        <div style="position: absolute; width: 80px; height: 80px; background: #FF68C5; opacity: 0.06; border-radius: 50%; bottom: -40px; left: 40%;"></div>
                                    </td>
                                </tr>
                                <!-- Logo area -->
                                <tr>
                                    <td align="center" style="padding: 40px 40px 20px 40px;">
                                        <a href="https://apparix.vibrixmedia.com" style="text-decoration: none;">
                                            <img src="https://apparix.vibrixmedia.com/assets/images/placeholder.png" alt="Lily\'s Pad Studio" width="280" style="max-width: 280px; width: 100%; height: auto; display: block;">
                                        </a>
                                    </td>
                                </tr>
                                <!-- Tagline -->
                                <tr>
                                    <td align="center" style="padding: 0 40px 30px 40px;">
                                        <p style="margin: 0; font-family: \'Playfair Display\', Georgia, serif; font-size: 14px; font-style: italic; color: #FF68C5; letter-spacing: 0.5px;">
                                            Unique Handmade Gifts &amp; Custom Creations
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

                    <!-- TITLE BANNER -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #FF68C5 0%, #ff4db8 100%); padding: 22px 40px; text-align: center;">
                            <h1 style="margin: 0; font-family: \'Playfair Display\', Georgia, serif; font-size: 26px; font-weight: 600; color: #ffffff; letter-spacing: 0.5px;">
                                Your Order Has Shipped!
                            </h1>
                        </td>
                    </tr>

                    <!-- MAIN CONTENT -->
                    <tr>
                        <td style="padding: 40px;">

                            <!-- Greeting -->
                            <p style="margin: 0 0 20px 0; font-size: 18px; color: #1f2937; line-height: 1.5;">
                                Hi <strong style="color: #FF68C5;">' . htmlspecialchars($data['customer_name']) . '</strong>,
                            </p>

                            <p style="margin: 0 0 30px 0; font-size: 16px; color: #4b5563; line-height: 1.7;">
                                Great news! Your order <strong style="color: #1f2937;">' . htmlspecialchars($data['order_number']) . '</strong> is on its way to you.
                            </p>

                            <!-- Shipping Details Box -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb; margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 20px 25px; border-bottom: 2px solid #FF68C5;">
                                        <h2 style="margin: 0; font-family: \'Playfair Display\', Georgia, serif; font-size: 18px; font-weight: 600; color: #1f2937;">
                                            Shipping Details
                                        </h2>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 20px 25px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding: 12px 0; color: #6b7280; font-size: 15px;">Carrier:</td>
                                                <td style="padding: 12px 0; color: #1f2937; font-size: 15px; font-weight: 600; text-align: right;">' . htmlspecialchars($data['carrier_name']) . '</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 12px 0; color: #6b7280; font-size: 15px; border-top: 1px solid #f3f4f6;">Tracking Number:</td>
                                                <td style="padding: 12px 0; color: #1f2937; font-size: 15px; font-weight: 600; text-align: right; font-family: \'Courier New\', monospace; border-top: 1px solid #f3f4f6;">' . htmlspecialchars($data['tracking_number']) . '</td>
                                            </tr>
                                            ' . $deliveryInfo . '
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA Button -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 10px 0 30px 0;">
                                        ' . $trackButton . '
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0; font-size: 14px; color: #6b7280; line-height: 1.6; text-align: center;">
                                You can also copy the tracking number and paste it on the carrier\'s website.
                            </p>

                        </td>
                    </tr>

                    <!-- FOOTER -->
                    <tr>
                        <td style="background-color: #fdf2f8; padding: 30px 40px; text-align: center; border-top: 1px solid #fce7f3;">
                            <p style="margin: 0 0 15px 0; font-family: \'Playfair Display\', Georgia, serif; font-size: 16px; font-style: italic; color: #FF68C5;">
                                Thank you for shopping with us!
                            </p>
                            <p style="margin: 0; font-size: 13px; color: #9ca3af; line-height: 1.6;">
                                Questions? Contact us at <a href="mailto:hello@apparix.vibrixmedia.com" style="color: #FF68C5; text-decoration: none;">hello@apparix.vibrixmedia.com</a>
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>';
}

// Output with navigation
?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Preview - Apparix</title>
    <style>
        body { margin: 0; padding: 0; background: #333; }
        .nav { background: #1a1a2e; padding: 15px 20px; display: flex; gap: 20px; align-items: center; }
        .nav a { color: #fff; text-decoration: none; padding: 8px 16px; border-radius: 4px; font-size: 14px; }
        .nav a:hover { background: rgba(255,255,255,0.1); }
        .nav a.active { background: #FF68C5; }
        .nav span { color: #9ca3af; font-size: 14px; }
        .preview { border: none; }
    </style>
</head>
<body>
    <div class="nav">
        <span>Preview:</span>
        <a href="?template=shipping" class="<?= $template === 'shipping' ? 'active' : '' ?>">Shipping Notification</a>
        <a href="?template=newsletter" class="<?= $template === 'newsletter' ? 'active' : '' ?>">Newsletter</a>
    </div>
    <iframe class="preview" width="100%" height="<?= $template === 'newsletter' ? '900' : '850' ?>" srcdoc="<?= htmlspecialchars($html) ?>"></iframe>
</body>
</html>
