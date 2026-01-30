<div class="page-header">
    <h1>Plugins</h1>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('uploadModal').classList.add('active')">
        + Upload Plugin
    </button>
</div>

<?php if ($flash = getFlash('success')): ?>
    <div class="alert alert-success"><?php echo escape($flash); ?></div>
<?php endif; ?>

<?php if ($flash = getFlash('error')): ?>
    <div class="alert alert-danger"><?php echo escape($flash); ?></div>
<?php endif; ?>

<!-- Plugin Types -->
<?php foreach ($typeLabels as $type => $label): ?>
    <?php $typePlugins = $groupedPlugins[$type] ?? []; ?>
    <?php if (!empty($typePlugins)): ?>
    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-header">
            <h3 class="card-title"><?php echo $label; ?></h3>
        </div>
        <div class="plugins-grid">
            <?php foreach ($typePlugins as $plugin): ?>
            <div class="plugin-card <?php echo $plugin['is_active'] ? 'active' : ''; ?>">
                <div class="plugin-icon">
                    <?php if ($plugin['icon']): ?>
                        <img src="/content/plugins/<?php echo escape($plugin['slug']); ?>/assets/<?php echo escape($plugin['icon']); ?>" alt="">
                    <?php else: ?>
                        <?php
                        $icons = [
                            'payment' => '💳',
                            'shipping' => '📦',
                            'analytics' => '📊',
                            'marketing' => '📧',
                            'utility' => '🔧'
                        ];
                        echo $icons[$plugin['type']] ?? '🔌';
                        ?>
                    <?php endif; ?>
                </div>
                <div class="plugin-info">
                    <h4><?php echo escape($plugin['name']); ?></h4>
                    <p class="plugin-description"><?php echo escape($plugin['description'] ?? ''); ?></p>
                    <div class="plugin-meta">
                        <span>v<?php echo escape($plugin['version']); ?></span>
                        <?php if ($plugin['author']): ?>
                        <span>by <?php echo escape($plugin['author']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="plugin-actions">
                    <?php if ($plugin['is_active']): ?>
                        <span class="badge badge-success">Active</span>
                        <a href="/admin/plugins/settings?slug=<?php echo escape($plugin['slug']); ?>" class="btn btn-sm btn-outline">Settings</a>
                        <?php if ($plugin['slug'] !== 'stripe'): ?>
                        <form method="POST" action="/admin/plugins/deactivate" style="display: inline;">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="slug" value="<?php echo escape($plugin['slug']); ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Deactivate</button>
                        </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="badge badge-gray">Inactive</span>
                        <form method="POST" action="/admin/plugins/activate" style="display: inline;">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="slug" value="<?php echo escape($plugin['slug']); ?>">
                            <button type="submit" class="btn btn-sm btn-primary">Activate</button>
                        </form>
                        <?php if ($plugin['slug'] !== 'stripe'): ?>
                        <form method="POST" action="/admin/plugins/delete" style="display: inline;" onsubmit="return confirm('Uninstall this plugin?');">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="slug" value="<?php echo escape($plugin['slug']); ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Uninstall</button>
                        </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
<?php endforeach; ?>

<?php if (empty($plugins)): ?>
<div class="empty-state">
    <h3>No plugins installed</h3>
    <p>Upload a plugin ZIP file to get started.</p>
</div>
<?php endif; ?>

<!-- Upload Modal -->
<div class="modal" id="uploadModal">
    <div class="modal-backdrop" onclick="document.getElementById('uploadModal').classList.remove('active')"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3>Upload Plugin</h3>
            <button type="button" class="modal-close" onclick="document.getElementById('uploadModal').classList.remove('active')">&times;</button>
        </div>
        <form method="POST" action="/admin/plugins/upload" enctype="multipart/form-data">
            <?php echo csrfField(); ?>
            <div class="modal-body">
                <div class="form-group">
                    <label for="plugin_zip">Plugin ZIP File</label>
                    <input type="file" name="plugin_zip" id="plugin_zip" accept=".zip" required class="form-control">
                    <span class="form-help">Upload a plugin package (.zip file containing plugin.json)</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('uploadModal').classList.remove('active')">Cancel</button>
                <button type="submit" class="btn btn-primary">Upload & Install</button>
            </div>
        </form>
    </div>
</div>

<style>
.plugins-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1rem;
    padding: 1rem;
}

.plugin-card {
    background: var(--admin-bg-light, #f8f9fa);
    border-radius: 8px;
    padding: 1.25rem;
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: flex-start;
    border: 2px solid transparent;
    transition: border-color 0.2s;
}

.plugin-card.active {
    border-color: var(--admin-success, #10b981);
    background: rgba(16, 185, 129, 0.05);
}

.plugin-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.plugin-icon img {
    max-width: 32px;
    max-height: 32px;
}

.plugin-info {
    flex: 1;
    min-width: 200px;
}

.plugin-info h4 {
    margin: 0 0 0.25rem 0;
    font-size: 1rem;
}

.plugin-description {
    margin: 0 0 0.5rem 0;
    font-size: 0.875rem;
    color: var(--admin-text-light);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.plugin-meta {
    font-size: 0.75rem;
    color: var(--admin-text-light);
}

.plugin-meta span {
    margin-right: 0.75rem;
}

.plugin-actions {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    flex-wrap: wrap;
    width: 100%;
    padding-top: 0.75rem;
    border-top: 1px solid var(--admin-border, #e5e7eb);
    margin-top: 0.5rem;
}

/* Modal styles */
.modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal.active {
    display: flex;
}

.modal-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.5);
}

.modal-content {
    position: relative;
    background: white;
    border-radius: 12px;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow: auto;
}

.modal-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--admin-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--admin-text-light);
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--admin-border);
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
}

@media (max-width: 768px) {
    .plugins-grid {
        grid-template-columns: 1fr;
    }
}
</style>
