<div class="page-header">
    <h1>Newsletter</h1>
    <a href="/admin/newsletter/compose" class="btn btn-primary">Compose Newsletter</a>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value"><?php echo number_format($activeCount); ?></div>
        <div class="stat-label">Active Subscribers</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo number_format($totalCount); ?></div>
        <div class="stat-label">Total Subscribers</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo count($newsletters); ?></div>
        <div class="stat-label">Newsletters Sent</div>
    </div>
</div>

<div class="admin-card">
    <div class="card-header">
        <h2>Recent Newsletters</h2>
    </div>

    <?php if (empty($newsletters)): ?>
        <p class="empty-state">No newsletters have been sent yet.</p>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Sent By</th>
                    <th>Recipients</th>
                    <th>Sent At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($newsletters as $newsletter): ?>
                    <tr>
                        <td><?php echo escape($newsletter['subject']); ?></td>
                        <td><?php echo escape($newsletter['sent_by_name'] ?? 'Unknown'); ?></td>
                        <td><?php echo number_format($newsletter['recipient_count']); ?></td>
                        <td><?php echo date('M j, Y g:i A', strtotime($newsletter['sent_at'])); ?></td>
                        <td class="actions-cell">
                            <a href="/admin/newsletter/view/<?php echo $newsletter['id']; ?>" class="btn btn-sm btn-outline">View</a>
                            <form method="POST" action="/admin/newsletter/resend" class="inline-form" onsubmit="return confirm('Resend this newsletter to all subscribers?');">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="id" value="<?php echo $newsletter['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-primary">Resend</button>
                            </form>
                            <form method="POST" action="/admin/newsletter/delete" class="inline-form" onsubmit="return confirm('Delete this newsletter?');">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="id" value="<?php echo $newsletter['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="admin-card">
    <div class="card-header">
        <h2>Recent Subscribers</h2>
        <div class="card-actions">
            <a href="/admin/newsletter/subscribers" class="btn btn-sm btn-outline">View All</a>
            <a href="/admin/newsletter/export" class="btn btn-sm btn-outline">Export CSV</a>
        </div>
    </div>

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
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($subscribers, 0, 10) as $sub): ?>
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
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    text-align: center;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: #FF68C5;
}

.stat-label {
    color: #6b7280;
    font-size: 0.9rem;
    margin-top: 0.5rem;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.card-header h2 {
    margin: 0;
}

.card-actions {
    display: flex;
    gap: 0.5rem;
}

.empty-state {
    text-align: center;
    color: #6b7280;
    padding: 2rem;
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

.actions-cell {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.inline-form {
    display: inline;
    margin: 0;
}

.btn-danger {
    background: #ef4444;
    color: white;
    border: none;
}

.btn-danger:hover {
    background: #dc2626;
}
</style>
