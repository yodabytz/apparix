<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

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
}
