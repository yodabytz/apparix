<?php
/**
 * Apparix E-Commerce Platform Installer
 *
 * Steps:
 * 1. License key validation (REQUIRED — gates the entire installer)
 * 2. Requirements check
 * 3. Database configuration
 * 4. Store information
 * 5. Admin account creation
 * 6. Optional integrations (Stripe, Email, reCAPTCHA)
 * 7. Theme selection
 * 8. Completion
 */

// Prevent access if already installed
$basePath = dirname(__DIR__);
$lockFile = $basePath . '/storage/.installed';

if (file_exists($lockFile)) {
    if (isset($_GET['step']) && $_GET['step'] === 'complete') {
        // Allow the completion page to render
    } else {
        header('Location: /');
        exit;
    }
}

// Start session for multi-step data storage
session_start();

// Define paths
define('INSTALL_PATH', __DIR__);
define('BASE_PATH', $basePath);
define('PUBLIC_PATH', $basePath . '/public');

// Load installer classes
require_once INSTALL_PATH . '/classes/RequirementsChecker.php';
require_once INSTALL_PATH . '/classes/Installer.php';

// Load License class for validation
require_once BASE_PATH . '/app/Core/License.php';

// Load .env file for pre-filling fields
$envFile = $basePath . '/.env';
$envVars = [];
if (file_exists($envFile)) {
    $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') !== false) {
            list($key, $val) = explode('=', $line, 2);
            $envVars[trim($key)] = trim($val);
        }
    }
}

// Pre-fill DB credentials from .env if not already in session
if (!isset($_SESSION['install']['db_host']) && !empty($envVars)) {
    $envHost = $envVars['DB_HOST'] ?? ($_ENV['DB_HOST'] ?? getenv('DB_HOST'));
    $envName = $envVars['DB_NAME'] ?? ($_ENV['DB_NAME'] ?? getenv('DB_NAME'));
    $envUser = $envVars['DB_USER'] ?? ($_ENV['DB_USER'] ?? getenv('DB_USER'));
    $envPass = $envVars['DB_PASS'] ?? ($_ENV['DB_PASS'] ?? getenv('DB_PASS'));

    if ($envHost && $envName && $envUser) {
        $_SESSION['install']['db_host'] = $envHost;
        $_SESSION['install']['db_name'] = $envName;
        $_SESSION['install']['db_user'] = $envUser;
        $_SESSION['install']['db_pass'] = $envPass ?: '';
    }
}

// Pre-fill store URL and app name from .env
if (!isset($_SESSION['install']['store_url']) && !empty($envVars)) {
    if (!empty($envVars['APP_URL'])) {
        $_SESSION['install']['store_url'] = $envVars['APP_URL'];
    }
    if (!empty($envVars['APP_NAME'])) {
        $_SESSION['install']['store_name'] = $envVars['APP_NAME'];
    }
}

// Pre-fill license key from .env
if (!isset($_SESSION['install']['license_key']) && !empty($envVars['LICENSE_KEY'])) {
    $_SESSION['install']['license_key'] = $envVars['LICENSE_KEY'];
}

// Determine current step
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$step = max(1, min(8, $step));

// SECURITY: Block access to any step beyond 1 if license not validated
if ($step > 1 && empty($_SESSION['install']['license_validated'])) {
    $step = 1;
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    handleAjaxAction($_GET['action']);
    exit;
}

// Handle form submissions
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = handleStepSubmission($step);
    if ($result['success']) {
        if (isset($result['redirect'])) {
            header('Location: ' . $result['redirect']);
            exit;
        }
    } else {
        $error = $result['error'];
    }
}

// Render the appropriate step
renderStep($step, $error, $success);

/**
 * Handle AJAX actions
 */
function handleAjaxAction(string $action): void
{
    header('Content-Type: application/json');

    // Block AJAX if license not validated
    if (empty($_SESSION['install']['license_validated'])) {
        echo json_encode(['success' => false, 'error' => 'License not validated']);
        return;
    }

    switch ($action) {
        case 'test-database':
            $host = $_POST['db_host'] ?? 'localhost';
            $name = $_POST['db_name'] ?? '';
            $user = $_POST['db_user'] ?? '';
            $pass = $_POST['db_pass'] ?? '';

            try {
                $dsn = "mysql:host={$host};charset=utf8mb4";
                $pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);

                $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = " . $pdo->quote($name));
                $exists = $stmt->fetch();

                echo json_encode([
                    'success' => true,
                    'database_exists' => (bool)$exists,
                    'message' => $exists ? 'Connection successful. Database exists.' : 'Connection successful. Database will be created.'
                ]);
            } catch (PDOException $e) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Connection failed: ' . $e->getMessage()
                ]);
            }
            break;

        default:
            echo json_encode(['error' => 'Unknown action']);
    }
}

/**
 * Handle step form submissions
 */
function handleStepSubmission(int $step): array
{
    switch ($step) {
        case 1: // License key validation
            $licenseKey = trim($_POST['license_key'] ?? '');

            if (empty($licenseKey)) {
                return ['success' => false, 'error' => 'License key is required'];
            }

            // Validate using the License class
            $result = \App\Core\License::validateKey($licenseKey);

            if (!$result['valid']) {
                $errorMsg = 'Invalid license key';
                if (!empty($result['error'])) {
                    switch ($result['error']) {
                        case 'INVALID_FORMAT':
                            $errorMsg = 'Invalid license key format. Keys start with APX- followed by 4 groups of 5 characters.';
                            break;
                        case 'INVALID_CHECKSUM':
                            $errorMsg = 'This license key is not valid. Please check your key and try again.';
                            break;
                        case 'DOMAIN_MISMATCH':
                            $errorMsg = 'This license key is locked to a different domain.';
                            break;
                        default:
                            $errorMsg = 'License validation failed: ' . $result['error'];
                    }
                }
                return ['success' => false, 'error' => $errorMsg];
            }

            $_SESSION['install']['license_key'] = $licenseKey;
            $_SESSION['install']['license_edition'] = $result['edition'] ?? 'standard';
            $_SESSION['install']['license_validated'] = true;

            return ['success' => true, 'redirect' => '/install?step=2'];

        case 2: // Requirements - just proceed
            return ['success' => true, 'redirect' => '/install?step=3'];

        case 3: // Database
            $_SESSION['install']['db_host'] = $_POST['db_host'] ?? 'localhost';
            $_SESSION['install']['db_name'] = $_POST['db_name'] ?? '';
            $_SESSION['install']['db_user'] = $_POST['db_user'] ?? '';
            $_SESSION['install']['db_pass'] = $_POST['db_pass'] ?? '';

            try {
                $installer = new Installer($_SESSION['install']);
                $installer->testDatabaseConnection();
                return ['success' => true, 'redirect' => '/install?step=4'];
            } catch (Exception $e) {
                return ['success' => false, 'error' => $e->getMessage()];
            }

        case 4: // Store info
            $_SESSION['install']['store_name'] = trim($_POST['store_name'] ?? '');
            $_SESSION['install']['store_url'] = trim($_POST['store_url'] ?? '');
            $_SESSION['install']['store_email'] = trim($_POST['store_email'] ?? '');

            if (empty($_SESSION['install']['store_name'])) {
                return ['success' => false, 'error' => 'Store name is required'];
            }

            return ['success' => true, 'redirect' => '/install?step=5'];

        case 5: // Admin account
            $_SESSION['install']['admin_name'] = trim($_POST['admin_name'] ?? '');
            $_SESSION['install']['admin_email'] = trim($_POST['admin_email'] ?? '');
            $_SESSION['install']['admin_password'] = $_POST['admin_password'] ?? '';

            if (empty($_SESSION['install']['admin_name'])) {
                return ['success' => false, 'error' => 'Admin name is required'];
            }
            if (empty($_SESSION['install']['admin_email']) || !filter_var($_SESSION['install']['admin_email'], FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'error' => 'Valid admin email is required'];
            }
            if (strlen($_SESSION['install']['admin_password']) < 8) {
                return ['success' => false, 'error' => 'Password must be at least 8 characters'];
            }

            return ['success' => true, 'redirect' => '/install?step=6'];

        case 6: // Optional integrations
            $_SESSION['install']['stripe_public'] = trim($_POST['stripe_public'] ?? '');
            $_SESSION['install']['stripe_secret'] = trim($_POST['stripe_secret'] ?? '');
            $_SESSION['install']['mail_host'] = trim($_POST['mail_host'] ?? '');
            $_SESSION['install']['mail_port'] = trim($_POST['mail_port'] ?? '25');
            $_SESSION['install']['mail_from'] = trim($_POST['mail_from'] ?? '');
            $_SESSION['install']['recaptcha_site'] = trim($_POST['recaptcha_site'] ?? '');
            $_SESSION['install']['recaptcha_secret'] = trim($_POST['recaptcha_secret'] ?? '');

            return ['success' => true, 'redirect' => '/install?step=7'];

        case 7: // Theme selection
            $_SESSION['install']['theme'] = $_POST['theme'] ?? 'apparix';
            return ['success' => true, 'redirect' => '/install?step=8'];

        case 8: // Run installation
            try {
                $installer = new Installer($_SESSION['install']);
                $installer->run();

                unset($_SESSION['install']);

                return ['success' => true, 'redirect' => '/install?step=complete'];
            } catch (Exception $e) {
                return ['success' => false, 'error' => 'Installation failed: ' . $e->getMessage()];
            }

        default:
            return ['success' => false, 'error' => 'Invalid step'];
    }
}

/**
 * Render a step view
 */
function renderStep(int $step, ?string $error, ?string $success): void
{
    // Check if installation is complete
    if (isset($_GET['step']) && $_GET['step'] === 'complete') {
        $viewFile = INSTALL_PATH . '/views/complete.php';
        $step = 9;
        include INSTALL_PATH . '/views/layout.php';
        return;
    }

    // Get requirements for step 2
    $requirements = null;
    if ($step === 2) {
        $checker = new RequirementsChecker();
        $requirements = $checker->check();
    }

    // Get themes for step 7
    $themes = null;
    if ($step === 7) {
        $themes = [
            'apparix' => [
                'name' => 'Apparix',
                'description' => 'Clean and modern, the default Apparix look',
                'color' => '#2186c4'
            ],
            'boutique' => [
                'name' => 'Boutique',
                'description' => 'Elegant and refined, perfect for handmade goods',
                'color' => '#FF68C5'
            ],
            'celtic' => [
                'name' => 'Celtic',
                'description' => 'Rich heritage tones, ideal for artisan and craft stores',
                'color' => '#1b5e3a'
            ],
            'tech' => [
                'name' => 'Tech',
                'description' => 'Modern and minimal for electronics and software',
                'color' => '#3B82F6'
            ],
            'fashion' => [
                'name' => 'Fashion',
                'description' => 'Bold and editorial for clothing and accessories',
                'color' => '#000000'
            ],
            'general' => [
                'name' => 'General',
                'description' => 'Versatile and professional, works for any industry',
                'color' => '#10B981'
            ]
        ];
    }

    // Include the layout with content
    $viewFile = INSTALL_PATH . '/views/step-' . $step . '.php';
    if (!file_exists($viewFile)) {
        $viewFile = INSTALL_PATH . '/views/step-1.php';
        $step = 1;
    }

    include INSTALL_PATH . '/views/layout.php';
}
