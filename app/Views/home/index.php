<?php
// Hero Settings
$heroHeading = setting('hero_heading', 'Welcome to {store_name}');
$heroHeading = str_replace('{store_name}', appName(), $heroHeading);
$heroTaglines = setting('hero_taglines', ['Discover quality products curated just for you']);
// If stored as string (from old data), decode it
if (is_string($heroTaglines)) {
    $heroTaglines = json_decode($heroTaglines, true) ?: ['Discover quality products curated just for you'];
}
if (empty($heroTaglines)) {
    $heroTaglines = ['Discover quality products curated just for you'];
}
$heroCtaText = setting('hero_cta_text', 'Shop Now');
$heroCtaUrl = setting('hero_cta_url', '/products');
$heroBgStyle = setting('hero_background_style', 'gradient-dark');
$heroShowGlow = setting('hero_show_glow', '1');
$heroShowShimmer = setting('hero_show_shimmer', '1');
$heroRotateTaglines = setting('hero_rotate_taglines', '1');
$heroTaglineInterval = (int)setting('hero_tagline_interval', '8');
$heroOverlayOpacity = setting('hero_overlay_opacity', '0.12');
$heroBgImage = setting('hero_background_image', '');

// Build hero class
$heroClasses = ['hero', 'hero-' . $heroBgStyle];
if (!$heroShowGlow) $heroClasses[] = 'hero-no-glow';
if (!$heroShowShimmer) $heroClasses[] = 'hero-no-shimmer';
?>
<!-- Hero Section -->
<section class="<?php echo escape(implode(' ', $heroClasses)); ?>"<?php if ($heroBgStyle === 'image' && $heroBgImage): ?> style="background-image: url('<?php echo escape($heroBgImage); ?>');"<?php endif; ?>>
    <div class="hero-content">
        <h1><?php echo escape($heroHeading); ?></h1>
        <p class="hero-tagline" id="heroTagline"><?php echo escape($heroTaglines[0] ?? ''); ?></p>
        <a href="<?php echo escape($heroCtaUrl); ?>" class="btn btn-primary"><?php echo escape($heroCtaText); ?></a>
    </div>
</section>

<?php if ($heroRotateTaglines && count($heroTaglines) > 1): ?>
<script>
(function() {
    var taglines = <?php echo json_encode($heroTaglines); ?>;
    var interval = <?php echo $heroTaglineInterval * 1000; ?>;

    var currentIndex = 0;
    var taglineEl = document.getElementById('heroTagline');
    if (!taglineEl || taglines.length < 2) return;

    function rotateTagline() {
        taglineEl.style.opacity = '0';
        taglineEl.style.transform = 'translateY(6px)';

        setTimeout(function() {
            currentIndex = (currentIndex + 1) % taglines.length;
            taglineEl.textContent = taglines[currentIndex];
            taglineEl.style.opacity = '1';
            taglineEl.style.transform = 'translateY(0)';
        }, 1000);
    }

    setTimeout(function() {
        setInterval(rotateTagline, interval);
    }, 5000);
})();
</script>
<?php endif; ?>

<!-- Shop by Category -->
<?php if (!empty($categories)): ?>
<section class="shop-categories">
    <div class="container">
        <h2>Shop by Category</h2>
        <div class="category-links">
            <a href="/products" class="category-link">All Products</a>
            <?php foreach ($categories as $category): ?>
                <a href="/category/<?php echo escape($category['slug']); ?>" class="category-link">
                    <?php echo escape($category['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Featured Products -->
<section class="featured-products">
    <div class="container">
        <h2>Featured Items</h2>
        <div class="products-grid">
            <?php if (!empty($featured_products)): ?>
                <?php foreach ($featured_products as $product): ?>
                    <div class="product-card" <?php if (!empty($product['video_path'])): ?>data-has-video="true"<?php endif; ?>>
                        <div class="product-image">
                            <a href="/products/<?php echo escape($product['slug']); ?>">
                                <img src="<?php echo escape($product['primary_image'] ?? '/assets/images/placeholder.png'); ?>"
                                     alt="<?php echo escape($product['name']); ?> - Featured Product | <?php echo appName(); ?>"
                                     title="<?php echo escape($product['name']); ?>"
                                     loading="lazy"
                                     width="300"
                                     height="300">
                                <?php if (!empty($product['video_path'])): ?>
                                    <video class="product-video" src="<?php echo escape($product['video_path']); ?>" muted loop playsinline preload="metadata"></video>
                                    <span class="video-indicator">&#9658;</span>
                                <?php endif; ?>
                            </a>
                            <?php if ($product['sale_price']): ?>
                                <span class="sale-badge">Sale</span>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3><a href="/products/<?php echo escape($product['slug']); ?>"><?php echo escape($product['name']); ?></a></h3>
                            <p class="product-price">
                                <?php if ($product['sale_price']): ?>
                                    <span class="original-price"><del><?php echo formatPrice($product['price']); ?></del></span>
                                    <span class="sale-price"><?php echo formatPrice($product['sale_price']); ?></span>
                                <?php else: ?>
                                    <?php echo formatPrice($product['price']); ?>
                                <?php endif; ?>
                            </p>
                            <a href="/products/<?php echo escape($product['slug']); ?>" class="btn btn-add-cart">Shop Now</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-products">No featured products available. Check back soon!</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <h2>Browse Our Full Collection</h2>
        <a href="/products" class="btn btn-outline">View All Products</a>
    </div>
</section>
