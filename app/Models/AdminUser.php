<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;
use App\Core\TOTP;

class AdminUser extends Model
{
    protected string $table = 'admin_users';

    /**
     * Find admin by email
     */
    public function findByEmail(string $email): array|false
    {
        return $this->queryOne(
            "SELECT * FROM {$this->table} WHERE email = ?",
            [$email]
        );
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Create admin user
     */
    public function createAdmin(string $email, string $password, string $name, string $role = 'admin'): int|string
    {
        $hash = password_hash($password, PASSWORD_ARGON2ID);
        return $this->create([
            'email' => $email,
            'password_hash' => $hash,
            'name' => $name,
            'role' => $role
        ]);
    }

    /**
     * Create session token
     */
    public function createSession(int $adminId, string $ipAddress, string $userAgent): string
    {
        $db = Database::getInstance();
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $db->insert(
            "INSERT INTO admin_sessions (admin_user_id, session_token, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)",
            [$adminId, $token, $ipAddress, $userAgent, $expiresAt]
        );

        // Update last login
        $db->update(
            "UPDATE {$this->table} SET last_login = NOW() WHERE id = ?",
            [$adminId]
        );

        return $token;
    }

    /**
     * Validate session token
     */
    public function validateSession(string $token): array|false
    {
        $db = Database::getInstance();
        $session = $db->selectOne(
            "SELECT s.*, a.id as admin_id, a.email, a.name, a.role
             FROM admin_sessions s
             JOIN admin_users a ON s.admin_user_id = a.id
             WHERE s.session_token = ? AND s.expires_at > NOW()",
            [$token]
        );

        return $session ?: false;
    }

    /**
     * Destroy session
     */
    public function destroySession(string $token): void
    {
        $db = Database::getInstance();
        $db->update("DELETE FROM admin_sessions WHERE session_token = ?", [$token]);
    }

    /**
     * Clean expired sessions
     */
    public function cleanExpiredSessions(): void
    {
        $db = Database::getInstance();
        $db->update("DELETE FROM admin_sessions WHERE expires_at < NOW()");
    }

    /**
     * Log activity
     */
    public function logActivity(int $adminId, string $action, ?string $entityType = null, ?int $entityId = null, ?string $details = null): void
    {
        $db = Database::getInstance();
        $db->insert(
            "INSERT INTO admin_activity_log (admin_user_id, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)",
            [$adminId, $action, $entityType, $entityId, $details, $_SERVER['REMOTE_ADDR'] ?? null]
        );
    }

    /**
     * Get recent activity
     */
    public function getRecentActivity(int $limit = 50): array
    {
        return $this->query(
            "SELECT l.*, a.name as admin_name
             FROM admin_activity_log l
             JOIN admin_users a ON l.admin_user_id = a.id
             ORDER BY l.created_at DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Get all admins
     */
    public function getAllAdmins(): array
    {
        return $this->query("SELECT id, email, name, role, last_login, created_at FROM {$this->table} ORDER BY created_at DESC");
    }

    /**
     * Find admin by ID
     */
    public function findById(int $id): array|false
    {
        return $this->queryOne(
            "SELECT id, email, name, role, last_login, created_at FROM {$this->table} WHERE id = ?",
            [$id]
        );
    }

    /**
     * Update admin user
     */
    public function updateAdmin(int $id, array $data): bool
    {
        $db = Database::getInstance();
        $fields = [];
        $params = [];

        // Only allow specific fields to be updated
        $allowedFields = ['email', 'name', 'role'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }

        // Handle password separately with proper hashing
        if (!empty($data['password'])) {
            $fields[] = "password_hash = ?";
            $params[] = password_hash($data['password'], PASSWORD_ARGON2ID);
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";

        return $db->update($sql, $params) >= 0;
    }

    /**
     * Delete admin user
     */
    public function deleteAdmin(int $id): bool
    {
        $db = Database::getInstance();

        // First delete their sessions
        $db->update("DELETE FROM admin_sessions WHERE admin_user_id = ?", [$id]);

        // Then delete the admin
        return $db->update("DELETE FROM {$this->table} WHERE id = ?", [$id]) > 0;
    }

    /**
     * Check if email exists (excluding specific ID for updates)
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE email = ?";
        $params = [$email];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $result = $this->queryOne($sql, $params);
        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Count total admins
     */
    public function countAdmins(): int
    {
        $result = $this->queryOne("SELECT COUNT(*) as count FROM {$this->table}");
        return (int)($result['count'] ?? 0);
    }

    // ==========================================
    // Two-Factor Authentication Methods
    // ==========================================

    /**
     * Check if admin has 2FA enabled
     */
    public function has2FAEnabled(int $adminId): bool
    {
        $result = $this->queryOne(
            "SELECT totp_enabled FROM {$this->table} WHERE id = ?",
            [$adminId]
        );
        return (bool)($result['totp_enabled'] ?? 0);
    }

    /**
     * Get 2FA secret for admin
     */
    public function get2FASecret(int $adminId): ?string
    {
        $result = $this->queryOne(
            "SELECT totp_secret FROM {$this->table} WHERE id = ?",
            [$adminId]
        );
        return $result['totp_secret'] ?? null;
    }

    /**
     * Initialize 2FA setup - generates secret and backup codes but doesn't enable yet
     */
    public function init2FASetup(int $adminId): array
    {
        $db = Database::getInstance();
        $secret = TOTP::generateSecret();
        $backupCodes = TOTP::generateBackupCodes();
        $hashedCodes = TOTP::hashBackupCodes($backupCodes);

        $db->update(
            "UPDATE {$this->table} SET totp_secret = ?, backup_codes = ? WHERE id = ?",
            [$secret, $hashedCodes, $adminId]
        );

        return [
            'secret' => $secret,
            'backup_codes' => $backupCodes,
        ];
    }

    /**
     * Enable 2FA after verifying a TOTP code
     */
    public function enable2FA(int $adminId, string $code): bool
    {
        $secret = $this->get2FASecret($adminId);
        if (!$secret) {
            return false;
        }

        if (!TOTP::verify($secret, $code)) {
            return false;
        }

        $db = Database::getInstance();
        $db->update(
            "UPDATE {$this->table} SET totp_enabled = 1 WHERE id = ?",
            [$adminId]
        );

        return true;
    }

    /**
     * Disable 2FA for admin
     */
    public function disable2FA(int $adminId): void
    {
        $db = Database::getInstance();
        $db->update(
            "UPDATE {$this->table} SET totp_enabled = 0, totp_secret = NULL, backup_codes = NULL WHERE id = ?",
            [$adminId]
        );

        // Remove all registered devices
        $db->update("DELETE FROM admin_devices WHERE admin_id = ?", [$adminId]);
    }

    /**
     * Verify a 2FA code (tries TOTP first, then backup codes)
     * Returns 'totp', 'backup', or false
     */
    public function verify2FA(int $adminId, string $code): string|false
    {
        $row = $this->queryOne(
            "SELECT totp_secret, backup_codes FROM {$this->table} WHERE id = ?",
            [$adminId]
        );

        if (!$row || empty($row['totp_secret'])) {
            return false;
        }

        // Try TOTP first
        $cleanCode = preg_replace('/\s+/', '', $code);
        if (TOTP::verify($row['totp_secret'], $cleanCode)) {
            return 'totp';
        }

        // Try backup codes
        if (!empty($row['backup_codes'])) {
            $index = TOTP::verifyBackupCode($cleanCode, $row['backup_codes']);
            if ($index !== false) {
                $updatedCodes = TOTP::markBackupCodeUsed($row['backup_codes'], $index);
                $db = Database::getInstance();
                $db->update(
                    "UPDATE {$this->table} SET backup_codes = ? WHERE id = ?",
                    [$updatedCodes, $adminId]
                );
                return 'backup';
            }
        }

        return false;
    }

    /**
     * Get remaining backup codes count
     */
    public function getRemainingBackupCodesCount(int $adminId): int
    {
        $result = $this->queryOne(
            "SELECT backup_codes FROM {$this->table} WHERE id = ?",
            [$adminId]
        );

        if (empty($result['backup_codes'])) {
            return 0;
        }

        return TOTP::countRemainingBackupCodes($result['backup_codes']);
    }

    /**
     * Regenerate backup codes
     */
    public function regenerateBackupCodes(int $adminId): array
    {
        $db = Database::getInstance();
        $codes = TOTP::generateBackupCodes();
        $hashedCodes = TOTP::hashBackupCodes($codes);

        $db->update(
            "UPDATE {$this->table} SET backup_codes = ? WHERE id = ?",
            [$hashedCodes, $adminId]
        );

        return $codes;
    }

    // ==========================================
    // Device Fingerprinting Methods
    // ==========================================

    /**
     * Generate a device fingerprint from IP subnet and user agent
     */
    public function generateDeviceFingerprint(string $ip, string $userAgent): string
    {
        // Use /24 subnet for IPv4
        $parts = explode('.', $ip);
        $subnet = count($parts) === 4
            ? implode('.', array_slice($parts, 0, 3)) . '.0/24'
            : $ip;

        return hash('sha256', $subnet . '|' . $userAgent);
    }

    /**
     * Check if device is known for this admin
     */
    public function isKnownDevice(int $adminId, string $fingerprint): bool
    {
        $result = $this->queryOne(
            "SELECT id FROM admin_devices WHERE admin_id = ? AND device_fingerprint = ?",
            [$adminId, $fingerprint]
        );

        if ($result) {
            // Update last_used_at
            $db = Database::getInstance();
            $db->update(
                "UPDATE admin_devices SET last_used_at = NOW() WHERE id = ?",
                [$result['id']]
            );
            return true;
        }

        return false;
    }

    /**
     * Register a new device
     */
    public function registerDevice(int $adminId, string $fingerprint, string $ip, string $userAgent): void
    {
        $db = Database::getInstance();
        $db->insert(
            "INSERT INTO admin_devices (admin_id, device_fingerprint, ip_address, user_agent) VALUES (?, ?, ?, ?)",
            [$adminId, $fingerprint, $ip, $userAgent]
        );
    }

    /**
     * Get device count for admin
     */
    public function getDeviceCount(int $adminId): int
    {
        $result = $this->queryOne(
            "SELECT COUNT(*) as count FROM admin_devices WHERE admin_id = ?",
            [$adminId]
        );
        return (int)($result['count'] ?? 0);
    }
}
