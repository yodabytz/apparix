<?php

namespace App\Services\Forum;

use App\Core\Database;
use App\Models\Forum\ForumSubscription;

class ForumEmailService
{
    /**
     * Get the trusted base URL from environment config (never from Host header)
     */
    private static function getBaseUrl(): string
    {
        $appUrl = getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? '');
        if ($appUrl) {
            return rtrim($appUrl, '/');
        }
        // Fallback: build from SERVER_NAME (set by nginx, not client)
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['SERVER_NAME'] ?? 'localhost';
        return $scheme . '://' . $host;
    }

    /**
     * Send reply notification to all topic subscribers
     */
    public static function sendReplyNotifications(array $topic, array $reply, array $replyAuthor): void
    {
        $subscriptionModel = new ForumSubscription();
        $subscribers = $subscriptionModel->getSubscribers($topic['id'], $replyAuthor['id'] ?? null);

        if (empty($subscribers)) return;

        $baseUrl = self::getBaseUrl();
        $topicUrl = $baseUrl . '/community/topic/' . $topic['slug'];
        $authorName = trim(($replyAuthor['first_name'] ?? '') . ' ' . ($replyAuthor['last_name'] ?? '')) ?: 'A community member';
        $subject = "New reply in: " . $topic['title'];

        foreach ($subscribers as $sub) {
            $unsubUrl = $baseUrl
                . '/community/unsubscribe?topic=' . $topic['id'] . '&token=' . self::generateUnsubToken($sub['user_id'], $topic['id']);

            $body = self::buildEmail(
                "New Reply in \"{$topic['title']}\"",
                "<p><strong>{$authorName}</strong> replied to a topic you're subscribed to:</p>"
                . "<blockquote style=\"border-left: 3px solid #e5e7eb; padding-left: 12px; margin: 12px 0; color: #6b7280;\">"
                . nl2br(htmlspecialchars(mb_substr($reply['body'], 0, 300)))
                . (mb_strlen($reply['body']) > 300 ? '...' : '')
                . "</blockquote>"
                . "<p><a href=\"{$topicUrl}\" style=\"color: #6366f1;\">View full discussion</a></p>"
                . "<p style=\"font-size: 12px; color: #9ca3af;\"><a href=\"{$unsubUrl}\" style=\"color: #9ca3af;\">Unsubscribe from this topic</a></p>"
            );

            if (function_exists('sendEmail')) {
                sendEmail($sub['email'], $subject, $body);
            }
        }
    }

    /**
     * Send notification when topic is approved
     */
    public static function sendTopicApproved(array $topic, string $authorEmail): void
    {
        $topicUrl = self::getBaseUrl() . '/community/topic/' . $topic['slug'];
        $subject = "Your topic has been approved";

        $body = self::buildEmail(
            "Topic Approved",
            "<p>Your topic <strong>\"{$topic['title']}\"</strong> has been approved and is now visible in the community.</p>"
            . "<p><a href=\"{$topicUrl}\" style=\"color: #6366f1;\">View your topic</a></p>"
        );

        if (function_exists('sendEmail')) {
            sendEmail($authorEmail, $subject, $body);
        }
    }

    /**
     * Send notification when topic is rejected
     */
    public static function sendTopicRejected(array $topic, string $authorEmail): void
    {
        $subject = "Your topic was not approved";

        $body = self::buildEmail(
            "Topic Not Approved",
            "<p>Your topic <strong>\"{$topic['title']}\"</strong> was reviewed and not approved for the community forum.</p>"
            . "<p>If you believe this was in error, please contact a moderator.</p>"
        );

        if (function_exists('sendEmail')) {
            sendEmail($authorEmail, $subject, $body);
        }
    }

    /**
     * Send report notification to moderators
     */
    public static function sendReportNotification(array $report, string $postTitle): void
    {
        $db = Database::getInstance();
        $moderators = $db->select(
            "SELECT DISTINCT u.email FROM forum_moderators fm
             LEFT JOIN users u ON fm.user_id = u.id
             WHERE u.email IS NOT NULL"
        );

        // Also notify admins
        $admins = $db->select("SELECT email FROM admin_users WHERE email IS NOT NULL");
        $emails = array_unique(array_merge(
            array_column($moderators, 'email'),
            array_column($admins, 'email')
        ));

        if (empty($emails)) return;

        $adminUrl = self::getBaseUrl() . '/admin/community/reports';
        $subject = "A post has been reported";

        $body = self::buildEmail(
            "Post Reported",
            "<p>A {$report['post_type']} has been reported in the community forum.</p>"
            . "<p><strong>Reason:</strong> " . htmlspecialchars($report['reason']) . "</p>"
            . (!empty($report['details']) ? "<p><strong>Details:</strong> " . htmlspecialchars($report['details']) . "</p>" : "")
            . "<p><a href=\"{$adminUrl}\" style=\"color: #6366f1;\">Review reports</a></p>"
        );

        foreach ($emails as $email) {
            if (function_exists('sendEmail')) {
                sendEmail($email, $subject, $body);
            }
        }
    }

    public static function generateUnsubToken(int $userId, int $topicId): string
    {
        $key = $_ENV['APP_KEY'] ?? 'community-hub-unsub-key';
        return hash_hmac('sha256', "{$userId}:{$topicId}", $key);
    }

    public static function verifyUnsubToken(int $userId, int $topicId, string $token): bool
    {
        $expected = self::generateUnsubToken($userId, $topicId);
        return hash_equals($expected, $token);
    }

    private static function buildEmail(string $heading, string $content): string
    {
        $appName = function_exists('appName') ? appName() : 'Community';

        return '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; color: #374151;">'
            . '<div style="text-align: center; padding: 20px 0; border-bottom: 1px solid #e5e7eb;">'
            . '<h2 style="margin: 0; color: #111827;">' . htmlspecialchars($appName) . '</h2>'
            . '</div>'
            . '<div style="padding: 24px 0;">'
            . '<h3 style="color: #111827;">' . $heading . '</h3>'
            . $content
            . '</div>'
            . '<div style="border-top: 1px solid #e5e7eb; padding-top: 16px; font-size: 12px; color: #9ca3af; text-align: center;">'
            . htmlspecialchars($appName) . ' Community'
            . '</div>'
            . '</body></html>';
    }
}
