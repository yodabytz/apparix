CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255),
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    phone VARCHAR(20),
    is_guest BOOLEAN DEFAULT 0,
    newsletter_subscribed TINYINT(1) DEFAULT 0,
    email_verified_at TIMESTAMP NULL DEFAULT NULL,
    email_verification_token VARCHAR(64) NULL DEFAULT NULL,
    remember_token VARCHAR(255) NULL DEFAULT NULL,
    remember_token_expires TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_created (created_at)
);
