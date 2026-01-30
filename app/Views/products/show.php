<!-- Product Detail Page -->
<section class="product-detail">
    <div class="container">
        <a href="/products" class="breadcrumb">&larr; Back to Products</a>

        <div class="product-layout">
            <!-- Product Images - Thumbnails on left -->
            <div class="product-images">
                <!-- Thumbnail Strip (Left Side) -->
                <?php
                // Flatten images for display: main images + their sub-images
                $flatImages = [];
                foreach ($product['images'] ?? [] as $img) {
                    $flatImages[] = $img;
                    if (!empty($img['sub_images'])) {
                        foreach ($img['sub_images'] as $subImg) {
                            $flatImages[] = $subImg;
                        }
                    }
                }
                ?>
                <?php
                // Check if product has options that link to images (color, style, tartan, pattern)
                $hasImageLinkedOptions = false;
                if (!empty($product['options'])) {
                    foreach ($product['options'] as $opt) {
                        $optNameLower = strtolower($opt['option_name']);
                        if (strpos($optNameLower, 'color') !== false ||
                            strpos($optNameLower, 'style') !== false ||
                            strpos($optNameLower, 'tartan') !== false ||
                            strpos($optNameLower, 'pattern') !== false) {
                            $hasImageLinkedOptions = true;
                            break;
                        }
                    }
                }
                // Always show thumbnails - customer should see all color/style options
                // Each main image represents a different color/style variant
                $hideThumbnails = false;
                ?>
                <?php if (count($flatImages) > 1): ?>
                    <div class="thumbnail-strip" id="thumbnailStrip" style="<?php echo $hideThumbnails ? 'display: none;' : ''; ?>">
                        <button type="button" class="thumb-nav thumb-nav-up" onclick="scrollThumbnails(-1)" aria-label="Previous images">&uarr;</button>
                        <div class="thumbnail-container" id="thumbnailContainer">
                            <div class="thumbnail-scroll" id="thumbnailScroll">
                                <?php foreach ($flatImages as $index => $image): ?>
                                    <div class="thumbnail-wrapper <?php echo $index === 0 ? 'active' : ''; ?>"
                                         data-index="<?php echo $index; ?>"
                                         data-is-video="<?php echo !empty($image['is_video']) ? '1' : '0'; ?>"
                                         data-src="<?php echo escape($image['image_path']); ?>"
                                         onclick="selectMedia(this, <?php echo $index; ?>)">
                                        <?php if (!empty($image['is_video'])): ?>
                                            <video src="<?php echo escape($image['image_path']); ?>" class="thumbnail" muted></video>
                                            <span class="video-badge">Video</span>
                                        <?php else: ?>
                                            <img src="<?php echo escape($image['image_path']); ?>"
                                                 alt="<?php echo escape($product['name']); ?> - Image <?php echo $index + 1; ?> | <?php echo appName(); ?>"
                                                 title="<?php echo escape($product['name']); ?>"
                                                 class="thumbnail"
                                                 loading="lazy"
                                                 width="80"
                                                 height="80">
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <button type="button" class="thumb-nav thumb-nav-down" onclick="scrollThumbnails(1)" aria-label="Next images">&darr;</button>
                    </div>
                <?php endif; ?>

                <!-- Main Image/Video -->
                <div class="main-image-container">
                    <div class="main-image" id="mainMediaContainer">
                        <?php
                        $mainImage = !empty($product['images']) ? $product['images'][0]['image_path'] : '/assets/images/placeholder.png';
                        $isMainVideo = !empty($product['images'][0]['is_video']);
                        ?>
                        <?php
                        $categoryName = $product['category_name'] ?? '';
                        $fullAlt = escape($product['name']) . ($categoryName ? ' - ' . escape($categoryName) : '') . ' | ' . appName();
                        ?>
                        <?php if ($isMainVideo): ?>
                            <video src="<?php echo escape($mainImage); ?>" id="mainVideo" autoplay loop muted playsinline>
                                Your browser does not support the video tag.
                            </video>
                            <img src="" alt="<?php echo $fullAlt; ?>" title="<?php echo escape($product['name']); ?>" id="mainImage" width="600" height="600" style="display: none;">
                        <?php else: ?>
                            <img src="<?php echo escape($mainImage); ?>" alt="<?php echo $fullAlt; ?>" title="<?php echo escape($product['name']); ?>" id="mainImage" width="600" height="600">
                            <video src="" id="mainVideo" autoplay loop muted playsinline style="display: none;">
                                Your browser does not support the video tag.
                            </video>
                        <?php endif; ?>
                        <!-- Navigation arrows for product gallery -->
                        <button type="button" class="gallery-nav gallery-nav-prev" onclick="navigateGallery(-1)" aria-label="Previous image">&#10094;</button>
                        <button type="button" class="gallery-nav gallery-nav-next" onclick="navigateGallery(1)" aria-label="Next image">&#10095;</button>
                    </div>
                </div>
            </div>

            <!-- Product Info -->
            <div class="product-details">
                <h1><?php echo escape($product['name']); ?></h1>

                <?php if (!empty($product['is_license_product']) && !empty($latestVersion)): ?>
                <div class="version-badge">
                    <span class="version-label">Version</span>
                    <span class="version-number"><?php echo escape($latestVersion); ?></span>
                </div>
                <?php endif; ?>

                <!-- Price -->
                <div class="price-section">
                    <?php
                    $hasVariants = !empty($product['variants']);
                    $priceMin = $product['price_min'] ?? $product['price'];
                    $priceMax = $product['price_max'] ?? $product['price'];
                    $showRange = $hasVariants && $priceMin != $priceMax;

                    // Determine free shipping indicator (hide for digital products)
                    $freeShippingText = '';
                    if (empty($product['is_digital'])) {
                        if (!empty($product['ships_free'])) {
                            $freeShippingText = '<span class="free-shipping-indicator">Free Shipping</span>';
                        } elseif (!empty($product['ships_free_us'])) {
                            $freeShippingText = '<span class="free-shipping-indicator">Free US Shipping</span>';
                        }
                    }
                    ?>
                    <?php if ($showRange): ?>
                        <p class="price price-range" id="priceDisplay">
                            <?php echo formatPrice($priceMin); ?> - <?php echo formatPrice($priceMax); ?><?php echo $freeShippingText; ?>
                        </p>
                        <p class="price-note">Select options to see exact price</p>
                    <?php elseif ($product['sale_price']): ?>
                        <p class="original-price"><del><?php echo formatPrice($product['price']); ?></del></p>
                        <p class="sale-price" id="priceDisplay"><?php echo formatPrice($product['sale_price']); ?><?php echo $freeShippingText; ?></p>
                        <p class="savings">Save <?php echo round(($product['price'] - $product['sale_price']) / $product['price'] * 100); ?>%</p>
                    <?php else: ?>
                        <p class="price" id="priceDisplay"><?php echo formatPrice($product['price']); ?><?php echo $freeShippingText; ?></p>
                    <?php endif; ?>
                </div>

                <!-- Options (Color, Size, etc.) -->
                <?php if (!empty($product['options'])): ?>
                    <div class="product-options">
                        <?php foreach ($product['options'] as $option): ?>
                            <div class="option-group">
                                <label for="option_<?php echo $option['id']; ?>"><?php echo escape($option['option_name']); ?>:</label>
                                <select name="option_<?php echo $option['id']; ?>"
                                        id="option_<?php echo $option['id']; ?>"
                                        class="option-select"
                                        data-option-id="<?php echo $option['id']; ?>"
                                        required>
                                    <option value="">Select <?php echo escape($option['option_name']); ?></option>
                                    <?php foreach ($option['values'] as $value): ?>
                                        <option value="<?php echo $value['id']; ?>"><?php echo escape($value['value_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Inventory -->
                <div class="inventory-section">
                    <?php
                    // Calculate initial stock - use variant stock if product has variants
                    $hasVariants = !empty($product['variants']);
                    $variantStock = 0;
                    if ($hasVariants) {
                        foreach ($product['variants'] as $variant) {
                            if ($variant['is_active']) {
                                $variantStock += (int)$variant['inventory_count'];
                            }
                        }
                        $initialStock = $variantStock;
                    } else {
                        $initialStock = (int)$product['inventory_count'];
                    }
                    ?>
                    <p id="stockStatus" class="<?php echo $initialStock > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                        <?php if ($hasVariants): ?>
                            <?php echo $initialStock > 0 ? 'Select Options' : 'Out of Stock'; ?>
                        <?php elseif ($initialStock > 0): ?>
                            In Stock
                        <?php else: ?>
                            Out of Stock
                        <?php endif; ?>
                    </p>
                </div>

                <!-- Live Viewer Count -->
                <div class="viewing-now" id="viewingNow" style="display: none;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <span><strong id="viewerCount">0</strong> people are viewing this right now</span>
                </div>

                <?php if (!empty($product['processing_time'])): ?>
                <!-- Processing Time -->
                <div class="processing-time-section">
                    <span class="processing-icon">&#9203;</span>
                    <span class="processing-label">Processing Time:</span>
                    <span class="processing-value"><?php echo escape($product['processing_time']); ?></span>
                </div>
                <?php endif; ?>

                <!-- Add to Cart -->
                <form action="/cart/add" method="POST" class="add-to-cart-section" id="addToCartForm">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <input type="hidden" name="variant_id" id="variantId" value="">

                    <?php if (!empty($product['options'])): ?>
                        <?php foreach ($product['options'] as $option): ?>
                            <input type="hidden" name="options[<?php echo $option['id']; ?>]" id="optionInput_<?php echo $option['id']; ?>" value="">
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <div class="quantity-selector">
                        <label for="quantity">Quantity:</label>
                        <div class="qty-inputs">
                            <button type="button" class="qty-btn" onclick="decrementQty()">&minus;</button>
                            <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $initialStock ?: 1; ?>">
                            <button type="button" class="qty-btn" onclick="incrementQty()">+</button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-large" id="addToCartBtn" <?php echo ($hasVariants || $initialStock <= 0) ? 'disabled' : ''; ?>>
                        <?php echo $hasVariants ? 'Select Options' : ($initialStock > 0 ? 'Add to Cart' : 'Out of Stock'); ?>
                    </button>
                </form>

                <!-- Favorite & Notify Buttons -->
                <div class="action-buttons-row">
                    <button type="button" class="favorite-heart-btn" id="favoriteBtn" data-product-id="<?php echo $product['id']; ?>" onclick="toggleFavorite(<?php echo $product['id']; ?>, this)" aria-label="Add to favorites">
                        <span class="heart-container">
                            <svg class="heart-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                            </svg>
                            <span class="particles"></span>
                        </span>
                        <span class="favorite-text">Add to Favorites</span>
                    </button>

                    <!-- Notify Me Button (shows when out of stock) -->
                    <button type="button" class="notify-me-btn" id="notifyMeBtn" style="display: none;" onclick="showNotifyModal()">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                        </svg>
                        <span>Notify Me</span>
                    </button>
                </div>

                <!-- Back in Stock Notification Modal -->
                <div id="notifyModal" class="notify-modal" style="display: none;">
                    <div class="notify-modal-content">
                        <button type="button" class="notify-modal-close" onclick="hideNotifyModal()">&times;</button>
                        <div class="notify-modal-header">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#FF68C5" stroke-width="2">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                            </svg>
                            <h3>Get Notified</h3>
                        </div>
                        <p class="notify-modal-subtitle">We'll email you when <strong id="notifyProductName"><?php echo escape($product['name']); ?></strong> is back in stock.</p>
                        <form id="notifyForm" onsubmit="submitNotifyForm(event)">
                            <input type="hidden" name="_csrf_token" value="<?php echo csrfToken(); ?>">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <input type="hidden" name="variant_id" id="notifyVariantId" value="">
                            <input type="hidden" name="variant_name" id="notifyVariantName" value="">
                            <div class="notify-form-group">
                                <input type="email" name="email" id="notifyEmail" class="notify-input" placeholder="Enter your email" required>
                            </div>
                            <div class="notify-checkbox-group">
                                <label class="notify-checkbox-label">
                                    <input type="checkbox" name="subscribe_newsletter" value="1" checked>
                                    <span>Also send me deals & new arrivals</span>
                                </label>
                            </div>
                            <button type="submit" class="notify-submit-btn" id="notifySubmitBtn">
                                <span class="notify-btn-text">Notify Me</span>
                                <span class="notify-btn-loading" style="display: none;">
                                    <svg class="spinner" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="30 70"/></svg>
                                </span>
                            </button>
                        </form>
                        <p class="notify-modal-footer">We respect your privacy. Unsubscribe anytime.</p>
                    </div>
                </div>

                <!-- Share Buttons -->
                <div class="share-buttons">
                    <span class="share-label">Share:</span>
                    <?php
                    $shareUrl = appUrl() . '/products/' . urlencode($product['slug']);
                    $shareTitle = urlencode($product['name'] . ' | ' . appName());
                    $shareImage = !empty($product['images']) ? appUrl() . $product['images'][0]['image_path'] : '';
                    ?>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $shareUrl; ?>" target="_blank" rel="noopener" class="share-btn facebook" title="Share on Facebook">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?php echo $shareUrl; ?>&text=<?php echo $shareTitle; ?>" target="_blank" rel="noopener" class="share-btn twitter" title="Share on X">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <a href="https://pinterest.com/pin/create/button/?url=<?php echo $shareUrl; ?>&media=<?php echo urlencode($shareImage); ?>&description=<?php echo $shareTitle; ?>" target="_blank" rel="noopener" class="share-btn pinterest" title="Pin on Pinterest">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.401.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.354-.629-2.758-1.379l-.749 2.848c-.269 1.045-1.004 2.352-1.498 3.146 1.123.345 2.306.535 3.55.535 6.607 0 11.985-5.365 11.985-11.987C23.97 5.39 18.592.026 11.985.026L12.017 0z"/></svg>
                    </a>
                    <button type="button" class="share-btn copy-link" onclick="copyProductLink(this)" title="Copy Link">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                    </button>
                </div>

                <!-- Shipping Details (Google Shopping friendly) - Hidden for digital products -->
                <?php if (empty($product['is_digital'])): ?>
                <?php
                // Get shipping dimensions - use database values or estimate based on product type
                $weightOz = $product['weight_oz'] ?? null;
                $lengthIn = $product['length_in'] ?? null;
                $widthIn = $product['width_in'] ?? null;
                $heightIn = $product['height_in'] ?? null;

                // Estimate if not set - based on product name/type
                if (!$weightOz || !$lengthIn) {
                    $nameLower = strtolower($product['name']);
                    if (strpos($nameLower, 'blanket') !== false || strpos($nameLower, 'throw') !== false) {
                        $weightOz = $weightOz ?: 80;
                        $lengthIn = $lengthIn ?: 14; $widthIn = $widthIn ?: 11; $heightIn = $heightIn ?: 4;
                    } elseif (strpos($nameLower, 'coat') !== false || strpos($nameLower, 'poncho') !== false) {
                        $weightOz = $weightOz ?: 64;
                        $lengthIn = $lengthIn ?: 15; $widthIn = $widthIn ?: 12; $heightIn = $heightIn ?: 5;
                    } elseif (strpos($nameLower, 'sweater') !== false || strpos($nameLower, 'cardigan') !== false) {
                        $weightOz = $weightOz ?: 48;
                        $lengthIn = $lengthIn ?: 14; $widthIn = $widthIn ?: 11; $heightIn = $heightIn ?: 4;
                    } elseif (strpos($nameLower, 'bag') !== false || strpos($nameLower, 'tote') !== false || strpos($nameLower, 'purse') !== false) {
                        $weightOz = $weightOz ?: 24;
                        $lengthIn = $lengthIn ?: 14; $widthIn = $widthIn ?: 10; $heightIn = $heightIn ?: 4;
                    } elseif (strpos($nameLower, 't-shirt') !== false || strpos($nameLower, 'tee') !== false || strpos($nameLower, 'shirt') !== false) {
                        $weightOz = $weightOz ?: 8;
                        $lengthIn = $lengthIn ?: 10; $widthIn = $widthIn ?: 8; $heightIn = $heightIn ?: 2;
                    } elseif (strpos($nameLower, 'comforter') !== false || strpos($nameLower, 'bedding') !== false || strpos($nameLower, 'quilt') !== false) {
                        $weightOz = $weightOz ?: 112;
                        $lengthIn = $lengthIn ?: 18; $widthIn = $widthIn ?: 14; $heightIn = $heightIn ?: 8;
                    } else {
                        // Default for misc items
                        $weightOz = $weightOz ?: 16;
                        $lengthIn = $lengthIn ?: 12; $widthIn = $widthIn ?: 10; $heightIn = $heightIn ?: 4;
                    }
                }

                // Convert weight to lbs for display
                $weightLbs = round($weightOz / 16, 1);
                $weightDisplay = $weightLbs >= 1 ? $weightLbs . ' lb' : ($weightOz . ' oz');
                ?>
                <details class="shipping-specs">
                    <summary>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="3" width="15" height="13"/>
                            <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                            <circle cx="5.5" cy="18.5" r="2.5"/>
                            <circle cx="18.5" cy="18.5" r="2.5"/>
                        </svg>
                        Shipping Details
                    </summary>
                    <div class="shipping-specs-content">
                        <div class="spec-row">
                            <span class="spec-label">Weight:</span>
                            <span class="spec-value" itemprop="weight"><?php echo $weightDisplay; ?></span>
                        </div>
                        <div class="spec-row">
                            <span class="spec-label">Package:</span>
                            <span class="spec-value"><?php echo (int)$lengthIn; ?>" × <?php echo (int)$widthIn; ?>" × <?php echo (int)$heightIn; ?>"</span><?php if ($product['slug'] === 'aran-cowl-neck-merino-wool-poncho-irish-cable-knit-shawl'): ?><span class="lucky-clover" id="luckyClover" title="">&#9752;</span><?php endif; ?>
                        </div>
                    </div>
                </details>
                <?php else: ?>
                <!-- Digital Delivery Notice -->
                <div class="digital-delivery-badge">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    <span>Instant Digital Download</span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Description Section - Below images -->
        <?php if ($product['description']): ?>
            <div class="description-section-full">
                <h2>Description</h2>
                <div class="description-text"><?php echo $product['description']; ?></div>
            </div>
        <?php endif; ?>

        <!-- Reviews Section -->
        <div class="reviews-section" id="reviewsSection">
            <h2>Customer Reviews</h2>

            <!-- Review Stats Summary -->
            <div class="review-stats" id="reviewStats">
                <div class="review-average">
                    <span class="average-number" id="averageRating">0</span>
                    <div class="stars-display" id="averageStars"></div>
                    <span class="total-reviews" id="totalReviews">0 reviews</span>
                </div>
                <div class="rating-distribution" id="ratingDistribution">
                    <!-- Filled by JS -->
                </div>
            </div>

            <!-- Write Review Button/Form -->
            <div class="write-review-section" id="writeReviewSection">
                <div id="reviewLoginPrompt" style="display: none;">
                    <p>Please <a href="/login?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">sign in</a> to write a review.</p>
                </div>
                <div id="reviewPurchasePrompt" style="display: none;">
                    <p>You must purchase this product to leave a review.</p>
                </div>
                <div id="reviewAlreadySubmitted" style="display: none;">
                    <p>You have already reviewed this product. Thank you!</p>
                </div>
                <div id="reviewFormContainer" style="display: none;">
                    <button type="button" class="btn btn-primary" id="writeReviewBtn" onclick="toggleReviewForm()">Write a Review</button>

                    <form id="reviewForm" class="review-form" style="display: none;">
                        <input type="hidden" name="csrf_token" value="<?php echo escape($_SESSION['csrf_token'] ?? ''); ?>">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">

                        <div class="form-group">
                            <label>Your Rating *</label>
                            <div class="star-rating-input" id="starRatingInput">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="star" data-rating="<?php echo $i; ?>" onclick="setRating(<?php echo $i; ?>)">&#9733;</span>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="rating" id="ratingInput" required>
                        </div>

                        <div class="form-group">
                            <label for="reviewTitle">Review Title (optional)</label>
                            <input type="text" id="reviewTitle" name="title" maxlength="255" placeholder="Summarize your experience">
                        </div>

                        <div class="form-group">
                            <label for="reviewText">Your Review (optional)</label>
                            <textarea id="reviewText" name="review_text" rows="4" placeholder="Tell others what you thought of this product..."></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Submit Review</button>
                            <button type="button" class="btn btn-secondary" onclick="toggleReviewForm()">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Reviews List -->
            <div class="reviews-list" id="reviewsList">
                <div class="loading-reviews" id="loadingReviews">Loading reviews...</div>
            </div>

            <div class="load-more-reviews" id="loadMoreContainer" style="display: none;">
                <button type="button" class="btn btn-secondary" onclick="loadMoreReviews()">Load More Reviews</button>
            </div>
        </div>

        <!-- Recently Viewed Products -->
        <section class="recently-viewed-section" id="recentlyViewedSection" style="display: none;">
            <h2>Recently Viewed</h2>
            <div class="products-grid recently-viewed-grid" id="recentlyViewedGrid">
                <!-- Populated by JavaScript -->
            </div>
        </section>

        <!-- Related Products -->
        <?php if (!empty($related)): ?>
            <section class="related-products">
                <h2>Related Products</h2>
                <div class="products-grid">
                    <?php foreach ($related as $relatedProduct): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <a href="/products/<?php echo escape($relatedProduct['slug']); ?>">
                                    <img src="<?php echo escape($relatedProduct['primary_image'] ?? '/assets/images/placeholder.png'); ?>"
                                         alt="<?php echo escape($relatedProduct['name']); ?> | <?php echo appName(); ?>"
                                         title="<?php echo escape($relatedProduct['name']); ?>"
                                         loading="lazy"
                                         width="300"
                                         height="300">
                                </a>
                            </div>
                            <div class="product-info">
                                <h3><a href="/products/<?php echo escape($relatedProduct['slug']); ?>"><?php echo escape($relatedProduct['name']); ?></a></h3>
                                <p class="product-price">
                                    <?php if ($relatedProduct['sale_price']): ?>
                                        <span class="sale-price"><?php echo formatPrice($relatedProduct['sale_price']); ?></span>
                                    <?php else: ?>
                                        <?php echo formatPrice($relatedProduct['price']); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</section>

<script>
// Product data from PHP
const variants = <?php echo json_encode($product['variants'] ?? []); ?>;
const hasOptions = <?php echo !empty($product['options']) ? 'true' : 'false'; ?>;
const basePrice = <?php echo $product['sale_price'] ?: $product['price']; ?>;
const allProductImages = <?php echo json_encode($product['images'] ?? []); ?>;
const visibleThumbnails = 5;

// Build dependent option mapping (e.g., which sizes are available for each color)
const optionDependencies = {};
const productOptions = <?php echo json_encode($product['options'] ?? []); ?>;

// Find Color and Size option IDs
let colorOptionId = null;
let sizeOptionId = null;
productOptions.forEach(opt => {
    const nameLower = opt.option_name.toLowerCase();
    if (nameLower.includes('color') || nameLower.includes('style') || nameLower.includes('pattern')) {
        colorOptionId = opt.id;
    }
    if (nameLower.includes('size')) {
        sizeOptionId = opt.id;
    }
});

// Build mapping: colorValueId -> [available sizeValueIds]
if (colorOptionId && sizeOptionId) {
    variants.forEach(v => {
        if (!v.option_value_ids) return;
        const valueIds = v.option_value_ids.split(',').map(id => parseInt(id));

        // Find color and size values in this variant
        let colorVal = null, sizeVal = null;
        productOptions.forEach(opt => {
            opt.values.forEach(val => {
                if (valueIds.includes(val.id)) {
                    if (opt.id == colorOptionId) colorVal = val.id;
                    if (opt.id == sizeOptionId) sizeVal = val.id;
                }
            });
        });

        if (colorVal && sizeVal) {
            if (!optionDependencies[colorVal]) optionDependencies[colorVal] = [];
            if (!optionDependencies[colorVal].includes(sizeVal)) {
                optionDependencies[colorVal].push(sizeVal);
            }
        }
    });
}

// Store all size options initially for filtering
let allSizeOptions = [];
if (sizeOptionId) {
    const sizeOpt = productOptions.find(opt => opt.id == sizeOptionId);
    if (sizeOpt && sizeOpt.values) {
        allSizeOptions = sizeOpt.values.map(v => ({ id: v.id, name: v.value_name }));
    }
}

// Function to filter size options based on selected color
// Uses DOM removal approach for cross-browser compatibility (display:none doesn't work on <option> in Safari)
function filterDependentOptions(selectedColorValue) {
    if (!colorOptionId || !sizeOptionId) return;

    const sizeSelect = document.querySelector('.option-select[data-option-id="' + sizeOptionId + '"]');
    if (!sizeSelect) return;

    const availableSizes = optionDependencies[selectedColorValue] || [];

    // Store current selection
    const currentSize = sizeSelect.value;

    // Clear all options except placeholder
    while (sizeSelect.options.length > 1) {
        sizeSelect.remove(1);
    }

    // Add back only available size options
    allSizeOptions.forEach(sizeOpt => {
        if (availableSizes.includes(sizeOpt.id)) {
            const option = document.createElement('option');
            option.value = sizeOpt.id;
            option.textContent = sizeOpt.name;
            sizeSelect.appendChild(option);
        }
    });

    // Restore selection if still available, otherwise reset
    if (currentSize && availableSizes.includes(parseInt(currentSize))) {
        sizeSelect.value = currentSize;
    } else if (currentSize) {
        sizeSelect.value = '';
        const hiddenInput = document.getElementById('optionInput_' + sizeOptionId);
        if (hiddenInput) hiddenInput.value = '';
    }
}

// Flatten images for gallery: main images + their sub-images
function flattenImages(images) {
    const flat = [];
    images.forEach(img => {
        flat.push(img);
        if (img.sub_images && img.sub_images.length > 0) {
            img.sub_images.forEach(sub => flat.push(sub));
        }
    });
    return flat;
}

// Currently displayed images (filtered or all)
let currentImages = flattenImages(allProductImages);
let currentThumbnailOffset = 0;

// Track which options have linked images (can be multiple)
let imageFilterOptionIds = [];

<?php if (!empty($product['options'])): ?>
<?php foreach ($product['options'] as $option): ?>
<?php
$optionNameLower = strtolower($option['option_name']);
if (strpos($optionNameLower, 'color') !== false ||
    strpos($optionNameLower, 'style') !== false ||
    strpos($optionNameLower, 'tartan') !== false ||
    strpos($optionNameLower, 'pattern') !== false):
?>
imageFilterOptionIds.push(<?php echo $option['id']; ?>);
<?php endif; ?>
<?php endforeach; ?>
<?php endif; ?>

// Legacy single ID for backward compatibility
let imageFilterOptionId = imageFilterOptionIds.length > 0 ? imageFilterOptionIds[0] : null;

// Track if video has been played once
let videoPlayedOnce = false;

function selectImage(thumb, index) {
    // Legacy function - redirect to selectMedia
    const wrapper = thumb.closest('.thumbnail-wrapper') || thumb;
    selectMedia(wrapper, index);
}

function selectMedia(wrapper, index) {
    // Reset zoom when changing images
    if (typeof resetImageZoom === 'function') {
        resetImageZoom();
    }

    const isVideo = wrapper.dataset.isVideo === '1';
    const src = wrapper.dataset.src;
    const mainImage = document.getElementById('mainImage');
    const mainVideo = document.getElementById('mainVideo');

    // Track current index for gallery navigation
    currentGalleryIndex = index;

    // Update active state on thumbnails
    document.querySelectorAll('.thumbnail-wrapper').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
    wrapper.classList.add('active');

    if (isVideo) {
        // Show video, hide image
        mainImage.style.display = 'none';
        mainVideo.style.display = 'block';
        mainVideo.src = src;
        // Auto-play video with loop (muted to allow autoplay)
        mainVideo.play();
    } else {
        // Show image, hide video
        mainVideo.style.display = 'none';
        mainVideo.pause();
        mainImage.style.display = 'block';
        mainImage.src = src;
    }

    // Update zoom hint visibility (hide for videos, show for images)
    if (typeof updateZoomHintVisibility === 'function') {
        updateZoomHintVisibility();
    }
}

// Filter and rebuild thumbnail gallery based on selected option values
// Now supports multiple option values per image via linked_option_value_ids array
function filterImagesByOption(optionValueId) {
    // Filter main images to those that have this option value in their linked array
    let filteredMainImages = allProductImages.filter(img => {
        // Support both old single option_value_id and new linked_option_value_ids array
        if (img.linked_option_value_ids && Array.isArray(img.linked_option_value_ids)) {
            return img.linked_option_value_ids.includes(optionValueId);
        }
        // Fallback to old single value
        return img.option_value_id == optionValueId;
    });

    // If no images match this option, show all images
    if (filteredMainImages.length === 0) {
        currentImages = flattenImages(allProductImages);
    } else {
        currentImages = flattenImages(filteredMainImages);
    }

    rebuildThumbnailGallery();
}

// Filter images by multiple selected option values (for combined filtering)
function filterImagesByMultipleOptions(selectedOptionValues) {
    if (!selectedOptionValues || selectedOptionValues.length === 0) {
        currentImages = flattenImages(allProductImages);
        rebuildThumbnailGallery();
        return;
    }

    // Show images that match ALL selected option values
    let filteredMainImages = allProductImages.filter(img => {
        const linkedIds = img.linked_option_value_ids || [];
        // Image matches if it has ALL selected option values in its linked array
        return selectedOptionValues.every(val => linkedIds.includes(val));
    });

    // If no exact match, try images that match ANY of the selected values
    if (filteredMainImages.length === 0) {
        filteredMainImages = allProductImages.filter(img => {
            const linkedIds = img.linked_option_value_ids || [];
            return selectedOptionValues.some(val => linkedIds.includes(val));
        });
    }

    // If still no matches, show all images
    if (filteredMainImages.length === 0) {
        currentImages = flattenImages(allProductImages);
    } else {
        currentImages = flattenImages(filteredMainImages);
    }

    rebuildThumbnailGallery();
}

// Reset to show all images
function resetImageFilter() {
    currentImages = flattenImages(allProductImages);
    // Always show thumbnails so customer can see all color/style options
    const thumbnailStrip = document.getElementById('thumbnailStrip');
    if (thumbnailStrip && currentImages.length > 1) {
        thumbnailStrip.style.display = 'flex';
    }
    // Reset main image to primary
    const mainImage = document.getElementById('mainImage');
    if (mainImage && allProductImages.length > 0) {
        mainImage.src = allProductImages[0].image_path;
    }
}

// Rebuild the thumbnail strip with current images
function rebuildThumbnailGallery() {
    const thumbnailScroll = document.getElementById('thumbnailScroll');
    const thumbnailStrip = document.getElementById('thumbnailStrip');
    const mainImage = document.getElementById('mainImage');
    const mainVideo = document.getElementById('mainVideo');

    if (!thumbnailScroll) return;

    // Reset scroll position
    currentThumbnailOffset = 0;
    thumbnailScroll.style.transform = 'translateY(0)';

    // Clear existing thumbnails
    thumbnailScroll.innerHTML = '';

    // Add filtered thumbnails
    currentImages.forEach((img, index) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'thumbnail-wrapper' + (index === 0 ? ' active' : '');
        wrapper.dataset.index = index;
        wrapper.dataset.isVideo = img.is_video ? '1' : '0';
        wrapper.dataset.src = img.image_path;
        wrapper.onclick = function() { selectMedia(this, index); };

        if (img.is_video) {
            const video = document.createElement('video');
            video.src = img.image_path;
            video.className = 'thumbnail';
            video.muted = true;
            wrapper.appendChild(video);

            const badge = document.createElement('span');
            badge.className = 'video-badge';
            badge.textContent = 'Video';
            wrapper.appendChild(badge);
        } else {
            const thumb = document.createElement('img');
            thumb.src = img.image_path;
            thumb.alt = '<?php echo escape($product['name']); ?>';
            thumb.className = 'thumbnail';
            wrapper.appendChild(thumb);
        }

        thumbnailScroll.appendChild(wrapper);
    });

    // Update main media to first in filtered set
    if (currentImages.length > 0) {
        const firstImg = currentImages[0];
        if (firstImg.is_video) {
            mainImage.style.display = 'none';
            mainVideo.style.display = 'block';
            mainVideo.src = firstImg.image_path;
        } else {
            mainVideo.style.display = 'none';
            mainVideo.pause();
            mainImage.style.display = 'block';
            mainImage.src = firstImg.image_path;
        }
    }

    // Show thumbnail strip when there are multiple images after filtering
    if (thumbnailStrip) {
        thumbnailStrip.style.display = currentImages.length > 1 ? 'flex' : 'none';
    }

    updateNavButtons();

    // Update zoom hint visibility (hide for videos, show for images)
    if (typeof updateZoomHintVisibility === 'function') {
        updateZoomHintVisibility();
    }
}

// Thumbnail scrolling
function scrollThumbnails(direction) {
    const container = document.getElementById('thumbnailScroll');
    const thumbHeight = 90; // 80px + 10px gap

    currentThumbnailOffset += direction;

    // Constrain to valid range
    const maxOffset = Math.max(0, currentImages.length - visibleThumbnails);
    currentThumbnailOffset = Math.max(0, Math.min(currentThumbnailOffset, maxOffset));

    container.style.transform = `translateY(-${currentThumbnailOffset * thumbHeight}px)`;

    updateNavButtons();
}

function updateNavButtons() {
    const upBtn = document.querySelector('.thumb-nav-up');
    const downBtn = document.querySelector('.thumb-nav-down');
    const maxOffset = Math.max(0, currentImages.length - visibleThumbnails);

    if (upBtn) {
        upBtn.disabled = currentThumbnailOffset <= 0;
        upBtn.style.opacity = currentThumbnailOffset <= 0 ? '0.3' : '1';
    }
    if (downBtn) {
        downBtn.disabled = currentThumbnailOffset >= maxOffset;
        downBtn.style.opacity = currentThumbnailOffset >= maxOffset ? '0.3' : '1';
    }
}

// Initialize nav buttons
document.addEventListener('DOMContentLoaded', function() {
    updateNavButtons();
    updateGalleryNavButtons();
});

// Current gallery index
let currentGalleryIndex = 0;

// Navigate to next/prev image in gallery
function navigateGallery(direction) {
    // Reset zoom when changing images
    if (typeof resetImageZoom === 'function') {
        resetImageZoom();
    }

    const totalImages = currentImages.length;
    if (totalImages <= 1) return;

    currentGalleryIndex += direction;

    // Wrap around
    if (currentGalleryIndex < 0) currentGalleryIndex = totalImages - 1;
    if (currentGalleryIndex >= totalImages) currentGalleryIndex = 0;

    // Find and click the thumbnail at this index
    const wrapper = document.querySelector(`.thumbnail-wrapper[data-index="${currentGalleryIndex}"]`);
    if (wrapper) {
        selectMedia(wrapper, currentGalleryIndex);

        // Scroll thumbnails to keep current in view
        const thumbHeight = 90;
        const targetOffset = Math.max(0, currentGalleryIndex - 2);
        const maxOffset = Math.max(0, totalImages - visibleThumbnails);
        currentThumbnailOffset = Math.min(targetOffset, maxOffset);

        const container = document.getElementById('thumbnailScroll');
        if (container) {
            container.style.transform = `translateY(-${currentThumbnailOffset * thumbHeight}px)`;
        }
        updateNavButtons();
    }

    updateGalleryNavButtons();
}

// Update gallery nav button visibility
function updateGalleryNavButtons() {
    const prevBtn = document.querySelector('.gallery-nav-prev');
    const nextBtn = document.querySelector('.gallery-nav-next');
    const totalImages = currentImages.length;

    if (prevBtn && nextBtn) {
        const showNav = totalImages > 1;
        prevBtn.style.display = showNav ? 'flex' : 'none';
        nextBtn.style.display = showNav ? 'flex' : 'none';
    }
}

function incrementQty() {
    const input = document.getElementById('quantity');
    const max = parseInt(input.getAttribute('max'));
    if (parseInt(input.value) < max) {
        input.value = parseInt(input.value) + 1;
    }
}

function decrementQty() {
    const input = document.getElementById('quantity');
    if (parseInt(input.value) > 1) {
        input.value = parseInt(input.value) - 1;
    }
}

function formatPrice(price) {
    return '$' + parseFloat(price).toFixed(2);
}

// Handle option selection
if (hasOptions) {
    const optionSelects = document.querySelectorAll('.option-select');

    optionSelects.forEach(select => {
        select.addEventListener('change', function() {
            const optionId = parseInt(this.dataset.optionId);
            document.getElementById('optionInput_' + optionId).value = this.value;

            // Filter dependent options (e.g., sizes based on color)
            if (optionId == colorOptionId && this.value) {
                filterDependentOptions(parseInt(this.value));
            }

            // Filter images if this is an image-linked option (Color, Style, Tartan, Pattern)
            if (imageFilterOptionIds.includes(optionId)) {
                // Collect all selected values from image-linked options
                const selectedImageOptionValues = [];
                imageFilterOptionIds.forEach(filterId => {
                    const filterSelect = document.querySelector(`.option-select[data-option-id="${filterId}"]`);
                    if (filterSelect && filterSelect.value) {
                        selectedImageOptionValues.push(parseInt(filterSelect.value));
                    }
                });

                if (selectedImageOptionValues.length > 0) {
                    filterImagesByMultipleOptions(selectedImageOptionValues);
                } else {
                    resetImageFilter();
                }
            }

            updateVariantSelection();
        });
    });

    function updateVariantSelection() {
        const selects = document.querySelectorAll('.option-select');
        const selectedValues = [];
        let allSelected = true;

        selects.forEach(select => {
            if (select.value) {
                selectedValues.push(parseInt(select.value));
            } else {
                allSelected = false;
            }
        });

        const btn = document.getElementById('addToCartBtn');
        const variantInput = document.getElementById('variantId');
        const priceDisplay = document.getElementById('priceDisplay');
        const stockStatus = document.getElementById('stockStatus');

        if (!allSelected) {
            btn.textContent = 'Select Options';
            btn.disabled = true;
            variantInput.value = '';
            return;
        }

        const matchingVariant = variants.find(v => {
            if (!v.option_value_ids) return false;
            const variantValueIds = v.option_value_ids.split(',').map(id => parseInt(id));
            return selectedValues.length === variantValueIds.length &&
                   selectedValues.every(sv => variantValueIds.includes(sv));
        });

        if (matchingVariant) {
            variantInput.value = matchingVariant.id;

            const finalPrice = parseFloat(basePrice) + parseFloat(matchingVariant.price_adjustment || 0);
            priceDisplay.textContent = formatPrice(finalPrice);
            priceDisplay.classList.remove('price-range');

            const priceNote = document.querySelector('.price-note');
            if (priceNote) priceNote.style.display = 'none';

            if (matchingVariant.inventory_count > 0 && matchingVariant.is_active == 1) {
                const stock = parseInt(matchingVariant.inventory_count);
                if (stock <= 3) {
                    stockStatus.textContent = `Only ${stock} left!`;
                    stockStatus.className = 'low-stock';
                } else {
                    stockStatus.textContent = 'In Stock';
                    stockStatus.className = 'in-stock';
                }
                btn.textContent = 'Add to Cart';
                btn.disabled = false;
                document.getElementById('quantity').max = stock;
                // Hide notify button when in stock
                updateNotifyButton(false, matchingVariant.id, '');
            } else {
                stockStatus.textContent = 'Out of Stock';
                stockStatus.className = 'out-of-stock';
                btn.textContent = 'Out of Stock';
                btn.disabled = true;
                // Show notify button when out of stock - build variant name from selections
                const variantName = buildVariantName();
                updateNotifyButton(true, matchingVariant.id, variantName);
            }
        } else {
            btn.textContent = 'Unavailable';
            btn.disabled = true;
            variantInput.value = '';
            // Hide notify for unavailable combinations
            updateNotifyButton(false, null, '');
        }
    }

    document.getElementById('addToCartForm').addEventListener('submit', function(e) {
        if (!document.getElementById('variantId').value) {
            e.preventDefault();
            alert('Please select all options before adding to cart.');
        }
    });
}

// Favorites functionality
const csrfToken = '<?php echo csrfToken(); ?>';
const productId = <?php echo $product['id']; ?>;

async function toggleFavorite(productId, btn) {
    try {
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('_csrf_token', csrfToken);

        const response = await fetch('/favorites/toggle', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success) {
            const wasActive = btn.classList.contains('active');
            btn.classList.toggle('active', data.favorited);
            const textEl = btn.querySelector('.favorite-text');
            if (textEl) {
                textEl.textContent = data.favorited ? 'Saved to Favorites' : 'Add to Favorites';
            }
            btn.setAttribute('aria-label', data.favorited ? 'Remove from favorites' : 'Add to favorites');

            // Trigger heart burst animation when adding to favorites
            if (data.favorited && !wasActive) {
                createHeartBurst(btn);
            }
        }
    } catch (error) {
        console.error('Error toggling favorite:', error);
    }
}

// Create exploding hearts effect
function createHeartBurst(btn) {
    const container = btn.querySelector('.heart-container');
    if (!container) return;

    const colors = ['#ff6b6b', '#ee5a5a', '#ff8787', '#fa5252', '#ff4757'];
    const particleCount = 12;

    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('span');
        particle.className = 'heart-particle';
        particle.innerHTML = '&#10084;';
        particle.style.color = colors[Math.floor(Math.random() * colors.length)];

        const angle = (i / particleCount) * 360;
        const distance = 30 + Math.random() * 20;
        const tx = Math.cos(angle * Math.PI / 180) * distance;
        const ty = Math.sin(angle * Math.PI / 180) * distance;

        particle.style.setProperty('--tx', tx + 'px');
        particle.style.setProperty('--ty', ty + 'px');
        particle.style.setProperty('--delay', (Math.random() * 0.1) + 's');

        container.appendChild(particle);

        // Remove particle after animation
        setTimeout(() => particle.remove(), 600);
    }
}

// Check if current product is favorited
document.addEventListener('DOMContentLoaded', async function() {
    try {
        const response = await fetch('/favorites/ids', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();

        if (data.success && data.ids.includes(productId)) {
            const btn = document.getElementById('favoriteBtn');
            if (btn) {
                btn.classList.add('active');
                const textEl = btn.querySelector('.favorite-text');
                if (textEl) {
                    textEl.textContent = 'Saved to Favorites';
                }
                btn.setAttribute('aria-label', 'Remove from favorites');
            }
        }
    } catch (error) {
        console.error('Error loading favorites:', error);
    }
});

// Copy product link to clipboard
function copyProductLink(btn) {
    const url = '<?php echo appUrl(); ?>/products/<?php echo escape($product['slug']); ?>';

    navigator.clipboard.writeText(url).then(() => {
        btn.classList.add('copied');
        btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>';

        setTimeout(() => {
            btn.classList.remove('copied');
            btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>';
        }, 2000);
    }).catch(err => {
        console.error('Failed to copy:', err);
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = url;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);

        btn.classList.add('copied');
        setTimeout(() => btn.classList.remove('copied'), 2000);
    });
}

// Back-in-Stock Notification Functions
function updateNotifyButton(show, variantId, variantName) {
    const notifyBtn = document.getElementById('notifyMeBtn');
    const notifyVariantId = document.getElementById('notifyVariantId');
    const notifyVariantName = document.getElementById('notifyVariantName');

    if (notifyBtn) {
        notifyBtn.style.display = show ? 'flex' : 'none';
    }
    if (notifyVariantId) {
        notifyVariantId.value = variantId || '';
    }
    if (notifyVariantName) {
        notifyVariantName.value = variantName || '';
    }
}

function buildVariantName() {
    const selects = document.querySelectorAll('.option-select');
    const parts = [];
    selects.forEach(select => {
        if (select.value) {
            const selectedOption = select.options[select.selectedIndex];
            if (selectedOption) {
                parts.push(selectedOption.textContent.trim());
            }
        }
    });
    return parts.join(' / ');
}

function showNotifyModal() {
    const modal = document.getElementById('notifyModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        // Focus email input
        setTimeout(() => {
            const emailInput = document.getElementById('notifyEmail');
            if (emailInput) emailInput.focus();
        }, 100);
    }
}

function hideNotifyModal() {
    const modal = document.getElementById('notifyModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

// Close modal on outside click
document.addEventListener('click', function(e) {
    const modal = document.getElementById('notifyModal');
    if (e.target === modal) {
        hideNotifyModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        hideNotifyModal();
    }
});

async function submitNotifyForm(e) {
    e.preventDefault();

    const form = document.getElementById('notifyForm');
    const submitBtn = document.getElementById('notifySubmitBtn');
    const btnText = submitBtn.querySelector('.notify-btn-text');
    const btnLoading = submitBtn.querySelector('.notify-btn-loading');

    // Show loading state
    submitBtn.disabled = true;
    btnText.style.display = 'none';
    btnLoading.style.display = 'inline-flex';

    try {
        const formData = new FormData(form);

        const response = await fetch('/notify/subscribe', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success) {
            // Show success state
            const modalContent = document.querySelector('.notify-modal-content');
            modalContent.innerHTML = `
                <div class="notify-success">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    <h3>You're on the list!</h3>
                    <p>We'll email you as soon as this item is back in stock.</p>
                    <button type="button" class="notify-submit-btn" onclick="hideNotifyModal()">Got it</button>
                </div>
            `;
        } else {
            // Show error
            alert(data.message || 'Something went wrong. Please try again.');
            // Reset button
            submitBtn.disabled = false;
            btnText.style.display = 'inline';
            btnLoading.style.display = 'none';
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Something went wrong. Please try again.');
        // Reset button
        submitBtn.disabled = false;
        btnText.style.display = 'inline';
        btnLoading.style.display = 'none';
    }
}

// Check initial stock state for products without variants
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!$hasVariants && $initialStock <= 0): ?>
    // Product without variants is out of stock - show notify button
    updateNotifyButton(true, null, '');
    <?php endif; ?>

    // Load reviews
    loadReviews();
});

// ============ REVIEWS FUNCTIONALITY ============
// productId already declared above for favorites
let currentReviewPage = 1;
let hasMoreReviews = false;
let selectedRating = 0;

function loadReviews(page = 1) {
    fetch(`/reviews/product?product_id=${productId}&page=${page}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('loadingReviews').style.display = 'none';
            updateReviewStats(data.stats);
            updateCanReviewStatus(data.can_review);
            if (page === 1) {
                renderReviews(data.reviews);
            } else {
                appendReviews(data.reviews);
            }
            currentReviewPage = page;
            hasMoreReviews = data.has_more;
            document.getElementById('loadMoreContainer').style.display = hasMoreReviews ? 'block' : 'none';
        })
        .catch(error => {
            console.error('Error loading reviews:', error);
            document.getElementById('loadingReviews').textContent = 'Failed to load reviews.';
        });
}

function updateReviewStats(stats) {
    document.getElementById('averageRating').textContent = stats.average.toFixed(1);
    document.getElementById('totalReviews').textContent = stats.total + (stats.total === 1 ? ' review' : ' reviews');

    // Build stars using DOM
    const starsContainer = document.getElementById('averageStars');
    starsContainer.textContent = '';
    for (let i = 1; i <= 5; i++) {
        const star = document.createElement('span');
        star.className = i <= Math.round(stats.average) ? 'star filled' : 'star empty';
        star.textContent = i <= Math.round(stats.average) ? '\u2605' : '\u2606';
        starsContainer.appendChild(star);
    }

    // Rating distribution using DOM
    const distContainer = document.getElementById('ratingDistribution');
    distContainer.textContent = '';
    for (let i = 5; i >= 1; i--) {
        const count = stats.distribution[i] || 0;
        const percent = stats.total > 0 ? (count / stats.total * 100) : 0;

        const bar = document.createElement('div');
        bar.className = 'rating-bar';

        const label = document.createElement('span');
        label.className = 'rating-label';
        label.textContent = i + ' star';

        const barContainer = document.createElement('div');
        barContainer.className = 'bar-container';
        const barFill = document.createElement('div');
        barFill.className = 'bar-fill';
        barFill.style.width = percent + '%';
        barContainer.appendChild(barFill);

        const countSpan = document.createElement('span');
        countSpan.className = 'rating-count';
        countSpan.textContent = count;

        bar.appendChild(label);
        bar.appendChild(barContainer);
        bar.appendChild(countSpan);
        distContainer.appendChild(bar);
    }
}

function updateCanReviewStatus(canReview) {
    document.getElementById('reviewLoginPrompt').style.display = 'none';
    document.getElementById('reviewPurchasePrompt').style.display = 'none';
    document.getElementById('reviewAlreadySubmitted').style.display = 'none';
    document.getElementById('reviewFormContainer').style.display = 'none';

    if (!canReview.can_review) {
        if (canReview.reason === 'login_required') {
            document.getElementById('reviewLoginPrompt').style.display = 'block';
        } else if (canReview.reason === 'purchase_required') {
            document.getElementById('reviewPurchasePrompt').style.display = 'block';
        } else if (canReview.reason === 'already_reviewed') {
            document.getElementById('reviewAlreadySubmitted').style.display = 'block';
        }
    } else {
        document.getElementById('reviewFormContainer').style.display = 'block';
    }
}

function createReviewCard(review) {
    const card = document.createElement('div');
    card.className = 'review-card' + (review.is_featured ? ' featured' : '');

    // Header with rating and date
    const header = document.createElement('div');
    header.className = 'review-header';

    const ratingDiv = document.createElement('div');
    ratingDiv.className = 'review-rating';
    for (let i = 1; i <= 5; i++) {
        const star = document.createElement('span');
        star.className = i <= review.rating ? 'star filled' : 'star empty';
        star.textContent = i <= review.rating ? '\u2605' : '\u2606';
        ratingDiv.appendChild(star);
    }

    const dateSpan = document.createElement('span');
    dateSpan.className = 'review-date';
    dateSpan.textContent = new Date(review.created_at).toLocaleDateString('en-US', {
        year: 'numeric', month: 'short', day: 'numeric'
    });

    header.appendChild(ratingDiv);
    header.appendChild(dateSpan);
    card.appendChild(header);

    // Title (if exists)
    if (review.title) {
        const title = document.createElement('h4');
        title.className = 'review-title';
        title.textContent = review.title;
        card.appendChild(title);
    }

    // Review text (if exists)
    if (review.review_text) {
        const text = document.createElement('p');
        text.className = 'review-text';
        text.textContent = review.review_text;
        card.appendChild(text);
    }

    // Author info
    const author = document.createElement('div');
    author.className = 'review-author';

    const badge = document.createElement('span');
    badge.className = 'verified-badge';
    badge.textContent = 'Verified Purchase';

    const name = document.createElement('span');
    name.className = 'author-name';
    name.textContent = review.display_name;

    author.appendChild(badge);
    author.appendChild(name);
    card.appendChild(author);

    return card;
}

function renderReviews(reviews) {
    const container = document.getElementById('reviewsList');
    container.textContent = '';

    if (reviews.length === 0) {
        const noReviews = document.createElement('p');
        noReviews.className = 'no-reviews';
        noReviews.textContent = 'No reviews yet. Be the first to review this product!';
        container.appendChild(noReviews);
        return;
    }

    reviews.forEach(review => container.appendChild(createReviewCard(review)));
}

function appendReviews(reviews) {
    const container = document.getElementById('reviewsList');
    reviews.forEach(review => container.appendChild(createReviewCard(review)));
}

function loadMoreReviews() {
    loadReviews(currentReviewPage + 1);
}

function toggleReviewForm() {
    const form = document.getElementById('reviewForm');
    const btn = document.getElementById('writeReviewBtn');

    if (form.style.display === 'none') {
        form.style.display = 'block';
        btn.style.display = 'none';
    } else {
        form.style.display = 'none';
        btn.style.display = 'inline-block';
        resetReviewForm();
    }
}

function setRating(rating) {
    selectedRating = rating;
    document.getElementById('ratingInput').value = rating;

    const stars = document.querySelectorAll('#starRatingInput .star');
    stars.forEach((star, index) => {
        star.classList.toggle('selected', index < rating);
    });
}

function resetReviewForm() {
    document.getElementById('reviewForm').reset();
    selectedRating = 0;
    document.querySelectorAll('#starRatingInput .star').forEach(star => {
        star.classList.remove('selected');
    });
}

// Review form submission
document.getElementById('reviewForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();

    if (!selectedRating) {
        alert('Please select a rating.');
        return;
    }

    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';

    try {
        const response = await fetch('/reviews/submit', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            alert(data.message);
            toggleReviewForm();
            loadReviews();
        } else {
            alert(data.error || 'Failed to submit review.');
        }
    } catch (error) {
        console.error('Error submitting review:', error);
        alert('Failed to submit review. Please try again.');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Review';
    }
});

// ============ LIVE VIEWER COUNT ============
(function() {
    const viewingNow = document.getElementById('viewingNow');
    const viewerCount = document.getElementById('viewerCount');
    if (!viewingNow || !viewerCount) return;

    // Only show on ~40% of products (use product ID for consistency)
    const showChance = ((productId * 7) % 10) < 4;
    if (!showChance) return;

    // Modest numbers for small boutique feel (1-4 people)
    const baseMin = 1;
    const baseMax = 3;

    let currentCount = Math.floor(Math.random() * (baseMax - baseMin + 1)) + baseMin;

    function updateViewerCount() {
        // Fluctuate by -1, 0, or +1
        const change = Math.floor(Math.random() * 3) - 1;
        currentCount = Math.max(1, Math.min(4, currentCount + change));

        viewerCount.textContent = currentCount;

        // Update text grammar
        const text = viewerCount.parentElement;
        if (text) {
            text.innerHTML = '<strong id="viewerCount">' + currentCount + '</strong> ' +
                (currentCount === 1 ? 'person is' : 'people are') + ' viewing this right now';
        }
    }

    // Initial display after short delay
    setTimeout(() => {
        viewerCount.textContent = currentCount;
        viewingNow.style.display = 'flex';
    }, 2000);

    // Update every 20-45 seconds
    setInterval(() => {
        updateViewerCount();
    }, 20000 + Math.random() * 25000);
})();

// ============ RECENTLY VIEWED PRODUCTS ============
(function() {
    const STORAGE_KEY = 'lps_recently_viewed';
    const MAX_ITEMS = 8;

    // Current product data
    const currentProduct = {
        id: <?php echo $product['id']; ?>,
        name: <?php echo json_encode($product['name']); ?>,
        slug: <?php echo json_encode($product['slug']); ?>,
        price: <?php echo $product['sale_price'] ?: $product['price']; ?>,
        image: <?php echo json_encode($product['images'][0]['image_path'] ?? '/assets/images/placeholder.png'); ?>,
        timestamp: Date.now()
    };

    // Get stored items
    function getRecentlyViewed() {
        try {
            return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
        } catch (e) {
            return [];
        }
    }

    // Save to storage
    function saveRecentlyViewed(items) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
        } catch (e) {
            // Storage full or disabled
        }
    }

    // Add current product to recently viewed
    function trackCurrentProduct() {
        let items = getRecentlyViewed();

        // Remove if already exists (will re-add at front)
        items = items.filter(item => item.id !== currentProduct.id);

        // Add to front
        items.unshift(currentProduct);

        // Keep only MAX_ITEMS
        items = items.slice(0, MAX_ITEMS);

        saveRecentlyViewed(items);
    }

    // Display recently viewed (excluding current product)
    function displayRecentlyViewed() {
        const section = document.getElementById('recentlyViewedSection');
        const grid = document.getElementById('recentlyViewedGrid');
        if (!section || !grid) return;

        let items = getRecentlyViewed();

        // Exclude current product
        items = items.filter(item => item.id !== currentProduct.id);

        // Need at least 1 item to display
        if (items.length === 0) return;

        // Show max 4 items
        items = items.slice(0, 4);

        // Build product cards
        grid.innerHTML = items.map(item => `
            <div class="product-card">
                <div class="product-image">
                    <a href="/products/${encodeURIComponent(item.slug)}">
                        <img src="${item.image}" alt="${item.name}" loading="lazy" width="300" height="300">
                    </a>
                </div>
                <div class="product-info">
                    <h3><a href="/products/${encodeURIComponent(item.slug)}">${item.name}</a></h3>
                    <p class="product-price">$${parseFloat(item.price).toFixed(2)}</p>
                </div>
            </div>
        `).join('');

        section.style.display = 'block';
    }

    // Track and display
    trackCurrentProduct();
    displayRecentlyViewed();
})();
</script>
