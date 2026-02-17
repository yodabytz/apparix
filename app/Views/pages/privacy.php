<!-- Privacy Policy Page -->
<section class="legal-page">
    <div class="container">
        <h1>Privacy Policy</h1>
        <p class="last-updated">Last updated: <?php echo date('F j, Y'); ?></p>

        <div class="legal-content">
            <h2>1. Introduction</h2>
            <p>Welcome to <?php echo escape(appName()); ?> ("we," "our," or "us"). We respect your privacy and are committed to protecting your personal information. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website and make purchases from us.</p>

            <h2>2. Information We Collect</h2>
            <h3>Personal Information</h3>
            <p>We collect information you provide directly to us, including:</p>
            <ul>
                <li>Name and contact information (email address, phone number, shipping and billing addresses)</li>
                <li>Account credentials (if you create an account)</li>
                <li>Payment information (processed securely through Stripe)</li>
                <li>Order history and preferences</li>
                <li>Communications with us (emails, contact form submissions)</li>
            </ul>

            <h3>Automatically Collected Information</h3>
            <p>When you visit our website, we may automatically collect:</p>
            <ul>
                <li>Device information (browser type, operating system)</li>
                <li>IP address and approximate location</li>
                <li>Pages visited and time spent on our site</li>
                <li>Referring website or source</li>
            </ul>

            <h2>3. How We Use Your Information</h2>
            <p>We use the information we collect to:</p>
            <ul>
                <li>Process and fulfill your orders</li>
                <li>Send order confirmations and shipping updates</li>
                <li>Respond to your inquiries and provide customer support</li>
                <li>Send promotional emails and newsletters (with your consent)</li>
                <li>Improve our website and services</li>
                <li>Detect and prevent fraud</li>
                <li>Comply with legal obligations</li>
            </ul>

            <h2>4. Information Sharing</h2>
            <p>We do not sell, trade, or rent your personal information to third parties. We may share your information with:</p>
            <ul>
                <li><strong>Service Providers:</strong> Companies that help us operate our business (payment processors, shipping carriers)</li>
                <li><strong>Legal Requirements:</strong> When required by law or to protect our rights</li>
            </ul>

            <h2>5. Payment Security</h2>
            <p>All payment transactions are processed through Stripe, a PCI-DSS compliant payment processor. We do not store your complete credit card information on our servers. Stripe's privacy policy can be found at <a href="https://stripe.com/privacy" target="_blank" rel="noopener">stripe.com/privacy</a>.</p>

            <h2>6. Cookies</h2>
            <p>We use cookies and similar technologies to:</p>
            <ul>
                <li>Keep you logged in to your account</li>
                <li>Remember items in your shopping cart</li>
                <li>Analyze website traffic and usage</li>
            </ul>
            <p>You can control cookies through your browser settings, though disabling cookies may limit your ability to use some features of our website.</p>

            <h2>7. Your Rights</h2>
            <p>You have the right to:</p>
            <ul>
                <li>Access the personal information we hold about you</li>
                <li>Request correction of inaccurate information</li>
                <li>Request deletion of your personal information</li>
                <li>Opt out of marketing communications</li>
                <li>Unsubscribe from our newsletter at any time</li>
            </ul>

            <h2>8. Data Retention</h2>
            <p>We retain your personal information for as long as necessary to fulfill the purposes outlined in this policy, unless a longer retention period is required by law. Order records are retained for accounting and legal purposes.</p>

            <h2>9. Children's Privacy</h2>
            <p>Our website is not intended for children under 13 years of age. We do not knowingly collect personal information from children under 13.</p>

            <h2>10. Changes to This Policy</h2>
            <p>We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new policy on this page and updating the "Last updated" date.</p>

            <h2>11. Contact Us</h2>
            <p>If you have questions about this Privacy Policy or our privacy practices, please contact us:</p>
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
