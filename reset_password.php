<?php
require_once 'config.php';

// Password baru yang ingin diset
$new_password = 'admin123';

// Membuat hash password baru sesuai algoritma server
$hash = password_hash($new_password, PASSWORD_DEFAULT);
$username = 'admin';

// Update password di database
$sql = "UPDATE users SET password = '$hash' WHERE username = '$username'";

echo "<h3>Reset Password Tool</h3>";

if ($conn->query($sql) === TRUE) {
    echo "<div style='color: green; padding: 10px; border: 1px solid green; background: #e8f5e9;'>";
    echo "✅ Password untuk user <strong>'$username'</strong> berhasil direset.<br>";
    echo "Password baru: <strong>$new_password</strong><br>";
    echo "Hash baru: <small>$hash</small>";
    echo "</div>";
    echo "<br><a href='login.php'>Kembali ke Halaman Login</a>";
} else {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; background: #ffebee;'>";
    echo "❌ Error updating record: " . $conn->error;
    echo "</div>";
}
?>
