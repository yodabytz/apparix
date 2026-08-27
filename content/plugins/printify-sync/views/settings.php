<div class="printify-sync-settings">
    <div class="printify-toolbar">
        <div>
            <h3>Pull Products From Printify</h3>
            <p>Select products from your Printify shop and import them into Apparix with variants and fulfillment mappings.</p>
        </div>
        <button type="submit" class="btn btn-primary">Save Connection</button>
    </div>

    <section class="printify-panel">
        <div class="panel-heading">
            <h4>Connection</h4>
            <span class="status-pill <?php echo !empty($settings['api_token']) && !empty($settings['shop_id']) ? 'ok' : 'warn'; ?>">
                <?php echo !empty($settings['api_token']) && !empty($settings['shop_id']) ? 'Connected' : 'Needs setup'; ?>
            </span>
        </div>

        <div class="connection-grid">
            <div class="form-group">
                <label for="api_token">Personal Access Token <span class="required">*</span></label>
                <input type="password" id="api_token" name="settings[api_token]"
                       value="<?php echo escape($settings['api_token'] ?? ''); ?>"
                       class="form-control" autocomplete="off" required>
            </div>

            <div class="form-group">
                <label for="shop_id">Printify Shop ID <span class="required">*</span></label>
                <input type="text" id="shop_id" name="settings[shop_id]"
                       value="<?php echo escape($settings['shop_id'] ?? ''); ?>"
                       class="form-control" inputmode="numeric" required>
            </div>
        </div>

        <div class="toggle-list">
            <label><input type="checkbox" name="settings[send_orders]" value="1" <?php echo !empty($settings['send_orders']) ? 'checked' : ''; ?>> Queue eligible paid orders for Printify fulfillment</label>
            <label><input type="checkbox" name="settings[sync_order_statuses]" value="1" <?php echo !empty($settings['sync_order_statuses']) ? 'checked' : ''; ?>> Automatically update order status, tracking, and customer notifications from Printify</label>
            <input type="hidden" name="settings[sync_inventory]" value="0">
            <label><input type="checkbox" name="settings[sync_inventory]" value="1" <?php echo !empty($settings['sync_inventory']) ? 'checked' : ''; ?>> Automatically sync Printify variant availability</label>
            <label><input type="checkbox" name="settings[sync_price]" value="1" <?php echo !empty($settings['sync_price']) ? 'checked' : ''; ?>> Keep imported Printify prices</label>
        </div>

        <div class="form-group inventory-sync-interval">
            <label for="inventory_sync_interval_hours">Inventory sync interval</label>
            <select id="inventory_sync_interval_hours" name="settings[inventory_sync_interval_hours]" class="form-control">
                <?php $inventoryInterval = (int)($settings['inventory_sync_interval_hours'] ?? 1); ?>
                <option value="1" <?php echo $inventoryInterval === 1 ? 'selected' : ''; ?>>Every hour</option>
                <option value="6" <?php echo $inventoryInterval === 6 ? 'selected' : ''; ?>>Every 6 hours</option>
                <option value="24" <?php echo $inventoryInterval === 24 ? 'selected' : ''; ?>>Nightly</option>
            </select>
        </div>

        <?php if (!empty($shops) && is_array($shops)): ?>
            <div class="printify-shops">
                <strong>Connected shop</strong>
                <?php foreach ($shops as $shop): ?>
                    <span><?php echo escape(($shop['title'] ?? 'Untitled') . ' #' . ($shop['id'] ?? '')); ?></span>
                <?php endforeach; ?>
            </div>
        <?php elseif (!empty($connectionError)): ?>
            <div class="alert alert-warning"><?php echo escape($connectionError); ?></div>
        <?php endif; ?>
    </section>

    <section class="printify-panel products-panel">
        <div class="panel-heading action-heading">
            <div>
                <h4>Printify Products</h4>
                <p class="muted">Choose products to pull into Lily's Pad. Existing imported products are updated and kept linked.</p>
            </div>
            <button type="submit" name="action" value="import_printify_products" class="btn btn-primary">
                Import Selected To Apparix
            </button>
            <input type="hidden" name="printify_page" value="<?php echo (int)($printifyPage ?? 1); ?>">
        </div>

        <div class="printify-search">
            <label for="printify_search">Search Printify products</label>
            <div class="printify-search-row">
                <input type="search" id="printify_search" class="form-control" value="<?php echo escape($printifySearch ?? ''); ?>" placeholder="Search by product name, SKU, color, size, or Printify ID">
                <button type="button" class="btn btn-outline" onclick="searchPrintifyProducts()">Search</button>
                <?php if (!empty($printifySearch)): ?>
                    <a class="btn btn-secondary" href="/admin/plugins/settings?slug=printify-sync">Clear</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($printifyProductsError)): ?>
            <div class="alert alert-warning"><?php echo escape($printifyProductsError); ?></div>
        <?php elseif (empty($printifyProducts)): ?>
            <div class="empty-printify-state">
                <?php if (empty($settings['api_token']) || empty($settings['shop_id'])): ?>
                    Save the token and shop ID, then reopen this page to load Printify products.
                <?php else: ?>
                    No Printify products were returned for this shop.
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php
                $currentPage = (int)($printifyPagination['current_page'] ?? $printifyPage ?? 1);
                $lastPage = (int)($printifyPagination['last_page'] ?? $currentPage);
                $from = (int)($printifyPagination['from'] ?? 0);
                $to = (int)($printifyPagination['to'] ?? count($printifyProducts));
                $total = (int)($printifyPagination['total'] ?? count($printifyProducts));
                $basePageUrl = '/admin/plugins/settings?slug=printify-sync' . (!empty($printifySearch) ? '&printify_search=' . urlencode((string)$printifySearch) : '') . '&printify_page=';
            ?>
            <div class="printify-pagination">
                <span><?php echo !empty($printifySearch) ? 'Search results: ' : 'Showing '; ?><?php echo $from; ?>-<?php echo $to; ?> of <?php echo $total; ?> products</span>
                <span class="page-links">
                    <?php if ($currentPage > 1): ?>
                        <a class="btn btn-secondary" href="<?php echo escape($basePageUrl . ($currentPage - 1)); ?>">Previous</a>
                    <?php endif; ?>
                    <strong>Page <?php echo $currentPage; ?> of <?php echo max(1, $lastPage); ?></strong>
                    <?php if ($currentPage < $lastPage): ?>
                        <a class="btn btn-secondary" href="<?php echo escape($basePageUrl . ($currentPage + 1)); ?>">Next</a>
                    <?php endif; ?>
                </span>
            </div>
            <div class="printify-product-list">
                <?php foreach ($printifyProducts as $printifyProduct): ?>
                    <?php
                        $printifyId = (string)($printifyProduct['id'] ?? '');
                        $title = (string)($printifyProduct['title'] ?? 'Untitled product');
                        $status = (string)($printifyProduct['visible'] ?? $printifyProduct['status'] ?? '');
                        $variants = $printifyProduct['variants'] ?? [];
                        $selectedVariantCount = count(array_filter($variants, fn($variant) => !empty($variant['is_enabled'])));
                        $possibleVariantCount = count($variants);
                        $syncInfo = $syncedPrintifyProducts[$printifyId] ?? null;
                        $image = '';
                        foreach (($printifyProduct['images'] ?? []) as $img) {
                            if (!empty($img['src'])) { $image = $img['src']; break; }
                        }
                    ?>
                    <label class="printify-product-row">
                        <input type="checkbox" name="printify_product_ids[]" value="<?php echo escape($printifyId); ?>">
                        <?php if ($image): ?>
                            <img src="<?php echo escape($image); ?>" alt="">
                        <?php else: ?>
                            <span class="no-image"></span>
                        <?php endif; ?>
                        <span class="product-copy">
                            <strong><?php echo escape($title); ?></strong>
                            <?php if ($syncInfo): ?>
                                <span class="synced-star" title="Already synced to Apparix<?php echo !empty($syncInfo['name']) ? ': ' . escape($syncInfo['name']) : ''; ?>">★ Synced</span>
                                <button type="submit" name="action" value="resync_printify_product:<?php echo escape($printifyId); ?>" class="btn btn-sm btn-outline resync-printify-btn" title="Update this existing Apparix product from Printify">Resync</button>
                            <?php endif; ?>
                            <small>
                                Printify <?php echo escape($printifyId); ?>
                                &middot; <?php echo $selectedVariantCount; ?> selected variant<?php echo $selectedVariantCount === 1 ? '' : 's'; ?>
                                <?php if ($possibleVariantCount > $selectedVariantCount): ?>
                                    of <?php echo $possibleVariantCount; ?> possible
                                <?php endif; ?>
                                <?php if ($status !== ''): ?>&middot; <?php echo escape($status); ?><?php endif; ?>
                                <?php if ($syncInfo): ?>
                                    &middot; Product #<?php echo (int)$syncInfo['product_id']; ?>
                                    <?php if (!empty($syncInfo['last_synced_at'])): ?>
                                        &middot; Synced <?php echo escape(date('M j, Y', strtotime((string)$syncInfo['last_synced_at']))); ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </small>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<style>
.printify-sync-settings { display: grid; gap: 1rem; }
.printify-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem; background: #f8fafc; border: 1px solid #dbe3ea; border-radius: 8px; }
.printify-toolbar h3 { margin: 0 0 0.25rem; font-size: 1.15rem; color: #12211a; }
.printify-toolbar p, .muted { margin: 0; color: #64748b; font-size: 0.9rem; }
.printify-panel { padding: 1rem; border: 1px solid #dbe3ea; border-radius: 8px; background: #fff; }
.panel-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
.panel-heading h4 { margin: 0; color: #1f8f49; font-size: 1rem; }
.required { color: #c2410c; }
.status-pill { display: inline-flex; align-items: center; height: 24px; padding: 0 0.55rem; border-radius: 999px; font-size: 0.78rem; font-weight: 700; }
.status-pill.ok { background: #dcfce7; color: #166534; }
.status-pill.warn { background: #fff7ed; color: #9a3412; }
.connection-grid { display: grid; grid-template-columns: minmax(0, 1fr) 220px; gap: 1rem; }
.toggle-list { display: grid; gap: 0.6rem; margin-top: 0.5rem; }
.toggle-list label { display: flex; align-items: center; gap: 0.5rem; color: #334155; }
.printify-shops { display: grid; gap: 0.25rem; margin-top: 0.75rem; padding: 0.75rem; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; }
.products-panel { padding-bottom: 1rem; }
.printify-search { display: grid; gap: 0.45rem; margin-bottom: 1rem; }
.printify-search label { font-weight: 700; color: #334155; }
.printify-search-row { display: grid; grid-template-columns: minmax(0, 1fr) auto auto; gap: 0.5rem; align-items: center; }
.printify-pagination { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; margin-bottom: 0.75rem; color: #475569; font-size: 0.9rem; }
.printify-pagination .page-links { display: flex; align-items: center; gap: 0.5rem; }
.printify-product-list { display: grid; gap: 0.6rem; max-height: 620px; overflow: auto; }
.printify-product-row { display: grid; grid-template-columns: auto 56px minmax(0, 1fr); gap: 0.75rem; align-items: center; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer; }
.printify-product-row:hover { background: #f8fafc; }
.printify-product-row img, .no-image { width: 56px; height: 56px; border-radius: 6px; object-fit: cover; background: #eef2f7; }
.product-copy strong { display: inline-block; color: #0f172a; margin-right: 0.45rem; }
.resync-printify-btn { margin-left: 0.35rem; padding: 0.2rem 0.55rem; line-height: 1.2; vertical-align: middle; }
.synced-star { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.15rem 0.45rem; border-radius: 999px; background: #dbeafe; color: #1d4ed8; font-size: 0.74rem; font-weight: 800; vertical-align: middle; }
.product-copy small { display: block; color: #64748b; margin-top: 0.15rem; }
.empty-printify-state { padding: 2rem; text-align: center; color: #64748b; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; }
@media (max-width: 900px) { .printify-toolbar, .action-heading, .printify-pagination { flex-direction: column; align-items: stretch; } .printify-search-row { grid-template-columns: 1fr; } .connection-grid { grid-template-columns: 1fr; } .printify-pagination .page-links { justify-content: space-between; } }
</style>

<script>
function searchPrintifyProducts() {
    const input = document.getElementById('printify_search');
    const params = new URLSearchParams(window.location.search);
    params.set('slug', 'printify-sync');
    params.delete('printify_page');
    const query = input ? input.value.trim() : '';
    if (query) {
        params.set('printify_search', query);
    } else {
        params.delete('printify_search');
    }
    window.location.href = '/admin/plugins/settings?' + params.toString();
}
</script>
