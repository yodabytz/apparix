<?php
/**
 * Backup Plugin Admin Settings View
 */

// Get plugin instance
$backupPlugin = new \Plugins\Backup\BackupPlugin();
$backupPlugin->initialize();

// Handle actions
$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Verify CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        switch ($_POST['action']) {
            case 'create_backup':
                $result = $backupPlugin->createBackup(false);
                if ($result['success']) {
                    $message = "Backup created successfully! Size: {$result['size_formatted']}";
                } else {
                    $error = "Backup failed: " . implode(', ', $result['errors']);
                }
                break;

            case 'delete_backup':
                $filename = $_POST['filename'] ?? '';
                if ($backupPlugin->deleteBackup($filename)) {
                    $message = "Backup deleted successfully.";
                } else {
                    $error = "Failed to delete backup.";
                }
                break;

            case 'save_settings':
                $settings = [
                    'retention_count' => max(1, min(30, (int)($_POST['retention_count'] ?? 5))),
                    'include_database' => isset($_POST['include_database']),
                    'include_code' => isset($_POST['include_code']),
                    'include_config' => isset($_POST['include_config']),
                    'auto_backup' => isset($_POST['auto_backup']),
                    'backup_schedule' => $_POST['backup_schedule'] ?? 'daily',
                ];
                if ($backupPlugin->saveSettings($settings)) {
                    $message = "Settings saved successfully.";
                } else {
                    $error = "Failed to save settings.";
                }
                break;
        }
    }
}

// Handle download
if (isset($_GET['download'])) {
    $filename = basename($_GET['download']);
    $filepath = $backupPlugin->getBackupPath($filename);

    if ($filepath) {
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: no-cache, must-revalidate');
        readfile($filepath);
        exit;
    }
}

// Get data for display
$backups = $backupPlugin->listBackups();
$storageInfo = $backupPlugin->getStorageInfo();
$lastBackup = $backupPlugin->getSetting('last_backup');
?>

<style>
.backup-dashboard {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 24px;
}

@media (max-width: 1024px) {
    .backup-dashboard {
        grid-template-columns: 1fr;
    }
}

.backup-card {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.backup-card h3 {
    margin: 0 0 20px;
    font-size: 1.1rem;
    color: #333;
    display: flex;
    align-items: center;
    gap: 8px;
}

.backup-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

.stat-box {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 16px;
    text-align: center;
}

.stat-box .stat-value {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--admin-primary, #FF68C5);
}

.stat-box .stat-label {
    font-size: 0.85rem;
    color: #666;
    margin-top: 4px;
}

.backup-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.backup-btn-primary {
    background: var(--admin-primary, #FF68C5);
    color: #fff;
}

.backup-btn-primary:hover {
    background: var(--admin-primary-hover, #ff4db8);
    transform: translateY(-1px);
}

.backup-btn-primary:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
}

.backup-btn-secondary {
    background: #f0f0f0;
    color: #333;
}

.backup-btn-secondary:hover {
    background: #e0e0e0;
}

.backup-btn-danger {
    background: #fee2e2;
    color: #dc2626;
}

.backup-btn-danger:hover {
    background: #fecaca;
}

.backup-list {
    margin-top: 16px;
}

.backup-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 8px;
    background: #fafafa;
}

.backup-item:hover {
    background: #f0f0f0;
}

.backup-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.backup-name {
    font-weight: 500;
    color: #333;
    font-size: 0.95rem;
}

.backup-meta {
    font-size: 0.85rem;
    color: #666;
}

.backup-actions {
    display: flex;
    gap: 8px;
}

.backup-actions a,
.backup-actions button {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.85rem;
    text-decoration: none;
}

.settings-form .form-group {
    margin-bottom: 20px;
}

.settings-form label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    color: #333;
}

.settings-form input[type="number"],
.settings-form select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 0.95rem;
}

.settings-form .checkbox-group {
    display: flex;
    align-items: center;
    gap: 8px;
}

.settings-form .checkbox-group input {
    width: 18px;
    height: 18px;
}

.settings-form .help-text {
    font-size: 0.85rem;
    color: #666;
    margin-top: 4px;
}

.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.cron-info {
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 8px;
    padding: 16px;
    margin-top: 16px;
}

.cron-info h4 {
    margin: 0 0 8px;
    color: #856404;
    font-size: 0.95rem;
}

.cron-info code {
    display: block;
    background: #1e1e1e;
    color: #d4d4d4;
    padding: 12px;
    border-radius: 6px;
    font-size: 0.8rem;
    overflow-x: auto;
    margin-top: 8px;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.empty-state svg {
    width: 48px;
    height: 48px;
    margin-bottom: 12px;
    opacity: 0.5;
}

.loading-spinner {
    display: none;
    width: 20px;
    height: 20px;
    border: 2px solid #fff;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.backup-btn-primary.loading .loading-spinner {
    display: inline-block;
}

.backup-btn-primary.loading .btn-text {
    display: none;
}
</style>

<div class="admin-content">
    <div class="content-header">
        <h1>Backup Manager</h1>
        <p>Create and manage backups of your store's database, code, and settings.</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo escape($message); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo escape($error); ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="backup-stats">
        <div class="stat-box">
            <div class="stat-value"><?php echo $storageInfo['backup_count']; ?></div>
            <div class="stat-label">Total Backups</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?php echo $storageInfo['total_size_formatted']; ?></div>
            <div class="stat-label">Storage Used</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?php echo $lastBackup ? date('M j', strtotime($lastBackup)) : 'Never'; ?></div>
            <div class="stat-label">Last Backup</div>
        </div>
    </div>

    <div class="backup-dashboard">
        <!-- Main Content -->
        <div class="backup-main">
            <!-- Create Backup Card -->
            <div class="backup-card">
                <h3>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                    Create Backup
                </h3>
                <p style="color: #666; margin-bottom: 20px;">
                    Create a new backup of your database, code files, and configuration.
                    Images and uploaded files are excluded to save space.
                </p>
                <form method="POST" id="backupForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    <input type="hidden" name="action" value="create_backup">
                    <button type="submit" class="backup-btn backup-btn-primary" id="createBackupBtn">
                        <span class="btn-text">Create Backup Now</span>
                        <span class="loading-spinner"></span>
                    </button>
                </form>
            </div>

            <!-- Existing Backups Card -->
            <div class="backup-card" style="margin-top: 24px;">
                <h3>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                    </svg>
                    Existing Backups
                </h3>

                <?php if (empty($backups)): ?>
                    <div class="empty-state">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                            <line x1="9" y1="9" x2="15" y2="15"/>
                            <line x1="15" y1="9" x2="9" y2="15"/>
                        </svg>
                        <p>No backups yet. Create your first backup above.</p>
                    </div>
                <?php else: ?>
                    <div class="backup-list">
                        <?php foreach ($backups as $backup): ?>
                            <div class="backup-item">
                                <div class="backup-info">
                                    <span class="backup-name"><?php echo escape($backup['filename']); ?></span>
                                    <span class="backup-meta">
                                        <?php echo $backup['size_formatted']; ?> &bull;
                                        <?php echo $backup['created_date']; ?>
                                    </span>
                                </div>
                                <div class="backup-actions">
                                    <a href="?download=<?php echo urlencode($backup['filename']); ?>"
                                       class="backup-btn backup-btn-secondary">
                                        Download
                                    </a>
                                    <form method="POST" style="display: inline;"
                                          onsubmit="return confirm('Delete this backup? This cannot be undone.');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                                        <input type="hidden" name="action" value="delete_backup">
                                        <input type="hidden" name="filename" value="<?php echo escape($backup['filename']); ?>">
                                        <button type="submit" class="backup-btn backup-btn-danger">Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar - Settings -->
        <div class="backup-sidebar">
            <div class="backup-card">
                <h3>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                    Settings
                </h3>

                <form method="POST" class="settings-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    <input type="hidden" name="action" value="save_settings">

                    <div class="form-group">
                        <label>Keep Last N Backups</label>
                        <input type="number" name="retention_count" min="1" max="30"
                               value="<?php echo $backupPlugin->getSetting('retention_count', 5); ?>">
                        <div class="help-text">Older backups are automatically deleted</div>
                    </div>

                    <div class="form-group">
                        <label>Include in Backup</label>
                        <div class="checkbox-group">
                            <input type="checkbox" name="include_database" id="include_database"
                                   <?php echo $backupPlugin->getSetting('include_database', true) ? 'checked' : ''; ?>>
                            <label for="include_database">Database</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" name="include_code" id="include_code"
                                   <?php echo $backupPlugin->getSetting('include_code', true) ? 'checked' : ''; ?>>
                            <label for="include_code">Code Files</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" name="include_config" id="include_config"
                                   <?php echo $backupPlugin->getSetting('include_config', true) ? 'checked' : ''; ?>>
                            <label for="include_config">Configuration</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="auto_backup" id="auto_backup"
                                   <?php echo $backupPlugin->getSetting('auto_backup', false) ? 'checked' : ''; ?>>
                            <label for="auto_backup">Enable Scheduled Backups</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Backup Schedule</label>
                        <select name="backup_schedule">
                            <option value="hourly" <?php echo $backupPlugin->getSetting('backup_schedule') === 'hourly' ? 'selected' : ''; ?>>Hourly</option>
                            <option value="daily" <?php echo $backupPlugin->getSetting('backup_schedule') === 'daily' ? 'selected' : ''; ?>>Daily</option>
                            <option value="weekly" <?php echo $backupPlugin->getSetting('backup_schedule') === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                        </select>
                    </div>

                    <button type="submit" class="backup-btn backup-btn-primary" style="width: 100%;">
                        Save Settings
                    </button>
                </form>

                <div class="cron-info">
                    <h4>Cron Job Required for Scheduled Backups</h4>
                    <p style="font-size: 0.85rem; color: #856404; margin: 0;">Add this to your server's crontab:</p>
                    <code>*/30 * * * * php <?php echo dirname(dirname(dirname(__DIR__))); ?>/cron/backup.php</code>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('backupForm').addEventListener('submit', function() {
    const btn = document.getElementById('createBackupBtn');
    btn.classList.add('loading');
    btn.disabled = true;
});
</script>
