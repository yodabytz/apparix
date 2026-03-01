<?php

namespace App\Models\Forum;

use App\Core\Model;
use App\Core\Database;

class ForumSubscription extends Model
{
    protected string $table = 'forum_subscriptions';

    public function isSubscribed(int $userId, int $topicId): bool
    {
        $result = Database::getInstance()->selectOne(
            "SELECT id FROM {$this->table} WHERE user_id = ? AND topic_id = ?",
            [$userId, $topicId]
        );
        return $result !== null;
    }

    public function subscribe(int $userId, int $topicId): void
    {
        try {
            Database::getInstance()->insert(
                "INSERT INTO {$this->table} (user_id, topic_id) VALUES (?, ?)",
                [$userId, $topicId]
            );
        } catch (\Exception $e) {
            // Already subscribed
        }
    }

    public function unsubscribe(int $userId, int $topicId): void
    {
        Database::getInstance()->query(
            "DELETE FROM {$this->table} WHERE user_id = ? AND topic_id = ?",
            [$userId, $topicId]
        );
    }

    public function getSubscribers(int $topicId, ?int $excludeUserId = null): array
    {
        $query = "SELECT s.*, u.first_name, u.last_name, u.email
                  FROM {$this->table} s
                  LEFT JOIN users u ON s.user_id = u.id
                  WHERE s.topic_id = ? AND s.notify_email = 1";
        $params = [$topicId];

        if ($excludeUserId) {
            $query .= " AND s.user_id != ?";
            $params[] = $excludeUserId;
        }

        return Database::getInstance()->select($query, $params);
    }
}
