<div class="amazon-sync-settings">
    <div class="settings-section">
        <h4>Amazon Seller Central Credentials</h4>
        <p class="text-muted">Connect your Amazon Seller Central account using the SP-API credentials.</p>

        <div class="form-group">
            <label for="seller_id">Seller ID <span class="required">*</span></label>
            <input type="text" id="seller_id" name="settings[seller_id]"
                   value="<?php echo escape($settings['seller_id'] ?? ''); ?>"
                   class="form-control" required>
            <small class="form-text">Found in Seller Central under Settings &gt; Account Info</small>
        </div>

        <div class="form-group">
            <label for="marketplace_id">Marketplace <span class="required">*</span></label>
            <select id="marketplace_id" name="settings[marketplace_id]" class="form-control" required>
                <option value="">Select Marketplace...</option>
                <option value="ATVPDKIKX0DER" <?php echo ($settings['marketplace_id'] ?? '') === 'ATVPDKIKX0DER' ? 'selected' : ''; ?>>United States (amazon.com)</option>
                <option value="A2EUQ1WTGCTBG2" <?php echo ($settings['marketplace_id'] ?? '') === 'A2EUQ1WTGCTBG2' ? 'selected' : ''; ?>>Canada (amazon.ca)</option>
                <option value="A1AM78C64UM0Y8" <?php echo ($settings['marketplace_id'] ?? '') === 'A1AM78C64UM0Y8' ? 'selected' : ''; ?>>Mexico (amazon.com.mx)</option>
                <option value="A1F83G8C2ARO7P" <?php echo ($settings['marketplace_id'] ?? '') === 'A1F83G8C2ARO7P' ? 'selected' : ''; ?>>United Kingdom (amazon.co.uk)</option>
                <option value="A1PA6795UKMFR9" <?php echo ($settings['marketplace_id'] ?? '') === 'A1PA6795UKMFR9' ? 'selected' : ''; ?>>Germany (amazon.de)</option>
                <option value="A13V1IB3VIYBER" <?php echo ($settings['marketplace_id'] ?? '') === 'A13V1IB3VIYBER' ? 'selected' : ''; ?>>France (amazon.fr)</option>
                <option value="APJ6JRA9NG5V4" <?php echo ($settings['marketplace_id'] ?? '') === 'APJ6JRA9NG5V4' ? 'selected' : ''; ?>>Italy (amazon.it)</option>
                <option value="A1RKKUPIHCS9HS" <?php echo ($settings['marketplace_id'] ?? '') === 'A1RKKUPIHCS9HS' ? 'selected' : ''; ?>>Spain (amazon.es)</option>
            </select>
        </div>
    </div>

    <div class="settings-section">
        <h4>SP-API Credentials</h4>
        <p class="text-muted">Create an app in Seller Central Developer Console to get these credentials.</p>

        <div class="form-group">
            <label for="lwa_client_id">LWA Client ID <span class="required">*</span></label>
            <input type="text" id="lwa_client_id" name="settings[lwa_client_id]"
                   value="<?php echo escape($settings['lwa_client_id'] ?? ''); ?>"
                   class="form-control" required>
        </div>

        <div class="form-group">
            <label for="lwa_client_secret">LWA Client Secret <span class="required">*</span></label>
            <input type="password" id="lwa_client_secret" name="settings[lwa_client_secret]"
                   value="<?php echo escape($settings['lwa_client_secret'] ?? ''); ?>"
                   class="form-control" required>
        </div>

        <div class="form-group">
            <label for="refresh_token">Refresh Token <span class="required">*</span></label>
            <input type="password" id="refresh_token" name="settings[refresh_token]"
                   value="<?php echo escape($settings['refresh_token'] ?? ''); ?>"
                   class="form-control" required>
            <small class="form-text">Generated when authorizing your app</small>
        </div>
    </div>

    <div class="settings-section">
        <h4>Sync Options</h4>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="settings[sync_inventory]" value="1"
                       <?php echo ($settings['sync_inventory'] ?? true) ? 'checked' : ''; ?>>
                Automatically sync inventory levels
            </label>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="settings[sync_orders]" value="1"
                       <?php echo ($settings['sync_orders'] ?? true) ? 'checked' : ''; ?>>
                Import Amazon orders into Apparix
            </label>
        </div>

        <div class="form-group">
            <label for="sync_interval">Sync Interval (minutes)</label>
            <input type="number" id="sync_interval" name="settings[sync_interval]"
                   value="<?php echo escape($settings['sync_interval'] ?? 15); ?>"
                   class="form-control" min="5" max="60">
        </div>
    </div>

    <div class="settings-section">
        <h4>Setup Instructions</h4>
        <ol class="setup-steps">
            <li>Go to <a href="https://sellercentral.amazon.com" target="_blank">Amazon Seller Central</a></li>
            <li>Navigate to Apps & Services &gt; Develop Apps</li>
            <li>Create a new app or use an existing one</li>
            <li>Note your LWA Client ID and Client Secret</li>
            <li>Authorize the app to get your Refresh Token</li>
            <li>Enter all credentials above and save</li>
        </ol>
    </div>
</div>

<style>
.amazon-sync-settings .settings-section {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #eee;
}
.amazon-sync-settings .settings-section:last-child {
    border-bottom: none;
}
.amazon-sync-settings h4 {
    margin-bottom: 0.5rem;
    color: #232f3e;
}
.amazon-sync-settings .text-muted {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 1rem;
}
.amazon-sync-settings .required {
    color: #ff9900;
}
.amazon-sync-settings .checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}
.amazon-sync-settings .setup-steps {
    background: #f7f7f7;
    padding: 1rem 1rem 1rem 2rem;
    border-radius: 4px;
}
.amazon-sync-settings .setup-steps li {
    margin-bottom: 0.5rem;
}
</style>
