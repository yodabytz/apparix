<?php

namespace App\Models\Forum;

use App\Core\Model;
use App\Core\Database;

class ForumEditHistory extends Model
{
    protected string $table = 'forum_edit_history';

    public function record(string $postType, int $postId, int $userId, string $oldBody, ?string $oldTitle = null, ?string $editReason = null): void
    {
        Database::getInstance()->insert(
            "INSERT INTO {$this->table} (post_type, post_id, user_id, old_body, old_title, edit_reason) VALUES (?, ?, ?, ?, ?, ?)",
            [$postType, $postId, $userId, $oldBody, $oldTitle, $editReason]
        );
    }

    public function getHistory(string $postType, int $postId): array
    {
        return Database::getInstance()->select(
            "SELECT h.*, u.first_name, u.last_name
             FROM {$this->table} h
             LEFT JOIN users u ON h.user_id = u.id
             WHERE h.post_type = ? AND h.post_id = ?
             ORDER BY h.created_at DESC",
            [$postType, $postId]
        );
    }

    public function getEditCount(string $postType, int $postId): int
    {
        $result = Database::getInstance()->selectOne(
            "SELECT COUNT(*) as cnt FROM {$this->table} WHERE post_type = ? AND post_id = ?",
            [$postType, $postId]
        );
        return (int)($result['cnt'] ?? 0);
    }
}
