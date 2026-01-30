<?php

namespace App\Core;

class ReCaptcha
{
    private static string $siteKey = '';
    private static string $secretKey = '';
    private static float $minScore = 0.5;

    /**
     * Initialize reCAPTCHA with keys from environment
     */
    public static function init(): void
    {
        self::$siteKey = $_ENV['RECAPTCHA_SITE_KEY'] ?? '';
        self::$secretKey = $_ENV['RECAPTCHA_SECRET_KEY'] ?? '';
    }

    /**
     * Get the site key for frontend
     */
    public static function getSiteKey(): string
    {
        if (empty(self::$siteKey)) {
            self::init();
        }
        return self::$siteKey;
    }

    /**
     * Verify reCAPTCHA token
     */
    public static function verify(string $token, string $expectedAction = ''): array
    {
        if (empty(self::$secretKey)) {
            self::init();
        }

        if (empty($token)) {
            return ['success' => false, 'error' => 'No reCAPTCHA token provided'];
        }

        if (empty(self::$secretKey)) {
            // If no secret key configured, skip verification (for development)
            return ['success' => true, 'score' => 1.0];
        }

        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret' => self::$secretKey,
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ];

        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        if ($result === false) {
            return ['success' => false, 'error' => 'Failed to verify reCAPTCHA'];
        }

        $response = json_decode($result, true);

        if (!$response['success']) {
            return [
                'success' => false,
                'error' => 'reCAPTCHA verification failed',
                'error_codes' => $response['error-codes'] ?? []
            ];
        }

        // Check action if expected
        if ($expectedAction && isset($response['action']) && $response['action'] !== $expectedAction) {
            return [
                'success' => false,
                'error' => 'reCAPTCHA action mismatch'
            ];
        }

        // Check score (0.0 = bot, 1.0 = human)
        $score = $response['score'] ?? 0;
        if ($score < self::$minScore) {
            return [
                'success' => false,
                'error' => 'reCAPTCHA score too low',
                'score' => $score
            ];
        }

        return [
            'success' => true,
            'score' => $score,
            'action' => $response['action'] ?? ''
        ];
    }

    /**
     * Get JavaScript to include reCAPTCHA
     */
    public static function getScript(): string
    {
        $siteKey = self::getSiteKey();
        if (empty($siteKey)) {
            return '';
        }
        return '<script src="https://www.google.com/recaptcha/api.js?render=' . htmlspecialchars($siteKey) . '"></script>';
    }

    /**
     * Get JavaScript function to execute reCAPTCHA and set token
     */
    public static function getExecuteScript(string $action, string $inputId = 'recaptcha_token'): string
    {
        $siteKey = self::getSiteKey();
        if (empty($siteKey)) {
            return '';
        }
        return "
        grecaptcha.ready(function() {
            grecaptcha.execute('" . htmlspecialchars($siteKey) . "', {action: '" . htmlspecialchars($action) . "'}).then(function(token) {
                document.getElementById('" . htmlspecialchars($inputId) . "').value = token;
            });
        });
        ";
    }
}
