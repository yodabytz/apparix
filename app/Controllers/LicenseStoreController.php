<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\License;

class LicenseStoreController extends Controller
{
    /**
     * License pricing tiers
     */
    private array $tiers = [
        'standard' => [
            'name' => 'Standard',
            'code' => 'S',
            'price' => 4900, // cents
            'price_display' => '$49',
            'period' => 'one-time',
            'features' => [
                '100 products',
                '500 orders/month',
                '1 admin user',
                'Custom themes',
                'Coupons & discounts',
                'Newsletter',
                'Remove branding',
                'Community support'
            ],
            'popular' => false
        ],
        'professional' => [
            'name' => 'Professional',
            'code' => 'P',
            'price' => 9900, // cents
            'price_display' => '$99',
            'period' => 'one-time',
            'features' => [
                '1,000 products',
                '5,000 orders/month',
                '5 admin users',
                'All Standard features',
                'API access',
                'Advanced analytics',
                'Bulk import/export',
                'Email support'
            ],
            'popular' => true
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'code' => 'E',
            'price' => 24900, // cents
            'price_display' => '$249',
            'period' => 'one-time',
            'features' => [
                'Unlimited products',
                'Unlimited orders',
                'Unlimited admin users',
                'All Professional features',
                'Abandoned cart recovery',
                'Multi-currency support',
                'Priority support',
                'Custom development'
            ],
            'popular' => false
        ]
    ];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Display pricing page
     */
    public function pricing(): void
    {
        $this->render('license/pricing', [
            'title' => 'Pricing - Apparix',
            'tiers' => $this->tiers,
            'stripePublicKey' => $_ENV['STRIPE_PUBLIC_KEY'] ?? ''
        ]);
    }

    /**
     * Create checkout session for license purchase
     */
    public function createCheckout(): void
    {
        $this->requireValidCSRF();

        $tier = $this->post('tier');
        $email = trim($this->post('email', ''));
        $domain = trim($this->post('domain', ''));

        // Validate tier
        if (!isset($this->tiers[$tier])) {
            $this->json(['error' => 'Invalid license tier'], 400);
            return;
        }

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['error' => 'Please enter a valid email address'], 400);
            return;
        }

        $tierData = $this->tiers[$tier];

        try {
            \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

            // Create Stripe Checkout Session
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => "Apparix {$tierData['name']} License",
                            'description' => "One-time license for Apparix E-Commerce Platform",
                        ],
                        'unit_amount' => $tierData['price'],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'customer_email' => $email,
                'success_url' => appUrl() . '/license/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => appUrl() . '/pricing',
                'metadata' => [
                    'tier' => $tier,
                    'edition_code' => $tierData['code'],
                    'email' => $email,
                    'domain' => $domain ?: 'wildcard',
                ],
            ]);

            $this->json([
                'success' => true,
                'sessionId' => $session->id,
                'url' => $session->url
            ]);

        } catch (\Exception $e) {
            error_log("License checkout error: " . $e->getMessage());
            $this->json(['error' => 'Failed to create checkout session'], 500);
        }
    }

    /**
     * Handle successful purchase
     */
    public function success(): void
    {
        $sessionId = $_GET['session_id'] ?? '';

        if (!$sessionId) {
            $this->redirect('/pricing');
            return;
        }

        try {
            $db = Database::getInstance();

            // Check if license was already generated for this session (idempotency)
            $existing = $db->selectOne(
                "SELECT * FROM license_purchases WHERE stripe_session_id = ?",
                [$sessionId]
            );

            if ($existing) {
                // Already processed - show existing license
                $domainDisplay = ($existing['domain'] && $existing['domain'] !== 'wildcard')
                    ? $existing['domain']
                    : null;

                $this->render('license/success', [
                    'title' => 'License Purchased - Apparix',
                    'licenseKey' => $existing['license_key'],
                    'email' => $existing['email'],
                    'tier' => $this->tiers[$this->getEditionName($existing['edition'])]['name'] ?? 'Standard',
                    'domain' => $domainDisplay
                ]);
                return;
            }

            \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
            $session = \Stripe\Checkout\Session::retrieve($sessionId);

            if ($session->payment_status !== 'paid') {
                setFlash('error', 'Payment was not completed');
                $this->redirect('/pricing');
                return;
            }

            // Get metadata
            $email = $session->metadata->email ?? $session->customer_email;
            $domain = $session->metadata->domain ?? null;
            $editionCode = $session->metadata->edition_code ?? 'S';
            $tier = $session->metadata->tier ?? 'standard';

            // Normalize domain
            $domainForLicense = ($domain && $domain !== 'wildcard') ? $domain : null;

            // Generate license key
            $licenseKey = License::generate($editionCode, $domainForLicense);

            // Store license in database
            $db->insert(
                "INSERT INTO license_purchases (email, license_key, edition, domain, stripe_session_id, amount, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())",
                [$email, $licenseKey, $editionCode, $domainForLicense, $sessionId, $session->amount_total / 100]
            );

            // Send license email
            $this->sendLicenseEmail($email, $licenseKey, $tier, $domainForLicense);

            // Show success page
            $this->render('license/success', [
                'title' => 'License Purchased - Apparix',
                'licenseKey' => $licenseKey,
                'email' => $email,
                'tier' => $this->tiers[$tier]['name'] ?? 'Standard',
                'domain' => $domainForLicense
            ]);

        } catch (\Exception $e) {
            error_log("License success error: " . $e->getMessage());
            setFlash('error', 'There was an issue processing your purchase. Please contact support.');
            $this->redirect('/pricing');
        }
    }

    /**
     * Send license key via email
     */
    private function sendLicenseEmail(string $email, string $licenseKey, string $tier, ?string $domain): void
    {
        $tierName = $this->tiers[$tier]['name'] ?? 'Standard';
        $domainDisplay = $domain ?: 'Any domain (wildcard)';

        $subject = "Your Apparix {$tierName} License Key";

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; padding: 20px 0; border-bottom: 2px solid #f0f0f0; }
        .logo { font-size: 28px; font-weight: bold; color: #8B5CF6; }
        .content { padding: 30px 0; }
        .license-box { background: #f8f9fa; border: 2px dashed #8B5CF6; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0; }
        .license-key { font-family: monospace; font-size: 18px; font-weight: bold; color: #333; letter-spacing: 1px; word-break: break-all; }
        .info-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .info-table td { padding: 10px; border-bottom: 1px solid #eee; }
        .info-table td:first-child { font-weight: bold; width: 140px; }
        .steps { background: #e8f4fd; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .steps h3 { margin-top: 0; color: #0066cc; }
        .steps ol { margin: 0; padding-left: 20px; }
        .steps li { margin-bottom: 8px; }
        .footer { text-align: center; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 14px; }
        .btn { display: inline-block; background: #8B5CF6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Apparix</div>
            <p style="color: #666; margin: 5px 0 0;">E-Commerce Platform</p>
        </div>

        <div class="content">
            <h2>Thank You for Your Purchase!</h2>
            <p>Your Apparix {$tierName} license is ready. Here's your license key:</p>

            <div class="license-box">
                <div class="license-key">{$licenseKey}</div>
            </div>

            <table class="info-table">
                <tr>
                    <td>Edition:</td>
                    <td>{$tierName}</td>
                </tr>
                <tr>
                    <td>Domain:</td>
                    <td>{$domainDisplay}</td>
                </tr>
                <tr>
                    <td>License Type:</td>
                    <td>Perpetual (one-time purchase)</td>
                </tr>
            </table>

            <div class="steps">
                <h3>How to Activate</h3>
                <ol>
                    <li>Open your Apparix installation's <code>.env</code> file</li>
                    <li>Find the line <code>LICENSE_KEY=</code></li>
                    <li>Add your license key: <code>LICENSE_KEY={$licenseKey}</code></li>
                    <li>Save the file and refresh your admin panel</li>
                </ol>
            </div>

            <p>
                <strong>Need Help?</strong><br>
                Visit our documentation at <a href="https://apparix.app/docs">apparix.app/docs</a> or
                contact us at <a href="mailto:support@apparix.app">support@apparix.app</a>
            </p>

            <p style="text-align: center;">
                <a href="https://apparix.app/docs/installation" class="btn">Installation Guide</a>
            </p>
        </div>

        <div class="footer">
            <p>
                <strong>Apparix E-Commerce Platform</strong><br>
                <a href="https://apparix.app">apparix.app</a>
            </p>
            <p style="font-size: 12px; color: #999;">
                Keep this email safe - it contains your license key.<br>
                &copy; 2026 Vibrix Media. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
HTML;

        $plainText = <<<TEXT
APPARIX LICENSE KEY

Thank you for purchasing Apparix {$tierName}!

Your License Key:
{$licenseKey}

Edition: {$tierName}
Domain: {$domainDisplay}

HOW TO ACTIVATE:
1. Open your .env file
2. Add: LICENSE_KEY={$licenseKey}
3. Save and refresh your admin panel

Documentation: https://apparix.app/docs
Support: support@apparix.app

---
Apparix E-Commerce Platform
https://apparix.app
TEXT;

        // Send email using configured SMTP
        $this->sendEmail($email, $subject, $html, $plainText);
    }

    /**
     * Send email via SMTP
     */
    private function sendEmail(string $to, string $subject, string $htmlBody, string $textBody): bool
    {
        $db = Database::getInstance();

        // Get email settings from database or .env
        $settings = [];
        $dbSettings = $db->select("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'mail_%'");
        foreach ($dbSettings as $s) {
            $settings[$s['setting_key']] = $s['setting_value'];
        }

        $smtpHost = $settings['mail_smtp_host'] ?? $_ENV['MAIL_HOST'] ?? '';
        $smtpPort = $settings['mail_smtp_port'] ?? $_ENV['MAIL_PORT'] ?? 587;
        $smtpUser = $settings['mail_smtp_user'] ?? $_ENV['MAIL_USER'] ?? '';
        $smtpPass = $settings['mail_smtp_pass'] ?? $_ENV['MAIL_PASS'] ?? '';
        $fromEmail = $settings['mail_from_email'] ?? $_ENV['MAIL_FROM'] ?? 'noreply@apparix.app';
        $fromName = $settings['mail_from_name'] ?? $_ENV['MAIL_FROM_NAME'] ?? 'Apparix';

        if (empty($smtpHost) || empty($smtpUser)) {
            error_log("SMTP not configured - cannot send license email to $to");
            return false;
        }

        try {
            // Use PHPMailer if available, otherwise fallback to mail()
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = $smtpHost;
                $mail->SMTPAuth = true;
                $mail->Username = $smtpUser;
                $mail->Password = $smtpPass;
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = (int)$smtpPort;

                $mail->setFrom($fromEmail, $fromName);
                $mail->addAddress($to);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $htmlBody;
                $mail->AltBody = $textBody;

                $mail->send();
                return true;
            }

            // Fallback to PHP mail()
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=UTF-8',
                "From: {$fromName} <{$fromEmail}>",
                'X-Mailer: Apparix'
            ];

            return mail($to, $subject, $htmlBody, implode("\r\n", $headers));

        } catch (\Exception $e) {
            error_log("Failed to send license email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Resend license key
     */
    public function resend(): void
    {
        $this->requireValidCSRF();

        $email = trim($this->post('email', ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['error' => 'Please enter a valid email address'], 400);
            return;
        }

        $db = Database::getInstance();
        $purchases = $db->select(
            "SELECT * FROM license_purchases WHERE email = ? ORDER BY created_at DESC",
            [$email]
        );

        if (empty($purchases)) {
            $this->json(['error' => 'No licenses found for this email address'], 404);
            return;
        }

        // Send all licenses for this email
        foreach ($purchases as $purchase) {
            $tierName = $this->getEditionName($purchase['edition']);
            $this->sendLicenseEmail(
                $purchase['email'],
                $purchase['license_key'],
                strtolower($tierName),
                $purchase['domain']
            );
        }

        $this->json([
            'success' => true,
            'message' => 'License key(s) sent to ' . $email
        ]);
    }

    /**
     * Get edition name from code
     */
    private function getEditionName(string $code): string
    {
        $names = [
            'S' => 'standard',
            'P' => 'professional',
            'E' => 'enterprise',
            'D' => 'developer',
            'U' => 'unlimited'
        ];
        return $names[$code] ?? 'standard';
    }
}
