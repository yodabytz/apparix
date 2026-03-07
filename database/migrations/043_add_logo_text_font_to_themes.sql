-- Add logo text font and size to themes table
ALTER TABLE themes ADD COLUMN IF NOT EXISTS logo_text_font VARCHAR(100) DEFAULT NULL AFTER logo_height;
ALTER TABLE themes ADD COLUMN IF NOT EXISTS logo_text_size INT DEFAULT NULL AFTER logo_text_font;

-- Set Summit Ridge preset to use Barlow Condensed
UPDATE themes SET heading_font = 'Barlow Condensed', logo_text_font = 'Barlow Condensed' WHERE slug = 'summitridge';
