<?php

namespace App\Models;

use App\Core\Model;

class RateLimiter extends Model
{
    protected string $table = 'login_attempts';

    // Rate limiting configuration
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 15;
    private const CLEANUP_HOURS = 24;

    /**
     * Record a failed login attempt
     */
    public function recordFailedAttempt(string $ip, string $email, string $type = 'user'): void
    {
        $this->db->insert(
            "INSERT INTO {$this->table} (ip_address, email, attempt_type, attempted_at) VALUES (?, ?, ?, NOW())",
            [$ip, strtolower($email), $type]
        );
    }

    /**
     * Check if login is allowed (not locked out)
     */
    public function isAllowed(string $ip, string $email, string $type = 'user'): bool
    {
        $attempts = $this->getRecentAttempts($ip, $email, $type);
        return $attempts < self::MAX_ATTEMPTS;
    }

    /**
     * Get number of recent failed attempts
     */
    public function getRecentAttempts(string $ip, string $email, string $type = 'user'): int
    {
        $lockoutTime = date('Y-m-d H:i:s', strtotime('-' . self::LOCKOUT_MINUTES . ' minutes'));

        $result = $this->queryOne(
            "SELECT COUNT(*) as attempts FROM {$this->table}
             WHERE (ip_address = ? OR email = ?)
             AND attempt_type = ?
             AND attempted_at > ?",
            [$ip, strtolower($email), $type, $lockoutTime]
        );

        return (int) ($result['attempts'] ?? 0);
    }

    /**
     * Get remaining lockout time in seconds
     */
    public function getRemainingLockoutTime(string $ip, string $email, string $type = 'user'): int
    {
        $result = $this->queryOne(
            "SELECT MAX(attempted_at) as last_attempt FROM {$this->table}
             WHERE (ip_address = ? OR email = ?)
             AND attempt_type = ?",
            [$ip, strtolower($email), $type]
        );

        if (!$result || !$result['last_attempt']) {
            return 0;
        }

        $lastAttempt = strtotime($result['last_attempt']);
        $unlockTime = $lastAttempt + (self::LOCKOUT_MINUTES * 60);
        $remaining = $unlockTime - time();

        return max(0, $remaining);
    }

    /**
     * Clear attempts on successful login
     */
    public function clearAttempts(string $ip, string $email, string $type = 'user'): void
    {
        $this->db->update(
            "DELETE FROM {$this->table}
             WHERE (ip_address = ? OR email = ?)
             AND attempt_type = ?",
            [$ip, strtolower($email), $type]
        );
    }

    /**
     * Get delay for progressive slowdown (in seconds)
     */
    public function getDelay(string $ip, string $email, string $type = 'user'): int
    {
        $attempts = $this->getRecentAttempts($ip, $email, $type);

        // Progressive delays: 0, 1, 2, 4, 8 seconds
        if ($attempts <= 1) {
            return 0;
        }

        return min(pow(2, $attempts - 1), 8);
    }

    /**
     * Cleanup old attempts (call periodically)
     */
    public function cleanupOldAttempts(): int
    {
        $cutoff = date('Y-m-d H:i:s', strtotime('-' . self::CLEANUP_HOURS . ' hours'));

        return $this->db->update(
            "DELETE FROM {$this->table} WHERE attempted_at < ?",
            [$cutoff]
        );
    }

    /**
     * Get lockout message
     */
    public function getLockoutMessage(string $ip, string $email, string $type = 'user'): string
    {
        $remaining = $this->getRemainingLockoutTime($ip, $email, $type);

        if ($remaining <= 0) {
            return '';
        }

        $minutes = ceil($remaining / 60);

        if ($minutes == 1) {
            return 'Too many failed login attempts. Please try again in 1 minute.';
        }

        return "Too many failed login attempts. Please try again in {$minutes} minutes.";
    }
}
