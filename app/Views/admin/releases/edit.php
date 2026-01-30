<div class="page-header">
    <h1>Edit Release v<?php echo escape($release['version']); ?></h1>
    <a href="/admin/releases" class="btn btn-outline">Back to Releases</a>
</div>

<form action="/admin/releases/update" method="POST" enctype="multipart/form-data">
    <?php echo csrfField(); ?>
    <input type="hidden" name="id" value="<?php echo $release['id']; ?>">

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
        <div>
            <div class="card">
                <h3 class="card-title" style="margin-bottom: 1rem;">Release Information</h3>

                <div class="form-group">
                    <label class="form-label">Version</label>
                    <input type="text" class="form-input" value="<?php echo escape($release['version']); ?>" disabled>
                    <small style="color: var(--admin-text-light);">Version cannot be changed after creation</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="update_file">Update Package</label>
                    <input type="file" id="update_file" name="update_file" class="form-input" accept=".tar.gz,.tgz,.zip">
                    <small style="color: var(--admin-text-light);">
                        Current: <?php echo escape($release['update_file']); ?>
                        (<?php echo formatBytes($release['file_size']); ?>)
                        - Upload new file to replace
                    </small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="release_notes">Release Notes</label>
                    <textarea id="release_notes" name="release_notes" class="form-textarea" rows="3"><?php echo escape($release['release_notes']); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="changelog">Changelog (Markdown)</label>
                    <textarea id="changelog" name="changelog" class="form-textarea" rows="10"><?php echo escape($release['changelog']); ?></textarea>
                </div>
            </div>

            <!-- File Info -->
            <div class="card">
                <h3 class="card-title" style="margin-bottom: 1rem;">File Details</h3>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <div>
                        <strong>File Name:</strong><br>
                        <code><?php echo escape($release['update_file']); ?></code>
                    </div>
                    <div>
                        <strong>File Size:</strong><br>
                        <?php echo formatBytes($release['file_size']); ?>
                    </div>
                    <div style="grid-column: span 2;">
                        <strong>SHA-256 Hash:</strong><br>
                        <code style="font-size: 0.75rem; word-break: break-all;"><?php echo escape($release['file_hash']); ?></code>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="card">
                <h3 class="card-title" style="margin-bottom: 1rem;">Requirements</h3>

                <div class="form-group">
                    <label class="form-label" for="min_php_version">Minimum PHP Version</label>
                    <select id="min_php_version" name="min_php_version" class="form-input">
                        <option value="8.0" <?php echo $release['min_php_version'] === '8.0' ? 'selected' : ''; ?>>PHP 8.0</option>
                        <option value="8.1" <?php echo $release['min_php_version'] === '8.1' ? 'selected' : ''; ?>>PHP 8.1</option>
                        <option value="8.2" <?php echo $release['min_php_version'] === '8.2' ? 'selected' : ''; ?>>PHP 8.2</option>
                        <option value="8.3" <?php echo $release['min_php_version'] === '8.3' ? 'selected' : ''; ?>>PHP 8.3</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="min_edition">Minimum License Edition</label>
                    <select id="min_edition" name="min_edition" class="form-input">
                        <option value="S" <?php echo $release['min_edition'] === 'S' ? 'selected' : ''; ?>>Standard (All users)</option>
                        <option value="P" <?php echo $release['min_edition'] === 'P' ? 'selected' : ''; ?>>Professional</option>
                        <option value="E" <?php echo $release['min_edition'] === 'E' ? 'selected' : ''; ?>>Enterprise</option>
                        <option value="U" <?php echo $release['min_edition'] === 'U' ? 'selected' : ''; ?>>Unlimited</option>
                    </select>
                </div>
            </div>

            <div class="card">
                <h3 class="card-title" style="margin-bottom: 1rem;">Status</h3>

                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" name="is_active" value="1" <?php echo $release['is_active'] ? 'checked' : ''; ?>>
                        <span>Active</span>
                    </label>
                </div>

                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--admin-border);">
                    <div style="font-size: 0.875rem; color: var(--admin-text-light);">
                        <strong>Downloads:</strong> <?php echo number_format($release['download_count']); ?><br>
                        <strong>Released:</strong> <?php echo date('M j, Y g:i A', strtotime($release['released_at'])); ?>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Save Changes
            </button>
        </div>
    </div>
</form>

<?php
function formatBytes($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}
?>
