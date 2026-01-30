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

// Set default environment variables
$_ENV['DB_HOST'] = $_ENV['DB_HOST'] ?? 'localhost';
$_ENV['DB_NAME'] = $_ENV['DB_NAME'] ?? 'lilyspad_ecommerce';
$_ENV['DB_USER'] = $_ENV['DB_USER'] ?? 'lilyspad';
$_ENV['DB_PASS'] = $_ENV['DB_PASS'] ?? 'lilyspad_secure_2024';

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
}

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' https://js.stripe.com https://cdn.jsdelivr.net https://pagead2.googlesyndication.com https://www.googletagservices.com https://adservice.google.com https://www.googletagmanager.com https://www.google-analytics.com https://www.google.com https://www.gstatic.com; frame-src https://js.stripe.com https://googleads.g.doubleclick.net https://www.google.com https://tpc.googlesyndication.com https://www.recaptcha.net; style-src \'self\' \'unsafe-inline\' https://fonts.googleapis.com; font-src \'self\' https://fonts.gstatic.com; img-src \'self\' data: https:; connect-src \'self\' https://pagead2.googlesyndication.com https://www.google-analytics.com https://analytics.google.com https://www.google.com https://www.gstatic.com');

// Configure session security with secure defaults
session_set_cookie_params([
    'lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 604800),
    'path' => '/',
    'domain' => '',  // Empty for current domain only
    'secure' => true,  // Always use secure cookies (HTTPS)
    'httponly' => true, // Prevent JavaScript access to session cookie
    'samesite' => 'Lax' // Lax allows same-site form submissions; Strict can block them
]);

// Don't use strict session mode - it causes issues when sessions expire
// The CSRF token provides the actual protection against session fixation
ini_set('session.use_strict_mode', '0');

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
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
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
$skipTracking = str_starts_with($requestPath, '/admin') ||
                str_starts_with($requestPath, '/api') ||
                str_starts_with($requestPath, '/assets') ||
                in_array($visitorIp, $excludedIps) ||
                preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot|map)$/i', $requestPath);

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

        if ($ip && $ip !== '127.0.0.1' && !str_starts_with($ip, '192.168.') && !str_starts_with($ip, '10.')) {
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
$bypassSplash = isset($_GET['preview']) || isset($_COOKIE['bypass_splash']);
$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Define paths that should always be accessible (even in maintenance mode)
$allowedPaths = [
    '/admin',           // Admin panel
    '/webhook',         // Webhooks for payment providers
    '/api',             // API endpoints
    '/assets',          // Static assets
    '/cron',            // Cron endpoints
];

// Check if current path should bypass maintenance
$isAllowedPath = false;
foreach ($allowedPaths as $allowed) {
    if (strpos($requestPath, $allowed) === 0) {
        $isAllowedPath = true;
        break;
    }
}

// Maintenance mode: Block ALL public routes if enabled and not bypassed
if ($showSplash && !$bypassSplash && !$isAllowedPath) {
    // Show splash page for any non-allowed route
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

// User routes
$router->get('/login', 'UserController', 'loginForm');
$router->post('/login', 'UserController', 'login');
$router->get('/register', 'UserController', 'registerForm');
$router->post('/register', 'UserController', 'register');
$router->get('/logout', 'UserController', 'logout');
$router->get('/account', 'UserController', 'dashboard');
$router->get('/account/orders', 'UserController', 'orders');
$router->post('/account/update-profile', 'UserController', 'updateProfile');
$router->post('/account/change-password', 'UserController', 'changePassword');

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

// Support chat API
$router->post('/api/support-chat', 'PageController', 'supportChat');

// Update API (for licensed installations)
$router->get('/api/updates/version', 'Api\\UpdateController', 'version');
$router->post('/api/updates/check', 'Api\\UpdateController', 'check');
$router->post('/api/updates/download', 'Api\\UpdateController', 'download');
$router->post('/api/updates/report', 'Api\\UpdateController', 'report');

// Help chat test page (temporary - remove after testing)
$router->get('/test-help-chat', 'PageController', 'testHelpChat');

// Sitemap
$router->get('/sitemap.xml', 'SitemapController', 'index');

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

// Admin coupon routes
$router->get('/admin/coupons', 'Admin\\CouponController', 'index');
$router->get('/admin/coupons/create', 'Admin\\CouponController', 'create');
$router->post('/admin/coupons/store', 'Admin\\CouponController', 'store');
$router->get('/admin/coupons/:id/edit', 'Admin\\CouponController', 'edit');
$router->post('/admin/coupons/update', 'Admin\\CouponController', 'update');
$router->post('/admin/coupons/delete', 'Admin\\CouponController', 'delete');
$router->get('/admin/coupons/generate-code', 'Admin\\CouponController', 'generateCode');
$router->post('/admin/coupons/toggle-status', 'Admin\\CouponController', 'toggleStatus');

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

// Admin visitors/analytics routes
$router->get('/admin/visitors', 'Admin\\VisitorController', 'index');

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

// Admin releases management (for this site - manages releases)
$router->get('/admin/releases', 'Admin\\ReleaseController', 'index');
$router->get('/admin/releases/create', 'Admin\\ReleaseController', 'create');
$router->post('/admin/releases/store', 'Admin\\ReleaseController', 'store');
$router->get('/admin/releases/:id/edit', 'Admin\\ReleaseController', 'edit');
$router->post('/admin/releases/update', 'Admin\\ReleaseController', 'update');
$router->post('/admin/releases/delete', 'Admin\\ReleaseController', 'delete');
$router->get('/admin/releases/logs', 'Admin\\ReleaseController', 'logs');

// Admin software updates (for customer sites - checks/installs updates)
$router->get('/admin/updates', 'Admin\\UpdateController', 'index');
$router->post('/admin/updates/check', 'Admin\\UpdateController', 'check');
$router->post('/admin/updates/install', 'Admin\\UpdateController', 'install');
$router->get('/admin/updates/version', 'Admin\\UpdateController', 'version');
$router->post('/admin/updates/cleanup-backups', 'Admin\\UpdateController', 'cleanupBackups');

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
$router->get('/admin/themes/create', 'Admin\\ThemeController', 'create');
$router->post('/admin/themes/create', 'Admin\\ThemeController', 'create');
$router->post('/admin/themes/delete', 'Admin\\ThemeController', 'delete');
$router->get('/admin/themes/preview-css', 'Admin\\ThemeController', 'previewCss');
$router->post('/admin/themes/upload', 'Admin\\ThemeController', 'upload');
$router->post('/admin/themes/activate-installed', 'Admin\\ThemeController', 'activateInstalled');
$router->post('/admin/themes/delete-installed', 'Admin\\ThemeController', 'deleteInstalled');

// Dispatch the route
$router->dispatch();

// Flush output buffer if one exists
if (ob_get_level()) {
    ob_end_flush();
}
