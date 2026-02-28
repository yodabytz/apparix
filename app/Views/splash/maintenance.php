<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape(appName()); ?> - Under Maintenance</title>
    <meta name="description" content="<?php echo escape(appName()); ?> is currently undergoing scheduled maintenance. We'll be back shortly.">
    <meta name="robots" content="noindex">

    <?php
    // Initialize theme service for colors and effects
    $themeService = new \App\Core\ThemeService();
    $theme = $themeService->getActiveTheme();
    $primaryColor = $theme['primary_color'] ?? '#2186c4';
    $secondaryColor = $theme['secondary_color'] ?? '#83b1ec';
    $accentColor = $theme['accent_color'] ?? '#5d82b1';
    $glowColor = $theme['glow_color'] ?? $accentColor;

    // Get effect settings
    $bgAnimationEnabled = $themeService->isBackgroundAnimationEnabled();
    $bgAnimationClass = $themeService->getBackgroundAnimationClass();
    ?>

    <?php $gaId = setting('google_analytics_id'); ?>
    <?php if ($gaId): ?>
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo escape($gaId); ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '<?php echo escape($gaId); ?>');
    </script>
    <?php endif; ?>

    <!-- Favicons -->
    <?php $customFavicon = setting('store_favicon'); ?>
    <?php if ($customFavicon): ?>
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo escape($customFavicon); ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo escape($customFavicon); ?>">
    <link rel="shortcut icon" href="<?php echo escape($customFavicon); ?>">
    <?php else: ?>
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="shortcut icon" href="/favicon.ico">
    <?php endif; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="<?php echo $themeService->getGoogleFontsUrl(); ?>" rel="stylesheet">

    <!-- Main CSS for visual effects -->
    <link rel="stylesheet" href="/assets/css/main.css?v=106">

    <?php
    // Inject full dynamic theme CSS variables
    $themeCss = $themeService->generateCssVariables();
    if ($themeCss): ?>
    <style id="theme-variables"><?php echo $themeCss; ?></style>
    <?php endif; ?>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
        }

        body {
            font-family: var(--font-body, 'Inter'), -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 50%, #f8fafc 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        /* Make bg-shapes visible on maintenance page */
        .bg-shapes {
            display: block !important;
            z-index: 0 !important;
        }

        .maintenance-container {
            position: relative;
            z-index: 101;
            text-align: center;
            max-width: 650px;
            animation: fadeInUp 0.8s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-container {
            margin-bottom: 2rem;
        }

        .logo-container img {
            max-width: 280px;
            height: auto;
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.1));
        }

        .maintenance-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, <?php echo escape($primaryColor); ?>, <?php echo escape($accentColor); ?>);
            border-radius: 50%;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 30px <?php echo escape($primaryColor); ?>30;
            animation: pulse 2s ease-in-out infinite;
        }

        .maintenance-icon svg {
            color: white;
            width: 36px;
            height: 36px;
        }

        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 10px 30px <?php echo escape($primaryColor); ?>30;
            }
            50% {
                box-shadow: 0 10px 40px <?php echo escape($primaryColor); ?>50;
            }
        }

        h1 {
            font-family: var(--font-heading, 'Montserrat'), sans-serif;
            font-size: 2.75rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
        }

        h1 span {
            background: linear-gradient(135deg, <?php echo escape($primaryColor); ?>, <?php echo escape($accentColor); ?>);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .tagline {
            font-size: 1.2rem;
            color: #64748b;
            margin-bottom: 2.5rem;
            font-weight: 400;
            line-height: 1.7;
        }

        .status-badge {
            display: inline-block;
            background: linear-gradient(135deg, <?php echo escape($primaryColor); ?>, <?php echo escape($accentColor); ?>);
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.875rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px <?php echo escape($primaryColor); ?>40;
        }

        .contact-link {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            color: <?php echo escape($primaryColor); ?>;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 0.7rem 1.5rem;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }

        .contact-link svg {
            color: <?php echo escape($primaryColor); ?>;
            flex-shrink: 0;
        }

        .contact-link:hover {
            transform: translateY(-2px);
            border-color: <?php echo escape($primaryColor); ?>;
            box-shadow: 0 6px 16px <?php echo escape($primaryColor); ?>25;
        }

        .maintenance-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1.5rem;
            text-align: center;
            color: #94a3b8;
            font-size: 0.85rem;
            z-index: 101;
            background: linear-gradient(to top, rgba(255,255,255,0.9), transparent);
        }

        .maintenance-footer a {
            color: <?php echo escape($primaryColor); ?>;
            text-decoration: none;
            font-weight: 500;
        }

        .maintenance-footer a:hover {
            text-decoration: underline;
        }

        /* Mobile responsiveness */
        @media (max-width: 768px) {
            body {
                padding: 1.5rem;
            }

            h1 {
                font-size: 1.75rem;
            }

            .tagline {
                font-size: 1rem;
            }

            .logo-container img {
                max-width: 200px;
            }

            .maintenance-icon {
                width: 64px;
                height: 64px;
            }

            .maintenance-icon svg {
                width: 28px;
                height: 28px;
            }

            .status-badge {
                padding: 0.6rem 1.5rem;
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <!-- Animated background shapes (uses theme visual effects) -->
    <?php if ($bgAnimationEnabled): ?>
    <div class="bg-shapes <?php echo $bgAnimationClass; ?>" aria-hidden="true">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    <?php endif; ?>

    <div class="maintenance-container">
        <div class="logo-container">
            <?php
            $mLogo = storeLogo();
            $mLogoWhite = '/assets/images/apparix-logo-white.png';
            if ($mLogo) {
                $whitePath = preg_replace('/(\.[^.]+)$/', '-white$1', $mLogo);
                if (file_exists((defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2)) . '/public' . $whitePath)) {
                    $mLogoWhite = $whitePath;
                } else {
                    $mLogoWhite = $mLogo;
                }
            }
            ?>
            <img src="<?php echo escape($mLogoWhite); ?>?v=2" alt="<?php echo escape(appName()); ?>">
        </div>

        <div class="maintenance-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3"></circle>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
            </svg>
        </div>

        <h1>We'll Be <span>Back Soon</span></h1>

        <p class="tagline">
            We're performing scheduled maintenance to improve your experience.<br>
            We'll be back shortly.
        </p>

        <div class="status-badge">Under Maintenance</div>

        <?php $contactEmail = storeEmail(); ?>
        <?php if ($contactEmail): ?>
        <div>
            <a href="mailto:<?php echo escape($contactEmail); ?>" class="contact-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                    <polyline points="22,6 12,13 2,6"></polyline>
                </svg>
                <?php echo escape($contactEmail); ?>
            </a>
        </div>
        <?php endif; ?>
    </div>

    <footer class="maintenance-footer">
        <p>&copy; <?php echo date('Y'); ?> <?php echo escape(appName()); ?>.<?php
            $showPoweredBy = setting('show_powered_by') || \App\Core\License::isFree();
            if ($showPoweredBy): ?> Powered by <a href="https://apparix.app" target="_blank" rel="noopener">Apparix</a>.<?php endif; ?></p>
    </footer>
</body>
</html>
