CREATE TABLE IF NOT EXISTS product_faqs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    question VARCHAR(500) NOT NULL,
    answer TEXT NOT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_product_active (product_id, is_active, sort_order),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
