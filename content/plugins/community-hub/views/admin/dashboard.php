<h2 style="margin:0 0 20px;">Community Forum</h2>

<!-- Stats -->
<div class="community-stats-grid">
    <div class="community-stat-card">
        <div class="stat-value"><?php echo (int)($categoryCount ?? 0); ?></div>
        <div class="stat-label">Categories</div>
    </div>
    <div class="community-stat-card">
        <div class="stat-value"><?php echo (int)($topicStats['total_topics'] ?? 0); ?></div>
        <div class="stat-label">Topics</div>
    </div>
    <div class="community-stat-card">
        <div class="stat-value"><?php echo (int)($replyStats['total_replies'] ?? 0); ?></div>
        <div class="stat-label">Replies</div>
    </div>
    <div class="community-stat-card">
        <div class="stat-value"><?php echo (int)($topicStats['total_views'] ?? 0); ?></div>
        <div class="stat-label">Views</div>
    </div>
</div>

<!-- Navigation Tabs -->
<div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
    <a href="/admin/community/moderation" class="community-btn community-btn-outline">
        Moderation Queue <?php $pc = (int)($topicStats['pending_topics'] ?? 0) + (int)($replyStats['pending_replies'] ?? 0); if ($pc > 0): ?><span class="community-badge community-badge-warning" style="margin-left:4px;"><?php echo $pc; ?></span><?php endif; ?>
    </a>
    <a href="/admin/community/moderators" class="community-btn community-btn-outline">Moderators</a>
    <a href="/admin/community/reports" class="community-btn community-btn-outline">
        Reports <?php if (($pendingReports ?? 0) > 0): ?><span class="community-badge community-badge-danger" style="margin-left:4px;"><?php echo (int)$pendingReports; ?></span><?php endif; ?>
    </a>
    <a href="/admin/community/settings" class="community-btn community-btn-outline">Settings</a>
</div>

<?php
// Separate parent and child categories for display
$parentCats = array_filter($categories, fn($c) => empty($c['parent_id']));
$childCats = [];
foreach ($categories as $c) {
    if (!empty($c['parent_id'])) {
        $childCats[$c['parent_id']][] = $c;
    }
}
?>

<!-- Add Category -->
<div class="community-card">
    <h3>Add Category</h3>
    <form method="POST" action="/admin/community/categories/store">
        <?php echo csrfField(); ?>
        <div class="community-form-row">
            <div class="community-form-group">
                <label for="cat-name">Name</label>
                <input type="text" id="cat-name" name="name" required placeholder="e.g. General Discussion">
            </div>
            <div class="community-form-group" style="max-width:100px;">
                <label for="cat-icon">Icon</label>
                <input type="text" id="cat-icon" name="icon" placeholder="&#128172;" maxlength="10">
            </div>
        </div>
        <div class="community-form-group">
            <label for="cat-parent">Parent Category</label>
            <select id="cat-parent" name="parent_id">
                <option value="">None (Top Level)</option>
                <?php foreach ($parentCats as $pc): ?>
                    <option value="<?php echo (int)$pc['id']; ?>"><?php echo escape($pc['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="community-form-group">
            <label for="cat-desc">Description</label>
            <textarea id="cat-desc" name="description" rows="2" placeholder="Brief description of this category"></textarea>
        </div>
        <div class="community-form-group">
            <div class="community-checkbox-group">
                <input type="checkbox" id="cat-admin-only" name="admin_only" value="1">
                <label for="cat-admin-only" style="margin-bottom:0;">Admin Only (only admins/moderators can post)</label>
            </div>
        </div>
        <button type="submit" class="community-btn community-btn-primary">Add Category</button>
    </form>
</div>

<!-- Categories List -->
<div class="community-card">
    <h3>Categories</h3>
    <?php if (!empty($categories)): ?>
        <table class="community-table">
            <thead>
                <tr>
                    <th></th>
                    <th>Name</th>
                    <th>Topics</th>
                    <th>Replies</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($parentCats as $cat): ?>
                    <tr>
                        <td style="font-size:20px;width:40px;"><?php echo escape($cat['icon'] ?? ''); ?></td>
                        <td>
                            <strong><?php echo escape($cat['name']); ?></strong>
                            <?php if ($cat['description']): ?>
                                <div style="font-size:12px;color:#6b7280;margin-top:2px;"><?php echo escape($cat['description']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo (int)$cat['topic_count']; ?></td>
                        <td><?php echo (int)$cat['reply_count']; ?></td>
                        <td>
                            <?php if ($cat['is_active']): ?>
                                <span class="community-badge community-badge-success">Active</span>
                            <?php else: ?>
                                <span class="community-badge community-badge-danger">Inactive</span>
                            <?php endif; ?>
                            <?php if (!empty($cat['admin_only'])): ?>
                                <span class="community-badge community-badge-info" style="margin-left:4px;">Admin Only</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="community-actions">
                                <button type="button" class="community-btn community-btn-outline community-btn-sm"
                                    onclick="openEditModal(<?php echo escape(json_encode($cat)); ?>)">Edit</button>
                                <form method="POST" action="/admin/community/categories/delete"
                                    onsubmit="return confirm('Delete this category and all its topics?');">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="id" value="<?php echo (int)$cat['id']; ?>">
                                    <button type="submit" class="community-btn community-btn-danger community-btn-sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php if (!empty($childCats[$cat['id']])): ?>
                        <?php foreach ($childCats[$cat['id']] as $sub): ?>
                            <tr style="background:#f9fafb;">
                                <td style="font-size:16px;width:40px;padding-left:24px;"><?php echo escape($sub['icon'] ?? ''); ?></td>
                                <td style="padding-left:24px;">
                                    <span style="color:#9ca3af;margin-right:4px;">&#8627;</span>
                                    <strong><?php echo escape($sub['name']); ?></strong>
                                    <?php if ($sub['description']): ?>
                                        <div style="font-size:12px;color:#6b7280;margin-top:2px;"><?php echo escape($sub['description']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo (int)$sub['topic_count']; ?></td>
                                <td><?php echo (int)$sub['reply_count']; ?></td>
                                <td>
                                    <?php if ($sub['is_active']): ?>
                                        <span class="community-badge community-badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="community-badge community-badge-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="community-actions">
                                        <button type="button" class="community-btn community-btn-outline community-btn-sm"
                                            onclick="openEditModal(<?php echo escape(json_encode($sub)); ?>)">Edit</button>
                                        <form method="POST" action="/admin/community/categories/delete"
                                            onsubmit="return confirm('Delete this subcategory and all its topics?');">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="id" value="<?php echo (int)$sub['id']; ?>">
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
        <div class="community-empty">
            <p>No categories yet. Add one above to get your forum started.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Recent Topics -->
<?php if (!empty($recentTopics)): ?>
<div class="community-card">
    <h3>Recent Topics</h3>
    <table class="community-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Author</th>
                <th>Replies</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentTopics as $topic): ?>
                <tr>
                    <td><a href="/admin/community/topic/<?php echo (int)$topic['id']; ?>"><?php echo escape($topic['title']); ?></a></td>
                    <td><?php echo escape(trim(($topic['first_name'] ?? '') . ' ' . ($topic['last_name'] ?? ''))); ?></td>
                    <td><?php echo (int)$topic['reply_count']; ?></td>
                    <td>
                        <?php if ($topic['status'] === 'pending'): ?>
                            <span class="community-badge community-badge-warning">Pending</span>
                        <?php elseif ($topic['status'] === 'closed'): ?>
                            <span class="community-badge community-badge-danger">Closed</span>
                        <?php else: ?>
                            <span class="community-badge community-badge-success">Active</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo date('M j', strtotime($topic['created_at'])); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Edit Category Modal -->
<div class="community-modal-overlay" id="editModal">
    <div class="community-modal">
        <h3>Edit Category</h3>
        <form method="POST" id="editCategoryForm" action="/admin/community/categories/update">
            <?php echo csrfField(); ?>
            <input type="hidden" name="id" id="edit-id">
            <div class="community-form-row">
                <div class="community-form-group">
                    <label for="edit-name">Name</label>
                    <input type="text" id="edit-name" name="name" required>
                </div>
                <div class="community-form-group" style="max-width:100px;">
                    <label for="edit-icon">Icon</label>
                    <input type="text" id="edit-icon" name="icon" maxlength="10">
                </div>
            </div>
            <div class="community-form-group">
                <label for="edit-parent">Parent Category</label>
                <select id="edit-parent" name="parent_id">
                    <option value="">None (Top Level)</option>
                    <?php foreach ($parentCats as $pc): ?>
                        <option value="<?php echo (int)$pc['id']; ?>"><?php echo escape($pc['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="community-form-group">
                <label for="edit-description">Description</label>
                <textarea id="edit-description" name="description" rows="2"></textarea>
            </div>
            <div class="community-form-group">
                <div class="community-checkbox-group">
                    <input type="checkbox" id="edit-active" name="is_active" value="1">
                    <label for="edit-active" style="margin-bottom:0;">Active</label>
                </div>
            </div>
            <div class="community-form-group">
                <div class="community-checkbox-group">
                    <input type="checkbox" id="edit-admin-only" name="admin_only" value="1">
                    <label for="edit-admin-only" style="margin-bottom:0;">Admin Only (only admins/moderators can post)</label>
                </div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" class="community-btn community-btn-outline" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="community-btn community-btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(cat) {
    document.getElementById('edit-id').value = cat.id;
    document.getElementById('edit-name').value = cat.name || '';
    document.getElementById('edit-icon').value = cat.icon || '';
    document.getElementById('edit-description').value = cat.description || '';
    document.getElementById('edit-active').checked = (cat.is_active == 1);
    document.getElementById('edit-admin-only').checked = (cat.admin_only == 1);
    document.getElementById('edit-parent').value = cat.parent_id || '';
    document.getElementById('editModal').classList.add('active');
}
function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
}
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
</script>
