<div class="card">
    <h1 class="card-title">Welcome to Apparix</h1>
    <p class="card-description">Enter your license key to begin installation. You can find your key in your purchase confirmation email or your <a href="https://apparix.app/licenses/lookup" target="_blank" style="color:#2186c4;">Apparix account</a>.</p>

    <form method="POST" action="/install?step=1">
        <div class="form-group">
            <label for="license_key">License Key *</label>
            <input type="text" name="license_key" id="license_key" class="form-control"
                   value="<?php echo htmlspecialchars($_SESSION['install']['license_key'] ?? ''); ?>"
                   placeholder="APX-XXXXX-XXXXX-XXXXX-XXXXX"
                   style="font-family: monospace; font-size: 1.1rem; letter-spacing: 1px;"
                   required>
            <span class="form-help">Format: APX-XXXXX-XXXXX-XXXXX-XXXXX</span>
        </div>

        <div class="form-actions">
            <div></div>
            <button type="submit" class="btn btn-primary btn-lg">Validate &amp; Continue</button>
        </div>
    </form>
</div>

<div class="card" style="margin-top: 16px; text-align: center;">
    <p style="color: #6b7280; margin: 0;">Don't have a license? <a href="https://apparix.app/pricing" target="_blank" style="color:#2186c4; font-weight: 500;">Purchase one here</a></p>
</div>
