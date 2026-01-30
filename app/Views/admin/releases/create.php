<div class="page-header">
    <h1>Create New Release</h1>
    <a href="/admin/releases" class="btn btn-outline">Cancel</a>
</div>

<form action="/admin/releases/store" method="POST" enctype="multipart/form-data">
    <?php echo csrfField(); ?>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
        <div>
            <div class="card">
                <h3 class="card-title" style="margin-bottom: 1rem;">Release Information</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="version">Version *</label>
                        <input type="text" id="version" name="version" class="form-input" placeholder="1.2.3" required
                               pattern="\d+\.\d+\.\d+" title="Use semantic versioning (e.g., 1.2.3)">
                        <small style="color: var(--admin-text-light);">Semantic versioning: major.minor.patch</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="release_type">Release Type</label>
                        <select id="release_type" name="release_type" class="form-input">
                            <option value="stable">Stable</option>
                            <option value="beta">Beta</option>
                            <option value="alpha">Alpha</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="update_file">Update Package *</label>
                    <input type="file" id="update_file" name="update_file" class="form-input" accept=".tar.gz,.tgz,.zip" required>
                    <small style="color: var(--admin-text-light);">Upload the .tar.gz package containing the update</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="release_notes">Release Notes</label>
                    <textarea id="release_notes" name="release_notes" class="form-textarea" rows="3" placeholder="Brief summary of this release..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="changelog">Changelog (Markdown)</label>
                    <textarea id="changelog" name="changelog" class="form-textarea" rows="10" placeholder="## What's New

### Features
- Feature 1
- Feature 2

### Bug Fixes
- Fixed issue X

### Breaking Changes
- None"></textarea>
                </div>
            </div>
        </div>

        <div>
            <div class="card">
                <h3 class="card-title" style="margin-bottom: 1rem;">Requirements</h3>

                <div class="form-group">
                    <label class="form-label" for="min_php_version">Minimum PHP Version</label>
                    <select id="min_php_version" name="min_php_version" class="form-input">
                        <option value="8.0">PHP 8.0</option>
                        <option value="8.1">PHP 8.1</option>
                        <option value="8.2">PHP 8.2</option>
                        <option value="8.3">PHP 8.3</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="min_edition">Minimum License Edition</label>
                    <select id="min_edition" name="min_edition" class="form-input">
                        <option value="S">Standard (All users)</option>
                        <option value="P">Professional</option>
                        <option value="E">Enterprise</option>
                        <option value="U">Unlimited</option>
                    </select>
                    <small style="color: var(--admin-text-light);">Users with lower editions won't see this update</small>
                </div>
            </div>

            <div class="card">
                <h3 class="card-title" style="margin-bottom: 1rem;">Status</h3>

                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" name="is_active" value="1" checked>
                        <span>Active (users can download this version)</span>
                    </label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Create Release
            </button>
        </div>
    </div>
</form>
