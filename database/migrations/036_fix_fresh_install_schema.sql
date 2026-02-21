-- ============================================================================
-- Migration 036: Fix fresh install schema gaps
-- ============================================================================
-- Adds all columns and tables that the application code expects but were
-- missing from previous migrations. Uses IF NOT EXISTS so safe to re-run.
-- ============================================================================

-- --------------------------------------------------------------------------
-- product_images - missing columns
-- --------------------------------------------------------------------------
ALTER TABLE product_images ADD COLUMN IF NOT EXISTS is_video TINYINT(1) DEFAULT 0;
ALTER TABLE product_images ADD COLUMN IF NOT EXISTS alt_text VARCHAR(255) DEFAULT NULL;
ALTER TABLE product_images ADD COLUMN IF NOT EXISTS option_value_id INT DEFAULT NULL;
ALTER TABLE product_images ADD COLUMN IF NOT EXISTS parent_image_id INT DEFAULT NULL;

-- --------------------------------------------------------------------------
-- visitors - missing columns
-- --------------------------------------------------------------------------
ALTER TABLE visitors ADD COLUMN IF NOT EXISTS country_code VARCHAR(2) DEFAULT NULL;

-- --------------------------------------------------------------------------
-- product_categories - missing sort_order for admin reordering
-- --------------------------------------------------------------------------
ALTER TABLE product_categories ADD COLUMN IF NOT EXISTS sort_order INT DEFAULT 0;

-- --------------------------------------------------------------------------
-- categories - missing columns
-- --------------------------------------------------------------------------
ALTER TABLE categories ADD COLUMN IF NOT EXISTS show_subcategory_grid TINYINT(1) DEFAULT 0;

-- --------------------------------------------------------------------------
-- products - missing columns
-- --------------------------------------------------------------------------
ALTER TABLE products ADD COLUMN IF NOT EXISTS featured_order INT DEFAULT 0;
ALTER TABLE products ADD COLUMN IF NOT EXISTS version VARCHAR(20) DEFAULT NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS price_min DECIMAL(10,2) DEFAULT NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS price_max DECIMAL(10,2) DEFAULT NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS shipping_class_id INT DEFAULT NULL;

-- --------------------------------------------------------------------------
-- product_variants - missing dimension columns
-- --------------------------------------------------------------------------
ALTER TABLE product_variants ADD COLUMN IF NOT EXISTS weight_oz DECIMAL(8,2) DEFAULT NULL;
ALTER TABLE product_variants ADD COLUMN IF NOT EXISTS length_in DECIMAL(6,2) DEFAULT NULL;
ALTER TABLE product_variants ADD COLUMN IF NOT EXISTS width_in DECIMAL(6,2) DEFAULT NULL;
ALTER TABLE product_variants ADD COLUMN IF NOT EXISTS height_in DECIMAL(6,2) DEFAULT NULL;

-- --------------------------------------------------------------------------
-- login_attempts - missing attempt_type
-- --------------------------------------------------------------------------
ALTER TABLE login_attempts ADD COLUMN IF NOT EXISTS attempt_type ENUM('admin','user') NOT NULL DEFAULT 'user';

-- --------------------------------------------------------------------------
-- cart - missing email for abandoned cart
-- --------------------------------------------------------------------------
ALTER TABLE cart ADD COLUMN IF NOT EXISTS email VARCHAR(255) DEFAULT NULL;

-- --------------------------------------------------------------------------
-- carts - missing abandoned cart tracking columns
-- --------------------------------------------------------------------------
ALTER TABLE carts ADD COLUMN IF NOT EXISTS email VARCHAR(255) DEFAULT NULL;
ALTER TABLE carts ADD COLUMN IF NOT EXISTS abandoned_email_sent TINYINT(1) DEFAULT 0;
ALTER TABLE carts ADD COLUMN IF NOT EXISTS abandoned_email_sent_at DATETIME DEFAULT NULL;
ALTER TABLE carts ADD COLUMN IF NOT EXISTS recovered TINYINT(1) DEFAULT 0;

-- --------------------------------------------------------------------------
-- cart_items - missing price column
-- --------------------------------------------------------------------------
ALTER TABLE cart_items ADD COLUMN IF NOT EXISTS price DECIMAL(10,2) DEFAULT NULL;

-- --------------------------------------------------------------------------
-- favorites - missing columns for wishlist reminders
-- --------------------------------------------------------------------------
ALTER TABLE favorites ADD COLUMN IF NOT EXISTS reminder_sent_at DATETIME DEFAULT NULL;
ALTER TABLE favorites ADD COLUMN IF NOT EXISTS session_id VARCHAR(128) DEFAULT NULL;

-- --------------------------------------------------------------------------
-- users - missing columns
-- --------------------------------------------------------------------------
ALTER TABLE users ADD COLUMN IF NOT EXISTS newsletter_subscribed TINYINT(1) DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS remember_token VARCHAR(255) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS remember_token_expires TIMESTAMP NULL DEFAULT NULL;

-- --------------------------------------------------------------------------
-- orders - missing columns
-- --------------------------------------------------------------------------
ALTER TABLE orders ADD COLUMN IF NOT EXISTS discount_code_id INT DEFAULT NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS origin_id INT DEFAULT NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipping_method_id INT DEFAULT NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS estimated_delivery VARCHAR(100) DEFAULT NULL;

-- --------------------------------------------------------------------------
-- discount_codes - missing columns
-- --------------------------------------------------------------------------
ALTER TABLE discount_codes ADD COLUMN IF NOT EXISTS description VARCHAR(255) DEFAULT NULL;
ALTER TABLE discount_codes ADD COLUMN IF NOT EXISTS applies_to ENUM('all','products','categories') DEFAULT 'all';
ALTER TABLE discount_codes ADD COLUMN IF NOT EXISTS product_ids TEXT DEFAULT NULL;
ALTER TABLE discount_codes ADD COLUMN IF NOT EXISTS category_ids TEXT DEFAULT NULL;
ALTER TABLE discount_codes ADD COLUMN IF NOT EXISTS one_per_customer TINYINT(1) DEFAULT 1;
ALTER TABLE discount_codes ADD COLUMN IF NOT EXISTS requires_account TINYINT(1) DEFAULT 1;

-- --------------------------------------------------------------------------
-- shipping_zones - missing columns
-- --------------------------------------------------------------------------
ALTER TABLE shipping_zones ADD COLUMN IF NOT EXISTS sort_order INT DEFAULT 0;
ALTER TABLE shipping_zones ADD COLUMN IF NOT EXISTS states TEXT DEFAULT NULL;

-- --------------------------------------------------------------------------
-- shipping_methods - missing columns
-- --------------------------------------------------------------------------
ALTER TABLE shipping_methods ADD COLUMN IF NOT EXISTS method_code VARCHAR(50) DEFAULT NULL;
ALTER TABLE shipping_methods ADD COLUMN IF NOT EXISTS description TEXT DEFAULT NULL;
ALTER TABLE shipping_methods ADD COLUMN IF NOT EXISTS rate_type ENUM('live','flat','free','table') DEFAULT 'flat';
ALTER TABLE shipping_methods ADD COLUMN IF NOT EXISTS flat_rate DECIMAL(8,2) DEFAULT NULL;
ALTER TABLE shipping_methods ADD COLUMN IF NOT EXISTS handling_fee DECIMAL(8,2) DEFAULT 0.00;
ALTER TABLE shipping_methods ADD COLUMN IF NOT EXISTS min_order_free DECIMAL(8,2) DEFAULT NULL;
ALTER TABLE shipping_methods ADD COLUMN IF NOT EXISTS delivery_estimate VARCHAR(100) DEFAULT NULL;
ALTER TABLE shipping_methods ADD COLUMN IF NOT EXISTS zone_id INT DEFAULT NULL;

-- --------------------------------------------------------------------------
-- shipping_classes - missing columns
-- --------------------------------------------------------------------------
ALTER TABLE shipping_classes ADD COLUMN IF NOT EXISTS slug VARCHAR(100) DEFAULT NULL;
ALTER TABLE shipping_classes ADD COLUMN IF NOT EXISTS handling_fee DECIMAL(8,2) DEFAULT 0.00;
ALTER TABLE shipping_classes ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- --------------------------------------------------------------------------
-- newsletter_subscribers - missing columns
-- --------------------------------------------------------------------------
ALTER TABLE newsletter_subscribers ADD COLUMN IF NOT EXISTS first_name VARCHAR(100) DEFAULT NULL;
ALTER TABLE newsletter_subscribers ADD COLUMN IF NOT EXISTS source VARCHAR(50) DEFAULT 'website';
ALTER TABLE newsletter_subscribers ADD COLUMN IF NOT EXISTS token VARCHAR(64) DEFAULT NULL;
ALTER TABLE newsletter_subscribers ADD COLUMN IF NOT EXISTS user_id INT DEFAULT NULL;
ALTER TABLE newsletter_subscribers ADD COLUMN IF NOT EXISTS preferences LONGTEXT DEFAULT NULL;

-- --------------------------------------------------------------------------
-- newsletters - missing columns
-- --------------------------------------------------------------------------
ALTER TABLE newsletters ADD COLUMN IF NOT EXISTS recipient_count INT DEFAULT 0;
ALTER TABLE newsletters ADD COLUMN IF NOT EXISTS sent_by INT DEFAULT NULL;

-- --------------------------------------------------------------------------
-- product_reviews - missing columns
-- --------------------------------------------------------------------------
ALTER TABLE product_reviews ADD COLUMN IF NOT EXISTS helpful_count INT DEFAULT 0;
ALTER TABLE product_reviews ADD COLUMN IF NOT EXISTS is_featured TINYINT(1) DEFAULT 0;
ALTER TABLE product_reviews ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL;

-- --------------------------------------------------------------------------
-- review_requests - missing columns
-- --------------------------------------------------------------------------
ALTER TABLE review_requests ADD COLUMN IF NOT EXISTS user_id INT DEFAULT NULL;
ALTER TABLE review_requests ADD COLUMN IF NOT EXISTS reminded_at TIMESTAMP NULL DEFAULT NULL;

-- --------------------------------------------------------------------------
-- stock_notifications - missing columns
-- --------------------------------------------------------------------------
ALTER TABLE stock_notifications ADD COLUMN IF NOT EXISTS variant_name VARCHAR(255) DEFAULT NULL;
ALTER TABLE stock_notifications ADD COLUMN IF NOT EXISTS notified_at TIMESTAMP NULL DEFAULT NULL;

-- --------------------------------------------------------------------------
-- product_bundles - missing columns
-- --------------------------------------------------------------------------
ALTER TABLE product_bundles ADD COLUMN IF NOT EXISTS slug VARCHAR(255) DEFAULT NULL;
ALTER TABLE product_bundles ADD COLUMN IF NOT EXISTS description TEXT DEFAULT NULL;
ALTER TABLE product_bundles ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE product_bundles ADD COLUMN IF NOT EXISTS expires_at DATETIME DEFAULT NULL;

-- --------------------------------------------------------------------------
-- Missing tables
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS product_bundle_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bundle_id INT NOT NULL,
    product_id INT NOT NULL,
    FOREIGN KEY (bundle_id) REFERENCES product_bundles(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_bundle (bundle_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quantity_discounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    min_quantity INT NOT NULL,
    discount_percent DECIMAL(5,2) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shipping_carriers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    carrier_code VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    api_mode ENUM('test','live') DEFAULT 'test',
    api_credentials TEXT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_carrier_code (carrier_code),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tracking_events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    carrier VARCHAR(50) NOT NULL,
    tracking_number VARCHAR(100) NOT NULL,
    status VARCHAR(100) NOT NULL,
    location VARCHAR(255) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    event_time TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_order (order_id),
    INDEX idx_tracking (tracking_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
