<?php
require_once 'includes/auth.php';
require_once 'config.php';
require_once 'includes/header.php';

checkLogin();

// Fetch items for dropdown
$items_result = $conn->query("SELECT * FROM items ORDER BY name ASC");
$items_arr = [];
if ($items_result) {
    while ($row = $items_result->fetch_assoc()) {
        $items_arr[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Process form
    $letter_number = $conn->real_escape_string($_POST['letter_number']);
    $type = $conn->real_escape_string($_POST['type']);
    $date = $conn->real_escape_string($_POST['date']);
    $admin_name = $conn->real_escape_string($_POST['admin_name']);
    $recipient = $conn->real_escape_string($_POST['recipient']);
    $notes = $conn->real_escape_string($_POST['notes']);
    
    $item_ids = $_POST['item_id']; // Array
    $quantities = $_POST['quantity']; // Array

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO letters (letter_number, type, date, admin_name, recipient, notes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $letter_number, $type, $date, $admin_name, $recipient, $notes);
        $stmt->execute();
        $letter_id = $conn->insert_id;

        $stmt_item = $conn->prepare("INSERT INTO letter_items (letter_id, item_id, quantity) VALUES (?, ?, ?)");
        for ($i = 0; $i < count($item_ids); $i++) {
            if (!empty($item_ids[$i]) && !empty($quantities[$i])) {
                $stmt_item->bind_param("iii", $letter_id, $item_ids[$i], $quantities[$i]);
                $stmt_item->execute();
            }
        }

        $conn->commit();
        echo "<script>alert('Surat berhasil dibuat!'); window.location.href='letters.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}
?>

<div class="container mt-4">
    <h2>Buat Surat Baru</h2>
    <form method="POST" action="" id="letterForm">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">No. Surat</label>
                    <input type="text" class="form-control" name="letter_number" required placeholder="Contoh: 001/SJ/KDK/XII/2024">
                </div>
                <div class="mb-3">
                    <label class="form-label">Jenis Surat</label>
                    <select class="form-select" name="type" required>
                        <option value="out">Surat Jalan (Barang Keluar)</option>
                        <option value="in">Surat Masuk (Barang Masuk)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tanggal</label>
                    <input type="date" class="form-control" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Admin Gudang</label>
                    <input type="text" class="form-control" name="admin_name" required placeholder="Nama Admin yang bertanda tangan">
                </div>
                <div class="mb-3">
                    <label class="form-label">Penerima / Pengirim</label>
                    <input type="text" class="form-control" name="recipient" placeholder="Nama Penerima (untuk Surat Jalan) atau Pengirim (untuk Surat Masuk)" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Keterangan</label>
                    <textarea class="form-control" name="notes" rows="3"></textarea>
                </div>
            </div>
        </div>

        <hr>
        <h4>Daftar Barang</h4>
        <table class="table table-bordered" id="itemsTable">
            <thead>
                <tr>
                    <th>Nama Barang</th>
                    <th>Jumlah</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <select class="form-select" name="item_id[]" required>
                            <option value="">Pilih Barang</option>
                            <?php foreach ($items_arr as $item): ?>
                                <option value="<?php echo $item['id']; ?>">
                                    <?php echo htmlspecialchars($item['name']); ?> (Stok: <?php echo $item['quantity']; ?> <?php echo $item['unit']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <input type="number" class="form-control" name="quantity[]" min="1" required>
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            </tbody>
        </table>
        <button type="button" class="btn btn-success mb-3" id="addRow"><i class="fas fa-plus"></i> Tambah Baris</button>

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary btn-lg">Simpan Surat</button>
        </div>
    </form>
</div>

<script>
document.getElementById('addRow').addEventListener('click', function() {
    var table = document.getElementById('itemsTable').getElementsByTagName('tbody')[0];
    var newRow = table.rows[0].cloneNode(true);
    // Clear values
    newRow.querySelector('select').value = '';
    newRow.querySelector('input').value = '';
    table.appendChild(newRow);
});

document.getElementById('itemsTable').addEventListener('click', function(e) {
    if (e.target.closest('.remove-row')) {
        var row = e.target.closest('tr');
        if (document.querySelectorAll('#itemsTable tbody tr').length > 1) {
            row.remove();
        } else {
            alert('Minimal satu barang harus ada.');
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
