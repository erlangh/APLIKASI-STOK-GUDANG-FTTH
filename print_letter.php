<?php
require_once 'includes/auth.php';
require_once 'config.php';

checkLogin();

if (!isset($_GET['id'])) {
    die("ID Surat tidak valid.");
}

$id = (int)$_GET['id'];
$letter = $conn->query("SELECT * FROM letters WHERE id = $id")->fetch_assoc();

if (!$letter) {
    die("Surat tidak ditemukan.");
}

$items = $conn->query("
    SELECT li.*, i.name, i.unit 
    FROM letter_items li 
    JOIN items i ON li.item_id = i.id 
    WHERE li.letter_id = $id
");

$title = ($letter['type'] == 'out') ? "SURAT JALAN" : "SURAT MASUK";

// Ambil data profil perusahaan
$company = $conn->query("SELECT * FROM company_profile LIMIT 1")->fetch_assoc();
$comp_name = $company['company_name'] ?? 'PT. KONEKTIVITAS DIGITAL KALIMANTAN';
$comp_address = $company['address'] ?? 'Alamat Perusahaan Belum Diatur';
$comp_phone = $company['phone'] ?? '-';
$comp_email = $company['email'] ?? '-';
$comp_logo = $company['logo_path'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak <?php echo $title; ?> - <?php echo htmlspecialchars($letter['letter_number']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 20px; position: relative; min-height: 100px; }
        .header img { position: absolute; left: 0; top: 0; max-height: 80px; max-width: 80px; }
        .header-content { margin-left: 0; }
        .header h1 { margin: 0; font-size: 24px; text-transform: uppercase; }
        .header p { margin: 5px 0 0; font-size: 14px; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 5px; vertical-align: top; }
        .content-table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        .content-table th, .content-table td { border: 1px solid #000; padding: 8px; text-align: left; }
        .content-table th { background-color: #f0f0f0; }
        .signatures { display: flex; justify-content: space-between; margin-top: 50px; text-align: center; }
        .signature-box { width: 200px; }
        .signature-box .name { margin-top: 60px; font-weight: bold; text-decoration: underline; }
        
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()">Cetak</button>
        <button onclick="window.close()">Tutup</button>
    </div>

    <div class="header">
        <?php if (!empty($comp_logo) && file_exists($comp_logo)): ?>
            <img src="<?php echo htmlspecialchars($comp_logo); ?>" alt="Logo">
        <?php endif; ?>
        <div class="header-content">
            <h1><?php echo htmlspecialchars($comp_name); ?></h1>
            <p><?php echo htmlspecialchars($comp_address); ?></p>
            <p>Telp: <?php echo htmlspecialchars($comp_phone); ?> | Email: <?php echo htmlspecialchars($comp_email); ?></p>
        </div>
    </div>

    <h2 style="text-align: center; text-decoration: underline;"><?php echo $title; ?></h2>
    
    <table class="info-table">
        <tr>
            <td width="150"><strong>No. Surat</strong></td>
            <td>: <?php echo htmlspecialchars($letter['letter_number']); ?></td>
            <td width="150"><strong>Tanggal</strong></td>
            <td>: <?php echo date('d-m-Y', strtotime($letter['date'])); ?></td>
        </tr>
        <tr>
            <td><strong><?php echo ($letter['type'] == 'out') ? 'Penerima' : 'Pengirim'; ?></strong></td>
            <td>: <?php echo htmlspecialchars($letter['recipient']); ?></td>
            <td><strong>Admin Gudang</strong></td>
            <td>: <?php echo htmlspecialchars($letter['admin_name']); ?></td>
        </tr>
        <tr>
            <td><strong>Keterangan</strong></td>
            <td colspan="3">: <?php echo nl2br(htmlspecialchars($letter['notes'])); ?></td>
        </tr>
    </table>

    <table class="content-table">
        <thead>
            <tr>
                <th width="50">No</th>
                <th>Nama Barang</th>
                <th width="100">Jumlah</th>
                <th width="100">Satuan</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; while ($row = $items->fetch_assoc()): ?>
            <tr>
                <td style="text-align: center;"><?php echo $no++; ?></td>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td style="text-align: center;"><?php echo $row['quantity']; ?></td>
                <td style="text-align: center;"><?php echo htmlspecialchars($row['unit']); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="signatures">
        <div class="signature-box">
            <p>Dibuat Oleh,</p>
            <div class="name"><?php echo htmlspecialchars($letter['admin_name']); ?></div>
            <div>(Admin Gudang)</div>
        </div>
        <div class="signature-box">
            <p><?php echo ($letter['type'] == 'out') ? 'Diterima Oleh,' : 'Diserahkan Oleh,'; ?></p>
            <div class="name"><?php echo htmlspecialchars($letter['recipient']); ?></div>
            <div>(<?php echo ($letter['type'] == 'out') ? 'Penerima' : 'Pengirim'; ?>)</div>
        </div>
        <div class="signature-box">
            <p>Mengetahui,</p>
            <div class="name">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
            <div>(Manager Operasional)</div>
        </div>
    </div>

</body>
</html>
