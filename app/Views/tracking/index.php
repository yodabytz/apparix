<div class="container" style="padding: 3rem 1rem; max-width: 800px; margin: 0 auto;">
    <h1 style="text-align: center; font-family: 'Playfair Display', serif; margin-bottom: 2rem;">Track Your Order</h1>

    <?php if (!$order): ?>
    <!-- Search Form -->
    <div class="card" style="background: #fff; border-radius: 16px; padding: 2rem; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
        <?php if (isset($error)): ?>
        <div class="alert alert-error" style="margin-bottom: 1.5rem;"><?php echo escape($error); ?></div>
        <?php endif; ?>

        <p style="text-align: center; color: #6b7280; margin-bottom: 1.5rem;">
            Enter your order number and email address to track your order.
        </p>

        <form method="POST" action="/track">
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 500; margin-bottom: 0.5rem;">Order Number</label>
                <input type="text" name="order_number" value="<?php echo escape($orderNumber ?? ''); ?>" placeholder="e.g., LPS-12345678"
                       style="width: 100%; padding: 0.875rem 1rem; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 1rem;" required>
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 500; margin-bottom: 0.5rem;">Email Address</label>
                <input type="email" name="email" value="<?php echo escape($email ?? ''); ?>" placeholder="The email used for your order"
                       style="width: 100%; padding: 0.875rem 1rem; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 1rem;" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1rem;">
                Track Order
            </button>
        </form>
    </div>

    <?php else: ?>
    <!-- Order Details -->
    <div class="card" style="background: #fff; border-radius: 16px; padding: 2rem; box-shadow: 0 4px 20px rgba(0,0,0,0.08); margin-bottom: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
            <div>
                <h2 style="margin: 0 0 0.5rem 0; font-size: 1.25rem;">Order #<?php echo escape($order['order_number']); ?></h2>
                <p style="margin: 0; color: #6b7280;">Placed on <?php echo date('F j, Y', strtotime($order['created_at'])); ?></p>
            </div>
            <div>
                <?php
                $statusColors = [
                    'pending' => '#f59e0b',
                    'processing' => '#3b82f6',
                    'shipped' => '#8b5cf6',
                    'delivered' => '#10b981',
                    'cancelled' => '#ef4444',
                    'refunded' => '#6b7280'
                ];
                $color = $statusColors[$order['status']] ?? '#6b7280';
                ?>
                <span style="display: inline-block; padding: 0.5rem 1rem; background: <?php echo $color; ?>20; color: <?php echo $color; ?>; border-radius: 9999px; font-weight: 600; text-transform: capitalize;">
                    <?php echo escape($order['status']); ?>
                </span>
            </div>
        </div>

        <!-- Status Timeline -->
        <div style="position: relative; padding-left: 30px; margin: 2rem 0;">
            <?php
            $statuses = ['pending' => 'Order Placed', 'processing' => 'Processing', 'shipped' => 'Shipped', 'delivered' => 'Delivered'];
            $currentIndex = array_search($order['status'], array_keys($statuses));
            $index = 0;
            foreach ($statuses as $status => $label):
                $isActive = $index <= $currentIndex && $order['status'] !== 'cancelled' && $order['status'] !== 'refunded';
                $isCurrent = $status === $order['status'];
            ?>
            <div style="position: relative; padding-bottom: <?php echo $index < 3 ? '25px' : '0'; ?>;">
                <!-- Line -->
                <?php if ($index < 3): ?>
                <div style="position: absolute; left: -21px; top: 20px; width: 2px; height: calc(100% - 10px); background: <?php echo $isActive ? '#FF68C5' : '#e5e7eb'; ?>;"></div>
                <?php endif; ?>
                <!-- Dot -->
                <div style="position: absolute; left: -30px; top: 0; width: 20px; height: 20px; border-radius: 50%; background: <?php echo $isActive ? '#FF68C5' : '#e5e7eb'; ?>; border: 3px solid #fff; box-shadow: 0 0 0 2px <?php echo $isActive ? '#FF68C5' : '#e5e7eb'; ?>;"></div>
                <!-- Content -->
                <div style="padding-left: 10px;">
                    <p style="margin: 0; font-weight: <?php echo $isCurrent ? '600' : '400'; ?>; color: <?php echo $isActive ? '#1f2937' : '#9ca3af'; ?>;">
                        <?php echo $label; ?>
                    </p>
                    <?php if ($isCurrent && !empty($order['history'])): ?>
                    <p style="margin: 5px 0 0 0; font-size: 0.875rem; color: #6b7280;">
                        <?php
                        $latestHistory = end($order['history']);
                        echo date('M j, g:ia', strtotime($latestHistory['created_at']));
                        ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            <?php $index++; endforeach; ?>
        </div>

        <!-- Tracking Info -->
        <?php if ($order['tracking_number']): ?>
        <div style="background: #f0fdf4; border-radius: 12px; padding: 1.25rem; margin-top: 1.5rem;">
            <p style="margin: 0 0 0.5rem 0; font-weight: 600; color: #166534;">
                Tracking Number: <?php echo escape($order['tracking_number']); ?>
            </p>
            <?php if ($order['shipping_carrier']): ?>
            <p style="margin: 0; color: #166534;">
                Carrier: <?php echo escape($order['shipping_carrier']); ?>
                <?php if ($order['tracking_url']): ?>
                    - <a href="<?php echo escape($order['tracking_url']); ?>" target="_blank" style="color: #FF68C5; text-decoration: underline;">Track Package</a>
                <?php endif; ?>
            </p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Order Items -->
    <div class="card" style="background: #fff; border-radius: 16px; padding: 2rem; box-shadow: 0 4px 20px rgba(0,0,0,0.08); margin-bottom: 1.5rem;">
        <h3 style="margin: 0 0 1.5rem 0; font-size: 1.125rem;">Order Items</h3>

        <?php foreach ($order['items'] as $item): ?>
        <div style="display: flex; gap: 1rem; padding: 1rem 0; border-bottom: 1px solid #f3f4f6;">
            <img src="<?php echo escape($item['image'] ?? '/assets/images/placeholder.png'); ?>"
                 alt="<?php echo escape($item['name']); ?>"
                 style="width: 70px; height: 70px; object-fit: cover; border-radius: 8px;">
            <div style="flex: 1;">
                <p style="margin: 0 0 0.25rem 0; font-weight: 500;"><?php echo escape($item['name']); ?></p>
                <?php if ($item['variant_name']): ?>
                <p style="margin: 0 0 0.25rem 0; font-size: 0.875rem; color: #6b7280;"><?php echo escape($item['variant_name']); ?></p>
                <?php endif; ?>
                <p style="margin: 0; font-size: 0.875rem; color: #6b7280;">Qty: <?php echo $item['quantity']; ?></p>
            </div>
            <div style="text-align: right;">
                <p style="margin: 0; font-weight: 600;">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Totals -->
        <div style="padding-top: 1rem; margin-top: 0.5rem;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                <span style="color: #6b7280;">Subtotal</span>
                <span>$<?php echo number_format($order['subtotal'], 2); ?></span>
            </div>
            <?php if ($order['discount'] > 0): ?>
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                <span style="color: #6b7280;">Discount</span>
                <span style="color: #10b981;">-$<?php echo number_format($order['discount'], 2); ?></span>
            </div>
            <?php endif; ?>
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                <span style="color: #6b7280;">Shipping</span>
                <span><?php echo $order['shipping_cost'] > 0 ? '$' . number_format($order['shipping_cost'], 2) : 'Free'; ?></span>
            </div>
            <?php if ($order['tax'] > 0): ?>
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                <span style="color: #6b7280;">Tax</span>
                <span>$<?php echo number_format($order['tax'], 2); ?></span>
            </div>
            <?php endif; ?>
            <div style="display: flex; justify-content: space-between; padding-top: 0.75rem; border-top: 1px solid #e5e7eb; font-weight: 600; font-size: 1.125rem;">
                <span>Total</span>
                <span>$<?php echo number_format($order['total'], 2); ?></span>
            </div>
        </div>
    </div>

    <!-- Shipping Address -->
    <div class="card" style="background: #fff; border-radius: 16px; padding: 2rem; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
        <h3 style="margin: 0 0 1rem 0; font-size: 1.125rem;">Shipping Address</h3>
        <p style="margin: 0; color: #4b5563; line-height: 1.6;">
            <?php echo escape($order['shipping_name']); ?><br>
            <?php echo escape($order['shipping_address']); ?><br>
            <?php if ($order['shipping_address2']): ?>
                <?php echo escape($order['shipping_address2']); ?><br>
            <?php endif; ?>
            <?php echo escape($order['shipping_city']); ?>, <?php echo escape($order['shipping_state']); ?> <?php echo escape($order['shipping_zip']); ?><br>
            <?php echo escape($order['shipping_country']); ?>
        </p>
    </div>

    <div style="text-align: center; margin-top: 2rem;">
        <a href="/track" style="color: #FF68C5;">Track a different order</a>
    </div>
    <?php endif; ?>

    <div style="text-align: center; margin-top: 2rem;">
        <p style="color: #6b7280; font-size: 0.875rem;">
            Need help? <a href="/contact" style="color: #FF68C5;">Contact us</a>
        </p>
    </div>
</div>
