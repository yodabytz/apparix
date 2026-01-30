<div class="card" style="text-align: center;">
    <div style="font-size: 64px; margin-bottom: 16px;">🎉</div>
    <h1 class="card-title">Installation Complete!</h1>
    <p class="card-description">Apparix has been successfully installed and configured.</p>

    <div style="background: #d4edda; border-radius: 8px; padding: 20px; margin: 24px 0; color: #155724;">
        <strong>Your store is ready!</strong>
        <p style="margin: 8px 0 0;">You can now access your admin panel and start adding products.</p>
    </div>

    <div style="background: #f9fafb; border-radius: 8px; padding: 20px; margin-bottom: 24px; text-align: left;">
        <h3 style="font-size: 1rem; margin-bottom: 16px;">Quick Start Guide</h3>
        <ol style="margin: 0; padding-left: 20px; line-height: 1.8;">
            <li>Log in to your admin panel</li>
            <li>Add your first product category</li>
            <li>Create your products with images and variants</li>
            <li>Configure shipping zones and rates</li>
            <li>Set up your payment methods (Stripe)</li>
            <li>Start selling!</li>
        </ol>
    </div>

    <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 20px; margin-bottom: 24px; text-align: left;">
        <h3 style="font-size: 1rem; margin-bottom: 12px; color: #856404;">⚙️ Important: Set Up Cron Jobs</h3>
        <p style="margin-bottom: 12px; color: #856404; font-size: 0.9rem;">For full functionality, add these cron jobs to your server. Run <code style="background: #f8f9fa; padding: 2px 6px; border-radius: 4px;">crontab -e</code> and add:</p>
        <pre style="background: #1e1e1e; color: #d4d4d4; padding: 16px; border-radius: 6px; overflow-x: auto; font-size: 0.8rem; line-height: 1.6; margin: 0;">
# Apparix E-Commerce Cron Jobs
# Replace SITEPATH with your actual installation path

# Abandoned cart emails - Every hour
0 * * * * php /var/www/SITEPATH/cron/abandoned-carts.php >> /var/log/apparix-cron.log 2>&1

# Review request emails - Daily at 10 AM
0 10 * * * php /var/www/SITEPATH/cron/send-review-requests.php >> /var/log/apparix-cron.log 2>&1

# Wishlist reminders - Daily at 9 AM
0 9 * * * php /var/www/SITEPATH/cron/wishlist-reminders.php >> /var/log/apparix-cron.log 2>&1

# Check delivery status - Every 4 hours
0 */4 * * * php /var/www/SITEPATH/cron/check-delivery-status.php >> /var/log/apparix-cron.log 2>&1

# Google Merchant Feed - Daily at 3 AM
0 3 * * * php /var/www/SITEPATH/scripts/generate-google-feed.php >> /var/log/google-feed.log 2>&1

# Cleanup orphaned favorites - Weekly on Sunday at 2 AM
0 2 * * 0 php /var/www/SITEPATH/scripts/cleanup-orphaned-favorites.php >> /var/log/apparix-cron.log 2>&1

# Image optimization - Daily at 4 AM
0 4 * * * php /var/www/SITEPATH/scripts/optimize-images.php >> /var/log/apparix-cron.log 2>&1</pre>
        <p style="margin-top: 12px; margin-bottom: 8px; color: #856404; font-size: 0.85rem;">
            <strong>Quick Setup:</strong> Run <code style="background: #f8f9fa; padding: 2px 6px; border-radius: 4px;">sudo bash setup-cron.sh</code> from your installation directory to automatically configure all cron jobs.
        </p>
        <p style="margin: 0; color: #856404; font-size: 0.85rem;">
            <strong>Manual:</strong> Create log file with: <code style="background: #f8f9fa; padding: 2px 6px; border-radius: 4px;">sudo touch /var/log/apparix-cron.log && sudo chmod 666 /var/log/apparix-cron.log</code>
        </p>
    </div>

    <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
        <a href="/admin" class="btn btn-primary btn-lg">Go to Admin Panel</a>
        <a href="/" class="btn btn-secondary">View Your Store</a>
    </div>

    <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: 0.9rem;">
        <p>Need help? Check out the <a href="https://docs.apparix.app" target="_blank" style="color: #FF68C5;">documentation</a> or contact support.</p>
    </div>
</div>
