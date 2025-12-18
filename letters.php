<?php
require_once 'includes/auth.php';
require_once 'config.php';
require_once 'includes/header.php';

checkLogin();

// Handle Delete Letter
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $sql = "DELETE FROM letters WHERE id = $id";
    if ($conn->query($sql)) {
        echo "<script>alert('Surat berhasil dihapus!'); window.location.href='letters.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus surat: " . $conn->error . "');</script>";
    }
}

$letters = $conn->query("SELECT * FROM letters ORDER BY date DESC, created_at DESC");
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Daftar Surat Masuk & Jalan</h2>
        <a href="create_letter.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Buat Surat Baru
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th>No. Surat</th>
                            <th>Jenis</th>
                            <th>Tanggal</th>
                            <th>Admin Gudang</th>
                            <th>Penerima/Pengirim</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($letters->num_rows > 0): ?>
                            <?php $no = 1; while ($row = $letters->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($row['letter_number']); ?></td>
                                    <td>
                                        <?php if ($row['type'] == 'in'): ?>
                                            <span class="badge bg-success">Surat Masuk</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Surat Jalan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d-m-Y', strtotime($row['date'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['admin_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['recipient'] ?? '-'); ?></td>
                                    <td>
                                        <a href="print_letter.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn btn-secondary btn-sm" title="Cetak">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <a href="edit_letter.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="letters.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus surat ini?')" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">Belum ada surat.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
