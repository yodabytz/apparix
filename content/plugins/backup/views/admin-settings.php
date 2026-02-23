<?php
/**
 * Backup Plugin Admin Settings View (v2.0)
 *
 * Pure template — receives $backupPlugin, $viewMessage, $viewError from getSettingsView() output buffer.
 * No action handling here; all logic is in BackupPlugin::getSettingsView().
 */

/** @var \Plugins\Backup\BackupPlugin $backupPlugin */

$backupPlugin->initialize();

// Gather display data
$backups = $backupPlugin->listBackups();
$storageInfo = $backupPlugin->getStorageInfo();
$lastBackup = $backupPlugin->getSetting('last_backup');
$effectiveTier = $backupPlugin->getEffectiveTier();
$availableTier = $backupPlugin->getAvailableTier();
$tiers = $backupPlugin->getTierConfig();
$notifications = $backupPlugin->getNotifications();
$b2Configured = $backupPlugin->isB2Configured();
$cloudBackups = $b2Configured ? $backupPlugin->listCloudBackups() : [];
$cloudInfo = $b2Configured ? $backupPlugin->getCloudStorageInfo() : null;
$tierEstimate = $backupPlugin->estimateTierSize($effectiveTier);
$csrfToken = $_SESSION['csrf_token'] ?? '';

// We need to close the wrapping form/card from settings.php since we provide our own complete UI
?>
</form></div>

<style>
.bk-wrap { max-width: 1200px; }
.bk-notifications { margin-bottom: 20px; }
.bk-notification {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 8px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
}
.bk-notification.warning { background: #fef3cd; border: 1px solid #ffc107; color: #856404; }
.bk-notification.error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
.bk-notification .bk-n-icon { flex-shrink: 0; font-size: 1.1rem; }
.bk-notification .bk-n-title { font-weight: 600; }
.bk-notification .bk-n-msg { font-size: 0.9rem; margin-top: 2px; }

.bk-alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.bk-alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.bk-alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

.bk-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
@media (max-width: 768px) { .bk-stats { grid-template-columns: repeat(2, 1fr); } }
.bk-stat {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 18px;
    text-align: center;
}
.bk-stat-value { font-size: 1.4rem; font-weight: 600; color: var(--admin-primary, #FF68C5); }
.bk-stat-label { font-size: 0.85rem; color: #666; margin-top: 4px; }

.bk-grid {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
}
@media (max-width: 1024px) { .bk-grid { grid-template-columns: 1fr; } }

.bk-card {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 24px;
}
.bk-card h3 {
    margin: 0 0 16px;
    font-size: 1.05rem;
    color: #333;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Tier cards */
.bk-tiers { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px; }
@media (max-width: 640px) { .bk-tiers { grid-template-columns: 1fr; } }
.bk-tier {
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    padding: 16px;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
}
.bk-tier:hover { border-color: #d1d5db; }
.bk-tier.selected { border-color: var(--admin-primary, #FF68C5); background: #fff5fa; }
.bk-tier.locked { opacity: 0.6; cursor: not-allowed; }
.bk-tier .bk-tier-name { font-weight: 600; font-size: 1rem; color: #333; }
.bk-tier .bk-tier-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 500;
    margin-left: 6px;
}
.bk-tier .badge-free { background: #d4edda; color: #155724; }
.bk-tier .badge-pro { background: #cce5ff; color: #004085; }
.bk-tier .badge-ent { background: #e2d9f3; color: #4a2884; }
.bk-tier .bk-tier-items { list-style: none; padding: 0; margin: 10px 0 0; font-size: 0.85rem; color: #555; }
.bk-tier .bk-tier-items li { padding: 2px 0; }
.bk-tier .bk-tier-items li::before { content: "\2713 "; color: #28a745; margin-right: 4px; }
.bk-tier .bk-tier-est {
    margin-top: 10px;
    font-size: 0.8rem;
    color: #888;
    font-style: italic;
}
.bk-tier .bk-tier-lock {
    display: block;
    margin-top: 10px;
    font-size: 0.8rem;
    color: var(--admin-primary, #FF68C5);
    text-decoration: none;
}
.bk-tier .bk-tier-lock:hover { text-decoration: underline; }
.bk-tier input[type="radio"] { display: none; }

/* Buttons */
.bk-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}
.bk-btn-primary { background: var(--admin-primary, #FF68C5); color: #fff; }
.bk-btn-primary:hover { background: var(--admin-primary-hover, #ff4db8); transform: translateY(-1px); }
.bk-btn-primary:disabled { background: #ccc; cursor: not-allowed; transform: none; }
.bk-btn-sm { padding: 6px 12px; font-size: 0.85rem; }
.bk-btn-secondary { background: #f0f0f0; color: #333; }
.bk-btn-secondary:hover { background: #e0e0e0; }
.bk-btn-danger { background: #fee2e2; color: #dc2626; }
.bk-btn-danger:hover { background: #fecaca; }
.bk-btn-outline { background: transparent; border: 1px solid #ddd; color: #555; }
.bk-btn-outline:hover { background: #f5f5f5; }

/* Backup list */
.bk-list-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 14px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 8px;
    background: #fafafa;
}
.bk-list-item:hover { background: #f0f0f0; }
.bk-list-info { display: flex; flex-direction: column; gap: 2px; }
.bk-list-name { font-weight: 500; color: #333; font-size: 0.9rem; }
.bk-list-meta { font-size: 0.82rem; color: #666; }
.bk-list-actions { display: flex; gap: 6px; flex-wrap: wrap; }

/* Cloud usage bar */
.bk-usage-bar { background: #e5e7eb; border-radius: 6px; height: 10px; margin: 8px 0 12px; overflow: hidden; }
.bk-usage-fill { height: 100%; border-radius: 6px; background: var(--admin-primary, #FF68C5); transition: width 0.3s; }

/* Sidebar forms */
.bk-form-group { margin-bottom: 16px; }
.bk-form-group label { display: block; margin-bottom: 4px; font-weight: 500; color: #333; font-size: 0.9rem; }
.bk-form-group input[type="text"],
.bk-form-group input[type="password"],
.bk-form-group input[type="number"],
.bk-form-group select {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 0.9rem;
    box-sizing: border-box;
}
.bk-form-group .bk-help { font-size: 0.8rem; color: #888; margin-top: 3px; }
.bk-checkbox { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
.bk-checkbox input { width: 16px; height: 16px; }
.bk-checkbox label { font-weight: 400; margin-bottom: 0; }

.bk-empty { text-align: center; padding: 30px 20px; color: #999; font-size: 0.9rem; }

.bk-cron-info {
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 8px;
    padding: 14px;
    margin-top: 16px;
}
.bk-cron-info h4 { margin: 0 0 6px; color: #856404; font-size: 0.9rem; }
.bk-cron-info code {
    display: block;
    background: #1e1e1e;
    color: #d4d4d4;
    padding: 10px;
    border-radius: 6px;
    font-size: 0.78rem;
    overflow-x: auto;
    margin-top: 6px;
}

.bk-b2-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: #e63946;
    color: #fff;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 500;
    margin-bottom: 14px;
}
.bk-b2-link:hover { background: #c1121f; color: #fff; }
.bk-b2-info {
    background: #f0f7ff;
    border: 1px solid #b8daff;
    border-radius: 6px;
    padding: 10px;
    font-size: 0.82rem;
    color: #004085;
    margin-top: 12px;
}

/* Loading spinner */
.bk-spinner {
    display: none;
    width: 18px; height: 18px;
    border: 2px solid #fff;
    border-top-color: transparent;
    border-radius: 50%;
    animation: bk-spin 0.8s linear infinite;
}
@keyframes bk-spin { to { transform: rotate(360deg); } }
.bk-btn-primary.loading .bk-spinner { display: inline-block; }
.bk-btn-primary.loading .bk-btn-text { display: none; }

.bk-create-meta {
    margin-bottom: 14px;
    font-size: 0.9rem;
    color: #555;
}
.bk-create-meta strong { color: #333; }
</style>

<!-- Close the wrapping card from settings.php and provide our own full UI -->
<div class="bk-wrap">
    <div class="content-header">
        <h1>Backup Manager</h1>
        <p>Create and manage backups of your store's data with local and cloud storage.</p>
    </div>

    <!-- Notifications -->
    <?php if (!empty($notifications)): ?>
    <div class="bk-notifications">
        <?php foreach ($notifications as $n): ?>
        <div class="bk-notification <?php echo $n['type']; ?>">
            <span class="bk-n-icon"><?php echo $n['type'] === 'error' ? '&#9888;' : '&#9888;'; ?></span>
            <div>
                <div class="bk-n-title"><?php echo htmlspecialchars($n['title']); ?></div>
                <div class="bk-n-msg"><?php echo htmlspecialchars($n['message']); ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Alert messages -->
    <?php if ($viewMessage): ?>
        <div class="bk-alert bk-alert-success"><?php echo htmlspecialchars($viewMessage); ?></div>
    <?php endif; ?>
    <?php if ($viewError): ?>
        <div class="bk-alert bk-alert-error"><?php echo htmlspecialchars($viewError); ?></div>
    <?php endif; ?>

    <!-- Stats Row -->
    <div class="bk-stats">
        <div class="bk-stat">
            <div class="bk-stat-value"><?php echo $storageInfo['backup_count']; ?></div>
            <div class="bk-stat-label">Local Backups</div>
        </div>
        <div class="bk-stat">
            <div class="bk-stat-value"><?php echo $storageInfo['total_size_formatted']; ?></div>
            <div class="bk-stat-label">Local Storage</div>
        </div>
        <div class="bk-stat">
            <div class="bk-stat-value"><?php echo $cloudInfo ? $cloudInfo['total_size_formatted'] : '--'; ?></div>
            <div class="bk-stat-label">Cloud Storage</div>
        </div>
        <div class="bk-stat">
            <div class="bk-stat-value"><?php echo $lastBackup ? date('M j', strtotime($lastBackup)) : 'Never'; ?></div>
            <div class="bk-stat-label">Last Backup</div>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="bk-grid">
        <!-- Main Column -->
        <div class="bk-main">

            <!-- Tier Selector -->
            <div class="bk-card">
                <h3>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                    Backup Tier
                </h3>

                <div class="bk-tiers" id="bkTiers">
                    <?php foreach ($tiers as $tierKey => $tier):
                        $isAvailable = $backupPlugin->isTierAvailable($tierKey);
                        $isSelected = ($effectiveTier === $tierKey);
                        $estimate = $backupPlugin->estimateTierSize($tierKey);
                        $badgeClass = $tierKey === 'essential' ? 'badge-free' : ($tierKey === 'standard' ? 'badge-pro' : 'badge-ent');
                        $badgeText = $tierKey === 'essential' ? 'Free' : ($tierKey === 'standard' ? 'Pro+' : 'Enterprise+');
                    ?>
                    <label class="bk-tier <?php echo $isSelected ? 'selected' : ''; ?> <?php echo !$isAvailable ? 'locked' : ''; ?>"
                           data-tier="<?php echo $tierKey; ?>">
                        <input type="radio" name="tier_preview" value="<?php echo $tierKey; ?>"
                               <?php echo $isSelected ? 'checked' : ''; ?>
                               <?php echo !$isAvailable ? 'disabled' : ''; ?>
                               onchange="selectTier(this.value)">
                        <div class="bk-tier-name">
                            <?php echo $tier['label']; ?>
                            <span class="bk-tier-badge <?php echo $badgeClass; ?>"><?php echo $badgeText; ?></span>
                        </div>
                        <ul class="bk-tier-items">
                            <?php
                            $itemLabels = [
                                'database' => 'Database (all tables)',
                                'env_encrypted' => '.env (AES-256 encrypted)',
                                'themes_plugins' => 'Themes & plugins',
                                'product_images' => 'Product images',
                                'uploads' => 'User uploads',
                                'code' => 'Full application code',
                                'config_exports' => 'Config exports (JSON)',
                            ];
                            foreach ($tier['components'] as $comp):
                            ?>
                            <li><?php echo $itemLabels[$comp] ?? $comp; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="bk-tier-est">~<?php echo $estimate['compressed_formatted']; ?> compressed</div>
                        <?php if (!$isAvailable): ?>
                        <a href="<?php echo \App\Core\License::getUpgradeUrl(); ?>" class="bk-tier-lock" target="_blank">Upgrade to unlock</a>
                        <?php endif; ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Create Backup -->
            <div class="bk-card">
                <h3>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    Create Backup
                </h3>
                <div class="bk-create-meta">
                    Tier: <strong><?php echo ucfirst($effectiveTier); ?></strong> &mdash;
                    Estimated: <strong>~<?php echo $tierEstimate['compressed_formatted']; ?></strong>
                </div>
                <form method="POST" id="bkCreateForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="action" value="create_backup">
                    <?php if ($b2Configured): ?>
                    <div class="bk-checkbox" style="margin-bottom: 14px;">
                        <input type="checkbox" name="upload_to_cloud" id="bk_upload_cloud" checked>
                        <label for="bk_upload_cloud">Upload to Backblaze B2 after creation</label>
                    </div>
                    <?php endif; ?>
                    <button type="submit" class="bk-btn bk-btn-primary" id="bkCreateBtn">
                        <span class="bk-btn-text">Create Backup Now</span>
                        <span class="bk-spinner"></span>
                    </button>
                </form>
            </div>

            <!-- Local Backups -->
            <div class="bk-card">
                <h3>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                    Local Backups
                </h3>
                <?php if (empty($backups)): ?>
                    <div class="bk-empty">No local backups yet. Create your first backup above.</div>
                <?php else: ?>
                    <?php foreach ($backups as $backup): ?>
                    <div class="bk-list-item">
                        <div class="bk-list-info">
                            <span class="bk-list-name"><?php echo htmlspecialchars($backup['filename']); ?></span>
                            <span class="bk-list-meta"><?php echo $backup['size_formatted']; ?> &bull; <?php echo $backup['created_date']; ?></span>
                        </div>
                        <div class="bk-list-actions">
                            <a href="?slug=backup&download=<?php echo urlencode($backup['filename']); ?>" class="bk-btn bk-btn-sm bk-btn-secondary">Download</a>
                            <?php if ($b2Configured): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="upload_to_cloud">
                                <input type="hidden" name="filename" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                <button type="submit" class="bk-btn bk-btn-sm bk-btn-outline" title="Upload to B2">Cloud</button>
                            </form>
                            <?php endif; ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this backup?');">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="delete_backup">
                                <input type="hidden" name="filename" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                <button type="submit" class="bk-btn bk-btn-sm bk-btn-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Cloud Backups -->
            <?php if ($b2Configured): ?>
            <div class="bk-card">
                <h3>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/></svg>
                    Cloud Backups (Backblaze B2)
                </h3>
                <?php if ($cloudInfo): ?>
                <div style="font-size: 0.85rem; color: #555; margin-bottom: 4px;">
                    <?php echo $cloudInfo['total_size_formatted']; ?> / 10 GB free tier
                </div>
                <div class="bk-usage-bar">
                    <div class="bk-usage-fill" style="width: <?php echo min(100, $cloudInfo['usage_percent']); ?>%"></div>
                </div>
                <?php endif; ?>

                <?php if (empty($cloudBackups)): ?>
                    <div class="bk-empty">No cloud backups yet. Upload a backup or enable auto-upload.</div>
                <?php else: ?>
                    <?php foreach ($cloudBackups as $cb): ?>
                    <div class="bk-list-item">
                        <div class="bk-list-info">
                            <span class="bk-list-name"><?php echo htmlspecialchars($cb['filename']); ?></span>
                            <span class="bk-list-meta"><?php echo $cb['size_formatted']; ?> &bull; <?php echo $cb['date']; ?></span>
                        </div>
                        <div class="bk-list-actions">
                            <a href="?slug=backup&download_cloud=<?php echo urlencode($cb['filename']); ?>" class="bk-btn bk-btn-sm bk-btn-secondary">Download</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this cloud backup?');">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="delete_cloud_backup">
                                <input type="hidden" name="cloud_key" value="<?php echo htmlspecialchars($cb['key']); ?>">
                                <button type="submit" class="bk-btn bk-btn-sm bk-btn-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </div>

        <!-- Sidebar -->
        <div class="bk-sidebar">

            <!-- Settings -->
            <div class="bk-card">
                <h3>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    Settings
                </h3>
                <form method="POST" id="bkSettingsForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="action" value="save_settings">
                    <input type="hidden" name="backup_tier" id="bkTierInput" value="<?php echo htmlspecialchars($effectiveTier); ?>">

                    <div class="bk-form-group">
                        <label>Keep Last N Backups</label>
                        <input type="number" name="retention_count" min="1" max="30"
                               value="<?php echo (int)$backupPlugin->getSetting('retention_count', 5); ?>">
                        <div class="bk-help">Older local backups are automatically deleted</div>
                    </div>

                    <div class="bk-form-group">
                        <label>Backup Schedule</label>
                        <select name="backup_schedule">
                            <option value="hourly" <?php echo $backupPlugin->getSetting('backup_schedule') === 'hourly' ? 'selected' : ''; ?>>Hourly</option>
                            <option value="daily" <?php echo $backupPlugin->getSetting('backup_schedule') === 'daily' ? 'selected' : ''; ?>>Daily</option>
                            <option value="weekly" <?php echo $backupPlugin->getSetting('backup_schedule') === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                        </select>
                    </div>

                    <div class="bk-checkbox">
                        <input type="checkbox" name="auto_backup" id="bk_auto"
                               <?php echo $backupPlugin->getSetting('auto_backup') ? 'checked' : ''; ?>>
                        <label for="bk_auto">Enable scheduled backups</label>
                    </div>

                    <div class="bk-form-group">
                        <label>Stale Backup Warning (days)</label>
                        <input type="number" name="notification_stale_days" min="1" max="90"
                               value="<?php echo (int)$backupPlugin->getSetting('notification_stale_days', 7); ?>">
                        <div class="bk-help">Warn if no backup created within this period</div>
                    </div>

                    <button type="submit" class="bk-btn bk-btn-primary" style="width:100%;">Save Settings</button>
                </form>

                <div class="bk-cron-info">
                    <h4>Cron Job for Scheduled Backups</h4>
                    <p style="font-size:0.82rem; color:#856404; margin:0;">Add to your server's crontab:</p>
                    <code>*/30 * * * * php <?php echo dirname(dirname(dirname(dirname(__DIR__)))); ?>/cron/backup.php</code>
                </div>
            </div>

            <!-- Backblaze B2 -->
            <div class="bk-card">
                <h3>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/></svg>
                    Backblaze B2 Cloud Storage
                </h3>

                <a href="https://www.backblaze.com/cloud-storage" target="_blank" class="bk-b2-link">
                    Sign Up Free &rarr;
                </a>

                <form method="POST" id="bkB2Form">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="action" value="save_settings">
                    <!-- Include all other settings so they're preserved -->
                    <input type="hidden" name="backup_tier" value="<?php echo htmlspecialchars($effectiveTier); ?>">
                    <input type="hidden" name="retention_count" value="<?php echo (int)$backupPlugin->getSetting('retention_count', 5); ?>">
                    <input type="hidden" name="backup_schedule" value="<?php echo htmlspecialchars($backupPlugin->getSetting('backup_schedule', 'daily')); ?>">
                    <input type="hidden" name="notification_stale_days" value="<?php echo (int)$backupPlugin->getSetting('notification_stale_days', 7); ?>">
                    <?php if ($backupPlugin->getSetting('auto_backup')): ?>
                    <input type="hidden" name="auto_backup" value="1">
                    <?php endif; ?>

                    <div class="bk-checkbox">
                        <input type="checkbox" name="b2_enabled" id="bk_b2_enabled"
                               <?php echo $backupPlugin->getSetting('b2_enabled') ? 'checked' : ''; ?>>
                        <label for="bk_b2_enabled">Enable B2 Cloud Storage</label>
                    </div>

                    <div class="bk-form-group">
                        <label>Application Key ID</label>
                        <input type="text" name="b2_key_id" value="<?php echo htmlspecialchars($backupPlugin->getSetting('b2_key_id', '')); ?>"
                               placeholder="e.g. 004a1b2c3d4e5f0000000001">
                    </div>

                    <div class="bk-form-group">
                        <label>Application Key</label>
                        <input type="password" name="b2_application_key" value="<?php echo htmlspecialchars($backupPlugin->getSetting('b2_application_key', '')); ?>"
                               placeholder="Your B2 application key">
                    </div>

                    <div class="bk-form-group">
                        <label>Bucket Name</label>
                        <input type="text" name="b2_bucket_name" value="<?php echo htmlspecialchars($backupPlugin->getSetting('b2_bucket_name', '')); ?>"
                               placeholder="e.g. my-apparix-backups">
                    </div>

                    <div class="bk-form-group">
                        <label>S3 Endpoint</label>
                        <input type="text" name="b2_endpoint" value="<?php echo htmlspecialchars($backupPlugin->getSetting('b2_endpoint', '')); ?>"
                               placeholder="e.g. s3.us-west-004.backblazeb2.com">
                        <div class="bk-help">Found in B2 bucket details under "S3 Compatible URL"</div>
                    </div>

                    <div class="bk-checkbox">
                        <input type="checkbox" name="b2_auto_upload" id="bk_b2_auto"
                               <?php echo $backupPlugin->getSetting('b2_auto_upload') ? 'checked' : ''; ?>>
                        <label for="bk_b2_auto">Auto-upload after backup</label>
                    </div>

                    <div class="bk-form-group">
                        <label>Cloud Retention (backups)</label>
                        <input type="number" name="b2_retention_count" min="1" max="100"
                               value="<?php echo (int)$backupPlugin->getSetting('b2_retention_count', 10); ?>">
                    </div>

                    <div style="display:flex; gap:8px; margin-top:12px;">
                        <button type="button" class="bk-btn bk-btn-outline" style="flex:1;" onclick="testB2Connection()">Test Connection</button>
                        <button type="submit" class="bk-btn bk-btn-primary" style="flex:1;">Save</button>
                    </div>

                    <div class="bk-b2-info">
                        <strong>Backblaze B2 Free Tier:</strong> 10 GB storage, 1 GB/day downloads, unlimited uploads. No credit card required to start.
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function selectTier(tier) {
    document.getElementById('bkTierInput').value = tier;
    document.querySelectorAll('.bk-tier').forEach(el => {
        el.classList.toggle('selected', el.dataset.tier === tier);
    });
}

document.getElementById('bkCreateForm').addEventListener('submit', function() {
    var btn = document.getElementById('bkCreateBtn');
    btn.classList.add('loading');
    btn.disabled = true;
});

function testB2Connection() {
    // Save settings first, then test
    var form = document.getElementById('bkB2Form');
    var data = new FormData(form);
    data.set('action', 'test_b2');
    fetch(window.location.href, { method: 'POST', body: data })
        .then(function() { window.location.reload(); });
}
</script>

<?php // Open hidden elements to absorb the remaining closing tags from settings.php ?>
<div style="display:none"><form>
