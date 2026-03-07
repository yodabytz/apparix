<div class="page-header">
    <h1>Pages</h1>
    <a href="/admin/pages/create" class="btn btn-primary">Add New Page</a>
</div>

<?php if ($flash = getFlash('success')): ?>
    <div class="alert alert-success"><?php echo escape($flash); ?></div>
<?php endif; ?>
<?php if ($flash = getFlash('error')): ?>
    <div class="alert alert-error"><?php echo escape($flash); ?></div>
<?php endif; ?>

<div class="admin-card">
    <?php if (empty($pages)): ?>
        <div style="text-align: center; padding: 3rem;">
            <p style="color: #6b7280; font-size: 1.1rem;">No pages yet. Create your first page to get started.</p>
            <a href="/admin/pages/create" class="btn btn-primary" style="margin-top: 1rem;">Create Page</a>
        </div>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>URL Slug</th>
                    <th>Footer</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pages as $page): ?>
                <tr>
                    <td>
                        <strong><?php echo escape($page['title']); ?></strong>
                    </td>
                    <td>
                        <a href="/pages/<?php echo escape($page['slug']); ?>" target="_blank" style="color: #6b7280; font-size: 0.9rem;">
                            /pages/<?php echo escape($page['slug']); ?>
                        </a>
                    </td>
                    <td>
                        <?php if ($page['show_in_footer']): ?>
                            <span class="badge badge-info">Footer</span>
                        <?php else: ?>
                            <span style="color: #9ca3af;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($page['is_active']): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Draft</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="/admin/pages/<?php echo $page['id']; ?>/edit" class="btn btn-sm btn-outline">Edit</a>
                            <button type="button" class="btn btn-sm btn-danger" onclick="deletePage(<?php echo $page['id']; ?>, '<?php echo escape($page['title']); ?>')">Delete</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Delete Page</h3>
            <button type="button" class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete "<strong id="deletePageTitle"></strong>"?</p>
            <p style="color: #dc3545; font-size: 0.9rem;">This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeDeleteModal()">Cancel</button>
            <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
        </div>
    </div>
</div>

<style>
.modal {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}
.modal-content {
    background: white;
    border-radius: 8px;
    max-width: 450px;
    width: 90%;
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}
.modal-header h3 { margin: 0; }
.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6b7280;
}
.modal-body { padding: 1.5rem; }
.modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}
.badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 500;
}
.badge-success { background: #d1fae5; color: #065f46; }
.badge-warning { background: #fef3c7; color: #92400e; }
.badge-info { background: #dbeafe; color: #1e40af; }
</style>

<script>
let deletePageId = null;

function deletePage(id, title) {
    deletePageId = id;
    document.getElementById('deletePageTitle').textContent = title;
    document.getElementById('deleteModal').style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    deletePageId = null;
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (!deletePageId) return;

    const formData = new FormData();
    formData.append('id', deletePageId);
    formData.append('_csrf_token', '<?php echo $_SESSION['_csrf_token'] ?? ''; ?>');

    fetch('/admin/pages/delete', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.error || 'Failed to delete page');
            closeDeleteModal();
        }
    })
    .catch(() => {
        alert('An error occurred');
        closeDeleteModal();
    });
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeDeleteModal();
});
</script>
