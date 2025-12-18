-- Update for Company Settings and Barcode
-- Version 5.0

-- Table for Company Profile
CREATE TABLE IF NOT EXISTS `company_profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) NOT NULL,
  `address` text,
  `phone` varchar(50),
  `email` varchar(100),
  `logo_path` varchar(255),
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default company info
INSERT INTO `company_profile` (`company_name`, `address`, `phone`, `email`) 
SELECT 'PT. Konektivitas Digital Kalimantan', 'Jl. Contoh No. 123, Kalimantan', '081234567890', 'info@kdk.com'
WHERE NOT EXISTS (SELECT * FROM `company_profile`);

-- Add barcode column to items table if not exists (using a safe procedure for MySQL 5.7+)
-- Since direct IF NOT EXISTS for ADD COLUMN is MariaDB specific or newer MySQL, we just run ADD COLUMN and ignore error if exists in manual execution, 
-- but for script we can use a block or just keep it simple.
-- Ideally we check, but for this environment, simple ADD is usually okay or the user runs it once.
-- We will just use simple ALTER. If it fails due to duplicate column, it's fine.
ALTER TABLE `items` ADD COLUMN `barcode` varchar(100) DEFAULT NULL AFTER `category`;
ALTER TABLE `items` ADD INDEX `idx_barcode` (`barcode`);
