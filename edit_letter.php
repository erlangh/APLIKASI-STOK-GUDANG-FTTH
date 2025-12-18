<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/auth.php';
require_once 'config.php';

// Check login before any output
checkLogin();

if (!isset($_GET['id'])) {
    echo "<script>window.location.href='letters.php';</script>";
    exit();
}

$id = (int)$_GET['id'];

// Fetch Letter Data using direct query (safer compatibility)
$sql = "SELECT * FROM letters WHERE id = $id";
$result = $conn->query($sql);

if (!$result || $result->num_rows == 0) {
    echo "<script>alert('Surat tidak ditemukan!'); window.location.href='letters.php';</script>";
    exit();
}

$letter = $result->fetch_assoc();

// Fetch Existing Items
$sql_items = "SELECT * FROM letter_items WHERE letter_id = $id";
$result_items = $conn->query($sql_items);
$existing_items = [];
if ($result_items) {
    while ($row = $result_items->fetch_assoc()) {
        $existing_items[] = $row;
    }
}

// Fetch All Items for Dropdown
$items_result = $conn->query("SELECT * FROM items ORDER BY name ASC");
$items_arr = [];
if ($items_result) {
    while ($row = $items_result->fetch_assoc()) {
        $items_arr[] = $row;
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Process form
    $letter_number = $conn->real_escape_string($_POST['letter_number']);
    $type = $conn->real_escape_string($_POST['type']);
    $date = $conn->real_escape_string($_POST['date']);
    $admin_name = $conn->real_escape_string($_POST['admin_name']);
    $recipient = $conn->real_escape_string($_POST['recipient']);
    $notes = $conn->real_escape_string($_POST['notes']);
    
    $item_ids = isset($_POST['item_id']) ? $_POST['item_id'] : []; // Array
    $quantities = isset($_POST['quantity']) ? $_POST['quantity'] : []; // Array

    $conn->begin_transaction();
    try {
        // Update Letter Details
        $stmt = $conn->prepare("UPDATE letters SET letter_number=?, type=?, date=?, admin_name=?, recipient=?, notes=? WHERE id=?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("ssssssi", $letter_number, $type, $date, $admin_name, $recipient, $notes, $id);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        // Update Items: Delete all existing and re-insert
        $conn->query("DELETE FROM letter_items WHERE letter_id = $id");

        $stmt_item = $conn->prepare("INSERT INTO letter_items (letter_id, item_id, quantity) VALUES (?, ?, ?)");
        if (!$stmt_item) {
             throw new Exception("Prepare item failed: " . $conn->error);
        }
        
        for ($i = 0; $i < count($item_ids); $i++) {
            if (!empty($item_ids[$i]) && !empty($quantities[$i])) {
                $stmt_item->bind_param("iii", $id, $item_ids[$i], $quantities[$i]);
                $stmt_item->execute();
            }
        }

        $conn->commit();
        echo "<script>alert('Surat berhasil diperbarui!'); window.location.href='letters.php';</script>";
        exit(); // Stop execution after redirect
    } catch (Exception $e) {
        $conn->rollback();
        $error_msg = $e->getMessage();
    }
}

// Start Output
require_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Edit Surat</h2>
        <a href="letters.php" class="btn btn-secondary">Kembali</a>
    </div>

    <?php if (isset($error_msg)): ?>
    <div class="alert alert-danger"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <form method="POST" action="" id="letterForm">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">No. Surat</label>
                    <input type="text" class="form-control" name="letter_number" required value="<?php echo htmlspecialchars($letter['letter_number']); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Jenis Surat</label>
                    <select class="form-select" name="type" required>
                        <option value="out" <?php if($letter['type'] == 'out') echo 'selected'; ?>>Surat Jalan (Barang Keluar)</option>
                        <option value="in" <?php if($letter['type'] == 'in') echo 'selected'; ?>>Surat Masuk (Barang Masuk)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tanggal</label>
                    <input type="date" class="form-control" name="date" required value="<?php echo date('Y-m-d', strtotime($letter['date'])); ?>">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Admin Gudang</label>
                    <input type="text" class="form-control" name="admin_name" required value="<?php echo htmlspecialchars($letter['admin_name']); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Penerima / Pengirim</label>
                    <input type="text" class="form-control" name="recipient" required value="<?php echo htmlspecialchars($letter['recipient']); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Keterangan</label>
                    <textarea class="form-control" name="notes" rows="3"><?php echo htmlspecialchars($letter['notes']); ?></textarea>
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
                <?php if (count($existing_items) > 0): ?>
                    <?php foreach ($existing_items as $ex_item): ?>
                        <tr>
                            <td>
                                <select class="form-select" name="item_id[]" required>
                                    <option value="">Pilih Barang</option>
                                    <?php foreach ($items_arr as $item): ?>
                                        <option value="<?php echo $item['id']; ?>" <?php if($item['id'] == $ex_item['item_id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($item['name']); ?> (Stok: <?php echo $item['quantity']; ?> <?php echo $item['unit']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="number" class="form-control" name="quantity[]" min="1" required value="<?php echo $ex_item['quantity']; ?>">
                            </td>
                            <td>
                                <button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Default Empty Row -->
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
                <?php endif; ?>
            </tbody>
        </table>
        <button type="button" class="btn btn-success mb-3" id="addRow"><i class="fas fa-plus"></i> Tambah Baris</button>

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary btn-lg">Simpan Perubahan</button>
        </div>
    </form>
</div>

<script>
// Template for new row
const itemOptions = `<?php foreach ($items_arr as $item): ?>
    <option value="<?php echo $item['id']; ?>">
        <?php echo addslashes(htmlspecialchars($item['name'])); ?> (Stok: <?php echo $item['quantity']; ?> <?php echo $item['unit']; ?>)
    </option>
<?php endforeach; ?>`;

document.getElementById('addRow').addEventListener('click', function() {
    var table = document.getElementById('itemsTable').getElementsByTagName('tbody')[0];
    var newRow = table.insertRow();
    
    var cell1 = newRow.insertCell(0);
    var cell2 = newRow.insertCell(1);
    var cell3 = newRow.insertCell(2);

    cell1.innerHTML = `<select class="form-select" name="item_id[]" required><option value="">Pilih Barang</option>${itemOptions}</select>`;
    cell2.innerHTML = `<input type="number" class="form-control" name="quantity[]" min="1" required>`;
    cell3.innerHTML = `<button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button>`;
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