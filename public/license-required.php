<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License Required - Apparix</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .license-container {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            max-width: 500px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }
        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #FF68C5, #FF94C8);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 32px;
            color: #fff;
            font-weight: bold;
        }
        h1 {
            font-size: 24px;
            color: #1a1a2e;
            margin-bottom: 12px;
        }
        .error-code {
            display: inline-block;
            background: #fee2e2;
            color: #dc2626;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .message {
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .license-box {
            background: #f8fafc;
            border: 2px dashed #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
        }
        .license-box label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .license-box code {
            display: block;
            background: #1e293b;
            color: #22c55e;
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 13px;
            word-break: break-all;
        }
        .steps {
            text-align: left;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
        }
        .steps h3 {
            font-size: 14px;
            color: #166534;
            margin-bottom: 12px;
        }
        .steps ol {
            margin: 0;
            padding-left: 20px;
            color: #166534;
            font-size: 14px;
        }
        .steps li {
            margin-bottom: 8px;
        }
        .steps code {
            background: #dcfce7;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
        }
        .footer-text {
            font-size: 13px;
            color: #94a3b8;
        }
        .footer-text a {
            color: #FF68C5;
            text-decoration: none;
        }
        .footer-text a:hover {
            text-decoration: underline;
        }
        .details {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            font-size: 12px;
            color: #94a3b8;
        }
        .details span {
            display: block;
            margin-bottom: 4px;
        }
    </style>
</head>
<body>
    <div class="license-container">
        <div class="logo">A</div>
        <h1>License Required</h1>
        <span class="error-code"><?php echo htmlspecialchars($errorCode ?? 'MISSING_KEY'); ?></span>

        <p class="message">
            <?php if (($errorCode ?? '') === 'DOMAIN_MISMATCH'): ?>
                This license key is not valid for the current domain. Please obtain a license for this domain or use a wildcard license.
            <?php elseif (($errorCode ?? '') === 'INVALID_FORMAT'): ?>
                The license key format is invalid. Please check that you entered it correctly.
            <?php elseif (($errorCode ?? '') === 'INVALID_CHECKSUM'): ?>
                The license key is invalid or has been tampered with. Please contact support.
            <?php else: ?>
                A valid license key is required to run Apparix. Please add your license key to continue.
            <?php endif; ?>
        </p>

        <div class="license-box">
            <label>Add to your .env file</label>
            <code>LICENSE_KEY=APX-XXXXX-XXXXX-XXXXX-XXXXX</code>
        </div>

        <div class="steps">
            <h3>How to activate:</h3>
            <ol>
                <li>Purchase a license at <strong>apparix.app</strong></li>
                <li>Open your <code>.env</code> file</li>
                <li>Add your license key</li>
                <li>Refresh this page</li>
            </ol>
        </div>

        <p class="footer-text">
            Need a license? <a href="https://apparix.app" target="_blank">Purchase here</a> |
            <a href="mailto:support@apparix.app">Contact Support</a>
        </p>

        <?php if (!empty($errorDetails)): ?>
        <div class="details">
            <span>Error: <?php echo htmlspecialchars($errorMessage ?? 'Unknown error'); ?></span>
            <?php if (!empty($currentDomain)): ?>
            <span>Domain: <?php echo htmlspecialchars($currentDomain); ?></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
