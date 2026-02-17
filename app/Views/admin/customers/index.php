<div class="page-header">
    <h1>Customers</h1>
    <div class="header-stats">
        <span class="stat"><?php echo number_format($totalUsers); ?> total customers</span>
    </div>
</div>

<div class="admin-card">
    <form method="GET" action="/admin/customers" class="search-form">
        <input type="text" name="search" placeholder="Search by email, name..."
               value="<?php echo escape($search); ?>" class="search-input">
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if ($search): ?>
            <a href="/admin/customers" class="btn btn-outline">Clear</a>
        <?php endif; ?>
    </form>

    <?php if (empty($users)): ?>
        <p class="empty-state">No customers found.</p>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Orders</th>
                    <th>Total Spent</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <strong><?php echo escape(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'No name'); ?></strong>
                            <?php if ($user['newsletter_subscribed']): ?>
                                <span class="badge badge-info">Newsletter</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo escape($user['email']); ?></td>
                        <td><?php echo $user['phone'] ? escape($user['phone']) : '<span class="text-muted">-</span>'; ?></td>
                        <td>
                            <?php if ($user['order_count'] > 0): ?>
                                <span class="badge badge-primary"><?php echo $user['order_count']; ?></span>
                            <?php else: ?>
                                <span class="text-muted">0</span>
                            <?php endif; ?>
                        </td>
                        <td>$<?php echo number_format($user['total_spent'], 2); ?></td>
                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                        <td class="actions-cell">
                            <form method="POST" action="/admin/customers/delete" style="display: inline;"
                                  onsubmit="return confirm('Are you sure you want to delete this customer?\n\nThis will remove their account, favorites, and addresses.\nTheir order history will be preserved but unlinked.');">
                                <input type="hidden" name="_csrf_token" value="<?php echo csrfToken(); ?>">
                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="/admin/customers?page=<?php echo $currentPage - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-sm btn-outline">&laquo; Prev</a>
                <?php endif; ?>

                <span class="page-info">Page <?php echo $currentPage; ?> of <?php echo $totalPages; ?></span>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="/admin/customers?page=<?php echo $currentPage + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-sm btn-outline">Next &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.header-stats .stat {
    background: #f3f4f6;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.875rem;
    color: #6b7280;
}

.admin-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.search-form {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

.search-input {
    flex: 1;
    max-width: 400px;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 1rem;
}

.search-input:focus {
    outline: none;
    border-color: #FF68C5;
    box-shadow: 0 0 0 3px rgba(255, 104, 197, 0.1);
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
}

.admin-table th,
.admin-table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

.admin-table th {
    font-weight: 600;
    color: #374151;
    background: #f9fafb;
}

.admin-table tbody tr:hover {
    background: #f9fafb;
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

.badge-primary {
    background: #FF68C5;
    color: white;
}

.badge-info {
    background: #dbeafe;
    color: #1e40af;
    margin-left: 0.5rem;
}

.text-muted {
    color: #9ca3af;
}

.actions-cell {
    white-space: nowrap;
}

.btn {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
}

.btn-primary {
    background: #FF68C5;
    color: white;
}

.btn-primary:hover {
    background: #e85eb3;
}

.btn-outline {
    background: white;
    border: 1px solid #d1d5db;
    color: #374151;
}

.btn-outline:hover {
    background: #f9fafb;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.btn-danger {
    background: #dc2626;
    color: white;
}

.btn-danger:hover {
    background: #b91c1c;
}

.empty-state {
    text-align: center;
    color: #6b7280;
    padding: 2rem;
}

.pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
}

.page-info {
    color: #6b7280;
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .search-form {
        flex-wrap: wrap;
    }

    .search-input {
        max-width: 100%;
    }

    .admin-table {
        display: block;
        overflow-x: auto;
    }
}
</style>
