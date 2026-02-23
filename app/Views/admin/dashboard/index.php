<div class="page-header">
    <h1>Dashboard</h1>
    <a href="/admin/products/create" class="btn btn-primary">+ Add Product</a>
</div>

<?php if (!empty($systemHealth)): ?>
<div style="margin-bottom: 24px;">
    <h3 style="margin: 0 0 12px; font-size: 1rem; display: flex; align-items: center; gap: 8px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        System Health
    </h3>
    <?php foreach ($systemHealth as $alert): ?>
    <div style="display: flex; align-items: flex-start; gap: 10px; margin-bottom: 8px; padding: 10px 14px; border-radius: 8px; font-size: 0.875rem; <?php echo $alert['type'] === 'error' ? 'background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;' : 'background: #fef3c7; color: #92400e; border: 1px solid #fcd34d;'; ?>">
        <span style="flex-shrink: 0; margin-top: 1px;"><?php echo $alert['type'] === 'error' ? '&#10060;' : '&#9888;'; ?></span>
        <div>
            <strong><?php echo escape($alert['title']); ?></strong>
            <span style="opacity: 0.85;"> &mdash; <?php echo escape($alert['message']); ?></span>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($backupNotifications)): ?>
<div style="margin-bottom: 24px;">
    <h3 style="margin: 0 0 12px; font-size: 1rem; display: flex; align-items: center; gap: 8px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        Backup Health
    </h3>
    <?php foreach ($backupNotifications as $bkn): ?>
    <div style="display: flex; align-items: flex-start; gap: 10px; margin-bottom: 8px; padding: 10px 14px; border-radius: 8px; font-size: 0.875rem; <?php echo $bkn['type'] === 'error' ? 'background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;' : 'background: #fef3c7; color: #92400e; border: 1px solid #fcd34d;'; ?>">
        <span style="flex-shrink: 0; margin-top: 1px;"><?php echo $bkn['type'] === 'error' ? '&#10060;' : '&#9888;'; ?></span>
        <div>
            <strong><?php echo escape($bkn['title']); ?></strong>
            <span style="opacity: 0.85;"> &mdash; <?php echo escape($bkn['message']); ?></span>
            <a href="/admin/plugins/settings?slug=backup" style="margin-left: 6px; font-size: 0.8rem;">Manage Backups &rarr;</a>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($setupTasks)): ?>
<div style="margin-bottom: 24px; background: var(--admin-card-bg, #fff); border-radius: 12px; padding: 20px 24px; border: 1px solid var(--admin-border, #e2e8f0);">
    <h3 style="margin: 0 0 4px; font-size: 1.05rem; display: flex; align-items: center; gap: 8px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        Getting Started
    </h3>
    <p style="margin: 0 0 16px; font-size: 0.85rem; color: var(--admin-text-light, #64748b);">Complete these steps to finish setting up your store.</p>
    <div style="display: flex; flex-direction: column; gap: 6px;">
        <?php foreach ($setupTasks as $task): ?>
        <a href="<?php echo escape($task['link']); ?>" style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 8px; background: var(--admin-bg, #f8fafc); border: 1px solid var(--admin-border, #e2e8f0); text-decoration: none; color: inherit; transition: border-color 0.2s, background 0.2s;">
            <span style="width: 22px; height: 22px; border-radius: 50%; border: 2px solid var(--admin-border, #cbd5e1); flex-shrink: 0;"></span>
            <div style="flex: 1; min-width: 0;">
                <strong style="font-size: 0.875rem; color: var(--admin-text, #1e293b);"><?php echo escape($task['label']); ?></strong>
                <p style="margin: 2px 0 0; font-size: 0.8rem; color: var(--admin-text-light, #64748b);"><?php echo escape($task['description']); ?></p>
            </div>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0; opacity: 0.4;"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card primary">
        <span class="stat-label">Today's Sales</span>
        <span class="stat-value"><?php echo formatPrice($todaySales['total']); ?></span>
        <span class="stat-change"><?php echo $todaySales['count']; ?> orders</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">This Week</span>
        <span class="stat-value"><?php echo formatPrice($weekSales['total']); ?></span>
        <span class="stat-change"><?php echo $weekSales['count']; ?> orders</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">This Month</span>
        <span class="stat-value"><?php echo formatPrice($monthSales['total']); ?></span>
        <span class="stat-change"><?php echo $monthSales['count']; ?> orders</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">All Time</span>
        <span class="stat-value"><?php echo formatPrice($allTimeSales['total']); ?></span>
        <span class="stat-change"><?php echo $allTimeSales['count']; ?> orders</span>
    </div>
</div>

<!-- Secondary Stats -->
<div class="stats-grid" style="margin-bottom: 1.5rem;">
    <div class="stat-card">
        <span class="stat-label">Favorites Today</span>
        <span class="stat-value"><?php echo $favoritesStats['today']['count'] ?? 0; ?></span>
        <span class="stat-change"><?php echo $favoritesStats['today']['unique_users'] ?? 0; ?> people</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Favorites This Week</span>
        <span class="stat-value"><?php echo $favoritesStats['week']['count'] ?? 0; ?></span>
        <span class="stat-change"><?php echo $favoritesStats['week']['unique_users'] ?? 0; ?> people</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Favorites This Month</span>
        <span class="stat-value"><?php echo $favoritesStats['month']['count'] ?? 0; ?></span>
        <span class="stat-change"><?php echo $favoritesStats['month']['unique_users'] ?? 0; ?> people</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Products</span>
        <span class="stat-value"><?php echo $productCount; ?></span>
        <span class="stat-change"><?php echo $lowStockCount; ?> low stock</span>
    </div>
</div>

<!-- License Status Card -->
<?php if (isset($licenseInfo)): ?>
<div class="card license-card" style="margin-bottom: 1.5rem; border-left: 4px solid <?php echo $licenseInfo['code'] === 'F' ? 'var(--admin-warning)' : 'var(--admin-success)'; ?>;">
    <div class="card-header">
        <h3 class="card-title">License Status</h3>
        <span class="badge <?php echo $licenseInfo['code'] === 'F' ? 'badge-warning' : 'badge-success'; ?>">
            <?php echo $licenseInfo['name']; ?>
        </span>
    </div>
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
        <div style="text-align: center; padding: 0.75rem;">
            <div style="font-size: 1.5rem; font-weight: 600;"><?php echo $licenseInfo['product_count']; ?> / <?php echo $licenseInfo['limits']['products']; ?></div>
            <div style="font-size: 0.75rem; color: var(--admin-text-light);">Products</div>
            <?php if ($licenseInfo['product_remaining'] !== 'unlimited' && $licenseInfo['product_remaining'] <= 3 && $licenseInfo['product_remaining'] > 0): ?>
            <div style="font-size: 0.7rem; color: var(--admin-warning); margin-top: 0.25rem;"><?php echo $licenseInfo['product_remaining']; ?> remaining</div>
            <?php endif; ?>
        </div>
        <div style="text-align: center; padding: 0.75rem;">
            <div style="font-size: 1.5rem; font-weight: 600;"><?php echo $licenseInfo['limits']['orders_month']; ?></div>
            <div style="font-size: 0.75rem; color: var(--admin-text-light);">Orders/Month</div>
        </div>
        <div style="text-align: center; padding: 0.75rem;">
            <div style="font-size: 1.5rem; font-weight: 600;"><?php echo $licenseInfo['limits']['admin_users']; ?></div>
            <div style="font-size: 0.75rem; color: var(--admin-text-light);">Admin Users</div>
        </div>
    </div>
    <?php if ($licenseInfo['code'] === 'F'): ?>
    <div style="padding: 0.75rem; background: var(--admin-bg-light); border-radius: 0 0 8px 8px; text-align: center;">
        <span style="font-size: 0.875rem; color: var(--admin-text-light);">Running on free tier with limited features.</span>
        <a href="<?php echo \App\Core\License::getUpgradeUrl(); ?>" target="_blank" style="color: var(--admin-primary); font-weight: 500; margin-left: 0.5rem;">Upgrade Now</a>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Security Status Card -->
<?php if (!empty($securityStatus)): ?>
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title" style="display: flex; align-items: center; gap: 8px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Security Status
        </h3>
    </div>
    <div style="display: flex; flex-direction: column; gap: 10px;">
        <?php foreach ($securityStatus as $tool): ?>
        <div style="display: flex; align-items: center; gap: 14px; padding: 12px 16px; border-radius: 8px; background: var(--admin-bg, #f8fafc); border: 1px solid var(--admin-border, #e2e8f0);">
            <?php if ($tool['active']): ?>
                <span style="width: 28px; height: 28px; border-radius: 50%; background: #dcfce7; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                </span>
            <?php elseif ($tool['installed']): ?>
                <span style="width: 28px; height: 28px; border-radius: 50%; background: #fef3c7; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="3"><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </span>
            <?php else: ?>
                <span style="width: 28px; height: 28px; border-radius: 50%; background: #fee2e2; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </span>
            <?php endif; ?>
            <div style="flex: 1; min-width: 0;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 2px;">
                    <strong style="font-size: 0.9rem;"><?php echo escape($tool['label']); ?></strong>
                    <?php if ($tool['active']): ?>
                        <span class="badge badge-success" style="font-size: 0.7rem;">Active</span>
                    <?php elseif ($tool['installed']): ?>
                        <span class="badge badge-warning" style="font-size: 0.7rem;">Inactive</span>
                    <?php else: ?>
                        <span class="badge badge-danger" style="font-size: 0.7rem;">Not Installed</span>
                    <?php endif; ?>
                </div>
                <div style="font-size: 0.8rem; color: var(--admin-text-light, #64748b);">
                    <?php echo escape($tool['description']); ?>
                    <?php if ($tool['details']): ?>
                        <span style="opacity: 0.8;"> &mdash; <?php echo escape($tool['details']); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!$tool['installed']): ?>
                <a href="<?php echo escape($tool['url']); ?>" target="_blank" rel="noopener" style="font-size: 0.8rem; font-weight: 600; color: var(--admin-primary, #3b82f6); white-space: nowrap; text-decoration: none;">Get It &rarr;</a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Profit Tracking Section -->
<?php if (isset($profitStats) && $profitStats['complete_order_count'] > 0): ?>
<div class="card profit-card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title">Profit Tracking</h3>
        <span class="badge badge-info"><?php echo $profitStats['complete_order_count']; ?> orders with complete data</span>
    </div>
    <div class="profit-grid">
        <div class="profit-stat">
            <span class="profit-label">Revenue</span>
            <span class="profit-value"><?php echo formatPrice($profitStats['total_revenue']); ?></span>
        </div>
        <div class="profit-stat">
            <span class="profit-label">Product Costs</span>
            <span class="profit-value cost"><?php echo formatPrice($profitStats['product_costs']); ?></span>
        </div>
        <div class="profit-stat">
            <span class="profit-label">Shipping Paid</span>
            <span class="profit-value cost"><?php echo formatPrice($profitStats['shipping_paid']); ?></span>
        </div>
        <div class="profit-stat highlight <?php echo $profitStats['total_profit'] >= 0 ? 'positive' : 'negative'; ?>">
            <span class="profit-label">Total Profit</span>
            <span class="profit-value">
                <?php echo $profitStats['total_profit'] >= 0 ? '' : '-'; ?>
                <?php echo formatPrice(abs($profitStats['total_profit'])); ?>
                <span class="profit-margin">(<?php echo number_format($profitStats['profit_margin'], 1); ?>%)</span>
            </span>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Needs Attention Section -->
<?php if (isset($needsAttention) && ($needsAttention['products_missing_cost_count'] > 0 || $needsAttention['orders_missing_shipping_count'] > 0)): ?>
<div class="card attention-card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title">Needs Attention</h3>
    </div>
    <div class="attention-grid">
        <?php if ($needsAttention['products_missing_cost_count'] > 0): ?>
        <div class="attention-section">
            <div class="attention-header">
                <span class="attention-icon">&#9888;</span>
                <strong><?php echo $needsAttention['products_missing_cost_count']; ?> Products Missing Cost</strong>
            </div>
            <p class="attention-desc">These products need cost data for accurate profit calculations.</p>
            <?php if (!empty($needsAttention['products_needing_cost'])): ?>
            <ul class="attention-list">
                <?php foreach (array_slice($needsAttention['products_needing_cost'], 0, 5) as $product): ?>
                <li>
                    <a href="/admin/products/<?php echo $product['id']; ?>/edit"><?php echo escape($product['name']); ?></a>
                    <span class="meta">
                        <?php echo formatPrice($product['price']); ?>
                        <?php if ($product['times_sold'] > 0): ?>
                            &middot; sold <?php echo $product['times_sold']; ?>x
                        <?php endif; ?>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php if ($needsAttention['products_missing_cost_count'] > 5): ?>
            <a href="/admin/products?filter=missing_cost" class="view-all-link">View all <?php echo $needsAttention['products_missing_cost_count']; ?> products</a>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($needsAttention['orders_missing_shipping_count'] > 0): ?>
        <div class="attention-section">
            <div class="attention-header">
                <span class="attention-icon">&#128230;</span>
                <strong><?php echo $needsAttention['orders_missing_shipping_count']; ?> Orders Missing Shipping Cost</strong>
            </div>
            <p class="attention-desc">Enter actual shipping costs for these orders to track profits.</p>
            <?php if (!empty($needsAttention['orders_needing_shipping'])): ?>
            <ul class="attention-list">
                <?php foreach (array_slice($needsAttention['orders_needing_shipping'], 0, 5) as $order): ?>
                <li>
                    <a href="/admin/orders/view?id=<?php echo $order['id']; ?>">#<?php echo escape($order['order_number']); ?></a>
                    <span class="meta">
                        <?php echo formatPrice($order['total']); ?>
                        &middot; <?php echo date('M j', strtotime($order['created_at'])); ?>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php if ($needsAttention['orders_missing_shipping_count'] > 5): ?>
            <a href="/admin/orders" class="view-all-link">View all <?php echo $needsAttention['orders_missing_shipping_count']; ?> orders</a>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Visitor Statistics Section -->
<?php if (isset($visitorStats)): ?>
<div class="card visitor-card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title">Visitor Analytics</h3>
        <a href="/admin/visitors" class="btn btn-sm btn-outline">View Details</a>
    </div>

    <!-- Visitor Stats Grid -->
    <div class="stats-grid" style="margin-bottom: 1rem;">
        <div class="stat-card">
            <span class="stat-label">Today</span>
            <span class="stat-value"><?php echo number_format($visitorStats['today']['unique']); ?></span>
            <span class="stat-change"><?php echo number_format($visitorStats['today']['views']); ?> views</span>
        </div>
        <div class="stat-card">
            <span class="stat-label">This Week</span>
            <span class="stat-value"><?php echo number_format($visitorStats['week']['unique']); ?></span>
            <span class="stat-change"><?php echo number_format($visitorStats['week']['views']); ?> views</span>
        </div>
        <div class="stat-card">
            <span class="stat-label">This Month</span>
            <span class="stat-value"><?php echo number_format($visitorStats['month']['unique']); ?></span>
            <span class="stat-change"><?php echo number_format($visitorStats['month']['views']); ?> views</span>
        </div>
        <div class="stat-card">
            <span class="stat-label">All Time</span>
            <span class="stat-value"><?php echo number_format($visitorStats['all']['unique']); ?></span>
            <span class="stat-change"><?php echo number_format($visitorStats['all']['views']); ?> views</span>
        </div>
    </div>

    <!-- Country and Referrer breakdown -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
        <!-- Top Countries -->
        <div>
            <h4 style="margin: 0 0 0.75rem 0; font-size: 0.875rem; color: var(--admin-text-light);">Top Countries (30 days)</h4>
            <?php if (!empty($visitorsByCountry)): ?>
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <?php foreach (array_slice($visitorsByCountry, 0, 5) as $country): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.25rem 0;">
                    <span>
                        <?php echo $country['country_code'] ? getFlagEmoji($country['country_code']) . ' ' : ''; ?>
                        <?php echo escape($country['country'] ?? 'Unknown'); ?>
                    </span>
                    <span class="badge badge-gray"><?php echo number_format($country['unique_visitors']); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p style="color: var(--admin-text-light); font-size: 0.875rem;">No visitor data yet</p>
            <?php endif; ?>
        </div>

        <!-- Top Referrers -->
        <div>
            <h4 style="margin: 0 0 0.75rem 0; font-size: 0.875rem; color: var(--admin-text-light);">Traffic Sources (30 days)</h4>
            <?php if (!empty($topReferrers)): ?>
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <?php foreach (array_slice($topReferrers, 0, 5) as $referrer): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.25rem 0;">
                    <span style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        <?php echo escape($referrer['source']); ?>
                    </span>
                    <span class="badge badge-gray"><?php echo number_format($referrer['unique_visitors']); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p style="color: var(--admin-text-light); font-size: 0.875rem;">No referrer data yet</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
    <!-- Recent Orders -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Orders</h3>
            <a href="/admin/orders" class="btn btn-sm btn-outline">View All</a>
        </div>

        <?php if (!empty($recentOrders)): ?>
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td>
                                    <a href="/admin/orders/view?id=<?php echo $order['id']; ?>" style="color: var(--admin-primary); font-weight: 500;">
                                        #<?php echo escape($order['order_number']); ?>
                                    </a>
                                </td>
                                <td><?php echo escape($order['customer_email']); ?></td>
                                <td>
                                    <?php
                                    $statusClass = match($order['status']) {
                                        'pending' => 'badge-warning',
                                        'processing' => 'badge-info',
                                        'shipped' => 'badge-info',
                                        'delivered' => 'badge-success',
                                        'cancelled' => 'badge-danger',
                                        'refunded' => 'badge-gray',
                                        default => 'badge-gray'
                                    };
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($order['status']); ?></span>
                                </td>
                                <td><?php echo formatPrice($order['total']); ?></td>
                                <td><?php echo date('M j, g:ia', strtotime($order['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h3>No orders yet</h3>
                <p>Orders will appear here when customers make purchases.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Quick Stats -->
    <div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Inventory</h3>
            </div>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div style="display: flex; justify-content: space-between;">
                    <span>Active Products</span>
                    <strong><?php echo $productCount; ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Low Stock Items</span>
                    <strong style="color: <?php echo $lowStockCount > 0 ? 'var(--admin-warning)' : 'inherit'; ?>">
                        <?php echo $lowStockCount; ?>
                    </strong>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Order Status</h3>
            </div>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <?php
                $statusCounts = [];
                foreach ($ordersByStatus as $s) {
                    $statusCounts[$s['status']] = $s['count'];
                }
                $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
                foreach ($statuses as $status):
                    $count = $statusCounts[$status] ?? 0;
                ?>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span><?php echo ucfirst($status); ?></span>
                        <span class="badge badge-gray"><?php echo $count; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Activity</h3>
            </div>
            <?php if (!empty($recentActivity)): ?>
                <div style="display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.875rem;">
                    <?php foreach (array_slice($recentActivity, 0, 5) as $activity): ?>
                        <div>
                            <strong><?php echo escape($activity['admin_name']); ?></strong>
                            <span style="color: var(--admin-text-light);">
                                <?php echo escape($activity['action']); ?>
                            </span>
                            <div style="font-size: 0.75rem; color: var(--admin-text-light);">
                                <?php echo date('M j, g:ia', strtotime($activity['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: var(--admin-text-light); font-size: 0.875rem;">No recent activity</p>
            <?php endif; ?>
        </div>
    </div>
</div>
