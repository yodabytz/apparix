<div class="page-header">
    <div>
        <a href="/admin/plugins" class="btn btn-sm btn-outline" style="margin-bottom: 0.5rem;">&larr; Back to Plugins</a>
        <h1><?php echo escape($plugin['name']); ?> Settings</h1>
    </div>
</div>

<?php if ($flash = getFlash('success')): ?>
    <div class="alert alert-success"><?php echo escape($flash); ?></div>
<?php endif; ?>

<?php if ($flash = getFlash('error')): ?>
    <div class="alert alert-danger"><?php echo escape($flash); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Configuration</h3>
        <span class="badge <?php echo $plugin['is_active'] ? 'badge-success' : 'badge-gray'; ?>">
            <?php echo $plugin['is_active'] ? 'Active' : 'Inactive'; ?>
        </span>
    </div>

    <form method="POST" action="/admin/plugins/settings" class="settings-form">
        <?php echo csrfField(); ?>
        <input type="hidden" name="slug" value="<?php echo escape($plugin['slug']); ?>">

        <?php if (!empty($settingsHtml)): ?>
            <!-- Plugin provides its own settings view -->
            <?php echo $settingsHtml; ?>
        <?php elseif (!empty($settingsSchema)): ?>
            <!-- Generate form from schema -->
            <?php foreach ($settingsSchema as $field): ?>
                <?php
                    $key = $field['key'];
                    $type = $field['type'] ?? 'text';
                    $label = $field['label'] ?? $key;
                    $value = $settings[$key] ?? ($field['default'] ?? '');
                    $required = ($field['required'] ?? false) ? 'required' : '';
                    $help = $field['help'] ?? '';
                ?>
                <div class="form-group">
                    <label for="setting_<?php echo $key; ?>"><?php echo escape($label); ?></label>

                    <?php if ($type === 'password'): ?>
                        <input type="password" name="settings[<?php echo $key; ?>]" id="setting_<?php echo $key; ?>"
                               value="<?php echo escape($value); ?>" class="form-control" <?php echo $required; ?>>
                    <?php elseif ($type === 'textarea'): ?>
                        <textarea name="settings[<?php echo $key; ?>]" id="setting_<?php echo $key; ?>"
                                  class="form-control" <?php echo $required; ?>><?php echo escape($value); ?></textarea>
                    <?php elseif ($type === 'select'): ?>
                        <select name="settings[<?php echo $key; ?>]" id="setting_<?php echo $key; ?>"
                                class="form-control" <?php echo $required; ?>>
                            <?php foreach ($field['options'] as $opt): ?>
                                <option value="<?php echo escape($opt); ?>" <?php echo $value === $opt ? 'selected' : ''; ?>>
                                    <?php echo escape($opt); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ($type === 'checkbox'): ?>
                        <label class="checkbox-label">
                            <input type="hidden" name="settings[<?php echo $key; ?>]" value="0">
                            <input type="checkbox" name="settings[<?php echo $key; ?>]" id="setting_<?php echo $key; ?>"
                                   value="1" <?php echo $value ? 'checked' : ''; ?>>
                            <span><?php echo escape($label); ?></span>
                        </label>
                    <?php else: ?>
                        <input type="text" name="settings[<?php echo $key; ?>]" id="setting_<?php echo $key; ?>"
                               value="<?php echo escape($value); ?>" class="form-control" <?php echo $required; ?>>
                    <?php endif; ?>

                    <?php if ($help): ?>
                        <span class="form-help"><?php echo escape($help); ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state" style="padding: 2rem;">
                <p>This plugin has no configurable settings.</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($settingsHtml) || !empty($settingsSchema)): ?>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </div>
        <?php endif; ?>
    </form>
</div>

<!-- Plugin Info -->
<div class="card" style="margin-top: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title">Plugin Information</h3>
    </div>
    <div style="padding: 1rem;">
        <div style="display: grid; grid-template-columns: 150px 1fr; gap: 0.75rem; font-size: 0.875rem;">
            <div style="color: var(--admin-text-light);">Slug</div>
            <div><code><?php echo escape($plugin['slug']); ?></code></div>

            <div style="color: var(--admin-text-light);">Version</div>
            <div><?php echo escape($plugin['version']); ?></div>

            <div style="color: var(--admin-text-light);">Type</div>
            <div><?php echo ucfirst(escape($plugin['type'])); ?></div>

            <?php if ($plugin['author']): ?>
            <div style="color: var(--admin-text-light);">Author</div>
            <div>
                <?php if ($plugin['author_url']): ?>
                    <a href="<?php echo escape($plugin['author_url']); ?>" target="_blank"><?php echo escape($plugin['author']); ?></a>
                <?php else: ?>
                    <?php echo escape($plugin['author']); ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div style="color: var(--admin-text-light);">Installed</div>
            <div><?php echo date('M j, Y', strtotime($plugin['installed_at'])); ?></div>
        </div>
    </div>
</div>

<style>
.settings-form {
    padding: 1.5rem;
}

.settings-form .form-group {
    margin-bottom: 1.25rem;
}

.settings-form .form-actions {
    padding-top: 1rem;
    border-top: 1px solid var(--admin-border, #e5e7eb);
    margin-top: 1rem;
}
</style>
