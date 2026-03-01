<div class="community-card" style="margin-bottom:0;">
    <div class="community-topic-header">
        <div>
            <h1><?= htmlspecialchars($topic['title']) ?></h1>
            <div class="community-topic-meta">
                Posted by <strong><?= htmlspecialchars(trim(($topic['first_name'] ?? '') . ' ' . ($topic['last_name'] ?? '')) ?: 'Unknown') ?></strong>
                in <strong><?= htmlspecialchars($topic['category_name'] ?? '—') ?></strong>
                on <?= date('M j, Y \a\t g:ia', strtotime($topic['created_at'])) ?>
                &middot; <?= number_format($topic['view_count'] ?? 0) ?> views
                &middot; <?= number_format($topic['reply_count'] ?? 0) ?> replies
            </div>
            <div style="margin-top:8px;">
                <?php if ($topic['is_pinned'] ?? 0): ?>
                    <span class="community-badge community-badge-info">Pinned</span>
                <?php endif; ?>
                <?php if ($topic['is_locked'] ?? 0): ?>
                    <span class="community-badge community-badge-danger">Locked</span>
                <?php endif; ?>
                <?php if (($topic['status'] ?? '') === 'closed'): ?>
                    <span class="community-badge community-badge-warning">Closed</span>
                <?php endif; ?>
                <?php if (($topic['status'] ?? '') === 'pending'): ?>
                    <span class="community-badge community-badge-warning">Pending</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="community-actions" style="flex-wrap:wrap;">
            <!-- Pin/Unpin -->
            <?php if ($topic['is_pinned'] ?? 0): ?>
                <form method="POST" action="/admin/community/topic/unpin">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int)$topic['id'] ?>">
                    <button type="submit" class="community-btn community-btn-outline community-btn-sm">Unpin</button>
                </form>
            <?php else: ?>
                <form method="POST" action="/admin/community/topic/pin">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int)$topic['id'] ?>">
                    <button type="submit" class="community-btn community-btn-outline community-btn-sm">Pin</button>
                </form>
            <?php endif; ?>

            <!-- Lock/Unlock -->
            <?php if ($topic['is_locked'] ?? 0): ?>
                <form method="POST" action="/admin/community/topic/unlock">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int)$topic['id'] ?>">
                    <button type="submit" class="community-btn community-btn-outline community-btn-sm">Unlock</button>
                </form>
            <?php else: ?>
                <form method="POST" action="/admin/community/topic/lock">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int)$topic['id'] ?>">
                    <button type="submit" class="community-btn community-btn-outline community-btn-sm">Lock</button>
                </form>
            <?php endif; ?>

            <!-- Close -->
            <?php if (($topic['status'] ?? '') !== 'closed'): ?>
                <form method="POST" action="/admin/community/topic/close">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int)$topic['id'] ?>">
                    <button type="submit" class="community-btn community-btn-outline community-btn-sm">Close</button>
                </form>
            <?php endif; ?>

            <!-- Delete -->
            <form method="POST" action="/admin/community/topic/delete"
                onsubmit="return confirm('Permanently delete this topic and all its replies?');">
                <?= csrfField() ?>
                <input type="hidden" name="id" value="<?= (int)$topic['id'] ?>">
                <button type="submit" class="community-btn community-btn-danger community-btn-sm">Delete</button>
            </form>
        </div>
    </div>
</div>

<!-- Topic Content -->
<div class="community-card">
    <h3>Topic Content</h3>
    <div style="line-height:1.7;color:#374151;">
        <?= $topic['body_html'] ?? nl2br(htmlspecialchars($topic['body'] ?? '')) ?>
    </div>
</div>

<!-- Replies -->
<div class="community-card">
    <h3>Replies (<?= count($replies ?? []) ?>)</h3>
    <?php if (!empty($replies)): ?>
        <?php foreach ($replies as $index => $reply): ?>
            <div style="display:flex;justify-content:space-between;align-items:flex-start;padding:16px 0;<?= $index > 0 ? 'border-top:1px solid #f3f4f6;' : '' ?>">
                <div style="flex:1;">
                    <div style="margin-bottom:4px;">
                        <strong><?= htmlspecialchars(trim(($reply['first_name'] ?? '') . ' ' . ($reply['last_name'] ?? '')) ?: 'Unknown') ?></strong>
                        <span style="color:#9ca3af;font-size:13px;margin-left:8px;">
                            <?= date('M j, Y \a\t g:ia', strtotime($reply['created_at'])) ?>
                        </span>
                        <?php if (($reply['status'] ?? '') === 'pending'): ?>
                            <span class="community-badge community-badge-warning" style="margin-left:6px;">Pending</span>
                        <?php endif; ?>
                    </div>
                    <div style="color:#4b5563;line-height:1.6;">
                        <?= $reply['body_html'] ?? nl2br(htmlspecialchars($reply['body'] ?? '')) ?>
                    </div>
                </div>
                <form method="POST" action="/admin/community/reply/delete"
                    onsubmit="return confirm('Delete this reply?');" style="margin-left:12px;flex-shrink:0;">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int)$reply['id'] ?>">
                    <button type="submit" class="community-btn community-btn-danger community-btn-sm">Delete</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="community-empty">No replies yet.</div>
    <?php endif; ?>
</div>

<div style="margin-bottom:24px;">
    <a href="/admin/community" class="community-btn community-btn-outline">&larr; Back to Dashboard</a>
</div>
