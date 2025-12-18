-- Update for Letters and User Management
-- Version 4.0

-- Table for Letters (Surat Masuk / Surat Jalan)
CREATE TABLE IF NOT EXISTS `letters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `letter_number` varchar(100) NOT NULL,
  `type` enum('in', 'out') NOT NULL COMMENT 'in: Surat Masuk, out: Surat Jalan',
  `date` date NOT NULL,
  `admin_name` varchar(100) NOT NULL,
  `recipient` varchar(100) DEFAULT NULL COMMENT 'Penerima / Pengirim',
  `notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for Letter Items
CREATE TABLE IF NOT EXISTS `letter_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `letter_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`letter_id`) REFERENCES `letters`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`item_id`) REFERENCES `items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
