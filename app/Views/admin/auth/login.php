<div class="login-container">
    <div class="login-card">
        <div class="login-logo">
            <span style="font-size: 32px; font-weight: 700; color: #FF68C5;">Apparix</span>
        </div>

        <h1 class="login-title">Welcome Back</h1>
        <p class="login-subtitle">Sign in to your admin dashboard</p>

        <?php if ($flash = getFlash('error')): ?>
            <div class="alert alert-error"><?php echo escape($flash); ?></div>
        <?php endif; ?>
        <?php if ($flash = getFlash('success')): ?>
            <div class="alert alert-success"><?php echo escape($flash); ?></div>
        <?php endif; ?>

        <form action="/admin/login" method="POST">
            <?php echo csrfField(); ?>

            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-input" placeholder="admin@example.com" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-input" placeholder="Enter your password" required>
            </div>

            <button type="submit" class="btn btn-primary">
                Sign In
            </button>
        </form>

        <div class="login-footer">
            <a href="/">Back to Store</a>
        </div>
    </div>
</div>
