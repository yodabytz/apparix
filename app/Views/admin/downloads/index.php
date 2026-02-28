<div class="admin-header">
    <h1>Downloads</h1>
</div>

<!-- Stats Summary -->
<div class="stats-grid">
    <div class="stat-card primary">
        <span class="stat-label">Total Downloads</span>
        <span class="stat-value"><?php echo number_format($stats['total_downloads']); ?></span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Unique IPs</span>
        <span class="stat-value"><?php echo number_format($stats['unique_ips']); ?></span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Active Download Links</span>
        <span class="stat-value"><?php echo number_format($stats['active_links']); ?></span>
    </div>
</div>

<?php if (!empty($stats['top_products'])): ?>
<!-- Top Products -->
<div class="admin-card" style="margin-bottom: 1.5rem;">
    <h3 style="margin: 0 0 1rem; font-size: 1rem; color: var(--admin-text);">Top Downloaded Products</h3>
    <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
        <?php foreach ($stats['top_products'] as $tp): ?>
        <div style="background: var(--admin-bg); border: 1px solid var(--admin-border); border-radius: 8px; padding: 0.6rem 1rem; display: flex; align-items: center; gap: 0.5rem;">
            <span style="font-weight: 600; color: var(--admin-primary);"><?php echo number_format($tp['downloads']); ?></span>
            <span style="color: var(--admin-text-light); font-size: 0.875rem;"><?php echo escape($tp['name']); ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Download Log -->
<div class="admin-card">
    <h3 style="margin: 0 0 1rem; font-size: 1rem; color: var(--admin-text);">Download Log</h3>

    <?php if (empty($logs)): ?>
    <div style="text-align: center; padding: 3rem 1rem; color: var(--admin-text-light);">
        <div style="font-size: 2.5rem; margin-bottom: 0.75rem;">&#128230;</div>
        <p style="margin: 0; font-size: 1rem;">No downloads recorded yet.</p>
        <p style="margin: 0.5rem 0 0; font-size: 0.875rem;">Downloads will appear here once customers purchase and download digital products.</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Product</th>
                    <th>Customer</th>
                    <th>IP Address</th>
                    <th>Downloads</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td>
                        <span style="white-space: nowrap;"><?php echo date('M j, Y', strtotime($log['downloaded_at'])); ?></span>
                        <br><small style="color: var(--admin-text-light);"><?php echo date('g:i A', strtotime($log['downloaded_at'])); ?></small>
                    </td>
                    <td><?php echo escape($log['product_name'] ?? 'Unknown'); ?></td>
                    <td>
                        <?php if ($log['customer_email']): ?>
                            <span style="font-size: 0.875rem;"><?php echo escape($log['customer_email']); ?></span>
                            <?php if ($log['order_number']): ?>
                            <br><small style="color: var(--admin-text-light);">#<?php echo escape($log['order_number']); ?></small>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color: var(--admin-text-light); font-size: 0.875rem;">—</span>
                        <?php endif; ?>
                    </td>
                    <td><code style="font-size: 0.8rem;"><?php echo escape($log['ip_address']); ?></code></td>
                    <td>
                        <?php if ($log['max_downloads'] !== null): ?>
                            <?php echo (int)$log['download_count']; ?> / <?php echo (int)$log['max_downloads']; ?>
                        <?php elseif ($log['download_count'] !== null): ?>
                            <?php echo (int)$log['download_count']; ?>
                        <?php else: ?>
                            <span style="color: var(--admin-text-light);">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $isExpired = $log['expires_at'] && strtotime($log['expires_at']) < time();
                        $isMaxed = $log['max_downloads'] !== null && (int)$log['download_count'] >= (int)$log['max_downloads'];
                        ?>
                        <?php if ($isExpired): ?>
                            <span class="badge" style="background: #ef4444; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem;">Expired</span>
                        <?php elseif ($isMaxed): ?>
                            <span class="badge" style="background: #f59e0b; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem;">Limit Reached</span>
                        <?php elseif ($log['order_number']): ?>
                            <span class="badge" style="background: #22c55e; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem;">Active</span>
                        <?php else: ?>
                            <span class="badge" style="background: #64748b; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem;">Direct</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="pagination" style="margin-top: 1rem; display: flex; justify-content: center; gap: 0.5rem;">
        <?php if ($currentPage > 1): ?>
            <a href="/admin/downloads?page=<?php echo $currentPage - 1; ?>" class="btn btn-sm btn-outline">&laquo; Prev</a>
        <?php endif; ?>
        <span style="padding: 0.4rem 0.75rem; color: var(--admin-text-light); font-size: 0.875rem;">
            Page <?php echo $currentPage; ?> of <?php echo $totalPages; ?>
        </span>
        <?php if ($currentPage < $totalPages): ?>
            <a href="/admin/downloads?page=<?php echo $currentPage + 1; ?>" class="btn btn-sm btn-outline">Next &raquo;</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
