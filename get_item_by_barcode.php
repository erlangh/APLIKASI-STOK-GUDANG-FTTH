<?php
require_once 'config.php';
require_once 'includes/auth.php';

// Allow access if logged in
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

if (isset($_GET['barcode'])) {
    $barcode = $conn->real_escape_string($_GET['barcode']);
    
    // Search by barcode exactly
    $sql = "SELECT * FROM items WHERE barcode = '$barcode' LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $item = $result->fetch_assoc();
        echo json_encode(['status' => 'found', 'data' => $item]);
    } else {
        echo json_encode(['status' => 'not_found']);
    }
} else {
    echo json_encode(['error' => 'No barcode provided']);
}
?>
