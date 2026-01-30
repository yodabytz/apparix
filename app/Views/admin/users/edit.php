<div class="page-header">
    <h1>Edit Admin User</h1>
    <div class="header-actions">
        <a href="/admin/users" class="btn btn-outline">Back to Users</a>
    </div>
</div>

<?php if ($flash = getFlash('error')): ?>
    <div class="alert alert-error"><?php echo $flash; ?></div>
<?php endif; ?>

<div class="admin-card">
    <form method="POST" action="/admin/users/update" class="admin-form">
        <input type="hidden" name="_csrf_token" value="<?php echo csrfToken(); ?>">
        <input type="hidden" name="id" value="<?php echo $adminUser['id']; ?>">

        <div class="form-group">
            <label for="name">Name *</label>
            <input type="text" id="name" name="name" required maxlength="100"
                   value="<?php echo escape($adminUser['name']); ?>"
                   placeholder="Full name" autocomplete="name">
        </div>

        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" required maxlength="255"
                   value="<?php echo escape($adminUser['email']); ?>"
                   placeholder="admin@example.com" autocomplete="email">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" minlength="8"
                       placeholder="Leave blank to keep current" autocomplete="new-password">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" minlength="8"
                       placeholder="Repeat new password" autocomplete="new-password">
            </div>
        </div>
        <small class="form-help password-help">Only fill in if you want to change the password. Minimum 8 characters.</small>

        <div class="form-group">
            <label for="role">Role *</label>
            <select id="role" name="role" required <?php echo ($adminUser['id'] === $admin['admin_id']) ? 'disabled' : ''; ?>>
                <option value="admin" <?php echo $adminUser['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                <option value="super_admin" <?php echo $adminUser['role'] === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
            </select>
            <?php if ($adminUser['id'] === $admin['admin_id']): ?>
                <input type="hidden" name="role" value="<?php echo escape($adminUser['role']); ?>">
                <small class="form-help">You cannot change your own role.</small>
            <?php else: ?>
                <small class="form-help">Super Admins can manage other admin users.</small>
            <?php endif; ?>
        </div>

        <div class="user-info">
            <p><strong>Created:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($adminUser['created_at'])); ?></p>
            <p><strong>Last Login:</strong>
                <?php if ($adminUser['last_login']): ?>
                    <?php echo date('F j, Y \a\t g:i A', strtotime($adminUser['last_login'])); ?>
                <?php else: ?>
                    Never
                <?php endif; ?>
            </p>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Admin User</button>
            <a href="/admin/users" class="btn btn-outline">Cancel</a>
        </div>
    </form>
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
    max-width: 600px;
}

.admin-form .form-group {
    margin-bottom: 1.25rem;
}

.admin-form label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #374151;
}

.admin-form input,
.admin-form select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 1rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.admin-form input:focus,
.admin-form select:focus {
    outline: none;
    border-color: #FF68C5;
    box-shadow: 0 0 0 3px rgba(255, 104, 197, 0.1);
}

.admin-form select:disabled {
    background: #f3f4f6;
    cursor: not-allowed;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-help {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.85rem;
    color: #6b7280;
}

.password-help {
    margin-bottom: 1.25rem;
}

.user-info {
    background: #f9fafb;
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1.25rem;
}

.user-info p {
    margin: 0.25rem 0;
    font-size: 0.9rem;
    color: #4b5563;
}

.form-actions {
    display: flex;
    gap: 0.75rem;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
}

.btn {
    display: inline-block;
    padding: 0.75rem 1.5rem;
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

.alert {
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

@media (max-width: 640px) {
    .form-row {
        grid-template-columns: 1fr;
    }

    .form-actions {
        flex-direction: column;
    }

    .form-actions .btn {
        text-align: center;
    }
}
</style>
