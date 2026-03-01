<div class="community-card" style="margin-bottom:0;">
    <h2>Moderation Queue</h2>
</div>

<!-- Pending Topics -->
<div class="community-card">
    <h3>Pending Topics
        <?php if (!empty($pendingTopics)): ?>
            <span class="community-badge community-badge-warning" style="margin-left:8px;"><?= count($pendingTopics) ?></span>
        <?php endif; ?>
    </h3>
    <?php if (!empty($pendingTopics)): ?>
        <table class="community-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Category</th>
                    <th>Date</th>
                    <th>Preview</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendingTopics as $topic): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($topic['title']) ?></strong></td>
                        <td><?= htmlspecialchars(trim(($topic['first_name'] ?? '') . ' ' . ($topic['last_name'] ?? '')) ?: 'Unknown') ?></td>
                        <td><?= htmlspecialchars($topic['category_name'] ?? '—') ?></td>
                        <td><?= date('M j, Y g:ia', strtotime($topic['created_at'])) ?></td>
                        <td>
                            <div class="community-post-preview">
                                <?= htmlspecialchars(mb_substr(strip_tags($topic['body'] ?? ''), 0, 150)) ?>...
                            </div>
                        </td>
                        <td>
                            <div class="community-actions">
                                <form method="POST" action="/admin/community/moderation/approve">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="type" value="topic">
                                    <input type="hidden" name="id" value="<?= $topic['id'] ?>">
                                    <button type="submit" class="community-btn community-btn-success community-btn-sm">Approve</button>
                                </form>
                                <form method="POST" action="/admin/community/moderation/reject">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="type" value="topic">
                                    <input type="hidden" name="id" value="<?= $topic['id'] ?>">
                                    <button type="submit" class="community-btn community-btn-danger community-btn-sm">Reject</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="community-empty">No pending topics. All clear!</div>
    <?php endif; ?>
</div>

<!-- Pending Replies -->
<div class="community-card">
    <h3>Pending Replies
        <?php if (!empty($pendingReplies)): ?>
            <span class="community-badge community-badge-warning" style="margin-left:8px;"><?= count($pendingReplies) ?></span>
        <?php endif; ?>
    </h3>
    <?php if (!empty($pendingReplies)): ?>
        <table class="community-table">
            <thead>
                <tr>
                    <th>Topic</th>
                    <th>Author</th>
                    <th>Date</th>
                    <th>Preview</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendingReplies as $reply): ?>
                    <tr>
                        <td>
                            <a href="/admin/community/topic/<?= $reply['topic_id'] ?>">
                                <?= htmlspecialchars($reply['topic_title'] ?? 'Topic #' . $reply['topic_id']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars(trim(($reply['first_name'] ?? '') . ' ' . ($reply['last_name'] ?? '')) ?: 'Unknown') ?></td>
                        <td><?= date('M j, Y g:ia', strtotime($reply['created_at'])) ?></td>
                        <td>
                            <div class="community-post-preview">
                                <?= htmlspecialchars(mb_substr(strip_tags($reply['body'] ?? ''), 0, 150)) ?>...
                            </div>
                        </td>
                        <td>
                            <div class="community-actions">
                                <form method="POST" action="/admin/community/moderation/approve">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="type" value="reply">
                                    <input type="hidden" name="id" value="<?= $reply['id'] ?>">
                                    <button type="submit" class="community-btn community-btn-success community-btn-sm">Approve</button>
                                </form>
                                <form method="POST" action="/admin/community/moderation/reject">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="type" value="reply">
                                    <input type="hidden" name="id" value="<?= $reply['id'] ?>">
                                    <button type="submit" class="community-btn community-btn-danger community-btn-sm">Reject</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="community-empty">No pending replies. All clear!</div>
    <?php endif; ?>
</div>
