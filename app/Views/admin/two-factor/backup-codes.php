<div class="admin-page">
    <div class="page-header">
        <h1>New Backup Codes</h1>
    </div>

    <div class="card" style="max-width: 600px;">
        <div class="card-body" style="padding: 30px;">
            <div style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 6px; padding: 12px 14px; margin-bottom: 20px; font-size: 14px; color: #155724;">
                Your backup codes have been regenerated. All previous codes have been invalidated.
            </div>

            <p style="color: #6c757d; font-size: 14px; margin-bottom: 20px;">
                Save these codes in a secure location. Each code can only be used once as an alternative to your authenticator app.
            </p>

            <div style="background: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
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

            <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px; padding: 12px 14px; margin-bottom: 25px; font-size: 13px; color: #856404;">
                <strong>Important:</strong> These codes will not be shown again. Store them in a safe place.
            </div>

            <a href="/admin/2fa" class="btn btn-primary">Done</a>
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
