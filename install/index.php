<?php
/**
 * Apparix E-Commerce Platform Installer
 *
 * This installer guides users through setting up their store:
 * 1. Requirements check
 * 2. Database configuration
 * 3. Store information
 * 4. Admin account creation
 * 5. Optional integrations (Stripe, Email, reCAPTCHA)
 * 6. Theme selection
 * 7. Completion
 */

// Prevent access if already installed
$basePath = dirname(__DIR__);
$lockFile = $basePath . '/storage/.installed';

if (file_exists($lockFile)) {
    header('Location: /');
    exit;
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

// Determine current step
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$step = max(1, min(7, $step));

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

                // Check if database exists
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
        case 1: // Requirements - just proceed
            return ['success' => true, 'redirect' => '/install?step=2'];

        case 2: // Database
            $_SESSION['install']['db_host'] = $_POST['db_host'] ?? 'localhost';
            $_SESSION['install']['db_name'] = $_POST['db_name'] ?? '';
            $_SESSION['install']['db_user'] = $_POST['db_user'] ?? '';
            $_SESSION['install']['db_pass'] = $_POST['db_pass'] ?? '';

            // Validate connection
            try {
                $installer = new Installer($_SESSION['install']);
                $installer->testDatabaseConnection();
                return ['success' => true, 'redirect' => '/install?step=3'];
            } catch (Exception $e) {
                return ['success' => false, 'error' => $e->getMessage()];
            }

        case 3: // Store info
            $_SESSION['install']['store_name'] = trim($_POST['store_name'] ?? '');
            $_SESSION['install']['store_url'] = trim($_POST['store_url'] ?? '');
            $_SESSION['install']['store_email'] = trim($_POST['store_email'] ?? '');

            if (empty($_SESSION['install']['store_name'])) {
                return ['success' => false, 'error' => 'Store name is required'];
            }

            return ['success' => true, 'redirect' => '/install?step=4'];

        case 4: // Admin account
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

            return ['success' => true, 'redirect' => '/install?step=5'];

        case 5: // Optional integrations
            $_SESSION['install']['stripe_public'] = trim($_POST['stripe_public'] ?? '');
            $_SESSION['install']['stripe_secret'] = trim($_POST['stripe_secret'] ?? '');
            $_SESSION['install']['mail_host'] = trim($_POST['mail_host'] ?? '');
            $_SESSION['install']['mail_port'] = trim($_POST['mail_port'] ?? '25');
            $_SESSION['install']['mail_from'] = trim($_POST['mail_from'] ?? '');
            $_SESSION['install']['recaptcha_site'] = trim($_POST['recaptcha_site'] ?? '');
            $_SESSION['install']['recaptcha_secret'] = trim($_POST['recaptcha_secret'] ?? '');

            return ['success' => true, 'redirect' => '/install?step=6'];

        case 6: // Theme selection
            $_SESSION['install']['theme'] = $_POST['theme'] ?? 'boutique';
            return ['success' => true, 'redirect' => '/install?step=7'];

        case 7: // Run installation
            try {
                $installer = new Installer($_SESSION['install']);
                $installer->run();

                // Clear session data
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
        $step = 8; // Use step 8 for complete (after step 7)
        include INSTALL_PATH . '/views/layout.php';
        return;
    }

    // Get requirements for step 1
    $requirements = null;
    if ($step === 1) {
        $checker = new RequirementsChecker();
        $requirements = $checker->check();
    }

    // Get themes for step 6
    $themes = null;
    if ($step === 6) {
        $themes = [
            'boutique' => [
                'name' => 'Boutique',
                'description' => 'Elegant and feminine, perfect for handmade goods',
                'color' => '#FF68C5'
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
