<?php if (!function_exists('forum_time_ago')) { function forum_time_ago($d) { $t=strtotime($d); $s=time()-$t; if($s<60)return'just now';if($s<3600)return floor($s/60).'m ago';if($s<86400)return floor($s/3600).'h ago';if($s<604800)return floor($s/86400).'d ago';return date('M j',$t); } } ?>

<div class="forum-container">
    <div class="forum-breadcrumb">
        <a href="/community">Community</a>
        <span class="breadcrumb-sep">&rsaquo;</span>
        <a href="/community/category/<?php echo escape($category['slug']); ?>"><?php echo escape($category['name']); ?></a>
        <span class="breadcrumb-sep">&rsaquo;</span>
        <span><?php echo escape($topic['title']); ?></span>
    </div>

    <div class="forum-header">
        <div class="forum-header-content">
            <h1>
                <?php echo escape($topic['title']); ?>
                <?php if ($topic['is_locked']): ?>
                    <span class="topic-badge badge-locked">Locked</span>
                <?php endif; ?>
                <?php if ($topic['is_closed']): ?>
                    <span class="topic-badge badge-closed">Closed</span>
                <?php endif; ?>
                <?php if (!empty($category['admin_only'])): ?>
                    <span class="topic-badge badge-admin-only">Admin Only</span>
                <?php endif; ?>
            </h1>
        </div>
        <div class="forum-header-actions">
            <?php if (auth()): ?>
                <?php if (!empty($isSubscribed)): ?>
                    <form method="post" action="/community/unsubscribe" style="display:inline;">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="topic_id" value="<?php echo (int)$topic['id']; ?>">
                        <button type="submit" class="forum-btn">Unsubscribe</button>
                    </form>
                <?php else: ?>
                    <form method="post" action="/community/subscribe" style="display:inline;">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="topic_id" value="<?php echo (int)$topic['id']; ?>">
                        <button type="submit" class="forum-btn">Subscribe</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Original Topic Post -->
    <div class="post-card post-original" id="post-<?php echo (int)$topic['id']; ?>">
        <div class="post-sidebar">
            <div class="post-avatar">
                <?php $topicAuthor = trim(($topic['first_name'] ?? '') . ' ' . ($topic['last_name'] ?? '')) ?: 'Anonymous'; ?>
                <span class="avatar-letter"><?php echo strtoupper(substr($topicAuthor, 0, 1)); ?></span>
            </div>
            <div class="post-author-info">
                <a href="/community/user/<?php echo (int)$topic['user_id']; ?>" class="post-author-name"><?php echo escape($topicAuthor); ?></a>
                <span class="post-author-joined">Joined <?php echo date('M Y', strtotime($topic['user_joined'] ?? $topic['created_at'])); ?></span>
            </div>
        </div>
        <div class="post-content">
            <div class="post-meta">
                <span class="post-date"><?php echo forum_time_ago($topic['created_at']); ?></span>
                <?php if (auth() && ((int)auth()['id'] === (int)$topic['user_id'] || !empty($isAdmin))): ?>
                    <a href="/community/edit/topic/<?php echo (int)$topic['id']; ?>" class="post-action">Edit</a>
                <?php endif; ?>
                <?php if (auth()): ?>
                    <button type="button" class="post-action" onclick="document.getElementById('reportModal').style.display='flex'; document.getElementById('reportPostId').value='<?php echo (int)$topic['id']; ?>'; document.getElementById('reportPostType').value='topic';">Report</button>
                <?php endif; ?>
            </div>
            <div class="post-body"><?php echo $topic['body_html']; ?></div>
            <?php if (!empty($topic['edited_at'])): ?>
                <div class="post-edited">
                    Edited <?php echo forum_time_ago($topic['edited_at']); ?>
                    <?php if (!empty($topic['edit_count']) && $topic['edit_count'] > 0): ?>
                        &middot; <a href="/community/edit-history/topic/<?php echo (int)$topic['id']; ?>" class="post-edit-history"><?php echo (int)$topic['edit_count']; ?> edit<?php echo $topic['edit_count'] > 1 ? 's' : ''; ?></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Replies -->
    <?php if (!empty($replies)): ?>
        <div class="reply-list">
            <?php foreach ($replies as $index => $reply): ?>
                <div class="post-card post-reply" id="post-<?php echo (int)$reply['id']; ?>">
                    <div class="post-sidebar">
                        <div class="post-avatar">
                            <?php $replyAuthor = trim(($reply['first_name'] ?? '') . ' ' . ($reply['last_name'] ?? '')) ?: 'Anonymous'; ?>
                            <span class="avatar-letter"><?php echo strtoupper(substr($replyAuthor, 0, 1)); ?></span>
                        </div>
                        <div class="post-author-info">
                            <a href="/community/user/<?php echo (int)$reply['user_id']; ?>" class="post-author-name"><?php echo escape($replyAuthor); ?></a>
                            <span class="post-author-joined">Joined <?php echo date('M Y', strtotime($reply['user_joined'] ?? $reply['created_at'])); ?></span>
                        </div>
                    </div>
                    <div class="post-content">
                        <div class="post-meta">
                            <span class="post-date"><?php echo forum_time_ago($reply['created_at']); ?></span>
                            <a href="#post-<?php echo (int)$reply['id']; ?>" class="post-action post-permalink">#<?php echo $index + 1 + (isset($pagination) ? ($pagination['current_page'] - 1) * $pagination['per_page'] : 0); ?></a>
                            <?php if (auth() && ((int)auth()['id'] === (int)$reply['user_id'] || !empty($isAdmin))): ?>
                                <a href="/community/edit/reply/<?php echo (int)$reply['id']; ?>" class="post-action">Edit</a>
                            <?php endif; ?>
                            <?php if (auth()): ?>
                                <button type="button" class="post-action" onclick="document.getElementById('reportModal').style.display='flex'; document.getElementById('reportPostId').value='<?php echo (int)$reply['id']; ?>'; document.getElementById('reportPostType').value='reply';">Report</button>
                            <?php endif; ?>
                        </div>
                        <div class="post-body"><?php echo $reply['body_html']; ?></div>
                        <?php if (!empty($reply['edited_at'])): ?>
                            <div class="post-edited">
                                Edited <?php echo forum_time_ago($reply['edited_at']); ?>
                                <?php if (!empty($reply['edit_count']) && $reply['edit_count'] > 0): ?>
                                    &middot; <a href="/community/edit-history/reply/<?php echo (int)$reply['id']; ?>" class="post-edit-history"><?php echo (int)$reply['edit_count']; ?> edit<?php echo $reply['edit_count'] > 1 ? 's' : ''; ?></a>
                                <?php endif; ?>
                            </div>
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
    <?php endif; ?>

    <!-- Reply Form -->
    <?php
        $canReply = false;
        if (auth() && !$topic['is_locked'] && !$topic['is_closed']) {
            if (!empty($category['admin_only'])) {
                $canReply = !empty($isAdmin);
            } else {
                $canReply = true;
            }
        }
    ?>
    <?php if ($canReply): ?>
        <div class="reply-form-container">
            <h3>Post a Reply</h3>
            <form method="post" action="/community/reply/create" class="reply-form">
                <?php echo csrfField(); ?>
                <input type="hidden" name="topic_id" value="<?php echo (int)$topic['id']; ?>">
                <input type="hidden" name="_form_token" value="<?php echo escape($formToken ?? ''); ?>">
                <div style="display:none;">
                    <input type="text" name="website_url" value="" tabindex="-1" autocomplete="off">
                </div>

                <div class="markdown-toolbar">
                    <button type="button" class="toolbar-btn" onclick="insertMd('**', '**')" title="Bold"><b>B</b></button>
                    <button type="button" class="toolbar-btn" onclick="insertMd('*', '*')" title="Italic"><i>I</i></button>
                    <button type="button" class="toolbar-btn" onclick="insertMd('[', '](url)')" title="Link">Link</button>
                    <button type="button" class="toolbar-btn" onclick="insertMd('`', '`')" title="Code">Code</button>
                    <button type="button" class="toolbar-btn" onclick="insertMd('\n- ', '')" title="List">List</button>
                </div>

                <textarea name="body" id="replyBody" class="forum-textarea" rows="6" placeholder="Write your reply... Markdown is supported." required></textarea>

                <div class="reply-form-actions">
                    <button type="submit" class="forum-btn forum-btn-primary">Post Reply</button>
                </div>
            </form>
        </div>
    <?php elseif (!auth()): ?>
        <div class="reply-form-container reply-form-guest">
            <p><a href="/login?redirect=<?php echo urlencode('/community/topic/' . $topic['slug']); ?>">Log in</a> to post a reply.</p>
        </div>
    <?php elseif ($topic['is_locked'] || $topic['is_closed']): ?>
        <div class="reply-form-container reply-form-closed">
            <p>This topic is <?php echo $topic['is_locked'] ? 'locked' : 'closed'; ?>. No new replies can be posted.</p>
        </div>
    <?php elseif (!empty($category['admin_only'])): ?>
        <div class="reply-form-container reply-form-closed">
            <p>Only administrators can post in this category.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Report Modal -->
<div id="reportModal" class="forum-modal" style="display:none;">
    <div class="forum-modal-backdrop" onclick="document.getElementById('reportModal').style.display='none';"></div>
    <div class="forum-modal-content">
        <div class="forum-modal-header">
            <h3>Report Post</h3>
            <button type="button" class="forum-modal-close" onclick="document.getElementById('reportModal').style.display='none';">&times;</button>
        </div>
        <form method="post" action="/community/report">
            <?php echo csrfField(); ?>
            <input type="hidden" name="post_id" id="reportPostId" value="">
            <input type="hidden" name="post_type" id="reportPostType" value="">
            <div class="forum-form-group">
                <label for="reportReason">Reason for reporting</label>
                <select name="reason" id="reportReason" class="forum-select" required>
                    <option value="">Select a reason</option>
                    <option value="spam">Spam</option>
                    <option value="harassment">Harassment</option>
                    <option value="inappropriate">Inappropriate content</option>
                    <option value="off_topic">Off-topic</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="forum-form-group">
                <label for="reportDetails">Additional details (optional)</label>
                <textarea name="details" id="reportDetails" class="forum-textarea" rows="3" placeholder="Provide any additional context..."></textarea>
            </div>
            <div class="forum-modal-actions">
                <button type="button" class="forum-btn" onclick="document.getElementById('reportModal').style.display='none';">Cancel</button>
                <button type="submit" class="forum-btn forum-btn-primary">Submit Report</button>
            </div>
        </form>
    </div>
</div>

<script src="/plugins/community-hub/assets/forum.js"></script>
