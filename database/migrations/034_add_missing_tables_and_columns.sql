-- ============================================================================
-- Migration 034: Add all missing tables and columns
-- ============================================================================
-- This catch-all migration adds every table and column referenced by the
-- application code that was not included in migrations 001-033.
--
-- Uses ADD COLUMN IF NOT EXISTS (MariaDB 10.0+ / MySQL 8.0.19+-compat
-- pattern via stored-procedure workaround is NOT used here; we rely on
-- MariaDB's native IF NOT EXISTS for ALTER TABLE ADD COLUMN).
--
-- All new tables use CREATE TABLE IF NOT EXISTS so this migration is
-- safe to re-run.
-- ============================================================================

-- --------------------------------------------------------------------------
-- 1. Missing columns on the `products` table (base: migration 003)
-- --------------------------------------------------------------------------
ALTER TABLE products ADD COLUMN IF NOT EXISTS origin_id INT DEFAULT NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS sort_order INT DEFAULT 0;
ALTER TABLE products ADD COLUMN IF NOT EXISTS disabled TINYINT(1) DEFAULT 0;
ALTER TABLE products ADD COLUMN IF NOT EXISTS cost DECIMAL(10,2) DEFAULT NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS cost_not_applicable TINYINT(1) DEFAULT 0;
ALTER TABLE products ADD COLUMN IF NOT EXISTS weight DECIMAL(10,4) DEFAULT NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS weight_oz DECIMAL(10,4) DEFAULT NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS length_in DECIMAL(10,2) DEFAULT NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS width_in DECIMAL(10,2) DEFAULT NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS height_in DECIMAL(10,2) DEFAULT NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS shipping_price DECIMAL(10,2) DEFAULT NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS ships_free TINYINT(1) DEFAULT 0;
ALTER TABLE products ADD COLUMN IF NOT EXISTS ships_free_us TINYINT(1) DEFAULT 0;
ALTER TABLE products ADD COLUMN IF NOT EXISTS meta_keywords VARCHAR(500) DEFAULT NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS meta_description TEXT DEFAULT NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS manufacturer VARCHAR(255) DEFAULT NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS processing_time VARCHAR(100) DEFAULT NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS supplier_email VARCHAR(255) DEFAULT NULL;

-- --------------------------------------------------------------------------
-- 2. Missing columns on the `categories` table (base: migration 002)
-- --------------------------------------------------------------------------
ALTER TABLE categories ADD COLUMN IF NOT EXISTS image VARCHAR(255) DEFAULT NULL;
ALTER TABLE categories ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1;

-- --------------------------------------------------------------------------
-- 3. Missing columns on the `orders` table (base: migration 007)
-- --------------------------------------------------------------------------
ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipping_name VARCHAR(255) DEFAULT NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipping_address VARCHAR(500) DEFAULT NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipping_address2 VARCHAR(255) DEFAULT NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipping_city VARCHAR(100) DEFAULT NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipping_state VARCHAR(100) DEFAULT NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipping_zip VARCHAR(20) DEFAULT NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipping_country VARCHAR(100) DEFAULT 'US';
ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipping_method VARCHAR(100) DEFAULT NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS tracking_number VARCHAR(255) DEFAULT NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS tracking_url VARCHAR(500) DEFAULT NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipping_carrier VARCHAR(100) DEFAULT NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipped_at TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS actual_shipping_cost DECIMAL(10,2) DEFAULT NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS gift_card_amount DECIMAL(10,2) DEFAULT 0;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS refund_amount DECIMAL(10,2) DEFAULT 0;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS coupon_code VARCHAR(100) DEFAULT NULL;

-- --------------------------------------------------------------------------
-- 4. Missing columns on the `order_items` table (base: migration 008)
--    Note: variant_id was added in migration 028, but we use IF NOT EXISTS
--    so it is safe to include here for completeness.
-- --------------------------------------------------------------------------
ALTER TABLE order_items ADD COLUMN IF NOT EXISTS variant_id INT DEFAULT NULL;
ALTER TABLE order_items ADD COLUMN IF NOT EXISTS variant_name VARCHAR(255) DEFAULT NULL;
ALTER TABLE order_items ADD COLUMN IF NOT EXISTS cost DECIMAL(10,2) DEFAULT NULL;

-- --------------------------------------------------------------------------
-- 5. Missing column on the `cart` table (base: migration 009)
-- --------------------------------------------------------------------------
ALTER TABLE cart ADD COLUMN IF NOT EXISTS variant_id INT DEFAULT NULL;

-- --------------------------------------------------------------------------
-- 6. Missing column on the `product_variants` table (base: migration 027)
-- --------------------------------------------------------------------------
ALTER TABLE product_variants ADD COLUMN IF NOT EXISTS shipping_cost DECIMAL(10,2) DEFAULT NULL;

-- --------------------------------------------------------------------------
-- 7. Missing columns on the `visitors` table
--    The visitors table is created further down (section 34). If it already
--    existed before this migration, the ALTER TABLE statements after the
--    CREATE TABLE will add the missing columns.
-- --------------------------------------------------------------------------


-- ============================================================================
-- NEW TABLES
-- ============================================================================

-- --------------------------------------------------------------------------
-- 8. login_attempts
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success TINYINT(1) DEFAULT 0,
    INDEX idx_email (email),
    INDEX idx_ip_address (ip_address),
    INDEX idx_attempted_at (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 9. shipping_origins
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS shipping_origins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    address_line1 VARCHAR(500) NOT NULL,
    address_line2 VARCHAR(255) DEFAULT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    country VARCHAR(100) NOT NULL DEFAULT 'US',
    phone VARCHAR(30) DEFAULT NULL,
    is_default TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    shipping_cost_usa DECIMAL(10,2) DEFAULT NULL,
    shipping_cost_canada DECIMAL(10,2) DEFAULT NULL,
    shipping_cost_overseas DECIMAL(10,2) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_default (is_default),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 10. shipping_zones
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS shipping_zones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    countries JSON DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 11. shipping_methods
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS shipping_methods (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    carrier VARCHAR(100) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active),
    INDEX idx_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 12. shipping_rates
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS shipping_rates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    zone_id INT NOT NULL,
    method_id INT NOT NULL,
    min_weight DECIMAL(10,4) DEFAULT NULL,
    max_weight DECIMAL(10,4) DEFAULT NULL,
    min_total DECIMAL(10,2) DEFAULT NULL,
    max_total DECIMAL(10,2) DEFAULT NULL,
    rate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (zone_id) REFERENCES shipping_zones(id) ON DELETE CASCADE,
    FOREIGN KEY (method_id) REFERENCES shipping_methods(id) ON DELETE CASCADE,
    INDEX idx_zone (zone_id),
    INDEX idx_method (method_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 13. shipping_classes
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS shipping_classes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 14. product_options
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS product_options (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    option_name VARCHAR(100) NOT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 15. product_option_values
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS product_option_values (
    id INT PRIMARY KEY AUTO_INCREMENT,
    option_id INT NOT NULL,
    value_name VARCHAR(100) NOT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (option_id) REFERENCES product_options(id) ON DELETE CASCADE,
    INDEX idx_option (option_id),
    INDEX idx_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 16. variant_option_values
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS variant_option_values (
    id INT PRIMARY KEY AUTO_INCREMENT,
    variant_id INT NOT NULL,
    option_id INT NOT NULL,
    option_value_id INT NOT NULL,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
    FOREIGN KEY (option_id) REFERENCES product_options(id) ON DELETE CASCADE,
    FOREIGN KEY (option_value_id) REFERENCES product_option_values(id) ON DELETE CASCADE,
    INDEX idx_variant (variant_id),
    INDEX idx_option (option_id),
    INDEX idx_option_value (option_value_id),
    UNIQUE KEY uq_variant_option (variant_id, option_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 17. product_bundles
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS product_bundles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    discount_type ENUM('percentage', 'fixed') NOT NULL DEFAULT 'percentage',
    discount_value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 18. bundle_products
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bundle_products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bundle_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_id INT DEFAULT NULL,
    quantity INT NOT NULL DEFAULT 1,
    FOREIGN KEY (bundle_id) REFERENCES product_bundles(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL,
    INDEX idx_bundle (bundle_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 19. carts (session-based carts, distinct from legacy `cart` table)
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS carts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id VARCHAR(255) NOT NULL,
    user_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_session (session_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 20. cart_items (linked to new `carts` table)
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cart_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cart_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_id INT DEFAULT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL,
    INDEX idx_cart (cart_id),
    INDEX idx_product (product_id),
    INDEX idx_variant (variant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 21. favorites
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS favorites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY uq_user_product (user_id, product_id),
    INDEX idx_user (user_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 22. coupons
-- --------------------------------------------------------------------------
-- Note: discount_codes table is created in migration 010.
-- coupon_usage below references discount_codes, not a separate coupons table.

-- --------------------------------------------------------------------------
-- 23. coupon_usage
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS coupon_usage (
    id INT PRIMARY KEY AUTO_INCREMENT,
    discount_code_id INT NOT NULL,
    order_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (discount_code_id) REFERENCES discount_codes(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_discount_code (discount_code_id),
    INDEX idx_order (order_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 24. product_reviews
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS product_reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    order_id INT DEFAULT NULL,
    rating TINYINT NOT NULL,
    title VARCHAR(255) DEFAULT NULL,
    review_text TEXT DEFAULT NULL,
    is_approved TINYINT(1) DEFAULT 0,
    is_verified_purchase TINYINT(1) DEFAULT 1,
    is_featured TINYINT(1) DEFAULT 0,
    helpful_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    INDEX idx_product (product_id),
    INDEX idx_user (user_id),
    INDEX idx_approved (is_approved),
    INDEX idx_rating (rating),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 25. review_requests
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS review_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    order_item_id INT NOT NULL,
    product_id INT NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    status ENUM('pending', 'sent', 'completed', 'expired') DEFAULT 'pending',
    sent_at TIMESTAMP NULL DEFAULT NULL,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_order (order_id),
    INDEX idx_product (product_id),
    INDEX idx_token (token),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 26. gift_cards
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS gift_cards (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(100) NOT NULL UNIQUE,
    initial_balance DECIMAL(10,2) NOT NULL,
    current_balance DECIMAL(10,2) NOT NULL,
    purchased_by INT DEFAULT NULL,
    recipient_email VARCHAR(255) DEFAULT NULL,
    recipient_name VARCHAR(255) DEFAULT NULL,
    sender_name VARCHAR(255) DEFAULT NULL,
    message TEXT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    expires_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (purchased_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_code (code),
    INDEX idx_is_active (is_active),
    INDEX idx_purchased_by (purchased_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 27. gift_card_transactions
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS gift_card_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    gift_card_id INT NOT NULL,
    order_id INT DEFAULT NULL,
    amount DECIMAL(10,2) NOT NULL,
    type ENUM('credit', 'debit', 'refund') NOT NULL DEFAULT 'debit',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (gift_card_id) REFERENCES gift_cards(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    INDEX idx_gift_card (gift_card_id),
    INDEX idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 28. newsletter_subscribers
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL UNIQUE,
    first_name VARCHAR(100) DEFAULT NULL,
    user_id INT DEFAULT NULL,
    token VARCHAR(64) DEFAULT NULL,
    source VARCHAR(50) DEFAULT 'website',
    preferences LONGTEXT DEFAULT NULL,
    is_subscribed TINYINT(1) DEFAULT 1,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_email (email),
    INDEX idx_is_subscribed (is_subscribed),
    INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 29. newsletters
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS newsletters (
    id INT PRIMARY KEY AUTO_INCREMENT,
    subject VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,
    sent_count INT DEFAULT 0,
    status ENUM('draft', 'sending', 'sent', 'failed') DEFAULT 'draft',
    sent_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_sent_at (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 30. popup_coupons
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS popup_coupons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    discount_percent DECIMAL(5,2) DEFAULT 10.00,
    min_order DECIMAL(10,2) DEFAULT 0.00,
    used TINYINT(1) DEFAULT 0,
    used_at DATETIME DEFAULT NULL,
    order_id INT UNSIGNED DEFAULT NULL,
    expires_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_email (email),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 31. referral_codes
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS referral_codes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    code VARCHAR(100) NOT NULL UNIQUE,
    discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    reward_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_code (code),
    INDEX idx_user (user_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 32. referral_uses
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS referral_uses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    referral_code_id INT NOT NULL,
    referred_user_id INT NOT NULL,
    order_id INT DEFAULT NULL,
    discount_applied DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    reward_earned DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (referral_code_id) REFERENCES referral_codes(id) ON DELETE CASCADE,
    FOREIGN KEY (referred_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    INDEX idx_referral_code (referral_code_id),
    INDEX idx_referred_user (referred_user_id),
    INDEX idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 33. stock_notifications
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS stock_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    variant_id INT DEFAULT NULL,
    email VARCHAR(255) NOT NULL,
    notified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL,
    INDEX idx_product (product_id),
    INDEX idx_variant (variant_id),
    INDEX idx_email (email),
    INDEX idx_notified (notified)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 34. visitors
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS visitors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id VARCHAR(255) DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    referrer TEXT DEFAULT NULL,
    page_url TEXT DEFAULT NULL,
    country VARCHAR(100) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    device_type VARCHAR(50) DEFAULT NULL,
    browser VARCHAR(100) DEFAULT NULL,
    os VARCHAR(100) DEFAULT NULL,
    is_bot TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session (session_id),
    INDEX idx_ip_address (ip_address),
    INDEX idx_created_at (created_at),
    INDEX idx_is_bot (is_bot)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- If the visitors table already existed but was missing columns, add them now.
ALTER TABLE visitors ADD COLUMN IF NOT EXISTS referrer TEXT DEFAULT NULL;
ALTER TABLE visitors ADD COLUMN IF NOT EXISTS page_url TEXT DEFAULT NULL;

-- --------------------------------------------------------------------------
-- 35. admin_sessions
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE CASCADE,
    INDEX idx_admin (admin_user_id),
    INDEX idx_token (session_token),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 36. admin_activity_log
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin_activity_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_user_id INT DEFAULT NULL,
    action VARCHAR(255) NOT NULL,
    entity_type VARCHAR(100) DEFAULT NULL,
    entity_id INT DEFAULT NULL,
    details TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_admin (admin_user_id),
    INDEX idx_action (action),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 37. order_status_history
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS order_status_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_order (order_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 38. inventory_import_logs
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS inventory_import_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT DEFAULT NULL,
    filename VARCHAR(255) NOT NULL,
    total_rows INT DEFAULT 0,
    processed INT DEFAULT 0,
    skipped INT DEFAULT 0,
    errors TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_admin (admin_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 39. product_image_option_values
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS product_image_option_values (
    id INT PRIMARY KEY AUTO_INCREMENT,
    image_id INT NOT NULL,
    option_value_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (image_id) REFERENCES product_images(id) ON DELETE CASCADE,
    FOREIGN KEY (option_value_id) REFERENCES product_option_values(id) ON DELETE CASCADE,
    INDEX idx_image (image_id),
    INDEX idx_option_value (option_value_id),
    UNIQUE KEY uq_image_option_value (image_id, option_value_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 40. failed_orders
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS failed_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payment_intent_id VARCHAR(255) DEFAULT NULL,
    customer_email VARCHAR(255) DEFAULT NULL,
    subtotal DECIMAL(10,2) DEFAULT NULL,
    shipping_cost DECIMAL(10,2) DEFAULT NULL,
    total DECIMAL(10,2) DEFAULT NULL,
    error_message TEXT DEFAULT NULL,
    refund_status VARCHAR(50) DEFAULT NULL,
    refund_error TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_payment_intent (payment_intent_id),
    INDEX idx_customer_email (customer_email),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
