-- Add theme-specific logo and hero image columns
ALTER TABLE themes ADD COLUMN IF NOT EXISTS theme_logo VARCHAR(500) DEFAULT NULL AFTER thumbnail;
ALTER TABLE themes ADD COLUMN IF NOT EXISTS hero_image VARCHAR(500) DEFAULT NULL AFTER theme_logo;
