-- Add options column to order_items table
ALTER TABLE order_items ADD COLUMN options TEXT AFTER price; 