<!-- My Downloads Page -->
<section class="downloads-section">
    <div class="container">
        <div class="page-header">
            <h1>My Downloads</h1>
            <a href="/account" class="back-link">Back to Account</a>
        </div>

        <?php if ($error = getFlash('error')): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success = getFlash('success')): ?>
            <div class="alert alert-success"><?php echo escape($success); ?></div>
        <?php endif; ?>

        <?php if (empty($downloads)): ?>
            <div class="empty-state">
                <h2>No Downloads Yet</h2>
                <p>You haven't purchased any digital products yet. Browse our products to find something you'll love!</p>
                <a href="/products" class="btn btn-primary">Browse Products</a>
            </div>
        <?php else: ?>
            <?php if (!empty($licenses)): ?>
            <div class="downloads-card">
                <h2>License Keys</h2>
                <div class="license-list">
                    <?php foreach ($licenses as $license): ?>
                    <div class="license-item">
                        <div class="license-info">
                            <strong><?php echo escape($license['product_name']); ?></strong>
                            <?php if (!empty($license['edition_code'])): ?>
                                <span class="edition-badge">
                                    <?php echo \App\Models\OrderLicense::getEditionName($license['edition_code']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="license-key-box">
                            <code><?php echo escape($license['license_key']); ?></code>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="downloads-card">
                <h2>Download Files</h2>
                <div class="download-list">
                    <?php foreach ($downloads as $download): ?>
                    <div class="download-item">
                        <div class="download-info">
                            <div class="download-product">
                                <strong><?php echo escape($download['product_name']); ?></strong>
                                <span class="order-ref">Order #<?php echo escape($download['order_number']); ?></span>
                            </div>
                            <div class="download-meta">
                                <?php
                                $isExpired = $download['expires_at'] && strtotime($download['expires_at']) < time();
                                $isLimitReached = $download['max_downloads'] !== null && $download['download_count'] >= $download['max_downloads'];
                                ?>
                                <span class="meta-item">
                                    Downloads: <?php echo $download['download_count']; ?><?php echo $download['max_downloads'] ? '/' . $download['max_downloads'] : ' (Unlimited)'; ?>
                                </span>
                                <?php if ($download['expires_at']): ?>
                                <span class="meta-item">
                                    Expires: <?php echo date('M j, Y', strtotime($download['expires_at'])); ?>
                                </span>
                                <?php endif; ?>
                                <span class="status-badge <?php
                                    if ($isExpired) echo 'status-expired';
                                    elseif ($isLimitReached) echo 'status-limit';
                                    else echo 'status-active';
                                ?>">
                                    <?php
                                    if ($isExpired) echo 'Expired';
                                    elseif ($isLimitReached) echo 'Limit Reached';
                                    else echo 'Active';
                                    ?>
                                </span>
                            </div>
                        </div>
                        <div class="download-action">
                            <?php if (!$isExpired && !$isLimitReached): ?>
                                <a href="/download/<?php echo escape($download['download_token']); ?>/file" class="btn btn-primary btn-download">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                                    </svg>
                                    Download
                                </a>
                            <?php elseif ($isExpired): ?>
                                <span class="btn btn-disabled">Expired</span>
                            <?php else: ?>
                                <span class="btn btn-disabled">Limit Reached</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="downloads-help">
                <p>Need help with your downloads? <a href="/contact">Contact support</a> for assistance.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.downloads-section {
    padding: 40px 0 80px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.page-header h1 {
    margin: 0;
}

.back-link {
    color: var(--primary-color, #FF68C5);
    text-decoration: none;
    font-weight: 500;
}

.back-link:hover {
    text-decoration: underline;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}

.empty-state h2 {
    color: #333;
    margin-bottom: 12px;
}

.empty-state p {
    color: #666;
    margin-bottom: 24px;
}

.downloads-card {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    margin-bottom: 24px;
}

.downloads-card h2 {
    font-size: 1.25rem;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid #eee;
}

.license-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.license-item {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 8px;
    padding: 16px;
}

.license-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.edition-badge {
    background: #166534;
    color: #fff;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.license-key-box {
    background: #1e293b;
    color: #22c55e;
    font-family: monospace;
    padding: 12px;
    border-radius: 6px;
    font-size: 0.95rem;
    word-break: break-all;
    user-select: all;
}

.download-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.download-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    gap: 16px;
}

.download-info {
    flex: 1;
}

.download-product {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
    flex-wrap: wrap;
}

.order-ref {
    font-size: 0.85rem;
    color: #64748b;
    font-family: monospace;
}

.download-meta {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.meta-item {
    font-size: 0.85rem;
    color: #64748b;
}

.status-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-expired {
    background: #f8d7da;
    color: #721c24;
}

.status-limit {
    background: #fff3cd;
    color: #856404;
}

.btn-download {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
}

.btn-disabled {
    display: inline-block;
    padding: 10px 20px;
    background: #e2e8f0;
    color: #94a3b8;
    border-radius: 8px;
    font-size: 0.9rem;
    cursor: not-allowed;
}

.downloads-help {
    text-align: center;
    margin-top: 30px;
    color: #64748b;
    font-size: 0.9rem;
}

.downloads-help a {
    color: var(--primary-color, #FF68C5);
    text-decoration: none;
}

.downloads-help a:hover {
    text-decoration: underline;
}

.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-error {
    background: #fee;
    color: #c00;
    border: 1px solid #fcc;
}

.alert-success {
    background: #efe;
    color: #060;
    border: 1px solid #cfc;
}

@media (max-width: 600px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .download-item {
        flex-direction: column;
        align-items: flex-start;
    }

    .download-action {
        width: 100%;
    }

    .download-action .btn {
        width: 100%;
        text-align: center;
    }

    .license-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }

    .download-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }
}
</style>
