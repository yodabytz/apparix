<div class="page-header">
    <h1>Software Updates</h1>
</div>

<!-- Current Version Info -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h3 style="margin: 0 0 0.5rem;"><?php echo escape($versionInfo['product'] ?? 'Apparix'); ?></h3>
            <div style="font-size: 2rem; font-weight: 700; color: var(--admin-primary);">
                v<?php echo escape($versionInfo['version'] ?? '1.0.0'); ?>
            </div>
            <div style="color: var(--admin-text-light); font-size: 0.875rem; margin-top: 0.25rem;">
                Released: <?php echo escape($versionInfo['release_date'] ?? 'Unknown'); ?>
            </div>
        </div>
        <div>
            <button type="button" class="btn btn-primary" onclick="checkForUpdates()" id="checkBtn">
                Check for Updates
            </button>
        </div>
    </div>
</div>

<!-- Update Status Area -->
<div id="updateStatus" class="card" style="display: none; margin-bottom: 1.5rem;">
    <span id="updateStatusText"></span>
</div>

<!-- Update Available Area -->
<div id="updateAvailable" style="display: none; margin-bottom: 1.5rem;">
    <div class="card" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 1px solid #93c5fd;">
        <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h3 style="margin: 0 0 0.5rem; color: #1e40af;">
                    <span style="font-size: 1.25rem;">&#127881;</span> Update Available!
                </h3>
                <div style="font-size: 1.5rem; font-weight: 700; color: #1e40af;">
                    v<span id="newVersion"></span>
                </div>
                <div style="color: #1d4ed8; font-size: 0.875rem; margin-top: 0.5rem;">
                    <span id="updateReleaseType"></span> &bull; <span id="updateFileSize"></span>
                </div>
            </div>
            <div>
                <button type="button" class="btn btn-primary" onclick="installUpdate()" id="installBtn">
                    Install Update
                </button>
            </div>
        </div>

        <div id="releaseNotes" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #bfdbfe;">
            <h4 style="margin: 0 0 0.5rem; color: #1e40af;">Release Notes</h4>
            <p id="releaseNotesContent" style="color: #1d4ed8; margin: 0;"></p>
        </div>

        <div id="changelog" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #bfdbfe; display: none;">
            <h4 style="margin: 0 0 0.5rem; color: #1e40af;">Changelog</h4>
            <pre id="changelogContent" style="background: rgba(255,255,255,0.5); padding: 1rem; border-radius: 6px; font-size: 0.875rem; overflow-x: auto; margin: 0; white-space: pre-wrap;"></pre>
        </div>
    </div>
</div>

<!-- Up to Date Message -->
<div id="upToDate" style="display: none; margin-bottom: 1.5rem;">
    <div class="card" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1px solid #86efac; text-align: center; padding: 2rem;">
        <div style="font-size: 3rem; margin-bottom: 0.5rem;">&#9989;</div>
        <h3 style="margin: 0 0 0.5rem; color: #166534;">You're up to date!</h3>
        <p style="color: #15803d; margin: 0;">You're running the latest version of Apparix.</p>
    </div>
</div>

<!-- Installation Progress -->
<div id="installProgress" style="display: none; margin-bottom: 1.5rem;">
    <div class="card">
        <h3 style="margin: 0 0 1rem;">Installing Update...</h3>
        <div style="background: #e5e7eb; border-radius: 9999px; height: 8px; overflow: hidden; margin-bottom: 1rem;">
            <div id="progressBar" style="background: var(--admin-primary); height: 100%; width: 0%; transition: width 0.3s ease;"></div>
        </div>
        <p id="progressText" style="color: var(--admin-text-light); margin: 0; text-align: center;">Preparing update...</p>
    </div>
</div>

<!-- Backups Section -->
<?php if (!empty($backups)): ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Backups</h3>
        <button type="button" class="btn btn-sm btn-outline" onclick="cleanupBackups()">
            Cleanup Old Backups
        </button>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Backup</th>
                <th>Size</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($backups as $backup): ?>
            <tr>
                <td><code><?php echo escape($backup['filename']); ?></code></td>
                <td><?php echo formatBytes($backup['size']); ?></td>
                <td><?php echo date('M j, Y g:i A', $backup['created']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<script>
const csrfToken = '<?php echo csrf_token(); ?>';
let pendingVersion = null;

function checkForUpdates() {
    const btn = document.getElementById('checkBtn');
    btn.disabled = true;
    btn.textContent = 'Checking...';

    hideAllStatus();
    showStatus('Checking for updates...', 'info');

    fetch('/admin/updates/check', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(r => r.json())
    .then(data => {
        hideAllStatus();

        if (!data.success) {
            showStatus(data.error || 'Failed to check for updates', 'error');
            return;
        }

        if (data.update_available) {
            pendingVersion = data.update.version;
            document.getElementById('newVersion').textContent = data.update.version;
            document.getElementById('updateReleaseType').textContent = ucfirst(data.update.release_type);
            document.getElementById('updateFileSize').textContent = data.update.file_size_formatted;
            document.getElementById('releaseNotesContent').textContent = data.update.release_notes || 'No release notes available.';

            if (data.update.changelog) {
                document.getElementById('changelogContent').textContent = data.update.changelog;
                document.getElementById('changelog').style.display = 'block';
            }

            document.getElementById('updateAvailable').style.display = 'block';
        } else {
            document.getElementById('upToDate').style.display = 'block';
        }
    })
    .catch(err => {
        showStatus('Error: ' + err.message, 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = 'Check for Updates';
    });
}

function installUpdate() {
    if (!pendingVersion) {
        showStatus('No version selected', 'error');
        return;
    }

    if (!confirm('Are you sure you want to install version ' + pendingVersion + '? A backup will be created automatically.')) {
        return;
    }

    hideAllStatus();
    document.getElementById('updateAvailable').style.display = 'none';
    document.getElementById('installProgress').style.display = 'block';

    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');

    // Simulate progress stages
    const stages = [
        { progress: 10, text: 'Creating backup...' },
        { progress: 25, text: 'Downloading update...' },
        { progress: 50, text: 'Extracting files...' },
        { progress: 75, text: 'Applying update...' },
        { progress: 90, text: 'Running migrations...' },
        { progress: 95, text: 'Cleaning up...' }
    ];

    let stageIndex = 0;
    const stageInterval = setInterval(() => {
        if (stageIndex < stages.length) {
            progressBar.style.width = stages[stageIndex].progress + '%';
            progressText.textContent = stages[stageIndex].text;
            stageIndex++;
        }
    }, 2000);

    fetch('/admin/updates/install', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: '_csrf_token=' + encodeURIComponent(csrfToken) + '&version=' + encodeURIComponent(pendingVersion)
    })
    .then(r => r.json())
    .then(data => {
        clearInterval(stageInterval);

        if (data.success) {
            progressBar.style.width = '100%';
            progressText.textContent = 'Update complete!';

            setTimeout(() => {
                document.getElementById('installProgress').style.display = 'none';
                showStatus('Successfully updated to v' + data.version + '! Reloading page...', 'success');

                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }, 1000);
        } else {
            document.getElementById('installProgress').style.display = 'none';
            showStatus('Update failed: ' + (data.error || 'Unknown error'), 'error');
        }
    })
    .catch(err => {
        clearInterval(stageInterval);
        document.getElementById('installProgress').style.display = 'none';
        showStatus('Error: ' + err.message, 'error');
    });
}

function cleanupBackups() {
    if (!confirm('Delete all but the last 5 backups?')) {
        return;
    }

    fetch('/admin/updates/cleanup-backups', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: '_csrf_token=' + encodeURIComponent(csrfToken) + '&keep=5'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showStatus(data.message, 'success');
            if (data.deleted > 0) {
                setTimeout(() => location.reload(), 1500);
            }
        } else {
            showStatus(data.error || 'Failed to cleanup backups', 'error');
        }
    });
}

function showStatus(message, type) {
    const statusEl = document.getElementById('updateStatus');
    const textEl = document.getElementById('updateStatusText');
    const bgColors = {
        'info': '#eff6ff',
        'success': '#f0fdf4',
        'error': '#fef2f2'
    };
    const textColors = {
        'info': '#1e40af',
        'success': '#166534',
        'error': '#991b1b'
    };
    const borderColors = {
        'info': '#93c5fd',
        'success': '#86efac',
        'error': '#fecaca'
    };

    statusEl.style.background = bgColors[type];
    statusEl.style.color = textColors[type];
    statusEl.style.border = '1px solid ' + borderColors[type];
    textEl.textContent = message;
    statusEl.style.display = 'block';
}

function hideAllStatus() {
    document.getElementById('updateStatus').style.display = 'none';
    document.getElementById('updateAvailable').style.display = 'none';
    document.getElementById('upToDate').style.display = 'none';
}

function ucfirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}
</script>

<?php
function formatBytes($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}
?>
