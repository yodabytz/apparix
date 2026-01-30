<div class="page-header">
    <h1>Admin Users</h1>
    <div class="header-actions">
        <a href="/admin/users/create" class="btn btn-primary">Add Admin User</a>
    </div>
</div>

<?php if ($flash = getFlash('success')): ?>
    <div class="alert alert-success"><?php echo $flash; ?></div>
<?php endif; ?>

<?php if ($flash = getFlash('error')): ?>
    <div class="alert alert-error"><?php echo $flash; ?></div>
<?php endif; ?>

<div class="admin-card">
    <?php if (empty($admins)): ?>
        <p class="empty-state">No admin users found.</p>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Last Login</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $adminUser): ?>
                    <tr>
                        <td>
                            <strong><?php echo escape($adminUser['name']); ?></strong>
                            <?php if ($adminUser['id'] === $admin['admin_id']): ?>
                                <span class="badge badge-info">You</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo escape($adminUser['email']); ?></td>
                        <td>
                            <?php if ($adminUser['role'] === 'super_admin'): ?>
                                <span class="badge badge-primary">Super Admin</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Admin</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($adminUser['last_login']): ?>
                                <?php echo date('M j, Y g:i A', strtotime($adminUser['last_login'])); ?>
                            <?php else: ?>
                                <span class="text-muted">Never</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($adminUser['created_at'])); ?></td>
                        <td class="actions-cell">
                            <a href="/admin/users/edit?id=<?php echo $adminUser['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                            <?php if ($adminUser['id'] !== $admin['admin_id']): ?>
                                <form method="POST" action="/admin/users/delete" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this admin user? This action cannot be undone.');">
                                    <input type="hidden" name="_csrf_token" value="<?php echo csrfToken(); ?>">
                                    <input type="hidden" name="id" value="<?php echo $adminUser['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.header-actions {
    display: flex;
    gap: 0.5rem;
}

.admin-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
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

.badge-secondary {
    background: #e5e7eb;
    color: #374151;
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

.alert {
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.empty-state {
    text-align: center;
    color: #6b7280;
    padding: 2rem;
}
</style>
