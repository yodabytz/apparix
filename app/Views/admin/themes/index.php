<div class="admin-header">
    <h1>Theme Settings</h1>
    <div class="header-actions">
        <button type="button" class="btn btn-secondary" onclick="showUploadModal()">Upload Theme</button>
        <a href="/admin/themes/create" class="btn btn-primary">+ Create Custom Theme</a>
    </div>
</div>

<?php if ($flash = getFlash('success')): ?>
    <div class="alert alert-success"><?php echo escape($flash); ?></div>
<?php endif; ?>

<?php if ($flash = getFlash('error')): ?>
    <div class="alert alert-error"><?php echo escape($flash); ?></div>
<?php endif; ?>

<?php
// Get installed themes from /content/themes
$installedThemes = \App\Core\ThemeLoader::getInstalledThemes();
?>

<?php if (!empty($installedThemes)): ?>
<div class="themes-section">
    <h2>Installed Themes</h2>
    <p class="section-description">Custom themes installed from theme packages.</p>

    <div class="theme-grid">
        <?php foreach ($installedThemes as $theme):
            $isActive = $active && isset($active['slug']) && $active['slug'] === $theme['slug'];
            $thumbnail = $theme['thumbnail'] ?: $theme['screenshot'];
        ?>
            <div class="theme-card installed-theme <?php echo $isActive ? 'active' : ''; ?>">
                <div class="theme-preview theme-screenshot">
                    <?php if ($thumbnail): ?>
                        <img src="<?php echo escape($thumbnail); ?>" alt="<?php echo escape($theme['name']); ?>" onerror="this.parentElement.classList.add('no-screenshot')">
                    <?php else: ?>
                        <div class="no-screenshot-placeholder">
                            <span>No Preview</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="theme-info">
                    <h3><?php echo escape($theme['name']); ?></h3>
                    <p><?php echo escape($theme['description'] ?: 'Installed theme'); ?></p>
                    <?php if (!empty($theme['colors'])): ?>
                    <div class="theme-colors">
                        <?php foreach (array_slice($theme['colors'], 0, 5) as $colorName => $colorValue): ?>
                        <span class="color-dot" style="background: <?php echo escape($colorValue); ?>;" title="<?php echo escape($colorName); ?>"></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <div class="theme-meta">
                        <span>By <?php echo escape($theme['author']); ?></span>
                        <span>v<?php echo escape($theme['version']); ?></span>
                    </div>
                </div>
                <div class="theme-actions">
                    <?php if ($isActive): ?>
                        <span class="badge badge-success">Active</span>
                    <?php else: ?>
                        <form method="POST" action="/admin/themes/activate-installed" class="inline-form">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="theme_slug" value="<?php echo escape($theme['slug']); ?>">
                            <button type="submit" class="btn btn-primary btn-sm">Activate</button>
                        </form>
                    <?php endif; ?>
                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteInstalledTheme('<?php echo escape($theme['slug']); ?>', '<?php echo escape($theme['name']); ?>')">Delete</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="themes-section">
    <h2>Pre-built Themes</h2>
    <p class="section-description">Choose an industry-optimized theme as your starting point. You can customize colors and layouts after selection.</p>

    <div class="theme-grid">
        <?php foreach ($presets as $theme): ?>
            <div class="theme-card <?php echo ($active && $active['id'] == $theme['id']) ? 'active' : ''; ?>">
                <div class="theme-preview" style="background: linear-gradient(135deg, <?php echo escape($theme['primary_color']); ?> 0%, <?php echo escape($theme['secondary_color']); ?> 100%);">
                    <div class="theme-preview-header" style="background: <?php echo escape($theme['accent_color']); ?>;">
                        <div class="preview-logo" style="color: <?php echo escape($theme['primary_color']); ?>;">Logo</div>
                    </div>
                    <div class="theme-preview-content">
                        <div class="preview-card"></div>
                        <div class="preview-card"></div>
                        <div class="preview-card"></div>
                    </div>
                </div>
                <div class="theme-info">
                    <h3><?php echo escape($theme['name']); ?></h3>
                    <p><?php echo escape($theme['description']); ?></p>
                    <div class="theme-colors">
                        <span class="color-dot" style="background: <?php echo escape($theme['primary_color']); ?>;" title="Primary"></span>
                        <span class="color-dot" style="background: <?php echo escape($theme['secondary_color']); ?>;" title="Secondary"></span>
                        <span class="color-dot" style="background: <?php echo escape($theme['accent_color']); ?>;" title="Accent"></span>
                    </div>
                    <div class="theme-meta">
                        <span>Layout: <?php echo escape(ucfirst($theme['layout_style'])); ?></span>
                        <span>Header: <?php echo escape(ucfirst($theme['header_style'])); ?></span>
                    </div>
                </div>
                <div class="theme-actions">
                    <?php if ($active && $active['id'] == $theme['id']): ?>
                        <span class="badge badge-success">Active</span>
                    <?php else: ?>
                        <form method="POST" action="/admin/themes/activate" class="inline-form">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="theme_id" value="<?php echo $theme['id']; ?>">
                            <button type="submit" class="btn btn-primary btn-sm">Activate</button>
                        </form>
                    <?php endif; ?>
                    <a href="/admin/themes/customize?id=<?php echo $theme['id']; ?>" class="btn btn-secondary btn-sm">Customize</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if (!empty($custom)): ?>
<div class="themes-section">
    <h2>Custom Themes</h2>
    <p class="section-description">Themes you've created or customized.</p>

    <div class="theme-grid">
        <?php foreach ($custom as $theme): ?>
            <div class="theme-card <?php echo ($active && $active['id'] == $theme['id']) ? 'active' : ''; ?>">
                <div class="theme-preview" style="background: linear-gradient(135deg, <?php echo escape($theme['primary_color']); ?> 0%, <?php echo escape($theme['secondary_color']); ?> 100%);">
                    <div class="theme-preview-header" style="background: <?php echo escape($theme['accent_color']); ?>;">
                        <div class="preview-logo" style="color: <?php echo escape($theme['primary_color']); ?>;">Logo</div>
                    </div>
                    <div class="theme-preview-content">
                        <div class="preview-card"></div>
                        <div class="preview-card"></div>
                        <div class="preview-card"></div>
                    </div>
                </div>
                <div class="theme-info">
                    <h3><?php echo escape($theme['name']); ?></h3>
                    <p><?php echo escape($theme['description'] ?: 'Custom theme'); ?></p>
                    <div class="theme-colors">
                        <span class="color-dot" style="background: <?php echo escape($theme['primary_color']); ?>;" title="Primary"></span>
                        <span class="color-dot" style="background: <?php echo escape($theme['secondary_color']); ?>;" title="Secondary"></span>
                        <span class="color-dot" style="background: <?php echo escape($theme['accent_color']); ?>;" title="Accent"></span>
                    </div>
                </div>
                <div class="theme-actions">
                    <?php if ($active && $active['id'] == $theme['id']): ?>
                        <span class="badge badge-success">Active</span>
                    <?php else: ?>
                        <form method="POST" action="/admin/themes/activate" class="inline-form">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="theme_id" value="<?php echo $theme['id']; ?>">
                            <button type="submit" class="btn btn-primary btn-sm">Activate</button>
                        </form>
                    <?php endif; ?>
                    <a href="/admin/themes/customize?id=<?php echo $theme['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteTheme(<?php echo $theme['id']; ?>, '<?php echo escape($theme['name']); ?>')">Delete</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<style>
.themes-section {
    margin-bottom: 40px;
}

.themes-section h2 {
    margin-bottom: 8px;
    font-size: 1.5rem;
}

.section-description {
    color: var(--admin-text-light);
    margin-bottom: 20px;
}

.theme-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 24px;
}

.theme-card {
    background: var(--admin-card-bg);
    border-radius: var(--admin-radius);
    overflow: hidden;
    border: 2px solid var(--admin-border);
    transition: all 0.2s ease;
}

.theme-card:hover {
    border-color: var(--admin-primary);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.theme-card.active {
    border-color: var(--admin-success);
}

.theme-preview {
    height: 160px;
    padding: 12px;
    display: flex;
    flex-direction: column;
}

.theme-preview-header {
    height: 30px;
    border-radius: 4px 4px 0 0;
    padding: 6px 12px;
    display: flex;
    align-items: center;
}

.preview-logo {
    font-weight: 700;
    font-size: 12px;
}

.theme-preview-content {
    flex: 1;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    padding: 12px 0;
}

.preview-card {
    background: rgba(255,255,255,0.9);
    border-radius: 4px;
}

.theme-info {
    padding: 16px;
}

.theme-info h3 {
    margin: 0 0 8px 0;
    font-size: 1.1rem;
}

.theme-info p {
    margin: 0 0 12px 0;
    color: var(--admin-text-light);
    font-size: 0.875rem;
    line-height: 1.4;
}

.theme-colors {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
}

.color-dot {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: 2px solid rgba(0,0,0,0.1);
    cursor: pointer;
}

.theme-meta {
    display: flex;
    gap: 16px;
    font-size: 0.75rem;
    color: var(--admin-text-light);
}

.theme-actions {
    padding: 12px 16px;
    background: var(--admin-bg);
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
}

.inline-form {
    display: inline;
}

.badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-success {
    background: var(--admin-success);
    color: white;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 0.875rem;
}

.btn-danger {
    background: var(--admin-danger);
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
}

.header-actions {
    display: flex;
    gap: 12px;
}

/* Screenshot theme preview styles */
.theme-screenshot {
    height: 180px;
    padding: 0;
    overflow: hidden;
    background: #f3f4f6;
}

.theme-screenshot img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: top;
}

.theme-screenshot.no-screenshot {
    display: flex;
    align-items: center;
    justify-content: center;
}

.no-screenshot-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: var(--admin-text-light);
    font-size: 0.875rem;
}

.installed-theme .theme-info h3::after {
    content: 'INSTALLED';
    font-size: 0.625rem;
    background: var(--admin-primary);
    color: white;
    padding: 2px 6px;
    border-radius: 4px;
    margin-left: 8px;
    vertical-align: middle;
    font-weight: 600;
}

/* Upload Modal */
.theme-upload-modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 1000;
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(4px);
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.theme-upload-modal.active {
    display: flex;
}

.theme-upload-content {
    background: var(--admin-card-bg);
    border-radius: var(--admin-radius);
    padding: 24px;
    max-width: 500px;
    width: 100%;
}

.theme-upload-content h3 {
    margin: 0 0 16px 0;
}

.upload-dropzone {
    border: 2px dashed var(--admin-border);
    border-radius: var(--admin-radius);
    padding: 40px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    margin-bottom: 16px;
}

.upload-dropzone:hover,
.upload-dropzone.dragover {
    border-color: var(--admin-primary);
    background: rgba(var(--admin-primary-rgb), 0.05);
}

.upload-dropzone-icon {
    font-size: 2.5rem;
    margin-bottom: 12px;
}

.upload-dropzone p {
    margin: 0;
    color: var(--admin-text-light);
}

.upload-dropzone input[type="file"] {
    display: none;
}

.upload-progress {
    display: none;
    margin-bottom: 16px;
}

.upload-progress.active {
    display: block;
}

.progress-bar {
    height: 8px;
    background: var(--admin-border);
    border-radius: 4px;
    overflow: hidden;
}

.progress-bar-fill {
    height: 100%;
    background: var(--admin-primary);
    width: 0;
    transition: width 0.3s ease;
}

.upload-status {
    font-size: 0.875rem;
    color: var(--admin-text-light);
    margin-top: 8px;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}
</style>

<script>
function deleteTheme(id, name) {
    if (!confirm('Are you sure you want to delete the theme "' + name + '"? This cannot be undone.')) {
        return;
    }

    fetch('/admin/themes/delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'theme_id=' + id + '&_csrf_token=' + encodeURIComponent('<?php echo csrfToken(); ?>')
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to delete theme');
        }
    })
    .catch(err => {
        alert('Error deleting theme');
        console.error(err);
    });
}

function deleteInstalledTheme(slug, name) {
    if (!confirm('Are you sure you want to delete the installed theme "' + name + '"? This will remove all theme files.')) {
        return;
    }

    fetch('/admin/themes/delete-installed', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'theme_slug=' + encodeURIComponent(slug) + '&_csrf_token=' + encodeURIComponent('<?php echo csrfToken(); ?>')
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to delete theme');
        }
    })
    .catch(err => {
        alert('Error deleting theme');
        console.error(err);
    });
}

function showUploadModal() {
    document.getElementById('themeUploadModal').classList.add('active');
}

function closeUploadModal() {
    document.getElementById('themeUploadModal').classList.remove('active');
    resetUploadForm();
}

function resetUploadForm() {
    document.getElementById('themeFile').value = '';
    document.getElementById('uploadProgress').classList.remove('active');
    document.getElementById('progressBarFill').style.width = '0%';
    document.getElementById('uploadStatus').textContent = '';
}

// Dropzone handlers
const dropzone = document.getElementById('uploadDropzone');
const fileInput = document.getElementById('themeFile');

if (dropzone && fileInput) {
    dropzone.addEventListener('click', () => fileInput.click());

    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.classList.add('dragover');
    });

    dropzone.addEventListener('dragleave', () => {
        dropzone.classList.remove('dragover');
    });

    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('dragover');
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            handleFileSelect();
        }
    });

    fileInput.addEventListener('change', handleFileSelect);
}

function handleFileSelect() {
    const file = fileInput.files[0];
    if (!file) return;

    if (!file.name.endsWith('.zip')) {
        alert('Please select a ZIP file');
        return;
    }

    uploadTheme(file);
}

function uploadTheme(file) {
    const progress = document.getElementById('uploadProgress');
    const progressFill = document.getElementById('progressBarFill');
    const status = document.getElementById('uploadStatus');

    progress.classList.add('active');
    status.textContent = 'Uploading...';

    const formData = new FormData();
    formData.append('theme_file', file);
    formData.append('_csrf_token', '<?php echo csrfToken(); ?>');

    const xhr = new XMLHttpRequest();

    xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
            const percent = Math.round((e.loaded / e.total) * 100);
            progressFill.style.width = percent + '%';
            status.textContent = 'Uploading... ' + percent + '%';
        }
    });

    xhr.addEventListener('load', () => {
        try {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                status.textContent = 'Theme installed successfully!';
                setTimeout(() => location.reload(), 1000);
            } else {
                status.textContent = 'Error: ' + (response.error || 'Upload failed');
                progressFill.style.background = 'var(--admin-danger)';
            }
        } catch (e) {
            status.textContent = 'Error parsing response';
            progressFill.style.background = 'var(--admin-danger)';
        }
    });

    xhr.addEventListener('error', () => {
        status.textContent = 'Upload failed';
        progressFill.style.background = 'var(--admin-danger)';
    });

    xhr.open('POST', '/admin/themes/upload');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.send(formData);
}

// Close modal on escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeUploadModal();
});
</script>

<!-- Theme Upload Modal -->
<div id="themeUploadModal" class="theme-upload-modal" onclick="if(event.target === this) closeUploadModal()">
    <div class="theme-upload-content">
        <h3>Upload Theme</h3>
        <p style="color: var(--admin-text-light); margin-bottom: 16px;">Upload a theme package (.zip) to install a new theme.</p>

        <div id="uploadDropzone" class="upload-dropzone">
            <div class="upload-dropzone-icon">&#128230;</div>
            <p>Drop ZIP file here or click to browse</p>
            <input type="file" id="themeFile" accept=".zip">
        </div>

        <div id="uploadProgress" class="upload-progress">
            <div class="progress-bar">
                <div id="progressBarFill" class="progress-bar-fill"></div>
            </div>
            <p id="uploadStatus" class="upload-status"></p>
        </div>

        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeUploadModal()">Cancel</button>
        </div>
    </div>
</div>
