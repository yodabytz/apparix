<!-- Order Confirmation Page -->
<section class="confirmation-section">
    <div class="container">
        <div class="confirmation-content">
            <div class="confirmation-icon">&#10003;</div>
            <h1>Thank You for Your Order!</h1>
            <p class="order-number">Order Number: <strong><?php echo escape($order['order_number']); ?></strong></p>
            <p class="confirmation-text">A confirmation email has been sent to <strong><?php echo escape($order['customer_email']); ?></strong></p>

            <div class="confirmation-details">
                <div class="detail-card">
                    <h3>Order Summary</h3>
                    <div class="order-items-list">
                        <?php foreach ($orderItems as $item): ?>
                            <div class="order-item-row">
                                <span class="item-name">
                                    <?php echo escape($item['product_name']); ?>
                                    <span class="item-qty">× <?php echo $item['quantity']; ?></span>
                                </span>
                                <span class="item-price"><?php echo formatPrice($item['total']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="order-totals">
                        <div class="total-row">
                            <span>Subtotal</span>
                            <span><?php echo formatPrice($order['subtotal']); ?></span>
                        </div>
                        <?php if ($order['shipping_cost'] > 0): ?>
                            <div class="total-row">
                                <span>Shipping</span>
                                <span><?php echo formatPrice($order['shipping_cost']); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($order['tax'] > 0): ?>
                            <div class="total-row">
                                <span>Tax</span>
                                <span><?php echo formatPrice($order['tax']); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="total-row grand-total">
                            <span>Total</span>
                            <span><?php echo formatPrice($order['total']); ?></span>
                        </div>
                    </div>
                </div>

                <div class="detail-card">
                    <h3>Shipping Address</h3>
                    <address>
                        <?php echo escape($order['ship_first_name'] . ' ' . $order['ship_last_name']); ?><br>
                        <?php echo escape($order['ship_address1']); ?><br>
                        <?php if (!empty($order['ship_address2'])): ?>
                            <?php echo escape($order['ship_address2']); ?><br>
                        <?php endif; ?>
                        <?php echo escape($order['ship_city'] . ', ' . $order['ship_state'] . ' ' . $order['ship_postal']); ?><br>
                        <?php echo escape($order['ship_country']); ?>
                    </address>
                </div>

                <?php if (!empty($licenses)): ?>
                <div class="detail-card license-card" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1px solid #86efac;">
                    <h3 style="color: #166534; display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-size: 1.25rem;">&#128273;</span> Your License Key<?php echo count($licenses) > 1 ? 's' : ''; ?>
                    </h3>
                    <p style="font-size: 0.875rem; color: #15803d; margin-bottom: 1rem;">
                        Save your license key<?php echo count($licenses) > 1 ? 's' : ''; ?> - you'll need <?php echo count($licenses) > 1 ? 'them' : 'it'; ?> to activate your software.
                    </p>
                    <?php foreach ($licenses as $license): ?>
                    <div style="margin-bottom: 1rem; padding: 1rem; background: #fff; border-radius: 8px; border: 1px solid #bbf7d0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <strong style="color: #166534;"><?php echo escape($license['product_name']); ?></strong>
                            <span style="background: #166534; color: #fff; padding: 0.125rem 0.5rem; border-radius: 20px; font-size: 0.7rem;">
                                <?php echo \App\Models\OrderLicense::getEditionName($license['edition_code']); ?>
                            </span>
                        </div>
                        <div style="background: #1e293b; color: #22c55e; font-family: 'Courier New', monospace; padding: 0.875rem; border-radius: 6px; font-size: 0.9375rem; word-break: break-all; user-select: all; letter-spacing: 0.05em;">
                            <?php echo escape($license['license_key']); ?>
                        </div>
                        <p style="margin: 0.75rem 0 0; font-size: 0.8125rem; color: #64748b;">
                            Add to your <code style="background: #e2e8f0; padding: 0.125rem 0.375rem; border-radius: 4px;">.env</code> file as: <code style="background: #e2e8f0; padding: 0.125rem 0.375rem; border-radius: 4px;">LICENSE_KEY=<?php echo escape($license['license_key']); ?></code>
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($downloads)): ?>
                <div class="detail-card download-card" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 1px solid #93c5fd;">
                    <h3 style="color: #1e40af; display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-size: 1.25rem;">&#128229;</span> Your Download<?php echo count($downloads) > 1 ? 's' : ''; ?>
                    </h3>
                    <p style="font-size: 0.875rem; color: #1d4ed8; margin-bottom: 1rem;">
                        Download links expire in 30 days. Make sure to save your files!
                    </p>
                    <?php foreach ($downloads as $download): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: #fff; border-radius: 8px; border: 1px solid #bfdbfe; margin-bottom: 0.75rem;">
                        <div>
                            <strong style="color: #1e40af;"><?php echo escape($download['product_name']); ?></strong>
                            <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">
                                <?php if ($download['max_downloads']): ?>
                                    <?php echo $download['max_downloads']; ?> downloads available
                                <?php else: ?>
                                    Unlimited downloads
                                <?php endif; ?>
                                &bull; Expires: <?php echo date('M j, Y', strtotime($download['expires_at'])); ?>
                            </div>
                        </div>
                        <a href="/download/<?php echo escape($download['token']); ?>" class="btn btn-primary" style="white-space: nowrap;">
                            &#8595; Download Now
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="confirmation-actions">
                <a href="/products" class="btn btn-primary">Continue Shopping</a>
                <?php if (auth()): ?>
                    <a href="/account/orders" class="btn btn-outline">View Order History</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
