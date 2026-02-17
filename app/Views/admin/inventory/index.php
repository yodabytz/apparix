<div class="page-header">
    <h1>Inventory Import</h1>
    <a href="/admin/inventory/template" class="btn btn-outline">Download CSV Template</a>
</div>

<!-- Stats Cards -->
<div class="stats-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
    <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="font-size: 2rem; font-weight: 700; color: var(--admin-primary);"><?php echo (int)($stats['products_with_sku'] ?? 0); ?></div>
        <div style="color: var(--admin-text-light); font-size: 0.9rem;">Products with SKU</div>
    </div>
    <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="font-size: 2rem; font-weight: 700; color: var(--admin-primary);"><?php echo (int)($stats['variants_with_sku'] ?? 0); ?></div>
        <div style="color: var(--admin-text-light); font-size: 0.9rem;">Variants with SKU</div>
    </div>
    <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="font-size: 2rem; font-weight: 700; color: var(--admin-primary);"><?php echo (int)($stats['total_product_stock'] ?? 0); ?></div>
        <div style="color: var(--admin-text-light); font-size: 0.9rem;">Total Product Stock</div>
    </div>
    <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="font-size: 2rem; font-weight: 700; color: var(--admin-primary);"><?php echo (int)($stats['total_variant_stock'] ?? 0); ?></div>
        <div style="color: var(--admin-text-light); font-size: 0.9rem;">Total Variant Stock</div>
    </div>
</div>

<!-- Upload Form -->
<div class="card" style="margin-bottom: 2rem;">
    <h3 class="card-title" style="margin-bottom: 1rem;">Upload Inventory CSV</h3>

    <form action="/admin/inventory/import" method="POST" enctype="multipart/form-data" id="importForm">
        <?php echo csrfField(); ?>

        <div class="form-group">
            <label class="form-label">CSV File</label>
            <input type="file" name="csv_file" id="csvFile" accept=".csv" class="form-input" required style="padding: 0.75rem;">
            <p style="font-size: 0.8rem; color: var(--admin-text-light); margin-top: 0.5rem;">
                CSV must have a <strong>SKU</strong> column and a stock column (like <strong>Total in stock</strong>, <strong>stock</strong>, <strong>quantity</strong>, or <strong>qty</strong>).
            </p>
        </div>

        <div class="form-group" style="margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary" id="importBtn">
                Upload & Update Inventory
            </button>
        </div>
    </form>
</div>

<!-- Import Instructions -->
<div class="card" style="margin-bottom: 2rem; background: #f9fafb;">
    <h3 class="card-title" style="margin-bottom: 1rem;">How It Works</h3>
    <ul style="line-height: 1.8; color: var(--admin-text-light);">
        <li>Upload a CSV file with SKU and stock quantity columns</li>
        <li>The system matches SKUs from the CSV to your existing products and variants</li>
        <li><strong>Only existing items are updated</strong> - new SKUs are skipped</li>
        <li>Stock values: Numbers = exact quantity, "-" = 5 (supplier available), Empty/0 = out of stock</li>
        <li>Import history is logged below for reference</li>
    </ul>
</div>

<!-- Recent Imports -->
<div class="card">
    <h3 class="card-title" style="margin-bottom: 1rem;">Recent Imports</h3>

    <?php if (!empty($recentImports)): ?>
        <div class="table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>File</th>
                        <th>Updated</th>
                        <th>Not Found</th>
                        <th>Skipped</th>
                        <th>Errors</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentImports as $import): ?>
                        <tr>
                            <td><?php echo date('M j, Y g:i A', strtotime($import['created_at'])); ?></td>
                            <td><?php echo escape($import['filename']); ?></td>
                            <td>
                                <span class="badge" style="background: #dcfce7; color: #166534;">
                                    <?php echo (int)$import['updated_count']; ?> updated
                                </span>
                            </td>
                            <td>
                                <?php if ($import['not_found_count'] > 0): ?>
                                    <span class="badge" style="background: #fef3c7; color: #92400e;">
                                        <?php echo (int)$import['not_found_count']; ?> not found
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--admin-text-light);">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($import['skipped_count'] > 0): ?>
                                    <span class="badge badge-gray">
                                        <?php echo (int)$import['skipped_count']; ?> skipped
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--admin-text-light);">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($import['error_count'] > 0): ?>
                                    <span class="badge" style="background: #fee2e2; color: #991b1b;">
                                        <?php echo (int)$import['error_count']; ?> errors
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--admin-text-light);">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <p style="color: var(--admin-text-light);">No imports yet. Upload your first CSV file above.</p>
        </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('importForm').addEventListener('submit', function() {
    const btn = document.getElementById('importBtn');
    btn.disabled = true;
    btn.textContent = 'Importing...';
});
</script>
