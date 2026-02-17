<div class="page-header">
    <h1>Visitor Analytics</h1>
    <div class="period-selector">
        <a href="/admin/visitors?period=today" class="btn btn-sm <?php echo $period === 'today' ? 'btn-primary' : 'btn-outline'; ?>">Today</a>
        <a href="/admin/visitors?period=yesterday" class="btn btn-sm <?php echo $period === 'yesterday' ? 'btn-primary' : 'btn-outline'; ?>">Yesterday</a>
        <a href="/admin/visitors?period=week" class="btn btn-sm <?php echo $period === 'week' ? 'btn-primary' : 'btn-outline'; ?>">Week</a>
        <a href="/admin/visitors?period=month" class="btn btn-sm <?php echo $period === 'month' ? 'btn-primary' : 'btn-outline'; ?>">Month</a>
        <a href="/admin/visitors?period=all" class="btn btn-sm <?php echo $period === 'all' ? 'btn-primary' : 'btn-outline'; ?>">All Time</a>
    </div>
</div>

<!-- Stats Summary -->
<?php
$periodLabels = [
    'today' => 'Today',
    'yesterday' => 'Yesterday',
    'week' => 'This Week',
    'month' => 'This Month',
    'all' => 'All Time'
];
$currentPeriodLabel = $periodLabels[$period] ?? 'This Month';

// Get selected period stats (humans only)
$selectedStats = $stats[$period] ?? $stats['month'];
$selectedBotStats = $botStats[$period] ?? $botStats['month'];
?>

<!-- Human Visitors Stats -->
<div style="margin-bottom: 0.5rem;">
    <h3 style="font-size: 1rem; color: var(--admin-text); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
        <span style="font-size: 1.25rem;">&#128100;</span> Real Visitors <span class="period-badge"><?php echo $currentPeriodLabel; ?></span>
    </h3>
</div>
<div class="stats-grid">
    <div class="stat-card primary">
        <span class="stat-label">Unique Visitors</span>
        <span class="stat-value"><?php echo number_format($selectedStats['unique']); ?></span>
        <span class="stat-change">from <?php echo number_format(count($byCountry)); ?> countries</span>
    </div>
    <div class="stat-card primary">
        <span class="stat-label">Page Views</span>
        <span class="stat-value"><?php echo number_format($selectedStats['views']); ?></span>
        <span class="stat-change"><?php echo count($topReferrers); ?> traffic sources</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Today</span>
        <span class="stat-value"><?php echo number_format($stats['today']['unique']); ?></span>
        <span class="stat-change"><?php echo number_format($stats['today']['views']); ?> views</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">This Month</span>
        <span class="stat-value"><?php echo number_format($stats['month']['unique']); ?></span>
        <span class="stat-change"><?php echo number_format($stats['month']['views']); ?> views</span>
    </div>
</div>

<!-- Bot Traffic Stats -->
<div style="margin: 1.5rem 0 0.5rem;">
    <h3 style="font-size: 1rem; color: var(--admin-text-light); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
        <span style="font-size: 1.25rem;">&#129302;</span> Bot Traffic <span class="period-badge" style="background: #64748b;"><?php echo $currentPeriodLabel; ?></span>
    </h3>
</div>
<div class="stats-grid" style="margin-bottom: 1.5rem;">
    <div class="stat-card" style="border-left: 3px solid #64748b;">
        <span class="stat-label">Bot Hits</span>
        <span class="stat-value"><?php echo number_format($selectedBotStats['views']); ?></span>
        <span class="stat-change"><?php echo number_format($selectedBotStats['unique']); ?> unique IPs</span>
    </div>
    <div class="stat-card" style="border-left: 3px solid #64748b;">
        <span class="stat-label">Bots Today</span>
        <span class="stat-value"><?php echo number_format($botStats['today']['views']); ?></span>
        <span class="stat-change"><?php echo number_format($botStats['today']['unique']); ?> unique IPs</span>
    </div>
    <div class="stat-card" style="border-left: 3px solid #64748b;">
        <span class="stat-label">Bots This Month</span>
        <span class="stat-value"><?php echo number_format($botStats['month']['views']); ?></span>
        <span class="stat-change"><?php echo number_format($botStats['month']['unique']); ?> unique IPs</span>
    </div>
    <div class="stat-card" style="border-left: 3px solid #64748b;">
        <span class="stat-label">% Bot Traffic</span>
        <?php
        $totalViews = $selectedStats['views'] + $selectedBotStats['views'];
        $botPercent = $totalViews > 0 ? round(($selectedBotStats['views'] / $totalViews) * 100, 1) : 0;
        ?>
        <span class="stat-value"><?php echo $botPercent; ?>%</span>
        <span class="stat-change">of all traffic</span>
    </div>
</div>

<!-- Visitors Line Chart -->
<div class="card" style="margin-top: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title">&#128200; <?php echo $chartType === 'hourly' ? 'Hourly' : 'Daily'; ?> Visitors <span class="period-badge"><?php echo $currentPeriodLabel; ?></span></h3>
    </div>
    <div style="padding: 1rem; height: 300px;">
        <?php if (!empty($chartStats) || !empty($chartBotStats)): ?>
        <canvas id="dailyVisitorsChart"></canvas>
        <?php else: ?>
        <p style="color: var(--admin-text-light); text-align: center; padding: 2rem;">No visitor data available</p>
        <?php endif; ?>
    </div>
</div>

<!-- Pie Charts Row -->
<div class="chart-grid-3" style="margin-top: 1.5rem;">
    <!-- Countries Pie Chart -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Top Countries <span class="period-badge"><?php echo $currentPeriodLabel; ?></span></h3>
        </div>
        <div style="padding: 1rem; height: 280px; display: flex; align-items: center; justify-content: center;">
            <?php if (!empty($byCountry)): ?>
            <canvas id="countriesChart"></canvas>
            <?php else: ?>
            <p style="color: var(--admin-text-light);">No data available</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sources Pie Chart -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Traffic Sources <span class="period-badge"><?php echo $currentPeriodLabel; ?></span></h3>
        </div>
        <div style="padding: 1rem; height: 280px; display: flex; align-items: center; justify-content: center;">
            <?php if (!empty($topReferrers)): ?>
            <canvas id="sourcesChart"></canvas>
            <?php else: ?>
            <p style="color: var(--admin-text-light);">No data available</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bots Pie Chart -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">&#129302; Bot Breakdown <span class="period-badge" style="background: #64748b;"><?php echo $currentPeriodLabel; ?></span></h3>
        </div>
        <div style="padding: 1rem; height: 280px; display: flex; align-items: center; justify-content: center;">
            <?php if (!empty($topBots)): ?>
            <canvas id="botsChart"></canvas>
            <?php else: ?>
            <p style="color: var(--admin-text-light);">No bot data available</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Second Row of Pie Charts -->
<div class="chart-grid-3" style="margin-top: 1.5rem;">
    <!-- Device/OS Pie Chart -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">&#128187; Device / OS <span class="period-badge"><?php echo $currentPeriodLabel; ?></span></h3>
        </div>
        <div style="padding: 1rem; height: 280px; display: flex; align-items: center; justify-content: center;">
            <?php if (!empty($deviceStats)): ?>
            <canvas id="deviceChart"></canvas>
            <?php else: ?>
            <p style="color: var(--admin-text-light);">No device data available</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Browser Pie Chart -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">&#127760; Browsers <span class="period-badge"><?php echo $currentPeriodLabel; ?></span></h3>
        </div>
        <div style="padding: 1rem; height: 280px; display: flex; align-items: center; justify-content: center;">
            <?php if (!empty($browserStats)): ?>
            <canvas id="browserChart"></canvas>
            <?php else: ?>
            <p style="color: var(--admin-text-light);">No browser data available</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- HTTP Status Codes Pie Chart -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">&#128202; HTTP Status Codes <span class="period-badge"><?php echo $currentPeriodLabel; ?></span></h3>
        </div>
        <div style="padding: 1rem; height: 280px; display: flex; align-items: center; justify-content: center;">
            <?php if (!empty($httpStats)): ?>
            <canvas id="httpChart"></canvas>
            <?php else: ?>
            <p style="color: var(--admin-text-light);">No data available</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Main Content Grid -->
<div class="chart-grid-2" style="margin-top: 1.5rem;">
    <!-- Countries -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Visitors by Country <span class="period-badge"><?php echo $currentPeriodLabel; ?></span></h3>
        </div>
        <?php if (!empty($byCountry)): ?>
        <div class="table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Country</th>
                        <th style="text-align: right;">Unique Visitors</th>
                        <th style="text-align: right;">Page Views</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($byCountry as $country): ?>
                    <tr>
                        <td>
                            <?php echo $country['country_code'] ? getFlagEmoji($country['country_code']) . ' ' : ''; ?>
                            <?php echo escape($country['country'] ?? 'Unknown'); ?>
                        </td>
                        <td style="text-align: right;"><?php echo number_format($country['unique_visitors']); ?></td>
                        <td style="text-align: right;"><?php echo number_format($country['page_views']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <p>No visitor data yet for this period.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Traffic Sources -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Traffic Sources <span class="period-badge"><?php echo $currentPeriodLabel; ?></span></h3>
        </div>
        <?php if (!empty($topReferrers)): ?>
        <div class="table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Source</th>
                        <th style="text-align: right;">Unique Visitors</th>
                        <th style="text-align: right;">Page Views</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topReferrers as $referrer): ?>
                    <tr>
                        <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            <?php echo escape($referrer['source']); ?>
                        </td>
                        <td style="text-align: right;"><?php echo number_format($referrer['unique_visitors']); ?></td>
                        <td style="text-align: right;"><?php echo number_format($referrer['page_views']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <p>No referrer data yet for this period.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Top Pages -->
<div class="card" style="margin-top: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title">Top Pages <span class="period-badge"><?php echo $currentPeriodLabel; ?></span></h3>
    </div>
    <?php if (!empty($topPages)): ?>
    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Page URL</th>
                    <th style="text-align: right;">Page Views</th>
                    <th style="text-align: right;">Unique Visitors</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topPages as $page): ?>
                <tr>
                    <td>
                        <a href="<?php echo escape($page['page_url']); ?>" target="_blank" style="color: var(--admin-primary);">
                            <?php echo escape($page['page_url']); ?>
                        </a>
                    </td>
                    <td style="text-align: right;"><?php echo number_format($page['views']); ?></td>
                    <td style="text-align: right;"><?php echo number_format($page['unique_visitors']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <p>No page data yet for this period.</p>
    </div>
    <?php endif; ?>
</div>

<!-- Recent Visitors -->
<div class="card" style="margin-top: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title">Recent Visitors</h3>
    </div>
    <?php if (!empty($recentVisitors)): ?>
    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Country</th>
                    <th>Page</th>
                    <th>Referrer</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentVisitors as $visitor): ?>
                <tr>
                    <td style="white-space: nowrap;"><?php echo date('M j, g:ia', strtotime($visitor['created_at'])); ?></td>
                    <td class="visitor-country-cell"
                        data-ip="<?php echo escape($visitor['ip_address'] ?? ''); ?>"
                        data-ua="<?php echo escape($visitor['user_agent'] ?? ''); ?>"
                        data-country="<?php echo escape($visitor['country'] ?? 'Unknown'); ?>"
                        data-city="<?php echo escape($visitor['city'] ?? ''); ?>">
                        <?php if ($visitor['country_code']): ?>
                            <?php echo getFlagEmoji($visitor['country_code']); ?>
                            <?php echo escape($visitor['country'] ?? ''); ?>
                            <?php if ($visitor['city']): ?>
                                <span style="color: var(--admin-text-light);">(<?php echo escape($visitor['city']); ?>)</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color: var(--admin-text-light);">Unknown</span>
                        <?php endif; ?>
                    </td>
                    <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        <a href="<?php echo escape($visitor['page_url']); ?>" target="_blank" style="color: var(--admin-primary);">
                            <?php echo escape($visitor['page_url']); ?>
                        </a>
                    </td>
                    <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--admin-text-light);">
                        <?php
                        if ($visitor['referrer']) {
                            $ref = parse_url($visitor['referrer'], PHP_URL_HOST) ?: $visitor['referrer'];
                            echo escape($ref);
                        } else {
                            echo 'Direct';
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <p>No visitor data yet.</p>
    </div>
    <?php endif; ?>
</div>

<style>
.period-selector {
    display: flex;
    gap: 0.5rem;
}
.period-badge {
    display: inline-block;
    font-size: 0.7rem;
    font-weight: 500;
    padding: 0.2rem 0.5rem;
    background: var(--admin-primary, #FF6B9D);
    color: white;
    border-radius: 4px;
    margin-left: 0.5rem;
    vertical-align: middle;
}
.visitor-country-cell {
    cursor: help;
    position: relative;
}
.visitor-tooltip {
    position: fixed;
    background: #1a1a2e;
    color: #fff;
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 12px;
    line-height: 1.5;
    max-width: 350px;
    z-index: 10000;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    pointer-events: none;
    opacity: 0;
    transform: translateY(5px);
    transition: opacity 0.2s, transform 0.2s;
}
.visitor-tooltip.visible {
    opacity: 1;
    transform: translateY(0);
}
.visitor-tooltip .tooltip-row {
    display: flex;
    margin-bottom: 4px;
}
.visitor-tooltip .tooltip-row:last-child {
    margin-bottom: 0;
}
.visitor-tooltip .tooltip-label {
    color: #a0a0a0;
    min-width: 75px;
    flex-shrink: 0;
}
.visitor-tooltip .tooltip-value {
    color: #fff;
    word-break: break-all;
}
.visitor-tooltip .tooltip-ip {
    font-family: monospace;
    color: #4fc3f7;
}
</style>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Visitor tooltip functionality
    (function() {
        let tooltip = null;
        let hoverTimeout = null;
        const DELAY = 500; // ms before tooltip appears

        function createTooltipRow(label, value, valueClass) {
            const row = document.createElement('div');
            row.className = 'tooltip-row';
            const labelSpan = document.createElement('span');
            labelSpan.className = 'tooltip-label';
            labelSpan.textContent = label;
            const valueSpan = document.createElement('span');
            valueSpan.className = 'tooltip-value' + (valueClass ? ' ' + valueClass : '');
            valueSpan.textContent = value;
            row.appendChild(labelSpan);
            row.appendChild(valueSpan);
            return row;
        }

        function createTooltip() {
            if (!tooltip) {
                tooltip = document.createElement('div');
                tooltip.className = 'visitor-tooltip';
                document.body.appendChild(tooltip);
            }
            return tooltip;
        }

        function showTooltip(cell, e) {
            const tip = createTooltip();
            const ip = cell.dataset.ip || 'Unknown';
            const ua = cell.dataset.ua || 'Unknown';
            const country = cell.dataset.country || 'Unknown';
            const city = cell.dataset.city || '';

            // Parse user agent for readable format
            let browser = 'Unknown';
            let os = 'Unknown';
            if (ua.includes('Chrome')) browser = 'Chrome';
            else if (ua.includes('Safari')) browser = 'Safari';
            else if (ua.includes('Firefox')) browser = 'Firefox';
            else if (ua.includes('Edge')) browser = 'Edge';
            if (ua.includes('Windows')) os = 'Windows';
            else if (ua.includes('Mac')) os = 'macOS';
            else if (ua.includes('iPhone')) os = 'iOS';
            else if (ua.includes('Android')) os = 'Android';
            else if (ua.includes('Linux')) os = 'Linux';

            const location = city ? city + ', ' + country : country;

            // Clear and rebuild tooltip content safely
            tip.textContent = '';
            tip.appendChild(createTooltipRow('IP:', ip, 'tooltip-ip'));
            tip.appendChild(createTooltipRow('Location:', location));
            tip.appendChild(createTooltipRow('Browser:', browser + ' / ' + os));

            // Position tooltip near cursor
            const x = e.clientX + 15;
            const y = e.clientY + 15;

            tip.style.left = x + 'px';
            tip.style.top = y + 'px';

            // Adjust if off screen
            requestAnimationFrame(function() {
                const rect = tip.getBoundingClientRect();
                if (rect.right > window.innerWidth) {
                    tip.style.left = (e.clientX - rect.width - 10) + 'px';
                }
                if (rect.bottom > window.innerHeight) {
                    tip.style.top = (e.clientY - rect.height - 10) + 'px';
                }
                tip.classList.add('visible');
            });
        }

        function hideTooltip() {
            if (tooltip) {
                tooltip.classList.remove('visible');
            }
        }

        document.querySelectorAll('.visitor-country-cell').forEach(function(cell) {
            cell.addEventListener('mouseenter', function(e) {
                var self = this;
                hoverTimeout = setTimeout(function() { showTooltip(self, e); }, DELAY);
            });
            cell.addEventListener('mousemove', function(e) {
                if (tooltip && tooltip.classList.contains('visible')) {
                    tooltip.style.left = (e.clientX + 15) + 'px';
                    tooltip.style.top = (e.clientY + 15) + 'px';
                }
            });
            cell.addEventListener('mouseleave', function() {
                clearTimeout(hoverTimeout);
                hideTooltip();
            });
        });
    })();
    // Color palette for charts
    const colors = [
        '#FF6B9D', // Pink (primary)
        '#9B59B6', // Purple
        '#3498DB', // Blue
        '#1ABC9C', // Teal
        '#F39C12', // Orange
        '#E74C3C', // Red
        '#2ECC71', // Green
        '#34495E', // Dark gray
        '#95A5A6', // Light gray
        '#E91E63'  // Deep pink
    ];

    <?php if (!empty($byCountry)): ?>
    // Countries Pie Chart
    const countriesCtx = document.getElementById('countriesChart');
    if (countriesCtx) {
        const countryData = <?php
            $topCountries = array_slice($byCountry, 0, 8);
            $otherCount = 0;
            foreach (array_slice($byCountry, 8) as $c) {
                $otherCount += $c['unique_visitors'];
            }
            $labels = array_map(fn($c) => $c['country'] ?? 'Unknown', $topCountries);
            $values = array_map(fn($c) => (int)$c['unique_visitors'], $topCountries);
            if ($otherCount > 0) {
                $labels[] = 'Other';
                $values[] = $otherCount;
            }
            echo json_encode(['labels' => $labels, 'values' => $values]);
        ?>;

        new Chart(countriesCtx, {
            type: 'doughnut',
            data: {
                labels: countryData.labels,
                datasets: [{
                    data: countryData.values,
                    backgroundColor: colors.slice(0, countryData.labels.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            padding: 8,
                            font: { size: 11 }
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>

    <?php if (!empty($topReferrers)): ?>
    // Sources Pie Chart
    const sourcesCtx = document.getElementById('sourcesChart');
    if (sourcesCtx) {
        const sourceData = <?php
            $topSources = array_slice($topReferrers, 0, 8);
            $otherCount = 0;
            foreach (array_slice($topReferrers, 8) as $s) {
                $otherCount += $s['unique_visitors'];
            }
            $labels = array_map(fn($s) => $s['source'], $topSources);
            $values = array_map(fn($s) => (int)$s['unique_visitors'], $topSources);
            if ($otherCount > 0) {
                $labels[] = 'Other';
                $values[] = $otherCount;
            }
            echo json_encode(['labels' => $labels, 'values' => $values]);
        ?>;

        new Chart(sourcesCtx, {
            type: 'doughnut',
            data: {
                labels: sourceData.labels,
                datasets: [{
                    data: sourceData.values,
                    backgroundColor: colors.slice(0, sourceData.labels.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            padding: 8,
                            font: { size: 11 }
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>

    <?php if (!empty($topBots)): ?>
    // Bot colors (grays and blues)
    const botColors = [
        '#64748b', // Slate
        '#475569', // Slate darker
        '#334155', // Slate dark
        '#6366f1', // Indigo
        '#8b5cf6', // Violet
        '#a855f7', // Purple
        '#0ea5e9', // Sky
        '#06b6d4', // Cyan
        '#14b8a6', // Teal
        '#78716c', // Stone
        '#57534e', // Stone dark
        '#94a3b8'  // Slate light
    ];

    // Bots Pie Chart
    const botsCtx = document.getElementById('botsChart');
    if (botsCtx) {
        const botData = <?php
            $topBotsList = array_slice($topBots, 0, 10);
            $otherHits = 0;
            foreach (array_slice($topBots, 10) as $b) {
                $otherHits += $b['hits'];
            }
            $labels = array_map(fn($b) => $b['bot_name'], $topBotsList);
            $values = array_map(fn($b) => (int)$b['hits'], $topBotsList);
            if ($otherHits > 0) {
                $labels[] = 'Other';
                $values[] = $otherHits;
            }
            echo json_encode(['labels' => $labels, 'values' => $values]);
        ?>;

        new Chart(botsCtx, {
            type: 'doughnut',
            data: {
                labels: botData.labels,
                datasets: [{
                    data: botData.values,
                    backgroundColor: botColors.slice(0, botData.labels.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            padding: 8,
                            font: { size: 11 }
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>

    // Device/OS colors
    const deviceColors = [
        '#10b981', // Emerald (iOS)
        '#14b8a6', // Teal
        '#3b82f6', // Blue (Windows)
        '#6366f1', // Indigo
        '#8b5cf6', // Violet (macOS)
        '#f59e0b', // Amber (Android)
        '#ef4444', // Red
        '#ec4899', // Pink
        '#64748b', // Slate
        '#78716c'  // Stone
    ];

    <?php if (!empty($deviceStats)): ?>
    // Device/OS Pie Chart
    const deviceCtx = document.getElementById('deviceChart');
    if (deviceCtx) {
        const deviceData = <?php
            $labels = array_map(fn($d) => $d['device_os'], $deviceStats);
            $values = array_map(fn($d) => (int)$d['unique_visitors'], $deviceStats);
            echo json_encode(['labels' => $labels, 'values' => $values]);
        ?>;

        new Chart(deviceCtx, {
            type: 'doughnut',
            data: {
                labels: deviceData.labels,
                datasets: [{
                    data: deviceData.values,
                    backgroundColor: deviceColors.slice(0, deviceData.labels.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            padding: 8,
                            font: { size: 11 }
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>

    // Browser colors
    const browserColors = [
        '#4285f4', // Chrome blue
        '#ff5722', // Firefox orange
        '#0078d7', // Edge blue
        '#00b4d8', // Safari cyan
        '#ff0000', // Opera red
        '#f4b400', // IE yellow
        '#7b68ee', // Other purple
        '#64748b', // Slate
        '#78716c', // Stone
        '#94a3b8'  // Light slate
    ];

    <?php if (!empty($browserStats)): ?>
    // Browser Pie Chart
    const browserCtx = document.getElementById('browserChart');
    if (browserCtx) {
        const browserData = <?php
            $labels = array_map(fn($b) => $b['browser'], $browserStats);
            $values = array_map(fn($b) => (int)$b['unique_visitors'], $browserStats);
            echo json_encode(['labels' => $labels, 'values' => $values]);
        ?>;

        new Chart(browserCtx, {
            type: 'doughnut',
            data: {
                labels: browserData.labels,
                datasets: [{
                    data: browserData.values,
                    backgroundColor: browserColors.slice(0, browserData.labels.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            padding: 8,
                            font: { size: 11 }
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>

    // HTTP status colors by type
    const httpColorMap = {
        'success': '#22c55e',      // Green for 2xx
        'redirect': '#3b82f6',     // Blue for 3xx
        'client_error': '#f97316', // Orange for 4xx
        'server_error': '#ef4444', // Red for 5xx
        'unknown': '#94a3b8'       // Gray for unknown
    };

    <?php if (!empty($httpStats)): ?>
    // HTTP Status Codes Pie Chart
    const httpCtx = document.getElementById('httpChart');
    if (httpCtx) {
        const httpData = <?php
            $labels = array_map(fn($s) => $s['status_code'] . ' ' . $s['label'], $httpStats);
            $values = array_map(fn($s) => (int)$s['count'], $httpStats);
            $types = array_map(fn($s) => $s['type'], $httpStats);
            echo json_encode(['labels' => $labels, 'values' => $values, 'types' => $types]);
        ?>;

        // Generate colors based on status type
        const httpColors = httpData.types.map(type => httpColorMap[type] || httpColorMap.unknown);

        new Chart(httpCtx, {
            type: 'doughnut',
            data: {
                labels: httpData.labels,
                datasets: [{
                    data: httpData.values,
                    backgroundColor: httpColors,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            padding: 8,
                            font: { size: 11 }
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>

    <?php if (!empty($chartStats) || !empty($chartBotStats)): ?>
    // Visitors Line Chart
    const dailyCtx = document.getElementById('dailyVisitorsChart');
    if (dailyCtx) {
        const chartData = <?php
            if ($chartType === 'hourly') {
                // Fill in all 24 hours (or up to current hour for today)
                $maxHour = ($period === 'yesterday') ? 23 : (int)date('G');
                $hourRange = [];
                for ($h = 0; $h <= $maxHour; $h++) {
                    $hourRange[$h] = ['visitors' => 0, 'views' => 0, 'bots' => 0];
                }
                foreach ($chartStats as $row) {
                    $h = (int)$row['hour'];
                    if (isset($hourRange[$h])) {
                        $hourRange[$h]['visitors'] = (int)$row['unique_visitors'];
                        $hourRange[$h]['views'] = (int)$row['page_views'];
                    }
                }
                foreach ($chartBotStats as $row) {
                    $h = (int)$row['hour'];
                    if (isset($hourRange[$h])) {
                        $hourRange[$h]['bots'] = (int)$row['unique_visitors'];
                    }
                }
                $labels = array_map(fn($h) => date('ga', strtotime("$h:00")), array_keys($hourRange));
                $visitors = array_map(fn($d) => $d['visitors'], array_values($hourRange));
                $views = array_map(fn($d) => $d['views'], array_values($hourRange));
                $bots = array_map(fn($d) => $d['bots'], array_values($hourRange));
            } else {
                // Daily data - fill in missing dates
                $endDate = new DateTime();
                $startDate = (new DateTime())->modify('-' . ($chartDays - 1) . ' days');
                $dateRange = [];
                $currentDate = clone $startDate;
                while ($currentDate <= $endDate) {
                    $dateRange[$currentDate->format('Y-m-d')] = ['visitors' => 0, 'views' => 0, 'bots' => 0];
                    $currentDate->modify('+1 day');
                }
                foreach ($chartStats as $day) {
                    if (isset($dateRange[$day['date']])) {
                        $dateRange[$day['date']]['visitors'] = (int)$day['unique_visitors'];
                        $dateRange[$day['date']]['views'] = (int)$day['page_views'];
                    }
                }
                foreach ($chartBotStats as $day) {
                    if (isset($dateRange[$day['date']])) {
                        $dateRange[$day['date']]['bots'] = (int)$day['unique_visitors'];
                    }
                }
                $labels = array_map(fn($d) => date('M j', strtotime($d)), array_keys($dateRange));
                $visitors = array_map(fn($d) => $d['visitors'], array_values($dateRange));
                $views = array_map(fn($d) => $d['views'], array_values($dateRange));
                $bots = array_map(fn($d) => $d['bots'], array_values($dateRange));
            }
            echo json_encode(['labels' => $labels, 'visitors' => $visitors, 'views' => $views, 'bots' => $bots]);
        ?>;

        new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [
                    {
                        label: 'Page Views',
                        data: chartData.views,
                        borderColor: '#3498DB',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 6,
                        pointHoverBackgroundColor: '#3498DB'
                    },
                    {
                        label: 'Unique Visitors',
                        data: chartData.visitors,
                        borderColor: '#FF6B9D',
                        backgroundColor: 'rgba(255, 107, 157, 0.15)',
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 6,
                        pointHoverBackgroundColor: '#FF6B9D'
                    },
                    {
                        label: 'Bots',
                        data: chartData.bots,
                        borderColor: '#64748b',
                        backgroundColor: 'rgba(100, 116, 139, 0.05)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: '#64748b',
                        borderDash: [5, 3]
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            boxWidth: 12,
                            padding: 20,
                            font: { size: 12 }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.85)',
                        padding: 12,
                        titleFont: { size: 13, weight: 'bold' },
                        bodyFont: { size: 12 },
                        displayColors: true,
                        boxPadding: 4
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: { size: 10 },
                            maxRotation: 0,
                            autoSkip: true,
                            maxTicksLimit: 12
                        }
                    },
                    y: {
                        position: 'left',
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.12)',
                            lineWidth: 1
                        },
                        ticks: {
                            font: { size: 11 },
                            precision: 0,
                            padding: 8
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>
});
</script>
