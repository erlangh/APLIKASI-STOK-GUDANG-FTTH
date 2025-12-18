-- Update for Item Images and Stock Transfer
-- Version 6.0

-- Add image column to items table
ALTER TABLE `items` ADD COLUMN `image_path` varchar(255) DEFAULT NULL AFTER `description`;

-- Modify transactions type to allow 'transfer' (changing to varchar to be flexible, or extending enum)
-- Assuming it might be ENUM, we alter it to VARCHAR to support 'in', 'out', 'transfer_in', 'transfer_out'
ALTER TABLE `transactions` MODIFY COLUMN `type` VARCHAR(20) NOT NULL;
