<!-- Terms of Service Page -->
<section class="legal-page">
    <div class="container">
        <h1>Terms of Service</h1>
        <p class="last-updated">Last updated: <?php echo date('F j, Y'); ?></p>

        <div class="legal-content">
            <h2>1. Agreement to Terms</h2>
            <p>By accessing and using the <?php echo escape(appName()); ?> website, you agree to be bound by these Terms of Service. If you do not agree to these terms, please do not use our website.</p>

            <h2>2. Products and Services</h2>
            <h3>Product Descriptions</h3>
            <p>We make every effort to display our products as accurately as possible. However, colors and details may appear differently depending on your device settings. Product dimensions and descriptions are approximate.</p>

            <h3>Handmade Nature</h3>
            <p>Many of our products are handmade or customized. This means each item is unique and may have slight variations from photos shown. These variations are part of the handmade charm and are not considered defects.</p>

            <h3>Customization</h3>
            <p>For custom orders, please carefully review all details before placing your order. Custom items are made specifically for you and may have different return policies (see Returns section).</p>

            <h2>3. Ordering and Payment</h2>
            <h3>Pricing</h3>
            <p>All prices are listed in US dollars and are subject to change without notice. Prices do not include shipping costs or applicable taxes, which will be calculated at checkout.</p>

            <h3>Payment</h3>
            <p>We accept payment through Stripe, which supports major credit cards and other payment methods. By providing payment information, you confirm you are authorized to use the payment method.</p>

            <h3>Order Confirmation</h3>
            <p>After placing an order, you will receive an email confirmation. This email confirms we received your order but does not guarantee product availability. We reserve the right to cancel orders if items are unavailable.</p>

            <h2>4. Shipping</h2>
            <h3>Processing Time</h3>
            <p>Orders are typically processed within 1-3 business days. Handmade or custom items may require additional processing time, which will be noted on the product page.</p>

            <h3>Shipping Methods</h3>
            <p>We ship via USPS and other carriers. Shipping times vary by destination and selected shipping method. We are not responsible for delays caused by the carrier or customs (for international orders).</p>

            <h3>International Orders</h3>
            <p>Orders shipped outside the United States may be subject to customs duties, import taxes, VAT, or other fees imposed by the destination country. These charges are determined by your country's customs authority upon arrival and are the sole responsibility of the buyer.</p>
            <p>We have no control over these charges and cannot predict their amount. We recommend contacting your local customs office for more information before placing an international order. Refusal to pay customs charges does not entitle you to a refund.</p>

            <h3>Lost or Damaged Packages</h3>
            <p>Please contact us if your package is lost or arrives damaged. We will work with you to resolve the issue, which may include filing a claim with the carrier or sending a replacement.</p>

            <h2>5. Returns and Refunds</h2>
            <h3>Standard Items</h3>
            <p>We accept returns of unused, undamaged items in original packaging within 14 days of delivery. Contact us to initiate a return. Customer pays return shipping unless the item was defective or we made an error.</p>

            <h3>Custom Items</h3>
            <p>Custom-made items are final sale and cannot be returned unless defective. Please review all customization details carefully before ordering.</p>

            <h3>Refunds</h3>
            <p>Refunds will be issued to the original payment method within 5-7 business days after we receive the returned item.</p>

            <h2>6. Intellectual Property</h2>
            <p>All content on this website, including images, text, designs, and logos, is the property of <?php echo escape(appName()); ?> and is protected by copyright and trademark laws. You may not use, reproduce, or distribute our content without written permission.</p>

            <h2>7. User Accounts</h2>
            <p>When you create an account, you are responsible for maintaining the security of your login credentials. You are responsible for all activities that occur under your account. Notify us immediately if you suspect unauthorized access.</p>

            <h2>8. Limitation of Liability</h2>
            <p>To the fullest extent permitted by law, <?php echo escape(appName()); ?> shall not be liable for any indirect, incidental, special, consequential, or punitive damages arising from your use of our website or products. Our total liability shall not exceed the amount you paid for the product in question.</p>

            <h2>9. Indemnification</h2>
            <p>You agree to indemnify and hold harmless <?php echo escape(appName()); ?> from any claims, damages, losses, or expenses arising from your use of our website or violation of these terms.</p>

            <h2>10. Governing Law</h2>
            <p>These Terms of Service shall be governed by and construed in accordance with the laws of the United States, without regard to conflict of law principles.</p>

            <h2>11. Changes to Terms</h2>
            <p>We reserve the right to modify these terms at any time. Changes will be effective immediately upon posting to the website. Your continued use of the website after changes constitutes acceptance of the new terms.</p>

            <h2>12. Contact Information</h2>
            <p>For questions about these Terms of Service, please contact us:</p>
            <ul>
                <li>Email: <a href="mailto:<?php echo escape(storeEmail()); ?>"><?php echo escape(storeEmail()); ?></a></li>
                <li>Contact Form: <a href="/contact"><?php echo appUrl(); ?>/contact</a></li>
            </ul>
        </div>
    </div>
</section>

<style>
.legal-page {
    padding: 60px 0;
}

.legal-page h1 {
    margin-bottom: 10px;
}

.last-updated {
    color: #666;
    margin-bottom: 40px;
    font-style: italic;
}

.legal-content {
    max-width: 800px;
    background: #fff;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}

.legal-content h2 {
    font-size: 1.4rem;
    margin-top: 30px;
    margin-bottom: 15px;
    color: #333;
    padding-bottom: 8px;
    border-bottom: 2px solid #FF68C5;
}

.legal-content h2:first-child {
    margin-top: 0;
}

.legal-content h3 {
    font-size: 1.1rem;
    margin-top: 20px;
    margin-bottom: 10px;
    color: #444;
}

.legal-content p {
    color: #555;
    line-height: 1.7;
    margin-bottom: 15px;
}

.legal-content ul {
    margin-bottom: 15px;
    padding-left: 25px;
}

.legal-content li {
    color: #555;
    line-height: 1.7;
    margin-bottom: 8px;
}

.legal-content a {
    color: #FF68C5;
}

.legal-content a:hover {
    text-decoration: underline;
}
</style>
