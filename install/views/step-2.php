<div class="card">
    <h1 class="card-title">System Requirements</h1>
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

    <?php if (!$requirements['passed']): ?>
        <h2>Prepare Your Server</h2>
        <p>On Debian or Ubuntu, run this from your Apparix directory to preview missing dependencies:</p>
        <pre style="white-space:pre-wrap;overflow-wrap:anywhere">bash tools/setup-server.sh --php=<?php echo PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION; ?></pre>
        <p>To apply the plan, your server administrator can run the command below. Use your actual PHP worker user in place of www-data.</p>
        <pre style="white-space:pre-wrap;overflow-wrap:anywhere">sudo bash tools/setup-server.sh --php=<?php echo PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION; ?> --owner=www-data --fix-permissions --apply</pre>
        <p>Changes require confirmation. The browser never runs sudo. See docs/server-setup.md for Docker, shared hosting, optional server packages, and scheduled tasks.</p>
        <a href="/install?step=2" class="btn btn-secondary">Recheck Requirements</a>
    <?php endif; ?>

    <form method="POST" action="/install?step=2">
        <div class="form-actions">
            <a href="/install?step=1" class="btn btn-secondary">Back</a>
            <?php if ($requirements['passed']): ?>
                <button type="submit" class="btn btn-primary btn-lg">Continue</button>
            <?php else: ?>
                <button type="button" class="btn btn-secondary btn-lg" disabled>Fix Issues to Continue</button>
            <?php endif; ?>
        </div>
    </form>
</div>
