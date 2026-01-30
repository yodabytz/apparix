<div class="etsy-sync-settings">
    <div class="settings-section">
        <h4>Etsy API Credentials</h4>
        <p class="text-muted">Connect your Etsy shop using Developer API credentials.</p>

        <div class="form-group">
            <label for="keystring">API Keystring <span class="required">*</span></label>
            <input type="text" id="keystring" name="settings[keystring]"
                   value="<?php echo escape($settings['keystring'] ?? ''); ?>"
                   class="form-control" required>
            <small class="form-text">Your app's API key from Etsy Developer Portal</small>
        </div>

        <div class="form-group">
            <label for="shared_secret">Shared Secret <span class="required">*</span></label>
            <input type="password" id="shared_secret" name="settings[shared_secret]"
                   value="<?php echo escape($settings['shared_secret'] ?? ''); ?>"
                   class="form-control" required>
        </div>

        <div class="form-group">
            <label for="access_token">Access Token <span class="required">*</span></label>
            <input type="password" id="access_token" name="settings[access_token]"
                   value="<?php echo escape($settings['access_token'] ?? ''); ?>"
                   class="form-control" required>
            <small class="form-text">OAuth access token from authorization flow</small>
        </div>

        <div class="form-group">
            <label for="refresh_token">Refresh Token</label>
            <input type="password" id="refresh_token" name="settings[refresh_token]"
                   value="<?php echo escape($settings['refresh_token'] ?? ''); ?>"
                   class="form-control">
            <small class="form-text">Used to automatically refresh expired tokens</small>
        </div>

        <div class="form-group">
            <label for="shop_id">Shop ID <span class="required">*</span></label>
            <input type="text" id="shop_id" name="settings[shop_id]"
                   value="<?php echo escape($settings['shop_id'] ?? ''); ?>"
                   class="form-control" required>
            <small class="form-text">Your numeric Etsy Shop ID</small>
        </div>
    </div>

    <div class="settings-section">
        <h4>Listing Defaults</h4>
        <p class="text-muted">Default values for new Etsy listings.</p>

        <div class="form-group">
            <label for="shipping_profile_id">Default Shipping Profile ID</label>
            <input type="text" id="shipping_profile_id" name="settings[shipping_profile_id]"
                   value="<?php echo escape($settings['shipping_profile_id'] ?? ''); ?>"
                   class="form-control">
            <small class="form-text">Shipping profile to use for new listings (get from Etsy)</small>
        </div>

        <div class="form-group">
            <label for="who_made">Who Made</label>
            <select id="who_made" name="settings[who_made]" class="form-control">
                <option value="i_did" <?php echo ($settings['who_made'] ?? 'i_did') === 'i_did' ? 'selected' : ''; ?>>I did</option>
                <option value="someone_else" <?php echo ($settings['who_made'] ?? '') === 'someone_else' ? 'selected' : ''; ?>>Someone else</option>
                <option value="collective" <?php echo ($settings['who_made'] ?? '') === 'collective' ? 'selected' : ''; ?>>A member of my shop</option>
            </select>
        </div>

        <div class="form-group">
            <label for="when_made">When Made</label>
            <select id="when_made" name="settings[when_made]" class="form-control">
                <option value="made_to_order" <?php echo ($settings['when_made'] ?? 'made_to_order') === 'made_to_order' ? 'selected' : ''; ?>>Made to order</option>
                <option value="2020_2024" <?php echo ($settings['when_made'] ?? '') === '2020_2024' ? 'selected' : ''; ?>>2020-2024</option>
                <option value="2010_2019" <?php echo ($settings['when_made'] ?? '') === '2010_2019' ? 'selected' : ''; ?>>2010-2019</option>
                <option value="before_2010" <?php echo ($settings['when_made'] ?? '') === 'before_2010' ? 'selected' : ''; ?>>Before 2010</option>
            </select>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="settings[is_supply]" value="1"
                       <?php echo ($settings['is_supply'] ?? false) ? 'checked' : ''; ?>>
                This is a supply or tool for crafting
            </label>
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
                Import Etsy orders into Apparix
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
            <li>Go to <a href="https://www.etsy.com/developers" target="_blank">Etsy Developer Portal</a></li>
            <li>Create a new app (or use existing)</li>
            <li>Note your API Keystring and Shared Secret</li>
            <li>Set up OAuth and complete the authorization flow</li>
            <li>Your Shop ID is in your shop URL: etsy.com/shop/[shopname] - use the Shop Manager to find the numeric ID</li>
            <li>Enter all credentials above and save</li>
        </ol>
    </div>
</div>

<style>
.etsy-sync-settings .settings-section {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #eee;
}
.etsy-sync-settings .settings-section:last-child {
    border-bottom: none;
}
.etsy-sync-settings h4 {
    margin-bottom: 0.5rem;
    color: #f56400;
}
.etsy-sync-settings .text-muted {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 1rem;
}
.etsy-sync-settings .required {
    color: #f56400;
}
.etsy-sync-settings .checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}
.etsy-sync-settings .setup-steps {
    background: #f7f7f7;
    padding: 1rem 1rem 1rem 2rem;
    border-radius: 4px;
}
.etsy-sync-settings .setup-steps li {
    margin-bottom: 0.5rem;
}
</style>
