-- Update Struktur Database untuk Fitur Lengkap (Versi 3)

-- Menambahkan kolom baru ke tabel items
ALTER TABLE `items`
ADD COLUMN `serial_number` varchar(100) DEFAULT NULL AFTER `category`,
ADD COLUMN `warehouse_name` varchar(100) DEFAULT NULL AFTER `unit`,
ADD COLUMN `location` varchar(255) DEFAULT NULL AFTER `warehouse_name`,
ADD COLUMN `map_link` text DEFAULT NULL AFTER `location`;

-- Memperbarui tipe data enum untuk kolom tipe transaksi jika diperlukan (opsional, karena sudah enum 'in','out')
-- ALTER TABLE `transactions` MODIFY COLUMN `type` enum('in','out') NOT NULL;
