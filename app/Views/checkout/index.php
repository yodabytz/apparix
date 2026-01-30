<!-- Checkout Page -->
<?php
// Check if cart contains only digital products (no physical items to ship)
$isDigitalOnly = true;
foreach ($items as $item) {
    if (empty($item['is_digital'])) {
        $isDigitalOnly = false;
        break;
    }
}
?>
<section class="checkout-section">
    <div class="container">
        <h1>Checkout</h1>

        <div class="checkout-layout">
            <!-- Checkout Form -->
            <div class="checkout-form-section">
                <form id="checkoutForm" action="/checkout/process" method="POST">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="payment_intent_id" id="paymentIntentId" value="">
                    <input type="hidden" name="shipping_method_id" id="shippingMethodId" value="">

                    <!-- Contact Information -->
                    <div class="checkout-card">
                        <h2>Contact Information</h2>
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" required
                                   value="<?php echo auth() ? escape(auth()['email']) : ''; ?>"
                                   placeholder="your@email.com">
                        </div>
                    </div>

                    <!-- Shipping Address (hidden for digital-only orders) -->
                    <?php if (!$isDigitalOnly): ?>
                    <div class="checkout-card">
                        <h2>Shipping Address</h2>

                        <?php if (!empty($savedAddresses)): ?>
                            <div class="form-group">
                                <label for="saved_address">Use Saved Address</label>
                                <select id="saved_address" onchange="fillSavedAddress(this.value)">
                                    <option value="">-- Enter new address --</option>
                                    <?php foreach ($savedAddresses as $addr): ?>
                                        <?php if ($addr['type'] === 'shipping'): ?>
                                            <option value="<?php echo $addr['id']; ?>"
                                                    data-first="<?php echo escape($addr['first_name']); ?>"
                                                    data-last="<?php echo escape($addr['last_name']); ?>"
                                                    data-addr1="<?php echo escape($addr['address_line1']); ?>"
                                                    data-addr2="<?php echo escape($addr['address_line2']); ?>"
                                                    data-city="<?php echo escape($addr['city']); ?>"
                                                    data-state="<?php echo escape($addr['state']); ?>"
                                                    data-postal="<?php echo escape($addr['postal_code']); ?>"
                                                    data-country="<?php echo escape($addr['country']); ?>"
                                                    data-phone="<?php echo escape($addr['phone']); ?>">
                                                <?php echo escape($addr['first_name'] . ' ' . $addr['last_name'] . ' - ' . $addr['address_line1'] . ', ' . $addr['city']); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="shipping_first_name">First Name *</label>
                                <input type="text" id="shipping_first_name" name="shipping_first_name" required>
                            </div>
                            <div class="form-group">
                                <label for="shipping_last_name">Last Name *</label>
                                <input type="text" id="shipping_last_name" name="shipping_last_name" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="shipping_address1">Address Line 1 *</label>
                            <input type="text" id="shipping_address1" name="shipping_address1" required placeholder="Street address">
                        </div>

                        <div class="form-group">
                            <label for="shipping_address2">Address Line 2</label>
                            <input type="text" id="shipping_address2" name="shipping_address2" placeholder="Apartment, suite, etc. (optional)">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="shipping_city">City *</label>
                                <input type="text" id="shipping_city" name="shipping_city" required>
                            </div>
                            <div class="form-group">
                                <label for="shipping_state">State/Province *</label>
                                <input type="text" id="shipping_state" name="shipping_state" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="shipping_postal">ZIP/Postal Code *</label>
                                <input type="text" id="shipping_postal" name="shipping_postal" required>
                            </div>
                            <div class="form-group">
                                <label for="shipping_country">Country *</label>
                                <select id="shipping_country" name="shipping_country" onchange="updateShippingRates()">
                                    <option value="">-- Select Country --</option>
                                    <optgroup label="North America">
                                        <option value="US">United States</option>
                                        <option value="CA">Canada</option>
                                    </optgroup>
                                    <optgroup label="Europe">
                                        <option value="GB">United Kingdom</option>
                                        <option value="IE">Ireland</option>
                                        <option value="DE">Germany</option>
                                        <option value="FR">France</option>
                                        <option value="IT">Italy</option>
                                        <option value="ES">Spain</option>
                                        <option value="NL">Netherlands</option>
                                        <option value="BE">Belgium</option>
                                        <option value="AT">Austria</option>
                                        <option value="CH">Switzerland</option>
                                        <option value="SE">Sweden</option>
                                        <option value="NO">Norway</option>
                                        <option value="DK">Denmark</option>
                                        <option value="FI">Finland</option>
                                        <option value="PL">Poland</option>
                                        <option value="PT">Portugal</option>
                                        <option value="CZ">Czech Republic</option>
                                        <option value="GR">Greece</option>
                                    </optgroup>
                                    <optgroup label="Asia Pacific">
                                        <option value="AU">Australia</option>
                                        <option value="NZ">New Zealand</option>
                                        <option value="JP">Japan</option>
                                        <option value="KR">South Korea</option>
                                        <option value="SG">Singapore</option>
                                        <option value="HK">Hong Kong</option>
                                        <option value="TW">Taiwan</option>
                                        <option value="MY">Malaysia</option>
                                        <option value="TH">Thailand</option>
                                        <option value="PH">Philippines</option>
                                        <option value="ID">Indonesia</option>
                                        <option value="VN">Vietnam</option>
                                        <option value="CN">China</option>
                                        <option value="IN">India</option>
                                    </optgroup>
                                    <optgroup label="South America">
                                        <option value="BR">Brazil</option>
                                        <option value="MX">Mexico</option>
                                        <option value="AR">Argentina</option>
                                        <option value="CL">Chile</option>
                                        <option value="CO">Colombia</option>
                                    </optgroup>
                                    <optgroup label="Middle East & Africa">
                                        <option value="AE">United Arab Emirates</option>
                                        <option value="SA">Saudi Arabia</option>
                                        <option value="IL">Israel</option>
                                        <option value="ZA">South Africa</option>
                                    </optgroup>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="shipping_phone">Phone Number</label>
                            <input type="tel" id="shipping_phone" name="shipping_phone" placeholder="For delivery questions">
                        </div>
                    </div>

                    <!-- Shipping Method -->
                    <div class="checkout-card">
                        <h2>Shipping Method</h2>
                        <div id="shippingMethodsContainer">
                            <div class="shipping-loading">
                                <span class="spinner"></span> Calculating shipping options...
                            </div>
                        </div>
                        <div id="shippingError" class="shipping-error" style="display: none;"></div>
                        <div id="freeShippingProgress" class="free-shipping-progress" style="display: none;">
                            <div class="progress-bar">
                                <div class="progress-fill" id="freeShippingBar"></div>
                            </div>
                            <p id="freeShippingMessage"></p>
                        </div>

                        <!-- International Customs Notice -->
                        <div id="customsNotice" class="customs-notice" style="display: none; margin-top: 1rem; padding: 12px 15px; background: #fef3cd; border: 1px solid #ffc107; border-radius: 6px; font-size: 0.9rem;">
                            <strong>📦 International Order Notice:</strong> Orders shipped outside the United States may be subject to customs duties, import taxes, or VAT upon delivery. These charges are determined by your country's customs authority and are the responsibility of the buyer. We have no control over these charges and cannot predict their amount.
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- Digital Delivery Notice (for digital-only orders) -->
                    <div class="checkout-card">
                        <h2>Delivery Method</h2>
                        <div class="digital-delivery-checkout">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            <div>
                                <strong>Instant Digital Delivery</strong>
                                <p>Your download links will be available immediately after purchase and sent to your email.</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Billing Address -->
                    <div class="checkout-card">
                        <h2>Billing Address</h2>
                        <div class="form-group checkbox-group">
                            <label>
                                <input type="checkbox" id="billing_same" name="billing_same" value="1" checked onchange="toggleBillingAddress()">
                                Same as shipping address
                            </label>
                        </div>

                        <div id="billingAddressFields" style="display: none;">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="billing_first_name">First Name *</label>
                                    <input type="text" id="billing_first_name" name="billing_first_name">
                                </div>
                                <div class="form-group">
                                    <label for="billing_last_name">Last Name *</label>
                                    <input type="text" id="billing_last_name" name="billing_last_name">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="billing_address1">Address Line 1 *</label>
                                <input type="text" id="billing_address1" name="billing_address1">
                            </div>

                            <div class="form-group">
                                <label for="billing_address2">Address Line 2</label>
                                <input type="text" id="billing_address2" name="billing_address2">
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="billing_city">City *</label>
                                    <input type="text" id="billing_city" name="billing_city">
                                </div>
                                <div class="form-group">
                                    <label for="billing_state">State *</label>
                                    <input type="text" id="billing_state" name="billing_state">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="billing_postal">ZIP/Postal Code *</label>
                                    <input type="text" id="billing_postal" name="billing_postal">
                                </div>
                                <div class="form-group">
                                    <label for="billing_country">Country</label>
                                    <select id="billing_country" name="billing_country">
                                        <option value="US" selected>United States</option>
                                        <option value="CA">Canada</option>
                                        <option value="GB">United Kingdom</option>
                                        <option value="IE">Ireland</option>
                                        <option value="AU">Australia</option>
                                        <option value="DE">Germany</option>
                                        <option value="FR">France</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Continue to Payment Button (shown before payment is initialized) -->
                    <div id="continueToPaymentSection" class="checkout-card">
                        <button type="button" id="continueToPaymentBtn" class="btn btn-primary btn-large checkout-btn" onclick="initializePayment()">
                            Continue to Payment
                        </button>
                        <div id="continueError" class="payment-message" style="display: none; margin-top: 1rem;"></div>
                    </div>

                    <!-- Payment (hidden until Continue to Payment is clicked) -->
                    <div id="paymentSection" class="checkout-card" style="display: none;">
                        <h2>Payment</h2>
                        <p class="payment-info">All transactions are secure and encrypted.</p>

                        <!-- Stripe Elements container -->
                        <div id="payment-element"></div>
                        <div id="payment-message" class="payment-message" style="display: none;"></div>
                    </div>

                    <button type="submit" id="submitBtn" class="btn btn-primary btn-large checkout-btn" style="display: none;" disabled>
                        <span id="buttonText">Place Order</span>
                        <span id="spinner" class="spinner" style="display: none;"></span>
                    </button>
                </form>
            </div>

            <!-- Order Summary -->
            <div class="checkout-summary">
                <div class="summary-box">
                    <h3>Order Summary</h3>

                    <div class="order-items">
                        <?php foreach ($items as $item): ?>
                            <?php
                            $itemPrice = $item['sale_price'] ?? $item['price'];
                            if (!empty($item['price_adjustment'])) {
                                $itemPrice += $item['price_adjustment'];
                            }
                            $itemTotal = $itemPrice * $item['quantity'];
                            ?>
                            <div class="order-item">
                                <div class="order-item-image">
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="<?php echo escape($item['image']); ?>"
                                             alt="<?php echo escape($item['name']); ?><?php echo !empty($item['variant_name']) ? ' - ' . escape($item['variant_name']) : ''; ?> | <?php echo appName(); ?>"
                                             title="<?php echo escape($item['name']); ?>"
                                             loading="lazy"
                                             width="60"
                                             height="60">
                                    <?php endif; ?>
                                    <span class="item-qty"><?php echo $item['quantity']; ?></span>
                                </div>
                                <div class="order-item-details">
                                    <div class="item-name"><?php echo escape($item['name']); ?></div>
                                    <?php if (!empty($item['variant_name'])): ?>
                                        <div class="item-variant"><?php echo escape($item['variant_name']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="order-item-price"><?php echo formatPrice($itemTotal); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="summary-divider"></div>

                    <!-- Coupon Code -->
                    <div class="coupon-section">
                        <?php $appliedCoupon = $_SESSION['applied_coupon'] ?? null; ?>
                        <div id="couponForm" style="<?php echo $appliedCoupon ? 'display:none;' : ''; ?>">
                            <div class="coupon-input-group">
                                <input type="text" id="couponCode" placeholder="Enter coupon code" maxlength="20">
                                <button type="button" id="applyCouponBtn" class="btn btn-outline btn-sm" onclick="applyCoupon()">Apply</button>
                            </div>
                            <div id="couponError" class="coupon-error" style="display: none;"></div>
                        </div>
                        <div id="couponApplied" class="coupon-applied" style="<?php echo $appliedCoupon ? '' : 'display:none;'; ?>">
                            <span class="coupon-badge">
                                <strong id="appliedCouponCode"><?php echo $appliedCoupon ? escape($appliedCoupon['code']) : ''; ?></strong>
                                <button type="button" class="coupon-remove" onclick="removeCoupon()" title="Remove coupon">&times;</button>
                            </span>
                            <span class="coupon-discount">-<span id="discountAmount"><?php echo $appliedCoupon ? formatPrice($appliedCoupon['discount']) : '$0.00'; ?></span></span>
                        </div>
                    </div>

                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span id="subtotalAmount"><?php echo formatPrice($cartTotal); ?></span>
                    </div>

                    <div class="summary-row" id="discountRow" style="<?php echo $appliedCoupon ? '' : 'display:none;'; ?>">
                        <span>Discount</span>
                        <span class="discount-value">-<span id="discountRowAmount"><?php echo $appliedCoupon ? formatPrice($appliedCoupon['discount']) : '$0.00'; ?></span></span>
                    </div>

                    <?php if (!$isDigitalOnly): ?>
                    <div class="summary-row" id="shippingRow">
                        <span>Shipping</span>
                        <span id="shippingAmount">--</span>
                    </div>
                    <?php endif; ?>

                    <div class="summary-divider"></div>

                    <div class="summary-row total">
                        <span>Total</span>
                        <span id="orderTotal"><?php echo formatPrice($appliedCoupon ? $cartTotal - $appliedCoupon['discount'] : $cartTotal); ?></span>
                    </div>
                </div>

                <a href="/cart" class="back-to-cart">&larr; Back to Cart</a>

                <!-- Trust Badges -->
                <div class="checkout-trust-badges">
                    <div class="trust-shield-mini">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
                            <path d="M12 2L4 6v6c0 5.5 3.4 10.6 8 12 4.6-1.4 8-6.5 8-12V6l-8-4z" fill="#22c55e"/>
                            <path d="M9 12l2 2 4-4" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <div>
                            <strong>100% Secure</strong>
                            <span>SSL Encrypted</span>
                        </div>
                    </div>
                    <div class="trust-payment-icons">
                        <img src="https://www.paypalobjects.com/webstatic/en_US/i/buttons/cc-badges-ppmcvdam.png" alt="We accept Visa, Mastercard, American Express, Discover, and PayPal" height="28">
                    </div>
                    <div class="checkout-guarantees">
                        <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg> Satisfaction Guaranteed</span>
                        <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> Safe Shopping</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stripe JS -->
<script src="https://js.stripe.com/v3/"></script>
<script>
const stripePublicKey = '<?php echo escape($stripePublicKey); ?>';
const csrfToken = '<?php echo csrfToken(); ?>';
const cartSubtotal = <?php echo $cartTotal; ?>;
const appliedDiscount = <?php echo $appliedCoupon ? $appliedCoupon['discount'] : 0; ?>;
const isDigitalOnly = <?php echo $isDigitalOnly ? 'true' : 'false'; ?>;

let stripe, elements, paymentElement;
let selectedShippingRate = 0;
let selectedShippingMethodId = null;
let paymentInitialized = false;
window.isFreeOrder = false;

// Initialize Stripe (but don't create Payment Intent yet - deferred until Continue is clicked)
document.addEventListener('DOMContentLoaded', async function() {
    // Don't auto-calculate shipping - wait for address to be entered (skip for digital-only orders)
    if (!isDigitalOnly) {
        // Show initial message instead of calculating
        const container = document.getElementById('shippingMethodsContainer');
        container.innerHTML = '<div class="shipping-message-info">Enter your shipping address to see available options</div>';
        // Shipping amount shows "--" until selected
        const shippingAmountEl = document.getElementById('shippingAmount');
        if (shippingAmountEl) {
            shippingAmountEl.textContent = '--';
        }
    } else {
        // For digital orders, set shipping to 0 and enable continue button
        selectedShippingRate = 0;
        selectedShippingMethodId = 'digital';
        document.getElementById('shippingMethodId').value = 'digital';
        updateTotals();
        enableSubmit();
    }

    if (!stripePublicKey) {
        showContinueError('Payment system not configured. Please contact support.');
        return;
    }

    stripe = Stripe(stripePublicKey);
});

// Initialize payment - called when Continue to Payment is clicked
async function initializePayment() {
    const continueBtn = document.getElementById('continueToPaymentBtn');
    const continueError = document.getElementById('continueError');
    continueError.style.display = 'none';

    const email = document.getElementById('email').value.trim();

    // Validate shipping info (only for physical products)
    if (!isDigitalOnly) {
        const firstName = document.getElementById('shipping_first_name').value.trim();
        const lastName = document.getElementById('shipping_last_name').value.trim();
        const address1 = document.getElementById('shipping_address1').value.trim();
        const city = document.getElementById('shipping_city').value.trim();
        const state = document.getElementById('shipping_state').value.trim();
        const postal = document.getElementById('shipping_postal').value.trim();

        if (!email || !firstName || !lastName || !address1 || !city || !state || !postal) {
            showContinueError('Please fill in all required shipping information.');
            return;
        }

        if (!selectedShippingMethodId) {
            showContinueError('Please select a shipping method.');
            return;
        }
    } else {
        // For digital orders, only email is required
        if (!email) {
            showContinueError('Please enter your email address.');
            return;
        }
    }

    // Disable button and show loading
    continueBtn.disabled = true;
    continueBtn.textContent = 'Loading Payment...';

    // Create Payment Intent with shipping info
    try {
        const shippingCountry = document.getElementById('shipping_country').value;

        const response = await fetch('/checkout/create-payment-intent', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: '_csrf_token=' + encodeURIComponent(csrfToken) +
                  '&shipping_method_id=' + encodeURIComponent(selectedShippingMethodId) +
                  '&shipping_country=' + encodeURIComponent(shippingCountry)
        });

        const data = await response.json();

        if (data.error) {
            showContinueError(data.error);
            continueBtn.disabled = false;
            continueBtn.textContent = 'Continue to Payment';
            return;
        }

        // Handle free orders - no payment needed
        if (data.freeOrder) {
            document.getElementById('paymentIntentId').value = '';

            // Hide Continue section, show free order message
            document.getElementById('continueToPaymentSection').style.display = 'none';
            document.getElementById('paymentSection').style.display = 'block';

            // Create free order notice using safe DOM methods
            const paymentEl = document.getElementById('payment-element');
            paymentEl.textContent = '';
            const freeNotice = document.createElement('div');
            freeNotice.style.cssText = 'padding: 1.5rem; background: #d4edda; border-radius: 8px; text-align: center;';
            const strongEl = document.createElement('strong');
            strongEl.style.color = '#155724';
            strongEl.textContent = 'Free Order';
            const pEl = document.createElement('p');
            pEl.style.cssText = 'color: #155724; margin: 0.5rem 0 0;';
            pEl.textContent = 'No payment required. Click below to complete your order.';
            freeNotice.appendChild(strongEl);
            freeNotice.appendChild(pEl);
            paymentEl.appendChild(freeNotice);

            document.getElementById('submitBtn').style.display = 'block';
            document.getElementById('submitBtn').textContent = 'Complete Free Order';

            window.isFreeOrder = true;
            paymentInitialized = true;
            enableSubmit();
            return;
        }

        document.getElementById('paymentIntentId').value = data.paymentIntentId;

        // Create Stripe Elements
        elements = stripe.elements({
            clientSecret: data.clientSecret,
            appearance: {
                theme: 'stripe',
                variables: {
                    colorPrimary: '#FF68C5',
                    colorBackground: '#ffffff',
                    colorText: '#333333',
                    colorDanger: '#dc3545',
                    fontFamily: 'Inter, system-ui, sans-serif',
                    borderRadius: '8px'
                }
            }
        });

        paymentElement = elements.create('payment', {
            layout: 'tabs'
        });
        paymentElement.mount('#payment-element');

        // Hide Continue section, show Payment section and Submit button
        document.getElementById('continueToPaymentSection').style.display = 'none';
        document.getElementById('paymentSection').style.display = 'block';
        document.getElementById('submitBtn').style.display = 'block';

        window.isFreeOrder = false;
        paymentInitialized = true;
        enableSubmit();

    } catch (error) {
        console.error('Error:', error);
        showContinueError('Failed to initialize payment. Please try again.');
        continueBtn.disabled = false;
        continueBtn.textContent = 'Continue to Payment';
    }
}

function showContinueError(message) {
    const continueError = document.getElementById('continueError');
    continueError.textContent = message;
    continueError.style.display = 'block';
}

// Update shipping rates when country changes
async function updateShippingRates() {
    // Block changes after payment is initialized (Stripe protection)
    if (paymentInitialized) {
        alert('Please refresh the page to change your shipping address after payment form is loaded.');
        return;
    }

    const country = document.getElementById('shipping_country').value;
    const state = document.getElementById('shipping_state').value;
    const container = document.getElementById('shippingMethodsContainer');
    const errorEl = document.getElementById('shippingError');
    const progressEl = document.getElementById('freeShippingProgress');
    const customsNotice = document.getElementById('customsNotice');

    // Don't calculate if no country selected
    if (!country) {
        container.innerHTML = '<div class="shipping-message-info">Enter your shipping address to see available options</div>';
        const shippingAmountEl = document.getElementById('shippingAmount');
        if (shippingAmountEl) {
            shippingAmountEl.textContent = '--';
        }
        disableSubmit();
        return;
    }

    // Show customs notice for non-US countries
    if (customsNotice) {
        customsNotice.style.display = (country !== 'US') ? 'block' : 'none';
    }

    container.innerHTML = '<div class="shipping-loading"><span class="spinner"></span> Calculating shipping options...</div>';
    errorEl.style.display = 'none';
    progressEl.style.display = 'none';

    try {
        const response = await fetch('/shipping/rates', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'country=' + encodeURIComponent(country) + '&state=' + encodeURIComponent(state)
        });

        const data = await response.json();

        if (!data.success) {
            container.innerHTML = '';
            errorEl.textContent = data.error || 'Unable to calculate shipping for this location.';
            errorEl.style.display = 'block';
            disableSubmit();
            return;
        }

        if (data.options.length === 0) {
            container.innerHTML = '<p class="no-shipping">No shipping options available for this location.</p>';
            disableSubmit();
            return;
        }

        // Show free shipping progress if applicable
        if (data.amount_until_free && data.amount_until_free > 0) {
            const threshold = data.free_shipping_threshold;
            const progress = ((cartSubtotal - appliedDiscount) / threshold) * 100;
            document.getElementById('freeShippingBar').style.width = Math.min(progress, 100) + '%';
            document.getElementById('freeShippingMessage').innerHTML = 'Add <strong>$' + data.amount_until_free.toFixed(2) + '</strong> more for FREE shipping!';
            progressEl.style.display = 'block';
        } else if (data.free_shipping_threshold && (cartSubtotal - appliedDiscount) >= data.free_shipping_threshold) {
            document.getElementById('freeShippingBar').style.width = '100%';
            document.getElementById('freeShippingMessage').innerHTML = 'You qualify for <strong>FREE shipping!</strong>';
            progressEl.style.display = 'block';
        }

        // Filter express shipping for non-US countries
        const isUS = country === 'US';
        const filteredOptions = isUS ? data.options : data.options.filter(opt => {
            const name = (opt.name || '').toLowerCase();
            return !name.includes('express');
        });

        if (filteredOptions.length === 0) {
            container.innerHTML = '<p class="no-shipping">No shipping options available for this location.</p>';
            disableSubmit();
            return;
        }

        // Render shipping options
        let html = '<div class="shipping-options">';
        filteredOptions.forEach((option, index) => {
            const checked = index === 0 ? 'checked' : '';
            const rateText = option.is_free ? 'FREE' : '$' + option.rate.toFixed(2);
            const freeNote = option.min_order_free && !option.is_free ?
                '<span class="free-threshold">(Free on orders $' + parseFloat(option.min_order_free).toFixed(0) + '+)</span>' : '';

            html += `
                <label class="shipping-option ${checked ? 'selected' : ''}">
                    <input type="radio" name="shipping_option" value="${option.method_id}"
                           data-rate="${option.rate}" data-free="${option.is_free ? 1 : 0}"
                           ${checked} onchange="selectShippingOption(this)">
                    <div class="shipping-option-content">
                        <div class="shipping-option-name">
                            <strong>${option.name}</strong>
                            ${freeNote}
                        </div>
                        <div class="shipping-option-estimate">${option.delivery_estimate || ''}</div>
                    </div>
                    <div class="shipping-option-price ${option.is_free ? 'free' : ''}">${rateText}</div>
                </label>
            `;
        });
        html += '</div>';

        container.innerHTML = html;

        // Select first option by default
        const firstOption = container.querySelector('input[name="shipping_option"]');
        if (firstOption) {
            selectShippingOption(firstOption);
        }

    } catch (error) {
        console.error('Shipping error:', error);
        container.innerHTML = '';
        errorEl.textContent = 'Failed to calculate shipping. Please try again.';
        errorEl.style.display = 'block';
        disableSubmit();
    }
}

function selectShippingOption(input) {
    // Block changes after payment is initialized (Stripe protection)
    if (paymentInitialized) {
        alert('Please refresh the page to change shipping method after payment form is loaded.');
        // Reset to previously selected option
        const prevSelected = document.querySelector('.shipping-option.selected input');
        if (prevSelected) {
            prevSelected.checked = true;
        }
        return;
    }

    // Update selected class
    document.querySelectorAll('.shipping-option').forEach(opt => opt.classList.remove('selected'));
    input.closest('.shipping-option').classList.add('selected');

    // Update shipping rate
    selectedShippingRate = input.dataset.free === '1' ? 0 : parseFloat(input.dataset.rate);
    selectedShippingMethodId = input.value;

    document.getElementById('shippingMethodId').value = selectedShippingMethodId;

    // Update display - directly update shipping amount and total
    const shippingAmountEl = document.getElementById('shippingAmount');
    if (shippingAmountEl) {
        const shippingText = selectedShippingRate === 0 ? 'FREE' : '$' + selectedShippingRate.toFixed(2);
        shippingAmountEl.textContent = shippingText;
    }

    const total = cartSubtotal - appliedDiscount + selectedShippingRate;
    document.getElementById('orderTotal').textContent = '$' + total.toFixed(2);
    document.getElementById('buttonText').textContent = 'Place Order - $' + total.toFixed(2);

    enableSubmit();
}

function updateTotals() {
    // Only update shipping display for physical products
    const shippingAmountEl = document.getElementById('shippingAmount');
    if (shippingAmountEl) {
        const shippingText = selectedShippingRate === 0 ? 'FREE' : '$' + selectedShippingRate.toFixed(2);
        shippingAmountEl.textContent = shippingText;
    }

    const total = cartSubtotal - appliedDiscount + selectedShippingRate;
    document.getElementById('orderTotal').textContent = '$' + total.toFixed(2);
    document.getElementById('buttonText').textContent = 'Place Order - $' + total.toFixed(2);
}

function enableSubmit() {
    // Only enable submit button if payment has been initialized
    if (paymentInitialized) {
        document.getElementById('submitBtn').disabled = false;
    }
    // Always enable Continue button if shipping is selected
    document.getElementById('continueToPaymentBtn').disabled = false;
}

function disableSubmit() {
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('continueToPaymentBtn').disabled = true;
    const shippingAmountEl = document.getElementById('shippingAmount');
    if (shippingAmountEl) {
        shippingAmountEl.textContent = '--';
    }
}

// Handle form submission
document.getElementById('checkoutForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    if (!paymentInitialized) {
        showMessage('Please click "Continue to Payment" first.');
        return;
    }

    if (!isDigitalOnly && !selectedShippingMethodId) {
        showMessage('Please select a shipping method.');
        return;
    }

    setLoading(true);

    // Validate form
    const email = document.getElementById('email').value;

    if (!isDigitalOnly) {
        // Validate shipping fields for physical products
        const firstName = document.getElementById('shipping_first_name').value;
        const lastName = document.getElementById('shipping_last_name').value;
        const address1 = document.getElementById('shipping_address1').value;
        const city = document.getElementById('shipping_city').value;
        const state = document.getElementById('shipping_state').value;
        const postal = document.getElementById('shipping_postal').value;

        if (!email || !firstName || !lastName || !address1 || !city || !state || !postal) {
            showMessage('Please fill in all required fields.');
            setLoading(false);
            return;
        }
    } else {
        // For digital orders, only email is required
        if (!email) {
            showMessage('Please enter your email address.');
            setLoading(false);
            return;
        }
    }

    // Handle free orders - skip Stripe confirmation
    if (window.isFreeOrder) {
        const formData = new FormData(document.getElementById('checkoutForm'));

        try {
            const response = await fetch('/checkout/process', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                window.location.href = data.redirectUrl || '/checkout/confirm';
            } else {
                showMessage(data.error || 'Failed to process order');
                setLoading(false);
            }
        } catch (err) {
            console.error('Error:', err);
            showMessage('An error occurred. Please try again.');
            setLoading(false);
        }
        return;
    }

    // Confirm payment with Stripe
    const { error, paymentIntent } = await stripe.confirmPayment({
        elements,
        confirmParams: {
            return_url: window.location.origin + '/checkout/confirm',
            receipt_email: email,
        },
        redirect: 'if_required'
    });

    if (error) {
        showMessage(error.message);
        setLoading(false);
        return;
    }

    if (paymentIntent && paymentIntent.status === 'succeeded') {
        // Payment successful, submit the form to create the order
        const formData = new FormData(document.getElementById('checkoutForm'));

        try {
            const response = await fetch('/checkout/process', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                window.location.href = data.redirectUrl || '/checkout/confirm';
            } else {
                showMessage(data.error || 'Failed to process order');
                setLoading(false);
            }
        } catch (err) {
            showMessage('Failed to process order. Please contact support.');
            setLoading(false);
        }
    }
});

function setLoading(isLoading) {
    const btn = document.getElementById('submitBtn');
    const btnText = document.getElementById('buttonText');
    const spinner = document.getElementById('spinner');

    btn.disabled = isLoading;
    btnText.style.display = isLoading ? 'none' : 'inline';
    spinner.style.display = isLoading ? 'inline-block' : 'none';
}

function showMessage(message) {
    const msgEl = document.getElementById('payment-message');
    msgEl.textContent = message;
    msgEl.style.display = 'block';
}

function toggleBillingAddress() {
    const same = document.getElementById('billing_same').checked;
    document.getElementById('billingAddressFields').style.display = same ? 'none' : 'block';
}

function fillSavedAddress(id) {
    if (!id) return;

    const select = document.getElementById('saved_address');
    const option = select.options[select.selectedIndex];

    document.getElementById('shipping_first_name').value = option.dataset.first || '';
    document.getElementById('shipping_last_name').value = option.dataset.last || '';
    document.getElementById('shipping_address1').value = option.dataset.addr1 || '';
    document.getElementById('shipping_address2').value = option.dataset.addr2 || '';
    document.getElementById('shipping_city').value = option.dataset.city || '';
    document.getElementById('shipping_state').value = option.dataset.state || '';
    document.getElementById('shipping_postal').value = option.dataset.postal || '';
    document.getElementById('shipping_country').value = option.dataset.country || 'US';
    document.getElementById('shipping_phone').value = option.dataset.phone || '';

    // Update shipping rates for new country
    updateShippingRates();
}

// Coupon functions
async function applyCoupon() {
    const code = document.getElementById('couponCode').value.trim();
    const errorEl = document.getElementById('couponError');
    const btn = document.getElementById('applyCouponBtn');

    if (!code) {
        errorEl.textContent = 'Please enter a coupon code';
        errorEl.style.display = 'block';
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Applying...';
    errorEl.style.display = 'none';

    try {
        const response = await fetch('/checkout/apply-coupon', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: '_csrf_token=' + encodeURIComponent(csrfToken) + '&coupon_code=' + encodeURIComponent(code)
        });

        const data = await response.json();

        if (data.success) {
            // Reload page to recalculate shipping with new subtotal
            window.location.reload();
        } else {
            errorEl.textContent = data.error || 'Invalid coupon code';
            errorEl.style.display = 'block';
        }
    } catch (error) {
        errorEl.textContent = 'Failed to apply coupon. Please try again.';
        errorEl.style.display = 'block';
    }

    btn.disabled = false;
    btn.textContent = 'Apply';
}

async function removeCoupon() {
    try {
        const response = await fetch('/checkout/remove-coupon', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: '_csrf_token=' + encodeURIComponent(csrfToken)
        });

        const data = await response.json();

        if (data.success) {
            // Reload page to recalculate shipping
            window.location.reload();
        }
    } catch (error) {
        console.error('Failed to remove coupon:', error);
    }
}
</script>
