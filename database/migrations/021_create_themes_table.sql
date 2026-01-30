-- Themes table for pre-built and custom themes
CREATE TABLE IF NOT EXISTS themes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    slug VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_preset BOOLEAN DEFAULT 0,
    is_active BOOLEAN DEFAULT 0,

    -- Core Colors
    primary_color VARCHAR(7) DEFAULT '#FF68C5',
    secondary_color VARCHAR(7) DEFAULT '#FF94C8',
    accent_color VARCHAR(7) DEFAULT '#FFE4F3',

    -- Generated Color Variants (stored as JSON)
    color_variants JSON,

    -- Typography
    heading_font VARCHAR(100) DEFAULT 'Playfair Display',
    body_font VARCHAR(100) DEFAULT 'Inter',

    -- Layout Options
    layout_style ENUM('sidebar', 'full-width') DEFAULT 'sidebar',
    header_style ENUM('standard', 'centered', 'minimal') DEFAULT 'standard',
    homepage_layout JSON,
    category_layout ENUM('grid', 'list', 'masonry') DEFAULT 'grid',
    product_grid_columns INT DEFAULT 4,

    -- Custom CSS Override
    custom_css TEXT,

    -- Preview image for theme selection
    preview_image VARCHAR(255),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_active (is_active),
    INDEX idx_preset (is_preset)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
