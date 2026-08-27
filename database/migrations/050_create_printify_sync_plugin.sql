-- Migration 050: Printify Sync plugin

CREATE TABLE IF NOT EXISTS printify_product_sync (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    variant_id INT NOT NULL DEFAULT 0,
    printify_product_id VARCHAR(64),
    printify_variant_id INT DEFAULT NULL,
    printify_sku VARCHAR(100),
    last_synced_at TIMESTAMP NULL,
    sync_status ENUM('pending', 'synced', 'error') DEFAULT 'pending',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_product_variant (product_id, variant_id),
    INDEX idx_printify_product (printify_product_id),
    INDEX idx_status (sync_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS printify_order_sync (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    printify_order_id VARCHAR(64),
    status ENUM('pending', 'awaiting_personalization', 'submitted', 'error') DEFAULT 'pending',
    error_message TEXT,
    submitted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_order (order_id),
    INDEX idx_printify_order (printify_order_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE printify_order_sync MODIFY status ENUM('pending', 'awaiting_personalization', 'submitted', 'error') DEFAULT 'pending';

CREATE TABLE IF NOT EXISTS printify_sync_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    action VARCHAR(50) NOT NULL,
    status ENUM('success', 'error', 'warning') NOT NULL,
    message TEXT,
    details JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action (action),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE plugins MODIFY type ENUM('payment','shipping','analytics','marketing','utility','marketplace') NOT NULL;

INSERT INTO plugins (slug, name, description, version, author, author_url, type, is_active, settings, icon)
VALUES (
    'printify-sync',
    'Printify Sync',
    'Sync selected Apparix products and send eligible orders to Printify.',
    '1.0.5',
    'Apparix',
    'https://apparix.app',
    'marketplace',
    0,
    JSON_OBJECT(
        'api_token', '',
        'shop_id', '',
        'sync_products', JSON_ARRAY(),
        'variant_map', JSON_OBJECT(),
        'default_blueprint_id', '',
        'default_print_provider_id', '',
        'default_variant_id', '',
        'publish_after_create', false,
        'send_orders', true,
        'sync_inventory', true,
        'sync_price', true
    ),
    'printify-logo.svg'
)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    version = VALUES(version),
    author = VALUES(author),
    author_url = VALUES(author_url),
    type = VALUES(type),
    icon = VALUES(icon);
