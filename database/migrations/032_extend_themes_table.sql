-- Extend themes table for installable theme packages
ALTER TABLE themes ADD COLUMN IF NOT EXISTS source ENUM('preset', 'installed') DEFAULT 'preset' AFTER is_active;
ALTER TABLE themes ADD COLUMN IF NOT EXISTS package_path VARCHAR(500) AFTER source;
ALTER TABLE themes ADD COLUMN IF NOT EXISTS manifest JSON AFTER package_path;
ALTER TABLE themes ADD COLUMN IF NOT EXISTS menu_config JSON AFTER manifest;
ALTER TABLE themes ADD COLUMN IF NOT EXISTS sections_config JSON AFTER menu_config;
ALTER TABLE themes ADD COLUMN IF NOT EXISTS thumbnail VARCHAR(500) AFTER preview_image;

-- Update existing themes to be presets
UPDATE themes SET source = 'preset' WHERE source IS NULL;
