<?php

namespace App\Models\Forum;

use App\Core\Model;
use App\Core\Database;

class ForumRateLimit extends Model
{
    protected string $table = 'forum_rate_limits';

    public function record(int $userId, string $ip, string $actionType): void
    {
        Database::getInstance()->insert(
            "INSERT INTO {$this->table} (user_id, ip_address, action_type) VALUES (?, ?, ?)",
            [$userId, $ip, $actionType]
        );
    }

    public function isRateLimited(int $userId, string $ip, string $actionType, int $minutes): bool
    {
        $db = Database::getInstance();

        // Check by user
        $byUser = $db->selectOne(
            "SELECT id FROM {$this->table} WHERE user_id = ? AND action_type = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)",
            [$userId, $actionType, $minutes]
        );
        if ($byUser) return true;

        // Check by IP
        $byIp = $db->selectOne(
            "SELECT id FROM {$this->table} WHERE ip_address = ? AND action_type = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)",
            [$ip, $actionType, $minutes]
        );
        if ($byIp) return true;

        return false;
    }

    public function cleanup(): void
    {
        Database::getInstance()->query(
            "DELETE FROM {$this->table} WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );
    }

    public function getUserPostCount(int $userId): int
    {
        $result = Database::getInstance()->selectOne(
            "SELECT COUNT(*) as cnt FROM {$this->table} WHERE user_id = ? AND action_type IN ('topic', 'reply')",
            [$userId]
        );
        return (int)($result['cnt'] ?? 0);
    }
}
