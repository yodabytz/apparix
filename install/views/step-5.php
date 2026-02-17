<div class="card">
    <h1 class="card-title">Optional Integrations</h1>
    <p class="card-description">Configure payment processing, email, and security. You can skip and set these up later.</p>

    <form method="POST" action="/install?step=5">
        <h3 style="margin: 24px 0 16px; font-size: 1.1rem;">Payment (Stripe)</h3>
        <div class="form-row">
            <div class="form-group">
                <label for="stripe_public">Stripe Public Key</label>
                <input type="text" name="stripe_public" id="stripe_public" class="form-control"
                       value="<?php echo htmlspecialchars($_SESSION['install']['stripe_public'] ?? ''); ?>"
                       placeholder="pk_test_...">
            </div>
            <div class="form-group">
                <label for="stripe_secret">Stripe Secret Key</label>
                <input type="text" name="stripe_secret" id="stripe_secret" class="form-control"
                       value="<?php echo htmlspecialchars($_SESSION['install']['stripe_secret'] ?? ''); ?>"
                       placeholder="sk_test_...">
            </div>
        </div>

        <h3 style="margin: 24px 0 16px; font-size: 1.1rem;">Email (SMTP)</h3>
        <div class="form-row">
            <div class="form-group">
                <label for="mail_host">SMTP Host</label>
                <input type="text" name="mail_host" id="mail_host" class="form-control"
                       value="<?php echo htmlspecialchars($_SESSION['install']['mail_host'] ?? ''); ?>"
                       placeholder="smtp.example.com">
            </div>
            <div class="form-group">
                <label for="mail_port">SMTP Port</label>
                <input type="text" name="mail_port" id="mail_port" class="form-control"
                       value="<?php echo htmlspecialchars($_SESSION['install']['mail_port'] ?? '25'); ?>"
                       placeholder="25">
            </div>
        </div>
        <div class="form-group">
            <label for="mail_from">From Email</label>
            <input type="email" name="mail_from" id="mail_from" class="form-control"
                   value="<?php echo htmlspecialchars($_SESSION['install']['mail_from'] ?? ''); ?>"
                   placeholder="orders@mystore.com">
        </div>

        <h3 style="margin: 24px 0 16px; font-size: 1.1rem;">Security (reCAPTCHA v3)</h3>
        <div class="form-row">
            <div class="form-group">
                <label for="recaptcha_site">Site Key</label>
                <input type="text" name="recaptcha_site" id="recaptcha_site" class="form-control"
                       value="<?php echo htmlspecialchars($_SESSION['install']['recaptcha_site'] ?? ''); ?>"
                       placeholder="6Le...">
            </div>
            <div class="form-group">
                <label for="recaptcha_secret">Secret Key</label>
                <input type="text" name="recaptcha_secret" id="recaptcha_secret" class="form-control"
                       value="<?php echo htmlspecialchars($_SESSION['install']['recaptcha_secret'] ?? ''); ?>"
                       placeholder="6Le...">
            </div>
        </div>

        <div class="form-actions">
            <a href="/install?step=4" class="btn btn-secondary">Back</a>
            <div>
                <a href="/install?step=6" class="skip-link" style="margin-right: 16px;">Skip for now</a>
                <button type="submit" class="btn btn-primary btn-lg">Continue</button>
            </div>
        </div>
    </form>
</div>
