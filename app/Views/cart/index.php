<!-- Shopping Cart Page -->
<section class="cart-section">
    <div class="container">
        <h1>Shopping Cart</h1>

        <?php if (!empty($items)): ?>
            <?php
            // Check if cart contains only digital products (no physical items to ship)
            $hasPhysicalItems = false;
            foreach ($items as $item) {
                if (empty($item['is_digital'])) {
                    $hasPhysicalItems = true;
                    break;
                }
            }
            ?>
            <div class="cart-container">
                <div class="cart-items">
                    <div class="cart-header">
                        <div class="col-name">Product</div>
                        <div class="col-price">Price</div>
                        <div class="col-qty">Quantity</div>
                        <div class="col-total">Total</div>
                        <div class="col-action"></div>
                    </div>

                    <?php foreach ($items as $item): ?>
                        <?php
                        $itemPrice = $item['sale_price'] ?? $item['price'];
                        if (!empty($item['price_adjustment'])) {
                            $itemPrice += $item['price_adjustment'];
                        }
                        $itemTotal = $itemPrice * $item['quantity'];
                        ?>
                        <div class="cart-item">
                            <div class="col-name">
                                <div class="cart-item-info">
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="<?php echo escape($item['image']); ?>"
                                             alt="<?php echo escape($item['name']); ?><?php echo !empty($item['variant_name']) ? ' - ' . escape($item['variant_name']) : ''; ?> | <?php echo appName(); ?>"
                                             title="<?php echo escape($item['name']); ?>"
                                             class="cart-item-image"
                                             loading="lazy"
                                             width="80"
                                             height="80">
                                    <?php endif; ?>
                                    <div class="cart-item-details">
                                        <a href="/products/<?php echo escape($item['slug']); ?>">
                                            <?php echo escape($item['name']); ?>
                                        </a>
                                        <?php if (!empty($item['variant_name'])): ?>
                                            <small class="variant-info"><?php echo escape($item['variant_name']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-price">
                                <?php echo formatPrice($itemPrice); ?>
                            </div>
                            <div class="col-qty">
                                <form class="qty-update-form" data-cart-item="<?php echo $item['id']; ?>">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="cart_item_id" value="<?php echo $item['id']; ?>">
                                    <div class="qty-inputs-small">
                                        <button type="button" class="qty-btn-small" onclick="decrementCartQty(this)">−</button>
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['inventory_count']; ?>" class="qty-input-small">
                                        <button type="button" class="qty-btn-small" onclick="incrementCartQty(this)">+</button>
                                    </div>
                                </form>
                            </div>
                            <div class="col-total">
                                <strong><?php echo formatPrice($itemTotal); ?></strong>
                            </div>
                            <div class="col-action">
                                <form action="/cart/remove" method="POST" class="remove-form" onsubmit="return confirm('Remove this item?');">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="cart_item_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn-remove">Remove</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-summary">
                    <div class="summary-box">
                        <h3>Order Summary</h3>

                        <?php if ($hasPhysicalItems): ?>
                        <?php
                        // Free shipping threshold
                        $freeShippingThreshold = 200.00;
                        $amountToFreeShipping = $freeShippingThreshold - $cartTotal;
                        $progressPercent = min(100, ($cartTotal / $freeShippingThreshold) * 100);

                        // Check if all items already ship free
                        $allItemsShipFree = true;
                        foreach ($items as $checkItem) {
                            if (empty($checkItem['ships_free']) && empty($checkItem['ships_free_us'])) {
                                $allItemsShipFree = false;
                                break;
                            }
                        }
                        ?>
                        <div class="free-shipping-progress">
                            <?php if ($allItemsShipFree): ?>
                                <div class="shipping-message success">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                                    </svg>
                                    <span><strong>Free shipping</strong> on your items!</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 100%;"></div>
                                </div>
                            <?php elseif ($amountToFreeShipping > 0): ?>
                                <div class="shipping-message">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
                                    </svg>
                                    <span>You're <strong><?php echo formatPrice($amountToFreeShipping); ?></strong> away from free shipping!</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $progressPercent; ?>%;"></div>
                                </div>
                                <div class="progress-labels">
                                    <span>$0</span>
                                    <span><?php echo formatPrice($freeShippingThreshold); ?></span>
                                </div>
                            <?php else: ?>
                                <div class="shipping-message success">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                                    </svg>
                                    <span>You've unlocked <strong>free shipping!</strong></span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 100%;"></div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="digital-delivery-notice">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            <span><strong>Digital Delivery</strong> - Instant download after purchase</span>
                        </div>
                        <?php endif; ?>

                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span><?php echo formatPrice($cartTotal); ?></span>
                        </div>

                        <?php if ($hasPhysicalItems): ?>
                        <div class="summary-row">
                            <span>Shipping:</span>
                            <span>Calculated at checkout</span>
                        </div>

                        <div class="summary-row">
                            <span>Tax:</span>
                            <span>Calculated at checkout</span>
                        </div>
                        <?php endif; ?>

                        <div class="summary-divider"></div>

                        <div class="summary-row total">
                            <span>Total:</span>
                            <span><?php echo formatPrice($cartTotal); ?></span>
                        </div>

                        <a href="/checkout" class="btn btn-primary btn-large" style="margin-top: 1.5rem;">
                            Proceed to Checkout
                        </a>

                        <a href="/products" class="btn btn-secondary btn-large" style="margin-top: 0.75rem;">
                            Continue Shopping
                        </a>

                        <!-- Trust Badges -->
                        <div class="cart-trust-badges">
                            <div class="trust-badge-row">
                                <div class="trust-shield">
                                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none">
                                        <path d="M12 2L4 6v6c0 5.5 3.4 10.6 8 12 4.6-1.4 8-6.5 8-12V6l-8-4z" fill="#22c55e"/>
                                        <path d="M9 12l2 2 4-4" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <div>
                                        <strong>Secure Checkout</strong>
                                        <span>256-bit SSL encryption</span>
                                    </div>
                                </div>
                            </div>
                            <div class="trust-icons-row">
                                <img src="https://www.paypalobjects.com/webstatic/en_US/i/buttons/cc-badges-ppmcvdam.png" alt="PayPal and Credit Cards accepted" height="35">
                            </div>
                            <div class="trust-mini-badges">
                                <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> Safe & Secure</span>
                                <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg> 100% Guarantee</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <div class="empty-cart">
                <p>Your cart is empty</p>
                <a href="/products" class="btn btn-primary">Start Shopping</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
function incrementCartQty(btn) {
    const input = btn.parentElement.querySelector('.qty-input-small');
    const max = parseInt(input.getAttribute('max'));
    if (parseInt(input.value) < max) {
        input.value = parseInt(input.value) + 1;
        updateCartItem(input.form);
    }
}

function decrementCartQty(btn) {
    const input = btn.parentElement.querySelector('.qty-input-small');
    if (parseInt(input.value) > 1) {
        input.value = parseInt(input.value) - 1;
        updateCartItem(input.form);
    }
}

function updateCartItem(form) {
    const formData = new FormData(form);

    fetch('/cart/update', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload page to show updated cart
            location.reload();
        } else {
            alert('Error updating cart: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update cart');
    });
}
</script>
