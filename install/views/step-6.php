<div class="card">
    <h1 class="card-title">Choose a Theme</h1>
    <p class="card-description">Select a theme to get started. You can customize colors and layouts later.</p>

    <form method="POST" action="/install?step=6" id="theme-form">
        <input type="hidden" name="theme" id="selected-theme" value="<?php echo htmlspecialchars($_SESSION['install']['theme'] ?? 'boutique'); ?>">

        <div class="theme-grid">
            <?php foreach ($themes as $slug => $theme): ?>
                <div class="theme-card <?php echo ($_SESSION['install']['theme'] ?? 'boutique') === $slug ? 'selected' : ''; ?>"
                     data-theme="<?php echo htmlspecialchars($slug); ?>">
                    <div class="theme-preview" style="background: linear-gradient(135deg, <?php echo htmlspecialchars($theme['color']); ?> 0%, <?php echo htmlspecialchars($theme['color']); ?>88 100%);"></div>
                    <div class="theme-name"><?php echo htmlspecialchars($theme['name']); ?></div>
                    <div class="theme-desc"><?php echo htmlspecialchars($theme['description']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="form-actions">
            <a href="/install?step=5" class="btn btn-secondary">Back</a>
            <button type="submit" class="btn btn-primary btn-lg">Continue</button>
        </div>
    </form>
</div>

<script>
document.querySelectorAll('.theme-card').forEach(function(card) {
    card.addEventListener('click', function() {
        // Remove selected from all
        document.querySelectorAll('.theme-card').forEach(function(c) {
            c.classList.remove('selected');
        });

        // Add selected to clicked
        this.classList.add('selected');

        // Update hidden input
        document.getElementById('selected-theme').value = this.dataset.theme;
    });
});
</script>
