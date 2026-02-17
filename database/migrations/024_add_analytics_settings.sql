-- Add analytics and tracking settings
INSERT INTO settings (setting_key, setting_value, setting_type, category, is_public) VALUES
-- Google
('google_tag_manager_id', '', 'string', 'integrations', 0),
('google_analytics_id', '', 'string', 'integrations', 0),
('google_adsense_id', '', 'string', 'integrations', 0),
('google_ads_conversion_id', '', 'string', 'integrations', 0),

-- Social Media Pixels
('facebook_pixel_id', '', 'string', 'integrations', 0),
('tiktok_pixel_id', '', 'string', 'integrations', 0),
('pinterest_tag_id', '', 'string', 'integrations', 0),
('snapchat_pixel_id', '', 'string', 'integrations', 0),

-- Other
('microsoft_uet_tag_id', '', 'string', 'integrations', 0),
('custom_head_scripts', '', 'string', 'integrations', 0),
('custom_body_scripts', '', 'string', 'integrations', 0)

ON DUPLICATE KEY UPDATE setting_key = setting_key;
