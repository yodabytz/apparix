<?php

namespace App\Models\Forum;

use App\Core\Model;
use App\Core\Database;

class ForumCategory extends Model
{
    protected string $table = 'forum_categories';

    public function getActive(): array
    {
        return Database::getInstance()->select(
            "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY sort_order ASC, name ASC"
        );
    }

    public function getActiveGrouped(): array
    {
        $all = $this->getActive();
        $parents = [];
        $children = [];

        foreach ($all as $cat) {
            if ($cat['parent_id']) {
                $children[$cat['parent_id']][] = $cat;
            } else {
                $cat['subcategories'] = [];
                $parents[$cat['id']] = $cat;
            }
        }

        foreach ($children as $parentId => $subs) {
            if (isset($parents[$parentId])) {
                $parents[$parentId]['subcategories'] = $subs;
            }
        }

        return array_values($parents);
    }

    public function getAll(string $orderBy = 'sort_order ASC, name ASC', ?int $limit = null): array
    {
        // Whitelist allowed ORDER BY clauses to prevent SQL injection
        $allowedOrders = [
            'sort_order ASC, name ASC',
            'name ASC',
            'name DESC',
            'created_at DESC',
            'created_at ASC',
            'id DESC',
            'id ASC',
            'sort_order ASC',
            'sort_order DESC',
        ];
        if (!in_array($orderBy, $allowedOrders, true)) {
            $orderBy = 'sort_order ASC, name ASC';
        }

        $sql = "SELECT * FROM {$this->table} ORDER BY {$orderBy}";
        if ($limit !== null) $sql .= " LIMIT " . (int)$limit;
        return Database::getInstance()->select($sql);
    }

    public function getParentCategories(): array
    {
        return Database::getInstance()->select(
            "SELECT * FROM {$this->table} WHERE parent_id IS NULL ORDER BY sort_order ASC, name ASC"
        );
    }

    public function getSubcategories(int $parentId): array
    {
        return Database::getInstance()->select(
            "SELECT * FROM {$this->table} WHERE parent_id = ? AND is_active = 1 ORDER BY sort_order ASC, name ASC",
            [$parentId]
        );
    }

    public function findBySlug(string $slug): ?array
    {
        return Database::getInstance()->selectOne(
            "SELECT * FROM {$this->table} WHERE slug = ?",
            [$slug]
        );
    }

    public function generateSlug(string $name, ?int $excludeId = null): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
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

    public function incrementTopicCount(int $categoryId): void
    {
        Database::getInstance()->query(
            "UPDATE {$this->table} SET topic_count = topic_count + 1 WHERE id = ?",
            [$categoryId]
        );
    }

    public function decrementTopicCount(int $categoryId): void
    {
        Database::getInstance()->query(
            "UPDATE {$this->table} SET topic_count = GREATEST(0, topic_count - 1) WHERE id = ?",
            [$categoryId]
        );
    }

    public function incrementReplyCount(int $categoryId): void
    {
        Database::getInstance()->query(
            "UPDATE {$this->table} SET reply_count = reply_count + 1 WHERE id = ?",
            [$categoryId]
        );
    }

    public function decrementReplyCount(int $categoryId): void
    {
        Database::getInstance()->query(
            "UPDATE {$this->table} SET reply_count = GREATEST(0, reply_count - 1) WHERE id = ?",
            [$categoryId]
        );
    }

    public function updateLastActivity(int $categoryId, ?int $topicId = null, ?string $replyAt = null): void
    {
        $sets = [];
        $params = [];

        if ($topicId !== null) {
            $sets[] = 'last_topic_id = ?';
            $params[] = $topicId;
        }
        if ($replyAt !== null) {
            $sets[] = 'last_reply_at = ?';
            $params[] = $replyAt;
        }

        if (empty($sets)) return;

        $params[] = $categoryId;
        Database::getInstance()->query(
            "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = ?",
            $params
        );
    }

    public function updateSortOrder(array $ids): void
    {
        $db = Database::getInstance();
        foreach ($ids as $order => $id) {
            $db->query(
                "UPDATE {$this->table} SET sort_order = ? WHERE id = ?",
                [$order, $id]
            );
        }
    }
}
