<div class="admin-page">
    <div class="page-header">
        <h1>Two-Factor Authentication</h1>
    </div>

    <div class="card" style="max-width: 700px;">
        <div class="card-body" style="padding: 30px;">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 25px;">
                <div style="width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; background: <?php echo $enabled ? '#d4edda' : '#f8f9fa'; ?>;">
                    <?php echo $enabled ? '&#9989;' : '&#128274;'; ?>
                </div>
                <div>
                    <h2 style="margin: 0; font-size: 18px;">
                        Status:
                        <?php if ($enabled): ?>
                            <span style="color: #28a745;">Enabled</span>
                        <?php else: ?>
                            <span style="color: #6c757d;">Disabled</span>
                        <?php endif; ?>
                    </h2>
                    <p style="margin: 5px 0 0; color: #6c757d; font-size: 14px;">
                        <?php if ($enabled): ?>
                            Your account is protected with an authenticator app.
                        <?php else: ?>
                            Add an extra layer of security to your admin account.
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <?php if ($enabled): ?>
                <div style="background: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <div>
                            <strong>Backup Codes</strong>
                            <p style="margin: 3px 0 0; color: #6c757d; font-size: 13px;">
                                <?php echo $backupCodesCount; ?> of 8 codes remaining
                            </p>
                        </div>
                        <form action="/admin/2fa/regenerate-codes" method="POST" style="margin: 0;"
                              onsubmit="return confirm('This will invalidate all existing backup codes. Continue?');">
                            <?php echo csrfField(); ?>
                            <button type="submit" class="btn btn-sm" style="font-size: 13px;">Regenerate Codes</button>
                        </form>
                    </div>

                    <?php if ($backupCodesCount <= 2): ?>
                        <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px; padding: 10px 14px; font-size: 13px; color: #856404;">
                            You're running low on backup codes. Consider regenerating them.
                        </div>
                    <?php endif; ?>
                </div>

                <div style="background: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 25px;">
                    <strong>Trusted Devices</strong>
                    <p style="margin: 3px 0 0; color: #6c757d; font-size: 13px;">
                        <?php echo $deviceCount; ?> device(s) registered
                    </p>
                </div>

                <hr style="border: none; border-top: 1px solid #e9ecef; margin: 25px 0;">

                <h3 style="font-size: 16px; margin-bottom: 15px; color: #dc3545;">Disable Two-Factor Authentication</h3>
                <p style="color: #6c757d; font-size: 14px; margin-bottom: 15px;">
                    This will remove two-factor authentication from your account. You'll need to enter your password to confirm.
                </p>

                <form action="/admin/2fa/disable" method="POST" id="disable2faForm"
                      onsubmit="return confirm('Are you sure you want to disable two-factor authentication?');">
                    <?php echo csrfField(); ?>
                    <div class="form-group" style="max-width: 350px;">
                        <label class="form-label" for="disable_password">Confirm Password</label>
                        <input type="password" id="disable_password" name="password" class="form-input"
                               placeholder="Enter your password" required>
                    </div>
                    <button type="submit" class="btn" style="background: #dc3545; color: #fff; border: none;">
                        Disable 2FA
                    </button>
                </form>

            <?php else: ?>
                <p style="color: #6c757d; font-size: 14px; line-height: 1.6; margin-bottom: 25px;">
                    Two-factor authentication adds an additional layer of security to your account by requiring
                    a time-based code from an authenticator app (such as Google Authenticator, Authy, or
                    Microsoft Authenticator) in addition to your password.
                </p>

                <a href="/admin/2fa/setup" class="btn btn-primary">
                    Enable Two-Factor Authentication
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
