<div class="card">
    <h1 class="card-title">Store Information</h1>
    <p class="card-description">Tell us about your store.</p>

    <form method="POST" action="/install?step=3">
        <div class="form-group">
            <label for="store_name">Store Name *</label>
            <input type="text" name="store_name" id="store_name" class="form-control"
                   value="<?php echo htmlspecialchars($_SESSION['install']['store_name'] ?? ''); ?>"
                   placeholder="My Awesome Store" required>
            <span class="form-help">This will appear in the header and page titles</span>
        </div>

        <div class="form-group">
            <label for="store_url">Store URL</label>
            <input type="url" name="store_url" id="store_url" class="form-control"
                   value="<?php echo htmlspecialchars($_SESSION['install']['store_url'] ?? ''); ?>"
                   placeholder="https://mystore.com">
            <span class="form-help">The full URL where your store will be accessible</span>
        </div>

        <div class="form-group">
            <label for="store_email">Contact Email</label>
            <input type="email" name="store_email" id="store_email" class="form-control"
                   value="<?php echo htmlspecialchars($_SESSION['install']['store_email'] ?? ''); ?>"
                   placeholder="contact@mystore.com">
            <span class="form-help">For customer inquiries and order notifications</span>
        </div>

        <div class="form-actions">
            <a href="/install?step=2" class="btn btn-secondary">Back</a>
            <button type="submit" class="btn btn-primary btn-lg">Continue</button>
        </div>
    </form>
</div>
