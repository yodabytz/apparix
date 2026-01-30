<div class="card">
    <h1 class="card-title">Ready to Install</h1>
    <p class="card-description">Review your configuration and complete the installation.</p>

    <div style="background: #f9fafb; border-radius: 8px; padding: 20px; margin-bottom: 24px;">
        <h3 style="font-size: 1rem; margin-bottom: 16px;">Configuration Summary</h3>

        <div style="display: grid; gap: 12px;">
            <div>
                <strong>Database:</strong>
                <?php echo htmlspecialchars($_SESSION['install']['db_user'] ?? ''); ?>@<?php echo htmlspecialchars($_SESSION['install']['db_host'] ?? ''); ?>/<?php echo htmlspecialchars($_SESSION['install']['db_name'] ?? ''); ?>
            </div>
            <div>
                <strong>Store Name:</strong>
                <?php echo htmlspecialchars($_SESSION['install']['store_name'] ?? ''); ?>
            </div>
            <div>
                <strong>Admin Email:</strong>
                <?php echo htmlspecialchars($_SESSION['install']['admin_email'] ?? ''); ?>
            </div>
            <div>
                <strong>Theme:</strong>
                <?php echo ucfirst(htmlspecialchars($_SESSION['install']['theme'] ?? 'boutique')); ?>
            </div>
        </div>
    </div>

    <div style="background: #fef3cd; border-radius: 8px; padding: 16px; margin-bottom: 24px; color: #856404;">
        <strong>Note:</strong> This will create the database tables, generate a .env file, and create your admin account.
    </div>

    <form method="POST" action="/install?step=7" id="install-form">
        <div class="form-actions">
            <a href="/install?step=6" class="btn btn-secondary">Back</a>
            <button type="submit" class="btn btn-primary btn-lg" id="install-btn">Install Apparix</button>
        </div>
    </form>
</div>

<script>
document.getElementById('install-form').addEventListener('submit', function() {
    var btn = document.getElementById('install-btn');
    btn.disabled = true;
    btn.textContent = 'Installing...';
});
</script>
