-- ============================================================================
-- Migration 042: Create pages table for static/custom pages feature
-- ============================================================================

CREATE TABLE IF NOT EXISTS pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    content LONGTEXT NOT NULL,
    meta_title VARCHAR(255) DEFAULT NULL,
    meta_description VARCHAR(500) DEFAULT NULL,
    keywords VARCHAR(500) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    show_title TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    show_in_footer TINYINT(1) DEFAULT 0,
    footer_label VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_active (is_active),
    INDEX idx_footer (show_in_footer)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
