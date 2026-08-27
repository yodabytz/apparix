-- Migration 051: Record Printify Sync live shipping support

UPDATE plugins
SET version = '1.0.6',
    description = 'Sync selected Apparix products, live shipping rates, and eligible orders with Printify.'
WHERE slug = 'printify-sync';
