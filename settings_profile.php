<?php
require_once 'includes/auth.php';
require_once 'config.php';
require_once 'includes/header.php';

checkLogin();

$message = '';
$error = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $company_name = $conn->real_escape_string($_POST['company_name']);
    $address = $conn->real_escape_string($_POST['address']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $email = $conn->real_escape_string($_POST['email']);
    
    // Check if profile exists
    $check = $conn->query("SELECT id FROM company_profile LIMIT 1");
    if ($check->num_rows == 0) {
        $sql = "INSERT INTO company_profile (company_name, address, phone, email) VALUES ('$company_name', '$address', '$phone', '$email')";
        $conn->query($sql);
        $profile_id = $conn->insert_id;
    } else {
        $profile_id = $check->fetch_assoc()['id'];
        $sql = "UPDATE company_profile SET company_name='$company_name', address='$address', phone='$phone', email='$email' WHERE id=$profile_id";
        $conn->query($sql);
    }

    // Handle Logo Upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['logo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $upload_dir = 'assets/img/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $new_filename = 'logo_' . time() . '.' . $ext;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                // Update database
                $conn->query("UPDATE company_profile SET logo_path='$upload_path' WHERE id=$profile_id");
            } else {
                $error = "Gagal mengupload logo.";
            }
        } else {
            $error = "Format file tidak diizinkan. Gunakan JPG, PNG, atau GIF.";
        }
    }

    if (empty($error)) {
        $message = "Profil perusahaan berhasil disimpan!";
    }
}

// Get Current Profile
$profile = $conn->query("SELECT * FROM company_profile LIMIT 1")->fetch_assoc();
?>

<div class="container mt-4">
    <h2>Pengaturan Profil Perusahaan</h2>
    <p class="text-muted">Informasi ini akan ditampilkan pada Kop Surat dan Laporan.</p>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label">Nama Perusahaan</label>
                            <input type="text" class="form-control" name="company_name" value="<?php echo htmlspecialchars($profile['company_name'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat Lengkap</label>
                            <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nomor Telepon / HP</label>
                                <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Logo Perusahaan</label>
                            <input type="file" class="form-control mb-2" name="logo" accept="image/*">
                            <div class="border p-2 text-center bg-light" style="min-height: 150px; display: flex; align-items: center; justify-content: center;">
                                <?php if (!empty($profile['logo_path']) && file_exists($profile['logo_path'])): ?>
                                    <img src="<?php echo $profile['logo_path']; ?>" alt="Logo" style="max-width: 100%; max-height: 150px;">
                                <?php else: ?>
                                    <span class="text-muted">Belum ada logo</span>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted">Format: JPG, PNG. Maks 2MB.</small>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
