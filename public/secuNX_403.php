<?php
/**
 * SecuNX 403 Forbidden Page
 * Displayed when an IP is blocked by SecuNX
 */

// Log the blocked attempt
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
error_log("SecuNX Block: IP=$ip URI=$uri UA=$userAgent");

// Set proper headers
http_response_code(403);
header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Access Denied - Apparix</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@500&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-pink: #FF68C5;
            --light-pink: #FFE4F3;
            --dark-gray: #424242;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, var(--light-pink) 0%, #fff 50%, var(--light-pink) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .container {
            text-align: center;
            max-width: 500px;
        }

        .icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
        }

        h1 {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 2rem;
            color: var(--dark-gray);
            margin-bottom: 1rem;
        }

        p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .details {
            background: rgba(255, 104, 197, 0.1);
            border-radius: 8px;
            padding: 1rem;
            font-size: 0.85rem;
            color: #888;
            margin-bottom: 1.5rem;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: var(--primary-pink);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 104, 197, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🛡️</div>
        <h1>Access Denied</h1>
        <p>Your access to this website has been restricted. This may be due to suspicious activity detected from your IP address.</p>
        <div class="details">
            If you believe this is an error, please contact us with reference:<br>
            <strong><?php echo date('Y-m-d H:i:s'); ?></strong>
        </div>
        <a href="mailto:support@apparix.vibrixmedia.com" class="btn">Contact Support</a>
    </div>
</body>
</html>
