<?php

namespace App\Plugins;

use App\Core\Plugins\PluginInterface;
use App\Core\Plugins\HookRegistry;
use App\Core\Database;
use App\Models\Plugin;

class CommunityHubPlugin implements PluginInterface
{
    private array $settings = [];
    private string $pluginDir;

    public function __construct()
    {
        $this->pluginDir = __DIR__;
    }

    public function getSlug(): string
    {
        return 'community-hub';
    }

    public function getName(): string
    {
        return 'Community Hub';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getType(): string
    {
        return 'utility';
    }

    public function getDescription(): string
    {
        return 'Full-featured community forum with categories, moderation, markdown posts, email notifications, and anti-spam.';
    }

    public function getAuthor(): string
    {
        return 'Apparix';
    }

    public function init(): void
    {
        $this->loadSettings();

        // Require all source files so the Router can find the controllers
        $srcDir = $this->pluginDir . '/src';

        // Models
        require_once $srcDir . '/Models/ForumCategory.php';
        require_once $srcDir . '/Models/ForumTopic.php';
        require_once $srcDir . '/Models/ForumReply.php';
        require_once $srcDir . '/Models/ForumModerator.php';
        require_once $srcDir . '/Models/ForumReport.php';
        require_once $srcDir . '/Models/ForumSubscription.php';
        require_once $srcDir . '/Models/ForumEditHistory.php';
        require_once $srcDir . '/Models/ForumRateLimit.php';

        // Services
        require_once $srcDir . '/Services/MarkdownParser.php';
        require_once $srcDir . '/Services/ForumEmailService.php';
        require_once $srcDir . '/Services/ForumSpamService.php';

        // Controllers
        require_once $srcDir . '/ForumController.php';
        require_once $srcDir . '/AdminForumController.php';

        // Register hooks
        HookRegistry::add('navbar_menu_items', [$this, 'addNavItem'], 10);
        HookRegistry::add('admin_sidebar_menu', [$this, 'addAdminNavItem'], 10);
        HookRegistry::add('page_head', [$this, 'injectAssets'], 10);
    }

    public function onActivate(): void
    {
        $this->createTables();
    }

    public function onDeactivate(): void
    {
        // Tables preserved on deactivation
    }

    public function getSettingsView(): string
    {
        return '';
    }

    public function validateSettings(array $settings): array
    {
        return [];
    }

    public function getDefaultSettings(): array
    {
        return [
            'forum_name' => 'Community',
            'require_approval' => false,
            'posts_per_page' => 20,
            'topics_per_page' => 25,
            'min_post_length' => 10,
            'max_post_length' => 10000,
            'rate_limit_posts' => 2,
            'rate_limit_topics' => 5,
            'email_notifications' => true,
            'new_user_post_delay' => 0,
        ];
    }

    public function getSettingsSchema(): array
    {
        return [
            ['key' => 'forum_name', 'label' => 'Forum Name', 'type' => 'text', 'default' => 'Community'],
            ['key' => 'require_approval', 'label' => 'Require Post Approval', 'type' => 'checkbox', 'default' => false],
            ['key' => 'posts_per_page', 'label' => 'Posts Per Page', 'type' => 'number', 'default' => 20],
            ['key' => 'topics_per_page', 'label' => 'Topics Per Page', 'type' => 'number', 'default' => 25],
            ['key' => 'min_post_length', 'label' => 'Minimum Post Length', 'type' => 'number', 'default' => 10],
            ['key' => 'max_post_length', 'label' => 'Maximum Post Length', 'type' => 'number', 'default' => 10000],
            ['key' => 'rate_limit_posts', 'label' => 'Reply Rate Limit (minutes)', 'type' => 'number', 'default' => 2],
            ['key' => 'rate_limit_topics', 'label' => 'Topic Rate Limit (minutes)', 'type' => 'number', 'default' => 5],
            ['key' => 'email_notifications', 'label' => 'Enable Email Notifications', 'type' => 'checkbox', 'default' => true],
            ['key' => 'new_user_post_delay', 'label' => 'New User Post Delay (hours)', 'type' => 'number', 'default' => 0],
        ];
    }

    public function getSetting(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * Hook: Add "Community" to frontend navbar
     */
    public function addNavItem(array $items): array
    {
        $items[] = [
            'label' => $this->settings['forum_name'] ?? 'Community',
            'url' => '/community',
        ];
        return $items;
    }

    /**
     * Hook: Add "Community" to admin sidebar
     */
    public function addAdminNavItem(): void
    {
        $isActive = strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/community') === 0 ? ' active' : '';
        echo '<a href="/admin/community" class="nav-item' . $isActive . '">';
        echo '<span class="nav-icon">&#128172;</span> Community';
        echo '</a>';
    }

    /**
     * Hook: Inject CSS/JS assets on community pages
     */
    public function injectAssets(): void
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url($uri, PHP_URL_PATH);

        if (strpos($path, '/community') === 0) {
            echo '<link rel="stylesheet" href="/content/plugins/community-hub/assets/forum.css?v=1">';
        }
        if (strpos($path, '/admin/community') === 0) {
            echo '<link rel="stylesheet" href="/content/plugins/community-hub/assets/admin-forum.css?v=1">';
        }
    }

    private function loadSettings(): void
    {
        try {
            $pluginModel = new Plugin();
            $plugin = $pluginModel->getBySlug('community-hub');
            if ($plugin && !empty($plugin['settings'])) {
                $saved = json_decode($plugin['settings'], true) ?: [];
                $this->settings = array_merge($this->getDefaultSettings(), $saved);
            } else {
                $this->settings = $this->getDefaultSettings();
            }
        } catch (\Exception $e) {
            $this->settings = $this->getDefaultSettings();
        }
    }

    private function createTables(): void
    {
        $db = Database::getInstance();

        $db->query("CREATE TABLE IF NOT EXISTS forum_categories (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            parent_id INT UNSIGNED NULL,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(120) NOT NULL UNIQUE,
            description TEXT NULL,
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            admin_only TINYINT(1) NOT NULL DEFAULT 0,
            icon VARCHAR(10) NULL COMMENT 'Emoji icon',
            topic_count INT UNSIGNED NOT NULL DEFAULT 0,
            reply_count INT UNSIGNED NOT NULL DEFAULT 0,
            last_topic_id INT UNSIGNED NULL,
            last_reply_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_sort (sort_order),
            INDEX idx_active (is_active),
            INDEX idx_parent (parent_id),
            FOREIGN KEY (parent_id) REFERENCES forum_categories(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->query("CREATE TABLE IF NOT EXISTS forum_topics (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            category_id INT UNSIGNED NOT NULL,
            user_id INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            slug VARCHAR(220) NOT NULL,
            body TEXT NOT NULL,
            body_html TEXT NOT NULL,
            status ENUM('pending','approved','rejected','closed') NOT NULL DEFAULT 'approved',
            is_pinned TINYINT(1) NOT NULL DEFAULT 0,
            is_locked TINYINT(1) NOT NULL DEFAULT 0,
            view_count INT UNSIGNED NOT NULL DEFAULT 0,
            reply_count INT UNSIGNED NOT NULL DEFAULT 0,
            last_reply_id INT UNSIGNED NULL,
            last_reply_at DATETIME NULL,
            last_reply_user_id INT NULL,
            edited_at DATETIME NULL,
            edited_by INT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_category_status (category_id, status),
            INDEX idx_user (user_id),
            INDEX idx_slug (slug),
            INDEX idx_pinned_created (is_pinned DESC, created_at DESC),
            INDEX idx_last_reply (last_reply_at DESC),
            FOREIGN KEY (category_id) REFERENCES forum_categories(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->query("CREATE TABLE IF NOT EXISTS forum_replies (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            topic_id INT UNSIGNED NOT NULL,
            user_id INT NOT NULL,
            body TEXT NOT NULL,
            body_html TEXT NOT NULL,
            status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved',
            edited_at DATETIME NULL,
            edited_by INT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_topic_status (topic_id, status),
            INDEX idx_user (user_id),
            FOREIGN KEY (topic_id) REFERENCES forum_topics(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->query("CREATE TABLE IF NOT EXISTS forum_edit_history (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_type ENUM('topic','reply') NOT NULL,
            post_id INT UNSIGNED NOT NULL,
            user_id INT NOT NULL,
            old_body TEXT NOT NULL,
            old_title VARCHAR(200) NULL,
            edit_reason VARCHAR(200) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_post (post_type, post_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->query("CREATE TABLE IF NOT EXISTS forum_moderators (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            role ENUM('moderator','admin') NOT NULL DEFAULT 'moderator',
            category_id INT UNSIGNED NULL COMMENT 'NULL = global moderator',
            granted_by INT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_category (user_id, category_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES forum_categories(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->query("CREATE TABLE IF NOT EXISTS forum_reports (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            reporter_user_id INT NOT NULL,
            post_type ENUM('topic','reply') NOT NULL,
            post_id INT UNSIGNED NOT NULL,
            reason ENUM('spam','harassment','inappropriate','off_topic','other') NOT NULL,
            details TEXT NULL,
            status ENUM('pending','reviewed','dismissed') NOT NULL DEFAULT 'pending',
            reviewed_by INT NULL,
            reviewed_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_post (post_type, post_id),
            FOREIGN KEY (reporter_user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->query("CREATE TABLE IF NOT EXISTS forum_subscriptions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            topic_id INT UNSIGNED NOT NULL,
            notify_email TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_topic (user_id, topic_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (topic_id) REFERENCES forum_topics(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->query("CREATE TABLE IF NOT EXISTS forum_rate_limits (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            ip_address VARCHAR(45) NOT NULL,
            action_type ENUM('topic','reply','report') NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_action (user_id, action_type, created_at),
            INDEX idx_ip_action (ip_address, action_type, created_at),
            INDEX idx_cleanup (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
}
