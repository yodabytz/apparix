-- License purchases table (for apparix.app website)
-- Stores license keys purchased through the license store

CREATE TABLE IF NOT EXISTS license_purchases (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    license_key VARCHAR(50) NOT NULL,
    edition CHAR(1) NOT NULL COMMENT 'S=Standard, P=Professional, E=Enterprise',
    domain VARCHAR(255) DEFAULT NULL,
    stripe_session_id VARCHAR(255),
    amount DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_license_key (license_key),
    INDEX idx_stripe_session (stripe_session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
