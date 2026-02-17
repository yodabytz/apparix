<div class="page-header">
    <h1>Profit Report</h1>
    <div class="period-selector">
        <a href="/admin/orders/profits?period=daily" class="btn btn-sm <?= $period === 'daily' ? 'btn-primary' : 'btn-outline' ?>">Daily</a>
        <a href="/admin/orders/profits?period=weekly" class="btn btn-sm <?= $period === 'weekly' ? 'btn-primary' : 'btn-outline' ?>">Weekly</a>
        <a href="/admin/orders/profits?period=monthly&year=<?= $year ?>" class="btn btn-sm <?= $period === 'monthly' ? 'btn-primary' : 'btn-outline' ?>">Monthly</a>
        <a href="/admin/orders/profits?period=yearly" class="btn btn-sm <?= $period === 'yearly' ? 'btn-primary' : 'btn-outline' ?>">Yearly</a>
    </div>
</div>

<?php if ($period === 'monthly'): ?>
<div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem;">
    <a href="/admin/orders/profits?period=monthly&year=<?= $year - 1 ?>" class="btn btn-sm btn-outline">&larr; <?= $year - 1 ?></a>
    <span style="font-weight: 600; font-size: 1.1rem; padding: 0 0.5rem;"><?= $year ?></span>
    <?php if ($year < (int)date('Y')): ?>
        <a href="/admin/orders/profits?period=monthly&year=<?= $year + 1 ?>" class="btn btn-sm btn-outline"><?= $year + 1 ?> &rarr;</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Summary Stats -->
<div class="stats-grid">
    <div class="stat-card primary">
        <span class="stat-label">Revenue</span>
        <span class="stat-value">$<?= number_format($totalRevenue, 2) ?></span>
        <span class="stat-change"><?= number_format($totalOrders) ?> orders</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Cost of Goods</span>
        <span class="stat-value" style="color: var(--admin-danger);">$<?= number_format($totalCogs, 2) ?></span>
        <span class="stat-change">Product costs</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Shipping Cost</span>
        <span class="stat-value" style="color: var(--admin-warning);">$<?= number_format($totalShippingCost, 2) ?></span>
        <span class="stat-change">Actual shipping paid</span>
    </div>
    <div class="stat-card" style="border-left: 4px solid <?= $totalProfit >= 0 ? 'var(--admin-success)' : 'var(--admin-danger)' ?>;">
        <span class="stat-label">Net Profit</span>
        <span class="stat-value" style="color: <?= $totalProfit >= 0 ? 'var(--admin-success)' : 'var(--admin-danger)' ?>;">
            <?= $totalProfit < 0 ? '-' : '' ?>$<?= number_format(abs($totalProfit), 2) ?>
        </span>
        <span class="stat-change"><?= number_format($totalMargin, 1) ?>% margin</span>
    </div>
</div>

<!-- Charts -->
<div class="chart-grid-2">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Revenue vs Profit</span>
        </div>
        <div style="height: 300px;">
            <canvas id="revenueProfitChart"></canvas>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <span class="card-title">Profit Margin %</span>
        </div>
        <div style="height: 300px;">
            <canvas id="marginChart"></canvas>
        </div>
    </div>
</div>

<!-- Period Data Table -->
<div class="card" style="margin-top: 1.5rem;">
    <div class="card-header">
        <span class="card-title">
            <?php
            $periodLabels = [
                'daily' => 'Daily Breakdown (Last 30 Days)',
                'weekly' => 'Weekly Breakdown (Last 12 Weeks)',
                'monthly' => "Monthly Breakdown ({$year})",
                'yearly' => 'Yearly Breakdown (Last 5 Years)',
            ];
            echo $periodLabels[$period];
            ?>
        </span>
    </div>
    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Period</th>
                    <th style="text-align: right;">Orders</th>
                    <th style="text-align: right;">Revenue</th>
                    <th style="text-align: right;">COGS</th>
                    <th style="text-align: right;">Shipping</th>
                    <th style="text-align: right;">Profit</th>
                    <th style="text-align: right;">Margin</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($periodData as $row): ?>
                <tr>
                    <td style="font-weight: 500;"><?= htmlspecialchars($row['label']) ?></td>
                    <td style="text-align: right;"><?= number_format($row['order_count']) ?></td>
                    <td style="text-align: right;">$<?= number_format($row['revenue'], 2) ?></td>
                    <td style="text-align: right; color: var(--admin-danger);">$<?= number_format($row['cogs'], 2) ?></td>
                    <td style="text-align: right; color: var(--admin-warning);">$<?= number_format($row['shipping_cost'], 2) ?></td>
                    <td style="text-align: right; font-weight: 600; color: <?= $row['profit'] >= 0 ? 'var(--admin-success)' : 'var(--admin-danger)' ?>;">
                        <?= $row['profit'] < 0 ? '-' : '' ?>$<?= number_format(abs($row['profit']), 2) ?>
                    </td>
                    <td style="text-align: right;">
                        <?php if ($row['revenue'] > 0): ?>
                            <span class="badge <?= $row['margin'] >= 0 ? 'badge-success' : 'badge-danger' ?>">
                                <?= number_format($row['margin'], 1) ?>%
                            </span>
                        <?php else: ?>
                            <span class="badge badge-gray">--</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="font-weight: 700; border-top: 2px solid var(--admin-border);">
                    <td>Total</td>
                    <td style="text-align: right;"><?= number_format($totalOrders) ?></td>
                    <td style="text-align: right;">$<?= number_format($totalRevenue, 2) ?></td>
                    <td style="text-align: right; color: var(--admin-danger);">$<?= number_format($totalCogs, 2) ?></td>
                    <td style="text-align: right; color: var(--admin-warning);">$<?= number_format($totalShippingCost, 2) ?></td>
                    <td style="text-align: right; color: <?= $totalProfit >= 0 ? 'var(--admin-success)' : 'var(--admin-danger)' ?>;">
                        <?= $totalProfit < 0 ? '-' : '' ?>$<?= number_format(abs($totalProfit), 2) ?>
                    </td>
                    <td style="text-align: right;">
                        <span class="badge <?= $totalMargin >= 0 ? 'badge-success' : 'badge-danger' ?>">
                            <?= number_format($totalMargin, 1) ?>%
                        </span>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Top Products & Cost Coverage -->
<div class="chart-grid-2" style="margin-top: 1.5rem;">
    <!-- Top 10 Products by Revenue -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Top 10 Products by Revenue</span>
        </div>
        <?php if (empty($topProducts)): ?>
            <div class="empty-state">
                <p>No order data yet.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th style="text-align: right;">Qty</th>
                            <th style="text-align: right;">Revenue</th>
                            <th style="text-align: right;">Cost</th>
                            <th style="text-align: right;">Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topProducts as $product): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 500;"><?= htmlspecialchars($product['product_name']) ?></div>
                                <?php if ($product['variant_name']): ?>
                                    <div style="font-size: 0.75rem; color: var(--admin-text-light);"><?= htmlspecialchars($product['variant_name']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;"><?= number_format($product['total_qty']) ?></td>
                            <td style="text-align: right;">$<?= number_format($product['total_revenue'], 2) ?></td>
                            <td style="text-align: right; color: var(--admin-danger);">$<?= number_format($product['total_cost'], 2) ?></td>
                            <td style="text-align: right; font-weight: 600; color: <?= (float)$product['total_profit'] >= 0 ? 'var(--admin-success)' : 'var(--admin-danger)' ?>;">
                                <?= (float)$product['total_profit'] < 0 ? '-' : '' ?>$<?= number_format(abs((float)$product['total_profit']), 2) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Cost Coverage Summary -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Cost Data Coverage</span>
        </div>
        <div style="padding: 0.5rem 0;">
            <?php
            $totalItems = (int)($costCoverage['total_items'] ?? 0);
            $itemsWithCost = (int)($costCoverage['items_with_cost'] ?? 0);
            $itemsWithoutCost = (int)($costCoverage['items_without_cost'] ?? 0);
            $costPct = $totalItems > 0 ? ($itemsWithCost / $totalItems) * 100 : 0;

            $totalShipOrders = (int)($shippingCoverage['total_orders'] ?? 0);
            $ordersWithShip = (int)($shippingCoverage['orders_with_shipping_cost'] ?? 0);
            $ordersWithoutShip = (int)($shippingCoverage['orders_without_shipping_cost'] ?? 0);
            $shipPct = $totalShipOrders > 0 ? ($ordersWithShip / $totalShipOrders) * 100 : 0;
            ?>

            <!-- Product Cost Coverage -->
            <div style="margin-bottom: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <span style="font-weight: 600; font-size: 0.875rem;">Product Costs</span>
                    <span class="badge <?= $costPct >= 80 ? 'badge-success' : ($costPct >= 50 ? 'badge-warning' : 'badge-danger') ?>">
                        <?= number_format($costPct, 1) ?>% covered
                    </span>
                </div>
                <div style="background: #e5e7eb; border-radius: 9999px; height: 8px; overflow: hidden; margin-bottom: 0.5rem;">
                    <div style="background: <?= $costPct >= 80 ? 'var(--admin-success)' : ($costPct >= 50 ? 'var(--admin-warning)' : 'var(--admin-danger)') ?>; height: 100%; width: <?= $costPct ?>%; border-radius: 9999px; transition: width 0.3s;"></div>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--admin-text-light);">
                    <span><?= number_format($itemsWithCost) ?> items with cost</span>
                    <span><?= number_format($itemsWithoutCost) ?> missing</span>
                </div>
            </div>

            <!-- Shipping Cost Coverage -->
            <div style="margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <span style="font-weight: 600; font-size: 0.875rem;">Shipping Costs</span>
                    <span class="badge <?= $shipPct >= 80 ? 'badge-success' : ($shipPct >= 50 ? 'badge-warning' : 'badge-danger') ?>">
                        <?= number_format($shipPct, 1) ?>% covered
                    </span>
                </div>
                <div style="background: #e5e7eb; border-radius: 9999px; height: 8px; overflow: hidden; margin-bottom: 0.5rem;">
                    <div style="background: <?= $shipPct >= 80 ? 'var(--admin-success)' : ($shipPct >= 50 ? 'var(--admin-warning)' : 'var(--admin-danger)') ?>; height: 100%; width: <?= $shipPct ?>%; border-radius: 9999px; transition: width 0.3s;"></div>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--admin-text-light);">
                    <span><?= number_format($ordersWithShip) ?> orders with shipping cost</span>
                    <span><?= number_format($ordersWithoutShip) ?> missing</span>
                </div>
            </div>

            <?php if ($itemsWithoutCost > 0 || $ordersWithoutShip > 0): ?>
            <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; padding: 0.75rem 1rem; margin-top: 1rem;">
                <div style="font-size: 0.8rem; color: #92400e;">
                    <strong>Note:</strong> Profit calculations are most accurate when all order items have cost data and all orders have actual shipping costs entered. You can set costs on individual order pages.
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div style="text-align: center; margin-top: 1.5rem;">
    <a href="/admin/orders" class="btn btn-outline">&larr; Back to Orders</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
(function() {
    const labels = <?= json_encode(array_column($periodData, 'label')) ?>;
    const revenueData = <?= json_encode(array_map('floatval', array_column($periodData, 'revenue'))) ?>;
    const profitData = <?= json_encode(array_map('floatval', array_column($periodData, 'profit'))) ?>;
    const marginData = <?= json_encode(array_map('floatval', array_column($periodData, 'margin'))) ?>;

    const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--admin-primary').trim() || '#FF68C5';

    // Revenue vs Profit Line Chart
    const rvpCtx = document.getElementById('revenueProfitChart');
    if (rvpCtx) {
        new Chart(rvpCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Revenue',
                        data: revenueData,
                        borderColor: primaryColor,
                        backgroundColor: primaryColor + '20',
                        fill: true,
                        tension: 0.3,
                        borderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                    },
                    {
                        label: 'Profit',
                        data: profitData,
                        borderColor: '#10b981',
                        backgroundColor: '#10b98120',
                        fill: true,
                        tension: 0.3,
                        borderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': $' + context.parsed.y.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }

    // Margin Bar Chart
    const marginCtx = document.getElementById('marginChart');
    if (marginCtx) {
        new Chart(marginCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Margin %',
                    data: marginData,
                    backgroundColor: marginData.map(v => v >= 0 ? '#10b98180' : '#ef444480'),
                    borderColor: marginData.map(v => v >= 0 ? '#10b981' : '#ef4444'),
                    borderWidth: 1,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Margin: ' + context.parsed.y.toFixed(1) + '%';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
    }
})();
</script>
