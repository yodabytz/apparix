<div class="admin-page">
    <div class="page-header">
        <h1>Enable Two-Factor Authentication</h1>
    </div>

    <div class="card" style="max-width: 700px;">
        <div class="card-body" style="padding: 30px;">

            <!-- Step 1: Scan QR Code -->
            <div style="margin-bottom: 30px;">
                <h2 style="font-size: 17px; margin-bottom: 5px;">
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 50%; background: var(--admin-primary); color: #fff; font-size: 14px; margin-right: 8px;">1</span>
                    Scan QR Code
                </h2>
                <p style="color: #6c757d; font-size: 14px; margin: 10px 0 20px 36px;">
                    Open your authenticator app and scan this QR code to add your account.
                </p>
                <div style="text-align: center; margin: 20px 0;">
                    <img src="<?php echo escape($qrCodeUrl); ?>" alt="QR Code" width="200" height="200"
                         style="border: 1px solid #e9ecef; border-radius: 8px; padding: 8px; background: #fff;">
                </div>
                <div style="margin: 15px 0; text-align: center;">
                    <details style="display: inline-block; text-align: left;">
                        <summary style="cursor: pointer; color: var(--admin-primary); font-size: 14px;">
                            Can't scan? Enter this code manually
                        </summary>
                        <div style="margin-top: 10px; padding: 15px; background: #f8f9fa; border-radius: 8px; text-align: center;">
                            <code style="font-size: 18px; letter-spacing: 3px; font-weight: 600; word-break: break-all; user-select: all;">
                                <?php echo escape($secret); ?>
                            </code>
                        </div>
                    </details>
                </div>
            </div>

            <hr style="border: none; border-top: 1px solid #e9ecef; margin: 25px 0;">

            <!-- Step 2: Save Backup Codes -->
            <div style="margin-bottom: 30px;">
                <h2 style="font-size: 17px; margin-bottom: 5px;">
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 50%; background: var(--admin-primary); color: #fff; font-size: 14px; margin-right: 8px;">2</span>
                    Save Backup Codes
                </h2>
                <p style="color: #6c757d; font-size: 14px; margin: 10px 0 15px 36px;">
                    Save these backup codes in a secure location. Each code can only be used once as an alternative to your authenticator app.
                </p>

                <div style="background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 0 0 15px 36px;">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; font-family: monospace; font-size: 15px;" id="backupCodesGrid">
                        <?php foreach ($backupCodes as $code): ?>
                            <div style="padding: 6px 12px; background: #fff; border-radius: 4px; text-align: center; border: 1px solid #dee2e6;">
                                <?php echo escape($code); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="margin-top: 12px; text-align: center;">
                        <button type="button" class="btn btn-sm" onclick="copyBackupCodes()" id="copyCodesBtn" style="font-size: 13px;">
                            Copy Codes
                        </button>
                    </div>
                </div>

                <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px; padding: 12px 14px; margin-left: 36px; font-size: 13px; color: #856404;">
                    <strong>Important:</strong> These codes will not be shown again. If you lose access to your authenticator app and don't have backup codes, you'll be locked out of your account.
                </div>
            </div>

            <hr style="border: none; border-top: 1px solid #e9ecef; margin: 25px 0;">

            <!-- Step 3: Verify -->
            <div>
                <h2 style="font-size: 17px; margin-bottom: 5px;">
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 50%; background: var(--admin-primary); color: #fff; font-size: 14px; margin-right: 8px;">3</span>
                    Verify Setup
                </h2>
                <p style="color: #6c757d; font-size: 14px; margin: 10px 0 15px 36px;">
                    Enter the 6-digit code from your authenticator app to verify the setup.
                </p>

                <form action="/admin/2fa/enable" method="POST" style="margin-left: 36px;">
                    <?php echo csrfField(); ?>
                    <div class="form-group" style="max-width: 250px;">
                        <input type="text" name="code" class="form-input" placeholder="000000"
                               maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="one-time-code"
                               required autofocus
                               style="font-size: 24px; text-align: center; letter-spacing: 8px; font-family: monospace;">
                    </div>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <button type="submit" class="btn btn-primary">Verify & Enable 2FA</button>
                        <a href="/admin/2fa" style="color: #6c757d; font-size: 14px;">Cancel</a>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<script>
function copyBackupCodes() {
    var codes = [];
    document.querySelectorAll('#backupCodesGrid > div').forEach(function(el) {
        codes.push(el.textContent.trim());
    });
    var text = codes.join('\n');

    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function() {
            var btn = document.getElementById('copyCodesBtn');
            btn.textContent = 'Copied!';
            setTimeout(function() { btn.textContent = 'Copy Codes'; }, 2000);
        });
    } else {
        var textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        var btn = document.getElementById('copyCodesBtn');
        btn.textContent = 'Copied!';
        setTimeout(function() { btn.textContent = 'Copy Codes'; }, 2000);
    }
}
</script>
