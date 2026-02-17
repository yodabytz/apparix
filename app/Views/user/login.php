<!-- Login Page -->
<section class="auth-section">
    <div class="container">
        <div class="auth-card">
            <h1>Login</h1>
            <p class="auth-subtitle">Welcome back! Please sign in to your account.</p>

            <?php if ($error = getFlash('error')): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success = getFlash('success')): ?>
                <div class="alert alert-success"><?php echo escape($success); ?></div>
            <?php endif; ?>

            <form action="/login" method="POST" class="auth-form" id="loginForm">
                <?php echo csrfField(); ?>
                <input type="hidden" name="recaptcha_token" id="login_recaptcha_token">

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required
                           placeholder="your@email.com" autocomplete="email">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required
                           placeholder="Enter your password" autocomplete="current-password">
                </div>

                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" name="remember" value="1">
                        Remember me for 30 days
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-large btn-block">Sign In</button>
            </form>

            <div class="auth-footer">
                <p>Don't have an account? <a href="/register">Create one</a></p>
            </div>
        </div>
    </div>
</section>

<style>
.auth-section {
    padding: 60px 0;
    min-height: calc(100vh - 300px);
    display: flex;
    align-items: center;
}

.auth-card {
    max-width: 450px;
    margin: 0 auto;
    background: #fff;
    border-radius: 12px;
    padding: 40px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.auth-card h1 {
    text-align: center;
    margin-bottom: 8px;
    color: #333;
}

.auth-subtitle {
    text-align: center;
    color: #666;
    margin-bottom: 30px;
}

.auth-form .form-group {
    margin-bottom: 20px;
}

.auth-form label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    color: #333;
}

.auth-form input[type="email"],
.auth-form input[type="password"],
.auth-form input[type="text"],
.auth-form input[type="tel"] {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.auth-form input:focus {
    outline: none;
    border-color: #FF68C5;
    box-shadow: 0 0 0 3px rgba(255, 104, 197, 0.1);
}

.auth-form .checkbox-group {
    display: flex;
    align-items: center;
}

.auth-form .checkbox-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: normal;
    cursor: pointer;
}

.auth-form .checkbox-group input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: #FF68C5;
}

.btn-block {
    width: 100%;
}

.btn-large {
    padding: 14px 28px;
    font-size: 1.1rem;
}

.auth-footer {
    text-align: center;
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid #eee;
}

.auth-footer a {
    color: #FF68C5;
    text-decoration: none;
    font-weight: 500;
}

.auth-footer a:hover {
    text-decoration: underline;
}

.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-error {
    background: #fee;
    color: #c00;
    border: 1px solid #fcc;
}

.alert-success {
    background: #efe;
    color: #060;
    border: 1px solid #cfc;
}

/* Mobile styles */
@media (max-width: 480px) {
    .auth-section {
        padding: 30px 0;
    }

    .auth-card {
        margin: 0 1rem;
        padding: 24px 20px;
        border-radius: 10px;
    }

    .auth-card h1 {
        font-size: 1.5rem;
    }

    .auth-subtitle {
        font-size: 0.9rem;
        margin-bottom: 20px;
    }

    .auth-form input[type="email"],
    .auth-form input[type="password"],
    .auth-form input[type="text"] {
        font-size: 16px; /* Prevents iOS zoom */
        padding: 14px 16px;
    }

    .btn-large {
        padding: 14px 24px;
        font-size: 1rem;
    }

    .auth-footer {
        margin-top: 20px;
        padding-top: 20px;
    }
}
</style>

<script>
document.getElementById('loginForm').addEventListener('submit', async function(e) {
    const siteKey = '<?php echo \App\Core\ReCaptcha::getSiteKey(); ?>';
    if (siteKey && typeof grecaptcha !== 'undefined') {
        e.preventDefault();
        try {
            const token = await grecaptcha.execute(siteKey, {action: 'login'});
            document.getElementById('login_recaptcha_token').value = token;
            this.submit();
        } catch (error) {
            console.error('reCAPTCHA error:', error);
            this.submit();
        }
    }
});
</script>
