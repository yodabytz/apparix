<!-- Products Listing Page -->
<section class="products-section">
    <div class="container">
        <div class="shop-layout">
            <!-- Sidebar -->
            <aside class="shop-sidebar">
                <div class="sidebar-section">
                    <h3>Categories</h3>
                    <ul class="category-list">
                        <li>
                            <a href="/products" class="<?php echo !isset($currentCategory) ? 'active' : ''; ?>">
                                All Products
                            </a>
                        </li>
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <li class="parent-category">
                                    <a href="/category/<?php echo escape($category['slug']); ?>"
                                       class="<?php echo (isset($currentCategory) && $currentCategory === $category['slug']) ? 'active' : ''; ?>">
                                        <?php echo escape($category['name']); ?>
                                        <span class="count"><?php echo (int)$category['product_count']; ?></span>
                                    </a>
                                    <?php if (!empty($category['children'])): ?>
                                        <ul class="subcategory-list">
                                            <?php foreach ($category['children'] as $child): ?>
                                                <li class="<?php echo !empty($child['children']) ? 'has-children' : ''; ?>">
                                                    <a href="/category/<?php echo escape($child['slug']); ?>"
                                                       class="<?php echo (isset($currentCategory) && $currentCategory === $child['slug']) ? 'active' : ''; ?>">
                                                        <?php echo escape($child['name']); ?>
                                                        <span class="count"><?php echo (int)$child['product_count']; ?></span>
                                                    </a>
                                                    <?php if (!empty($child['children'])): ?>
                                                        <ul class="grandchild-list">
                                                            <?php foreach ($child['children'] as $grandchild): ?>
                                                                <li>
                                                                    <a href="/category/<?php echo escape($grandchild['slug']); ?>"
                                                                       class="<?php echo (isset($currentCategory) && $currentCategory === $grandchild['slug']) ? 'active' : ''; ?>">
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
                        <h1><?php echo escape($title); ?></h1>
                        <p class="product-count"><?php echo $totalProducts; ?> products</p>
                    </div>
                    <div class="header-right">
                        <label for="sortOrder">Sort by:</label>
                        <select id="sortOrder" class="sort-select" onchange="sortProducts(this.value)">
                            <option value="default" <?php echo ($sort ?? 'default') === 'default' ? 'selected' : ''; ?>>Featured</option>
                            <option value="newest" <?php echo ($sort ?? '') === 'newest' ? 'selected' : ''; ?>>Newest</option>
                            <option value="price-low" <?php echo ($sort ?? '') === 'price-low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price-high" <?php echo ($sort ?? '') === 'price-high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="name-az" <?php echo ($sort ?? '') === 'name-az' ? 'selected' : ''; ?>>Name: A-Z</option>
                            <option value="name-za" <?php echo ($sort ?? '') === 'name-za' ? 'selected' : ''; ?>>Name: Z-A</option>
                        </select>
                    </div>
                </div>

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
                            <?php if ($product['sale_price']): ?>
                                <span class="sale-badge">Sale</span>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3><a href="/products/<?php echo escape($product['slug']); ?>"><?php echo escape($product['name']); ?></a></h3>

                            <p class="product-price">
                                <?php
                                $priceMin = $product['price_min'] ?? $product['price'];
                                $priceMax = $product['price_max'] ?? $product['price'];
                                $showRange = $priceMin && $priceMax && $priceMin != $priceMax;
                                ?>
                                <?php if ($showRange): ?>
                                    <span class="price-range"><?php echo formatPrice($priceMin); ?> - <?php echo formatPrice($priceMax); ?></span>
                                <?php elseif ($product['sale_price']): ?>
                                    <span class="original-price"><del><?php echo formatPrice($product['price']); ?></del></span>
                                    <span class="sale-price"><?php echo formatPrice($product['sale_price']); ?></span>
                                <?php else: ?>
                                    <?php echo formatPrice($product['price']); ?>
                                <?php endif; ?>
                            </p>

                            <p class="inventory-status">
                                <?php
                                // Use variant stock if product has variants, otherwise use base inventory
                                $stock = (isset($product['variant_count']) && $product['variant_count'] > 0)
                                    ? (int)$product['variant_stock']
                                    : (int)$product['inventory_count'];
                                ?>
                                <?php if ($stock > 0): ?>
                                    <span class="in-stock">In Stock</span>
                                <?php else: ?>
                                    <span class="out-of-stock">Out of Stock</span>
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
                        <a href="/products?page=1" class="page-link">First</a>
                        <a href="/products?page=<?php echo $currentPage - 1; ?>" class="page-link">← Previous</a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                        <?php if ($i === $currentPage): ?>
                            <span class="page-current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="/products?page=<?php echo $i; ?>" class="page-link"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <a href="/products?page=<?php echo $currentPage + 1; ?>" class="page-link">Next →</a>
                        <a href="/products?page=<?php echo $totalPages; ?>" class="page-link">Last</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

                <?php else: ?>
                    <div class="no-products">
                        <p>No products found. Please try again later.</p>
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
    url.searchParams.delete('page'); // Reset to page 1 when sorting
    window.location.href = url.toString();
}
</script>
