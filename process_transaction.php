<?php
require_once 'config.php';
require_once 'includes/auth.php';
checkLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_id = (int)$_POST['item_id'];
    $type = $conn->real_escape_string($_POST['type']);
    $quantity = (int)$_POST['quantity'];
    $notes = $conn->real_escape_string($_POST['notes']);
    $user_id = $_SESSION['user_id'];

    if ($item_id > 0 && $quantity > 0 && ($type == 'in' || $type == 'out')) {
        
        // 1. Ambil stok saat ini
        $sql_check = "SELECT quantity FROM items WHERE id = $item_id";
        $result = $conn->query($sql_check);
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $current_qty = $row['quantity'];
            
            // 2. Cek jika barang keluar melebihi stok
            if ($type == 'out' && $quantity > $current_qty) {
                echo "<script>alert('Stok tidak mencukupi!'); window.location.href='index.php';</script>";
                exit();
            }

            // 3. Update stok di tabel items
            if ($type == 'in') {
                $new_qty = $current_qty + $quantity;
            } else {
                $new_qty = $current_qty - $quantity;
            }
            
            $sql_update = "UPDATE items SET quantity = $new_qty WHERE id = $item_id";
            
            // 4. Simpan ke riwayat transaksi
            $sql_log = "INSERT INTO transactions (item_id, user_id, type, quantity, notes) VALUES ($item_id, $user_id, '$type', $quantity, '$notes')";
            
            if ($conn->query($sql_update) && $conn->query($sql_log)) {
                header("Location: index.php?msg=transaction_success");
                exit();
            } else {
                echo "Error: " . $conn->error;
            }
            
        } else {
            echo "Barang tidak ditemukan.";
        }
    } else {
        echo "Data tidak valid.";
    }
} else {
    header("Location: index.php");
}
?>
