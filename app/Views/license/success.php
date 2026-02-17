<style>
.success-container {
    max-width: 700px;
    margin: 3rem auto;
    padding: 0 1rem;
}
.success-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    overflow: hidden;
}
.success-header {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: white;
    padding: 2rem;
    text-align: center;
}
.success-header svg {
    width: 64px;
    height: 64px;
    margin-bottom: 1rem;
}
.success-header h1 {
    margin: 0 0 0.5rem;
    font-size: 1.75rem;
}
.success-header p {
    margin: 0;
    opacity: 0.9;
}
.success-body {
    padding: 2rem;
}
.license-display {
    background: #f8f9fa;
    border: 2px dashed var(--theme-primary, #8B5CF6);
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    margin: 1.5rem 0;
}
.license-display label {
    display: block;
    font-size: 0.875rem;
    color: #666;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.license-key {
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 1.25rem;
    font-weight: 600;
    color: #333;
    letter-spacing: 1px;
    word-break: break-all;
    padding: 1rem;
    background: white;
    border-radius: 8px;
    margin-top: 0.5rem;
    position: relative;
}
.copy-btn {
    position: absolute;
    right: 0.5rem;
    top: 50%;
    transform: translateY(-50%);
    background: var(--theme-primary, #8B5CF6);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.875rem;
}
.copy-btn:hover {
    opacity: 0.9;
}
.info-box {
    background: #e8f4fd;
    border-left: 4px solid #0066cc;
    padding: 1rem 1.25rem;
    border-radius: 0 8px 8px 0;
    margin: 1.5rem 0;
}
.info-box h4 {
    margin: 0 0 0.5rem;
    color: #0066cc;
}
.info-box p {
    margin: 0;
    color: #333;
}
.info-box code {
    background: rgba(0,0,0,0.05);
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    font-size: 0.9em;
}
.details-table {
    width: 100%;
    margin: 1.5rem 0;
}
.details-table td {
    padding: 0.75rem 0;
    border-bottom: 1px solid #eee;
}
.details-table td:first-child {
    font-weight: 500;
    color: #666;
    width: 140px;
}
.success-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    flex-wrap: wrap;
}
.success-actions .btn {
    flex: 1;
    min-width: 200px;
    padding: 1rem;
    text-align: center;
}
.email-notice {
    text-align: center;
    padding: 1.5rem;
    background: #f0fdf4;
    border-top: 1px solid #e0e0e0;
    color: #166534;
}
.email-notice svg {
    vertical-align: middle;
    margin-right: 0.5rem;
}
</style>

<div class="success-container">
    <div class="success-card">
        <div class="success-header">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <h1>Purchase Successful!</h1>
            <p>Thank you for purchasing Apparix <?php echo escape($tier); ?></p>
        </div>

        <div class="success-body">
            <p>Your license key is ready. Save it somewhere safe!</p>

            <div class="license-display">
                <label>Your License Key</label>
                <div class="license-key" id="licenseKey">
                    <?php echo escape($licenseKey); ?>
                    <button class="copy-btn" onclick="copyLicense()">Copy</button>
                </div>
            </div>

            <table class="details-table">
                <tr>
                    <td>Edition:</td>
                    <td><?php echo escape($tier); ?></td>
                </tr>
                <tr>
                    <td>Domain:</td>
                    <td><?php echo escape($domain ?: 'Any domain (wildcard)'); ?></td>
                </tr>
                <tr>
                    <td>Sent to:</td>
                    <td><?php echo escape($email); ?></td>
                </tr>
            </table>

            <div class="info-box">
                <h4>How to Activate</h4>
                <p>
                    Open your Apparix installation's <code>.env</code> file and add:<br>
                    <code>LICENSE_KEY=<?php echo escape($licenseKey); ?></code>
                </p>
            </div>

            <div class="success-actions">
                <a href="https://github.com/yodabytz/apparix" class="btn btn-primary" target="_blank">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: middle; margin-right: 0.5rem;">
                        <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                    </svg>
                    Download Apparix
                </a>
                <a href="/docs/installation" class="btn btn-outline">
                    Installation Guide
                </a>
            </div>
        </div>

        <div class="email-notice">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                <polyline points="22,6 12,13 2,6"/>
            </svg>
            A copy of this license has been sent to <strong><?php echo escape($email); ?></strong>
        </div>
    </div>
</div>

<script>
function copyLicense() {
    const key = '<?php echo escape($licenseKey); ?>';
    navigator.clipboard.writeText(key).then(function() {
        const btn = document.querySelector('.copy-btn');
        const originalText = btn.textContent;
        btn.textContent = 'Copied!';
        setTimeout(() => {
            btn.textContent = originalText;
        }, 2000);
    });
}
</script>
