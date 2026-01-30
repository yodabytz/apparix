<?php
/**
 * Payment Settings (Stripe & PayPal)
 */
?>
<div class="page-header">
    <h1>Payment Settings</h1>
    <a href="/admin/settings" class="btn btn-outline">Back to Settings</a>
</div>

<form id="paymentForm" method="POST">
    <?php echo csrfField(); ?>

    <!-- Stripe Section -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title" style="display: flex; align-items: center; gap: 0.5rem;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="#635BFF">
                    <path d="M13.976 9.15c-2.172-.806-3.356-1.426-3.356-2.409 0-.831.683-1.305 1.901-1.305 2.227 0 4.515.858 6.09 1.631l.89-5.494C18.252.975 15.697 0 12.165 0 9.667 0 7.589.654 6.104 1.872 4.56 3.147 3.757 4.992 3.757 7.218c0 4.039 2.467 5.76 6.476 7.219 2.585.92 3.445 1.574 3.445 2.583 0 .98-.84 1.545-2.354 1.545-1.875 0-4.965-.921-6.99-2.109l-.9 5.555C5.175 22.99 8.385 24 11.714 24c2.641 0 4.843-.624 6.328-1.813 1.664-1.305 2.525-3.236 2.525-5.732 0-4.128-2.524-5.851-6.591-7.305z"/>
                </svg>
                Stripe
            </h3>
        </div>

        <p style="color: var(--admin-text-light); font-size: 0.875rem; margin-bottom: 1.5rem;">
            Configure your Stripe payment gateway. Get your API keys from the <a href="https://dashboard.stripe.com/apikeys" target="_blank" style="color: var(--admin-primary);">Stripe Dashboard</a>.
        </p>

        <div class="form-group">
            <label class="form-label">Stripe Mode</label>
            <div style="display: flex; gap: 1.5rem;">
                <label class="form-checkbox">
                    <input type="radio" name="stripe_mode" value="test" <?php echo $settings['stripe_mode'] === 'test' ? 'checked' : ''; ?>>
                    <span>Test Mode</span>
                </label>
                <label class="form-checkbox">
                    <input type="radio" name="stripe_mode" value="live" <?php echo $settings['stripe_mode'] === 'live' ? 'checked' : ''; ?>>
                    <span>Live Mode</span>
                </label>
            </div>
            <small style="color: var(--admin-text-light); font-size: 0.75rem;">Use Test Mode while developing. Switch to Live Mode when ready to accept real payments.</small>
        </div>

        <div style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: var(--admin-radius); padding: 1rem; margin-bottom: 1.5rem; display: <?php echo $settings['stripe_mode'] === 'live' ? 'block' : 'none'; ?>;" id="liveWarning">
            <strong style="color: #92400e;">Live Mode Active</strong>
            <p style="margin: 0.25rem 0 0; font-size: 0.875rem; color: #92400e;">Real payments will be processed. Ensure your live keys are correct.</p>
        </div>

        <!-- Test Keys -->
        <div id="testKeys" style="<?php echo $settings['stripe_mode'] === 'test' ? '' : 'display: none;'; ?>">
            <h4 style="font-size: 0.875rem; font-weight: 600; margin-bottom: 1rem; color: var(--admin-text-light);">Test API Keys</h4>
            <div class="form-row" style="grid-template-columns: 1fr 1fr;">
                <div class="form-group">
                    <label class="form-label">Test Publishable Key</label>
                    <input type="text" name="stripe_test_public_key" class="form-input" value="<?php echo escape($settings['stripe_test_public_key']); ?>" placeholder="pk_test_...">
                </div>
                <div class="form-group">
                    <label class="form-label">Test Secret Key</label>
                    <input type="password" name="stripe_test_secret_key" class="form-input" value="<?php echo escape($settings['stripe_test_secret_key']); ?>" placeholder="sk_test_...">
                </div>
            </div>
        </div>

        <!-- Live Keys -->
        <div id="liveKeys" style="<?php echo $settings['stripe_mode'] === 'live' ? '' : 'display: none;'; ?>">
            <h4 style="font-size: 0.875rem; font-weight: 600; margin-bottom: 1rem; color: var(--admin-text-light);">Live API Keys</h4>
            <div class="form-row" style="grid-template-columns: 1fr 1fr;">
                <div class="form-group">
                    <label class="form-label">Live Publishable Key</label>
                    <input type="text" name="stripe_live_public_key" class="form-input" value="<?php echo escape($settings['stripe_live_public_key']); ?>" placeholder="pk_live_...">
                </div>
                <div class="form-group">
                    <label class="form-label">Live Secret Key</label>
                    <input type="password" name="stripe_live_secret_key" class="form-input" value="<?php echo escape($settings['stripe_live_secret_key']); ?>" placeholder="sk_live_...">
                </div>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Webhook Secret</label>
            <input type="password" name="stripe_webhook_secret" class="form-input" value="<?php echo escape($settings['stripe_webhook_secret']); ?>" placeholder="whsec_...">
            <small style="color: var(--admin-text-light); font-size: 0.75rem;">Required for receiving Stripe webhook events. Get this from your Stripe webhook endpoint settings.</small>
        </div>
    </div>

    <!-- PayPal Section -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title" style="display: flex; align-items: center; gap: 0.5rem;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="#003087">
                    <path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944 3.72a.77.77 0 0 1 .757-.63h6.265c2.679 0 4.534.84 5.508 2.494.45.766.703 1.636.752 2.58.05.997-.07 2.158-.386 3.454-.632 2.591-1.847 4.468-3.613 5.578-1.578.99-3.584 1.493-5.968 1.493H7.358a.77.77 0 0 0-.757.63l-.525 2.018zM19.9 8.563c-.023.166-.05.333-.08.5-.847 3.47-3.05 5.228-6.553 5.228H11.1a.77.77 0 0 0-.758.63l-.868 3.35-.245.943a.641.641 0 0 0 .633.74h3.612a.77.77 0 0 0 .757-.63l.312-1.202.625-2.398a.77.77 0 0 1 .757-.63h.477c3.086 0 5.507-1.254 6.213-4.879.295-1.514.142-2.778-.628-3.652z"/>
                </svg>
                PayPal (Coming Soon)
            </h3>
        </div>

        <p style="color: var(--admin-text-light); font-size: 0.875rem; margin-bottom: 1.5rem;">
            PayPal integration is planned for a future update. Configure your PayPal settings here once available.
        </p>

        <div class="form-group">
            <label class="form-checkbox">
                <input type="checkbox" name="paypal_enabled" value="1" <?php echo $settings['paypal_enabled'] === '1' ? 'checked' : ''; ?> disabled>
                <span>Enable PayPal Payments</span>
            </label>
        </div>

        <div style="opacity: 0.5; pointer-events: none;">
            <div class="form-group">
                <label class="form-label">PayPal Mode</label>
                <div style="display: flex; gap: 1.5rem;">
                    <label class="form-checkbox">
                        <input type="radio" name="paypal_mode" value="sandbox" <?php echo $settings['paypal_mode'] === 'sandbox' ? 'checked' : ''; ?>>
                        <span>Sandbox</span>
                    </label>
                    <label class="form-checkbox">
                        <input type="radio" name="paypal_mode" value="live" <?php echo $settings['paypal_mode'] === 'live' ? 'checked' : ''; ?>>
                        <span>Live</span>
                    </label>
                </div>
            </div>

            <div class="form-row" style="grid-template-columns: 1fr 1fr;">
                <div class="form-group">
                    <label class="form-label">Client ID</label>
                    <input type="text" name="paypal_client_id" class="form-input" value="<?php echo escape($settings['paypal_client_id']); ?>" placeholder="PayPal Client ID">
                </div>
                <div class="form-group">
                    <label class="form-label">Secret</label>
                    <input type="password" name="paypal_secret" class="form-input" value="<?php echo escape($settings['paypal_secret']); ?>" placeholder="PayPal Secret">
                </div>
            </div>
        </div>
    </div>

    <!-- Security Notice -->
    <div class="card" style="border-left: 4px solid var(--admin-warning);">
        <h4 style="margin: 0 0 0.5rem; font-size: 1rem;">Security Notice</h4>
        <ul style="margin: 0; padding-left: 1.25rem; color: var(--admin-text-light); font-size: 0.875rem;">
            <li>Never share your secret keys publicly</li>
            <li>API keys are stored encrypted in the database</li>
            <li>Use test keys during development to avoid real charges</li>
            <li>Set up webhook endpoints in Stripe to receive payment notifications</li>
        </ul>
    </div>

    <div style="display: flex; gap: 1rem; align-items: center;">
        <button type="submit" class="btn btn-primary" id="saveBtn">Save Payment Settings</button>
        <span id="saveStatus" style="color: var(--admin-success); font-size: 0.875rem;"></span>
    </div>
</form>

<script>
(function() {
    var modeRadios = document.querySelectorAll('input[name="stripe_mode"]');
    var testKeys = document.getElementById('testKeys');
    var liveKeys = document.getElementById('liveKeys');
    var liveWarning = document.getElementById('liveWarning');

    modeRadios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            if (this.value === 'test') {
                testKeys.style.display = '';
                liveKeys.style.display = 'none';
                liveWarning.style.display = 'none';
            } else {
                testKeys.style.display = 'none';
                liveKeys.style.display = '';
                liveWarning.style.display = 'block';
            }
        });
    });

    document.getElementById('paymentForm').addEventListener('submit', function(e) {
        e.preventDefault();

        var btn = document.getElementById('saveBtn');
        var status = document.getElementById('saveStatus');

        btn.disabled = true;
        btn.textContent = 'Saving...';
        status.textContent = '';

        var formData = new FormData(this);

        fetch('/admin/settings/payments/update', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                status.style.color = 'var(--admin-success)';
                status.textContent = 'Saved!';
            } else {
                status.style.color = 'var(--admin-danger)';
                status.textContent = data.error || 'Failed to save';
            }
        })
        .catch(function(err) {
            status.style.color = 'var(--admin-danger)';
            status.textContent = 'Error saving settings';
        })
        .finally(function() {
            btn.disabled = false;
            btn.textContent = 'Save Payment Settings';
            setTimeout(function() { status.textContent = ''; }, 3000);
        });
    });
})();
</script>
