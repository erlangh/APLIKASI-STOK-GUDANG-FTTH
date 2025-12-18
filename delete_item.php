<?php
require_once 'config.php';
require_once 'includes/auth.php';
checkLogin();

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql = "DELETE FROM items WHERE id = $id";
    
    if ($conn->query($sql)) {
        header("Location: index.php?msg=deleted");
    } else {
        echo "Error deleting record: " . $conn->error;
    }
} else {
    header("Location: index.php");
}
?>
