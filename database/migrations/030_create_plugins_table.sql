-- Create plugins table for managing installable plugins
CREATE TABLE IF NOT EXISTS plugins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    slug VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    version VARCHAR(20) NOT NULL,
    author VARCHAR(200),
    author_url VARCHAR(500),
    type ENUM('payment', 'shipping', 'analytics', 'marketing', 'utility') NOT NULL,
    is_active BOOLEAN DEFAULT 0,
    settings JSON,
    icon VARCHAR(500),
    installed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Stripe as the default built-in payment plugin
INSERT INTO plugins (slug, name, description, version, author, type, is_active, icon) VALUES
('stripe', 'Stripe Payments', 'Accept credit and debit card payments with Stripe', '1.0.0', 'Apparix', 'payment', 1, 'stripe-logo.svg');
