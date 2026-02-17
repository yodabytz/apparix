<div class="container" style="padding: 3rem 1rem; max-width: 800px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 3rem;">
        <div style="font-size: 3rem; margin-bottom: 1rem;">💝</div>
        <h1 style="font-family: 'Playfair Display', serif; font-size: 2.5rem; margin-bottom: 1rem;">Referral Program</h1>
        <p style="color: #6b7280; font-size: 1.125rem; max-width: 600px; margin: 0 auto;">
            Share the love! Give your friends 10% off their first order and earn $10 credit for every successful referral.
        </p>
    </div>

    <!-- How it Works -->
    <div class="card" style="background: #fff; border-radius: 20px; padding: 2rem; box-shadow: 0 4px 20px rgba(0,0,0,0.08); margin-bottom: 2rem;">
        <h2 style="font-size: 1.25rem; margin: 0 0 1.5rem 0; text-align: center;">How It Works</h2>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; text-align: center;">
            <div>
                <div style="width: 50px; height: 50px; background: #fff5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 1.5rem;">1</div>
                <h3 style="font-size: 1rem; margin: 0 0 0.5rem 0;">Share Your Code</h3>
                <p style="font-size: 0.875rem; color: #6b7280; margin: 0;">Share your unique referral code with friends and family.</p>
            </div>
            <div>
                <div style="width: 50px; height: 50px; background: #fff5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 1.5rem;">2</div>
                <h3 style="font-size: 1rem; margin: 0 0 0.5rem 0;">They Shop</h3>
                <p style="font-size: 0.875rem; color: #6b7280; margin: 0;">They get 10% off their first order using your code.</p>
            </div>
            <div>
                <div style="width: 50px; height: 50px; background: #fff5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 1.5rem;">3</div>
                <h3 style="font-size: 1rem; margin: 0 0 0.5rem 0;">You Earn</h3>
                <p style="font-size: 0.875rem; color: #6b7280; margin: 0;">You get $10 store credit for each successful referral!</p>
            </div>
        </div>
    </div>

    <?php if (auth() && $userCode): ?>
    <!-- User's Referral Code -->
    <div class="card" style="background: linear-gradient(135deg, #FF68C5 0%, #ff8fd4 100%); border-radius: 20px; padding: 2rem; color: white; margin-bottom: 2rem;">
        <h2 style="font-size: 1.125rem; margin: 0 0 1rem 0; opacity: 0.9;">Your Referral Code</h2>
        <div id="referralCode" style="background: rgba(255,255,255,0.2); padding: 1rem 1.5rem; border-radius: 12px; font-family: monospace; font-size: 1.5rem; font-weight: 700; letter-spacing: 0.1em; display: inline-block; cursor: pointer;" onclick="copyCode()">
            <?php echo escape($userCode['code']); ?>
        </div>
        <p id="copyMessage" style="font-size: 0.875rem; margin: 0.75rem 0 0 0; opacity: 0.9;">Click to copy</p>

        <div style="display: flex; gap: 1rem; margin-top: 1.5rem; flex-wrap: wrap;">
            <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode('Get 10% off at ' . appName() . ' with my code: ' . $userCode['code'] . ' 💝'); ?>&url=<?php echo urlencode(appUrl()); ?>" target="_blank" style="background: rgba(255,255,255,0.2); padding: 0.5rem 1rem; border-radius: 8px; color: white; text-decoration: none; font-size: 0.875rem;">
                Share on X
            </a>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(appUrl()); ?>&quote=<?php echo urlencode('Get 10% off with my code: ' . $userCode['code']); ?>" target="_blank" style="background: rgba(255,255,255,0.2); padding: 0.5rem 1rem; border-radius: 8px; color: white; text-decoration: none; font-size: 0.875rem;">
                Share on Facebook
            </a>
            <button onclick="shareViaEmail()" style="background: rgba(255,255,255,0.2); padding: 0.5rem 1rem; border-radius: 8px; color: white; border: none; font-size: 0.875rem; cursor: pointer;">
                Share via Email
            </button>
        </div>
    </div>

    <!-- Stats -->
    <?php if ($userStats): ?>
    <div class="card" style="background: #fff; border-radius: 16px; padding: 2rem; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
        <h2 style="font-size: 1.25rem; margin: 0 0 1.5rem 0;">Your Referral Stats</h2>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; text-align: center;">
            <div style="background: #f9fafb; padding: 1.5rem; border-radius: 12px;">
                <p style="font-size: 2rem; font-weight: 700; color: #FF68C5; margin: 0;"><?php echo $userStats['total_referrals']; ?></p>
                <p style="font-size: 0.875rem; color: #6b7280; margin: 0.5rem 0 0 0;">Total Referrals</p>
            </div>
            <div style="background: #f9fafb; padding: 1.5rem; border-radius: 12px;">
                <p style="font-size: 2rem; font-weight: 700; color: #10b981; margin: 0;">$<?php echo number_format($userStats['total_earned'], 2); ?></p>
                <p style="font-size: 0.875rem; color: #6b7280; margin: 0.5rem 0 0 0;">Total Earned</p>
            </div>
            <div style="background: #f9fafb; padding: 1.5rem; border-radius: 12px;">
                <p style="font-size: 2rem; font-weight: 700; color: #3b82f6; margin: 0;">$<?php echo number_format($userStats['available_credit'], 2); ?></p>
                <p style="font-size: 0.875rem; color: #6b7280; margin: 0.5rem 0 0 0;">Available Credit</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <!-- Not Logged In -->
    <div class="card" style="background: #fff; border-radius: 20px; padding: 2.5rem; box-shadow: 0 4px 20px rgba(0,0,0,0.08); text-align: center;">
        <h2 style="font-size: 1.5rem; margin: 0 0 1rem 0;">Get Your Referral Code</h2>
        <p style="color: #6b7280; margin: 0 0 1.5rem 0;">
            Sign in or create an account to get your unique referral code and start earning rewards!
        </p>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="/login?redirect=/referrals" class="btn btn-primary" style="padding: 0.875rem 2rem;">Sign In</a>
            <a href="/register?redirect=/referrals" class="btn btn-secondary" style="padding: 0.875rem 2rem;">Create Account</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Have a Code Section -->
    <div class="card" style="background: #fff; border-radius: 16px; padding: 2rem; box-shadow: 0 4px 20px rgba(0,0,0,0.08); margin-top: 2rem;">
        <h3 style="margin: 0 0 1rem 0; font-size: 1.125rem;">Have a referral code?</h3>
        <p style="color: #6b7280; margin: 0 0 1rem 0; font-size: 0.875rem;">
            Enter a friend's referral code at checkout to get 10% off your first order!
        </p>
        <a href="/products" class="btn btn-primary" style="padding: 0.75rem 1.5rem;">Start Shopping</a>
    </div>
</div>

<script>
function copyCode() {
    const code = document.getElementById('referralCode').textContent.trim();
    navigator.clipboard.writeText(code).then(() => {
        document.getElementById('copyMessage').textContent = 'Copied!';
        setTimeout(() => {
            document.getElementById('copyMessage').textContent = 'Click to copy';
        }, 2000);
    });
}

function shareViaEmail() {
    const code = '<?php echo $userCode['code'] ?? ''; ?>';
    const storeName = '<?php echo escape(appName()); ?>';
    const storeUrl = '<?php echo appUrl(); ?>';
    const subject = encodeURIComponent('Get 10% off at ' + storeName + '!');
    const body = encodeURIComponent(`Hi!\n\nI thought you'd love ${storeName}!\n\nUse my referral code ${code} to get 10% off your first order.\n\nShop now: ${storeUrl}\n\nHappy shopping!`);
    window.location.href = `mailto:?subject=${subject}&body=${body}`;
}
</script>
