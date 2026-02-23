<div class="ebay-sync-settings">
    <div class="settings-section">
        <h4>eBay Developer Credentials</h4>
        <p class="text-muted">Connect your eBay seller account using Developer Program credentials.</p>

        <div class="form-group">
            <label for="environment">Environment <span class="required">*</span></label>
            <select id="environment" name="settings[environment]" class="form-control" required>
                <option value="sandbox" <?php echo ($settings['environment'] ?? 'sandbox') === 'sandbox' ? 'selected' : ''; ?>>Sandbox (Testing)</option>
                <option value="production" <?php echo ($settings['environment'] ?? '') === 'production' ? 'selected' : ''; ?>>Production (Live)</option>
            </select>
            <small class="form-text">Start with Sandbox to test, then switch to Production</small>
        </div>

        <div class="form-group">
            <label for="client_id">Client ID (App ID) <span class="required">*</span></label>
            <input type="text" id="client_id" name="settings[client_id]"
                   value="<?php echo escape($settings['client_id'] ?? ''); ?>"
                   class="form-control" required>
        </div>

        <div class="form-group">
            <label for="client_secret">Client Secret (Cert ID) <span class="required">*</span></label>
            <input type="password" id="client_secret" name="settings[client_secret]"
                   value="<?php echo escape($settings['client_secret'] ?? ''); ?>"
                   class="form-control" required>
        </div>

        <div class="form-group">
            <label for="refresh_token">User Refresh Token <span class="required">*</span></label>
            <input type="password" id="refresh_token" name="settings[refresh_token]"
                   value="<?php echo escape($settings['refresh_token'] ?? ''); ?>"
                   class="form-control" required>
            <small class="form-text">Generated from OAuth user consent flow</small>
        </div>
    </div>

    <div class="settings-section">
        <h4>Marketplace Settings</h4>

        <div class="form-group">
            <label for="site_id">eBay Site <span class="required">*</span></label>
            <select id="site_id" name="settings[site_id]" class="form-control" required>
                <option value="EBAY_US" <?php echo ($settings['site_id'] ?? 'EBAY_US') === 'EBAY_US' ? 'selected' : ''; ?>>United States (ebay.com)</option>
                <option value="EBAY_GB" <?php echo ($settings['site_id'] ?? '') === 'EBAY_GB' ? 'selected' : ''; ?>>United Kingdom (ebay.co.uk)</option>
                <option value="EBAY_DE" <?php echo ($settings['site_id'] ?? '') === 'EBAY_DE' ? 'selected' : ''; ?>>Germany (ebay.de)</option>
                <option value="EBAY_AU" <?php echo ($settings['site_id'] ?? '') === 'EBAY_AU' ? 'selected' : ''; ?>>Australia (ebay.com.au)</option>
                <option value="EBAY_CA" <?php echo ($settings['site_id'] ?? '') === 'EBAY_CA' ? 'selected' : ''; ?>>Canada (ebay.ca)</option>
                <option value="EBAY_FR" <?php echo ($settings['site_id'] ?? '') === 'EBAY_FR' ? 'selected' : ''; ?>>France (ebay.fr)</option>
            </select>
        </div>

        <div class="form-group">
            <label for="listing_format">Default Listing Format</label>
            <select id="listing_format" name="settings[listing_format]" class="form-control">
                <option value="FIXED_PRICE" <?php echo ($settings['listing_format'] ?? 'FIXED_PRICE') === 'FIXED_PRICE' ? 'selected' : ''; ?>>Fixed Price (Buy It Now)</option>
                <option value="AUCTION" <?php echo ($settings['listing_format'] ?? '') === 'AUCTION' ? 'selected' : ''; ?>>Auction</option>
            </select>
        </div>

        <div class="form-group">
            <label for="listing_duration">Listing Duration</label>
            <select id="listing_duration" name="settings[listing_duration]" class="form-control">
                <option value="GTC" <?php echo ($settings['listing_duration'] ?? 'GTC') === 'GTC' ? 'selected' : ''; ?>>Good 'Til Cancelled</option>
                <option value="DAYS_30" <?php echo ($settings['listing_duration'] ?? '') === 'DAYS_30' ? 'selected' : ''; ?>>30 Days</option>
                <option value="DAYS_7" <?php echo ($settings['listing_duration'] ?? '') === 'DAYS_7' ? 'selected' : ''; ?>>7 Days</option>
                <option value="DAYS_3" <?php echo ($settings['listing_duration'] ?? '') === 'DAYS_3' ? 'selected' : ''; ?>>3 Days</option>
            </select>
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
                Import eBay orders into Apparix
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
            <li>Create an account at <a href="https://developer.ebay.com" target="_blank">eBay Developer Program</a></li>
            <li>Create a new application (Sandbox first, then Production)</li>
            <li>Note your App ID (Client ID) and Cert ID (Client Secret)</li>
            <li>Generate User Token using OAuth flow with required scopes</li>
            <li>Enter credentials above and save</li>
            <li>Test with Sandbox before going live</li>
        </ol>
    </div>
</div>

<style>
.ebay-sync-settings .settings-section {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #eee;
}
.ebay-sync-settings .settings-section:last-child {
    border-bottom: none;
}
.ebay-sync-settings h4 {
    margin-bottom: 0.5rem;
    color: #e53238;
}
.ebay-sync-settings .text-muted {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 1rem;
}
.ebay-sync-settings .required {
    color: #e53238;
}
.ebay-sync-settings .checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}
.ebay-sync-settings .setup-steps {
    background: #f7f7f7;
    padding: 1rem 1rem 1rem 2rem;
    border-radius: 4px;
}
.ebay-sync-settings .setup-steps li {
    margin-bottom: 0.5rem;
}
</style>
