<div class="container" style="max-width: 500px; margin: 5rem auto; padding: 0 1rem; text-align: center;">
    <div style="background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); padding: 3rem 2rem;">
        <div style="width: 80px; height: 80px; background: #fee2e2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; font-size: 2.5rem;">
            &#9888;
        </div>

        <h1 style="font-size: 1.5rem; margin: 0 0 1rem; color: #dc2626;">Download Error</h1>

        <p style="color: #64748b; margin: 0 0 2rem; line-height: 1.6;">
            <?php echo escape($error ?? 'An error occurred while processing your download.'); ?>
        </p>

        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <a href="/licenses/lookup" class="btn btn-outline" style="display: block;">Look Up Your Licenses</a>
            <a href="mailto:support@apparix.app" class="btn btn-primary" style="display: block;">Contact Support</a>
        </div>
    </div>
</div>
