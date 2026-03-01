<?php

namespace App\Models\Forum;

use App\Core\Model;
use App\Core\Database;

class ForumModerator extends Model
{
    protected string $table = 'forum_moderators';

    public function isModerator(int $userId, ?int $categoryId = null): bool
    {
        $db = Database::getInstance();

        // Check global moderator
        $global = $db->selectOne(
            "SELECT id FROM {$this->table} WHERE user_id = ? AND category_id IS NULL",
            [$userId]
        );
        if ($global) return true;

        // Check category-specific
        if ($categoryId) {
            $specific = $db->selectOne(
                "SELECT id FROM {$this->table} WHERE user_id = ? AND category_id = ?",
                [$userId, $categoryId]
            );
            if ($specific) return true;
        }

        return false;
    }

    public function getAllWithUsers(): array
    {
        return Database::getInstance()->select(
            "SELECT m.*, u.first_name, u.last_name, u.email, c.name as category_name
             FROM {$this->table} m
             LEFT JOIN users u ON m.user_id = u.id
             LEFT JOIN forum_categories c ON m.category_id = c.id
             ORDER BY m.created_at DESC"
        );
    }

    public function addModerator(int $userId, string $role = 'moderator', ?int $categoryId = null, ?int $grantedBy = null): bool
    {
        try {
            Database::getInstance()->insert(
                "INSERT INTO {$this->table} (user_id, role, category_id, granted_by) VALUES (?, ?, ?, ?)",
                [$userId, $role, $categoryId, $grantedBy]
            );
            return true;
        } catch (\Exception $e) {
            return false; // Likely duplicate
        }
    }

    public function removeModerator(int $userId, ?int $categoryId = null): void
    {
        if ($categoryId === null) {
            Database::getInstance()->query(
                "DELETE FROM {$this->table} WHERE user_id = ? AND category_id IS NULL",
                [$userId]
            );
        } else {
            Database::getInstance()->query(
                "DELETE FROM {$this->table} WHERE user_id = ? AND category_id = ?",
                [$userId, $categoryId]
            );
        }
    }

    public function removeById(int $id): void
    {
        Database::getInstance()->query(
            "DELETE FROM {$this->table} WHERE id = ?",
            [$id]
        );
    }
}
