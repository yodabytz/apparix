<div class="community-card" style="margin-bottom:0;">
    <h2>Forum Settings</h2>
</div>

<form method="POST" action="/admin/community/settings/update">
    <?= csrfField() ?>

    <!-- General Section -->
    <div class="community-card">
        <div class="community-section-title">General</div>
        <div class="community-form-group">
            <label for="setting-forum-name">Forum Name</label>
            <input type="text" id="setting-forum-name" name="forum_name"
                value="<?= htmlspecialchars($settings['forum_name'] ?? 'Community') ?>"
                placeholder="Community Forum">
        </div>
        <div class="community-form-group">
            <div class="community-checkbox-group">
                <input type="checkbox" id="setting-email-notifications" name="email_notifications" value="1"
                    <?= ($settings['email_notifications'] ?? 0) ? 'checked' : '' ?>>
                <label for="setting-email-notifications" style="margin-bottom:0;">Enable Email Notifications</label>
            </div>
        </div>
    </div>

    <!-- Moderation Section -->
    <div class="community-card">
        <div class="community-section-title">Moderation</div>
        <div class="community-form-group">
            <div class="community-checkbox-group">
                <input type="checkbox" id="setting-require-approval" name="require_approval" value="1"
                    <?= ($settings['require_approval'] ?? 0) ? 'checked' : '' ?>>
                <label for="setting-require-approval" style="margin-bottom:0;">Require Approval for New Posts</label>
            </div>
        </div>
        <div class="community-form-row">
            <div class="community-form-group">
                <label for="setting-min-post-length">Minimum Post Length (characters)</label>
                <input type="number" id="setting-min-post-length" name="min_post_length"
                    value="<?= htmlspecialchars($settings['min_post_length'] ?? 10) ?>" min="1">
            </div>
            <div class="community-form-group">
                <label for="setting-max-post-length">Maximum Post Length (characters)</label>
                <input type="number" id="setting-max-post-length" name="max_post_length"
                    value="<?= htmlspecialchars($settings['max_post_length'] ?? 10000) ?>" min="100">
            </div>
        </div>
    </div>

    <!-- Anti-Spam Section -->
    <div class="community-card">
        <div class="community-section-title">Anti-Spam</div>
        <div class="community-form-row">
            <div class="community-form-group">
                <label for="setting-rate-limit-posts">Rate Limit: Replies (per hour)</label>
                <input type="number" id="setting-rate-limit-posts" name="rate_limit_posts"
                    value="<?= htmlspecialchars($settings['rate_limit_posts'] ?? 20) ?>" min="1">
            </div>
            <div class="community-form-group">
                <label for="setting-rate-limit-topics">Rate Limit: Topics (per hour)</label>
                <input type="number" id="setting-rate-limit-topics" name="rate_limit_topics"
                    value="<?= htmlspecialchars($settings['rate_limit_topics'] ?? 5) ?>" min="1">
            </div>
        </div>
        <div class="community-form-group">
            <label for="setting-new-user-delay">New User Post Delay (minutes after registration)</label>
            <input type="number" id="setting-new-user-delay" name="new_user_post_delay"
                value="<?= htmlspecialchars($settings['new_user_post_delay'] ?? 0) ?>" min="0">
        </div>
    </div>

    <!-- Pagination Section -->
    <div class="community-card">
        <div class="community-section-title">Pagination</div>
        <div class="community-form-row">
            <div class="community-form-group">
                <label for="setting-topics-per-page">Topics Per Page</label>
                <input type="number" id="setting-topics-per-page" name="topics_per_page"
                    value="<?= htmlspecialchars($settings['topics_per_page'] ?? 20) ?>" min="5" max="100">
            </div>
            <div class="community-form-group">
                <label for="setting-posts-per-page">Posts Per Page</label>
                <input type="number" id="setting-posts-per-page" name="posts_per_page"
                    value="<?= htmlspecialchars($settings['posts_per_page'] ?? 20) ?>" min="5" max="100">
            </div>
        </div>
    </div>

    <div style="display:flex;justify-content:flex-end;margin-bottom:24px;">
        <button type="submit" class="community-btn community-btn-primary" style="padding:10px 28px;font-size:15px;">
            Save Settings
        </button>
    </div>
</form>
