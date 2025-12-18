PANDUAN INSTALASI APLIKASI STOK GUDANG FTTH
=============================================

1. Persiapan Database (cPanel)
   - Masuk ke cPanel hosting Anda.
   - Cari menu "MySQL Database Wizard".
   - Buat database baru (misal: `namauser_ftth`).
   - Buat user database baru (misal: `namauser_admin`) dan passwordnya.
   - Tambahkan user ke database dan centang "ALL PRIVILEGES".

2. Import Struktur Database
   - Buka menu "phpMyAdmin" di cPanel.
   - Pilih database yang baru dibuat di sebelah kiri.
   - Klik tab "Import" di bagian atas.
   - Klik "Choose File" dan pilih file `database.sql` yang ada di folder ini.
   - Klik "Go" atau "Kirim".

3. Konfigurasi Koneksi
   - Buka file `config.php` menggunakan text editor (Notepad, VS Code, atau File Manager cPanel).
   - Ubah bagian berikut sesuai data yang Anda buat di langkah 1:
     $host = 'localhost';
     $user = 'namauser_admin';  <-- Ganti ini
     $pass = 'password_anda';   <-- Ganti ini
     $db   = 'namauser_ftth';   <-- Ganti ini
   - Simpan perubahan.

4. Upload File
   - Upload semua file dan folder dalam proyek ini ke folder subdomain Anda (biasanya di dalam `public_html`).
   - Pastikan folder `assets` dan `includes` juga terupload.

5. Logo Perusahaan
   - Siapkan file logo perusahaan Anda (format PNG atau JPG).
   - Beri nama file tersebut `logo.png`.
   - Upload ke dalam folder `assets/img/`.

6. Penggunaan
   - Buka alamat website subdomain Anda.
   - Login dengan akun default:
     Username: admin
     Password: admin123
   - Segera ganti password atau buat user baru jika diperlukan (saat ini fitur ganti password bisa dilakukan via phpMyAdmin untuk keamanan lebih lanjut atau request fitur tambahan).

7. Update Fitur Tambahan (Penting)
   Aplikasi ini telah mengalami beberapa pembaruan. Pastikan Anda juga mengimport file database berikut di phpMyAdmin agar semua fitur (Transfer Stok, Gambar, Surat Jalan, dll) berjalan lancar:
   
   Urutan Import:
   1. `database.sql` (Struktur Utama)
   2. `database_v2.sql` (Tabel Transaksi)
   3. `database_v3.sql` (Update Kolom Item)
   4. `database_v4.sql` (Surat Jalan & User Management)
   5. `database_v5.sql` (Profil Perusahaan)
   6. `database_v6.sql` (Fitur Transfer Stok & Upload Gambar)

   Pastikan folder `uploads/items` dan `uploads/company` memiliki izin tulis (permissions 755 atau 777) agar fitur upload gambar berfungsi.

Selamat menggunakan!

