<?php if (!function_exists('forum_time_ago')) { function forum_time_ago($d) { $t=strtotime($d); $s=time()-$t; if($s<60)return'just now';if($s<3600)return floor($s/60).'m ago';if($s<86400)return floor($s/3600).'h ago';if($s<604800)return floor($s/86400).'d ago';return date('M j',$t); } } ?>

<div class="forum-container">
    <div class="forum-breadcrumb">
        <a href="/community">Community</a>
        <span class="breadcrumb-sep">&rsaquo;</span>
        <?php if (!empty($topic)): ?>
            <a href="/community/topic/<?php echo escape($topic['slug']); ?>"><?php echo escape($topic['title']); ?></a>
            <span class="breadcrumb-sep">&rsaquo;</span>
        <?php endif; ?>
        <span>Edit History</span>
    </div>

    <div class="forum-header">
        <h1>Edit History</h1>
        <p class="forum-subtitle">
            <?php echo (int)count($edits); ?> edit<?php echo count($edits) !== 1 ? 's' : ''; ?> for this <?php echo escape($postType); ?>
        </p>
    </div>

    <?php if (!empty($edits)): ?>
        <div class="edit-history-list">
            <?php foreach ($edits as $index => $edit): ?>
                <div class="edit-history-item">
                    <div class="edit-history-header">
                        <div class="edit-history-meta">
                            <strong><?php echo escape(trim(($edit['first_name'] ?? '') . ' ' . ($edit['last_name'] ?? '')) ?: 'Unknown'); ?></strong>
                            edited <?php echo forum_time_ago($edit['created_at']); ?>
                            <span class="edit-history-date">(<?php echo date('M j, Y g:i A', strtotime($edit['created_at'])); ?>)</span>
                        </div>
                        <?php if (!empty($edit['edit_reason'])): ?>
                            <div class="edit-history-reason">
                                Reason: <?php echo escape($edit['edit_reason']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="edit-history-content">
                        <div class="edit-history-label">Previous content:</div>
                        <div class="edit-history-body">
                            <pre class="edit-history-pre"><?php echo escape($edit['old_body']); ?></pre>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="forum-empty">
            <p>No edit history available for this post.</p>
        </div>
    <?php endif; ?>

    <div class="forum-form-actions">
        <?php if (!empty($topic)): ?>
            <a href="/community/topic/<?php echo escape($topic['slug']); ?>" class="forum-btn">&larr; Back to Topic</a>
        <?php else: ?>
            <a href="/community" class="forum-btn">&larr; Back to Community</a>
        <?php endif; ?>
    </div>
</div>
