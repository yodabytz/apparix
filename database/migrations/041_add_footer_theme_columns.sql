ALTER TABLE themes ADD COLUMN footer_bg_color VARCHAR(7) DEFAULT '#12121f' AFTER navbar_bg_image;
ALTER TABLE themes ADD COLUMN footer_text_color VARCHAR(7) DEFAULT '#FFFFFF' AFTER footer_bg_color;
ALTER TABLE themes ADD COLUMN footer_bg_image VARCHAR(500) DEFAULT NULL AFTER footer_text_color;
