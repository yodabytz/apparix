<?php

namespace App\Models\Forum;

use App\Core\Model;
use App\Core\Database;

class ForumReport extends Model
{
    protected string $table = 'forum_reports';

    public function getPending(int $limit = 50): array
    {
        return Database::getInstance()->select(
            "SELECT r.*, u.first_name, u.last_name, u.email as reporter_email
             FROM {$this->table} r
             LEFT JOIN users u ON r.reporter_user_id = u.id
             WHERE r.status = 'pending'
             ORDER BY r.created_at ASC
             LIMIT ?",
            [$limit]
        );
    }

    public function countPending(): int
    {
        $result = Database::getInstance()->selectOne(
            "SELECT COUNT(*) as cnt FROM {$this->table} WHERE status = 'pending'"
        );
        return (int)($result['cnt'] ?? 0);
    }

    public function hasReported(int $userId, string $postType, int $postId): bool
    {
        $result = Database::getInstance()->selectOne(
            "SELECT id FROM {$this->table} WHERE reporter_user_id = ? AND post_type = ? AND post_id = ?",
            [$userId, $postType, $postId]
        );
        return $result !== null;
    }

    public function review(int $id, string $status, int $reviewedBy): void
    {
        Database::getInstance()->query(
            "UPDATE {$this->table} SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?",
            [$status, $reviewedBy, $id]
        );
    }
}
