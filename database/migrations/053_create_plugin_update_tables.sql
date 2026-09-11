CREATE TABLE IF NOT EXISTS plugin_update_entitlements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    license_key_hash CHAR(64) NOT NULL,
    plugin_slug VARCHAR(100) NOT NULL,
    source_order_id INT DEFAULT NULL,
    notes VARCHAR(255) DEFAULT NULL,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    revoked_at TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uniq_plugin_license (license_key_hash, plugin_slug),
    INDEX idx_plugin_slug (plugin_slug),
    INDEX idx_source_order (source_order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS plugin_update_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    license_key_hash CHAR(64) NOT NULL,
    domain VARCHAR(255) NOT NULL,
    plugin_slug VARCHAR(100) NOT NULL,
    from_version VARCHAR(50) DEFAULT NULL,
    to_version VARCHAR(50) NOT NULL,
    status ENUM('downloaded', 'installed', 'failed') NOT NULL,
    error_message VARCHAR(1000) DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_plugin_update_license (license_key_hash),
    INDEX idx_plugin_update_slug (plugin_slug),
    INDEX idx_plugin_update_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
