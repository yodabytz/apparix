<div class="container" style="max-width: 800px; margin: 3rem auto; padding: 0 1rem;">
    <div class="download-card" style="background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); overflow: hidden;">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, var(--primary-pink), var(--secondary-pink)); color: #fff; padding: 2rem; text-align: center;">
            <h1 style="margin: 0 0 0.5rem; font-size: 1.75rem;">Thank You for Your Purchase!</h1>
            <p style="margin: 0; opacity: 0.9;">Order #<?php echo escape($download['order_number']); ?></p>
        </div>

        <div style="padding: 2rem;">
            <!-- License Keys Section -->
            <?php if (!empty($licenses)): ?>
            <div style="margin-bottom: 2rem;">
                <h2 style="font-size: 1.25rem; margin: 0 0 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span style="font-size: 1.5rem;">&#128273;</span> Your License Key<?php echo count($licenses) > 1 ? 's' : ''; ?>
                </h2>

                <?php foreach ($licenses as $license): ?>
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 1.25rem; margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.75rem;">
                        <span style="font-weight: 600; color: #166534;"><?php echo escape($license['product_name']); ?></span>
                        <span style="background: #166534; color: #fff; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">
                            <?php echo \App\Models\OrderLicense::getEditionName($license['edition_code']); ?>
                        </span>
                    </div>
                    <div style="background: #1e293b; color: #22c55e; font-family: monospace; padding: 1rem; border-radius: 6px; font-size: 1rem; word-break: break-all; user-select: all;">
                        <?php echo escape($license['license_key']); ?>
                    </div>
                    <p style="margin: 0.75rem 0 0; font-size: 0.875rem; color: #64748b;">
                        Add this key to your <code style="background: #e2e8f0; padding: 0.125rem 0.375rem; border-radius: 4px;">.env</code> file as <code style="background: #e2e8f0; padding: 0.125rem 0.375rem; border-radius: 4px;">LICENSE_KEY=<?php echo escape($license['license_key']); ?></code>
                    </p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Download Section -->
            <div style="margin-bottom: 2rem;">
                <h2 style="font-size: 1.25rem; margin: 0 0 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span style="font-size: 1.5rem;">&#128229;</span> Download Your Files
                </h2>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <strong style="display: block; margin-bottom: 0.25rem;"><?php echo escape($download['product_name']); ?></strong>
                            <span style="font-size: 0.875rem; color: #64748b;">
                                <?php if ($download['max_downloads']): ?>
                                    Downloads: <?php echo $download['download_count']; ?>/<?php echo $download['max_downloads']; ?>
                                <?php else: ?>
                                    Downloads: <?php echo $download['download_count']; ?> (Unlimited)
                                <?php endif; ?>
                                <?php if ($download['expires_at']): ?>
                                    &bull; Expires: <?php echo date('M j, Y', strtotime($download['expires_at'])); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <a href="/download/<?php echo escape($token); ?>/file" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                            <span>&#8595;</span> Download Now
                        </a>
                    </div>
                </div>
            </div>

            <!-- Installation Instructions -->
            <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 1.5rem;">
                <h3 style="margin: 0 0 1rem; font-size: 1rem; color: #1e40af;">Quick Installation Guide</h3>
                <ol style="margin: 0; padding-left: 1.25rem; color: #1e40af; font-size: 0.9375rem; line-height: 1.8;">
                    <li>Extract the downloaded archive to your web server</li>
                    <li>Copy <code style="background: #dbeafe; padding: 0.125rem 0.375rem; border-radius: 4px;">.env.example</code> to <code style="background: #dbeafe; padding: 0.125rem 0.375rem; border-radius: 4px;">.env</code></li>
                    <li>Add your license key to the <code style="background: #dbeafe; padding: 0.125rem 0.375rem; border-radius: 4px;">.env</code> file</li>
                    <li>Visit your domain to run the installer</li>
                    <li>Follow the setup wizard to configure your store</li>
                </ol>
            </div>

            <!-- Support Info -->
            <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e2e8f0; text-align: center;">
                <p style="margin: 0; color: #64748b; font-size: 0.9375rem;">
                    Need help? Contact us at <a href="mailto:support@apparix.app" style="color: var(--primary-pink);">support@apparix.app</a>
                </p>
            </div>
        </div>
    </div>
</div>
