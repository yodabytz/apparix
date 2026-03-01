<?php

namespace App\Controllers\CommunityHub;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Forum\ForumCategory;
use App\Models\Forum\ForumTopic;
use App\Models\Forum\ForumReply;
use App\Models\Forum\ForumModerator;
use App\Models\Forum\ForumReport;
use App\Models\Forum\ForumSubscription;
use App\Models\Forum\ForumEditHistory;
use App\Services\Forum\MarkdownParser;
use App\Services\Forum\ForumSpamService;
use App\Services\Forum\ForumEmailService;

class ForumController extends Controller
{
    private ForumCategory $categoryModel;
    private ForumTopic $topicModel;
    private ForumReply $replyModel;
    private ForumModerator $moderatorModel;
    private ForumReport $reportModel;
    private ForumSubscription $subscriptionModel;
    private ForumEditHistory $editHistoryModel;
    private array $pluginSettings;

    public function __construct()
    {
        parent::__construct();
        $this->categoryModel = new ForumCategory();
        $this->topicModel = new ForumTopic();
        $this->replyModel = new ForumReply();
        $this->moderatorModel = new ForumModerator();
        $this->reportModel = new ForumReport();
        $this->subscriptionModel = new ForumSubscription();
        $this->editHistoryModel = new ForumEditHistory();
        $this->pluginSettings = $this->loadPluginSettings();
    }

    /**
     * Forum homepage — category list
     */
    public function index(): void
    {
        $categories = $this->categoryModel->getActive();
        $forumName = $this->pluginSettings['forum_name'] ?? 'Community';

        $this->renderForum('categories', [
            'title' => $forumName,
            'categories' => $categories,
            'forumName' => $forumName,
        ]);
    }

    /**
     * Category — topic listing
     */
    public function category(): void
    {
        $slug = $_GET['slug'] ?? '';
        $category = $this->categoryModel->findBySlug($slug);

        if (!$category || !$category['is_active']) {
            $this->show404();
            return;
        }

        $page = max(1, (int)($this->get('page', 1)));
        $perPage = (int)($this->pluginSettings['topics_per_page'] ?? 25);
        $topics = $this->topicModel->getByCategory($category['id'], $page, $perPage);
        $totalTopics = $this->topicModel->countByCategory($category['id']);
        $totalPages = ceil($totalTopics / $perPage);

        $user = auth();
        $canModerate = $this->canModerate($user, $category['id']);

        $this->renderForum('topics', [
            'title' => $category['name'],
            'category' => $category,
            'topics' => $topics,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalTopics' => $totalTopics,
            'isAdmin' => $canModerate,
            'forumName' => $this->pluginSettings['forum_name'] ?? 'Community',
        ]);
    }

    /**
     * Single topic with replies
     */
    public function topic(): void
    {
        $slug = $_GET['slug'] ?? '';
        $topic = $this->topicModel->findBySlug($slug);

        if (!$topic || !in_array($topic['status'], ['approved', 'closed'])) {
            $this->show404();
            return;
        }

        // Deduplicate view counts per session
        $viewKey = 'forum_viewed_' . $topic['id'];
        if (empty($_SESSION[$viewKey])) {
            $this->topicModel->incrementViewCount($topic['id']);
            $_SESSION[$viewKey] = true;
        }

        $page = max(1, (int)($this->get('page', 1)));
        $perPage = (int)($this->pluginSettings['posts_per_page'] ?? 20);
        $replies = $this->replyModel->getByTopic($topic['id'], $page, $perPage);
        $totalReplies = $this->replyModel->countByTopic($topic['id']);
        $totalPages = ceil($totalReplies / $perPage);

        $user = auth();
        $isSubscribed = $user ? $this->subscriptionModel->isSubscribed($user['id'], $topic['id']) : false;
        $canModerate = $this->canModerate($user, $topic['category_id'] ?? null);
        $editCount = $this->editHistoryModel->getEditCount('topic', $topic['id']);

        // Build category array for breadcrumb + admin_only check
        $fullCategory = $this->categoryModel->find($topic['category_id']);
        $category = [
            'id' => $topic['category_id'],
            'name' => $topic['category_name'] ?? '',
            'slug' => $topic['category_slug'] ?? '',
            'admin_only' => $fullCategory['admin_only'] ?? 0,
        ];

        // Merge edit count into topic for view convenience
        $topic['edit_count'] = $editCount;
        $topic['is_closed'] = ($topic['status'] ?? '') === 'closed';

        $this->renderForum('topic', [
            'title' => $topic['title'],
            'topic' => $topic,
            'category' => $category,
            'replies' => $replies,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'per_page' => $perPage,
            ],
            'isSubscribed' => $isSubscribed,
            'isAdmin' => $canModerate,
            'formToken' => ForumSpamService::generateFormToken(),
            'forumName' => $this->pluginSettings['forum_name'] ?? 'Community',
        ]);
    }

    /**
     * New topic form
     */
    public function newTopicForm(): void
    {
        $user = auth();
        if (!$user) {
            $redirect = $_SERVER['REQUEST_URI'] ?? '/community';
            $this->redirect('/login?redirect=' . urlencode($redirect));
            return;
        }

        $categoryId = $_GET['category'] ?? '';
        $categories = $this->categoryModel->getActive();
        $canModerate = $this->canModerate($user);
        $selectedCategory = $categoryId ? (int)$categoryId : null;

        // Filter out admin_only categories for non-admins
        if (!$canModerate) {
            $categories = array_filter($categories, fn($c) => empty($c['admin_only']));
            $categories = array_values($categories);
        }

        $this->renderForum('new-topic', [
            'title' => 'New Topic',
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
            'formToken' => ForumSpamService::generateFormToken(),
            'forumName' => $this->pluginSettings['forum_name'] ?? 'Community',
        ]);
    }

    /**
     * Create a new topic
     */
    public function createTopic(): void
    {
        $user = auth();
        if (!$user) {
            $this->redirect('/login?redirect=/community/new-topic');
            return;
        }

        $this->requireValidCSRF();

        $title = trim($this->post('title', ''));
        $body = trim($this->post('body', ''));
        $categoryId = (int)$this->post('category_id', 0);

        // Validate
        if (empty($title) || mb_strlen($title) < 5) {
            setFlash('error', 'Title must be at least 5 characters.');
            $this->redirect('/community/new-topic');
            return;
        }
        if (mb_strlen($title) > 200) {
            setFlash('error', 'Title cannot exceed 200 characters.');
            $this->redirect('/community/new-topic');
            return;
        }

        $category = $this->categoryModel->find($categoryId);
        if (!$category || !$category['is_active']) {
            setFlash('error', 'Invalid category.');
            $this->redirect('/community/new-topic');
            return;
        }

        // Admin-only category: only admins/moderators can post
        if (!empty($category['admin_only']) && !$this->canModerate($user, $categoryId)) {
            setFlash('error', 'Only administrators can post in this category.');
            $this->redirect('/community/category/' . ($category['slug'] ?? ''));
            return;
        }

        // Admins/moderators bypass spam checks and moderation
        $isMod = $this->canModerate($user, $categoryId);

        if (!$isMod) {
            // Spam check
            $spamResult = ForumSpamService::check([
                'body' => $body,
                'website_url' => $this->post('website_url', ''),
                '_form_token' => $this->post('_form_token', ''),
                '_action_type' => 'topic',
            ], $this->pluginSettings, $user['id']);

            if (!$spamResult['passed']) {
                if ($spamResult['reason'] === 'spam_detected') {
                    setFlash('success', 'Your topic has been submitted for review.');
                    $this->redirect('/community');
                    return;
                }
                setFlash('error', $spamResult['reason']);
                $this->redirect('/community/new-topic');
                return;
            }
        }

        // Determine status — moderators always approved
        $status = 'approved';
        if (!$isMod) {
            $shouldModerate = ForumSpamService::shouldModerate($user['id'], $this->pluginSettings);
            $status = $shouldModerate ? 'pending' : 'approved';
        }

        $slug = $this->topicModel->generateSlug($title);
        $bodyHtml = MarkdownParser::parse($body);

        $topicId = Database::getInstance()->insert(
            "INSERT INTO forum_topics (category_id, user_id, title, slug, body, body_html, status) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$categoryId, $user['id'], $title, $slug, $body, $bodyHtml, $status]
        );

        // Record rate limit
        $rateLimitModel = new \App\Models\Forum\ForumRateLimit();
        $rateLimitModel->record($user['id'], $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 'topic');

        if ($status === 'approved') {
            $this->categoryModel->incrementTopicCount($categoryId);
            $this->categoryModel->updateLastActivity($categoryId, $topicId, date('Y-m-d H:i:s'));

            // Auto-subscribe author
            $this->subscriptionModel->subscribe($user['id'], $topicId);

            setFlash('success', 'Topic created successfully!');
            $this->redirect('/community/topic/' . $slug);
        } else {
            setFlash('success', 'Your topic has been submitted and is awaiting moderator approval.');
            $this->redirect('/community/category/' . $category['slug']);
        }
    }

    /**
     * Create a reply
     */
    public function createReply(): void
    {
        $user = auth();
        if (!$user) {
            $this->redirect('/login');
            return;
        }

        $this->requireValidCSRF();

        $topicId = (int)$this->post('topic_id', 0);
        $body = trim($this->post('body', ''));

        $topic = $this->topicModel->find($topicId);
        if (!$topic || $topic['status'] === 'closed' || $topic['is_locked']) {
            setFlash('error', 'This topic is closed for replies.');
            $this->redirect('/community');
            return;
        }

        // Admin-only category: only admins/moderators can reply
        $category = $this->categoryModel->find($topic['category_id']);
        if ($category && !empty($category['admin_only']) && !$this->canModerate($user, $topic['category_id'])) {
            setFlash('error', 'Only administrators can reply in this category.');
            $this->redirect('/community/topic/' . $topic['slug']);
            return;
        }

        // Admins/moderators bypass spam checks and moderation
        $isMod = $this->canModerate($user, $topic['category_id'] ?? null);

        if (!$isMod) {
            // Spam check
            $spamResult = ForumSpamService::check([
                'body' => $body,
                'website_url' => $this->post('website_url', ''),
                '_form_token' => $this->post('_form_token', ''),
                '_action_type' => 'reply',
            ], $this->pluginSettings, $user['id']);

            if (!$spamResult['passed']) {
                if ($spamResult['reason'] === 'spam_detected') {
                    setFlash('success', 'Your reply has been submitted for review.');
                    $this->redirect('/community/topic/' . $topic['slug']);
                    return;
                }
                setFlash('error', $spamResult['reason']);
                $this->redirect('/community/topic/' . $topic['slug']);
                return;
            }
        }

        // Moderators always approved
        $status = 'approved';
        if (!$isMod) {
            $shouldModerate = ForumSpamService::shouldModerate($user['id'], $this->pluginSettings);
            $status = $shouldModerate ? 'pending' : 'approved';
        }

        $bodyHtml = MarkdownParser::parse($body);

        $replyId = Database::getInstance()->insert(
            "INSERT INTO forum_replies (topic_id, user_id, body, body_html, status) VALUES (?, ?, ?, ?, ?)",
            [$topicId, $user['id'], $body, $bodyHtml, $status]
        );

        // Record rate limit
        $rateLimitModel = new \App\Models\Forum\ForumRateLimit();
        $rateLimitModel->record($user['id'], $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 'reply');

        if ($status === 'approved') {
            $this->topicModel->updateLastReply($topicId, $replyId, $user['id']);
            $this->categoryModel->incrementReplyCount($topic['category_id']);
            $this->categoryModel->updateLastActivity($topic['category_id'], null, date('Y-m-d H:i:s'));

            // Auto-subscribe replier
            $this->subscriptionModel->subscribe($user['id'], $topicId);

            // Send email notifications
            try {
                ForumEmailService::sendReplyNotifications(
                    $topic,
                    ['body' => $body],
                    ['id' => $user['id'], 'first_name' => $user['name'] ?? '', 'last_name' => '']
                );
            } catch (\Exception $e) {
                // Don't fail for email errors
            }

            setFlash('success', 'Reply posted!');
        } else {
            setFlash('success', 'Your reply has been submitted and is awaiting moderator approval.');
        }

        $this->redirect('/community/topic/' . $topic['slug']);
    }

    /**
     * Edit topic form
     */
    public function editTopicForm(): void
    {
        $user = auth();
        if (!$user) {
            $this->redirect('/login');
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        $topic = $this->topicModel->findById($id);

        if (!$topic) {
            $this->show404();
            return;
        }

        $canEdit = ((int)$topic['user_id'] === (int)$user['id']) || $this->canModerate($user, $topic['category_id']);
        if (!$canEdit) {
            setFlash('error', 'You do not have permission to edit this topic.');
            $this->redirect('/community/topic/' . $topic['slug']);
            return;
        }

        $this->renderForum('edit-post', [
            'title' => 'Edit Topic',
            'post' => $topic,
            'topic' => $topic,
            'postType' => 'topic',
            'forumName' => $this->pluginSettings['forum_name'] ?? 'Community',
        ]);
    }

    /**
     * Update a topic
     */
    public function updateTopic(): void
    {
        $user = auth();
        if (!$user) {
            $this->redirect('/login');
            return;
        }

        $this->requireValidCSRF();

        $id = (int)($_GET['id'] ?? 0);
        $topic = $this->topicModel->findById($id);

        if (!$topic) {
            $this->show404();
            return;
        }

        $canEdit = ((int)$topic['user_id'] === (int)$user['id']) || $this->canModerate($user, $topic['category_id']);
        if (!$canEdit) {
            setFlash('error', 'You do not have permission to edit this topic.');
            $this->redirect('/community/topic/' . $topic['slug']);
            return;
        }

        $title = trim($this->post('title', ''));
        $body = trim($this->post('body', ''));
        $editReason = trim($this->post('edit_reason', ''));

        if (empty($title) || mb_strlen($title) < 5) {
            setFlash('error', 'Title must be at least 5 characters.');
            $this->redirect('/community/edit/topic/' . $id);
            return;
        }

        $minLength = (int)($this->pluginSettings['min_post_length'] ?? 10);
        $maxLength = (int)($this->pluginSettings['max_post_length'] ?? 10000);
        if (mb_strlen($body) < $minLength) {
            setFlash('error', "Post must be at least {$minLength} characters.");
            $this->redirect('/community/edit/topic/' . $id);
            return;
        }
        if (mb_strlen($body) > $maxLength) {
            setFlash('error', "Post cannot exceed {$maxLength} characters.");
            $this->redirect('/community/edit/topic/' . $id);
            return;
        }

        // Record edit history
        $this->editHistoryModel->record('topic', $id, $user['id'], $topic['body'], $topic['title'], $editReason);

        $bodyHtml = MarkdownParser::parse($body);
        $slug = ($title !== $topic['title']) ? $this->topicModel->generateSlug($title, $id) : $topic['slug'];

        Database::getInstance()->update(
            "UPDATE forum_topics SET title = ?, slug = ?, body = ?, body_html = ?, edited_at = ?, edited_by = ? WHERE id = ?",
            [$title, $slug, $body, $bodyHtml, date('Y-m-d H:i:s'), $user['id'], $id]
        );

        setFlash('success', 'Topic updated.');
        $this->redirect('/community/topic/' . $slug);
    }

    /**
     * Edit reply form
     */
    public function editReplyForm(): void
    {
        $user = auth();
        if (!$user) {
            $this->redirect('/login');
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        $reply = $this->replyModel->findWithUser($id);

        if (!$reply) {
            $this->show404();
            return;
        }

        $canEdit = ((int)$reply['user_id'] === (int)$user['id']) || $this->canModerate($user, $reply['category_id'] ?? null);
        if (!$canEdit) {
            setFlash('error', 'You do not have permission to edit this reply.');
            $this->redirect('/community/topic/' . $reply['topic_slug']);
            return;
        }

        $replyTopic = $this->topicModel->findById($reply['topic_id'] ?? 0);

        $this->renderForum('edit-post', [
            'title' => 'Edit Reply',
            'post' => $reply,
            'topic' => $replyTopic,
            'postType' => 'reply',
            'forumName' => $this->pluginSettings['forum_name'] ?? 'Community',
        ]);
    }

    /**
     * Update a reply
     */
    public function updateReply(): void
    {
        $user = auth();
        if (!$user) {
            $this->redirect('/login');
            return;
        }

        $this->requireValidCSRF();

        $id = (int)($_GET['id'] ?? 0);
        $reply = $this->replyModel->findWithUser($id);

        if (!$reply) {
            $this->show404();
            return;
        }

        $canEdit = ((int)$reply['user_id'] === (int)$user['id']) || $this->canModerate($user, $reply['category_id'] ?? null);
        if (!$canEdit) {
            setFlash('error', 'You do not have permission to edit this reply.');
            $this->redirect('/community/topic/' . $reply['topic_slug']);
            return;
        }

        $body = trim($this->post('body', ''));
        $editReason = trim($this->post('edit_reason', ''));

        $minLength = (int)($this->pluginSettings['min_post_length'] ?? 10);
        $maxLength = (int)($this->pluginSettings['max_post_length'] ?? 10000);
        if (mb_strlen($body) < $minLength) {
            setFlash('error', "Reply must be at least {$minLength} characters.");
            $this->redirect('/community/edit/reply/' . $id);
            return;
        }
        if (mb_strlen($body) > $maxLength) {
            setFlash('error', "Reply cannot exceed {$maxLength} characters.");
            $this->redirect('/community/edit/reply/' . $id);
            return;
        }

        // Record edit history
        $this->editHistoryModel->record('reply', $id, $user['id'], $reply['body'], null, $editReason);

        $bodyHtml = MarkdownParser::parse($body);

        Database::getInstance()->update(
            "UPDATE forum_replies SET body = ?, body_html = ?, edited_at = ?, edited_by = ? WHERE id = ?",
            [$body, $bodyHtml, date('Y-m-d H:i:s'), $user['id'], $id]
        );

        setFlash('success', 'Reply updated.');
        $this->redirect('/community/topic/' . $reply['topic_slug']);
    }

    /**
     * User profile
     */
    public function userProfile(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $user = Database::getInstance()->selectOne(
            "SELECT id, first_name, last_name, created_at FROM users WHERE id = ?",
            [$id]
        );

        if (!$user) {
            $this->show404();
            return;
        }

        $topics = $this->topicModel->getByUser($id, 20);
        $replies = $this->replyModel->getByUser($id, 20);
        $topicCount = $this->topicModel->countByUser($id);
        $replyCount = $this->replyModel->countByUser($id);

        // Build profile array matching what the view expects
        $profile = [
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'display_name' => trim($user['first_name'] . ' ' . $user['last_name']) ?: 'Community Member',
            'created_at' => $user['created_at'],
            'topic_count' => $topicCount,
            'reply_count' => $replyCount,
        ];

        $this->renderForum('profile', [
            'title' => $profile['display_name'],
            'profile' => $profile,
            'recentTopics' => $topics,
            'recentReplies' => $replies,
            'forumName' => $this->pluginSettings['forum_name'] ?? 'Community',
        ]);
    }

    /**
     * Subscribe to a topic
     */
    public function subscribe(): void
    {
        $user = auth();
        if (!$user) {
            $this->json(['error' => 'Login required'], 401);
            return;
        }

        $this->requireValidCSRF();
        $topicId = (int)$this->post('topic_id', 0);
        $this->subscriptionModel->subscribe($user['id'], $topicId);

        $topic = $this->topicModel->find($topicId);
        setFlash('success', 'Subscribed to topic notifications.');
        $this->redirect('/community/topic/' . ($topic['slug'] ?? ''));
    }

    /**
     * Unsubscribe from a topic (supports GET for email links)
     */
    public function unsubscribe(): void
    {
        // GET request from email link
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $topicId = (int)($_GET['topic'] ?? 0);
            $token = $_GET['token'] ?? '';

            if ($topicId && $token) {
                // Find the subscriber by checking all subscribers
                $db = Database::getInstance();
                $subs = $db->select(
                    "SELECT user_id FROM forum_subscriptions WHERE topic_id = ?",
                    [$topicId]
                );
                foreach ($subs as $sub) {
                    if (ForumEmailService::verifyUnsubToken($sub['user_id'], $topicId, $token)) {
                        $this->subscriptionModel->unsubscribe($sub['user_id'], $topicId);
                        setFlash('success', 'You have been unsubscribed from this topic.');
                        $topic = $this->topicModel->find($topicId);
                        $this->redirect('/community/topic/' . ($topic['slug'] ?? ''));
                        return;
                    }
                }
            }

            setFlash('error', 'Invalid unsubscribe link.');
            $this->redirect('/community');
            return;
        }

        // POST request from UI
        $user = auth();
        if (!$user) {
            $this->json(['error' => 'Login required'], 401);
            return;
        }

        $this->requireValidCSRF();
        $topicId = (int)$this->post('topic_id', 0);
        $this->subscriptionModel->unsubscribe($user['id'], $topicId);

        $topic = $this->topicModel->find($topicId);
        setFlash('success', 'Unsubscribed from topic notifications.');
        $this->redirect('/community/topic/' . ($topic['slug'] ?? ''));
    }

    /**
     * Report a post
     */
    public function report(): void
    {
        $user = auth();
        if (!$user) {
            $this->json(['error' => 'Login required'], 401);
            return;
        }

        $this->requireValidCSRF();

        $postType = $this->post('post_type', '');
        $postId = (int)$this->post('post_id', 0);
        $reason = $this->post('reason', '');
        $details = trim($this->post('details', ''));

        if (!in_array($postType, ['topic', 'reply']) || !in_array($reason, ['spam', 'harassment', 'inappropriate', 'off_topic', 'other'])) {
            setFlash('error', 'Invalid report.');
            $this->redirect('/community');
            return;
        }

        if ($this->reportModel->hasReported($user['id'], $postType, $postId)) {
            setFlash('error', 'You have already reported this post.');
            $this->redirect('/community');
            return;
        }

        Database::getInstance()->insert(
            "INSERT INTO forum_reports (reporter_user_id, post_type, post_id, reason, details) VALUES (?, ?, ?, ?, ?)",
            [$user['id'], $postType, $postId, $reason, $details ?: null]
        );

        // Send email to moderators
        try {
            ForumEmailService::sendReportNotification(
                ['post_type' => $postType, 'reason' => $reason, 'details' => $details],
                ''
            );
        } catch (\Exception $e) {
            // Don't fail
        }

        setFlash('success', 'Thank you for your report. A moderator will review it.');

        // Redirect back to the topic
        if ($postType === 'topic') {
            $topic = $this->topicModel->find($postId);
            $this->redirect('/community/topic/' . ($topic['slug'] ?? ''));
        } else {
            $reply = $this->replyModel->findWithUser($postId);
            $this->redirect('/community/topic/' . ($reply['topic_slug'] ?? ''));
        }
    }

    /**
     * View edit history
     */
    public function editHistory(): void
    {
        $type = $_GET['type'] ?? '';
        $id = (int)($_GET['id'] ?? 0);

        if (!in_array($type, ['topic', 'reply'])) {
            $this->show404();
            return;
        }

        $history = $this->editHistoryModel->getHistory($type, $id);

        if ($type === 'topic') {
            $post = $this->topicModel->findById($id);
        } else {
            $post = $this->replyModel->findWithUser($id);
        }

        if (!$post) {
            $this->show404();
            return;
        }

        // For breadcrumb: get the topic (even if editing a reply)
        if ($type === 'topic') {
            $topic = $post;
        } else {
            $topic = $this->topicModel->findById($post['topic_id'] ?? 0);
        }

        $this->renderForum('edit-history', [
            'title' => 'Edit History',
            'edits' => $history,
            'topic' => $topic,
            'post' => $post,
            'postType' => $type,
            'forumName' => $this->pluginSettings['forum_name'] ?? 'Community',
        ]);
    }

    private function canModerate(?array $user, ?int $categoryId = null): bool
    {
        if (!$user) return false;

        // Admin users
        if (!empty($_SESSION['admin_id'])) return true;

        // Forum moderators
        return $this->moderatorModel->isModerator($user['id'], $categoryId);
    }

    private function renderForum(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        ob_start();
        $viewFile = dirname(__DIR__) . '/views/frontend/' . $view . '.php';
        if (!file_exists($viewFile)) {
            ob_end_clean();
            die("Community Hub view not found: {$view}");
        }
        include $viewFile;
        $content = ob_get_clean();

        $layoutFile = dirname(__DIR__, 4) . '/app/Views/layouts/main.php';
        include $layoutFile;
    }

    private function loadPluginSettings(): array
    {
        try {
            $pluginModel = new \App\Models\Plugin();
            $plugin = $pluginModel->getBySlug('community-hub');
            if ($plugin && !empty($plugin['settings'])) {
                $saved = json_decode($plugin['settings'], true) ?: [];
                return array_merge([
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
                ], $saved);
            }
        } catch (\Exception $e) {
            // Fall through
        }
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
}
