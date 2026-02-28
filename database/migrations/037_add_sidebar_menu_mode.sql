ALTER TABLE themes ADD COLUMN IF NOT EXISTS sidebar_menu_mode ENUM('click','hover','expanded') DEFAULT 'hover' AFTER category_layout;
