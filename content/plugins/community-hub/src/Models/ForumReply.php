<?php

namespace App\Models\Forum;

use App\Core\Model;
use App\Core\Database;

class ForumReply extends Model
{
    protected string $table = 'forum_replies';

    public function getByTopic(int $topicId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        return Database::getInstance()->select(
            "SELECT r.*, u.first_name, u.last_name, u.created_at as user_joined,
                    (SELECT COUNT(*) FROM {$this->table} eh WHERE eh.topic_id = r.topic_id AND eh.user_id = r.user_id) as user_post_count,
                    (SELECT COUNT(*) FROM forum_edit_history WHERE post_type = 'reply' AND post_id = r.id) as edit_count
             FROM {$this->table} r
             LEFT JOIN users u ON r.user_id = u.id
             WHERE r.topic_id = ? AND r.status = 'approved'
             ORDER BY r.created_at ASC
             LIMIT ? OFFSET ?",
            [$topicId, $perPage, $offset]
        );
    }

    public function countByTopic(int $topicId): int
    {
        $result = Database::getInstance()->selectOne(
            "SELECT COUNT(*) as cnt FROM {$this->table} WHERE topic_id = ? AND status = 'approved'",
            [$topicId]
        );
        return (int)($result['cnt'] ?? 0);
    }

    public function findWithUser(int $id): ?array
    {
        return Database::getInstance()->selectOne(
            "SELECT r.*, u.first_name, u.last_name, u.email,
                    t.title as topic_title, t.slug as topic_slug, t.category_id
             FROM {$this->table} r
             LEFT JOIN users u ON r.user_id = u.id
             LEFT JOIN forum_topics t ON r.topic_id = t.id
             WHERE r.id = ?",
            [$id]
        );
    }

    public function getPending(int $limit = 50): array
    {
        return Database::getInstance()->select(
            "SELECT r.*, u.first_name, u.last_name, u.email,
                    t.title as topic_title, t.slug as topic_slug
             FROM {$this->table} r
             LEFT JOIN users u ON r.user_id = u.id
             LEFT JOIN forum_topics t ON r.topic_id = t.id
             WHERE r.status = 'pending'
             ORDER BY r.created_at ASC
             LIMIT ?",
            [$limit]
        );
    }

    public function getByUser(int $userId, int $limit = 20): array
    {
        return Database::getInstance()->select(
            "SELECT r.*, t.title as topic_title, t.slug as topic_slug
             FROM {$this->table} r
             LEFT JOIN forum_topics t ON r.topic_id = t.id
             WHERE r.user_id = ? AND r.status = 'approved'
             ORDER BY r.created_at DESC
             LIMIT ?",
            [$userId, $limit]
        );
    }

    public function countByUser(int $userId): int
    {
        $result = Database::getInstance()->selectOne(
            "SELECT COUNT(*) as cnt FROM {$this->table} WHERE user_id = ? AND status = 'approved'",
            [$userId]
        );
        return (int)($result['cnt'] ?? 0);
    }

    public function getStats(): array
    {
        return Database::getInstance()->selectOne(
            "SELECT COUNT(*) as total_replies,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_replies,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_replies
             FROM {$this->table}"
        ) ?: [];
    }
}
