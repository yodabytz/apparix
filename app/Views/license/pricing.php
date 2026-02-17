<style>
.pricing-hero {
    text-align: center;
    padding: 3rem 1rem;
    background: linear-gradient(135deg, var(--theme-primary, #8B5CF6) 0%, var(--theme-secondary, #A78BFA) 100%);
    color: white;
    margin-bottom: 3rem;
}
.pricing-hero h1 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}
.pricing-hero p {
    font-size: 1.2rem;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto;
}
.pricing-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}
.pricing-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: transform 0.3s, box-shadow 0.3s;
    position: relative;
}
.pricing-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
}
.pricing-card.popular {
    border: 3px solid var(--theme-primary, #8B5CF6);
}
.popular-badge {
    position: absolute;
    top: 0;
    right: 20px;
    background: var(--theme-primary, #8B5CF6);
    color: white;
    padding: 0.5rem 1rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    border-radius: 0 0 8px 8px;
}
.pricing-header {
    padding: 2rem;
    text-align: center;
    border-bottom: 1px solid #f0f0f0;
}
.pricing-header h3 {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    color: #333;
}
.pricing-price {
    font-size: 3rem;
    font-weight: 700;
    color: var(--theme-primary, #8B5CF6);
}
.pricing-price span {
    font-size: 1rem;
    font-weight: 400;
    color: #666;
}
.pricing-features {
    padding: 2rem;
}
.pricing-features ul {
    list-style: none;
    padding: 0;
    margin: 0;
}
.pricing-features li {
    padding: 0.75rem 0;
    border-bottom: 1px solid #f5f5f5;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.pricing-features li:last-child {
    border-bottom: none;
}
.pricing-features li svg {
    color: #22c55e;
    flex-shrink: 0;
}
.pricing-action {
    padding: 0 2rem 2rem;
}
.pricing-action .btn {
    width: 100%;
    padding: 1rem;
    font-size: 1.1rem;
}
.pricing-card.popular .pricing-action .btn {
    background: var(--theme-primary, #8B5CF6);
}

/* Purchase Modal */
.purchase-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.6);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.purchase-modal.active {
    display: flex;
}
.purchase-modal-content {
    background: white;
    border-radius: 16px;
    max-width: 500px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
}
.purchase-modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.purchase-modal-header h3 {
    margin: 0;
}
.purchase-modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #666;
}
.purchase-modal-body {
    padding: 1.5rem;
}
.form-group {
    margin-bottom: 1.5rem;
}
.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}
.form-group input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
}
.form-group input:focus {
    outline: none;
    border-color: var(--theme-primary, #8B5CF6);
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}
.form-group small {
    display: block;
    margin-top: 0.5rem;
    color: #666;
    font-size: 0.875rem;
}
.purchase-summary {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}
.purchase-summary-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
}
.purchase-summary-row.total {
    border-top: 2px solid #ddd;
    font-weight: 600;
    font-size: 1.2rem;
    margin-top: 0.5rem;
    padding-top: 1rem;
}

/* Resend Section */
.resend-section {
    max-width: 500px;
    margin: 3rem auto;
    padding: 2rem;
    background: #f8f9fa;
    border-radius: 12px;
    text-align: center;
}
.resend-section h3 {
    margin-bottom: 1rem;
}
.resend-form {
    display: flex;
    gap: 0.5rem;
}
.resend-form input {
    flex: 1;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 8px;
}
.resend-form button {
    white-space: nowrap;
}
.resend-message {
    margin-top: 1rem;
    padding: 0.75rem;
    border-radius: 8px;
    display: none;
}
.resend-message.success {
    background: #d4edda;
    color: #155724;
    display: block;
}
.resend-message.error {
    background: #f8d7da;
    color: #721c24;
    display: block;
}

/* FAQ */
.faq-section {
    max-width: 800px;
    margin: 3rem auto;
    padding: 0 1rem;
}
.faq-section h2 {
    text-align: center;
    margin-bottom: 2rem;
}
.faq-item {
    margin-bottom: 1rem;
    border: 1px solid #eee;
    border-radius: 8px;
}
.faq-question {
    padding: 1rem 1.5rem;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.faq-question:hover {
    background: #f8f9fa;
}
.faq-answer {
    display: none;
    padding: 0 1.5rem 1rem;
    color: #666;
}
.faq-item.open .faq-answer {
    display: block;
}
.faq-item.open .faq-question svg {
    transform: rotate(180deg);
}
</style>

<div class="pricing-hero">
    <h1>Simple, Transparent Pricing</h1>
    <p>One-time purchase. No subscriptions. Lifetime updates for the version you purchase.</p>
</div>

<div class="pricing-grid">
    <?php foreach ($tiers as $tierId => $tier): ?>
    <div class="pricing-card <?php echo $tier['popular'] ? 'popular' : ''; ?>">
        <?php if ($tier['popular']): ?>
            <div class="popular-badge">Most Popular</div>
        <?php endif; ?>

        <div class="pricing-header">
            <h3><?php echo escape($tier['name']); ?></h3>
            <div class="pricing-price">
                <?php echo escape($tier['price_display']); ?>
                <span>one-time</span>
            </div>
        </div>

        <div class="pricing-features">
            <ul>
                <?php foreach ($tier['features'] as $feature): ?>
                <li>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    <?php echo escape($feature); ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="pricing-action">
            <button class="btn btn-primary" onclick="openPurchaseModal('<?php echo $tierId; ?>')">
                Get <?php echo escape($tier['name']); ?>
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Resend License Section -->
<div class="resend-section">
    <h3>Already purchased?</h3>
    <p>Enter your email to resend your license key.</p>
    <form class="resend-form" onsubmit="resendLicense(event)">
        <input type="email" id="resendEmail" placeholder="your@email.com" required>
        <button type="submit" class="btn btn-outline">Resend</button>
    </form>
    <div id="resendMessage" class="resend-message"></div>
</div>

<!-- FAQ Section -->
<div class="faq-section">
    <h2>Frequently Asked Questions</h2>

    <div class="faq-item" onclick="toggleFaq(this)">
        <div class="faq-question">
            Is this a one-time purchase or subscription?
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="faq-answer">
            All licenses are one-time purchases. You pay once and own the license forever. Updates for your major version are included.
        </div>
    </div>

    <div class="faq-item" onclick="toggleFaq(this)">
        <div class="faq-question">
            Can I use one license on multiple domains?
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="faq-answer">
            Each license is for one production domain. You can use it on unlimited development/staging environments. Need multiple domains? Contact us for volume discounts.
        </div>
    </div>

    <div class="faq-item" onclick="toggleFaq(this)">
        <div class="faq-question">
            What's included in "lifetime updates"?
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="faq-answer">
            You receive all updates within your major version (e.g., all 1.x updates). Major version upgrades (e.g., 2.0) are offered at a discounted upgrade price.
        </div>
    </div>

    <div class="faq-item" onclick="toggleFaq(this)">
        <div class="faq-question">
            Is there a free trial?
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="faq-answer">
            Yes! Apparix has a free tier with limited features. Download it from <a href="https://github.com/yodabytz/apparix">GitHub</a> and try it out. Upgrade when you need more features.
        </div>
    </div>

    <div class="faq-item" onclick="toggleFaq(this)">
        <div class="faq-question">
            What payment methods do you accept?
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="faq-answer">
            We accept all major credit cards (Visa, Mastercard, American Express, Discover) via Stripe. All payments are secure and encrypted.
        </div>
    </div>

    <div class="faq-item" onclick="toggleFaq(this)">
        <div class="faq-question">
            What if I need help installing?
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="faq-answer">
            Professional and Enterprise licenses include email support. We also have comprehensive documentation at <a href="/docs">apparix.app/docs</a> and a community forum.
        </div>
    </div>
</div>

<!-- Purchase Modal -->
<div class="purchase-modal" id="purchaseModal">
    <div class="purchase-modal-content">
        <div class="purchase-modal-header">
            <h3>Purchase <span id="modalTierName"></span> License</h3>
            <button class="purchase-modal-close" onclick="closePurchaseModal()">&times;</button>
        </div>
        <div class="purchase-modal-body">
            <form id="purchaseForm" onsubmit="submitPurchase(event)">
                <?php echo csrfField(); ?>
                <input type="hidden" name="tier" id="modalTier" value="">

                <div class="form-group">
                    <label for="purchaseEmail">Email Address *</label>
                    <input type="email" id="purchaseEmail" name="email" required placeholder="your@email.com">
                    <small>Your license key will be sent to this email</small>
                </div>

                <div class="form-group">
                    <label for="purchaseDomain">Domain (Optional)</label>
                    <input type="text" id="purchaseDomain" name="domain" placeholder="example.com">
                    <small>Leave blank for a wildcard license (any domain)</small>
                </div>

                <div class="purchase-summary">
                    <div class="purchase-summary-row">
                        <span>License:</span>
                        <span id="summaryTier">-</span>
                    </div>
                    <div class="purchase-summary-row total">
                        <span>Total:</span>
                        <span id="summaryPrice">-</span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem;" id="purchaseBtn">
                    Proceed to Payment
                </button>

                <p style="text-align: center; margin-top: 1rem; font-size: 0.875rem; color: #666;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle;">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    Secure payment powered by Stripe
                </p>
            </form>
        </div>
    </div>
</div>

<script src="https://js.stripe.com/v3/"></script>
<script>
const csrfToken = '<?php echo csrfToken(); ?>';
const tiers = <?php echo json_encode($tiers); ?>;

function openPurchaseModal(tierId) {
    const tier = tiers[tierId];
    document.getElementById('modalTier').value = tierId;
    document.getElementById('modalTierName').textContent = tier.name;
    document.getElementById('summaryTier').textContent = 'Apparix ' + tier.name;
    document.getElementById('summaryPrice').textContent = tier.price_display;
    document.getElementById('purchaseModal').classList.add('active');
    document.getElementById('purchaseEmail').focus();
}

function closePurchaseModal() {
    document.getElementById('purchaseModal').classList.remove('active');
}

// Close modal on outside click
document.getElementById('purchaseModal').addEventListener('click', function(e) {
    if (e.target === this) closePurchaseModal();
});

async function submitPurchase(e) {
    e.preventDefault();

    const btn = document.getElementById('purchaseBtn');
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Processing...';

    const formData = new FormData(document.getElementById('purchaseForm'));

    try {
        const response = await fetch('/license/checkout', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.error) {
            alert(data.error);
            btn.disabled = false;
            btn.textContent = originalText;
            return;
        }

        // Redirect to Stripe Checkout
        window.location.href = data.url;

    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
        btn.disabled = false;
        btn.textContent = originalText;
    }
}

async function resendLicense(e) {
    e.preventDefault();

    const email = document.getElementById('resendEmail').value;
    const messageEl = document.getElementById('resendMessage');

    messageEl.className = 'resend-message';
    messageEl.textContent = '';

    try {
        const formData = new FormData();
        formData.append('_csrf_token', csrfToken);
        formData.append('email', email);

        const response = await fetch('/license/resend', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.error) {
            messageEl.className = 'resend-message error';
            messageEl.textContent = data.error;
        } else {
            messageEl.className = 'resend-message success';
            messageEl.textContent = data.message || 'License key sent! Check your email.';
            document.getElementById('resendEmail').value = '';
        }
    } catch (error) {
        messageEl.className = 'resend-message error';
        messageEl.textContent = 'An error occurred. Please try again.';
    }
}

function toggleFaq(el) {
    el.classList.toggle('open');
}
</script>
