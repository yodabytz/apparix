<?php

namespace App\Models\Forum;

use App\Core\Model;
use App\Core\Database;

class ForumTopic extends Model
{
    protected string $table = 'forum_topics';

    public function getByCategory(int $categoryId, int $page = 1, int $perPage = 25): array
    {
        $offset = ($page - 1) * $perPage;
        return Database::getInstance()->select(
            "SELECT t.*, u.first_name, u.last_name,
                    lu.first_name as last_reply_first_name, lu.last_name as last_reply_last_name
             FROM {$this->table} t
             LEFT JOIN users u ON t.user_id = u.id
             LEFT JOIN users lu ON t.last_reply_user_id = lu.id
             WHERE t.category_id = ? AND t.status IN ('approved', 'closed')
             ORDER BY t.is_pinned DESC, COALESCE(t.last_reply_at, t.created_at) DESC
             LIMIT ? OFFSET ?",
            [$categoryId, $perPage, $offset]
        );
    }

    public function countByCategory(int $categoryId): int
    {
        $result = Database::getInstance()->selectOne(
            "SELECT COUNT(*) as cnt FROM {$this->table} WHERE category_id = ? AND status IN ('approved', 'closed')",
            [$categoryId]
        );
        return (int)($result['cnt'] ?? 0);
    }

    public function findBySlug(string $slug): ?array
    {
        return Database::getInstance()->selectOne(
            "SELECT t.*, u.first_name, u.last_name, u.created_at as user_joined,
                    c.name as category_name, c.slug as category_slug
             FROM {$this->table} t
             LEFT JOIN users u ON t.user_id = u.id
             LEFT JOIN forum_categories c ON t.category_id = c.id
             WHERE t.slug = ?",
            [$slug]
        );
    }

    public function findById(int $id): ?array
    {
        return Database::getInstance()->selectOne(
            "SELECT t.*, u.first_name, u.last_name,
                    c.name as category_name, c.slug as category_slug
             FROM {$this->table} t
             LEFT JOIN users u ON t.user_id = u.id
             LEFT JOIN forum_categories c ON t.category_id = c.id
             WHERE t.id = ?",
            [$id]
        );
    }

    public function incrementViewCount(int $id): void
    {
        Database::getInstance()->query(
            "UPDATE {$this->table} SET view_count = view_count + 1 WHERE id = ?",
            [$id]
        );
    }

    public function updateLastReply(int $topicId, int $replyId, int $userId): void
    {
        Database::getInstance()->query(
            "UPDATE {$this->table} SET last_reply_id = ?, last_reply_at = NOW(), last_reply_user_id = ?, reply_count = reply_count + 1 WHERE id = ?",
            [$replyId, $userId, $topicId]
        );
    }

    public function generateSlug(string $title, ?int $excludeId = null): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        $slug = substr($slug, 0, 200);
        $original = $slug;
        $counter = 1;
        $db = Database::getInstance();

        while (true) {
            $query = "SELECT id FROM {$this->table} WHERE slug = ?";
            $params = [$slug];
            if ($excludeId) {
                $query .= " AND id != ?";
                $params[] = $excludeId;
            }
            $existing = $db->selectOne($query, $params);
            if (!$existing) break;
            $slug = $original . '-' . $counter++;
        }

        return $slug;
    }

    public function getPending(int $limit = 50): array
    {
        return Database::getInstance()->select(
            "SELECT t.*, u.first_name, u.last_name, u.email, c.name as category_name
             FROM {$this->table} t
             LEFT JOIN users u ON t.user_id = u.id
             LEFT JOIN forum_categories c ON t.category_id = c.id
             WHERE t.status = 'pending'
             ORDER BY t.created_at ASC
             LIMIT ?",
            [$limit]
        );
    }

    public function getByUser(int $userId, int $limit = 20): array
    {
        return Database::getInstance()->select(
            "SELECT t.*, c.name as category_name, c.slug as category_slug
             FROM {$this->table} t
             LEFT JOIN forum_categories c ON t.category_id = c.id
             WHERE t.user_id = ? AND t.status IN ('approved', 'closed')
             ORDER BY t.created_at DESC
             LIMIT ?",
            [$userId, $limit]
        );
    }

    public function countByUser(int $userId): int
    {
        $result = Database::getInstance()->selectOne(
            "SELECT COUNT(*) as cnt FROM {$this->table} WHERE user_id = ? AND status IN ('approved', 'closed')",
            [$userId]
        );
        return (int)($result['cnt'] ?? 0);
    }

    public function getRecent(int $limit = 10): array
    {
        return Database::getInstance()->select(
            "SELECT t.*, u.first_name, u.last_name, c.name as category_name, c.slug as category_slug
             FROM {$this->table} t
             LEFT JOIN users u ON t.user_id = u.id
             LEFT JOIN forum_categories c ON t.category_id = c.id
             WHERE t.status IN ('approved', 'closed')
             ORDER BY t.created_at DESC
             LIMIT ?",
            [$limit]
        );
    }

    public function getStats(): array
    {
        return Database::getInstance()->selectOne(
            "SELECT COUNT(*) as total_topics,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_topics,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_topics,
                    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_topics,
                    SUM(view_count) as total_views
             FROM {$this->table}"
        ) ?: [];
    }

    public function decrementReplyCount(int $topicId): void
    {
        Database::getInstance()->query(
            "UPDATE {$this->table} SET reply_count = GREATEST(0, reply_count - 1) WHERE id = ?",
            [$topicId]
        );
    }
}
