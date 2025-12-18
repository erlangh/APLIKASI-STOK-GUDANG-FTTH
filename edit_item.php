<?php
require_once 'config.php';
require_once 'includes/auth.php';
checkLogin();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = (int)$_GET['id'];
$sql = "SELECT * FROM items WHERE id = $id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    header("Location: index.php");
    exit();
}

$row = $result->fetch_assoc();
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

    // Image Handling
    $image_update_sql = "";
    
    // Check if delete requested
    if (isset($_POST['delete_image']) && !empty($row['image_path'])) {
        if (file_exists($row['image_path'])) {
            unlink($row['image_path']);
        }
        $image_update_sql = ", image_path = NULL";
    }

    // Check if new file uploaded
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
                    // Delete old image if exists and not already deleted
                    if (!empty($row['image_path']) && file_exists($row['image_path']) && empty($image_update_sql)) {
                        unlink($row['image_path']);
                    }
                    $image_update_sql = ", image_path = '$target_file'";
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
            $sql_update = "UPDATE items SET 
                            name='$name', 
                            category='$category', 
                            barcode='$barcode',
                            serial_number='$serial_number',
                            warehouse_name='$warehouse_name',
                            location='$location',
                            map_link='$map_link',
                            quantity=$quantity, 
                            unit='$unit', 
                            description='$description' 
                            $image_update_sql
                            WHERE id=$id";
            
            if ($conn->query($sql_update)) {
                $success = "Barang berhasil diperbarui!";
                // Refresh data
                $result = $conn->query($sql);
                $row = $result->fetch_assoc();
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
            <div class="card-header bg-warning">
                <h5 class="mb-0">Edit Barang</h5>
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
                        <label class="form-label">Foto Barang</label>
                        <?php if (!empty($row['image_path'])): ?>
                            <div class="mb-2">
                                <img src="<?php echo htmlspecialchars($row['image_path']); ?>" alt="Current Image" class="img-thumbnail" style="max-height: 150px;">
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="checkbox" name="delete_image" id="deleteImage">
                                    <label class="form-check-label text-danger" for="deleteImage">Hapus Gambar Saat Ini</label>
                                </div>
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" name="item_image" accept="image/*">
                        <div class="form-text">Biarkan kosong jika tidak ingin mengubah gambar.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Barcode / SKU</label>
                        <input type="text" class="form-control" name="barcode" value="<?php echo htmlspecialchars(isset($row['barcode']) ? $row['barcode'] : ''); ?>" placeholder="Scan atau ketik kode barcode">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Barang</label>
                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($row['name']); ?>" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kategori</label>
                            <select class="form-select" name="category" id="category" required onchange="toggleSN()">
                                <option value="Kabel Drop Core" <?php if($row['category'] == 'Kabel Drop Core') echo 'selected'; ?>>Kabel Drop Core</option>
                                <option value="Precon" <?php if($row['category'] == 'Precon') echo 'selected'; ?>>Precon</option>
                                <option value="Kabel Fiber Optik" <?php if($row['category'] == 'Kabel Fiber Optik') echo 'selected'; ?>>Kabel Fiber Optik</option>
                                <option value="Kabel Feeder" <?php if($row['category'] == 'Kabel Feeder') echo 'selected'; ?>>Kabel Feeder</option>
                                <option value="Patchcord" <?php if($row['category'] == 'Patchcord') echo 'selected'; ?>>Patchcord</option>
                                <option value="Modem ONT" <?php if($row['category'] == 'Modem ONT') echo 'selected'; ?>>Modem ONT</option>
                                <option value="Router" <?php if($row['category'] == 'Router') echo 'selected'; ?>>Router</option>
                                <option value="Konektor" <?php if($row['category'] == 'Konektor') echo 'selected'; ?>>Konektor (Fast Connector)</option>
                                <option value="ODP" <?php if($row['category'] == 'ODP') echo 'selected'; ?>>ODP (Optical Distribution Point)</option>
                                <option value="ODC" <?php if($row['category'] == 'ODC') echo 'selected'; ?>>ODC (Optical Distribution Cabinet)</option>
                                <option value="Closure" <?php if($row['category'] == 'Closure') echo 'selected'; ?>>Closure</option>
                                <option value="Tiang" <?php if($row['category'] == 'Tiang') echo 'selected'; ?>>Tiang</option>
                                <option value="Aksesoris" <?php if($row['category'] == 'Aksesoris') echo 'selected'; ?>>Aksesoris (Klem, Hook, dll)</option>
                                <option value="Tools" <?php if($row['category'] == 'Tools') echo 'selected'; ?>>Tools (Splicer, OTDR, dll)</option>
                                <option value="Lainnya" <?php if($row['category'] == 'Lainnya') echo 'selected'; ?>>Lainnya</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Satuan</label>
                            <select class="form-select" name="unit" required>
                                <option value="Pcs" <?php if($row['unit'] == 'Pcs') echo 'selected'; ?>>Pcs</option>
                                <option value="Unit" <?php if($row['unit'] == 'Unit') echo 'selected'; ?>>Unit</option>
                                <option value="Box" <?php if($row['unit'] == 'Box') echo 'selected'; ?>>Box</option>
                                <option value="Meter" <?php if($row['unit'] == 'Meter') echo 'selected'; ?>>Meter</option>
                                <option value="Roll" <?php if($row['unit'] == 'Roll') echo 'selected'; ?>>Roll</option>
                                <option value="Batang" <?php if($row['unit'] == 'Batang') echo 'selected'; ?>>Batang</option>
                                <option value="Set" <?php if($row['unit'] == 'Set') echo 'selected'; ?>>Set</option>
                                <option value="Pack" <?php if($row['unit'] == 'Pack') echo 'selected'; ?>>Pack</option>
                            </select>
                        </div>
                    </div>

                    <!-- Input Serial Number -->
                    <div class="mb-3" id="sn-container" style="display: <?php echo ($row['category'] == 'Modem ONT' || $row['category'] == 'Router') ? 'block' : 'none'; ?>;">
                        <label class="form-label">Serial Number (SN)</label>
                        <input type="text" class="form-control" name="serial_number" value="<?php echo htmlspecialchars(isset($row['serial_number']) ? $row['serial_number'] : ''); ?>" placeholder="Masukkan SN perangkat">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Gudang / Penyimpanan</label>
                            <input type="text" class="form-control" name="warehouse_name" value="<?php echo htmlspecialchars(isset($row['warehouse_name']) ? $row['warehouse_name'] : ''); ?>" placeholder="Contoh: Gudang Pusat / Mobil Teknisi A">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Lokasi Detail (Rak/Lemari)</label>
                            <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars(isset($row['location']) ? $row['location'] : ''); ?>" placeholder="Contoh: Rak A-01">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Link Google Maps (Opsional)</label>
                        <input type="url" class="form-control" name="map_link" value="<?php echo htmlspecialchars(isset($row['map_link']) ? $row['map_link'] : ''); ?>" placeholder="https://maps.google.com/...">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Jumlah Stok</label>
                        <input type="number" class="form-control" name="quantity" min="0" value="<?php echo $row['quantity']; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($row['description']); ?></textarea>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-secondary">Kembali</a>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
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
