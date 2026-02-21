<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape(appName()); ?> - Coming Soon</title>
    <meta name="description" content="<?php echo escape(appName()); ?> - Coming soon!">

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
    <link rel="stylesheet" href="/assets/css/main.css?v=95">

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

        /* Make bg-shapes visible on splash page */
        .bg-shapes {
            display: block !important;
            z-index: 0 !important;
        }

        .splash-container {
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

        .coming-soon-badge {
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
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .coming-soon-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px <?php echo escape($primaryColor); ?>50;
        }

        .features {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.75rem;
            margin-bottom: 2.5rem;
        }

        .feature-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: white;
            border: 1px solid #e2e8f0;
            color: #334155;
            padding: 0.6rem 1.1rem;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
        }

        .feature-tag:hover {
            border-color: <?php echo escape($primaryColor); ?>;
            box-shadow: 0 4px 12px <?php echo escape($primaryColor); ?>20;
            transform: translateY(-2px);
        }

        .feature-tag svg {
            color: <?php echo escape($primaryColor); ?>;
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

        .splash-footer {
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

        .splash-footer a {
            color: <?php echo escape($primaryColor); ?>;
            text-decoration: none;
            font-weight: 500;
        }

        .splash-footer a:hover {
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

            .coming-soon-badge {
                padding: 0.6rem 1.5rem;
                font-size: 0.75rem;
            }

            .features {
                gap: 0.5rem;
            }

            .feature-tag {
                font-size: 0.8rem;
                padding: 0.5rem 0.9rem;
            }

            .social-link {
                width: 44px;
                height: 44px;
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

    <div class="splash-container">
        <div class="logo-container">
            <img src="<?php echo storeLogo() ?: '/assets/images/apparix-logo.png'; ?>" alt="<?php echo escape(appName()); ?>">
        </div>

        <h1>Welcome to <span><?php echo escape(appName()); ?></span></h1>

        <p class="tagline">
            We're getting things ready. Something great is on its way.<br>
            Stay tuned!
        </p>

        <div class="coming-soon-badge">Coming Soon</div>

        <div class="features">
            <div class="feature-tag">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                </svg>
                Online Shopping
            </div>
            <div class="feature-tag">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
                Secure Checkout
            </div>
            <div class="feature-tag">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="1" y="3" width="15" height="13"></rect>
                    <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                    <circle cx="5.5" cy="18.5" r="2.5"></circle>
                    <circle cx="18.5" cy="18.5" r="2.5"></circle>
                </svg>
                Fast Shipping
            </div>
            <div class="feature-tag">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                </svg>
                Curated Selection
            </div>
        </div>

        <?php $contactEmail = storeEmail(); ?>
        <?php if ($contactEmail): ?>
        <a href="mailto:<?php echo escape($contactEmail); ?>" class="contact-link">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                <polyline points="22,6 12,13 2,6"></polyline>
            </svg>
            <?php echo escape($contactEmail); ?>
        </a>
        <?php endif; ?>
    </div>

    <footer class="splash-footer">
        <p>&copy; <?php echo date('Y'); ?> <?php echo escape(appName()); ?>. Built with <a href="https://apparix.app" target="_blank" rel="noopener">Apparix</a>.</p>
    </footer>
</body>
</html>
