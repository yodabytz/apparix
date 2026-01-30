-- Add hero section settings
INSERT INTO settings (setting_key, setting_value, setting_type, category, is_public) VALUES
-- Hero Text Content
('hero_heading', 'Welcome to {store_name}', 'string', 'store', 1),
('hero_taglines', '["Discover quality products curated just for you","Find something special today","Quality meets style in every product","Your next favorite item awaits"]', 'json', 'store', 1),
('hero_cta_text', 'Shop Now', 'string', 'store', 1),
('hero_cta_url', '/products', 'string', 'store', 1),

-- Hero Visual Settings
('hero_background_style', 'gradient-dark', 'string', 'theme', 1),
('hero_show_glow', '1', 'boolean', 'theme', 1),
('hero_show_shimmer', '1', 'boolean', 'theme', 1),
('hero_rotate_taglines', '1', 'boolean', 'theme', 1),
('hero_tagline_interval', '8', 'integer', 'theme', 1),
('hero_overlay_opacity', '0.12', 'string', 'theme', 1)

ON DUPLICATE KEY UPDATE setting_key = setting_key;
