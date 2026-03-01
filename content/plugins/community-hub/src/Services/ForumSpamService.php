<?php

namespace App\Services\Forum;

use App\Core\Database;
use App\Models\Forum\ForumRateLimit;

class ForumSpamService
{
    /**
     * Run all spam checks. Returns ['passed' => bool, 'reason' => string]
     */
    public static function check(array $data, array $settings, int $userId): array
    {
        // 1. Honeypot
        if (!empty($data['website_url'])) {
            return ['passed' => false, 'reason' => 'spam_detected'];
        }

        // 2. Submission timing
        if (!empty($data['_form_token'])) {
            $timestamp = self::verifyFormToken($data['_form_token']);
            if ($timestamp && (time() - $timestamp) < 3) {
                return ['passed' => false, 'reason' => 'Please wait a moment before submitting.'];
            }
        }

        // 3. Rate limiting
        $actionType = $data['_action_type'] ?? 'reply';
        $rateMinutes = ($actionType === 'topic')
            ? (int)($settings['rate_limit_topics'] ?? 5)
            : (int)($settings['rate_limit_posts'] ?? 2);

        $rateLimitModel = new ForumRateLimit();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if ($rateLimitModel->isRateLimited($userId, $ip, $actionType, $rateMinutes)) {
            return ['passed' => false, 'reason' => "Please wait {$rateMinutes} minutes between posts."];
        }

        // 4. Content validation
        $body = trim($data['body'] ?? '');
        $minLength = (int)($settings['min_post_length'] ?? 10);
        $maxLength = (int)($settings['max_post_length'] ?? 10000);

        if (mb_strlen($body) < $minLength) {
            return ['passed' => false, 'reason' => "Post must be at least {$minLength} characters."];
        }
        if (mb_strlen($body) > $maxLength) {
            return ['passed' => false, 'reason' => "Post cannot exceed {$maxLength} characters."];
        }

        // URL density check (more than 5 URLs is suspicious)
        $urlCount = preg_match_all('/https?:\/\//i', $body);
        if ($urlCount > 5) {
            return ['passed' => false, 'reason' => 'Too many links in your post.'];
        }

        // All-caps detection (more than 70% uppercase)
        $letters = preg_replace('/[^a-zA-Z]/', '', $body);
        if (strlen($letters) > 10) {
            $upperRatio = strlen(preg_replace('/[^A-Z]/', '', $letters)) / strlen($letters);
            if ($upperRatio > 0.7) {
                return ['passed' => false, 'reason' => 'Please avoid excessive use of capital letters.'];
            }
        }

        // Repeated character detection
        if (preg_match('/(.)\1{9,}/', $body)) {
            return ['passed' => false, 'reason' => 'Your post contains excessive repeated characters.'];
        }

        // 5. Duplicate detection (within 24h)
        $hash = md5($body);
        $db = Database::getInstance();
        $dupTopic = $db->selectOne(
            "SELECT id FROM forum_topics WHERE MD5(body) = ? AND user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            [$hash, $userId]
        );
        $dupReply = $db->selectOne(
            "SELECT id FROM forum_replies WHERE MD5(body) = ? AND user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            [$hash, $userId]
        );
        if ($dupTopic || $dupReply) {
            return ['passed' => false, 'reason' => 'You have already posted this content recently.'];
        }

        return ['passed' => true, 'reason' => ''];
    }

    /**
     * Check if a new user should have posts auto-moderated
     */
    public static function shouldModerate(int $userId, array $settings): bool
    {
        // New user post delay
        $delayHours = (int)($settings['new_user_post_delay'] ?? 0);
        if ($delayHours > 0) {
            $user = Database::getInstance()->selectOne(
                "SELECT created_at FROM users WHERE id = ?",
                [$userId]
            );
            if ($user) {
                $accountAge = time() - strtotime($user['created_at']);
                if ($accountAge < ($delayHours * 3600)) {
                    return true;
                }
            }
        }

        // First 3 posts always go to moderation (count from actual posts, not ephemeral rate limits)
        $db = Database::getInstance();
        $topicCount = $db->selectOne("SELECT COUNT(*) as cnt FROM forum_topics WHERE user_id = ? AND status IN ('approved','closed')", [$userId]);
        $replyCount = $db->selectOne("SELECT COUNT(*) as cnt FROM forum_replies WHERE user_id = ? AND status = 'approved'", [$userId]);
        $totalPosts = (int)($topicCount['cnt'] ?? 0) + (int)($replyCount['cnt'] ?? 0);
        if ($totalPosts < 3) {
            return true;
        }

        // Global require approval setting
        if (!empty($settings['require_approval'])) {
            return true;
        }

        return false;
    }

    /**
     * Generate an encrypted form token with timestamp
     */
    public static function generateFormToken(): string
    {
        $timestamp = time();
        $key = $_ENV['APP_KEY'] ?? 'community-hub-default-key';
        $signature = hash_hmac('sha256', (string)$timestamp, $key);
        return base64_encode($timestamp . '|' . $signature);
    }

    /**
     * Verify form token and return timestamp
     */
    public static function verifyFormToken(string $token): ?int
    {
        $decoded = base64_decode($token);
        if (!$decoded || strpos($decoded, '|') === false) return null;

        [$timestamp, $signature] = explode('|', $decoded, 2);
        $key = $_ENV['APP_KEY'] ?? 'community-hub-default-key';
        $expected = hash_hmac('sha256', $timestamp, $key);

        if (hash_equals($expected, $signature)) {
            return (int)$timestamp;
        }

        return null;
    }
}
