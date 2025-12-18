<?php
// Konfigurasi Database
$host = 'localhost';
$user = 'root';     // Ganti dengan username database cPanel Anda
$pass = '';         // Ganti dengan password database cPanel Anda
$db   = 'ftth_warehouse'; // Ganti dengan nama database Anda

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
