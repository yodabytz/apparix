-- Add variant_id to order_items for digital product handling
ALTER TABLE order_items
ADD COLUMN variant_id INT DEFAULT NULL AFTER product_id,
ADD INDEX idx_variant_id (variant_id);
