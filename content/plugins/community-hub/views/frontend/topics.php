<?php if (!function_exists('forum_time_ago')) { function forum_time_ago($d) { $t=strtotime($d); $s=time()-$t; if($s<60)return'just now';if($s<3600)return floor($s/60).'m ago';if($s<86400)return floor($s/3600).'h ago';if($s<604800)return floor($s/86400).'d ago';return date('M j',$t); } } ?>

<div class="forum-container">
    <div class="forum-breadcrumb">
        <a href="/community">Community</a>
        <span class="breadcrumb-sep">&rsaquo;</span>
        <span><?php echo escape($category['name']); ?></span>
    </div>

    <div class="forum-header">
        <div class="forum-header-content">
            <h1><?php echo escape($category['name']); ?></h1>
            <?php if (!empty($category['description'])): ?>
                <p class="forum-subtitle"><?php echo escape($category['description']); ?></p>
            <?php endif; ?>
        </div>
        <div class="forum-header-actions">
            <?php if (!empty($category['admin_only'])): ?>
                <span class="topic-badge badge-admin-only" style="padding:6px 14px;font-size:13px;">Admin Only</span>
                <?php if (!empty($isAdmin)): ?>
                    <a href="/community/new-topic?category=<?php echo (int)$category['id']; ?>" class="forum-btn forum-btn-primary">New Topic</a>
                <?php endif; ?>
            <?php elseif (auth()): ?>
                <a href="/community/new-topic?category=<?php echo (int)$category['id']; ?>" class="forum-btn forum-btn-primary">New Topic</a>
            <?php else: ?>
                <a href="/login?redirect=<?php echo urlencode('/community/category/' . $category['slug']); ?>" class="forum-btn forum-btn-primary">Log in to Post</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($topics)): ?>
        <div class="topic-list">
            <?php foreach ($topics as $topic): ?>
                <div class="topic-row<?php echo $topic['is_pinned'] ? ' topic-pinned' : ''; ?><?php echo $topic['is_locked'] ? ' topic-locked' : ''; ?>">
                    <div class="topic-main">
                        <div class="topic-badges">
                            <?php if ($topic['is_pinned']): ?>
                                <span class="topic-badge badge-pinned" title="Pinned">Pinned</span>
                            <?php endif; ?>
                            <?php if ($topic['is_locked']): ?>
                                <span class="topic-badge badge-locked" title="Locked">Locked</span>
                            <?php endif; ?>
                        </div>
                        <a href="/community/topic/<?php echo escape($topic['slug']); ?>" class="topic-title">
                            <?php echo escape($topic['title']); ?>
                        </a>
                        <div class="topic-meta">
                            <span class="topic-author">by <?php echo escape(trim(($topic['first_name'] ?? '') . ' ' . ($topic['last_name'] ?? '')) ?: 'Anonymous'); ?></span>
                            <span class="topic-date"><?php echo forum_time_ago($topic['created_at']); ?></span>
                        </div>
                    </div>
                    <div class="topic-stats">
                        <div class="topic-stat">
                            <span class="stat-count"><?php echo (int)$topic['reply_count']; ?></span>
                            <span class="stat-label">replies</span>
                        </div>
                        <div class="topic-stat">
                            <span class="stat-count"><?php echo (int)$topic['view_count']; ?></span>
                            <span class="stat-label">views</span>
                        </div>
                    </div>
                    <div class="topic-last-reply">
                        <?php if ($topic['last_reply_at']): ?>
                            <span class="last-reply-time"><?php echo forum_time_ago($topic['last_reply_at']); ?></span>
                            <?php $lastReplyName = trim(($topic['last_reply_first_name'] ?? '') . ' ' . ($topic['last_reply_last_name'] ?? '')); if ($lastReplyName): ?>
                                <span class="last-reply-author">by <?php echo escape($lastReplyName); ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="last-reply-time">No replies</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($pagination) && $pagination['total_pages'] > 1): ?>
            <div class="forum-pagination">
                <?php if ($pagination['current_page'] > 1): ?>
                    <a href="?page=<?php echo $pagination['current_page'] - 1; ?>" class="forum-btn pagination-prev">&laquo; Previous</a>
                <?php endif; ?>

                <?php for ($p = 1; $p <= $pagination['total_pages']; $p++): ?>
                    <?php if ($p == $pagination['current_page']): ?>
                        <span class="pagination-page pagination-current"><?php echo $p; ?></span>
                    <?php elseif ($p <= 2 || $p >= $pagination['total_pages'] - 1 || abs($p - $pagination['current_page']) <= 2): ?>
                        <a href="?page=<?php echo $p; ?>" class="pagination-page"><?php echo $p; ?></a>
                    <?php elseif ($p == 3 || $p == $pagination['total_pages'] - 2): ?>
                        <span class="pagination-ellipsis">&hellip;</span>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                    <a href="?page=<?php echo $pagination['current_page'] + 1; ?>" class="forum-btn pagination-next">Next &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="forum-empty">
            <?php if (!empty($category['admin_only'])): ?>
                <p>No announcements yet. Check back soon!</p>
            <?php else: ?>
                <p>No topics in this category yet. Be the first to start a conversation!</p>
                <?php if (auth()): ?>
                    <a href="/community/new-topic?category=<?php echo (int)$category['id']; ?>" class="forum-btn forum-btn-primary">Create First Topic</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
