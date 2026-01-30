<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <?php
    // Initialize theme service early (needed for theme-color meta tag)
    $themeService = new \App\Core\ThemeService();

    // Initialize ThemeLoader for installable themes
    \App\Core\ThemeLoader::init();

    // Get SEO settings for homepage
    $isHomePage = in_array(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), ['/', '']);
    $seoTitle = setting('seo_title');
    $seoDescription = setting('seo_description');
    $seoKeywords = setting('seo_keywords');
    $seoOgImage = setting('seo_og_image');
    $storeUrl = rtrim(setting('store_url') ?: 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '/');

    // Determine page title
    if (isset($title)) {
        $pageTitle = escape($title) . ' | ' . appName();
    } elseif ($isHomePage && $seoTitle) {
        $pageTitle = escape($seoTitle);
    } else {
        $pageTitle = appName();
    }

    // Determine meta description
    if (isset($metaDescription)) {
        $pageDescription = escape($metaDescription);
    } elseif ($isHomePage && $seoDescription) {
        $pageDescription = escape($seoDescription);
    } else {
        $pageDescription = appName() . ' - Premium products and unique designs. Shop our collection.';
    }

    // Determine OG image
    if (isset($ogImage)) {
        $pageOgImage = escape($ogImage);
    } elseif ($isHomePage && $seoOgImage) {
        $pageOgImage = (strpos($seoOgImage, 'http') === 0) ? $seoOgImage : $storeUrl . $seoOgImage;
    } else {
        $pageOgImage = $storeUrl . '/assets/images/og-default.jpg';
    }
    ?>
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="<?php echo $pageDescription; ?>">
    <?php if (isset($metaKeywords) || ($isHomePage && $seoKeywords)): ?>
    <meta name="keywords" content="<?php echo escape($metaKeywords ?? $seoKeywords); ?>">
    <?php endif; ?>
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?php echo htmlspecialchars($storeUrl . ($_SERVER['REQUEST_URI'] ?? '/'), ENT_QUOTES, 'UTF-8'); ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="<?php echo isset($product) ? 'product' : 'website'; ?>">
    <meta property="og:site_name" content="<?php echo appName(); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($storeUrl . ($_SERVER['REQUEST_URI'] ?? '/'), ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:title" content="<?php echo $pageTitle; ?>">
    <meta property="og:description" content="<?php echo $pageDescription; ?>">
    <meta property="og:image" content="<?php echo $pageOgImage; ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="<?php echo $pageTitle; ?>">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@<?php echo strtolower(str_replace(' ', '', appName())); ?>">
    <meta name="twitter:title" content="<?php echo $pageTitle; ?>">
    <meta name="twitter:description" content="<?php echo $pageDescription; ?>">
    <meta name="twitter:image" content="<?php echo $pageOgImage; ?>">
    <meta name="twitter:image:alt" content="<?php echo $pageTitle; ?>">

    <!-- Favicons -->
    <?php $customFavicon = setting('store_favicon'); ?>
    <?php if ($customFavicon): ?>
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo escape($customFavicon); ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo escape($customFavicon); ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo escape($customFavicon); ?>">
    <link rel="shortcut icon" href="<?php echo escape($customFavicon); ?>">
    <?php else: ?>
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="shortcut icon" href="/favicon.ico">
    <?php endif; ?>
    <meta name="theme-color" content="<?php echo escape($themeService->getThemeColor()); ?>">

    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="<?php echo appName(); ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/main.css?v=91">
    <?php
    // Load installed theme CSS (if any)
    $installedThemeCss = \App\Core\ThemeLoader::getThemeCss();
    if ($installedThemeCss): ?>
    <link rel="stylesheet" href="<?php echo escape($installedThemeCss); ?>">
    <?php endif; ?>
    <?php
    // Inject dynamic theme CSS variables
    $themeCss = $themeService->generateCssVariables();
    $bgAnimationClass = $themeService->getBackgroundAnimationClass();
    $bgAnimationEnabled = $themeService->isBackgroundAnimationEnabled();
    if ($themeCss): ?>
    <style id="theme-variables"><?php echo $themeCss; ?></style>
    <?php endif; ?>
    <?php $activeHoliday = \App\Core\HolidayEffects::getActiveHoliday(); ?>
    <?php if ($activeHoliday): ?>
    <link rel="stylesheet" href="/assets/css/holidays.css?v=10">
    <?php endif; ?>

    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-MK6Z5DFB');</script>
    <!-- End Google Tag Manager -->

    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-Z70TTG09B4"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-Z70TTG09B4');
    </script>

    <!-- Google Ads Conversion Tracking - Add to Cart -->
    <script>
    function gtag_report_conversion(url) {
      var callback = function () {
        if (typeof(url) != 'undefined') {
          window.location = url;
        }
      };
      gtag('event', 'conversion', {
          'send_to': 'AW-17845558218/QdVLCKiaquAbEMq3tr1C',
          'event_callback': callback
      });
      return false;
    }
    </script>

    <!-- Google AdSense -->
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-XXXXXXXXXXXXXXXX" crossorigin="anonymous"></script>

    <!-- reCAPTCHA v3 -->
    <?php echo \App\Core\ReCaptcha::getScript(); ?>

    <!-- JSON-LD Structured Data -->
    <?php
    $storeUrl = rtrim(setting('store_url') ?: 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '/');
    $storeLogo = setting('store_logo') ?: '/assets/images/apparix-logo.png';
    $logoUrl = (strpos($storeLogo, 'http') === 0) ? $storeLogo : $storeUrl . $storeLogo;
    $socialLinks = array_filter([
        setting('social_facebook'),
        setting('social_instagram'),
        setting('social_pinterest'),
        setting('social_twitter'),
        setting('social_tiktok')
    ]);
    ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "<?php echo escape(appName()); ?>",
        "url": "<?php echo escape($storeUrl); ?>",
        "logo": "<?php echo escape($logoUrl); ?>",
        "description": "<?php echo escape(setting('store_description') ?: 'Shop our collection of quality products.'); ?>",
        "sameAs": <?php echo json_encode(array_values($socialLinks)); ?>,
        "contactPoint": {
            "@type": "ContactPoint",
            "contactType": "customer service",
            "email": "<?php echo escape(setting('store_email') ?: 'support@' . ($_SERVER['HTTP_HOST'] ?? 'example.com')); ?>"
        }
    }
    </script>
    <?php if (isset($jsonLd)): ?>
    <script type="application/ld+json">
    <?php echo $jsonLd; ?>
    </script>
    <?php endif; ?>
</head>
<body style="background-color:#fdf2f8 !important"<?php echo $activeHoliday ? ' class="' . $activeHoliday['class'] . '"' : ''; ?>>
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-MK6Z5DFB"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->

    <!-- Animated background shapes -->
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
    <!-- Navigation -->
    <?php
    // Check if theme overrides the navbar partial
    $navbarPartial = \App\Core\ThemeLoader::getPartial('navbar');
    if ($navbarPartial && \App\Core\ThemeLoader::hasOverride('partial', 'navbar')):
        include $navbarPartial;
    else:
    ?>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">
                <a href="/" class="logo"><img src="<?php echo setting('store_logo') ?: '/assets/images/apparix-logo.png'; ?>" alt="<?php echo appName(); ?>"></a>
            </div>
            <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle menu" aria-expanded="false">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <ul class="navbar-menu" id="navbarMenu">
                <li><a href="/">Home</a></li>
                <li><a href="/products">Shop</a></li>
                <li class="nav-search">
                    <form action="/search" method="GET" class="nav-search-form">
                        <input type="text" name="q" placeholder="Search..." class="nav-search-input" autocomplete="off">
                        <button type="submit" class="nav-search-btn" aria-label="Search">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"/>
                                <path d="m21 21-4.35-4.35"/>
                            </svg>
                        </button>
                    </form>
                </li>
                <li><a href="/favorites" class="nav-favorites" title="My Favorites"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></a></li>
                <?php
                $cartModel = new \App\Models\Cart();
                $cartCount = $cartModel->getCount(session_id(), auth() ? auth()['id'] : null);
                ?>
                <li><a href="/cart" class="nav-cart">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    </svg>
                    <span>Cart</span>
                    <?php if ($cartCount > 0): ?>
                    <span class="cart-badge" id="cartBadge"><?php echo $cartCount; ?></span>
                    <?php else: ?>
                    <span class="cart-badge" id="cartBadge" style="display: none;">0</span>
                    <?php endif; ?>
                </a></li>
                <?php if (auth()): ?>
                    <li><a href="/account">Account</a></li>
                    <li><a href="/logout">Logout</a></li>
                <?php else: ?>
                    <li><a href="/login">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    <?php endif; // End navbar theme override check ?>

    <!-- Main Content -->
    <main class="main-content">
        <?php if ($flash = getFlash('success')): ?>
            <div class="alert alert-success"><?php echo escape($flash); ?></div>
        <?php endif; ?>
        <?php if ($flash = getFlash('error')): ?>
            <div class="alert alert-error"><?php echo escape($flash); ?></div>
        <?php endif; ?>

        <?php echo $content; ?>
    </main>

    <!-- Newsletter Section -->
    <?php
    // Check if theme overrides the newsletter partial
    $newsletterPartial = \App\Core\ThemeLoader::getPartial('newsletter');
    if ($newsletterPartial && \App\Core\ThemeLoader::hasOverride('partial', 'newsletter')):
        include $newsletterPartial;
    else:
    ?>
    <section class="newsletter-section">
        <div class="container">
            <div class="newsletter-content">
                <h3>Stay in the Loop!</h3>
                <p>Subscribe for exclusive offers, new product announcements, and creative inspiration.</p>
                <form id="newsletterForm" class="newsletter-form" onsubmit="subscribeNewsletter(event)">
                    <input type="hidden" name="_csrf_token" value="<?php echo csrfToken(); ?>">
                    <input type="hidden" name="recaptcha_token" id="newsletter_recaptcha_token">
                    <div class="newsletter-input-group">
                        <input type="email" name="email" id="newsletterEmail" placeholder="Your email address" required>
                        <button type="submit" class="btn btn-primary" id="newsletterBtn">Subscribe</button>
                    </div>
                    <div id="newsletterMessage" class="newsletter-message" style="display: none;"></div>
                </form>
            </div>
        </div>
    </section>
    <?php endif; // End newsletter theme override check ?>

    <!-- Footer -->
    <?php
    // Check if theme overrides the footer partial
    $footerPartial = \App\Core\ThemeLoader::getPartial('footer');
    if ($footerPartial && \App\Core\ThemeLoader::hasOverride('partial', 'footer')):
        include $footerPartial;
    else:
    ?>
    <footer class="footer">
        <div class="container">
            <!-- Social Media Links -->
            <?php
            $hasSocialLinks = !empty($settings['social_facebook']) || !empty($settings['social_instagram']) ||
                              !empty($settings['social_twitter']) || !empty($settings['social_tiktok']) ||
                              !empty($settings['social_youtube']) || !empty($settings['social_etsy']) ||
                              !empty($settings['social_pinterest']) || !empty($settings['social_linkedin']);
            if ($hasSocialLinks): ?>
            <div class="social-links">
                <?php if (!empty($settings['social_facebook'])): ?>
                <a href="<?php echo escape($settings['social_facebook']); ?>" target="_blank" rel="noopener" class="social-link" title="Facebook">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </a>
                <?php endif; ?>
                <?php if (!empty($settings['social_instagram'])): ?>
                <a href="<?php echo escape($settings['social_instagram']); ?>" target="_blank" rel="noopener" class="social-link" title="Instagram">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                </a>
                <?php endif; ?>
                <?php if (!empty($settings['social_twitter'])): ?>
                <a href="<?php echo escape($settings['social_twitter']); ?>" target="_blank" rel="noopener" class="social-link" title="X (Twitter)">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                </a>
                <?php endif; ?>
                <?php if (!empty($settings['social_tiktok'])): ?>
                <a href="<?php echo escape($settings['social_tiktok']); ?>" target="_blank" rel="noopener" class="social-link" title="TikTok">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                </a>
                <?php endif; ?>
                <?php if (!empty($settings['social_youtube'])): ?>
                <a href="<?php echo escape($settings['social_youtube']); ?>" target="_blank" rel="noopener" class="social-link" title="YouTube">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                </a>
                <?php endif; ?>
                <?php if (!empty($settings['social_etsy'])): ?>
                <a href="<?php echo escape($settings['social_etsy']); ?>" target="_blank" rel="noopener" class="social-link" title="Etsy Shop">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M8.559 3.89c0-.308.032-.6.06-.861.631-.054 1.647-.109 2.721-.109 2.005 0 4.178.243 4.995.392.198.034.314.104.326.316.063 1.104.249 2.396.376 3.426l-.81.125c-.323-1.282-.764-2.618-2.152-2.966-.698-.175-1.676-.21-2.303-.206-.524.003-1.148.014-1.213.014v6.639c1.134.009 2.291-.078 2.875-.134.635-.062.914-.263 1.043-.989l.217-1.217h.77c-.02.929-.064 2.035-.073 3.2.009 1.073.053 2.253.073 3.151h-.77l-.217-1.178c-.128-.766-.393-.94-1.043-1.037-.584-.088-1.741-.108-2.875-.117v4.94c0 1.553.204 1.966.726 2.146.521.18 1.327.195 2.234.195 1.855 0 3.073-.181 3.888-.769.84-.605 1.476-1.781 2.036-3.365l.836.249c-.236 1.121-.772 3.386-.961 4.281-.108.51-.205.682-.741.805-1.156.267-4.088.393-5.842.393-1.617 0-2.842-.025-3.813-.08-.506-.03-.813-.086-.813-.579 0-.449.05-1.393.05-3.886v-8.52c0-2.491-.05-3.434-.05-3.884z"/></svg>
                </a>
                <?php endif; ?>
                <?php if (!empty($settings['social_pinterest'])): ?>
                <a href="<?php echo escape($settings['social_pinterest']); ?>" target="_blank" rel="noopener" class="social-link" title="Pinterest">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0a12 12 0 0 0-4.373 23.178c-.07-.634-.134-1.606.028-2.298.146-.625.938-3.977.938-3.977s-.239-.479-.239-1.187c0-1.113.645-1.943 1.448-1.943.682 0 1.012.512 1.012 1.127 0 .687-.437 1.712-.663 2.663-.188.796.4 1.446 1.185 1.446 1.422 0 2.515-1.5 2.515-3.664 0-1.915-1.377-3.254-3.342-3.254-2.276 0-3.612 1.707-3.612 3.471 0 .688.265 1.425.595 1.826a.24.24 0 0 1 .056.23c-.061.252-.196.796-.222.907-.035.146-.116.177-.268.107-1-.465-1.624-1.926-1.624-3.1 0-2.523 1.834-4.84 5.286-4.84 2.775 0 4.932 1.977 4.932 4.62 0 2.757-1.739 4.976-4.151 4.976-.811 0-1.573-.421-1.834-.919l-.498 1.902c-.181.695-.669 1.566-.995 2.097A12 12 0 1 0 12 0z"/></svg>
                </a>
                <?php endif; ?>
                <?php if (!empty($settings['social_linkedin'])): ?>
                <a href="<?php echo escape($settings['social_linkedin']); ?>" target="_blank" rel="noopener" class="social-link" title="LinkedIn">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <p class="footer-copy">&copy; <?php echo date('Y'); ?> <?php echo appName(); ?>. All rights reserved.</p>
            <p class="footer-links"><a href="/privacy">Privacy</a> | <a href="/terms">Terms</a> | <a href="/contact">Contact</a></p>
            <?php
            // Show "Powered by Apparix" if enabled in settings OR if on free tier (can't disable on free)
            $showPoweredBy = !empty($settings['show_powered_by']) || \App\Core\License::isFree();
            if ($showPoweredBy): ?>
            <p class="footer-powered-by"><a href="https://apparix.app" target="_blank" rel="noopener">Powered by Apparix</a></p>
            <?php endif; ?>
        </div>
    </footer>
    <?php endif; // End footer theme override check ?>

    <script>
    const recaptchaSiteKey = '<?php echo \App\Core\ReCaptcha::getSiteKey(); ?>';

    async function subscribeNewsletter(e) {
        e.preventDefault();
        const form = document.getElementById('newsletterForm');
        const btn = document.getElementById('newsletterBtn');
        const msg = document.getElementById('newsletterMessage');
        const email = document.getElementById('newsletterEmail').value;

        if (!email) {
            msg.style.display = 'block';
            msg.className = 'newsletter-message error';
            msg.textContent = 'Please enter your email address.';
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Subscribing...';
        msg.style.display = 'none';

        try {
            // Get reCAPTCHA token first (wrapped in ready check)
            if (recaptchaSiteKey && typeof grecaptcha !== 'undefined') {
                try {
                    await new Promise((resolve, reject) => {
                        grecaptcha.ready(() => {
                            grecaptcha.execute(recaptchaSiteKey, {action: 'newsletter_subscribe'})
                                .then(token => {
                                    document.getElementById('newsletter_recaptcha_token').value = token;
                                    resolve();
                                })
                                .catch(reject);
                        });
                    });
                } catch (recaptchaError) {
                    console.warn('reCAPTCHA failed, continuing without it:', recaptchaError);
                }
            }

            const formData = new FormData(form);
            formData.append('source', 'footer');

            const response = await fetch('/newsletter/subscribe', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            msg.style.display = 'block';
            if (data.success) {
                msg.className = 'newsletter-message success';
                msg.textContent = data.message || 'Thank you for subscribing!';
                document.getElementById('newsletterEmail').value = '';
            } else {
                msg.className = 'newsletter-message error';
                msg.textContent = data.error || 'Failed to subscribe. Please try again.';
            }
        } catch (error) {
            console.error('Newsletter subscribe error:', error);
            msg.style.display = 'block';
            msg.className = 'newsletter-message error';
            msg.textContent = 'An error occurred. Please try again.';
        }

        btn.disabled = false;
        btn.textContent = 'Subscribe';
    }
    </script>

    <script src="/assets/js/main.js?v=13"></script>
    <?php if ($activeHoliday): ?>
    <script src="/assets/js/holidays.js?v=10"></script>
    <?php endif; ?>

    <!-- Mobile Menu Toggle -->
    <script>
    (function() {
        const toggle = document.getElementById('mobileMenuToggle');
        const menu = document.getElementById('navbarMenu');

        if (toggle && menu) {
            toggle.addEventListener('click', function() {
                toggle.classList.toggle('active');
                menu.classList.toggle('active');
                toggle.setAttribute('aria-expanded', menu.classList.contains('active'));
                document.body.style.overflow = menu.classList.contains('active') ? 'hidden' : '';
            });

            // Close menu when clicking a link
            menu.querySelectorAll('a').forEach(function(link) {
                link.addEventListener('click', function() {
                    toggle.classList.remove('active');
                    menu.classList.remove('active');
                    toggle.setAttribute('aria-expanded', 'false');
                    document.body.style.overflow = '';
                });
            });

            // Close menu on resize to desktop
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    toggle.classList.remove('active');
                    menu.classList.remove('active');
                    toggle.setAttribute('aria-expanded', 'false');
                    document.body.style.overflow = '';
                }
            });
        }
    })();
    </script>

    <!-- Exit-Intent Discount Popup -->
    <div id="exitIntentPopup" class="exit-intent-popup" style="display: none;">
        <div class="exit-popup-overlay"></div>
        <div class="exit-popup-content">
            <button type="button" class="exit-popup-close" onclick="closeExitPopup()" aria-label="Close">&times;</button>
            <div class="exit-popup-badge">WAIT!</div>
            <h3>Don't Leave Empty-Handed!</h3>
            <p>Get <strong>10% OFF</strong> your first order when you enter your email below.</p>
            <p class="exit-popup-exclusion">*Excludes items already on sale</p>
            <div class="exit-popup-timer" id="exitPopupTimer">
                <span class="timer-label">Offer expires in:</span>
                <span class="timer-value" id="exitTimerValue">48:00:00</span>
            </div>
            <form id="exitIntentForm" class="exit-popup-form" onsubmit="submitExitIntent(event)">
                <input type="hidden" name="_csrf_token" value="<?php echo csrfToken(); ?>">
                <input type="email" name="email" id="exitEmail" placeholder="Enter your email" required>
                <button type="submit" class="btn btn-primary" id="exitSubmitBtn">Get My 10% Off</button>
            </form>
            <div id="exitCouponResult" class="exit-coupon-result" style="display: none;">
                <div class="coupon-success-icon">🎉</div>
                <p>Your exclusive discount code:</p>
                <div class="coupon-code" id="exitCouponCode"></div>
                <p class="coupon-expires">Expires in <span id="couponExpiresIn"></span></p>
                <p class="coupon-note">*Cannot be combined with other offers or applied to sale items</p>
                <a href="/products" class="btn btn-primary">Start Shopping</a>
            </div>
            <div id="exitMessage" class="exit-popup-message" style="display: none;"></div>
            <p class="exit-popup-dismiss"><a href="#" onclick="closeExitPopup(); return false;">No thanks, I'll pay full price</a></p>
        </div>
    </div>

    <style>
    .exit-intent-popup {
        position: fixed;
        inset: 0;
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }
    .exit-popup-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.6);
        backdrop-filter: blur(4px);
    }
    .exit-popup-content {
        position: relative;
        background: linear-gradient(135deg, #fff5f9 0%, #ffffff 100%);
        border-radius: 20px;
        padding: 2.5rem;
        max-width: 420px;
        width: 100%;
        text-align: center;
        box-shadow: 0 25px 50px rgba(0,0,0,0.25);
        animation: exitPopupIn 0.4s ease;
    }
    @keyframes exitPopupIn {
        from { opacity: 0; transform: scale(0.9) translateY(-20px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }
    .exit-popup-close {
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: none;
        border: none;
        font-size: 1.5rem;
        color: #9ca3af;
        cursor: pointer;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: all 0.2s;
    }
    .exit-popup-close:hover {
        background: #f3f4f6;
        color: #1f2937;
    }
    .exit-popup-badge {
        display: inline-block;
        background: #FF68C5;
        color: white;
        padding: 0.375rem 1rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        margin-bottom: 1rem;
    }
    .exit-popup-content h3 {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 1.75rem;
        color: #1f2937;
        margin: 0 0 0.75rem 0;
    }
    .exit-popup-content > p {
        color: #4b5563;
        margin: 0 0 1.25rem 0;
        line-height: 1.5;
    }
    .exit-popup-content strong {
        color: #FF68C5;
        font-size: 1.25em;
    }
    .exit-popup-timer {
        background: #1f2937;
        color: white;
        padding: 0.75rem 1.25rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    .timer-label {
        font-size: 0.875rem;
        opacity: 0.8;
    }
    .timer-value {
        font-family: monospace;
        font-size: 1.25rem;
        font-weight: 700;
        color: #FF68C5;
    }
    .exit-popup-form {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    .exit-popup-form input[type="email"] {
        padding: 0.875rem 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        font-size: 1rem;
        transition: border-color 0.2s;
    }
    .exit-popup-form input[type="email"]:focus {
        outline: none;
        border-color: #FF68C5;
    }
    .exit-popup-form .btn {
        padding: 0.875rem 1.5rem;
        font-size: 1rem;
        font-weight: 600;
    }
    .exit-coupon-result {
        padding: 1rem 0;
    }
    .coupon-success-icon {
        font-size: 3rem;
        margin-bottom: 0.75rem;
    }
    .coupon-code {
        background: #1f2937;
        color: #FF68C5;
        font-family: monospace;
        font-size: 1.5rem;
        font-weight: 700;
        padding: 1rem 1.5rem;
        border-radius: 10px;
        margin: 1rem 0;
        letter-spacing: 0.1em;
        user-select: all;
        cursor: pointer;
    }
    .coupon-expires {
        color: #6b7280;
        font-size: 0.875rem;
        margin-bottom: 0.5rem;
    }
    .coupon-note {
        color: #9ca3af;
        font-size: 0.75rem;
        font-style: italic;
        margin-bottom: 1rem;
    }
    .exit-popup-exclusion {
        color: #9ca3af;
        font-size: 0.7rem;
        margin: -0.25rem 0 0.75rem;
    }
    .exit-popup-message {
        padding: 0.75rem;
        border-radius: 8px;
        margin-top: 0.75rem;
        font-size: 0.875rem;
    }
    .exit-popup-message.error {
        background: #fef2f2;
        color: #dc2626;
    }
    .exit-popup-dismiss {
        margin-top: 1rem;
        font-size: 0.8125rem;
    }
    .exit-popup-dismiss a {
        color: #9ca3af;
        text-decoration: none;
    }
    .exit-popup-dismiss a:hover {
        color: #6b7280;
        text-decoration: underline;
    }
    @media (max-width: 480px) {
        .exit-popup-content {
            padding: 2rem 1.5rem;
        }
        .exit-popup-content h3 {
            font-size: 1.5rem;
        }
    }
    /* Hidden Lucky Clover Game */
    .lucky-clover {
        display: inline-block;
        font-size: 7px;
        color: #c9d1d9;
        cursor: default;
        opacity: 0.35;
        margin-left: 3px;
        vertical-align: middle;
        transition: all 0.3s;
    }
    .lucky-clover:hover {
        opacity: 0.7;
        color: #4ade80;
        transform: scale(1.3);
    }
    .clover-modal {
        position: fixed;
        inset: 0;
        z-index: 10001;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }
    .clover-modal-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.7);
        backdrop-filter: blur(4px);
    }
    .clover-modal-content {
        position: relative;
        background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%);
        border-radius: 20px;
        padding: 2.5rem;
        max-width: 400px;
        width: 100%;
        text-align: center;
        box-shadow: 0 25px 50px rgba(0,0,0,0.3);
        animation: cloverIn 0.5s ease;
    }
    @keyframes cloverIn {
        from { opacity: 0; transform: scale(0.8) rotate(-10deg); }
        to { opacity: 1; transform: scale(1) rotate(0); }
    }
    .clover-modal-close {
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: none;
        border: none;
        font-size: 1.5rem;
        color: #9ca3af;
        cursor: pointer;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: all 0.2s;
    }
    .clover-modal-close:hover {
        background: #f3f4f6;
        color: #1f2937;
    }
    .clover-icon {
        font-size: 4rem;
        display: block;
        margin-bottom: 1rem;
        animation: cloverSpin 1s ease;
    }
    @keyframes cloverSpin {
        0% { transform: rotate(0deg) scale(0); }
        50% { transform: rotate(180deg) scale(1.2); }
        100% { transform: rotate(360deg) scale(1); }
    }
    .clover-modal h3 {
        color: #166534;
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
    }
    .clover-modal p {
        color: #4b5563;
        margin-bottom: 1rem;
    }
    .clover-code {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: white;
        font-size: 1.5rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        margin: 1rem 0;
        font-family: monospace;
        cursor: pointer;
        transition: transform 0.2s;
    }
    .clover-code:hover {
        transform: scale(1.02);
    }
    .clover-note {
        font-size: 0.75rem;
        color: #9ca3af;
        font-style: italic;
    }
    </style>

    <!-- Lucky Clover Modal -->
    <div id="cloverModal" class="clover-modal" style="display: none;">
        <div class="clover-modal-overlay" onclick="closeCloverModal()"></div>
        <div class="clover-modal-content">
            <button type="button" class="clover-modal-close" onclick="closeCloverModal()" aria-label="Close">&times;</button>
            <span class="clover-icon">&#127808;</span>
            <h3>Lucky Find!</h3>
            <p>You discovered the hidden clover! Here's <strong>15% OFF</strong> your order:</p>
            <div class="clover-code" id="cloverCode" onclick="copyCloverCode()" title="Click to copy">Loading...</div>
            <p id="cloverCopyMsg" style="color: #22c55e; font-size: 0.875rem; display: none;">Copied to clipboard!</p>
            <p class="clover-note">*One-time use only. Cannot be combined with other offers or applied to sale items.</p>
            <a href="/products" class="btn btn-primary" style="background: #22c55e; margin-top: 1rem;">Start Shopping</a>
        </div>
    </div>

    <script>
    // Lucky Clover Game
    var cloverCodeValue = '';
    var cloverEl = document.getElementById('luckyClover');
    if (cloverEl) {
        cloverEl.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('cloverModal').style.display = 'flex';
            document.getElementById('cloverCode').textContent = 'Loading...';

            fetch('/api/clover/generate', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    cloverCodeValue = data.code;
                    document.getElementById('cloverCode').textContent = data.code;
                } else {
                    document.getElementById('cloverCode').textContent = 'Error - try again';
                }
            })
            .catch(() => {
                document.getElementById('cloverCode').textContent = 'Error - try again';
            });
        });
    }
    function closeCloverModal() {
        document.getElementById('cloverModal').style.display = 'none';
    }
    function copyCloverCode() {
        if (cloverCodeValue) {
            navigator.clipboard.writeText(cloverCodeValue).then(function() {
                document.getElementById('cloverCopyMsg').style.display = 'block';
                setTimeout(function() {
                    document.getElementById('cloverCopyMsg').style.display = 'none';
                }, 2000);
            });
        }
    }
    </script>

    <!-- Newsletter Popup for First-Time Visitors (hidden on mobile) -->
    <div id="newsletterPopup" class="newsletter-popup" style="display: none;">
        <div class="newsletter-popup-overlay"></div>
        <div class="newsletter-popup-content">
            <button type="button" class="newsletter-popup-close" onclick="closeNewsletterPopup()" aria-label="Close">&times;</button>
            <div class="newsletter-popup-icon">💌</div>
            <h3>Let's Keep in Touch!</h3>
            <p>Join our community for exclusive offers, new arrivals, and creative inspiration delivered to your inbox.</p>
            <form id="popupNewsletterForm" class="newsletter-popup-form" onsubmit="submitPopupNewsletter(event)">
                <input type="hidden" name="_csrf_token" value="<?php echo csrfToken(); ?>">
                <input type="hidden" name="recaptcha_token" id="popup_recaptcha_token">
                <input type="email" name="email" id="popupEmail" placeholder="Enter your email" required>
                <button type="submit" class="btn btn-primary" id="popupSubmitBtn">Subscribe</button>
            </form>
            <div id="popupMessage" class="newsletter-popup-message" style="display: none;"></div>
            <p class="newsletter-popup-dismiss"><a href="#" onclick="closeNewsletterPopup(); return false;">No thanks, maybe later</a></p>
        </div>
    </div>

    <script>
    // Newsletter Popup for First-Time Visitors
    (function() {
        // Skip popup on mobile to prevent rendering issues
        if (window.innerWidth <= 768) return;

        const POPUP_KEY = 'lps_newsletter_seen';
        const POPUP_DELAY = 5000; // Show after 5 seconds

        // Check if user has seen the popup before
        if (localStorage.getItem(POPUP_KEY)) return;

        // Show popup after delay
        setTimeout(function() {
            const popup = document.getElementById('newsletterPopup');
            if (popup) {
                popup.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        }, POPUP_DELAY);
    })();

    function closeNewsletterPopup() {
        const popup = document.getElementById('newsletterPopup');
        if (popup) {
            popup.style.display = 'none';
            document.body.style.overflow = '';
            localStorage.setItem('lps_newsletter_seen', 'true');
        }
    }

    // Close on overlay click
    document.querySelector('.newsletter-popup-overlay')?.addEventListener('click', closeNewsletterPopup);

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeNewsletterPopup();
    });

    async function submitPopupNewsletter(e) {
        e.preventDefault();
        const form = document.getElementById('popupNewsletterForm');
        const btn = document.getElementById('popupSubmitBtn');
        const msg = document.getElementById('popupMessage');
        const email = document.getElementById('popupEmail').value;

        if (!email) return;

        btn.disabled = true;
        btn.textContent = 'Subscribing...';
        msg.style.display = 'none';

        try {
            // Get reCAPTCHA token if available
            if (typeof recaptchaSiteKey !== 'undefined' && recaptchaSiteKey && typeof grecaptcha !== 'undefined') {
                try {
                    await new Promise((resolve, reject) => {
                        grecaptcha.ready(() => {
                            grecaptcha.execute(recaptchaSiteKey, {action: 'newsletter_subscribe'})
                                .then(token => {
                                    document.getElementById('popup_recaptcha_token').value = token;
                                    resolve();
                                })
                                .catch(reject);
                        });
                    });
                } catch (err) {
                    console.warn('reCAPTCHA failed:', err);
                }
            }

            const formData = new FormData(form);
            formData.append('source', 'popup');

            const response = await fetch('/newsletter/subscribe', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const data = await response.json();

            msg.style.display = 'block';
            if (data.success) {
                msg.className = 'newsletter-popup-message success';
                msg.textContent = data.message || 'Thank you for subscribing!';
                localStorage.setItem('lps_newsletter_seen', 'true');
                setTimeout(closeNewsletterPopup, 2500);
            } else {
                msg.className = 'newsletter-popup-message error';
                msg.textContent = data.error || 'Something went wrong. Please try again.';
            }
        } catch (error) {
            msg.style.display = 'block';
            msg.className = 'newsletter-popup-message error';
            msg.textContent = 'An error occurred. Please try again.';
        }

        btn.disabled = false;
        btn.textContent = 'Subscribe';
    }
    </script>

    <!-- Exit-Intent Popup Script -->
    <script>
    (function() {
        const EXIT_KEY = 'lps_exit_popup_shown';
        const EXIT_COUPON_KEY = 'lps_exit_coupon';
        let exitPopupShown = false;
        let canShowPopup = false;
        let lastY = 0;

        // Skip on mobile (no mouse exit intent) and admin pages
        if (window.innerWidth <= 768 || window.location.pathname.startsWith('/admin')) return;

        // Check if already shown this session or has coupon
        if (sessionStorage.getItem(EXIT_KEY) || localStorage.getItem(EXIT_COUPON_KEY)) return;

        // Wait 5 seconds before enabling exit popup (let user browse first)
        setTimeout(function() { canShowPopup = true; }, 5000);

        // Track mouse Y position to detect upward movement
        document.addEventListener('mousemove', function(e) {
            lastY = e.clientY;
        });

        // Detect exit intent - only trigger on fast upward movement to top
        document.addEventListener('mouseout', function(e) {
            if (exitPopupShown || !canShowPopup) return;

            // Must leave through top of page with upward momentum
            if (e.clientY < 5 && e.relatedTarget == null && lastY < 100) {
                showExitPopup();
            }
        });

        function showExitPopup() {
            exitPopupShown = true;
            sessionStorage.setItem(EXIT_KEY, 'true');

            const popup = document.getElementById('exitIntentPopup');
            if (popup) {
                popup.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        }

        window.closeExitPopup = function() {
            const popup = document.getElementById('exitIntentPopup');
            if (popup) {
                popup.style.display = 'none';
                document.body.style.overflow = '';
            }
        };

        // Close on overlay click
        document.querySelector('.exit-popup-overlay')?.addEventListener('click', closeExitPopup);

        // Close on escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeExitPopup();
        });

        window.submitExitIntent = async function(e) {
            e.preventDefault();
            const form = document.getElementById('exitIntentForm');
            const btn = document.getElementById('exitSubmitBtn');
            const msg = document.getElementById('exitMessage');
            const email = document.getElementById('exitEmail').value;

            if (!email) return;

            btn.disabled = true;
            btn.textContent = 'Creating your code...';
            msg.style.display = 'none';

            try {
                const formData = new FormData(form);
                const response = await fetch('/api/popup-coupon', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const data = await response.json();

                if (data.success) {
                    // Hide form, show coupon
                    form.style.display = 'none';
                    document.getElementById('exitPopupTimer').style.display = 'none';
                    document.querySelector('.exit-popup-dismiss').style.display = 'none';

                    const result = document.getElementById('exitCouponResult');
                    document.getElementById('exitCouponCode').textContent = data.code;
                    document.getElementById('couponExpiresIn').textContent = data.expires_in;
                    result.style.display = 'block';

                    // Save coupon to localStorage
                    localStorage.setItem(EXIT_COUPON_KEY, JSON.stringify({
                        code: data.code,
                        expires_in: data.expires_in
                    }));

                    // Copy code on click
                    document.getElementById('exitCouponCode').addEventListener('click', function() {
                        navigator.clipboard.writeText(this.textContent);
                        this.style.background = '#10b981';
                        this.textContent = 'Copied!';
                        setTimeout(() => {
                            this.style.background = '#1f2937';
                            this.textContent = data.code;
                        }, 1500);
                    });
                } else {
                    msg.style.display = 'block';
                    msg.className = 'exit-popup-message error';
                    msg.textContent = data.error || 'Something went wrong. Please try again.';
                }
            } catch (error) {
                msg.style.display = 'block';
                msg.className = 'exit-popup-message error';
                msg.textContent = 'An error occurred. Please try again.';
            }

            btn.disabled = false;
            btn.textContent = 'Get My 10% Off';
        };
    })();
    </script>

    <!-- Mobile Newsletter Slide-in (mobile only) -->
    <div id="mobileNewsletter" class="mobile-newsletter-banner">
        <button type="button" class="mobile-newsletter-close" onclick="closeMobileNewsletter()" aria-label="Close">&times;</button>
        <div class="mobile-newsletter-content">
            <p class="mobile-newsletter-text"><strong>Stay in the loop!</strong> Get occasional deals & new arrivals.</p>
            <form id="mobileNewsletterForm" class="mobile-newsletter-form" onsubmit="submitMobileNewsletter(event)">
                <input type="hidden" name="_csrf_token" value="<?php echo csrfToken(); ?>">
                <input type="email" name="email" id="mobileNewsletterEmail" placeholder="Your email" required>
                <button type="submit" class="btn btn-primary" id="mobileNewsletterBtn">Join</button>
            </form>
            <div id="mobileNewsletterSuccess" class="mobile-newsletter-success" style="display: none;">
                <span>You're in! Watch for deals in your inbox.</span>
            </div>
        </div>
    </div>

    <style>
    .mobile-newsletter-banner {
        display: none;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(135deg, #fff5f9 0%, #ffffff 100%);
        padding: 1rem;
        box-shadow: 0 -4px 20px rgba(0,0,0,0.15);
        z-index: 9999;
        transform: translateY(100%);
        transition: transform 0.4s ease;
        border-top: 2px solid #FF68C5;
    }
    .mobile-newsletter-banner.visible {
        transform: translateY(0);
    }
    .mobile-newsletter-close {
        position: absolute;
        top: 0.5rem;
        right: 0.75rem;
        background: none;
        border: none;
        font-size: 1.5rem;
        color: #9ca3af;
        cursor: pointer;
        padding: 0.25rem;
        line-height: 1;
    }
    .mobile-newsletter-content {
        padding-right: 2rem;
    }
    .mobile-newsletter-text {
        margin: 0 0 0.75rem 0;
        font-size: 0.9rem;
        color: #374151;
    }
    .mobile-newsletter-text strong {
        color: #1f2937;
    }
    .mobile-newsletter-form {
        display: flex;
        gap: 0.5rem;
    }
    .mobile-newsletter-form input[type="email"] {
        flex: 1;
        padding: 0.625rem 0.875rem;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 0.9rem;
    }
    .mobile-newsletter-form input[type="email"]:focus {
        outline: none;
        border-color: #FF68C5;
    }
    .mobile-newsletter-form .btn {
        padding: 0.625rem 1rem;
        font-size: 0.9rem;
        white-space: nowrap;
    }
    .mobile-newsletter-success {
        color: #059669;
        font-size: 0.9rem;
        padding: 0.5rem 0;
    }
    .mobile-newsletter-success span::before {
        content: '✓ ';
    }
    /* Only show on mobile */
    @media (min-width: 769px) {
        .mobile-newsletter-banner {
            display: none !important;
        }
    }
    </style>

    <script>
    // Mobile Newsletter Slide-in
    (function() {
        // Only run on mobile
        if (window.innerWidth > 768) return;

        const MOBILE_NL_KEY = 'lps_mobile_newsletter_dismissed';
        const SHOW_DELAY = 15000; // Show after 15 seconds
        const SCROLL_THRESHOLD = 0.3; // Or after scrolling 30% of page

        // Skip if already dismissed or subscribed
        if (localStorage.getItem(MOBILE_NL_KEY)) return;

        let shown = false;

        function showBanner() {
            if (shown) return;
            shown = true;

            const banner = document.getElementById('mobileNewsletter');
            if (banner) {
                banner.style.display = 'block';
                // Trigger reflow then add visible class for animation
                banner.offsetHeight;
                banner.classList.add('visible');
            }
        }

        // Show after delay
        setTimeout(showBanner, SHOW_DELAY);

        // Or show after scrolling 30%
        window.addEventListener('scroll', function() {
            if (shown) return;
            const scrollPercent = window.scrollY / (document.body.scrollHeight - window.innerHeight);
            if (scrollPercent > SCROLL_THRESHOLD) {
                showBanner();
            }
        }, { passive: true });
    })();

    function closeMobileNewsletter() {
        const banner = document.getElementById('mobileNewsletter');
        if (banner) {
            banner.classList.remove('visible');
            setTimeout(() => { banner.style.display = 'none'; }, 400);
        }
        localStorage.setItem('lps_mobile_newsletter_dismissed', 'true');
    }

    async function submitMobileNewsletter(e) {
        e.preventDefault();

        const form = e.target;
        const email = document.getElementById('mobileNewsletterEmail').value;
        const btn = document.getElementById('mobileNewsletterBtn');

        btn.disabled = true;
        btn.textContent = '...';

        try {
            const formData = new FormData();
            formData.append('email', email);
            formData.append('_csrf_token', form.querySelector('[name="_csrf_token"]').value);

            const response = await fetch('/newsletter/subscribe', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                form.style.display = 'none';
                document.getElementById('mobileNewsletterSuccess').style.display = 'block';
                localStorage.setItem('lps_mobile_newsletter_dismissed', 'true');

                // Auto-hide after 3 seconds
                setTimeout(closeMobileNewsletter, 3000);
            } else {
                btn.disabled = false;
                btn.textContent = 'Join';
                alert(data.error || 'Please try again.');
            }
        } catch (error) {
            btn.disabled = false;
            btn.textContent = 'Join';
            alert('An error occurred. Please try again.');
        }
    }
    </script>

    <!-- Service Worker Registration -->
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js')
                .then(reg => console.log('SW registered'))
                .catch(err => console.log('SW registration failed:', err));
        });
    }
    </script>

    <!-- Social Proof Popup - Recent Purchases -->
    <div id="socialProofPopup" class="social-proof-popup">
        <button type="button" id="socialProofClose" class="social-proof-close" aria-label="Close">&times;</button>
        <div class="social-proof-icon">🛍️</div>
        <div class="social-proof-content">
            <p class="social-proof-text">
                <strong id="socialProofName">Someone</strong> from <span id="socialProofLocation">somewhere</span> just purchased
            </p>
            <p class="social-proof-product" id="socialProofProduct">a product</p>
            <p class="social-proof-time" id="socialProofTime">just now</p>
        </div>
    </div>

    <style>
    .social-proof-popup {
        position: fixed;
        bottom: 20px;
        left: 20px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        padding: 1rem 1.25rem;
        display: none;
        align-items: flex-start;
        gap: 0.75rem;
        max-width: 320px;
        z-index: 9998;
        border-left: 4px solid #FF68C5;
    }
    .social-proof-popup.visible {
        display: flex;
        animation: socialProofSlideIn 0.4s ease forwards;
    }
    @keyframes socialProofSlideIn {
        from { opacity: 0; transform: translateX(-30px); }
        to { opacity: 1; transform: translateX(0); }
    }
    .social-proof-close {
        position: absolute;
        top: 5px;
        right: 5px;
        background: #f3f4f6;
        border: none;
        border-radius: 50%;
        font-size: 1.25rem;
        color: #6b7280;
        cursor: pointer;
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        touch-action: manipulation;
        -webkit-tap-highlight-color: transparent;
        z-index: 10;
    }
    .social-proof-close:hover,
    .social-proof-close:active {
        background: #e5e7eb;
        color: #1f2937;
    }
    .social-proof-icon {
        font-size: 1.75rem;
        flex-shrink: 0;
    }
    .social-proof-content {
        flex: 1;
        padding-right: 1.5rem;
    }
    .social-proof-text {
        margin: 0;
        font-size: 0.875rem;
        color: #4b5563;
        line-height: 1.4;
    }
    .social-proof-text strong {
        color: #1f2937;
    }
    .social-proof-product {
        margin: 0.25rem 0 0 0;
        font-size: 0.875rem;
        font-weight: 600;
        color: #1f2937;
    }
    .social-proof-time {
        margin: 0.25rem 0 0 0;
        font-size: 0.75rem;
        color: #9ca3af;
    }
    @media (max-width: 480px) {
        .social-proof-popup {
            left: 10px;
            right: 10px;
            bottom: 80px;
            max-width: none;
        }
        .social-proof-popup.visible {
            animation: socialProofSlideUp 0.4s ease forwards;
        }
        @keyframes socialProofSlideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .social-proof-close {
            width: 36px;
            height: 36px;
            font-size: 1.5rem;
            top: 8px;
            right: 8px;
        }
    }
    </style>

    <script>
    // Social Proof Popup - Shows recent purchases
    (function() {
        const POPUP_INTERVAL = 45000; // Show every 45 seconds
        const POPUP_DURATION = 6000;  // Stay visible for 6 seconds
        const STORAGE_KEY = 'lps_social_proof_dismissed';

        // Skip on admin pages and checkout
        if (window.location.pathname.startsWith('/admin')) return;
        if (window.location.pathname.startsWith('/checkout')) return;

        // Check for test mode via URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const testMode = urlParams.get('test_social') === '1';

        // Check if permanently dismissed this session (skip in test mode)
        if (!testMode && sessionStorage.getItem(STORAGE_KEY)) return;

        let popupTimeout = null;
        let fetchInterval = null;

        async function fetchAndShowPurchase() {
            try {
                const apiUrl = testMode ? '/api/recent-purchases?test=socialproof2024' : '/api/recent-purchases';
                const response = await fetch(apiUrl);
                const data = await response.json();

                if (data.success && data.purchase) {
                    showSocialProof(data.purchase);
                }
            } catch (error) {
                // Silently fail - don't disrupt user experience
            }
        }

        function showSocialProof(purchase) {
            const popup = document.getElementById('socialProofPopup');
            if (!popup) return;

            // Update content
            const location = purchase.city && purchase.state
                ? `${purchase.city}, ${purchase.state}`
                : (purchase.city || purchase.state || 'USA');

            document.getElementById('socialProofName').textContent =
                purchase.first_name + ' ' + purchase.last_initial + '.';
            document.getElementById('socialProofLocation').textContent = location;
            document.getElementById('socialProofProduct').textContent = purchase.product_name;
            document.getElementById('socialProofTime').textContent = purchase.time_ago;

            // Show popup
            popup.classList.add('visible');

            // Auto-hide after duration
            popupTimeout = setTimeout(function() {
                popup.classList.remove('visible');
            }, POPUP_DURATION);
        }

        function closeSocialProof() {
            const popup = document.getElementById('socialProofPopup');
            if (popup) {
                popup.classList.remove('visible');
            }
            if (popupTimeout) {
                clearTimeout(popupTimeout);
            }
            // Dismiss for this session
            sessionStorage.setItem(STORAGE_KEY, 'true');
            if (fetchInterval) {
                clearInterval(fetchInterval);
            }
        }

        // Attach close button event listener
        const closeBtn = document.getElementById('socialProofClose');
        if (closeBtn) {
            closeBtn.addEventListener('click', closeSocialProof);
            closeBtn.addEventListener('touchend', function(e) {
                e.preventDefault();
                closeSocialProof();
            });
        }

        // Start showing popups after initial delay (shorter in test mode)
        const initialDelay = testMode ? 2000 : 10000;
        setTimeout(function() {
            fetchAndShowPurchase();
            fetchInterval = setInterval(fetchAndShowPurchase, POPUP_INTERVAL);
        }, initialDelay);
    })();
    </script>

    <!-- Back to Top Button -->
    <button class="back-to-top" id="backToTop" aria-label="Back to top">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
        </svg>
    </button>

    <script>
    // Back to Top Button
    (function() {
        var backToTop = document.getElementById('backToTop');
        if (backToTop) {
            window.addEventListener('scroll', function() {
                if (window.scrollY > 400) {
                    backToTop.classList.add('visible');
                } else {
                    backToTop.classList.remove('visible');
                }
            });

            backToTop.addEventListener('click', function() {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }
    })();
    </script>

    <?php
    // Load installed theme JS (if any)
    $installedThemeJs = \App\Core\ThemeLoader::getThemeJs();
    if ($installedThemeJs): ?>
    <script src="<?php echo escape($installedThemeJs); ?>"></script>
    <?php endif; ?>

</body>
</html>
