<div class="admin-reviews">
    <div class="page-header">
        <h1>Product Reviews</h1>
    </div>

    <!-- Stats Cards -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-value"><?php echo $counts['pending'] ?? 0; ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $counts['approved'] ?? 0; ?></div>
            <div class="stat-label">Approved</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $counts['total'] ?? 0; ?></div>
            <div class="stat-label">Total</div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <a href="/admin/reviews" class="tab <?php echo !$currentStatus ? 'active' : ''; ?>">All</a>
        <a href="/admin/reviews?status=pending" class="tab <?php echo $currentStatus === 'pending' ? 'active' : ''; ?>">
            Pending
            <?php if (($counts['pending'] ?? 0) > 0): ?>
                <span class="badge"><?php echo $counts['pending']; ?></span>
            <?php endif; ?>
        </a>
        <a href="/admin/reviews?status=approved" class="tab <?php echo $currentStatus === 'approved' ? 'active' : ''; ?>">Approved</a>
    </div>

    <!-- Reviews Table -->
    <?php if (empty($reviews)): ?>
        <div class="empty-state">
            <p>No reviews found.</p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Customer</th>
                        <th>Rating</th>
                        <th>Review</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reviews as $review): ?>
                        <tr class="<?php echo $review['is_featured'] ? 'featured' : ''; ?>">
                            <td>
                                <a href="/products/<?php echo escape($review['product_slug']); ?>" target="_blank">
                                    <?php echo escape($review['product_name']); ?>
                                </a>
                            </td>
                            <td>
                                <?php echo escape($review['first_name'] . ' ' . $review['last_name']); ?><br>
                                <small><?php echo escape($review['email']); ?></small>
                            </td>
                            <td class="rating-col">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>">&#9733;</span>
                                <?php endfor; ?>
                            </td>
                            <td class="review-content">
                                <?php if ($review['title']): ?>
                                    <strong><?php echo escape($review['title']); ?></strong><br>
                                <?php endif; ?>
                                <?php echo escape(substr($review['review_text'] ?? '', 0, 150)); ?>
                                <?php if (strlen($review['review_text'] ?? '') > 150): ?>...<?php endif; ?>
                            </td>
                            <td>
                                <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                            </td>
                            <td>
                                <?php if ($review['is_approved']): ?>
                                    <span class="status-badge approved">Approved</span>
                                <?php else: ?>
                                    <span class="status-badge pending">Pending</span>
                                <?php endif; ?>
                                <?php if ($review['is_featured']): ?>
                                    <span class="status-badge featured">Featured</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions-col">
                                <?php if (!$review['is_approved']): ?>
                                    <form action="/admin/reviews/approve" method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo escape($_SESSION['csrf_token'] ?? ''); ?>">
                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                            &#10003;
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <form action="/admin/reviews/toggle-featured" method="POST" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo escape($_SESSION['csrf_token'] ?? ''); ?>">
                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                    <button type="submit" class="btn btn-sm <?php echo $review['is_featured'] ? 'btn-warning' : 'btn-secondary'; ?>" title="<?php echo $review['is_featured'] ? 'Unfeature' : 'Feature'; ?>">
                                        &#9733;
                                    </button>
                                </form>

                                <form action="/admin/reviews/reject" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this review?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo escape($_SESSION['csrf_token'] ?? ''); ?>">
                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                        &#10005;
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
.stats-row {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #fff;
    padding: 20px 30px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    text-align: center;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: #ec4899;
}

.stat-label {
    color: #666;
    font-size: 0.9rem;
}

.filter-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.filter-tabs .tab {
    padding: 10px 20px;
    background: #f3f4f6;
    border-radius: 8px;
    text-decoration: none;
    color: #666;
    font-weight: 500;
    transition: all 0.2s;
}

.filter-tabs .tab:hover {
    background: #e5e7eb;
}

.filter-tabs .tab.active {
    background: #ec4899;
    color: #fff;
}

.filter-tabs .badge {
    background: #fff;
    color: #ec4899;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.8rem;
    margin-left: 5px;
}

.rating-col .star {
    color: #d1d5db;
}

.rating-col .star.filled {
    color: #fbbf24;
}

.review-content {
    max-width: 300px;
    font-size: 0.9rem;
    line-height: 1.4;
}

.status-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-badge.approved {
    background: #dcfce7;
    color: #16a34a;
}

.status-badge.pending {
    background: #fef3c7;
    color: #d97706;
}

.status-badge.featured {
    background: #fce7f3;
    color: #ec4899;
    margin-top: 5px;
}

.actions-col {
    white-space: nowrap;
}

.btn-sm {
    padding: 5px 10px;
    font-size: 0.9rem;
    margin: 0 2px;
}

.btn-success {
    background: #16a34a;
    color: #fff;
    border: none;
}

.btn-warning {
    background: #fbbf24;
    color: #000;
    border: none;
}

tr.featured {
    background: #fdf2f8;
}

.empty-state {
    text-align: center;
    padding: 60px;
    background: #f9fafb;
    border-radius: 10px;
    color: #666;
}
</style>
