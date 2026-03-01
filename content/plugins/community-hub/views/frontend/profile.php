<div class="forum-container">
    <div class="forum-breadcrumb">
        <a href="/community">Community</a>
        <span class="breadcrumb-sep">&rsaquo;</span>
        <span><?php echo escape($profile['display_name']); ?></span>
    </div>

    <div class="profile-header">
        <div class="profile-avatar">
            <span class="avatar-letter avatar-letter-large"><?php echo strtoupper(substr($profile['first_name'], 0, 1)); ?></span>
        </div>
        <div class="profile-info">
            <h1 class="profile-name"><?php echo escape($profile['first_name'] . ' ' . $profile['last_name']); ?></h1>
            <div class="profile-stats">
                <span class="profile-stat">
                    <strong><?php echo (int)$profile['topic_count']; ?></strong> topics
                </span>
                <span class="profile-stat">
                    <strong><?php echo (int)$profile['reply_count']; ?></strong> replies
                </span>
                <span class="profile-stat">
                    Member since <strong><?php echo date('F Y', strtotime($profile['created_at'])); ?></strong>
                </span>
            </div>
        </div>
    </div>

    <?php if (!empty($recentTopics)): ?>
        <div class="profile-section">
            <h2>Recent Topics</h2>
            <div class="topic-list">
                <?php foreach ($recentTopics as $topic): ?>
                    <div class="topic-row">
                        <div class="topic-main">
                            <a href="/community/topic/<?php echo escape($topic['slug']); ?>" class="topic-title">
                                <?php echo escape($topic['title']); ?>
                            </a>
                            <div class="topic-meta">
                                <span class="topic-category"><?php echo escape($topic['category_name']); ?></span>
                                <span class="topic-date"><?php echo date('M j, Y', strtotime($topic['created_at'])); ?></span>
                            </div>
                        </div>
                        <div class="topic-stats">
                            <div class="topic-stat">
                                <span class="stat-count"><?php echo (int)$topic['reply_count']; ?></span>
                                <span class="stat-label">replies</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($recentReplies)): ?>
        <div class="profile-section">
            <h2>Recent Replies</h2>
            <div class="topic-list">
                <?php foreach ($recentReplies as $reply): ?>
                    <div class="topic-row">
                        <div class="topic-main">
                            <a href="/community/topic/<?php echo escape($reply['topic_slug']); ?>#post-<?php echo (int)$reply['id']; ?>" class="topic-title">
                                Re: <?php echo escape($reply['topic_title']); ?>
                            </a>
                            <div class="topic-meta">
                                <span class="topic-date"><?php echo date('M j, Y', strtotime($reply['created_at'])); ?></span>
                            </div>
                            <div class="reply-preview">
                                <?php echo escape(mb_substr(strip_tags($reply['body']), 0, 150)); ?><?php echo mb_strlen(strip_tags($reply['body'])) > 150 ? '...' : ''; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($recentTopics) && empty($recentReplies)): ?>
        <div class="forum-empty">
            <p>This user hasn't posted anything yet.</p>
        </div>
    <?php endif; ?>
</div>
