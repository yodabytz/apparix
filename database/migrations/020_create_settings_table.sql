-- Settings table for dynamic configuration storage
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json', 'file') DEFAULT 'string',
    category ENUM('store', 'theme', 'layout', 'integrations', 'email') DEFAULT 'store',
    is_public BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, setting_type, category, is_public) VALUES
('store_name', 'My Store', 'string', 'store', 1),
('store_tagline', 'Welcome to our store', 'string', 'store', 1),
('store_email', '', 'string', 'store', 0),
('store_phone', '', 'string', 'store', 1),
('store_logo', '', 'file', 'store', 1),
('store_favicon', '', 'file', 'store', 1),
('store_currency', 'USD', 'string', 'store', 1),
('store_currency_symbol', '$', 'string', 'store', 1)
ON DUPLICATE KEY UPDATE setting_key = setting_key;
