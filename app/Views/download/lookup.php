<div class="container" style="max-width: 700px; margin: 3rem auto; padding: 0 1rem;">
    <div style="background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); padding: 2rem;">
        <h1 style="font-size: 1.5rem; margin: 0 0 0.5rem; text-align: center;">License Lookup</h1>
        <p style="color: #64748b; text-align: center; margin: 0 0 2rem;">Enter your email to view your licenses and downloads</p>

        <?php if (!empty($error)): ?>
        <div style="background: #fee2e2; color: #dc2626; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
            <?php echo escape($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="/licenses/lookup">
            <?php echo csrfField(); ?>
            <div style="display: flex; gap: 1rem; margin-bottom: 2rem;">
                <input type="email" name="email" placeholder="you@example.com" value="<?php echo escape($email ?? ''); ?>"
                       style="flex: 1; padding: 0.875rem 1rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 1rem;"
                       required>
                <button type="submit" class="btn btn-primary">Look Up</button>
            </div>
        </form>

        <?php if (isset($email) && isset($licenses)): ?>
            <?php if (empty($licenses) && empty($downloads)): ?>
            <div style="text-align: center; padding: 2rem; background: #f8fafc; border-radius: 8px;">
                <p style="color: #64748b; margin: 0;">No licenses found for <strong><?php echo escape($email); ?></strong></p>
                <p style="color: #94a3b8; font-size: 0.875rem; margin: 0.5rem 0 0;">Make sure you're using the email from your order.</p>
            </div>
            <?php else: ?>

            <!-- Licenses -->
            <?php if (!empty($licenses)): ?>
            <div style="margin-bottom: 2rem;">
                <h2 style="font-size: 1.125rem; margin: 0 0 1rem;">Your Licenses</h2>
                <?php foreach ($licenses as $license): ?>
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 1rem; margin-bottom: 0.75rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <span style="font-weight: 600;"><?php echo escape($license['product_name']); ?></span>
                        <span style="background: #166534; color: #fff; padding: 0.125rem 0.5rem; border-radius: 20px; font-size: 0.7rem;">
                            <?php echo \App\Models\OrderLicense::getEditionName($license['edition_code']); ?>
                        </span>
                    </div>
                    <div style="background: #1e293b; color: #22c55e; font-family: monospace; padding: 0.75rem; border-radius: 4px; font-size: 0.875rem; word-break: break-all;">
                        <?php echo escape($license['license_key']); ?>
                    </div>
                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.5rem;">
                        Order #<?php echo escape($license['order_number']); ?> &bull;
                        <?php echo date('M j, Y', strtotime($license['created_at'])); ?>
                        <?php if ($license['domain']): ?>
                        &bull; Domain: <?php echo escape($license['domain']); ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Downloads -->
            <?php if (!empty($downloads)): ?>
            <div>
                <h2 style="font-size: 1.125rem; margin: 0 0 1rem;">Your Downloads</h2>
                <?php foreach ($downloads as $download): ?>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; margin-bottom: 0.75rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                        <div>
                            <strong><?php echo escape($download['product_name']); ?></strong>
                            <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">
                                Order #<?php echo escape($download['order_number']); ?>
                                <?php if ($download['expires_at']): ?>
                                &bull; Expires: <?php echo date('M j, Y', strtotime($download['expires_at'])); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <a href="/download/<?php echo escape($download['download_token']); ?>" class="btn btn-primary btn-sm">Download</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
