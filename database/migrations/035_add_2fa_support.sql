-- Migration 035: Add Two-Factor Authentication Support
-- Adds TOTP-based 2FA columns to admin_users and creates admin_devices table

-- Add 2FA columns to admin_users (idempotent)
SET @dbname = DATABASE();

-- Add totp_secret column
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'admin_users' AND COLUMN_NAME = 'totp_secret') = 0,
    'ALTER TABLE admin_users ADD COLUMN totp_secret VARCHAR(32) DEFAULT NULL AFTER password_hash',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add totp_enabled column
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'admin_users' AND COLUMN_NAME = 'totp_enabled') = 0,
    'ALTER TABLE admin_users ADD COLUMN totp_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER totp_secret',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add backup_codes column
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'admin_users' AND COLUMN_NAME = 'backup_codes') = 0,
    'ALTER TABLE admin_users ADD COLUMN backup_codes TEXT DEFAULT NULL AFTER totp_enabled',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create admin_devices table for device fingerprinting
CREATE TABLE IF NOT EXISTS admin_devices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id INT UNSIGNED NOT NULL,
    device_fingerprint VARCHAR(64) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_admin_device (admin_id, device_fingerprint),
    INDEX idx_admin_id (admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
