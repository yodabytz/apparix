<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ReCaptcha;

class PageController extends Controller
{
    /**
     * Privacy Policy page
     */
    public function privacy(): void
    {
        $this->render('pages/privacy', [
            'title' => 'Privacy Policy'
        ]);
    }

    /**
     * Terms of Service page
     */
    public function terms(): void
    {
        $this->render('pages/terms', [
            'title' => 'Terms of Service'
        ]);
    }

    /**
     * Contact page
     */
    public function contact(): void
    {
        $this->render('pages/contact', [
            'title' => 'Contact Us'
        ]);
    }

    /**
     * Process contact form
     */
    public function sendContact(): void
    {
        $this->requireValidCSRF();

        // Verify reCAPTCHA (required)
        $recaptchaToken = $this->post('recaptcha_token', '');
        if (empty($recaptchaToken)) {
            setFlash('error', 'Security verification failed. Please wait a moment and try again.');
            $this->redirect('/contact');
            return;
        }

        $recaptchaResult = ReCaptcha::verify($recaptchaToken, 'contact');
        if (!$recaptchaResult['success']) {
            error_log('reCAPTCHA verification failed: ' . json_encode($recaptchaResult));
            setFlash('error', 'Security verification failed. Please try again.');
            $this->redirect('/contact');
            return;
        }

        $name = trim($this->post('name', ''));
        $email = trim($this->post('email', ''));
        $subject = trim($this->post('subject', ''));
        $message = trim($this->post('message', ''));

        // Validation
        if (empty($name) || empty($email) || empty($message)) {
            setFlash('error', 'Please fill in all required fields');
            $this->redirect('/contact');
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlash('error', 'Please enter a valid email address');
            $this->redirect('/contact');
            return;
        }

        // Send email
        $to = '' . storeEmail() . '';
        $emailSubject = 'Contact Form: ' . ($subject ?: 'General Inquiry');
        $body = "Name: {$name}\n";
        $body .= "Email: {$email}\n";
        $body .= "Subject: {$subject}\n\n";
        $body .= "Message:\n{$message}";

        $sent = sendEmail($to, $emailSubject, $body, [
            'html' => false,
            'replyTo' => $email
        ]);

        if ($sent) {
            // Send confirmation email to the sender
            $this->sendContactConfirmation($name, $email, $subject, $message);
            setFlash('success', 'Thank you for your message! We will get back to you soon.');
        } else {
            setFlash('error', 'Failed to send message. Please try again or email us directly.');
        }

        $this->redirect('/contact');
    }

    /**
     * Send confirmation email to contact form sender
     */
    private function sendContactConfirmation(string $name, string $email, string $subject, string $message): void
    {
        $confirmSubject = 'We received your message - ' . appName() . '';

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; background-color: #fdf2f8;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #fdf2f8;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(255, 104, 197, 0.15);">
                    <!-- Header with Logo - pinkish-white gradient like newsletter -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #FFE4F3 0%, #FFFFFF 40%, #FFFFFF 60%, #FFE4F3 100%); padding: 40px 40px 20px; text-align: center;">
                            <a href="' . appUrl() . '" style="text-decoration: none;">
                                <img src="' . appUrl() . '/assets/images/placeholder.png" alt="' . appName() . '" width="280" style="max-width: 280px; width: 100%; height: auto; display: block; margin: 0 auto;">
                            </a>
                        </td>
                    </tr>
                    <!-- Tagline -->
                    <tr>
                        <td align="center" style="background: linear-gradient(135deg, #FFE4F3 0%, #FFFFFF 40%, #FFFFFF 60%, #FFE4F3 100%); padding: 0 40px 30px 40px;">
                            <p style="margin: 0; font-family: Georgia, serif; font-size: 14px; font-style: italic; color: #FF68C5; letter-spacing: 0.5px;">
                                Unique Handmade Gifts &amp; Custom Creations
                            </p>
                        </td>
                    </tr>

                    <!-- Pink divider line -->
                    <tr>
                        <td style="height: 3px; background: linear-gradient(90deg, #FFE4F3, #FF68C5, #FFE4F3);"></td>
                    </tr>

                    <!-- Main Content -->
                    <tr>
                        <td style="padding: 35px 40px 0 40px;">
                            <p style="margin: 0; font-size: 18px; color: #1f2937; line-height: 1.5;">
                                Hi <strong style="color: #FF68C5;">' . htmlspecialchars($name) . '</strong>,
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 25px 40px 40px 40px; font-size: 16px; line-height: 1.7; color: #4b5563;">
                            <p style="margin: 0 0 20px;">Thank you so much for reaching out to us! We\'ve received your message and our team will get back to you within <strong>24-48 hours</strong>.</p>

                            <!-- Message Box -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 25px 0; background: linear-gradient(135deg, #fdf2f8 0%, #fff 100%); border-radius: 12px; border-left: 4px solid #FF68C5;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="margin: 0 0 12px; color: #FF68C5; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Your Message</p>
                                        <p style="margin: 0 0 10px; color: #1f2937; font-size: 15px;"><strong>Subject:</strong> ' . htmlspecialchars($subject ?: 'General Inquiry') . '</p>
                                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 12px; background: #ffffff; border-radius: 8px;">
                                            <tr>
                                                <td style="padding: 15px;">
                                                    <p style="margin: 0; color: #4b5563; font-size: 15px; line-height: 1.7; white-space: pre-wrap;">' . htmlspecialchars($message) . '</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 25px 0 0;">While you wait, why not explore our latest handcrafted treasures?</p>
                        </td>
                    </tr>

                    <!-- CTA Button -->
                    <tr>
                        <td align="center" style="padding: 0 40px 40px 40px;">
                            <a href="' . appUrl() . '/products" style="display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #FF68C5 0%, #ff4db8 100%); color: #ffffff; text-decoration: none; border-radius: 50px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 15px rgba(255, 104, 197, 0.4);">
                                Shop Our Collection
                            </a>
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="padding: 0 40px;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="height: 1px; background: linear-gradient(90deg, transparent, #fce7f3, #FF68C5, #fce7f3, transparent);"></td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #fdf2f8;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 35px 40px;">
                                        <!-- Social Links -->
                                        ' . $this->getSocialLinksHtml() . '

                                        <p style="margin: 0 0 15px; font-family: Georgia, serif; font-size: 15px; font-style: italic; color: #FF68C5;">
                                            With love, The ' . appName() . ' Team
                                        </p>

                                        <!-- Footer Links -->
                                        <table cellpadding="0" cellspacing="0" style="margin-bottom: 25px;">
                                            <tr>
                                                <td style="padding: 0 10px;">
                                                    <a href="' . appUrl() . '" style="color: #6b7280; font-size: 13px; text-decoration: none;">Website</a>
                                                </td>
                                                <td style="padding: 0 10px;">
                                                    <a href="' . appUrl() . '/products" style="color: #6b7280; font-size: 13px; text-decoration: none;">Shop</a>
                                                </td>
                                                <td style="padding: 0 10px;">
                                                    <a href="' . appUrl() . '/contact" style="color: #6b7280; font-size: 13px; text-decoration: none;">Contact</a>
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Auto-reply notice -->
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="border-top: 1px solid #fce7f3; padding-top: 20px;">
                                                    <p style="margin: 0; font-size: 12px; color: #9ca3af; line-height: 1.6;">
                                                        This is an automated confirmation. Please do not reply to this email.<br>
                                                        &copy; ' . date('Y') . ' ' . appName() . '. All rights reserved.
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
</html>';

        sendEmail($email, $confirmSubject, $html, ['html' => true]);
    }

    /**
     * Test page for help chat widget (temporary - remove after testing)
     */
    public function testHelpChat(): void
    {
        $this->render('pages/test-help-chat', [
            'title' => 'Test Help Chat'
        ]);
    }

    /**
     * Handle support chat API endpoint
     */
    public function supportChat(): void
    {
        header('Content-Type: application/json');

        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            echo json_encode(['error' => 'Invalid request']);
            return;
        }

        $email = trim($input['email'] ?? '');
        $message = trim($input['message'] ?? '');

        // Validation
        if (empty($email) || empty($message)) {
            echo json_encode(['error' => 'Please fill in all fields']);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['error' => 'Please enter a valid email address']);
            return;
        }

        // Rate limiting (simple session-based)
        $lastSent = $_SESSION['last_support_chat'] ?? 0;
        if (time() - $lastSent < 60) {
            echo json_encode(['error' => 'Please wait a minute before sending another message']);
            return;
        }

        // Send email to support
        $to = '' . storeEmail() . '';
        $subject = 'Chat Support Request';
        $body = "Support request from help chat:\n\n";
        $body .= "Email: {$email}\n\n";
        $body .= "Message:\n{$message}";

        $sent = sendEmail($to, $subject, $body, [
            'html' => false,
            'replyTo' => $email
        ]);

        if ($sent) {
            $_SESSION['last_support_chat'] = time();
            // Send confirmation to the customer
            $this->sendChatSupportConfirmation($email, $message);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Failed to send message. Please try again.']);
        }
    }

    /**
     * Send confirmation email for chat support request
     */
    private function sendChatSupportConfirmation(string $email, string $message): void
    {
        $subject = 'We received your message - ' . appName() . '';

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; background-color: #fdf2f8;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #fdf2f8;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(255, 104, 197, 0.15);">
                    <!-- Header with Logo -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #FFE4F3 0%, #FFFFFF 40%, #FFFFFF 60%, #FFE4F3 100%); padding: 40px 40px 20px; text-align: center;">
                            <a href="' . appUrl() . '" style="text-decoration: none;">
                                <img src="' . appUrl() . '/assets/images/placeholder.png" alt="' . appName() . '" width="280" style="max-width: 280px; width: 100%; height: auto; display: block; margin: 0 auto;">
                            </a>
                        </td>
                    </tr>
                    <!-- Tagline -->
                    <tr>
                        <td align="center" style="background: linear-gradient(135deg, #FFE4F3 0%, #FFFFFF 40%, #FFFFFF 60%, #FFE4F3 100%); padding: 0 40px 30px 40px;">
                            <p style="margin: 0; font-family: Georgia, serif; font-size: 14px; font-style: italic; color: #FF68C5; letter-spacing: 0.5px;">
                                Unique Handmade Gifts &amp; Custom Creations
                            </p>
                        </td>
                    </tr>

                    <!-- Pink divider line -->
                    <tr>
                        <td style="height: 3px; background: linear-gradient(90deg, #FFE4F3, #FF68C5, #FFE4F3);"></td>
                    </tr>

                    <!-- Main Content -->
                    <tr>
                        <td style="padding: 35px 40px 0 40px;">
                            <h2 style="margin: 0 0 20px; font-size: 22px; color: #FF68C5;">Thank You for Reaching Out!</h2>
                            <p style="margin: 0 0 20px; font-size: 16px; line-height: 1.7; color: #4b5563;">
                                We\'ve received your message and our support team will get back to you within <strong>24 hours</strong>.
                            </p>

                            <!-- Message Box -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 25px 0; background: linear-gradient(135deg, #fdf2f8 0%, #fff 100%); border-radius: 12px; border-left: 4px solid #FF68C5;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="margin: 0 0 12px; color: #FF68C5; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Your Message</p>
                                        <p style="margin: 0; color: #4b5563; font-size: 15px; line-height: 1.7; white-space: pre-wrap;">' . htmlspecialchars($message) . '</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- CTA Button -->
                    <tr>
                        <td align="center" style="padding: 20px 40px 40px 40px;">
                            <a href="' . appUrl() . '/products" style="display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #FF68C5 0%, #ff4db8 100%); color: #ffffff; text-decoration: none; border-radius: 50px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 15px rgba(255, 104, 197, 0.4);">
                                Continue Shopping
                            </a>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #fdf2f8; padding: 25px 40px; text-align: center;">
                            <p style="margin: 0 0 15px; font-family: Georgia, serif; font-size: 15px; font-style: italic; color: #FF68C5;">
                                With love, The ' . appName() . ' Team
                            </p>
                            <p style="margin: 0; font-size: 12px; color: #9ca3af;">
                                &copy; ' . date('Y') . ' ' . appName() . '. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';

        sendEmail($email, $subject, $html, ['html' => true]);
    }

    /**
     * Generate social links HTML for email templates
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

        return '<table cellpadding="0" cellspacing="0" style="margin-bottom: 20px;">
            <tr>
                <td style="padding: 0 12px;">' . implode('</td><td style="color: #fce7f3; font-size: 14px;">•</td><td style="padding: 0 12px;">', $links) . '</td>
            </tr>
        </table>';
    }
}
