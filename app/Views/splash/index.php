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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Main CSS for visual effects -->
    <link rel="stylesheet" href="/assets/css/main.css?v=91">

    <style>
        /* Override theme CSS variables with active theme colors */
        :root {
            --primary-pink: <?php echo escape($primaryColor); ?> !important;
            --secondary-pink: <?php echo escape($secondaryColor); ?> !important;
            --glow-color: <?php echo escape($glowColor); ?> !important;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
        }

        body {
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, sans-serif;
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
            font-family: 'Montserrat', sans-serif;
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

        .social-links {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .social-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            color: #64748b;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .social-link:hover {
            transform: translateY(-3px);
            background: <?php echo escape($primaryColor); ?>;
            border-color: <?php echo escape($primaryColor); ?>;
            color: white;
            box-shadow: 0 8px 20px <?php echo escape($primaryColor); ?>40;
        }

        .contact-info {
            color: #64748b;
            font-size: 0.95rem;
        }

        .contact-info a {
            color: <?php echo escape($primaryColor); ?>;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .contact-info a:hover {
            color: <?php echo escape($accentColor); ?>;
            text-decoration: underline;
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

        <h1>Modern <span>E-Commerce</span> Platform</h1>

        <p class="tagline">
            A powerful, self-hosted shopping cart and content management system.<br>
            Built for performance. Designed for growth.
        </p>

        <div class="coming-soon-badge">Coming Soon</div>

        <div class="features">
            <div class="feature-tag">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                </svg>
                Product Management
            </div>
            <div class="feature-tag">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                    <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
                Payment Gateways
            </div>
            <div class="feature-tag">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                    <path d="M2 17l10 5 10-5"></path>
                    <path d="M2 12l10 5 10-5"></path>
                </svg>
                Plugin System
            </div>
            <div class="feature-tag">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="2" y1="12" x2="22" y2="12"></line>
                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                </svg>
                Marketplace Sync
            </div>
        </div>

        <div class="social-links">
            <a href="mailto:<?php echo escape(storeEmail()); ?>" class="social-link" title="Email Us">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                    <polyline points="22,6 12,13 2,6"></polyline>
                </svg>
            </a>
            <a href="https://github.com" class="social-link" title="GitHub" target="_blank" rel="noopener">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                </svg>
            </a>
        </div>

        <p class="contact-info">
            Questions? <a href="mailto:<?php echo escape(storeEmail()); ?>"><?php echo escape(storeEmail()); ?></a>
        </p>
    </div>

    <footer class="splash-footer">
        <p>&copy; <?php echo date('Y'); ?> <?php echo escape(appName()); ?>. Built with <a href="https://apparix.app" target="_blank" rel="noopener">Apparix</a>.</p>
    </footer>
</body>
</html>
