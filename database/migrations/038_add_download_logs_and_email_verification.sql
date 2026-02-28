-- Download event logs for admin tracking
CREATE TABLE IF NOT EXISTS download_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_download_id INT NULL,
    product_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(500) NULL,
    referer VARCHAR(500) NULL,
    downloaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product_id (product_id),
    INDEX idx_downloaded_at (downloaded_at)
);

-- Email verification support for users
ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verified_at TIMESTAMP NULL DEFAULT NULL AFTER newsletter_subscribed;
ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verification_token VARCHAR(64) NULL DEFAULT NULL AFTER email_verified_at;

-- Mark all existing users as verified (retroactive)
UPDATE users SET email_verified_at = created_at WHERE email_verified_at IS NULL AND password_hash IS NOT NULL;
