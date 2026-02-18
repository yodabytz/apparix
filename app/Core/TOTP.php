<?php

namespace App\Core;

class TOTP
{
    private const PERIOD = 30;
    private const DIGITS = 6;
    private const ALGORITHM = 'sha1';
    private const SECRET_LENGTH = 20;
    private const BACKUP_CODE_COUNT = 8;
    private const BACKUP_CODE_LENGTH = 8;

    private static string $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate a random Base32-encoded secret
     */
    public static function generateSecret(): string
    {
        $bytes = random_bytes(self::SECRET_LENGTH);
        return self::base32Encode($bytes);
    }

    /**
     * Get the current TOTP code for a secret
     */
    public static function getCode(string $secret, ?int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();
        $counter = intdiv($timestamp, self::PERIOD);
        return self::generateHOTP($secret, $counter);
    }

    /**
     * Verify a TOTP code with a window of +-1 for clock drift
     */
    public static function verify(string $secret, string $code, int $window = 1, ?int $timestamp = null): bool
    {
        $timestamp = $timestamp ?? time();
        $counter = intdiv($timestamp, self::PERIOD);

        for ($i = -$window; $i <= $window; $i++) {
            $expected = self::generateHOTP($secret, $counter + $i);
            if (hash_equals($expected, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate HOTP code per RFC 4226
     */
    private static function generateHOTP(string $secret, int $counter): string
    {
        $key = self::base32Decode($secret);

        // Pack counter as 8-byte big-endian
        $counterBytes = pack('N*', 0, $counter);

        // HMAC-SHA1
        $hash = hash_hmac(self::ALGORITHM, $counterBytes, $key, true);

        // Dynamic truncation
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $binary = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        );

        $otp = $binary % pow(10, self::DIGITS);
        return str_pad((string)$otp, self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Base32 decode
     */
    private static function base32Decode(string $input): string
    {
        $input = strtoupper(rtrim($input, '='));
        $buffer = 0;
        $bitsLeft = 0;
        $output = '';

        for ($i = 0; $i < strlen($input); $i++) {
            $val = strpos(self::$base32Chars, $input[$i]);
            if ($val === false) {
                continue;
            }
            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $output;
    }

    /**
     * Base32 encode
     */
    private static function base32Encode(string $data): string
    {
        $encoded = '';
        $buffer = 0;
        $bitsLeft = 0;

        for ($i = 0; $i < strlen($data); $i++) {
            $buffer = ($buffer << 8) | ord($data[$i]);
            $bitsLeft += 8;
            while ($bitsLeft >= 5) {
                $bitsLeft -= 5;
                $encoded .= self::$base32Chars[($buffer >> $bitsLeft) & 0x1F];
            }
        }

        if ($bitsLeft > 0) {
            $encoded .= self::$base32Chars[($buffer << (5 - $bitsLeft)) & 0x1F];
        }

        return $encoded;
    }

    /**
     * Get the provisioning URI for authenticator apps
     */
    public static function getProvisioningUri(string $secret, string $accountName, string $issuer = 'apparix.app'): string
    {
        $params = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => strtoupper(self::ALGORITHM),
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ]);

        $label = rawurlencode($issuer) . ':' . rawurlencode($accountName);
        return "otpauth://totp/{$label}?{$params}";
    }

    /**
     * Get QR code URL using api.qrserver.com
     */
    public static function getQRCodeUrl(string $provisioningUri, int $size = 200): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?' . http_build_query([
            'size' => "{$size}x{$size}",
            'data' => $provisioningUri,
            'ecc' => 'M',
        ]);
    }

    /**
     * Generate backup codes
     */
    public static function generateBackupCodes(int $count = self::BACKUP_CODE_COUNT): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(self::BACKUP_CODE_LENGTH / 2)));
        }
        return $codes;
    }

    /**
     * Hash backup codes for storage (each individually hashed)
     */
    public static function hashBackupCodes(array $codes): string
    {
        $hashed = [];
        foreach ($codes as $code) {
            $hashed[] = [
                'hash' => password_hash(strtoupper($code), PASSWORD_BCRYPT),
                'used' => false,
            ];
        }
        return json_encode($hashed);
    }

    /**
     * Verify a backup code against stored hashes
     * Returns the index of the matched code, or false
     */
    public static function verifyBackupCode(string $code, string $storedJson): int|false
    {
        $codes = json_decode($storedJson, true);
        if (!is_array($codes)) {
            return false;
        }

        $code = strtoupper(trim($code));

        foreach ($codes as $index => $entry) {
            if (!empty($entry['used'])) {
                continue;
            }
            if (password_verify($code, $entry['hash'])) {
                return $index;
            }
        }

        return false;
    }

    /**
     * Mark a backup code as used
     */
    public static function markBackupCodeUsed(string $storedJson, int $index): string
    {
        $codes = json_decode($storedJson, true);
        if (isset($codes[$index])) {
            $codes[$index]['used'] = true;
        }
        return json_encode($codes);
    }

    /**
     * Count remaining (unused) backup codes
     */
    public static function countRemainingBackupCodes(string $storedJson): int
    {
        $codes = json_decode($storedJson, true);
        if (!is_array($codes)) {
            return 0;
        }

        $remaining = 0;
        foreach ($codes as $entry) {
            if (empty($entry['used'])) {
                $remaining++;
            }
        }
        return $remaining;
    }
}
