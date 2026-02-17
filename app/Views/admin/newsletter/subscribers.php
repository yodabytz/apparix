<div class="page-header">
    <h1>Newsletter Subscribers</h1>
    <div class="header-actions">
        <a href="/admin/newsletter/export" class="btn btn-outline">Export CSV</a>
        <a href="/admin/newsletter" class="btn btn-outline">Back to Newsletter</a>
    </div>
</div>

<div class="stats-row">
    <div class="stat-item">
        <span class="stat-number"><?php echo number_format($activeCount); ?></span>
        <span class="stat-text">Active</span>
    </div>
    <div class="stat-item">
        <span class="stat-number"><?php echo number_format($totalCount - $activeCount); ?></span>
        <span class="stat-text">Unsubscribed</span>
    </div>
    <div class="stat-item">
        <span class="stat-number"><?php echo number_format($totalCount); ?></span>
        <span class="stat-text">Total</span>
    </div>
</div>

<div class="admin-card">
    <?php if (empty($subscribers)): ?>
        <p class="empty-state">No subscribers yet.</p>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Source</th>
                    <th>Subscribed</th>
                    <th>Unsubscribed</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subscribers as $sub): ?>
                    <tr>
                        <td><?php echo escape($sub['email']); ?></td>
                        <td><?php echo escape($sub['first_name'] ?? '-'); ?></td>
                        <td>
                            <?php if ($sub['is_subscribed']): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-muted">Unsubscribed</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo escape(ucfirst($sub['source'])); ?></td>
                        <td><?php echo date('M j, Y', strtotime($sub['subscribed_at'])); ?></td>
                        <td>
                            <?php if ($sub['unsubscribed_at']): ?>
                                <?php echo date('M j, Y', strtotime($sub['unsubscribed_at'])); ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" action="/admin/newsletter/subscribers/delete" style="display: inline;" onsubmit="return confirm('Delete this subscriber?');">
                                <input type="hidden" name="_csrf_token" value="<?php echo csrfToken(); ?>">
                                <input type="hidden" name="id" value="<?php echo $sub['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger" title="Delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>" class="btn btn-sm btn-outline">&laquo; Previous</a>
                <?php endif; ?>

                <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="btn btn-sm btn-outline">Next &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.header-actions {
    display: flex;
    gap: 0.5rem;
}

.stats-row {
    display: flex;
    gap: 2rem;
    margin-bottom: 1.5rem;
}

.stat-item {
    display: flex;
    align-items: baseline;
    gap: 0.5rem;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: #FF68C5;
}

.stat-text {
    color: #6b7280;
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

.badge-success {
    background: #d1fae5;
    color: #065f46;
}

.badge-muted {
    background: #f3f4f6;
    color: #6b7280;
}

.empty-state {
    text-align: center;
    color: #6b7280;
    padding: 2rem;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
}

.page-info {
    color: #6b7280;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.btn-danger {
    background: #dc2626;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.btn-danger:hover {
    background: #b91c1c;
}
</style>
