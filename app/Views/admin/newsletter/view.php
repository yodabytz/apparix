<div class="page-header">
    <h1>Newsletter: <?php echo escape($newsletter['subject']); ?></h1>
    <a href="/admin/newsletter" class="btn btn-outline">Back to Newsletter</a>
</div>

<div class="newsletter-details">
    <div class="admin-card">
        <div class="detail-row">
            <span class="detail-label">Subject:</span>
            <span class="detail-value"><?php echo escape($newsletter['subject']); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Sent By:</span>
            <span class="detail-value"><?php echo escape($newsletter['sent_by_name'] ?? 'Unknown'); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Recipients:</span>
            <span class="detail-value"><?php echo number_format($newsletter['recipient_count']); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Sent At:</span>
            <span class="detail-value"><?php echo date('F j, Y \a\t g:i A', strtotime($newsletter['sent_at'])); ?></span>
        </div>
    </div>

    <div class="admin-card">
        <h2>Content</h2>
        <div class="newsletter-content">
            <?php echo $newsletter['content']; ?>
        </div>
    </div>
</div>

<style>
.detail-row {
    display: flex;
    padding: 0.75rem 0;
    border-bottom: 1px solid #e5e7eb;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    font-weight: 500;
    width: 120px;
    color: #6b7280;
}

.detail-value {
    flex: 1;
}

.newsletter-content {
    background: #f9fafb;
    padding: 2rem;
    border-radius: 8px;
    margin-top: 1rem;
}

.newsletter-content img {
    max-width: 100%;
    height: auto;
}
</style>
