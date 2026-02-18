<div class="login-container">
    <div class="login-card">
        <div class="login-logo">
            <span style="font-size: 32px; font-weight: 700; color: #FF68C5;">Apparix</span>
        </div>

        <?php if (!empty($pending_2fa)): ?>
            <!-- Two-Factor Authentication Verification -->
            <h1 class="login-title">Two-Factor Verification</h1>
            <p class="login-subtitle">Enter the code from your authenticator app</p>

            <?php if ($flash = getFlash('error')): ?>
                <div class="alert alert-error"><?php echo escape($flash); ?></div>
            <?php endif; ?>
            <?php if ($flash = getFlash('success')): ?>
                <div class="alert alert-success"><?php echo escape($flash); ?></div>
            <?php endif; ?>

            <form action="/admin/login" method="POST">
                <?php echo csrfField(); ?>

                <div class="form-group">
                    <label class="form-label" for="two_factor_code">Verification Code</label>
                    <input type="text" id="two_factor_code" name="two_factor_code" class="form-input"
                           placeholder="000000" maxlength="8" inputmode="numeric" autocomplete="one-time-code"
                           required autofocus
                           style="font-size: 20px; text-align: center; letter-spacing: 6px; font-family: monospace;">
                    <p style="color: #6c757d; font-size: 12px; margin-top: 6px;">
                        Enter the 6-digit code from your authenticator app, or a backup code.
                    </p>
                </div>

                <button type="submit" class="btn btn-primary">
                    Verify
                </button>
            </form>

            <div class="login-footer">
                <a href="/admin/2fa/cancel">Cancel and return to login</a>
            </div>

        <?php else: ?>
            <!-- Normal Login Form -->
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
        <?php endif; ?>
    </div>
</div>
