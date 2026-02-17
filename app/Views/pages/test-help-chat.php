<!-- Help Chat Test Page -->
<section class="test-page">
    <div class="container">
        <h1>Help Chat Widget Test</h1>
        <p class="test-intro">This page is for testing the help chat widget before enabling it on the live site.</p>

        <div class="test-instructions">
            <h2>How to Test</h2>
            <ol>
                <li>Click the pink chat bubble in the bottom-right corner</li>
                <li>Try asking questions like:
                    <ul>
                        <li>"How long does shipping take?"</li>
                        <li>"What's your return policy?"</li>
                        <li>"Where is my order?"</li>
                        <li>"What sizes are available?"</li>
                        <li>"I need help" (to test the email form)</li>
                    </ul>
                </li>
                <li>Test the email form by clicking "yes" when asked to connect with support</li>
                <li>Enter a test email and message, then click "Send to Support"</li>
            </ol>

            <h2>What Happens When Email is Sent</h2>
            <ul>
                <li>An email is sent to <?php echo escape(storeEmail()); ?></li>
                <li>A confirmation email is sent to the customer</li>
                <li>Rate limiting prevents spam (1 email per minute)</li>
            </ul>
        </div>

        <div class="test-status">
            <h2>API Endpoint Status</h2>
            <p id="apiStatus">Checking...</p>
        </div>
    </div>
</section>

<?php include dirname(__DIR__) . '/partials/help-chat.php'; ?>

<style>
.test-page {
    padding: 60px 0;
    min-height: 80vh;
}

.test-page h1 {
    margin-bottom: 10px;
}

.test-intro {
    color: #666;
    margin-bottom: 40px;
    font-size: 1.1rem;
}

.test-instructions {
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    max-width: 700px;
    margin-bottom: 30px;
}

.test-instructions h2 {
    margin: 0 0 16px;
    font-size: 1.2rem;
    color: #FF68C5;
}

.test-instructions ol,
.test-instructions ul {
    margin: 0 0 20px 20px;
    line-height: 1.8;
}

.test-instructions ul ul {
    margin-top: 8px;
    margin-bottom: 8px;
}

.test-status {
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    max-width: 700px;
}

.test-status h2 {
    margin: 0 0 16px;
    font-size: 1.2rem;
    color: #FF68C5;
}

.status-ok { color: #28a745; }
.status-error { color: #dc3545; }
</style>

<script>
(function() {
    var statusEl = document.getElementById('apiStatus');

    // Test the API endpoint
    fetch('/api/support-chat', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: '', message: '' })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        // Expecting an error about empty fields - that means the endpoint is working
        if (data.error && data.error.indexOf('fill in') !== -1) {
            statusEl.textContent = 'API endpoint is working correctly';
            statusEl.className = 'status-ok';
        } else {
            statusEl.textContent = 'Unexpected response: ' + JSON.stringify(data);
            statusEl.className = 'status-error';
        }
    })
    .catch(function(err) {
        statusEl.textContent = 'API endpoint not responding: ' + err.message;
        statusEl.className = 'status-error';
    });
})();
</script>
