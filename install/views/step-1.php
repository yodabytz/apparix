<div class="card">
    <h1 class="card-title">Welcome to Apparix</h1>
    <p class="card-description">Let's check if your server meets the requirements.</p>

    <ul class="requirements-list">
        <?php foreach ($requirements['requirements'] as $req): ?>
            <li>
                <div class="req-name"><?php echo htmlspecialchars($req['name']); ?></div>
                <div class="req-status <?php echo $req['passed'] ? 'req-passed' : 'req-failed'; ?>">
                    <?php echo htmlspecialchars($req['current']); ?>
                    <?php if (!$req['passed']): ?>
                        <span>(Required: <?php echo htmlspecialchars($req['required']); ?>)</span>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>

    <form method="POST" action="/install?step=1">
        <div class="form-actions">
            <div></div>
            <?php if ($requirements['passed']): ?>
                <button type="submit" class="btn btn-primary btn-lg">Continue</button>
            <?php else: ?>
                <button type="button" class="btn btn-secondary btn-lg" disabled>Fix Issues to Continue</button>
            <?php endif; ?>
        </div>
    </form>
</div>
