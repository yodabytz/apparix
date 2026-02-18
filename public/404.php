<?php
/**
 * Custom 404 Not Found Page
 * Apparix - Theme-aware with admin-customizable content
 */

http_response_code(404);
header('Content-Type: text/html; charset=UTF-8');

// Bootstrap minimal app context for theme and settings support
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Load helpers and required classes
require_once BASE_PATH . '/app/Helpers/functions.php';
require_once BASE_PATH . '/app/Core/Database.php';
require_once BASE_PATH . '/app/Core/Model.php';
require_once BASE_PATH . '/app/Models/Theme.php';
require_once BASE_PATH . '/app/Models/Setting.php';
require_once BASE_PATH . '/app/Core/ThemeService.php';

// Get theme colors
try {
    $themeService = new \App\Core\ThemeService();
    $theme = $themeService->getActiveTheme();
    $primaryColor = $theme['primary_color'] ?? '#2186c4';
    $secondaryColor = $theme['secondary_color'] ?? '#83b1ec';
    $accentColor = $theme['accent_color'] ?? '#5d82b1';
    if ($theme) {
        $r = (int)(255 * 0.95 + intval(substr($primaryColor, 1, 2), 16) * 0.05);
        $g = (int)(255 * 0.95 + intval(substr($primaryColor, 3, 2), 16) * 0.05);
        $b = (int)(255 * 0.95 + intval(substr($primaryColor, 5, 2), 16) * 0.05);
        $pageBg = sprintf('#%02x%02x%02x', min(255, $r), min(255, $g), min(255, $b));
    } else {
        $pageBg = '#f3f8fc';
    }
} catch (\Exception $e) {
    $primaryColor = '#2186c4';
    $secondaryColor = '#83b1ec';
    $accentColor = '#5d82b1';
    $pageBg = '#f3f8fc';
}

// Get customizable content from admin settings
$storeName = appName();
$favicon = setting('store_favicon') ?? '/favicon.ico';
$error404Heading = setting('error_404_heading', "Oops! This page doesn't exist");
$error404Message = setting('error_404_message', "The page you're looking for doesn't exist or has been moved. Don't worry, let's help you find your way back!");
$error404Image = setting('error_404_image', '');
$error404PrimaryBtnText = setting('error_404_primary_btn_text', 'Back to Home');
$error404PrimaryBtnUrl = setting('error_404_primary_btn_url', '/');
$error404SecondaryBtnText = setting('error_404_secondary_btn_text', 'Browse Products');
$error404SecondaryBtnUrl = setting('error_404_secondary_btn_url', '/products');
$error404ShowSearch = (bool)setting('error_404_show_search', '1');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Page Not Found - <?php echo escape($storeName); ?></title>
    <link rel="icon" href="<?php echo escape($favicon); ?>">
    <link rel="shortcut icon" href="<?php echo escape($favicon); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: <?php echo $primaryColor; ?>;
            --secondary: <?php echo $secondaryColor; ?>;
            --accent: <?php echo $accentColor; ?>;
            --page-bg: <?php echo $pageBg; ?>;
            --dark-gray: #424242;
            --text-secondary: #666;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, var(--page-bg) 0%, #fff 50%, var(--page-bg) 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            text-align: center;
        }

        .container { max-width: 500px; }

        .error-code {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 8rem;
            font-weight: 600;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 1rem;
        }

        .error-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            animation: float 3s ease-in-out infinite;
        }

        .error-image { margin-bottom: 1.5rem; }
        .error-image img { max-width: 280px; max-height: 200px; border-radius: 8px; }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        h1 {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 1.75rem;
            color: var(--dark-gray);
            margin-bottom: 1rem;
        }

        p { color: var(--text-secondary); line-height: 1.6; margin-bottom: 2rem; }

        .buttons { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }

        .btn {
            display: inline-block;
            padding: 0.875rem 1.75rem;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .btn-outline {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background: var(--page-bg);
            transform: translateY(-2px);
        }

        .search-box {
            margin-top: 2.5rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }

        .search-box p { font-size: 0.9rem; margin-bottom: 1rem; }

        .search-form {
            display: flex;
            gap: 0.5rem;
            max-width: 350px;
            margin: 0 auto;
        }

        .search-form input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 2px solid #e5e5e5;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .search-form input:focus { outline: none; border-color: var(--primary); }

        .search-form button {
            padding: 0.75rem 1.25rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.2s;
        }

        .search-form button:hover { opacity: 0.9; }

        @media (max-width: 480px) {
            .error-code { font-size: 5rem; }
            .error-icon { font-size: 3rem; }
            h1 { font-size: 1.4rem; }
            .buttons { flex-direction: column; }
            .btn { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-code">404</div>
        <?php if (!empty($error404Image)): ?>
            <div class="error-image">
                <img src="<?php echo escape($error404Image); ?>" alt="Page not found">
            </div>
        <?php else: ?>
            <div class="error-icon">&#x1F50D;</div>
        <?php endif; ?>
        <h1><?php echo escape($error404Heading); ?></h1>
        <p><?php echo escape($error404Message); ?></p>

        <div class="buttons">
            <?php if (!empty($error404PrimaryBtnText)): ?>
                <a href="<?php echo escape($error404PrimaryBtnUrl); ?>" class="btn btn-primary"><?php echo escape($error404PrimaryBtnText); ?></a>
            <?php endif; ?>
            <?php if (!empty($error404SecondaryBtnText)): ?>
                <a href="<?php echo escape($error404SecondaryBtnUrl); ?>" class="btn btn-outline"><?php echo escape($error404SecondaryBtnText); ?></a>
            <?php endif; ?>
        </div>

        <?php if ($error404ShowSearch): ?>
        <div class="search-box">
            <p>Or try searching for what you need:</p>
            <form class="search-form" action="/search" method="GET">
                <input type="text" name="q" placeholder="Search products..." required>
                <button type="submit">Search</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
