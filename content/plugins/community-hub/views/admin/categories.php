<?php
$parentCats = array_filter($categories, fn($c) => empty($c['parent_id']));
$childCats = [];
foreach ($categories as $c) {
    if (!empty($c['parent_id'])) {
        $childCats[$c['parent_id']][] = $c;
    }
}
?>
<div class="community-card" style="margin-bottom:0;">
    <h2>Manage Categories</h2>
</div>

<!-- Add Category Form -->
<div class="community-card">
    <h3>Add Category</h3>
    <form method="POST" action="/admin/community/categories/store">
        <?= csrfField() ?>
        <div class="community-form-row">
            <div class="community-form-group">
                <label for="cat-name">Name</label>
                <input type="text" id="cat-name" name="name" required placeholder="Category name">
            </div>
            <div class="community-form-group" style="max-width:100px;">
                <label for="cat-icon">Icon (Emoji)</label>
                <input type="text" id="cat-icon" name="icon" placeholder="💬" maxlength="10">
            </div>
        </div>
        <div class="community-form-group">
            <label for="cat-parent">Parent Category</label>
            <select id="cat-parent" name="parent_id">
                <option value="">None (Top Level)</option>
                <?php foreach ($parentCats as $pc): ?>
                    <option value="<?= (int)$pc['id'] ?>"><?= htmlspecialchars($pc['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="community-form-group">
            <label for="cat-description">Description</label>
            <textarea id="cat-description" name="description" placeholder="Brief description of this category"></textarea>
        </div>
        <button type="submit" class="community-btn community-btn-primary">Add Category</button>
    </form>
</div>

<!-- Categories Table -->
<div class="community-card">
    <h3>Categories</h3>
    <?php if (!empty($categories)): ?>
        <table class="community-table">
            <thead>
                <tr>
                    <th>Icon</th>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Topics</th>
                    <th>Replies</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($parentCats as $category): ?>
                    <tr>
                        <td style="font-size:20px;"><?= htmlspecialchars($category['icon'] ?? '📁') ?></td>
                        <td><strong><?= htmlspecialchars($category['name']) ?></strong></td>
                        <td><code><?= htmlspecialchars($category['slug']) ?></code></td>
                        <td><?= number_format($category['topic_count'] ?? 0) ?></td>
                        <td><?= number_format($category['reply_count'] ?? 0) ?></td>
                        <td>
                            <?php if ($category['is_active'] ?? 1): ?>
                                <span class="community-badge community-badge-success">Active</span>
                            <?php else: ?>
                                <span class="community-badge community-badge-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="community-actions">
                                <button type="button" class="community-btn community-btn-outline community-btn-sm"
                                    onclick="openEditModal(<?= htmlspecialchars(json_encode($category)) ?>)">Edit</button>
                                <form method="POST" action="/admin/community/categories/delete"
                                    onsubmit="return confirm('Delete this category and all its topics?');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= (int)$category['id'] ?>">
                                    <button type="submit" class="community-btn community-btn-danger community-btn-sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php if (!empty($childCats[$category['id']])): ?>
                        <?php foreach ($childCats[$category['id']] as $sub): ?>
                            <tr style="background:#f9fafb;">
                                <td style="font-size:16px;padding-left:24px;"><?= htmlspecialchars($sub['icon'] ?? '📁') ?></td>
                                <td style="padding-left:24px;">
                                    <span style="color:#9ca3af;margin-right:4px;">&#8627;</span>
                                    <strong><?= htmlspecialchars($sub['name']) ?></strong>
                                </td>
                                <td><code><?= htmlspecialchars($sub['slug']) ?></code></td>
                                <td><?= number_format($sub['topic_count'] ?? 0) ?></td>
                                <td><?= number_format($sub['reply_count'] ?? 0) ?></td>
                                <td>
                                    <?php if ($sub['is_active'] ?? 1): ?>
                                        <span class="community-badge community-badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="community-badge community-badge-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="community-actions">
                                        <button type="button" class="community-btn community-btn-outline community-btn-sm"
                                            onclick="openEditModal(<?= htmlspecialchars(json_encode($sub)) ?>)">Edit</button>
                                        <form method="POST" action="/admin/community/categories/delete"
                                            onsubmit="return confirm('Delete this subcategory and all its topics?');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="id" value="<?= (int)$sub['id'] ?>">
                                            <button type="submit" class="community-btn community-btn-danger community-btn-sm">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="community-empty">No categories created yet.</div>
    <?php endif; ?>
</div>

<!-- Edit Modal -->
<div class="community-modal-overlay" id="editModal">
    <div class="community-modal">
        <h3>Edit Category</h3>
        <form method="POST" id="editCategoryForm" action="/admin/community/categories/update">
            <?= csrfField() ?>
            <input type="hidden" name="id" id="edit-id">
            <div class="community-form-row">
                <div class="community-form-group">
                    <label for="edit-name">Name</label>
                    <input type="text" id="edit-name" name="name" required>
                </div>
                <div class="community-form-group" style="max-width:100px;">
                    <label for="edit-icon">Icon (Emoji)</label>
                    <input type="text" id="edit-icon" name="icon" maxlength="10">
                </div>
            </div>
            <div class="community-form-group">
                <label for="edit-parent">Parent Category</label>
                <select id="edit-parent" name="parent_id">
                    <option value="">None (Top Level)</option>
                    <?php foreach ($parentCats as $pc): ?>
                        <option value="<?= (int)$pc['id'] ?>"><?= htmlspecialchars($pc['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="community-form-group">
                <label for="edit-description">Description</label>
                <textarea id="edit-description" name="description"></textarea>
            </div>
            <div class="community-form-group">
                <div class="community-checkbox-group">
                    <input type="checkbox" id="edit-is-active" name="is_active" value="1">
                    <label for="edit-is-active" style="margin-bottom:0;">Active</label>
                </div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" class="community-btn community-btn-outline" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="community-btn community-btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(category) {
    document.getElementById('edit-id').value = category.id;
    document.getElementById('edit-name').value = category.name || '';
    document.getElementById('edit-icon').value = category.icon || '';
    document.getElementById('edit-description').value = category.description || '';
    document.getElementById('edit-is-active').checked = (category.is_active == 1);
    document.getElementById('edit-parent').value = category.parent_id || '';
    document.getElementById('editModal').classList.add('active');
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
}

document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
</script>
