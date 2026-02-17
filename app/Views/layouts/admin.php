<?php
// Prevent browser caching of admin pages
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

// Get pending orders count for badge
$pendingOrdersCount = 0;
if (isset($admin)) {
    $orderModel = new \App\Models\Order();
    $pendingOrdersCount = $orderModel->countOrders('pending') + $orderModel->countOrders('processing');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title><?php echo isset($title) ? escape($title) . ' | ' : ''; ?>Apparix Admin</title>
    <link rel="icon" href="/favicon.ico">
    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/admin.css?v=26">
    <?php
    // Get theme colors from ThemeService to match admin panel with site theme
    $themeService = \App\Core\ThemeService::getInstance();
    $themeColors = $themeService->getColors();
    $primaryColor = $themeColors['primary'] ?? '#FF68C5';
    $secondaryColor = $themeColors['secondary'] ?? '#ff4db8';

    // Calculate darker shade for hover
    $primaryHover = $themeService->adjustBrightness($primaryColor, -15);
    $primaryDark = $themeService->adjustBrightness($primaryColor, -25);
    ?>
    <style>
        :root {
            --admin-primary: <?php echo $primaryColor; ?>;
            --admin-primary-hover: <?php echo $primaryHover; ?>;
            --admin-primary-dark: <?php echo $primaryDark; ?>;
        }
    </style>
</head>
<body>
    <?php if (isset($admin)): ?>
    <?php
    // Get store logo for admin panel
    $adminStoreLogo = storeLogo();
    $adminStoreName = appName();
    ?>
    <!-- Mobile Header -->
    <header class="admin-mobile-header">
        <button class="admin-menu-toggle" id="adminMenuToggle" aria-label="Toggle menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <a href="/admin" class="admin-mobile-logo">
            <?php if ($adminStoreLogo): ?>
                <img src="<?php echo escape($adminStoreLogo); ?>" alt="<?php echo escape($adminStoreName); ?>" height="28">
            <?php else: ?>
                <span class="admin-logo-text"><?php echo escape($adminStoreName); ?></span>
            <?php endif; ?>
        </a>
        <a href="/" class="admin-mobile-view-site" target="_blank" title="View Site">&#127760;</a>
    </header>

    <!-- Sidebar Overlay -->
    <div class="admin-sidebar-overlay" id="adminSidebarOverlay"></div>

    <!-- Admin Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-header">
            <a href="/admin" class="admin-logo">
                <?php if ($adminStoreLogo): ?>
                    <img src="<?php echo escape($adminStoreLogo); ?>" alt="<?php echo escape($adminStoreName); ?>" height="32">
                <?php else: ?>
                    <span class="admin-logo-text"><?php echo escape($adminStoreName); ?></span>
                <?php endif; ?>
            </a>
        </div>

        <nav class="sidebar-nav">
            <a href="/admin" class="nav-item <?php echo ($_SERVER['REQUEST_URI'] === '/admin' || $_SERVER['REQUEST_URI'] === '/admin/') ? 'active' : ''; ?>">
                <span class="nav-icon">&#128202;</span> Dashboard
            </a>
            <a href="/admin/products" class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/products') === 0 ? 'active' : ''; ?>">
                <span class="nav-icon">&#128722;</span> Products
            </a>
            <a href="/admin/orders" class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/orders') === 0 ? 'active' : ''; ?>">
                <span class="nav-icon">&#128230;</span> Orders<?php if ($pendingOrdersCount > 0): ?><span class="order-badge"><?php echo $pendingOrdersCount; ?></span><?php endif; ?>
            </a>
            <a href="/admin/customers" class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/customers') === 0 ? 'active' : ''; ?>">
                <span class="nav-icon">&#128101;</span> Customers
            </a>
            <a href="/admin/coupons" class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/coupons') === 0 ? 'active' : ''; ?>">
                <span class="nav-icon">&#127991;</span> Coupons
            </a>
            <a href="/admin/categories" class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/categories') === 0 ? 'active' : ''; ?>">
                <span class="nav-icon">&#128193;</span> Categories
            </a>
            <a href="/admin/inventory" class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/inventory') === 0 ? 'active' : ''; ?>">
                <span class="nav-icon">&#128230;</span> Inventory
            </a>
            <a href="/admin/shipping" class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/shipping') === 0 ? 'active' : ''; ?>">
                <span class="nav-icon">&#128666;</span> Shipping
            </a>
            <a href="/admin/newsletter" class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/newsletter') === 0 ? 'active' : ''; ?>">
                <span class="nav-icon">&#128231;</span> Newsletter
            </a>
            <a href="/admin/notifications" class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/notifications') === 0 ? 'active' : ''; ?>">
                <span class="nav-icon">&#128276;</span> Stock Alerts
            </a>
            <a href="/admin/reviews" class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/reviews') === 0 ? 'active' : ''; ?>">
                <span class="nav-icon">&#11088;</span> Reviews
            </a>
            <a href="/admin/visitors" class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/visitors') === 0 ? 'active' : ''; ?>">
                <span class="nav-icon">&#128200;</span> Visitors
            </a>

            <div class="nav-divider"></div>
            <span class="nav-section-title">Settings</span>

            <a href="/admin/settings" class="nav-item <?php echo ($_SERVER['REQUEST_URI'] === '/admin/settings' || strpos($_SERVER['REQUEST_URI'], '/admin/settings/upload') === 0 || strpos($_SERVER['REQUEST_URI'], '/admin/settings/remove') === 0) ? 'active' : ''; ?>">
                <span class="nav-icon">&#9881;</span> Store Settings
            </a>
            <a href="/admin/settings/hero" class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/settings/hero') === 0 ? 'active' : ''; ?>">
                <span class="nav-icon">&#127775;</span> Hero Section
            </a>
            <a href="/admin/settings/social" class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/settings/social') === 0 ? 'active' : ''; ?>">
                <span class="nav-icon">&#128279;</span> Social Links
            </a>
            <a href="/admin/settings/payments" class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/settings/payments') === 0 ? 'active' : ''; ?>">
                <span class="nav-icon">&#128179;</span> Payments
            </a>
            <a href="/admin/settings/integrations" class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/settings/integrations') === 0 ? 'active' : ''; ?>">
                <span class="nav-icon">&#128202;</span> Analytics
            </a>
            <a href="/admin/themes" class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/themes') === 0 ? 'active' : ''; ?>">
                <span class="nav-icon">&#127912;</span> Themes
            </a>
            <a href="/admin/plugins" class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/plugins') === 0 ? 'active' : ''; ?>">
                <span class="nav-icon">&#128268;</span> Plugins
            </a>
            <a href="/admin/releases" class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/releases') === 0 ? 'active' : ''; ?>">
                <span class="nav-icon">&#128230;</span> Releases
            </a>
            <a href="/admin/updates" class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/updates') === 0 ? 'active' : ''; ?>">
                <span class="nav-icon">&#128259;</span> Updates
            </a>
            <?php if (isset($admin['role']) && $admin['role'] === 'super_admin'): ?>
            <a href="/admin/users" class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/users') === 0 ? 'active' : ''; ?>">
                <span class="nav-icon">&#128101;</span> Admin Users
            </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <div class="admin-info">
                <span class="admin-name"><?php echo escape($admin['name']); ?></span>
                <span class="admin-role"><?php echo ucfirst($admin['role']); ?></span>
            </div>
            <a href="/" class="nav-item" target="_blank">
                <span class="nav-icon">&#127760;</span> View Site
            </a>
            <a href="/admin/logout" class="nav-item logout">
                <span class="nav-icon">&#128682;</span> Logout
            </a>
        </div>
    </aside>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="admin-main <?php echo isset($admin) ? 'with-sidebar' : ''; ?>">
        <?php if ($flash = getFlash('success')): ?>
            <div class="alert alert-success"><?php echo escape($flash); ?></div>
        <?php endif; ?>
        <?php if ($flash = getFlash('error')): ?>
            <div class="alert alert-error"><?php echo escape($flash); ?></div>
        <?php endif; ?>

        <?php echo $content; ?>
    </main>

    <script src="/assets/js/admin.js?v=12"></script>

    <!-- Mobile Menu Toggle -->
    <script>
    (function() {
        const toggle = document.getElementById('adminMenuToggle');
        const sidebar = document.getElementById('adminSidebar');
        const overlay = document.getElementById('adminSidebarOverlay');

        if (toggle && sidebar && overlay) {
            function openMenu() {
                sidebar.classList.add('active');
                overlay.classList.add('active');
                toggle.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function closeMenu() {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                toggle.classList.remove('active');
                document.body.style.overflow = '';
            }

            toggle.addEventListener('click', function() {
                if (sidebar.classList.contains('active')) {
                    closeMenu();
                } else {
                    openMenu();
                }
            });

            overlay.addEventListener('click', closeMenu);

            // Close menu when clicking nav items
            sidebar.querySelectorAll('.nav-item').forEach(function(item) {
                item.addEventListener('click', closeMenu);
            });

            // Close on resize to desktop
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    closeMenu();
                }
            });
        }
    })();
    </script>
</body>
</html>
