CREATE TABLE IF NOT EXISTS product_customization_fields (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    field_key VARCHAR(64) NOT NULL,
    label VARCHAR(120) NOT NULL,
    field_type ENUM('text', 'textarea') NOT NULL DEFAULT 'text',
    placeholder VARCHAR(255) DEFAULT NULL,
    help_text VARCHAR(255) DEFAULT NULL,
    is_required TINYINT(1) NOT NULL DEFAULT 0,
    max_length INT NOT NULL DEFAULT 100,
    printify_position VARCHAR(64) DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY uq_product_custom_field (product_id, field_key),
    INDEX idx_product_active (product_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE cart
    ADD COLUMN IF NOT EXISTS customizations JSON DEFAULT NULL AFTER is_backorder,
    ADD COLUMN IF NOT EXISTS customization_hash CHAR(64) NOT NULL DEFAULT '' AFTER customizations,
    ADD INDEX IF NOT EXISTS idx_cart_customization_hash (customization_hash);

ALTER TABLE order_items
    ADD COLUMN IF NOT EXISTS customizations JSON DEFAULT NULL AFTER is_backorder;
