<!-- Favorites Page -->
<section class="favorites-section">
    <div class="container">
        <h1>My Favorites</h1>

        <?php if (empty($favorites)): ?>
            <div class="empty-favorites">
                <div class="empty-icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                </div>
                <h2>No favorites yet</h2>
                <p>Browse our products and click the heart icon to save your favorites!</p>
                <a href="/products" class="btn btn-primary">Shop Now</a>
            </div>
        <?php else: ?>
            <p class="favorites-count"><?php echo count($favorites); ?> item<?php echo count($favorites) !== 1 ? 's' : ''; ?></p>

            <?php if (!auth()): ?>
            <div class="favorites-account-prompt">
                <p><strong>Want to keep your favorites forever?</strong> Create an account to save them across devices and sessions.</p>
                <a href="/register" class="btn btn-outline">Create Account</a>
                <a href="/login" class="btn btn-link">Already have an account? Log in</a>
            </div>
            <?php endif; ?>

            <div class="products-grid">
                <?php foreach ($favorites as $product): ?>
                    <div class="product-card" data-product-id="<?php echo $product['product_id']; ?>">
                        <div class="product-image">
                            <a href="/products/<?php echo escape($product['slug']); ?>">
                                <img src="<?php echo escape($product['primary_image'] ?? '/assets/images/placeholder.png'); ?>"
                                     alt="<?php echo escape($product['name']); ?> - Saved Favorite | <?php echo appName(); ?>"
                                     title="<?php echo escape($product['name']); ?>"
                                     loading="lazy"
                                     width="300"
                                     height="300">
                            </a>
                            <button type="button" class="favorite-btn active" onclick="toggleFavorite(<?php echo $product['product_id']; ?>, this)" aria-label="Remove from favorites">
                                <svg class="heart-icon" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                                </svg>
                            </button>
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
        <?php endif; ?>
    </div>
</section>

<style>
.favorites-section {
    padding: 40px 0 60px;
}

.favorites-section h1 {
    margin-bottom: 10px;
}

.favorites-count {
    color: #666;
    margin-bottom: 30px;
}

.empty-favorites {
    text-align: center;
    padding: 60px 20px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}

.empty-icon {
    margin-bottom: 20px;
}

.empty-favorites h2 {
    margin-bottom: 10px;
    color: #333;
}

.empty-favorites p {
    color: #666;
    margin-bottom: 24px;
}
</style>

<script>
async function toggleFavorite(productId, btn) {
    try {
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('_csrf_token', '<?php echo csrfToken(); ?>');

        const response = await fetch('/favorites/toggle', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success) {
            if (!data.favorited) {
                // Remove the product card from the page
                const card = btn.closest('.product-card');
                card.style.transition = 'opacity 0.3s, transform 0.3s';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    card.remove();
                    // Check if no favorites left
                    if (document.querySelectorAll('.product-card').length === 0) {
                        location.reload();
                    } else {
                        // Update count
                        const countEl = document.querySelector('.favorites-count');
                        if (countEl) {
                            const count = document.querySelectorAll('.product-card').length;
                            countEl.textContent = count + ' item' + (count !== 1 ? 's' : '');
                        }
                    }
                }, 300);
            }
        }
    } catch (error) {
        console.error('Error toggling favorite:', error);
    }
}
</script>
