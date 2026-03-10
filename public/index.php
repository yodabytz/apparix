<?php
/**
 * Front Controller for Apparix E-Commerce
 * All requests go through this file
 */

// Start output buffering to prevent any accidental output before DOCTYPE
ob_start();

// Define base paths
define('BASE_PATH', dirname(__DIR__));
define('PUBLIC_PATH', __DIR__);

// Load environment variables
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

// Require database credentials from .env (no hardcoded fallbacks)
if (empty($_ENV['DB_HOST']) || empty($_ENV['DB_NAME']) || empty($_ENV['DB_USER'])) {
    http_response_code(500);
    die('Database configuration missing. Check your .env file.');
}

// Installer detection - redirect to installer if not yet installed
$installLockFile = BASE_PATH . '/storage/.installed';
$isInstalled = file_exists($installLockFile);

if (!$isInstalled) {
    $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    // Allow access to installer routes and static assets
    $allowedPaths = ['/install', '/assets'];
    $isAllowed = false;
    foreach ($allowedPaths as $path) {
        if (strpos($requestPath, $path) === 0) {
            $isAllowed = true;
            break;
        }
    }

    if (!$isAllowed) {
        header('Location: /install');
        exit;
    }

    // If accessing installer, include installer bootstrap and exit
    if (strpos($requestPath, '/install') === 0) {
        require_once BASE_PATH . '/install/index.php';
        exit;
    }
} else {
    // Allow the install complete page through even when installed
    $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (strpos($requestPath, '/install') === 0 && isset($_GET['step']) && $_GET['step'] === 'complete') {
        require_once BASE_PATH . '/install/index.php';
        exit;
    }
}

// Bot detection & auto-blocking (runs before session to avoid overhead for bots)
require_once BASE_PATH . '/app/Core/BotBlocker.php';
$botBlocker = \App\Core\BotBlocker::getInstance();
if (!$botBlocker->check()) {
    exit; // Blocked — BotBlocker already sent 403
}

// Security headers (X-Frame-Options, X-Content-Type-Options, X-XSS-Protection set by nginx)
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' https://js.stripe.com https://cdn.jsdelivr.net https://pagead2.googlesyndication.com https://www.googletagservices.com https://adservice.google.com https://www.googletagmanager.com https://www.google-analytics.com https://www.google.com https://www.gstatic.com; frame-src https://js.stripe.com https://googleads.g.doubleclick.net https://www.google.com https://tpc.googlesyndication.com https://www.recaptcha.net; style-src \'self\' \'unsafe-inline\' https://fonts.googleapis.com; font-src \'self\' https://fonts.gstatic.com; img-src \'self\' data: https:; connect-src \'self\' https://pagead2.googlesyndication.com https://www.google-analytics.com https://analytics.google.com https://www.google.com https://www.gstatic.com');

// Configure session security with secure defaults
session_set_cookie_params([
    'lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 604800),
    'path' => '/',
    'domain' => '',  // Empty for current domain only
    'secure' => !(isset($_ENV['SESSION_COOKIE_SECURE']) && $_ENV['SESSION_COOKIE_SECURE'] === 'false'),
    'httponly' => true, // Prevent JavaScript access to session cookie
    'samesite' => 'Lax' // Lax allows same-site form submissions; Strict can block them
]);

// Enable strict session mode for session fixation prevention
ini_set('session.use_strict_mode', '1');

// Start session
session_start();

// Regenerate session ID periodically for security
if (empty($_SESSION['_created'])) {
    $_SESSION['_created'] = time();
} elseif (time() - $_SESSION['_created'] > 1800) { // Regenerate every 30 minutes
    session_regenerate_id(true);
    $_SESSION['_created'] = time();
}

// Check "remember me" token if not logged in
if (empty($_SESSION['user_id']) && !empty($_COOKIE['remember_token'])) {
    // Lazy-load User model to check token
    require_once BASE_PATH . '/app/Core/Model.php';
    require_once BASE_PATH . '/app/Core/Database.php';
    require_once BASE_PATH . '/app/Models/User.php';

    $userModel = new \App\Models\User();
    $user = $userModel->findByRememberToken($_COOKIE['remember_token']);

    if ($user) {
        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);

        // Restore session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['first_name'] ?: explode('@', $user['email'])[0];

        // Refresh the remember token for security (token rotation)
        $newToken = $userModel->setRememberToken($user['id']);
        setcookie('remember_token', $newToken, [
            'expires' => time() + (30 * 24 * 60 * 60),
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } else {
        // Invalid token - clear it
        setcookie('remember_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
}

// Enable error reporting in development
if ($_ENV['APP_DEBUG'] === 'true' || $_ENV['APP_DEBUG'] === '1') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(E_ALL);
}

// Autoload composer dependencies
require BASE_PATH . '/vendor/autoload.php';

// Autoload application classes
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

use App\Core\Router;
use App\Core\CSRF;
use App\Core\Database;

// Initialize database connection
try {
    Database::getInstance();
} catch (Exception $e) {
    http_response_code(500);
    error_log('Database connection failed: ' . $e->getMessage());
    die('Service temporarily unavailable. Please try again later.');
}

// License validation - check if valid license key exists
use App\Core\License;
$licenseResult = License::validate();
if (!$licenseResult['valid']) {
    // Allow access to installer even without license
    $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (strpos($requestPath, '/install') !== 0) {
        http_response_code(403);
        $errorCode = $licenseResult['code'] ?? 'INVALID_LICENSE';
        $errorMessage = $licenseResult['error'] ?? 'License validation failed';

        // Return JSON for AJAX and API requests instead of HTML
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        $isApi = strpos($requestPath, '/api/') === 0;
        if ($isAjax || $isApi) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $errorMessage, 'code' => $errorCode]);
            exit;
        }

        $errorDetails = true;
        $currentDomain = $_SERVER['HTTP_HOST'] ?? 'unknown';
        include __DIR__ . '/license-required.php';
        exit;
    }
}

// Track visitor (skip admin pages, bots, static assets, and excluded IPs)
$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$visitorIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
if (strpos($visitorIp, ',') !== false) {
    $visitorIp = trim(explode(',', $visitorIp)[0]);
}
$excludedIps = ['97.102.234.155']; // Owner IPs to exclude from tracking
$nonPagePaths = ['/manifest.json', '/robots.txt', '/sitemap.xml', '/favicon.ico', '/favicon-32x32.png', '/apple-touch-icon.png', '/android-chrome-192x192.png', '/android-chrome-512x512.png', '/sw.js', '/browserconfig.xml', '/site.webmanifest'];
$skipTracking = str_starts_with($requestPath, '/admin') ||
                str_starts_with($requestPath, '/api') ||
                str_starts_with($requestPath, '/assets') ||
                str_starts_with($requestPath, '/storage') ||
                str_starts_with($requestPath, '/webhook') ||
                str_starts_with($requestPath, '/cron') ||
                in_array($requestPath, $nonPagePaths) ||
                in_array($visitorIp, $excludedIps) ||
                preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot|map|xml|json|txt)$/i', $requestPath);

if (!$skipTracking) {
    try {
        $visitorModel = new \App\Models\Visitor();
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        // Get first IP if multiple
        if (strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }

        // Get country from IP (using cache to avoid too many API calls)
        $country = null;
        $countryCode = null;
        $city = null;

        if ($ip && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            $cacheKey = 'geo_' . md5($ip);
            $cached = $_SESSION[$cacheKey] ?? null;

            if ($cached) {
                $country = $cached['country'];
                $countryCode = $cached['country_code'];
                $city = $cached['city'];
            } else {
                // Use ip-api.com (free, 45 requests/minute)
                $geoData = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,countryCode,city", false, stream_context_create(['http' => ['timeout' => 1]]));
                if ($geoData) {
                    $geo = json_decode($geoData, true);
                    if (isset($geo['status']) && $geo['status'] === 'success') {
                        $country = $geo['country'] ?? null;
                        $countryCode = $geo['countryCode'] ?? null;
                        $city = $geo['city'] ?? null;
                        $_SESSION[$cacheKey] = ['country' => $country, 'country_code' => $countryCode, 'city' => $city];
                    }
                }
            }
        }

        $visitorModel->track([
            'ip_address' => $ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
            'page_url' => $requestPath,
            'country' => $country,
            'country_code' => $countryCode,
            'city' => $city,
            'session_id' => session_id()
        ]);
    } catch (Exception $e) {
        // Silently fail - don't break the site for tracking errors
        error_log('Visitor tracking error: ' . $e->getMessage());
    }
}

// Define routes
$router = new Router();

// Splash page control - check if we should show the main site or splash
// Enable via Admin > Settings or set 'maintenance_mode' in settings table
// Admins automatically get bypass_splash cookie on login
$showSplash = (bool)setting('maintenance_mode');
$showMaintenance = (bool)setting('maintenance_enabled');
$bypassSplash = isset($_GET['preview']) || isset($_COOKIE['bypass_splash']);
$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Define paths that should always be accessible (even in maintenance mode)
$allowedPaths = [
    '/admin',           // Admin panel
    '/webhook',         // Webhooks for payment providers
    '/api',             // API endpoints
    '/assets',          // Static assets
    '/cron',            // Cron endpoints
    '/pricing',         // License pricing page
    '/license',         // License purchase flow
];

// Check if current path should bypass maintenance
$isAllowedPath = false;
foreach ($allowedPaths as $allowed) {
    if (strpos($requestPath, $allowed) === 0) {
        $isAllowedPath = true;
        break;
    }
}

// Maintenance mode: 503 + Retry-After (Google holds rankings)
if ($showMaintenance && !$bypassSplash && !$isAllowedPath) {
    http_response_code(503);
    header('Retry-After: 3600');
    require BASE_PATH . '/app/Views/splash/maintenance.php';
    exit;
}

// Coming soon mode: for new stores being set up
if ($showSplash && !$bypassSplash && !$isAllowedPath) {
    require BASE_PATH . '/app/Views/splash/index.php';
    exit;
}

// Normal routing when not in maintenance mode or bypassed
$router->get('/', 'HomeController', 'index');
$router->get('/products', 'ProductController', 'index');
$router->get('/products/:slug', 'ProductController', 'show');
$router->get('/category/:slug', 'ProductController', 'byCategory');
$router->post('/search', 'ProductController', 'search');
$router->get('/search', 'ProductController', 'search');

// Cart routes
$router->get('/cart', 'CartController', 'index');
$router->post('/cart/add', 'CartController', 'add');
$router->post('/cart/update', 'CartController', 'update');
$router->post('/cart/remove', 'CartController', 'remove');
$router->post('/cart/capture-email', 'CartController', 'captureEmail');

// User routes
$router->get('/login', 'UserController', 'loginForm');
$router->post('/login', 'UserController', 'login');
$router->get('/register', 'UserController', 'registerForm');
$router->post('/register', 'UserController', 'register');
$router->get('/verify-email/resend', 'UserController', 'resendVerification');
$router->get('/verify-email/:token', 'UserController', 'verifyEmail');
$router->get('/logout', 'UserController', 'logout');
$router->get('/account', 'UserController', 'dashboard');
$router->get('/account/orders', 'UserController', 'orders');
$router->get('/account/downloads', 'UserController', 'downloads');
$router->post('/account/update-profile', 'UserController', 'updateProfile');
$router->post('/account/change-password', 'UserController', 'changePassword');
$router->post('/account/upload-avatar', 'UserController', 'uploadAvatar');
$router->post('/account/remove-avatar', 'UserController', 'removeAvatar');

// Newsletter routes
$router->post('/newsletter/subscribe', 'NewsletterController', 'subscribe');
$router->get('/newsletter/unsubscribe', 'NewsletterController', 'unsubscribe');

// Favorites routes
$router->get('/favorites', 'FavoriteController', 'index');
$router->post('/favorites/toggle', 'FavoriteController', 'toggle');
$router->get('/favorites/ids', 'FavoriteController', 'getIds');

// Stock notification routes (back-in-stock alerts)
$router->post('/notify/subscribe', 'StockNotificationController', 'subscribe');
$router->get('/notify/check', 'StockNotificationController', 'check');
$router->get('/notify/unsubscribe', 'StockNotificationController', 'unsubscribe');

// Product review routes
$router->get('/reviews/product', 'ReviewController', 'getProductReviews');
$router->post('/reviews/submit', 'ReviewController', 'submit');
$router->get('/review/:token', 'ReviewController', 'fromEmail');
$router->post('/review/submit', 'ReviewController', 'submitFromEmail');

// Order tracking
$router->get('/track', 'TrackingController', 'index');
$router->post('/track', 'TrackingController', 'index');

// Gift cards
$router->get('/gift-cards', 'GiftCardController', 'index');
$router->post('/gift-cards/purchase', 'GiftCardController', 'purchase');
$router->get('/gift-cards/redeem', 'GiftCardController', 'redeemForm');
$router->post('/gift-cards/redeem', 'GiftCardController', 'redeem');
$router->get('/gift-cards/check-balance', 'GiftCardController', 'checkBalance');

// Referrals
$router->get('/referrals', 'ReferralController', 'index');
$router->post('/api/referral/validate', 'ReferralController', 'validate');

// Digital downloads and license lookup
$router->get('/download/:token', 'DownloadController', 'show');
$router->get('/download/:token/file', 'DownloadController', 'download');
$router->get('/licenses/lookup', 'DownloadController', 'lookup');
$router->post('/licenses/lookup', 'DownloadController', 'lookup');

// Exit-intent popup
$router->post('/api/popup-coupon', 'PopupCouponController', 'generate');
$router->post('/api/popup-coupon/validate', 'PopupCouponController', 'validate');

// Social proof - recent purchases
$router->get('/api/recent-purchases', 'HomeController', 'recentPurchases');

// Lucky clover game
$router->post('/api/clover/generate', 'CloverController', 'generate');

// Static pages
$router->get('/privacy', 'PageController', 'privacy');
$router->get('/terms', 'PageController', 'terms');
$router->get('/contact', 'PageController', 'contact');
$router->post('/contact', 'PageController', 'sendContact');

// Custom pages
$router->get('/pages/:slug', 'PageController', 'showPage');

// Support chat API
$router->post('/api/support-chat', 'PageController', 'supportChat');

// Update API (for licensed installations)
$router->get('/api/updates/version', 'Api\\UpdateController', 'version');
$router->post('/api/updates/check', 'Api\\UpdateController', 'check');
$router->post('/api/updates/download', 'Api\\UpdateController', 'download');
$router->post('/api/updates/report', 'Api\\UpdateController', 'report');

// Sitemap & Robots
$router->get('/sitemap.xml', 'SitemapController', 'index');
$router->get('/robots.txt', 'SitemapController', 'robots');
$router->get('/manifest.json', 'SitemapController', 'manifest');

// Checkout routes
$router->get('/checkout', 'CheckoutController', 'index');
$router->post('/checkout/create-payment-intent', 'CheckoutController', 'createPaymentIntent');
$router->post('/checkout/apply-coupon', 'CheckoutController', 'applyCoupon');
$router->post('/checkout/remove-coupon', 'CheckoutController', 'removeCoupon');
$router->post('/checkout/process', 'CheckoutController', 'process');
$router->get('/checkout/confirm', 'CheckoutController', 'confirm');
$router->post('/webhook/stripe', 'CheckoutController', 'webhookStripe');

// Shipping routes
$router->get('/shipping/rates', 'ShippingController', 'getRates');
$router->post('/shipping/rates', 'ShippingController', 'getRates');
$router->get('/shipping/method-rate', 'ShippingController', 'getMethodRate');
$router->post('/shipping/method-rate', 'ShippingController', 'getMethodRate');
$router->post('/shipping/validate', 'ShippingController', 'validateMethod');

// Admin authentication routes
$router->get('/admin/login', 'Admin\\AuthController', 'login');
$router->post('/admin/login', 'Admin\\AuthController', 'doLogin');
$router->get('/admin/logout', 'Admin\\AuthController', 'logout');
$router->get('/admin/2fa/cancel', 'Admin\\AuthController', 'cancel2FA');

// Admin two-factor authentication routes
$router->get('/admin/2fa', 'Admin\\TwoFactorController', 'index');
$router->get('/admin/2fa/setup', 'Admin\\TwoFactorController', 'setup');
$router->post('/admin/2fa/enable', 'Admin\\TwoFactorController', 'enable');
$router->post('/admin/2fa/disable', 'Admin\\TwoFactorController', 'disable');
$router->post('/admin/2fa/regenerate-codes', 'Admin\\TwoFactorController', 'regenerateBackupCodes');

// Admin dashboard
$router->get('/admin', 'Admin\\DashboardController', 'index');

// Admin product routes
$router->get('/admin/products', 'Admin\\ProductController', 'index');
$router->get('/admin/products/create', 'Admin\\ProductController', 'create');
$router->post('/admin/products/store', 'Admin\\ProductController', 'store');
$router->get('/admin/products/:id/edit', 'Admin\\ProductController', 'edit');
$router->post('/admin/products/update', 'Admin\\ProductController', 'update');
$router->post('/admin/products/delete', 'Admin\\ProductController', 'delete');

// Admin product options and variants
$router->post('/admin/products/add-option', 'Admin\\ProductController', 'addOption');
$router->post('/admin/products/add-option-value', 'Admin\\ProductController', 'addOptionValue');
$router->post('/admin/products/delete-option', 'Admin\\ProductController', 'deleteOption');
$router->post('/admin/products/delete-option-value', 'Admin\\ProductController', 'deleteOptionValue');
$router->post('/admin/products/generate-variants', 'Admin\\ProductController', 'generateVariants');
$router->post('/admin/products/update-variant', 'Admin\\ProductController', 'updateVariant');
$router->post('/admin/products/mass-update-variant-prices', 'Admin\\ProductController', 'massUpdateVariantPrices');

// Admin product images
$router->post('/admin/products/upload-images', 'Admin\\ProductController', 'uploadImages');
$router->post('/admin/products/update-image', 'Admin\\ProductController', 'updateImage');
$router->post('/admin/products/delete-image', 'Admin\\ProductController', 'deleteImage');
$router->post('/admin/products/delete-images', 'Admin\\ProductController', 'deleteImages');
$router->post('/admin/products/reorder-images', 'Admin\\ProductController', 'reorderImages');
$router->post('/admin/products/move-images-to-sub', 'Admin\\ProductController', 'moveImagesToSub');

// Featured products ordering
$router->get('/admin/products/featured', 'Admin\\ProductController', 'featured');
$router->post('/admin/products/reorder-featured', 'Admin\\ProductController', 'reorderFeatured');
$router->post('/admin/products/remove-featured', 'Admin\\ProductController', 'removeFeatured');

// Product reordering and bulk actions
$router->post('/admin/products/reorder', 'Admin\\ProductController', 'reorderProducts');
$router->post('/admin/products/bulk-action', 'Admin\\ProductController', 'bulkAction');
$router->get('/admin/products/stats', 'Admin\\ProductController', 'stats');

// Admin customers routes
$router->get('/admin/customers', 'Admin\\CustomerController', 'index');
$router->post('/admin/customers/delete', 'Admin\\CustomerController', 'delete');

// Admin bundle routes
$router->get('/admin/bundles', 'Admin\\BundleController', 'index');
$router->get('/admin/bundles/create', 'Admin\\BundleController', 'create');
$router->post('/admin/bundles/store', 'Admin\\BundleController', 'store');
$router->get('/admin/bundles/:id/edit', 'Admin\\BundleController', 'edit');
$router->post('/admin/bundles/update', 'Admin\\BundleController', 'update');
$router->post('/admin/bundles/delete', 'Admin\\BundleController', 'delete');
$router->get('/admin/bundles/search-products', 'Admin\\BundleController', 'searchProducts');

// Admin coupon routes
$router->get('/admin/coupons', 'Admin\\CouponController', 'index');
$router->get('/admin/coupons/create', 'Admin\\CouponController', 'create');
$router->post('/admin/coupons/store', 'Admin\\CouponController', 'store');
$router->get('/admin/coupons/:id/edit', 'Admin\\CouponController', 'edit');
$router->post('/admin/coupons/update', 'Admin\\CouponController', 'update');
$router->post('/admin/coupons/delete', 'Admin\\CouponController', 'delete');
$router->get('/admin/coupons/generate-code', 'Admin\\CouponController', 'generateCode');
$router->post('/admin/coupons/toggle-status', 'Admin\\CouponController', 'toggleStatus');

// Admin pages routes
$router->get('/admin/pages', 'Admin\\PageController', 'index');
$router->get('/admin/pages/create', 'Admin\\PageController', 'create');
$router->post('/admin/pages/store', 'Admin\\PageController', 'store');
$router->get('/admin/pages/:id/edit', 'Admin\\PageController', 'edit');
$router->post('/admin/pages/update', 'Admin\\PageController', 'update');
$router->post('/admin/pages/delete', 'Admin\\PageController', 'delete');

// Admin category routes
$router->get('/admin/categories', 'Admin\\CategoryController', 'index');
$router->post('/admin/categories/store', 'Admin\\CategoryController', 'store');
$router->post('/admin/categories/update', 'Admin\\CategoryController', 'update');
$router->post('/admin/categories/delete', 'Admin\\CategoryController', 'delete');
$router->post('/admin/categories/reorder', 'Admin\\CategoryController', 'reorder');

// Admin stock notifications routes
$router->get('/admin/notifications', 'Admin\\NotificationController', 'index');
$router->post('/admin/notifications/cancel', 'Admin\\NotificationController', 'cancel');
$router->post('/admin/notifications/trigger', 'Admin\\NotificationController', 'trigger');

// Admin review routes
$router->get('/admin/reviews', 'Admin\\ReviewController', 'index');
$router->post('/admin/reviews/approve', 'Admin\\ReviewController', 'approve');
$router->post('/admin/reviews/reject', 'Admin\\ReviewController', 'reject');
$router->post('/admin/reviews/toggle-featured', 'Admin\\ReviewController', 'toggleFeatured');

// Admin inventory import routes
$router->get('/admin/inventory', 'Admin\\InventoryController', 'index');
$router->post('/admin/inventory/import', 'Admin\\InventoryController', 'import');
$router->get('/admin/inventory/template', 'Admin\\InventoryController', 'template');

// Admin newsletter routes
$router->get('/admin/newsletter', 'Admin\\NewsletterController', 'index');
$router->get('/admin/newsletter/compose', 'Admin\\NewsletterController', 'compose');
$router->post('/admin/newsletter/preview', 'Admin\\NewsletterController', 'preview');
$router->post('/admin/newsletter/send', 'Admin\\NewsletterController', 'send');
$router->get('/admin/newsletter/view/:id', 'Admin\\NewsletterController', 'view');
$router->post('/admin/newsletter/delete', 'Admin\\NewsletterController', 'delete');
$router->post('/admin/newsletter/resend', 'Admin\\NewsletterController', 'resend');
$router->get('/admin/newsletter/subscribers', 'Admin\\NewsletterController', 'subscribers');
$router->post('/admin/newsletter/subscribers/delete', 'Admin\\NewsletterController', 'deleteSubscriber');
$router->get('/admin/newsletter/export', 'Admin\\NewsletterController', 'export');

// Admin shipping routes
$router->get('/admin/shipping', 'Admin\\ShippingController', 'index');
$router->post('/admin/shipping/zones/store', 'Admin\\ShippingController', 'storeZone');
$router->post('/admin/shipping/zones/update', 'Admin\\ShippingController', 'updateZone');
$router->post('/admin/shipping/zones/delete', 'Admin\\ShippingController', 'deleteZone');
$router->post('/admin/shipping/methods/store', 'Admin\\ShippingController', 'storeMethod');
$router->post('/admin/shipping/methods/update', 'Admin\\ShippingController', 'updateMethod');
$router->post('/admin/shipping/methods/delete', 'Admin\\ShippingController', 'deleteMethod');
$router->post('/admin/shipping/origins/store', 'Admin\\ShippingController', 'storeOrigin');
$router->post('/admin/shipping/origins/update', 'Admin\\ShippingController', 'updateOrigin');
$router->post('/admin/shipping/origins/delete', 'Admin\\ShippingController', 'deleteOrigin');
$router->post('/admin/shipping/classes/store', 'Admin\\ShippingController', 'storeClass');
$router->post('/admin/shipping/classes/update', 'Admin\\ShippingController', 'updateClass');
$router->post('/admin/shipping/classes/delete', 'Admin\\ShippingController', 'deleteClass');

// Admin orders routes
$router->get('/admin/orders', 'Admin\\OrderController', 'index');
$router->get('/admin/orders/view', 'Admin\\OrderController', 'view');
$router->post('/admin/orders/status', 'Admin\\OrderController', 'updateStatus');
$router->post('/admin/orders/quick-status', 'Admin\\OrderController', 'quickStatus');
$router->post('/admin/orders/tracking', 'Admin\\OrderController', 'addTracking');
$router->post('/admin/orders/notes', 'Admin\\OrderController', 'updateNotes');
$router->post('/admin/orders/delete', 'Admin\\OrderController', 'delete');
$router->post('/admin/orders/update-shipping-cost', 'Admin\\OrderController', 'updateShippingCost');
$router->post('/admin/orders/update-item-cost', 'Admin\\OrderController', 'updateItemCost');
$router->get('/admin/orders/profits', 'Admin\\OrderController', 'profits');

// Admin visitors/analytics routes
$router->get('/admin/visitors', 'Admin\\VisitorController', 'index');

// Admin downloads tracking
$router->get('/admin/downloads', 'Admin\\DownloadController', 'index');

// Admin user management routes (super_admin only)
$router->get('/admin/users', 'Admin\\AdminUserController', 'index');
$router->get('/admin/users/create', 'Admin\\AdminUserController', 'create');
$router->post('/admin/users/store', 'Admin\\AdminUserController', 'store');
$router->get('/admin/users/edit', 'Admin\\AdminUserController', 'edit');
$router->post('/admin/users/update', 'Admin\\AdminUserController', 'update');
$router->post('/admin/users/delete', 'Admin\\AdminUserController', 'delete');

// Admin store settings routes
$router->get('/admin/settings', 'Admin\\SettingsController', 'index');
$router->post('/admin/settings/update', 'Admin\\SettingsController', 'update');
$router->post('/admin/settings/upload-logo', 'Admin\\SettingsController', 'uploadLogo');
$router->post('/admin/settings/remove-logo', 'Admin\\SettingsController', 'removeLogo');
$router->post('/admin/settings/upload-favicon', 'Admin\\SettingsController', 'uploadFavicon');
$router->post('/admin/settings/update-seo', 'Admin\\SettingsController', 'updateSeo');
$router->post('/admin/settings/upload-og-image', 'Admin\\SettingsController', 'uploadOgImage');
$router->post('/admin/settings/remove-og-image', 'Admin\\SettingsController', 'removeOgImage');
$router->get('/admin/settings/integrations', 'Admin\\SettingsController', 'integrations');
$router->post('/admin/settings/integrations/update', 'Admin\\SettingsController', 'updateIntegrations');
$router->get('/admin/settings/hero', 'Admin\\SettingsController', 'hero');
$router->post('/admin/settings/hero/update', 'Admin\\SettingsController', 'updateHero');
$router->post('/admin/settings/hero/upload-image', 'Admin\\SettingsController', 'uploadHeroImage');
$router->get('/admin/settings/social', 'Admin\\SettingsController', 'social');
$router->post('/admin/settings/social/update', 'Admin\\SettingsController', 'updateSocial');
$router->get('/admin/settings/payments', 'Admin\\SettingsController', 'payments');
$router->post('/admin/settings/payments/update', 'Admin\\SettingsController', 'updatePayments');
$router->post('/admin/settings/update-email', 'Admin\\SettingsController', 'updateEmail');
$router->post('/admin/settings/test-email', 'Admin\\SettingsController', 'testEmail');
$router->get('/admin/settings/menus', 'Admin\\SettingsController', 'menus');
$router->post('/admin/settings/menus', 'Admin\\SettingsController', 'updateMenus');

// Admin releases management (mothership only - manages releases for distribution)
if (strpos(appUrl(), 'apparix.app') !== false) {
    $router->get('/admin/releases', 'Admin\\ReleaseController', 'index');
    $router->get('/admin/releases/create', 'Admin\\ReleaseController', 'create');
    $router->post('/admin/releases/store', 'Admin\\ReleaseController', 'store');
    $router->get('/admin/releases/:id/edit', 'Admin\\ReleaseController', 'edit');
    $router->post('/admin/releases/update', 'Admin\\ReleaseController', 'update');
    $router->post('/admin/releases/delete', 'Admin\\ReleaseController', 'delete');
    $router->get('/admin/releases/logs', 'Admin\\ReleaseController', 'logs');
}

// Admin software updates (for customer sites - checks/installs updates)
$router->get('/admin/updates', 'Admin\\UpdateController', 'index');
$router->post('/admin/updates/check', 'Admin\\UpdateController', 'check');
$router->post('/admin/updates/install', 'Admin\\UpdateController', 'install');
$router->get('/admin/updates/version', 'Admin\\UpdateController', 'version');
$router->post('/admin/updates/cleanup-backups', 'Admin\\UpdateController', 'cleanupBackups');
$router->get('/admin/updates/download', 'Admin\\UpdateController', 'download');
$router->post('/admin/updates/restore', 'Admin\\UpdateController', 'restore');

// Admin plugin routes
$router->get('/admin/plugins', 'Admin\\PluginController', 'index');
$router->post('/admin/plugins/upload', 'Admin\\PluginController', 'upload');
$router->post('/admin/plugins/activate', 'Admin\\PluginController', 'activate');
$router->post('/admin/plugins/deactivate', 'Admin\\PluginController', 'deactivate');
$router->post('/admin/plugins/delete', 'Admin\\PluginController', 'delete');
$router->get('/admin/plugins/settings', 'Admin\\PluginController', 'settings');
$router->post('/admin/plugins/settings', 'Admin\\PluginController', 'saveSettings');

// Admin theme routes
$router->get('/admin/themes', 'Admin\\ThemeController', 'index');
$router->post('/admin/themes/activate', 'Admin\\ThemeController', 'activate');
$router->get('/admin/themes/customize', 'Admin\\ThemeController', 'customize');
$router->post('/admin/themes/save', 'Admin\\ThemeController', 'save');
$router->post('/admin/themes/quick-preview', 'Admin\\ThemeController', 'quickPreview');
$router->get('/admin/themes/create', 'Admin\\ThemeController', 'create');
$router->post('/admin/themes/create', 'Admin\\ThemeController', 'create');
$router->post('/admin/themes/delete', 'Admin\\ThemeController', 'delete');
$router->get('/admin/themes/preview-css', 'Admin\\ThemeController', 'previewCss');
$router->post('/admin/themes/upload', 'Admin\\ThemeController', 'upload');
$router->post('/admin/themes/activate-installed', 'Admin\\ThemeController', 'activateInstalled');
$router->post('/admin/themes/delete-installed', 'Admin\\ThemeController', 'deleteInstalled');
$router->post('/admin/themes/preview/start', 'Admin\\ThemeController', 'startPreview');
$router->post('/admin/themes/preview/activate', 'Admin\\ThemeController', 'activatePreview');
$router->post('/admin/themes/upload-logo', 'Admin\\ThemeController', 'uploadThemeLogo');
$router->post('/admin/themes/remove-logo', 'Admin\\ThemeController', 'removeThemeLogo');
$router->post('/admin/themes/upload-hero-image', 'Admin\\ThemeController', 'uploadThemeHeroImage');
$router->post('/admin/themes/remove-hero-image', 'Admin\\ThemeController', 'removeThemeHeroImage');
$router->post('/admin/themes/upload-navbar-bg-image', 'Admin\\ThemeController', 'uploadNavbarBgImage');
$router->post('/admin/themes/remove-navbar-bg-image', 'Admin\\ThemeController', 'removeNavbarBgImage');
$router->post('/admin/themes/upload-footer-bg-image', 'Admin\\ThemeController', 'uploadFooterBgImage');
$router->post('/admin/themes/remove-footer-bg-image', 'Admin\\ThemeController', 'removeFooterBgImage');

// Admin 404 error page customization
$router->get('/admin/themes/404', 'Admin\\ThemeController', 'errorPage');
$router->post('/admin/themes/404', 'Admin\\ThemeController', 'saveErrorPage');
$router->post('/admin/themes/404/remove-image', 'Admin\\ThemeController', 'removeErrorPageImage');

// License store routes (for apparix.app website)
$router->get('/pricing', 'LicenseStoreController', 'pricing');
$router->post('/license/checkout', 'LicenseStoreController', 'createCheckout');
$router->get('/license/success', 'LicenseStoreController', 'success');
$router->post('/license/resend', 'LicenseStoreController', 'resend');

// Initialize plugins (loads active plugins and fires init hooks)
$pluginManager = \App\Core\Plugins\PluginManager::getInstance();
$pluginManager->init();

// Community Hub plugin routes (only if plugin is active)
if ($pluginManager->getPlugin('community-hub')) {
    // Frontend forum routes
    $router->get('/community', 'CommunityHub\\ForumController', 'index');
    $router->get('/community/category/:slug', 'CommunityHub\\ForumController', 'category');
    $router->get('/community/topic/:slug', 'CommunityHub\\ForumController', 'topic');
    $router->get('/community/new-topic', 'CommunityHub\\ForumController', 'newTopicForm');
    $router->get('/community/new-topic/:categorySlug', 'CommunityHub\\ForumController', 'newTopicForm');
    $router->post('/community/topic/create', 'CommunityHub\\ForumController', 'createTopic');
    $router->post('/community/reply/create', 'CommunityHub\\ForumController', 'createReply');
    $router->get('/community/edit/topic/:id', 'CommunityHub\\ForumController', 'editTopicForm');
    $router->post('/community/edit/topic/:id', 'CommunityHub\\ForumController', 'updateTopic');
    $router->get('/community/edit/reply/:id', 'CommunityHub\\ForumController', 'editReplyForm');
    $router->post('/community/edit/reply/:id', 'CommunityHub\\ForumController', 'updateReply');
    $router->get('/community/user/:id', 'CommunityHub\\ForumController', 'userProfile');
    $router->post('/community/subscribe', 'CommunityHub\\ForumController', 'subscribe');
    $router->post('/community/unsubscribe', 'CommunityHub\\ForumController', 'unsubscribe');
    $router->get('/community/unsubscribe', 'CommunityHub\\ForumController', 'unsubscribe');
    $router->post('/community/report', 'CommunityHub\\ForumController', 'report');
    $router->get('/community/edit-history/:type/:id', 'CommunityHub\\ForumController', 'editHistory');

    // Admin forum routes
    $router->get('/admin/community', 'CommunityHub\\AdminForumController', 'dashboard');
    $router->get('/admin/community/categories', 'CommunityHub\\AdminForumController', 'categories');
    $router->post('/admin/community/categories/store', 'CommunityHub\\AdminForumController', 'storeCategory');
    $router->post('/admin/community/categories/update', 'CommunityHub\\AdminForumController', 'updateCategory');
    $router->post('/admin/community/categories/delete', 'CommunityHub\\AdminForumController', 'deleteCategory');
    $router->post('/admin/community/categories/reorder', 'CommunityHub\\AdminForumController', 'reorderCategories');
    $router->get('/admin/community/moderation', 'CommunityHub\\AdminForumController', 'moderation');
    $router->post('/admin/community/moderation/approve', 'CommunityHub\\AdminForumController', 'approvePost');
    $router->post('/admin/community/moderation/reject', 'CommunityHub\\AdminForumController', 'rejectPost');
    $router->get('/admin/community/moderators', 'CommunityHub\\AdminForumController', 'moderators');
    $router->post('/admin/community/moderators/add', 'CommunityHub\\AdminForumController', 'addModerator');
    $router->post('/admin/community/moderators/remove', 'CommunityHub\\AdminForumController', 'removeModerator');
    $router->get('/admin/community/reports', 'CommunityHub\\AdminForumController', 'reports');
    $router->post('/admin/community/reports/review', 'CommunityHub\\AdminForumController', 'reviewReport');
    $router->get('/admin/community/topic/:id', 'CommunityHub\\AdminForumController', 'topicDetail');
    $router->post('/admin/community/topic/lock', 'CommunityHub\\AdminForumController', 'lockTopic');
    $router->post('/admin/community/topic/unlock', 'CommunityHub\\AdminForumController', 'unlockTopic');
    $router->post('/admin/community/topic/pin', 'CommunityHub\\AdminForumController', 'pinTopic');
    $router->post('/admin/community/topic/unpin', 'CommunityHub\\AdminForumController', 'unpinTopic');
    $router->post('/admin/community/topic/close', 'CommunityHub\\AdminForumController', 'closeTopic');
    $router->post('/admin/community/topic/delete', 'CommunityHub\\AdminForumController', 'deleteTopic');
    $router->post('/admin/community/reply/delete', 'CommunityHub\\AdminForumController', 'deleteReply');
    $router->get('/admin/community/settings', 'CommunityHub\\AdminForumController', 'settings');
    $router->post('/admin/community/settings/update', 'CommunityHub\\AdminForumController', 'updateSettings');
}

// Dispatch the route
$router->dispatch();

// Flush output buffer if one exists
if (ob_get_level()) {
    ob_end_flush();
}
