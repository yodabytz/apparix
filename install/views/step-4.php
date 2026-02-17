<div class="card">
    <h1 class="card-title">Admin Account</h1>
    <p class="card-description">Create your administrator account.</p>

    <form method="POST" action="/install?step=4">
        <div class="form-group">
            <label for="admin_name">Full Name *</label>
            <input type="text" name="admin_name" id="admin_name" class="form-control"
                   value="<?php echo htmlspecialchars($_SESSION['install']['admin_name'] ?? ''); ?>"
                   placeholder="John Doe" required>
        </div>

        <div class="form-group">
            <label for="admin_email">Email Address *</label>
            <input type="email" name="admin_email" id="admin_email" class="form-control"
                   value="<?php echo htmlspecialchars($_SESSION['install']['admin_email'] ?? ''); ?>"
                   placeholder="admin@mystore.com" required>
            <span class="form-help">Used to log in to the admin panel</span>
        </div>

        <div class="form-group">
            <label for="admin_password">Password *</label>
            <input type="password" name="admin_password" id="admin_password" class="form-control"
                   placeholder="Minimum 8 characters" required minlength="8">
            <span class="form-help">Choose a strong password with at least 8 characters</span>
        </div>

        <div class="form-group">
            <label for="admin_password_confirm">Confirm Password *</label>
            <input type="password" name="admin_password_confirm" id="admin_password_confirm" class="form-control"
                   placeholder="Re-enter password" required>
        </div>

        <div class="form-actions">
            <a href="/install?step=3" class="btn btn-secondary">Back</a>
            <button type="submit" class="btn btn-primary btn-lg">Continue</button>
        </div>
    </form>
</div>

<script>
document.querySelector('form').addEventListener('submit', function(e) {
    const pass = document.getElementById('admin_password').value;
    const confirm = document.getElementById('admin_password_confirm').value;

    if (pass !== confirm) {
        e.preventDefault();
        alert('Passwords do not match');
    }
});
</script>
