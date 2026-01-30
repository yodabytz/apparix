<div class="card">
    <h1 class="card-title">Database Configuration</h1>
    <p class="card-description">Enter your MySQL database credentials.</p>

    <form method="POST" action="/install?step=2" id="db-form">
        <div class="form-row">
            <div class="form-group">
                <label for="db_host">Database Host</label>
                <input type="text" name="db_host" id="db_host" class="form-control"
                       value="<?php echo htmlspecialchars($_SESSION['install']['db_host'] ?? 'localhost'); ?>"
                       placeholder="localhost">
            </div>

            <div class="form-group">
                <label for="db_name">Database Name</label>
                <input type="text" name="db_name" id="db_name" class="form-control"
                       value="<?php echo htmlspecialchars($_SESSION['install']['db_name'] ?? ''); ?>"
                       placeholder="apparix_store" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="db_user">Database User</label>
                <input type="text" name="db_user" id="db_user" class="form-control"
                       value="<?php echo htmlspecialchars($_SESSION['install']['db_user'] ?? ''); ?>"
                       placeholder="root" required>
            </div>

            <div class="form-group">
                <label for="db_pass">Database Password</label>
                <input type="password" name="db_pass" id="db_pass" class="form-control"
                       value="<?php echo htmlspecialchars($_SESSION['install']['db_pass'] ?? ''); ?>"
                       placeholder="Enter password">
            </div>
        </div>

        <div class="form-group">
            <button type="button" class="btn btn-secondary" id="test-connection">Test Connection</button>
            <span id="connection-status" style="margin-left: 12px;"></span>
        </div>

        <div class="form-actions">
            <a href="/install?step=1" class="btn btn-secondary">Back</a>
            <button type="submit" class="btn btn-primary btn-lg">Continue</button>
        </div>
    </form>
</div>

<script>
document.getElementById('test-connection').addEventListener('click', function() {
    const form = document.getElementById('db-form');
    const status = document.getElementById('connection-status');
    const btn = this;

    btn.disabled = true;
    btn.textContent = 'Testing...';
    status.textContent = '';

    const formData = new FormData(form);

    fetch('/install?action=test-database', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            status.style.color = '#10b981';
            status.textContent = data.message;
        } else {
            status.style.color = '#ef4444';
            status.textContent = data.error;
        }
        btn.disabled = false;
        btn.textContent = 'Test Connection';
    })
    .catch(err => {
        status.style.color = '#ef4444';
        status.textContent = 'Connection test failed';
        btn.disabled = false;
        btn.textContent = 'Test Connection';
    });
});
</script>
