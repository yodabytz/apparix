<div class="page-header">
    <h1>Update Logs</h1>
    <a href="/admin/releases" class="btn btn-outline">Back to Releases</a>
</div>

<div class="card">
    <?php if (empty($logs)): ?>
        <div class="empty-state" style="padding: 3rem; text-align: center;">
            <p style="color: var(--admin-text-light);">No update activity yet</p>
        </div>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Domain</th>
                    <th>License Key</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Status</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo date('M j, Y g:i A', strtotime($log['created_at'])); ?></td>
                        <td>
                            <code style="font-size: 0.875rem;"><?php echo escape($log['domain'] ?? '-'); ?></code>
                        </td>
                        <td>
                            <code style="font-size: 0.75rem;"><?php echo escape(substr($log['license_key'], 0, 15) . '...'); ?></code>
                        </td>
                        <td>
                            <?php if ($log['from_version']): ?>
                                <span style="font-family: monospace;">v<?php echo escape($log['from_version']); ?></span>
                            <?php else: ?>
                                <span style="color: var(--admin-text-light);">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="font-family: monospace; font-weight: 600;">v<?php echo escape($log['to_version']); ?></span>
                        </td>
                        <td>
                            <?php
                            $statusClasses = [
                                'downloaded' => 'status-pending',
                                'installed' => 'status-active',
                                'failed' => 'status-cancelled'
                            ];
                            $class = $statusClasses[$log['status']] ?? 'status-pending';
                            ?>
                            <span class="status-badge <?php echo $class; ?>">
                                <?php echo ucfirst($log['status']); ?>
                            </span>
                            <?php if ($log['error_message']): ?>
                                <div style="font-size: 0.75rem; color: #ef4444; margin-top: 0.25rem;">
                                    <?php echo escape($log['error_message']); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="font-size: 0.875rem; color: var(--admin-text-light);">
                            <?php echo escape($log['ip_address'] ?? '-'); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
            <div class="pagination" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--admin-border); display: flex; justify-content: center; gap: 0.5rem;">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=<?php echo $currentPage - 1; ?>" class="btn btn-sm btn-outline">&laquo; Prev</a>
                <?php endif; ?>

                <span style="padding: 0.5rem 1rem; color: var(--admin-text-light);">
                    Page <?php echo $currentPage; ?> of <?php echo $totalPages; ?>
                </span>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?php echo $currentPage + 1; ?>" class="btn btn-sm btn-outline">Next &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
