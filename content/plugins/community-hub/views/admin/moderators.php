<div class="community-card" style="margin-bottom:0;">
    <h2>Manage Moderators</h2>
</div>

<!-- Add Moderator Form -->
<div class="community-card">
    <h3>Add Moderator</h3>
    <form method="POST" action="/admin/community/moderators/add">
        <?= csrfField() ?>
        <div class="community-form-row">
            <div class="community-form-group">
                <label for="mod-email">User Email</label>
                <input type="email" id="mod-email" name="email" required placeholder="user@example.com">
            </div>
            <div class="community-form-group">
                <label for="mod-role">Role</label>
                <select id="mod-role" name="role" required>
                    <option value="moderator">Moderator</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="community-form-group">
                <label for="mod-category">Category Scope</label>
                <select id="mod-category" name="category_id">
                    <option value="">Global (All Categories)</option>
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>">
                                <?= htmlspecialchars($category['icon'] ?? '') ?> <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>
        <button type="submit" class="community-btn community-btn-primary">Add Moderator</button>
    </form>
</div>

<!-- Current Moderators -->
<div class="community-card">
    <h3>Current Moderators</h3>
    <?php if (!empty($moderators)): ?>
        <table class="community-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Scope</th>
                    <th>Date Added</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($moderators as $mod): ?>
                    <tr>
                        <td><?= htmlspecialchars(trim(($mod['first_name'] ?? '') . ' ' . ($mod['last_name'] ?? '')) ?: '—') ?></td>
                        <td><?= htmlspecialchars($mod['email']) ?></td>
                        <td>
                            <?php if (($mod['role'] ?? '') === 'admin'): ?>
                                <span class="community-badge community-badge-danger">Admin</span>
                            <?php else: ?>
                                <span class="community-badge community-badge-info">Moderator</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($mod['category_name'])): ?>
                                <?= htmlspecialchars($mod['category_name']) ?>
                            <?php else: ?>
                                <span class="community-badge community-badge-success">Global</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('M j, Y', strtotime($mod['created_at'])) ?></td>
                        <td>
                            <form method="POST" action="/admin/community/moderators/remove"
                                onsubmit="return confirm('Remove this moderator?');">
                                <?= csrfField() ?>
                                <input type="hidden" name="id" value="<?= (int)$mod['id'] ?>">
                                <button type="submit" class="community-btn community-btn-danger community-btn-sm">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="community-empty">No moderators assigned yet.</div>
    <?php endif; ?>
</div>
