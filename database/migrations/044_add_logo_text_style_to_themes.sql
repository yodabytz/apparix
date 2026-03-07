-- Add logo text weight, stretch, and spacing columns to themes table
ALTER TABLE themes ADD COLUMN IF NOT EXISTS logo_text_weight VARCHAR(10) DEFAULT NULL AFTER logo_text_size;
ALTER TABLE themes ADD COLUMN IF NOT EXISTS logo_text_stretch VARCHAR(20) DEFAULT NULL AFTER logo_text_weight;
ALTER TABLE themes ADD COLUMN IF NOT EXISTS logo_text_spacing VARCHAR(10) DEFAULT NULL AFTER logo_text_stretch;
