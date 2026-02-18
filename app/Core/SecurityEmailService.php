<?php

namespace App\Core;

class SecurityEmailService
{
    /**
     * Send notification that 2FA was enabled
     */
    public static function send2FAEnabledNotification(string $email, string $name): bool
    {
        $storeName = appName();
        $subject = "[{$storeName}] Two-Factor Authentication Enabled";

        $body = self::buildEmailBody(
            "Two-Factor Authentication Enabled",
            "Hi {$name},",
            "Two-factor authentication has been successfully enabled on your admin account. " .
            "You will now need to enter a verification code from your authenticator app each time you sign in.",
            "If you did not make this change, please contact the site administrator immediately and change your password.",
            $email
        );

        return sendEmail($email, $subject, $body);
    }

    /**
     * Send notification that 2FA was disabled
     */
    public static function send2FADisabledNotification(string $email, string $name): bool
    {
        $storeName = appName();
        $subject = "[{$storeName}] Two-Factor Authentication Disabled";

        $body = self::buildEmailBody(
            "Two-Factor Authentication Disabled",
            "Hi {$name},",
            "Two-factor authentication has been disabled on your admin account. " .
            "Your account is now protected by password only.",
            "If you did not make this change, your account may be compromised. " .
            "Please change your password immediately and re-enable two-factor authentication.",
            $email
        );

        return sendEmail($email, $subject, $body);
    }

    /**
     * Send notification about a new device login
     */
    public static function sendNewDeviceNotification(string $email, string $name, string $ip, string $userAgent): bool
    {
        $storeName = appName();
        $subject = "[{$storeName}] New Device Sign-In";

        $time = date('F j, Y \a\t g:i A T');

        $body = self::buildEmailBody(
            "New Device Sign-In Detected",
            "Hi {$name},",
            "Your admin account was just accessed from a new device or location:" .
            "<br><br>" .
            "<strong>IP Address:</strong> {$ip}<br>" .
            "<strong>Browser:</strong> " . htmlspecialchars(self::parseUserAgent($userAgent)) . "<br>" .
            "<strong>Time:</strong> {$time}",
            "If this was you, you can safely ignore this email. " .
            "If you don't recognize this activity, please change your password immediately.",
            $email
        );

        return sendEmail($email, $subject, $body);
    }

    /**
     * Build a consistent security email HTML body
     */
    private static function buildEmailBody(string $heading, string $greeting, string $message, string $warning, string $recipientEmail): string
    {
        $storeName = htmlspecialchars(appName());
        $year = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#f4f4f7;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f7;padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 4px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="background-color:#1a1a2e;padding:30px;text-align:center;">
                            <h1 style="color:#ffffff;margin:0;font-size:20px;">{$storeName}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:40px 30px;">
                            <h2 style="color:#1a1a2e;margin:0 0 20px;font-size:22px;">{$heading}</h2>
                            <p style="color:#4a5568;font-size:15px;line-height:1.6;margin:0 0 15px;">{$greeting}</p>
                            <p style="color:#4a5568;font-size:15px;line-height:1.6;margin:0 0 25px;">{$message}</p>
                            <div style="background-color:#fff3cd;border:1px solid #ffc107;border-radius:6px;padding:15px;margin:20px 0;">
                                <p style="color:#856404;font-size:14px;line-height:1.5;margin:0;">
                                    <strong>&#9888; Security Notice:</strong> {$warning}
                                </p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#f8f9fa;padding:20px 30px;text-align:center;border-top:1px solid #e9ecef;">
                            <p style="color:#6c757d;font-size:12px;margin:0;">
                                This is an automated security notification from {$storeName}.<br>
                                &copy; {$year} {$storeName}. All rights reserved.
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
     * Parse user agent into a human-readable browser name
     */
    private static function parseUserAgent(string $userAgent): string
    {
        if (empty($userAgent)) {
            return 'Unknown Browser';
        }

        $browser = 'Unknown Browser';
        $os = 'Unknown OS';

        if (preg_match('/Firefox\/[\d.]+/', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Edg\/[\d.]+/', $userAgent)) {
            $browser = 'Microsoft Edge';
        } elseif (preg_match('/Chrome\/[\d.]+/', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Safari\/[\d.]+/', $userAgent) && !preg_match('/Chrome/', $userAgent)) {
            $browser = 'Safari';
        }

        if (preg_match('/Windows/', $userAgent)) {
            $os = 'Windows';
        } elseif (preg_match('/Macintosh/', $userAgent)) {
            $os = 'macOS';
        } elseif (preg_match('/Linux/', $userAgent)) {
            $os = 'Linux';
        } elseif (preg_match('/iPhone|iPad/', $userAgent)) {
            $os = 'iOS';
        } elseif (preg_match('/Android/', $userAgent)) {
            $os = 'Android';
        }

        return "{$browser} on {$os}";
    }
}
