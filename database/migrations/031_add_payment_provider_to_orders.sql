-- Add payment_provider column to orders table for multi-provider support
ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_provider VARCHAR(50) DEFAULT 'stripe' AFTER payment_method;

-- Add index for querying orders by provider
ALTER TABLE orders ADD INDEX idx_payment_provider (payment_provider);
