<!-- Search Results Page -->
<section class="products-section">
    <div class="container">
        <div class="shop-layout">
            <!-- Sidebar -->
            <aside class="shop-sidebar">
                <div class="sidebar-section">
                    <h3>Categories</h3>
                    <ul class="category-list">
                        <li>
                            <a href="/products">All Products</a>
                        </li>
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <li class="parent-category">
                                    <a href="/category/<?php echo escape($category['slug']); ?>">
                                        <?php echo escape($category['name']); ?>
                                        <span class="count"><?php echo (int)$category['product_count']; ?></span>
                                    </a>
                                    <?php if (!empty($category['children'])): ?>
                                        <ul class="subcategory-list">
                                            <?php foreach ($category['children'] as $child): ?>
                                                <li class="<?php echo !empty($child['children']) ? 'has-children' : ''; ?>">
                                                    <a href="/category/<?php echo escape($child['slug']); ?>">
                                                        <?php echo escape($child['name']); ?>
                                                        <span class="count"><?php echo (int)$child['product_count']; ?></span>
                                                    </a>
                                                    <?php if (!empty($child['children'])): ?>
                                                        <ul class="grandchild-list">
                                                            <?php foreach ($child['children'] as $grandchild): ?>
                                                                <li>
                                                                    <a href="/category/<?php echo escape($grandchild['slug']); ?>">
                                                                        <?php echo escape($grandchild['name']); ?>
                                                                        <span class="count"><?php echo (int)$grandchild['product_count']; ?></span>
                                                                    </a>
                                                                </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </aside>

            <!-- Main Content -->
            <div class="shop-main">
                <div class="section-header">
                    <div class="header-left">
                        <h1>Search Results</h1>
                        <?php if (!empty($query)): ?>
                            <p class="search-query">Results for "<strong><?php echo escape($query); ?></strong>"</p>
                        <?php endif; ?>
                        <p class="product-count"><?php echo $productCount; ?> product<?php echo $productCount !== 1 ? 's' : ''; ?> found</p>
                    </div>
                    <div class="header-right">
                        <!-- Search box -->
                        <form action="/search" method="GET" class="header-search-form">
                            <input type="text" name="q" value="<?php echo escape($query); ?>" placeholder="Search products..." class="search-input">
                            <button type="submit" class="search-btn">Search</button>
                        </form>
                    </div>
                </div>

                <?php if (!empty($message)): ?>
                    <div class="search-message">
                        <p><?php echo escape($message); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($products)): ?>
                    <div class="products-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="product-card" <?php if (!empty($product['video_path'])): ?>data-has-video="true"<?php endif; ?>>
                                <div class="product-image">
                                    <a href="/products/<?php echo escape($product['slug']); ?>">
                                        <img src="<?php echo escape($product['primary_image'] ?? '/assets/images/placeholder.png'); ?>"
                                             alt="<?php echo escape($product['name']); ?><?php echo !empty($product['category_name']) ? ' - ' . escape($product['category_name']) : ''; ?> | <?php echo appName(); ?>"
                                             title="<?php echo escape($product['name']); ?>"
                                             loading="lazy"
                                             width="300"
                                             height="300">
                                        <?php if (!empty($product['video_path'])): ?>
                                            <video class="product-video" src="<?php echo escape($product['video_path']); ?>" muted loop playsinline preload="metadata"></video>
                                            <span class="video-indicator">&#9658;</span>
                                        <?php endif; ?>
                                    </a>
                                    <?php if (!empty($product['sale_price'])): ?>
                                        <span class="sale-badge">Sale</span>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <h3><a href="/products/<?php echo escape($product['slug']); ?>"><?php echo escape($product['name']); ?></a></h3>

                                    <p class="product-price">
                                        <?php if (!empty($product['sale_price'])): ?>
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
                <?php else: ?>
                    <div class="no-products search-no-results">
                        <?php if (!empty($query)): ?>
                            <div class="no-results-icon">🔍</div>
                            <h2>No products found</h2>
                            <p>We couldn't find any products matching "<strong><?php echo escape($query); ?></strong>".</p>
                            <p>Try different keywords or browse our categories.</p>
                        <?php else: ?>
                            <div class="no-results-icon">🔍</div>
                            <h2>Search for products</h2>
                            <p>Enter a search term above to find products.</p>
                        <?php endif; ?>
                        <a href="/products" class="btn btn-primary">Browse All Products</a>
                    </div>
                <?php endif; ?>
            </div><!-- /.shop-main -->
        </div><!-- /.shop-layout -->
    </div>
</section>
