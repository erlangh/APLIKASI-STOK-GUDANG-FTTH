<?php
require_once 'config.php';
require_once 'includes/auth.php';
checkLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $category = $conn->real_escape_string($_POST['category']);
    $serial_number = $conn->real_escape_string($_POST['serial_number']);
    $warehouse_name = $conn->real_escape_string($_POST['warehouse_name']);
    $location = $conn->real_escape_string($_POST['location']);
    $map_link = $conn->real_escape_string($_POST['map_link']);
    $quantity = (int)$_POST['quantity'];
    $unit = $conn->real_escape_string($_POST['unit']);
    $description = $conn->real_escape_string($_POST['description']);
    $barcode = isset($_POST['barcode']) ? $conn->real_escape_string($_POST['barcode']) : '';

    // Image Upload Logic
    $image_path = '';
    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] == 0) {
        $target_dir = "uploads/items/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES["item_image"]["name"], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_ext, $allowed)) {
            if ($_FILES["item_image"]["size"] <= 2000000) { // 2MB
                $new_filename = uniqid() . '.' . $file_ext;
                $target_file = $target_dir . $new_filename;
                
                if (move_uploaded_file($_FILES["item_image"]["tmp_name"], $target_file)) {
                    $image_path = $target_file;
                } else {
                    $error = "Gagal mengupload gambar.";
                }
            } else {
                $error = "Ukuran gambar terlalu besar (Maks 2MB).";
            }
        } else {
            $error = "Format gambar tidak didukung (Hanya JPG, PNG, GIF).";
        }
    }

    if (empty($error)) {
        if (empty($name) || empty($category) || empty($unit)) {
            $error = "Nama, Kategori, dan Satuan wajib diisi!";
        } else {
            $sql = "INSERT INTO items (name, category, barcode, serial_number, warehouse_name, location, map_link, quantity, unit, description, image_path) 
                    VALUES ('$name', '$category', '$barcode', '$serial_number', '$warehouse_name', '$location', '$map_link', $quantity, '$unit', '$description', '$image_path')";
            if ($conn->query($sql)) {
                $success = "Barang berhasil ditambahkan!";
            } else {
                $error = "Error: " . $conn->error;
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-custom">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Tambah Barang Baru</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Foto Barang (Opsional)</label>
                        <input type="file" class="form-control" name="item_image" accept="image/*">
                        <div class="form-text">Format: JPG, PNG, GIF. Maks: 2MB.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Barcode / SKU (Opsional)</label>
                        <input type="text" class="form-control" name="barcode" placeholder="Scan atau ketik kode barcode" autofocus>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Barang <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kategori <span class="text-danger">*</span></label>
                            <select class="form-select" name="category" id="category" required onchange="toggleSN()">
                                <option value="">Pilih Kategori</option>
                                <option value="Kabel Drop Core">Kabel Drop Core</option>
                                <option value="Precon">Precon</option>
                                <option value="Kabel Fiber Optik">Kabel Fiber Optik</option>
                                <option value="Kabel Feeder">Kabel Feeder</option>
                                <option value="Patchcord">Patchcord</option>
                                <option value="Modem ONT">Modem ONT</option>
                                <option value="Router">Router</option>
                                <option value="Konektor">Konektor (Fast Connector)</option>
                                <option value="ODP">ODP (Optical Distribution Point)</option>
                                <option value="ODC">ODC (Optical Distribution Cabinet)</option>
                                <option value="Closure">Closure</option>
                                <option value="Tiang">Tiang</option>
                                <option value="Aksesoris">Aksesoris (Klem, Hook, dll)</option>
                                <option value="Tools">Tools (Splicer, OTDR, dll)</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Satuan <span class="text-danger">*</span></label>
                            <select class="form-select" name="unit" required>
                                <option value="">Pilih Satuan</option>
                                <option value="Pcs">Pcs</option>
                                <option value="Unit">Unit</option>
                                <option value="Box">Box</option>
                                <option value="Meter">Meter</option>
                                <option value="Roll">Roll</option>
                                <option value="Batang">Batang</option>
                                <option value="Set">Set</option>
                                <option value="Pack">Pack</option>
                            </select>
                        </div>
                    </div>

                    <!-- Input Serial Number (Hanya muncul jika Modem/Router dipilih) -->
                    <div class="mb-3" id="sn-container" style="display: none;">
                        <label class="form-label">Serial Number (SN)</label>
                        <input type="text" class="form-control" name="serial_number" placeholder="Masukkan SN perangkat">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Gudang / Penyimpanan</label>
                            <input type="text" class="form-control" name="warehouse_name" placeholder="Contoh: Gudang Pusat / Mobil Teknisi A">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Lokasi Detail (Rak/Lemari)</label>
                            <input type="text" class="form-control" name="location" placeholder="Contoh: Rak A-01">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Link Google Maps (Opsional)</label>
                        <input type="url" class="form-control" name="map_link" placeholder="https://maps.google.com/...">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Jumlah Stok <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="quantity" min="0" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-secondary">Kembali</a>
                        <button type="submit" class="btn btn-primary">Simpan Barang</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSN() {
    var category = document.getElementById("category").value;
    var snContainer = document.getElementById("sn-container");
    if (category === "Modem ONT" || category === "Router") {
        snContainer.style.display = "block";
    } else {
        snContainer.style.display = "none";
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
