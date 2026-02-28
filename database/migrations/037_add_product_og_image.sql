-- Add OG image column for social sharing on category pages
ALTER TABLE categories ADD COLUMN og_image VARCHAR(500) DEFAULT NULL AFTER image;
