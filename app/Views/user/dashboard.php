<!-- Account Dashboard -->
<section class="account-section">
    <div class="container">
        <h1>My Account</h1>

        <?php if ($error = getFlash('error')): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success = getFlash('success')): ?>
            <div class="alert alert-success"><?php echo escape($success); ?></div>
        <?php endif; ?>

        <div class="account-grid">
            <!-- Profile Section -->
            <div class="account-card">
                <h2>Profile Information</h2>

                <!-- Avatar Upload -->
                <div class="avatar-upload-area">
                    <div class="avatar-preview" id="avatarPreview">
                        <?php if (!empty($user['avatar_path'])): ?>
                            <img src="<?php echo escape($user['avatar_path']); ?>" alt="Avatar" class="avatar-img" id="avatarImg">
                        <?php else: ?>
                            <span class="avatar-initial" id="avatarInitial"><?php echo strtoupper(substr($user['first_name'] ?? $user['email'], 0, 1)); ?></span>
                        <?php endif; ?>
                        <div class="avatar-overlay" id="avatarOverlay">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/></svg>
                        </div>
                    </div>
                    <input type="file" id="avatarInput" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none;">
                    <?php if (!empty($user['avatar_path'])): ?>
                        <button type="button" class="avatar-remove-btn" id="avatarRemoveBtn">Remove</button>
                    <?php endif; ?>
                </div>

                <form action="/account/update-profile" method="POST" class="account-form">
                    <?php echo csrfField(); ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name"
                                   value="<?php echo escape($user['first_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name"
                                   value="<?php echo escape($user['last_name'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" value="<?php echo escape($user['email']); ?>" disabled>
                        <small>Contact support to change your email address</small>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone"
                               value="<?php echo escape($user['phone'] ?? ''); ?>"
                               placeholder="(555) 123-4567">
                    </div>

                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" name="newsletter" value="1"
                                <?php echo !empty($user['newsletter_subscribed']) ? 'checked' : ''; ?>>
                            Subscribe to newsletter
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>
            </div>

            <!-- Password Section -->
            <div class="account-card">
                <h2>Change Password</h2>
                <form action="/account/change-password" method="POST" class="account-form">
                    <?php echo csrfField(); ?>

                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required minlength="8">
                        <small>At least 8 characters</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>

                    <button type="submit" class="btn btn-outline">Change Password</button>
                </form>
            </div>

            <!-- My Downloads Section (only if user has digital purchases) -->
            <?php
            $downloadModel = new \App\Models\OrderDownload();
            $userDownloads = $downloadModel->getByUserId(auth()['id']);
            ?>
            <?php if (!empty($userDownloads)): ?>
            <div class="account-card full-width">
                <div class="card-header">
                    <h2>My Downloads</h2>
                    <a href="/account/downloads" class="view-all">View All Downloads</a>
                </div>
                <div class="downloads-summary">
                    <p><?php echo count($userDownloads); ?> digital product<?php echo count($userDownloads) !== 1 ? 's' : ''; ?> available for download.</p>
                    <a href="/account/downloads" class="btn btn-primary">Go to Downloads</a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Orders Section -->
            <div class="account-card full-width">
                <div class="card-header">
                    <h2>Recent Orders</h2>
                    <?php if (!empty($recentOrders)): ?>
                        <a href="/account/orders" class="view-all">View All Orders</a>
                    <?php endif; ?>
                </div>

                <?php if (empty($recentOrders)): ?>
                    <div class="empty-state">
                        <p>You haven't placed any orders yet.</p>
                        <a href="/products" class="btn btn-primary">Start Shopping</a>
                    </div>
                <?php else: ?>
                    <div class="orders-table-wrapper">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td class="order-number"><?php echo escape($order['order_number']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                        <td><?php echo $order['item_count']; ?> item<?php echo $order['item_count'] != 1 ? 's' : ''; ?></td>
                                        <td><?php echo formatPrice($order['total']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Addresses Section -->
            <div class="account-card full-width">
                <h2>Saved Addresses</h2>

                <?php if (empty($addresses)): ?>
                    <div class="empty-state">
                        <p>No saved addresses yet. Addresses will be saved when you complete a purchase.</p>
                    </div>
                <?php else: ?>
                    <div class="addresses-grid">
                        <?php foreach ($addresses as $address): ?>
                            <div class="address-card">
                                <?php if ($address['is_default']): ?>
                                    <span class="default-badge">Default</span>
                                <?php endif; ?>
                                <div class="address-type"><?php echo ucfirst($address['type']); ?></div>
                                <div class="address-content">
                                    <strong><?php echo escape($address['first_name'] . ' ' . $address['last_name']); ?></strong><br>
                                    <?php echo escape($address['address_line1']); ?><br>
                                    <?php if ($address['address_line2']): ?>
                                        <?php echo escape($address['address_line2']); ?><br>
                                    <?php endif; ?>
                                    <?php echo escape($address['city'] . ', ' . $address['state'] . ' ' . $address['postal_code']); ?><br>
                                    <?php echo escape($address['country']); ?>
                                    <?php if ($address['phone']): ?>
                                        <br><?php echo escape($address['phone']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="account-actions">
            <a href="/logout" class="btn btn-outline">Sign Out</a>
        </div>
    </div>
</section>

<style>
.account-section {
    padding: 40px 0 80px;
}

.account-section h1 {
    margin-bottom: 30px;
}

.account-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
}

.account-card {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}

.account-card.full-width {
    grid-column: 1 / -1;
}

.account-card h2 {
    font-size: 1.25rem;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid #eee;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid #eee;
}

.card-header h2 {
    margin: 0;
    padding: 0;
    border: none;
}

.view-all {
    color: #FF68C5;
    text-decoration: none;
    font-weight: 500;
}

.account-form .form-group {
    margin-bottom: 16px;
}

.account-form .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.account-form label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    color: #333;
}

.account-form input[type="text"],
.account-form input[type="email"],
.account-form input[type="tel"],
.account-form input[type="password"] {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 0.95rem;
}

.account-form input:focus {
    outline: none;
    border-color: #FF68C5;
}

.account-form input:disabled {
    background: #f5f5f5;
    color: #666;
}

.account-form small {
    display: block;
    margin-top: 4px;
    color: #666;
    font-size: 0.85rem;
}

.account-form .checkbox-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: normal;
    cursor: pointer;
}

.account-form .checkbox-group input {
    width: 18px;
    height: 18px;
    accent-color: #FF68C5;
}

.downloads-summary {
    text-align: center;
    padding: 20px;
}

.downloads-summary p {
    color: #555;
    margin-bottom: 16px;
}

.empty-state {
    text-align: center;
    padding: 30px;
    color: #666;
}

.empty-state .btn {
    margin-top: 16px;
}

.orders-table-wrapper {
    overflow-x: auto;
}

.orders-table {
    width: 100%;
    border-collapse: collapse;
}

.orders-table th,
.orders-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.orders-table th {
    background: #f9f9f9;
    font-weight: 600;
    color: #333;
}

.order-number {
    font-family: monospace;
    font-weight: 500;
}

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-processing { background: #cce5ff; color: #004085; }
.status-shipped { background: #d4edda; color: #155724; }
.status-delivered { background: #d4edda; color: #155724; }
.status-cancelled { background: #f8d7da; color: #721c24; }

.addresses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
}

.address-card {
    border: 1px solid #eee;
    border-radius: 8px;
    padding: 16px;
    position: relative;
}

.address-type {
    font-weight: 600;
    color: #FF68C5;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.address-content {
    color: #555;
    line-height: 1.5;
}

.default-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: #FF68C5;
    color: white;
    font-size: 0.7rem;
    padding: 2px 8px;
    border-radius: 10px;
    font-weight: 500;
}

.account-actions {
    margin-top: 40px;
    text-align: center;
}

.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
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

/* Avatar Upload */
.avatar-upload-area {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.avatar-preview {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    flex-shrink: 0;
}

.avatar-preview .avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.avatar-preview .avatar-initial {
    color: white;
    font-size: 2rem;
    font-weight: 600;
    user-select: none;
}

.avatar-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.2s;
    border-radius: 50%;
}

.avatar-preview:hover .avatar-overlay {
    opacity: 1;
}

.avatar-remove-btn {
    background: none;
    border: none;
    color: #dc2626;
    font-size: 0.85rem;
    cursor: pointer;
    padding: 4px 8px;
}

.avatar-remove-btn:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .account-grid {
        grid-template-columns: 1fr;
    }

    .account-form .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
(function() {
    var preview = document.getElementById('avatarPreview');
    var input = document.getElementById('avatarInput');
    var removeBtn = document.getElementById('avatarRemoveBtn');
    var csrfToken = document.querySelector('input[name="_csrf_token"]');

    if (preview && input) {
        preview.addEventListener('click', function() { input.click(); });

        input.addEventListener('change', function() {
            if (!this.files || !this.files[0]) return;
            var file = this.files[0];
            if (file.size > 5 * 1024 * 1024) {
                alert('File too large. Maximum 5MB.');
                return;
            }

            var formData = new FormData();
            formData.append('avatar', file);
            formData.append('_csrf_token', csrfToken ? csrfToken.value : '');

            fetch('/account/upload-avatar', {
                method: 'POST',
                body: formData
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    preview.innerHTML = '<img src="' + data.avatar_url + '?t=' + Date.now() + '" alt="Avatar" class="avatar-img" id="avatarImg">' +
                        '<div class="avatar-overlay" id="avatarOverlay"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/></svg></div>';
                    if (!removeBtn) {
                        removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.className = 'avatar-remove-btn';
                        removeBtn.id = 'avatarRemoveBtn';
                        removeBtn.textContent = 'Remove';
                        preview.parentNode.appendChild(removeBtn);
                        attachRemove(removeBtn);
                    }
                } else {
                    alert(data.error || 'Upload failed');
                }
            })
            .catch(function() { alert('Upload failed'); });
        });
    }

    function attachRemove(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Remove your profile photo?')) return;
            var formData = new FormData();
            formData.append('_csrf_token', csrfToken ? csrfToken.value : '');

            fetch('/account/remove-avatar', {
                method: 'POST',
                body: formData
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    var initial = document.querySelector('#first_name') ? document.querySelector('#first_name').value.charAt(0).toUpperCase() : '?';
                    preview.innerHTML = '<span class="avatar-initial" id="avatarInitial">' + (initial || '?') + '</span>' +
                        '<div class="avatar-overlay" id="avatarOverlay"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/></svg></div>';
                    btn.remove();
                    removeBtn = null;
                }
            });
        });
    }

    if (removeBtn) attachRemove(removeBtn);
})();
</script>
