<?php
require_once 'config.php';
require_once 'includes/auth.php';
checkLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_id = (int)$_POST['item_id'];
    $target_warehouse = $conn->real_escape_string($_POST['target_warehouse']);
    $quantity = (int)$_POST['quantity'];
    $notes = $conn->real_escape_string($_POST['notes']);
    $user_id = $_SESSION['user_id'];

    if ($item_id > 0 && $quantity > 0 && !empty($target_warehouse)) {
        
        $conn->begin_transaction();
        
        try {
            // 1. Get Source Item
            $sql_source = "SELECT * FROM items WHERE id = $item_id FOR UPDATE";
            $res_source = $conn->query($sql_source);
            
            if ($res_source->num_rows == 0) {
                throw new Exception("Barang asal tidak ditemukan.");
            }
            
            $source_item = $res_source->fetch_assoc();
            
            // Check if source warehouse is same as target
            if (trim(strtolower($source_item['warehouse_name'])) == trim(strtolower($target_warehouse))) {
                throw new Exception("Gudang tujuan sama dengan gudang asal.");
            }
            
            // Check stock
            if ($source_item['quantity'] < $quantity) {
                throw new Exception("Stok tidak mencukupi untuk transfer.");
            }
            
            // 2. Reduce Source Stock
            $new_source_qty = $source_item['quantity'] - $quantity;
            $conn->query("UPDATE items SET quantity = $new_source_qty WHERE id = $item_id");
            
            // Log Source Transaction (Transfer Out)
            $note_out = "Transfer ke $target_warehouse. " . $notes;
            $conn->query("INSERT INTO transactions (item_id, user_id, type, quantity, notes) VALUES ($item_id, $user_id, 'transfer_out', $quantity, '$note_out')");
            
            // 3. Find or Create Target Item
            // Search for item with same Name, Category, Unit in Target Warehouse
            // We use prepared statement for safety and better matching
            $stmt_check = $conn->prepare("SELECT id, quantity FROM items WHERE name = ? AND category = ? AND unit = ? AND warehouse_name = ?");
            $stmt_check->bind_param("ssss", $source_item['name'], $source_item['category'], $source_item['unit'], $target_warehouse);
            $stmt_check->execute();
            $res_target = $stmt_check->get_result();
            
            if ($res_target->num_rows > 0) {
                // Target item exists, update quantity
                $target_item = $res_target->fetch_assoc();
                $target_id = $target_item['id'];
                $new_target_qty = $target_item['quantity'] + $quantity;
                
                $conn->query("UPDATE items SET quantity = $new_target_qty WHERE id = $target_id");
            } else {
                // Target item does not exist, create it
                // We copy most fields from source, but set new warehouse and quantity
                // Note: We don't copy ID. Barcode might be same? If barcode is unique, we might have issue. 
                // Usually barcode is same for same product regardless of location.
                // If `barcode` column has UNIQUE index, we might fail. 
                // Let's assume barcode represents the product SKU and can be duplicated across locations OR we nullify it if unique.
                // For now, let's copy barcode too.
                
                $stmt_insert = $conn->prepare("INSERT INTO items (name, category, barcode, serial_number, warehouse_name, location, map_link, quantity, unit, description, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                // Location map_link etc usually specific to warehouse, so maybe reset them?
                // User only provided warehouse name. We leave location/map_link empty or copy?
                // Better to leave location details empty for the new warehouse item as they are likely different.
                $empty_loc = "";
                $empty_map = "";
                
                $stmt_insert->bind_param("sssssssisss", 
                    $source_item['name'], 
                    $source_item['category'], 
                    $source_item['barcode'], 
                    $source_item['serial_number'], 
                    $target_warehouse, 
                    $empty_loc, 
                    $empty_map, 
                    $quantity, 
                    $source_item['unit'], 
                    $source_item['description'],
                    $source_item['image_path']
                );
                $stmt_insert->execute();
                $target_id = $conn->insert_id;
            }
            
            // Log Target Transaction (Transfer In)
            $note_in = "Transfer dari " . $source_item['warehouse_name'] . ". " . $notes;
            $conn->query("INSERT INTO transactions (item_id, user_id, type, quantity, notes) VALUES ($target_id, $user_id, 'transfer_in', $quantity, '$note_in')");
            
            $conn->commit();
            
            $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : 'index.php';
            header("Location: $redirect?msg=transfer_success");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            echo "<script>alert('Gagal transfer: " . $e->getMessage() . "'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Data tidak valid!'); window.history.back();</script>";
    }
} else {
    header("Location: index.php");
}
?>