<!-- Category Page -->
<section class="products-section">
    <div class="container">
        <div class="shop-layout">
            <!-- Sidebar -->
            <?php include __DIR__ . '/../partials/shop-sidebar.php'; ?>

            <!-- Main Content -->
            <div class="shop-main">
                <?php if (!empty($category['banner_url'])): ?>
                <a href="<?php echo escape($category['banner_url']); ?>" target="_blank" class="category-banner" style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;background:linear-gradient(135deg,#0d0d1a 0%,#1a1a2e 100%);border-radius:10px;margin-bottom:1.25rem;text-decoration:none;color:#fff;transition:box-shadow 0.2s;">
                    <span style="font-size:0.9rem;font-weight:500;"><?php echo escape($category['banner_text'] ?? 'View Gallery'); ?></span>
                    <span style="background:rgba(255,104,197,0.15);color:#FF68C5;padding:6px 14px;border-radius:20px;font-size:0.8rem;font-weight:600;">Visit Gallery &rarr;</span>
                </a>
                <?php endif; ?>
                <div class="section-header">
                    <div class="header-left">
                        <h1><?php echo escape($category['name']); ?></h1>
                        <?php if (!empty($category['description'])): ?>
                            <p class="category-desc"><?php echo escape($category['description']); ?></p>
                        <?php endif; ?>
                        <p class="product-count"><?php echo $totalProducts; ?> products</p>
                    </div>
                    <?php if (empty($subcategories)): ?>
                    <div class="header-right">
                        <label for="sortOrder">Sort by:</label>
                        <select id="sortOrder" class="sort-select" onchange="sortProducts(this.value)">
                            <option value="default" <?php echo ($sort ?? 'default') === 'default' ? 'selected' : ''; ?>>Featured</option>
                            <option value="newest" <?php echo ($sort ?? '') === 'newest' ? 'selected' : ''; ?>>Newest</option>
                            <option value="price-low" <?php echo ($sort ?? '') === 'price-low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price-high" <?php echo ($sort ?? '') === 'price-high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="name-az" <?php echo ($sort ?? '') === 'name-az' ? 'selected' : ''; ?>>Name: A-Z</option>
                            <option value="name-za" <?php echo ($sort ?? '') === 'name-za' ? 'selected' : ''; ?>>Name: Z-A</option>
                            <option value="custom-date" <?php echo ($sort ?? '') === 'custom-date' ? 'selected' : ''; ?>>Order By Date</option>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($subcategories)): ?>
                    <!-- Subcategory Grid -->
                    <div class="subcategory-grid">
                        <?php foreach ($subcategories as $subcat): ?>
                            <a href="/category/<?php echo escape($subcat['slug']); ?>" class="subcategory-card">
                                <div class="subcategory-image">
                                    <?php if (!empty($subcat['image'])): ?>
                                        <img src="<?php echo escape($subcat['image']); ?>"
                                             alt="<?php echo escape($subcat['name']); ?> - Shop <?php echo escape($category['name']); ?> | <?php echo appName(); ?>"
                                             title="<?php echo escape($subcat['name']); ?>"
                                             loading="lazy"
                                             width="280"
                                             height="210">
                                    <?php else: ?>
                                        <div class="subcategory-placeholder">
                                            <span><?php echo escape(substr($subcat['name'], 0, 1)); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="subcategory-info">
                                    <h3><?php echo escape($subcat['name']); ?></h3>
                                    <span class="subcategory-count"><?php echo (int)$subcat['product_count']; ?> products</span>
                                    <span class="shop-now-btn">Shop Now</span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <style>
                    .subcategory-grid {
                        display: grid;
                        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                        gap: 1.5rem;
                        margin-bottom: 2rem;
                    }
                    .subcategory-card {
                        display: block;
                        background: #fff;
                        border-radius: 12px;
                        overflow: hidden;
                        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
                        text-decoration: none;
                        transition: transform 0.2s, box-shadow 0.2s;
                    }
                    .subcategory-card:hover {
                        transform: translateY(-4px);
                        box-shadow: 0 8px 25px rgba(255, 104, 197, 0.2);
                    }
                    .subcategory-image {
                        aspect-ratio: 4/3;
                        overflow: hidden;
                    }
                    .subcategory-image img {
                        width: 100%;
                        height: 100%;
                        object-fit: cover;
                        transition: transform 0.3s;
                    }
                    .subcategory-card:hover .subcategory-image img {
                        transform: scale(1.05);
                    }
                    .subcategory-placeholder {
                        width: 100%;
                        height: 100%;
                        background: linear-gradient(135deg, #FFE4F3 0%, #fff0f7 100%);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                    .subcategory-placeholder span {
                        font-size: 4rem;
                        font-weight: 600;
                        color: #FF68C5;
                        opacity: 0.5;
                    }
                    .subcategory-info {
                        padding: 1.25rem;
                        text-align: center;
                    }
                    .subcategory-info h3 {
                        margin: 0 0 0.5rem;
                        font-size: 1.25rem;
                        color: #333;
                    }
                    .subcategory-count {
                        display: block;
                        color: #888;
                        font-size: 0.9rem;
                        margin-bottom: 1rem;
                    }
                    .shop-now-btn {
                        display: inline-block;
                        padding: 0.6rem 1.5rem;
                        background: linear-gradient(135deg, #FF68C5 0%, #ff4db8 100%);
                        color: #fff;
                        border-radius: 25px;
                        font-weight: 500;
                        font-size: 0.9rem;
                        transition: background 0.2s, transform 0.2s;
                    }
                    .subcategory-card:hover .shop-now-btn {
                        background: linear-gradient(135deg, #ff4db8 0%, #FF68C5 100%);
                    }
                    @media (max-width: 600px) {
                        .subcategory-grid {
                            grid-template-columns: 1fr 1fr;
                            gap: 1rem;
                        }
                        .subcategory-info h3 {
                            font-size: 1rem;
                        }
                        .shop-now-btn {
                            padding: 0.5rem 1rem;
                            font-size: 0.8rem;
                        }
                    }
                    </style>
                <?php endif; ?>
                <?php if (!empty($products)): ?>
                    <div class="products-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="product-card" <?php if (!empty($product['video_path'])): ?>data-has-video="true"<?php endif; ?>>
                                <div class="product-image">
                                    <a href="/products/<?php echo escape($product['slug']); ?>">
                                        <img src="<?php echo escape($product['primary_image'] ?? '/assets/images/placeholder.png'); ?>"
                                             alt="<?php echo escape($product['name']); ?> - <?php echo escape($category['name']); ?> | <?php echo appName(); ?>"
                                             title="<?php echo escape($product['name']); ?>"
                                             loading="lazy"
                                             width="300"
                                             height="300">
                                        <?php if (!empty($product['video_path'])): ?>
                                            <video class="product-video" src="<?php echo escape($product['video_path']); ?>" muted loop playsinline preload="none"></video>
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
                                    <a href="/products/<?php echo escape($product['slug']); ?>" class="btn btn-add-cart">View Product</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php if ($currentPage > 1): ?>
                                <a href="/category/<?php echo escape($category['slug']); ?>?page=1" class="page-link">First</a>
                                <a href="/category/<?php echo escape($category['slug']); ?>?page=<?php echo $currentPage - 1; ?>" class="page-link">← Previous</a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                                <?php if ($i === $currentPage): ?>
                                    <span class="page-current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="/category/<?php echo escape($category['slug']); ?>?page=<?php echo $i; ?>" class="page-link"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($currentPage < $totalPages): ?>
                                <a href="/category/<?php echo escape($category['slug']); ?>?page=<?php echo $currentPage + 1; ?>" class="page-link">Next →</a>
                                <a href="/category/<?php echo escape($category['slug']); ?>?page=<?php echo $totalPages; ?>" class="page-link">Last</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="no-products">
                        <p>No products found in this category.</p>
                    </div>
                <?php endif; ?>
            </div><!-- /.shop-main -->
        </div><!-- /.shop-layout -->
    </div>
</section>

<script>
function sortProducts(sortValue) {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', sortValue);
    url.searchParams.delete('page');
    window.location.href = url.toString();
}
</script>
