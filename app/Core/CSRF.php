<?php

namespace App\Core;

/**
 * CSRF Token Protection
 * Prevents Cross-Site Request Forgery attacks
 */
class CSRF
{
    private const TOKEN_KEY = '_csrf_token';
    private const TOKEN_LIFETIME = 86400; // 24 hours

    /**
     * Generate CSRF token
     */
    public static function generateToken(): string
    {
        if (empty($_SESSION[self::TOKEN_KEY])) {
            $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
            $_SESSION[self::TOKEN_KEY . '_time'] = time();
        }

        // Regenerate token if expired
        if (time() - $_SESSION[self::TOKEN_KEY . '_time'] > self::TOKEN_LIFETIME) {
            $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
            $_SESSION[self::TOKEN_KEY . '_time'] = time();
        }

        return $_SESSION[self::TOKEN_KEY];
    }

    /**
     * Verify CSRF token
     * @param string $token Token to verify
     * @return bool True if valid, false otherwise
     */
    public static function verify(string $token): bool
    {
        // Check if session has token
        if (empty($_SESSION[self::TOKEN_KEY])) {
            return false;
        }

        // Timing-safe comparison to prevent timing attacks
        return hash_equals($_SESSION[self::TOKEN_KEY], $token);
    }

    /**
     * Get token for use in forms
     */
    public static function getToken(): string
    {
        return self::generateToken();
    }

    /**
     * Verify POST request token
     */
    public static function verifyPostToken(): bool
    {
        $token = $_POST[self::TOKEN_KEY] ?? '';
        return self::verify($token);
    }

    /**
     * Generate HTML hidden input field
     */
    public static function field(): string
    {
        return '<input type="hidden" name="' . self::TOKEN_KEY . '" value="' . htmlspecialchars(self::getToken(), ENT_QUOTES, 'UTF-8') . '">';
    }
}
