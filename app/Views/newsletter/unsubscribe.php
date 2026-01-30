<section class="page-section">
    <div class="container">
        <div class="unsubscribe-card">
            <?php if (isset($success) && $success): ?>
                <div class="success-icon">&#10003;</div>
                <h1>Unsubscribed</h1>
                <p><?php echo escape($message); ?></p>
                <p class="sub-text">We're sorry to see you go. You can always resubscribe from our website.</p>
            <?php elseif (isset($error)): ?>
                <div class="error-icon">&#10007;</div>
                <h1>Error</h1>
                <p><?php echo escape($error); ?></p>
            <?php else: ?>
                <div class="info-icon">&#9432;</div>
                <h1>Unsubscribe</h1>
                <p><?php echo escape($message ?? 'An error occurred'); ?></p>
            <?php endif; ?>

            <a href="/" class="btn btn-primary">Back to Home</a>
        </div>
    </div>
</section>

<style>
.unsubscribe-card {
    max-width: 500px;
    margin: 4rem auto;
    text-align: center;
    background: white;
    padding: 3rem;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.success-icon, .error-icon, .info-icon {
    font-size: 4rem;
    margin-bottom: 1.5rem;
}

.success-icon {
    color: #28a745;
}

.error-icon {
    color: #dc3545;
}

.info-icon {
    color: #6b7280;
}

.unsubscribe-card h1 {
    font-size: 1.75rem;
    margin-bottom: 1rem;
}

.unsubscribe-card p {
    color: #6b7280;
    margin-bottom: 1rem;
}

.sub-text {
    font-size: 0.9rem;
    color: #9ca3af;
}

.unsubscribe-card .btn {
    margin-top: 1.5rem;
}
</style>
