-- Seed the 5 pre-built industry themes

-- Apparix Theme (Default - modern blue professional look)
INSERT INTO themes (
    slug, name, description, is_preset, is_active,
    primary_color, secondary_color, accent_color,
    navbar_bg_color, navbar_text_color, glow_color,
    color_variants,
    heading_font, body_font,
    layout_style, header_style, category_layout, product_grid_columns,
    effect_settings
) VALUES (
    'apparix',
    'Apparix',
    'Modern and professional, the signature Apparix theme',
    1, 1,
    '#2186c4', '#83b1ec', '#5d82b1',
    '#e3e5ee', '#303030', '#5d82b1',
    '{"primary_light":"#27A0EB","primary_dark":"#1A6B9C","primary_50":"#2FC2FF","secondary_light":"#9DD4FF","secondary_dark":"#688DBC","accent_light":"#668FC2","accent_dark":"#415B7B"}',
    'Montserrat', 'Nunito',
    'sidebar', 'centered', 'list', 4,
    '{"button_glow":{"enabled":true,"intensity":"subtle"},"hover_animations":{"enabled":true,"speed":"normal"},"background_animation":{"enabled":true,"style":"particles"},"page_transitions":{"enabled":true,"style":"fade-up"},"shimmer_effects":{"enabled":true},"shadow_style":"soft","border_radius":"slightly-rounded","card_hover":{"enabled":true,"style":"lift"}}'
) ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Boutique Theme
INSERT INTO themes (
    slug, name, description, is_preset, is_active,
    primary_color, secondary_color, accent_color,
    color_variants,
    heading_font, body_font,
    layout_style, header_style, category_layout, product_grid_columns
) VALUES (
    'boutique',
    'Boutique',
    'Elegant and feminine, perfect for handmade goods, jewelry, and gifts',
    1, 0,
    '#FF68C5', '#FF94C8', '#FFE4F3',
    '{"primary_light":"#FF8AD4","primary_dark":"#CC53A0","primary_50":"#FFD4ED","secondary_light":"#FFB0D6","secondary_dark":"#CC76A0","accent_light":"#FFF0F8","accent_dark":"#FFB0D6"}',
    'Playfair Display', 'Inter',
    'sidebar', 'standard', 'grid', 4
) ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Tech Theme
INSERT INTO themes (
    slug, name, description, is_preset, is_active,
    primary_color, secondary_color, accent_color,
    color_variants,
    heading_font, body_font,
    layout_style, header_style, category_layout, product_grid_columns
) VALUES (
    'tech',
    'Tech',
    'Modern and minimal, ideal for electronics, software, and digital products',
    1, 0,
    '#3B82F6', '#60A5FA', '#DBEAFE',
    '{"primary_light":"#5B9DF7","primary_dark":"#2F68C5","primary_50":"#B3D4FC","secondary_light":"#80B7FB","secondary_dark":"#4D84C8","accent_light":"#EBF4FF","accent_dark":"#A3CCFD"}',
    'Inter', 'Inter',
    'full-width', 'minimal', 'grid', 4
) ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Fashion Theme
INSERT INTO themes (
    slug, name, description, is_preset, is_active,
    primary_color, secondary_color, accent_color,
    color_variants,
    heading_font, body_font,
    layout_style, header_style, category_layout, product_grid_columns
) VALUES (
    'fashion',
    'Fashion',
    'Bold and editorial, designed for clothing, accessories, and lifestyle brands',
    1, 0,
    '#000000', '#374151', '#F3F4F6',
    '{"primary_light":"#333333","primary_dark":"#000000","primary_50":"#666666","secondary_light":"#4B5563","secondary_dark":"#1F2937","accent_light":"#F9FAFB","accent_dark":"#E5E7EB"}',
    'Playfair Display', 'Inter',
    'full-width', 'centered', 'masonry', 3
) ON DUPLICATE KEY UPDATE name = VALUES(name);

-- General Theme
INSERT INTO themes (
    slug, name, description, is_preset, is_active,
    primary_color, secondary_color, accent_color,
    color_variants,
    heading_font, body_font,
    layout_style, header_style, category_layout, product_grid_columns
) VALUES (
    'general',
    'General',
    'Versatile and professional, works for any industry',
    1, 0,
    '#10B981', '#34D399', '#D1FAE5',
    '{"primary_light":"#34D399","primary_dark":"#0D9668","primary_50":"#6EE7B7","secondary_light":"#5DDCAB","secondary_dark":"#2AA97A","accent_light":"#E6FFF5","accent_dark":"#A7F3D0"}',
    'Inter', 'Inter',
    'sidebar', 'standard', 'grid', 4
) ON DUPLICATE KEY UPDATE name = VALUES(name);
