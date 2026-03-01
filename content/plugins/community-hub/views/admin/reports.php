<div class="community-card" style="margin-bottom:0;">
    <h2>Reported Posts</h2>
</div>

<div class="community-card">
    <?php if (!empty($reports)): ?>
        <table class="community-table">
            <thead>
                <tr>
                    <th>Reporter</th>
                    <th>Post Type</th>
                    <th>Reason</th>
                    <th>Details</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $report): ?>
                    <tr>
                        <td><?= htmlspecialchars(trim(($report['first_name'] ?? '') . ' ' . ($report['last_name'] ?? '')) ?: 'Unknown') ?></td>
                        <td>
                            <?php if (($report['post_type'] ?? '') === 'topic'): ?>
                                <span class="community-badge community-badge-info">Topic</span>
                                <br><a href="/admin/community/topic/<?= (int)$report['post_id'] ?>" style="font-size:12px;">
                                    View Topic
                                </a>
                            <?php else: ?>
                                <span class="community-badge community-badge-warning">Reply</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= htmlspecialchars($report['reason'] ?? '—') ?></strong></td>
                        <td>
                            <?php if (!empty($report['details'])): ?>
                                <div class="community-post-preview">
                                    <?= htmlspecialchars($report['details']) ?>
                                </div>
                            <?php else: ?>
                                <span style="color:#9ca3af;">No details provided</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('M j, Y g:ia', strtotime($report['created_at'])) ?></td>
                        <td>
                            <div class="community-actions">
                                <form method="POST" action="/admin/community/reports/review">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= (int)$report['id'] ?>">
                                    <input type="hidden" name="status" value="reviewed">
                                    <button type="submit" class="community-btn community-btn-success community-btn-sm">Reviewed</button>
                                </form>
                                <form method="POST" action="/admin/community/reports/review">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= (int)$report['id'] ?>">
                                    <input type="hidden" name="status" value="dismissed">
                                    <button type="submit" class="community-btn community-btn-outline community-btn-sm">Dismiss</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="community-empty">No reports to review. Everything looks good!</div>
    <?php endif; ?>
</div>
