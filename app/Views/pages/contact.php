<!-- Contact Page -->
<section class="contact-page">
    <div class="container">
        <h1>Contact Us</h1>
        <p class="contact-intro">Have a question, feedback, or just want to say hello? We'd love to hear from you!</p>

        <?php if ($error = getFlash('error')): ?>
            <div class="alert alert-error"><?php echo escape($error); ?></div>
        <?php endif; ?>

        <?php if ($success = getFlash('success')): ?>
            <div class="alert alert-success"><?php echo escape($success); ?></div>
        <?php endif; ?>

        <div class="contact-grid">
            <div class="contact-form-section">
                <h2>Send Us a Message</h2>
                <form action="/contact" method="POST" class="contact-form" id="contactForm">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="recaptcha_token" id="contact_recaptcha_token">

                    <div class="form-group">
                        <label for="name">Your Name *</label>
                        <input type="text" id="name" name="name" required placeholder="Jane Doe">
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required placeholder="your@email.com">
                    </div>

                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <select id="subject" name="subject">
                            <option value="">Select a topic...</option>
                            <option value="General Inquiry">General Inquiry</option>
                            <option value="Order Question">Order Question</option>
                            <option value="Custom Order Request">Custom Order Request</option>
                            <option value="Return/Exchange">Return or Exchange</option>
                            <option value="Feedback">Feedback</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" required rows="6" placeholder="Tell us what's on your mind..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-large">Send Message</button>
                </form>

                <script>
                (function() {
                    const siteKey = '<?php echo \App\Core\ReCaptcha::getSiteKey(); ?>';
                    const form = document.getElementById('contactForm');
                    const tokenInput = document.getElementById('contact_recaptcha_token');
                    let tokenReady = false;

                    function getToken() {
                        if (!siteKey) return;

                        // Wait for grecaptcha to load
                        if (typeof grecaptcha === 'undefined') {
                            setTimeout(getToken, 100);
                            return;
                        }

                        grecaptcha.ready(function() {
                            grecaptcha.execute(siteKey, {action: 'contact'}).then(function(token) {
                                tokenInput.value = token;
                                tokenReady = true;
                            }).catch(function(err) {
                                console.error('reCAPTCHA error:', err);
                            });
                        });
                    }

                    // Get token when page loads
                    getToken();

                    // Refresh token every 90 seconds (tokens expire after 2 min)
                    setInterval(getToken, 90000);

                    // Validate before submit
                    form.addEventListener('submit', function(e) {
                        if (!tokenInput.value) {
                            e.preventDefault();
                            alert('Please wait a moment for security verification to complete, then try again.');
                            getToken();
                        }
                    });
                })();
                </script>
            </div>

            <div class="contact-info-section">
                <h2>Other Ways to Reach Us</h2>

                <div class="contact-info-card">
                    <div class="info-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                    </div>
                    <div class="info-content">
                        <h3>Email</h3>
                        <p><a href="mailto:<?php echo escape(storeEmail()); ?>"><?php echo escape(storeEmail()); ?></a></p>
                        <p class="info-note">We typically respond within 24-48 hours</p>
                    </div>
                </div>

                <div class="contact-info-card">
                    <div class="info-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    <div class="info-content">
                        <h3>Business Hours</h3>
                        <p>Monday - Friday: 9am - 5pm EST</p>
                        <p class="info-note">Orders are processed within business hours</p>
                    </div>
                </div>

                <div class="faq-teaser">
                    <h3>Frequently Asked Questions</h3>
                    <ul>
                        <li><strong>How long does shipping take?</strong><br>
                            Standard shipping is 5-7 business days within the US.</li>
                        <li><strong>Do you offer custom orders?</strong><br>
                            Yes! Contact us with your ideas and we'll work together.</li>
                        <li><strong>What's your return policy?</strong><br>
                            We accept returns within 14 days. See our <a href="/terms">Terms</a> for details.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.contact-page {
    padding: 60px 0;
}

.contact-page h1 {
    margin-bottom: 10px;
}

.contact-intro {
    color: #666;
    margin-bottom: 40px;
    font-size: 1.1rem;
}

.contact-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
}

.contact-form-section,
.contact-info-section {
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}

.contact-form-section h2,
.contact-info-section h2 {
    margin-bottom: 24px;
    font-size: 1.3rem;
}

.contact-form .form-group {
    margin-bottom: 20px;
}

.contact-form label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    color: #333;
}

.contact-form input[type="text"],
.contact-form input[type="email"],
.contact-form select,
.contact-form textarea {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
    font-family: inherit;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.contact-form input:focus,
.contact-form select:focus,
.contact-form textarea:focus {
    outline: none;
    border-color: #FF68C5;
    box-shadow: 0 0 0 3px rgba(255, 104, 197, 0.1);
}

.contact-form textarea {
    resize: vertical;
    min-height: 120px;
}

.contact-info-card {
    display: flex;
    gap: 16px;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 8px;
    margin-bottom: 16px;
}

.info-icon {
    flex-shrink: 0;
    width: 48px;
    height: 48px;
    background: #FF68C5;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.info-content h3 {
    margin: 0 0 4px;
    font-size: 1rem;
}

.info-content p {
    margin: 0;
    color: #555;
}

.info-content a {
    color: #FF68C5;
    text-decoration: none;
}

.info-content a:hover {
    text-decoration: underline;
}

.info-note {
    font-size: 0.85rem;
    color: #888 !important;
    margin-top: 4px !important;
}

.faq-teaser {
    margin-top: 30px;
    padding-top: 24px;
    border-top: 1px solid #eee;
}

.faq-teaser h3 {
    margin-bottom: 16px;
    font-size: 1.1rem;
}

.faq-teaser ul {
    list-style: none;
    padding: 0;
}

.faq-teaser li {
    padding: 12px 0;
    border-bottom: 1px solid #eee;
    color: #555;
    line-height: 1.5;
}

.faq-teaser li:last-child {
    border-bottom: none;
}

.faq-teaser strong {
    color: #333;
}

.faq-teaser a {
    color: #FF68C5;
}

.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    max-width: 800px;
}

.alert-error {
    background: #fee;
    color: #c00;
    border: 1px solid #fcc;
}

.alert-success {
    background: #efe;
    color: #060;
    border: 1px solid #cfc;
}

@media (max-width: 768px) {
    .contact-grid {
        grid-template-columns: 1fr;
    }
}
</style>
