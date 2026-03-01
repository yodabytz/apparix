<?php

namespace App\Controllers\CommunityHub;

use App\Core\Controller;
use App\Core\Database;
use App\Models\AdminUser;
use App\Models\Forum\ForumCategory;
use App\Models\Forum\ForumTopic;
use App\Models\Forum\ForumReply;
use App\Models\Forum\ForumModerator;
use App\Models\Forum\ForumReport;
use App\Services\Forum\ForumEmailService;

class AdminForumController extends Controller
{
    private ForumCategory $categoryModel;
    private ForumTopic $topicModel;
    private ForumReply $replyModel;
    private ForumModerator $moderatorModel;
    private ForumReport $reportModel;
    private ?array $admin = null;

    public function __construct()
    {
        parent::__construct();
        $this->authenticateAdmin();
        $this->categoryModel = new ForumCategory();
        $this->topicModel = new ForumTopic();
        $this->replyModel = new ForumReply();
        $this->moderatorModel = new ForumModerator();
        $this->reportModel = new ForumReport();
    }

    private function authenticateAdmin(): void
    {
        $token = $_COOKIE['admin_token'] ?? null;
        if (!$token) {
            $this->redirect('/admin/login');
            exit;
        }

        $adminModel = new AdminUser();
        $session = $adminModel->validateSession($token);
        if (!$session) {
            setcookie('admin_token', '', ['expires' => time() - 3600, 'path' => '/admin']);
            $this->redirect('/admin/login');
            exit;
        }

        $this->admin = $session;
        $_SESSION['admin_id'] = $session['id'];
    }

    public function dashboard(): void
    {
        $topicStats = $this->topicModel->getStats();
        $replyStats = $this->replyModel->getStats();
        $pendingReports = $this->reportModel->countPending();
        $categories = $this->categoryModel->getAll();
        $recentTopics = $this->topicModel->getRecent(10);
        $pendingTopics = $this->topicModel->getPending();
        $pendingReplies = $this->replyModel->getPending();

        $this->renderAdmin('dashboard', [
            'title' => 'Community',
            'topicStats' => $topicStats,
            'replyStats' => $replyStats,
            'pendingReports' => $pendingReports,
            'categories' => $categories,
            'categoryCount' => count($categories),
            'recentTopics' => $recentTopics,
            'pendingTopics' => $pendingTopics,
            'pendingReplies' => $pendingReplies,
        ]);
    }

    public function categories(): void
    {
        $categories = $this->categoryModel->getAll();
        $this->renderAdmin('categories', [
            'title' => 'Forum Categories',
            'categories' => $categories,
        ]);
    }

    public function storeCategory(): void
    {
        $this->requireValidCSRF();

        $name = trim($this->post('name', ''));
        $description = trim($this->post('description', ''));
        $icon = trim($this->post('icon', ''));
        $parentId = $this->post('parent_id', '') ?: null;

        if (empty($name)) {
            setFlash('error', 'Category name is required.');
            $this->redirect('/admin/community');
            return;
        }

        $slug = $this->categoryModel->generateSlug($name);
        $maxOrder = Database::getInstance()->selectOne("SELECT MAX(sort_order) as mx FROM forum_categories");
        $adminOnly = (int)$this->post('admin_only', 0);

        Database::getInstance()->insert(
            "INSERT INTO forum_categories (name, slug, description, icon, sort_order, parent_id, admin_only) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$name, $slug, $description ?: null, $icon ?: null, ($maxOrder['mx'] ?? 0) + 1, $parentId ? (int)$parentId : null, $adminOnly]
        );

        setFlash('success', 'Category created.');
        $this->redirect('/admin/community');
    }

    public function updateCategory(): void
    {
        $this->requireValidCSRF();

        $id = (int)$this->post('id', 0);
        $name = trim($this->post('name', ''));
        $description = trim($this->post('description', ''));
        $icon = trim($this->post('icon', ''));
        $isActive = (int)$this->post('is_active', 1);
        $parentId = $this->post('parent_id', '') ?: null;

        if (empty($name) || !$id) {
            setFlash('error', 'Category name is required.');
            $this->redirect('/admin/community');
            return;
        }

        $slug = $this->categoryModel->generateSlug($name, $id);
        $adminOnly = (int)$this->post('admin_only', 0);

        Database::getInstance()->update(
            "UPDATE forum_categories SET name = ?, slug = ?, description = ?, icon = ?, is_active = ?, parent_id = ?, admin_only = ? WHERE id = ?",
            [$name, $slug, $description ?: null, $icon ?: null, $isActive, $parentId ? (int)$parentId : null, $adminOnly, $id]
        );

        setFlash('success', 'Category updated.');
        $this->redirect('/admin/community');
    }

    public function deleteCategory(): void
    {
        $this->requireValidCSRF();
        $id = (int)$this->post('id', 0);

        if ($id) {
            Database::getInstance()->query("DELETE FROM forum_categories WHERE id = ?", [$id]);
            setFlash('success', 'Category deleted.');
        }

        $this->redirect('/admin/community');
    }

    public function reorderCategories(): void
    {
        $this->requireValidCSRF();
        $order = $this->post('order', []);

        if (is_array($order)) {
            $this->categoryModel->updateSortOrder($order);
        }

        $this->json(['success' => true]);
    }

    public function moderation(): void
    {
        $pendingTopics = $this->topicModel->getPending();
        $pendingReplies = $this->replyModel->getPending();

        $this->renderAdmin('moderation', [
            'title' => 'Moderation Queue',
            'pendingTopics' => $pendingTopics,
            'pendingReplies' => $pendingReplies,
        ]);
    }

    public function approvePost(): void
    {
        $this->requireValidCSRF();

        $type = $this->post('type', '');
        $id = (int)$this->post('id', 0);

        if ($type === 'topic') {
            $topic = $this->topicModel->findById($id);
            if ($topic) {
                Database::getInstance()->update("UPDATE forum_topics SET status = 'approved' WHERE id = ?", [$id]);
                $this->categoryModel->incrementTopicCount($topic['category_id']);
                $this->categoryModel->updateLastActivity($topic['category_id'], $id, date('Y-m-d H:i:s'));

                // Notify author
                try {
                    $authorUser = Database::getInstance()->selectOne("SELECT email FROM users WHERE id = ?", [$topic['user_id']]);
                    if ($authorUser && !empty($authorUser['email'])) {
                        ForumEmailService::sendTopicApproved($topic, $authorUser['email']);
                    }
                } catch (\Exception $e) {}
            }
        } elseif ($type === 'reply') {
            $reply = $this->replyModel->findWithUser($id);
            if ($reply) {
                Database::getInstance()->update("UPDATE forum_replies SET status = 'approved' WHERE id = ?", [$id]);

                $topic = $this->topicModel->find($reply['topic_id']);
                if ($topic) {
                    $this->topicModel->updateLastReply($reply['topic_id'], $id, $reply['user_id']);
                    $this->categoryModel->incrementReplyCount($topic['category_id']);
                }
            }
        }

        setFlash('success', ucfirst($type) . ' approved.');
        $this->redirect('/admin/community/moderation');
    }

    public function rejectPost(): void
    {
        $this->requireValidCSRF();

        $type = $this->post('type', '');
        $id = (int)$this->post('id', 0);

        if ($type === 'topic') {
            $topic = $this->topicModel->findById($id);
            if ($topic) {
                Database::getInstance()->update("UPDATE forum_topics SET status = 'rejected' WHERE id = ?", [$id]);
                try {
                    $authorUser = Database::getInstance()->selectOne("SELECT email FROM users WHERE id = ?", [$topic['user_id']]);
                    if ($authorUser && !empty($authorUser['email'])) {
                        ForumEmailService::sendTopicRejected($topic, $authorUser['email']);
                    }
                } catch (\Exception $e) {}
            }
        } elseif ($type === 'reply') {
            Database::getInstance()->update("UPDATE forum_replies SET status = 'rejected' WHERE id = ?", [$id]);
        }

        setFlash('success', ucfirst($type) . ' rejected.');
        $this->redirect('/admin/community/moderation');
    }

    public function moderators(): void
    {
        $moderators = $this->moderatorModel->getAllWithUsers();
        $categories = $this->categoryModel->getAll();

        $this->renderAdmin('moderators', [
            'title' => 'Forum Moderators',
            'moderators' => $moderators,
            'categories' => $categories,
        ]);
    }

    public function addModerator(): void
    {
        $this->requireValidCSRF();

        $email = trim($this->post('email', ''));
        $role = $this->post('role', 'moderator');
        $categoryId = $this->post('category_id', '') ?: null;

        $user = Database::getInstance()->selectOne("SELECT id FROM users WHERE email = ?", [$email]);
        if (!$user) {
            setFlash('error', 'No user found with that email address.');
            $this->redirect('/admin/community/moderators');
            return;
        }

        $success = $this->moderatorModel->addModerator(
            $user['id'],
            $role,
            $categoryId ? (int)$categoryId : null,
            $_SESSION['admin_id'] ?? null
        );

        if ($success) {
            setFlash('success', 'Moderator added.');
        } else {
            setFlash('error', 'User is already a moderator for that scope.');
        }

        $this->redirect('/admin/community/moderators');
    }

    public function removeModerator(): void
    {
        $this->requireValidCSRF();

        $id = (int)$this->post('id', 0);
        if ($id) {
            $this->moderatorModel->removeById($id);
            setFlash('success', 'Moderator removed.');
        }

        $this->redirect('/admin/community/moderators');
    }

    public function reports(): void
    {
        $reports = $this->reportModel->getPending();

        $this->renderAdmin('reports', [
            'title' => 'Reported Posts',
            'reports' => $reports,
        ]);
    }

    public function reviewReport(): void
    {
        $this->requireValidCSRF();

        $id = (int)$this->post('id', 0);
        $status = $this->post('status', 'reviewed');

        if ($id && in_array($status, ['reviewed', 'dismissed'])) {
            $this->reportModel->review($id, $status, $_SESSION['admin_id'] ?? 0);
            setFlash('success', 'Report ' . $status . '.');
        }

        $this->redirect('/admin/community/reports');
    }

    public function topicDetail(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $topic = $this->topicModel->findById($id);

        if (!$topic) {
            $this->show404();
            return;
        }

        $replies = $this->replyModel->getByTopic($id, 1, 100);

        $this->renderAdmin('topic-detail', [
            'title' => $topic['title'],
            'topic' => $topic,
            'replies' => $replies,
        ]);
    }

    public function lockTopic(): void
    {
        $this->requireValidCSRF();
        $id = (int)$this->post('id', 0);
        Database::getInstance()->update("UPDATE forum_topics SET is_locked = 1 WHERE id = ?", [$id]);
        setFlash('success', 'Topic locked.');
        $this->redirect('/admin/community/topic/' . $id);
    }

    public function unlockTopic(): void
    {
        $this->requireValidCSRF();
        $id = (int)$this->post('id', 0);
        Database::getInstance()->update("UPDATE forum_topics SET is_locked = 0 WHERE id = ?", [$id]);
        setFlash('success', 'Topic unlocked.');
        $this->redirect('/admin/community/topic/' . $id);
    }

    public function pinTopic(): void
    {
        $this->requireValidCSRF();
        $id = (int)$this->post('id', 0);
        Database::getInstance()->update("UPDATE forum_topics SET is_pinned = 1 WHERE id = ?", [$id]);
        setFlash('success', 'Topic pinned.');
        $this->redirect('/admin/community/topic/' . $id);
    }

    public function unpinTopic(): void
    {
        $this->requireValidCSRF();
        $id = (int)$this->post('id', 0);
        Database::getInstance()->update("UPDATE forum_topics SET is_pinned = 0 WHERE id = ?", [$id]);
        setFlash('success', 'Topic unpinned.');
        $this->redirect('/admin/community/topic/' . $id);
    }

    public function closeTopic(): void
    {
        $this->requireValidCSRF();
        $id = (int)$this->post('id', 0);
        Database::getInstance()->update("UPDATE forum_topics SET status = 'closed' WHERE id = ?", [$id]);
        setFlash('success', 'Topic closed.');
        $this->redirect('/admin/community/topic/' . $id);
    }

    public function deleteTopic(): void
    {
        $this->requireValidCSRF();
        $id = (int)$this->post('id', 0);

        $topic = $this->topicModel->find($id);
        if ($topic) {
            Database::getInstance()->query("DELETE FROM forum_topics WHERE id = ?", [$id]);
            $this->categoryModel->decrementTopicCount($topic['category_id']);
            setFlash('success', 'Topic deleted.');
        }

        $this->redirect('/admin/community');
    }

    public function deleteReply(): void
    {
        $this->requireValidCSRF();
        $id = (int)$this->post('id', 0);

        $reply = $this->replyModel->findWithUser($id);
        if ($reply) {
            Database::getInstance()->query("DELETE FROM forum_replies WHERE id = ?", [$id]);
            $this->topicModel->decrementReplyCount($reply['topic_id']);

            $topic = $this->topicModel->find($reply['topic_id']);
            if ($topic) {
                $this->categoryModel->decrementReplyCount($topic['category_id']);
            }

            setFlash('success', 'Reply deleted.');
            $this->redirect('/admin/community/topic/' . $reply['topic_id']);
            return;
        }

        $this->redirect('/admin/community');
    }

    public function settings(): void
    {
        $pluginModel = new \App\Models\Plugin();
        $plugin = $pluginModel->getBySlug('community-hub');
        $settings = [];
        if ($plugin && !empty($plugin['settings'])) {
            $settings = json_decode($plugin['settings'], true) ?: [];
        }

        $defaults = [
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

        $settings = array_merge($defaults, $settings);

        $this->renderAdmin('settings', [
            'title' => 'Forum Settings',
            'settings' => $settings,
        ]);
    }

    public function updateSettings(): void
    {
        $this->requireValidCSRF();

        $settings = [
            'forum_name' => trim($this->post('forum_name', 'Community')),
            'require_approval' => (bool)$this->post('require_approval', false),
            'posts_per_page' => max(5, (int)$this->post('posts_per_page', 20)),
            'topics_per_page' => max(5, (int)$this->post('topics_per_page', 25)),
            'min_post_length' => max(1, (int)$this->post('min_post_length', 10)),
            'max_post_length' => max(100, (int)$this->post('max_post_length', 10000)),
            'rate_limit_posts' => max(0, (int)$this->post('rate_limit_posts', 2)),
            'rate_limit_topics' => max(0, (int)$this->post('rate_limit_topics', 5)),
            'email_notifications' => (bool)$this->post('email_notifications', false),
            'new_user_post_delay' => max(0, (int)$this->post('new_user_post_delay', 0)),
        ];

        $pluginModel = new \App\Models\Plugin();
        $plugin = $pluginModel->getBySlug('community-hub');

        if ($plugin) {
            Database::getInstance()->update(
                "UPDATE plugins SET settings = ? WHERE id = ?",
                [json_encode($settings), $plugin['id']]
            );
        }

        setFlash('success', 'Forum settings updated.');
        $this->redirect('/admin/community/settings');
    }

    private function renderAdmin(string $view, array $data = []): void
    {
        $admin = $this->admin;

        extract($data, EXTR_SKIP);

        ob_start();
        $viewFile = dirname(__DIR__) . '/views/admin/' . $view . '.php';
        if (!file_exists($viewFile)) {
            ob_end_clean();
            die("Community Hub admin view not found: {$view}");
        }
        include $viewFile;
        $content = ob_get_clean();

        $layoutFile = dirname(__DIR__, 4) . '/app/Views/layouts/admin.php';
        include $layoutFile;
    }
}
