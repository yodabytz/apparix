<div class="page-header">
    <h1>Software Releases</h1>
    <div class="action-buttons">
        <a href="/admin/releases/logs" class="btn btn-outline">View Update Logs</a>
        <a href="/admin/releases/create" class="btn btn-primary">+ New Release</a>
    </div>
</div>

<!-- Stats Cards -->
<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem;">
    <div class="card" style="text-align: center; padding: 1.5rem;">
        <div style="font-size: 2rem; font-weight: 700; color: var(--admin-primary);">
            <?php echo number_format($stats['total_downloads'] ?? 0); ?>
        </div>
        <div style="color: var(--admin-text-light); font-size: 0.875rem;">Total Downloads</div>
    </div>
    <div class="card" style="text-align: center; padding: 1.5rem;">
        <div style="font-size: 2rem; font-weight: 700; color: var(--admin-primary);">
            <?php echo number_format($stats['unique_licenses'] ?? 0); ?>
        </div>
        <div style="color: var(--admin-text-light); font-size: 0.875rem;">Unique Licenses</div>
    </div>
    <div class="card" style="text-align: center; padding: 1.5rem;">
        <div style="font-size: 2rem; font-weight: 700; color: #22c55e;">
            <?php echo number_format($stats['successful_installs'] ?? 0); ?>
        </div>
        <div style="color: var(--admin-text-light); font-size: 0.875rem;">Successful Installs</div>
    </div>
    <div class="card" style="text-align: center; padding: 1.5rem;">
        <div style="font-size: 2rem; font-weight: 700; color: #ef4444;">
            <?php echo number_format($stats['failed_installs'] ?? 0); ?>
        </div>
        <div style="color: var(--admin-text-light); font-size: 0.875rem;">Failed Installs</div>
    </div>
</div>

<!-- Releases Table -->
<div class="card">
    <?php if (empty($releases)): ?>
        <div class="empty-state" style="padding: 3rem; text-align: center;">
            <p style="color: var(--admin-text-light); margin-bottom: 1rem;">No releases yet</p>
            <a href="/admin/releases/create" class="btn btn-primary">Create First Release</a>
        </div>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Version</th>
                    <th>Type</th>
                    <th>Min PHP</th>
                    <th>Min Edition</th>
                    <th>File Size</th>
                    <th>Downloads</th>
                    <th>Status</th>
                    <th>Released</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($releases as $release): ?>
                    <tr>
                        <td>
                            <strong style="font-family: monospace; font-size: 1rem;">v<?php echo escape($release['version']); ?></strong>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo $release['release_type']; ?>">
                                <?php echo ucfirst($release['release_type']); ?>
                            </span>
                        </td>
                        <td><?php echo escape($release['min_php_version']); ?></td>
                        <td>
                            <?php
                            $editions = ['S' => 'Standard', 'P' => 'Pro', 'E' => 'Enterprise', 'D' => 'Dev', 'U' => 'Unlimited'];
                            echo $editions[$release['min_edition']] ?? $release['min_edition'];
                            ?>
                        </td>
                        <td><?php echo formatBytes($release['file_size']); ?></td>
                        <td><?php echo number_format($release['download_count']); ?></td>
                        <td>
                            <?php if ($release['is_active']): ?>
                                <span class="status-badge status-active">Active</span>
                            <?php else: ?>
                                <span class="status-badge status-inactive">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($release['released_at'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="/admin/releases/<?php echo $release['id']; ?>/edit" class="btn btn-sm btn-outline">Edit</a>
                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteRelease(<?php echo $release['id']; ?>, '<?php echo escape($release['version']); ?>')">Delete</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<style>
.status-stable { background: #dcfce7; color: #166534; }
.status-beta { background: #fef3c7; color: #92400e; }
.status-alpha { background: #fee2e2; color: #991b1b; }
.status-active { background: #dcfce7; color: #166534; }
.status-inactive { background: #f3f4f6; color: #6b7280; }
</style>

<script>
function deleteRelease(id, version) {
    if (!confirm('Are you sure you want to delete release v' + version + '? This will also delete the update file.')) {
        return;
    }

    fetch('/admin/releases/delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: '_csrf_token=<?php echo csrf_token(); ?>&id=' + id
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to delete release');
        }
    });
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
