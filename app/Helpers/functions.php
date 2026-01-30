<?php

/**
 * Global helper functions
 */

/**
 * Escape HTML entities
 */
function escape(string $string): string
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Format price as currency
 */
function formatPrice(float $price): string
{
    return '$' . number_format($price, 2);
}

/**
 * Generate URL slug from string
 */
function slug(string $string): string
{
    $string = mb_strtolower(trim($string), 'UTF-8');
    $string = preg_replace('/[^\w\s-]/', '', $string);
    $string = preg_replace('/[-\s]+/', '-', $string);
    return trim($string, '-');
}

/**
 * Get CSRF token field
 */
function csrfField(): string
{
    return App\Core\CSRF::field();
}

/**
 * Get CSRF token value
 */
function csrfToken(): string
{
    return App\Core\CSRF::getToken();
}

/**
 * Get CSRF token value (alias for JavaScript)
 */
function csrf_token(): string
{
    return App\Core\CSRF::getToken();
}

/**
 * Check if user is authenticated and return user data
 */
function auth(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'] ?? '',
        'name' => $_SESSION['user_name'] ?? ''
    ];
}

/**
 * Get current user
 */
function user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    return $_SESSION['user'] ?? null;
}

/**
 * Get flash message
 */
function getFlash(string $key): ?string
{
    $message = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $message;
}

/**
 * Set flash message
 */
function setFlash(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

/**
 * Redirect with flash message
 */
function redirectWithFlash(string $url, string $type, string $message): void
{
    setFlash($type, $message);
    header('Location: ' . $url);
    exit;
}

/**
 * Log an error
 */
function logError(string $message): void
{
    $logFile = __DIR__ . '/../../storage/logs/error.log';
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp] $message\n", 3, $logFile);
}

/**
 * Get file upload directory
 */
function getUploadDir(): string
{
    $dir = __DIR__ . '/../../storage/uploads/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

/**
 * Validate email
 */
function isValidEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate random token
 */
function generateToken(int $length = 32): string
{
    return bin2hex(random_bytes($length / 2));
}

/**
 * Convert country code to flag emoji
 */
function getFlagEmoji(?string $countryCode): string
{
    if (!$countryCode || strlen($countryCode) !== 2) {
        return '';
    }
    $countryCode = strtoupper($countryCode);
    $offset = 127397; // Regional indicator symbol "A" (U+1F1E6) - 65
    $flag = mb_chr(ord($countryCode[0]) + $offset) . mb_chr(ord($countryCode[1]) + $offset);
    return $flag;
}

/**
 * Get a setting value from the database
 * Uses caching for performance
 */
function setting(string $key, mixed $default = null): mixed
{
    static $settingModel = null;

    // Check if database is available (for installer compatibility)
    try {
        if ($settingModel === null) {
            $settingModel = new App\Models\Setting();
        }
        return $settingModel->get($key, $default);
    } catch (\Exception $e) {
        // Database not available (during install)
        return $default;
    }
}

/**
 * Get the active theme
 */
function activeTheme(): ?array
{
    static $themeModel = null;

    try {
        if ($themeModel === null) {
            $themeModel = new App\Models\Theme();
        }
        return $themeModel->getActive();
    } catch (\Exception $e) {
        return null;
    }
}

/**
 * Get app/store name from config
 * Prefers database setting, falls back to .env
 */
function appName(): string
{
    $dbName = setting('store_name');
    if ($dbName && $dbName !== 'My Store') {
        return $dbName;
    }
    return $_ENV['APP_NAME'] ?? 'My Store';
}

/**
 * Get app URL from config
 */
function appUrl(): string
{
    return $_ENV['APP_URL'] ?? 'https://apparix.vibrixmedia.com';
}

/**
 * Get store logo path
 */
function storeLogo(): ?string
{
    return setting('store_logo') ?: null;
}

/**
 * Get store email address
 */
function storeEmail(): string
{
    return setting('store_email') ?: $_ENV['ADMIN_EMAIL'] ?? 'support@example.com';
}

/**
 * Get email "From" address (for outgoing emails)
 * Reads from database settings with .env fallback
 */
function mailFromEmail(): string
{
    $dbValue = setting('mail_from_email');
    if (!empty($dbValue)) {
        return $dbValue;
    }
    return $_ENV['MAIL_FROM'] ?? storeEmail();
}

/**
 * Get email "From" name (for outgoing emails)
 * Reads from database settings with .env fallback
 */
function mailFromName(): string
{
    $dbValue = setting('mail_from_name');
    if (!empty($dbValue)) {
        return $dbValue;
    }
    return $_ENV['MAIL_FROM_NAME'] ?? appName();
}

/**
 * Get admin notification email
 * Reads from database settings with .env fallback
 */
function adminNotificationEmail(): string
{
    $dbValue = setting('admin_notification_email');
    if (!empty($dbValue)) {
        return $dbValue;
    }
    return $_ENV['ADMIN_EMAIL'] ?? storeEmail();
}

/**
 * Send email using configured method (SMTP or PHP mail)
 * Reads settings from database with .env fallback
 *
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $body Email body (can be HTML)
 * @param array $options Optional settings (html, replyTo, headers)
 * @return bool True if sent successfully
 */
function sendEmail(string $to, string $subject, string $body, array $options = []): bool
{
    $fromEmail = mailFromEmail();
    $fromName = mailFromName();
    $isHtml = $options['html'] ?? true;
    $replyTo = $options['replyTo'] ?? $fromEmail;

    // Check if SMTP is enabled
    $smtpEnabled = setting('smtp_enabled') === '1';

    if ($smtpEnabled) {
        return sendSmtpEmail($to, $subject, $body, $fromEmail, $fromName, $isHtml, $replyTo);
    }

    // Use PHP mail()
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: ' . ($isHtml ? 'text/html' : 'text/plain') . '; charset=UTF-8',
        'From: ' . $fromName . ' <' . $fromEmail . '>',
        'Reply-To: ' . $replyTo,
        'X-Mailer: PHP/' . phpversion()
    ];

    if (!empty($options['headers'])) {
        $headers = array_merge($headers, $options['headers']);
    }

    return @mail($to, $subject, $body, implode("\r\n", $headers), '-f ' . $fromEmail);
}

/**
 * Send email via SMTP
 * @internal Used by sendEmail()
 */
function sendSmtpEmail(string $to, string $subject, string $body, string $fromEmail, string $fromName, bool $isHtml = true, string $replyTo = ''): bool
{
    $host = setting('smtp_host') ?: $_ENV['MAIL_HOST'] ?? '';
    $port = (int)(setting('smtp_port') ?: $_ENV['MAIL_PORT'] ?? 587);
    $username = setting('smtp_username') ?: '';
    $password = setting('smtp_password') ?: '';
    $encryption = setting('smtp_encryption') ?: 'tls';

    if (empty($host)) {
        error_log("SMTP error: No host configured");
        return false;
    }

    $timeout = 30;
    $errno = 0;
    $errstr = '';

    try {
        // For TLS, connect without encryption first, then upgrade
        if ($encryption === 'tls') {
            $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
        } elseif ($encryption === 'ssl') {
            $socket = @fsockopen('ssl://' . $host, $port, $errno, $errstr, $timeout);
        } else {
            $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
        }

        if (!$socket) {
            error_log("SMTP error: Could not connect to {$host}:{$port} - {$errstr}");
            return false;
        }

        stream_set_timeout($socket, $timeout);

        // Read greeting
        $response = fgets($socket, 515);
        if (substr($response, 0, 3) !== '220') {
            fclose($socket);
            error_log("SMTP error: Unexpected greeting - " . trim($response));
            return false;
        }

        // EHLO
        fputs($socket, "EHLO " . gethostname() . "\r\n");
        smtpReadResponse($socket);

        // STARTTLS for TLS connections
        if ($encryption === 'tls') {
            fputs($socket, "STARTTLS\r\n");
            $response = smtpReadResponse($socket);
            if (substr($response, 0, 3) !== '220') {
                fclose($socket);
                error_log("SMTP error: STARTTLS failed - " . trim($response));
                return false;
            }

            $crypto = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if (!$crypto) {
                fclose($socket);
                error_log("SMTP error: Could not enable TLS encryption");
                return false;
            }

            // EHLO again after TLS
            fputs($socket, "EHLO " . gethostname() . "\r\n");
            smtpReadResponse($socket);
        }

        // AUTH LOGIN
        if (!empty($username) && !empty($password)) {
            fputs($socket, "AUTH LOGIN\r\n");
            $response = smtpReadResponse($socket);
            if (substr($response, 0, 3) !== '334') {
                fclose($socket);
                error_log("SMTP error: AUTH LOGIN failed");
                return false;
            }

            fputs($socket, base64_encode($username) . "\r\n");
            $response = smtpReadResponse($socket);

            fputs($socket, base64_encode($password) . "\r\n");
            $response = smtpReadResponse($socket);
            if (substr($response, 0, 3) !== '235') {
                fclose($socket);
                error_log("SMTP error: Authentication failed");
                return false;
            }
        }

        // MAIL FROM
        fputs($socket, "MAIL FROM:<{$fromEmail}>\r\n");
        $response = smtpReadResponse($socket);
        if (substr($response, 0, 3) !== '250') {
            fclose($socket);
            error_log("SMTP error: MAIL FROM rejected");
            return false;
        }

        // RCPT TO
        fputs($socket, "RCPT TO:<{$to}>\r\n");
        $response = smtpReadResponse($socket);
        if (substr($response, 0, 3) !== '250') {
            fclose($socket);
            error_log("SMTP error: RCPT TO rejected");
            return false;
        }

        // DATA
        fputs($socket, "DATA\r\n");
        $response = smtpReadResponse($socket);
        if (substr($response, 0, 3) !== '354') {
            fclose($socket);
            error_log("SMTP error: DATA command failed");
            return false;
        }

        // Email headers and body
        $contentType = $isHtml ? 'text/html' : 'text/plain';
        $email = "From: {$fromName} <{$fromEmail}>\r\n";
        $email .= "To: {$to}\r\n";
        $email .= "Subject: {$subject}\r\n";
        $email .= "Reply-To: " . ($replyTo ?: $fromEmail) . "\r\n";
        $email .= "MIME-Version: 1.0\r\n";
        $email .= "Content-Type: {$contentType}; charset=UTF-8\r\n";
        $email .= "\r\n";
        $email .= $body;
        $email .= "\r\n.\r\n";

        fputs($socket, $email);
        $response = smtpReadResponse($socket);
        if (substr($response, 0, 3) !== '250') {
            fclose($socket);
            error_log("SMTP error: Message rejected");
            return false;
        }

        // QUIT
        fputs($socket, "QUIT\r\n");
        fclose($socket);

        return true;
    } catch (\Exception $e) {
        error_log("SMTP exception: " . $e->getMessage());
        return false;
    }
}

/**
 * Read SMTP response (helper for sendSmtpEmail)
 * @internal
 */
function smtpReadResponse($socket): string
{
    $response = '';
    while ($line = @fgets($socket, 515)) {
        $response .= $line;
        if (substr($line, 3, 1) === ' ') {
            break;
        }
    }
    return $response;
}
