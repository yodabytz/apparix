-- Add digital product and license fields
ALTER TABLE products
ADD COLUMN is_digital TINYINT(1) DEFAULT 0 AFTER sku,
ADD COLUMN download_file VARCHAR(500) DEFAULT NULL AFTER is_digital,
ADD COLUMN download_limit INT DEFAULT NULL AFTER download_file,
ADD COLUMN is_license_product TINYINT(1) DEFAULT 0 AFTER download_limit;

-- Add license key storage for orders
CREATE TABLE IF NOT EXISTS order_licenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    order_item_id INT NOT NULL,
    product_id INT NOT NULL,
    license_key VARCHAR(100) NOT NULL,
    edition_code CHAR(1) NOT NULL DEFAULT 'S',
    domain VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    activated_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_license_key (license_key),
    INDEX idx_order_id (order_id)
);

-- Add download tracking
CREATE TABLE IF NOT EXISTS order_downloads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    order_item_id INT NOT NULL,
    product_id INT NOT NULL,
    download_token VARCHAR(64) NOT NULL UNIQUE,
    download_count INT DEFAULT 0,
    max_downloads INT DEFAULT NULL,
    expires_at TIMESTAMP NULL,
    last_download_at TIMESTAMP NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_download_token (download_token),
    INDEX idx_order_id (order_id)
);

-- Create product variants table
CREATE TABLE IF NOT EXISTS product_variants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    sku VARCHAR(100) DEFAULT NULL,
    price DECIMAL(10,2) DEFAULT NULL,
    price_adjustment DECIMAL(10,2) DEFAULT 0.00,
    cost DECIMAL(10,2) DEFAULT NULL,
    license_edition CHAR(1) DEFAULT NULL,
    inventory_count INT DEFAULT 0,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_sku (sku),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
