-- Add social media links settings
INSERT INTO settings (setting_key, setting_value, setting_type, category, is_public) VALUES
-- Social Media Links
('social_facebook', '', 'string', 'store', 1),
('social_instagram', '', 'string', 'store', 1),
('social_twitter', '', 'string', 'store', 1),
('social_tiktok', '', 'string', 'store', 1),
('social_youtube', '', 'string', 'store', 1),
('social_pinterest', '', 'string', 'store', 1),
('social_linkedin', '', 'string', 'store', 1),
('social_etsy', '', 'string', 'store', 1),
('social_amazon', '', 'string', 'store', 1),
('social_discord', '', 'string', 'store', 1),
('social_threads', '', 'string', 'store', 1),

-- Payment Settings (Stripe)
('stripe_mode', 'test', 'string', 'integrations', 0),
('stripe_test_public_key', '', 'string', 'integrations', 0),
('stripe_test_secret_key', '', 'string', 'integrations', 0),
('stripe_live_public_key', '', 'string', 'integrations', 0),
('stripe_live_secret_key', '', 'string', 'integrations', 0),
('stripe_webhook_secret', '', 'string', 'integrations', 0),

-- Other Payment Options
('paypal_enabled', '0', 'boolean', 'integrations', 0),
('paypal_client_id', '', 'string', 'integrations', 0),
('paypal_secret', '', 'string', 'integrations', 0),
('paypal_mode', 'sandbox', 'string', 'integrations', 0)

ON DUPLICATE KEY UPDATE setting_key = setting_key;
