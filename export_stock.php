<?php
require_once 'includes/auth.php';
require_once 'config.php';

checkLogin();

// Ambil data profil perusahaan
$company = $conn->query("SELECT * FROM company_profile LIMIT 1")->fetch_assoc();
$comp_name = $company['company_name'] ?? 'PT. KONEKTIVITAS DIGITAL KALIMANTAN';
$comp_address = $company['address'] ?? 'Alamat Perusahaan Belum Diatur';
$comp_logo = $company['logo_path'] ?? '';

if (isset($_GET['type']) && $_GET['type'] == 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=laporan_stok_" . date('Y-m-d') . ".xls");
    
    // Output HTML table for Excel
    echo '<table border="1">';
    echo '<tr><th colspan="8" style="font-size: 14pt; font-weight: bold; text-align: center;">LAPORAN STOK GUDANG FTTH</th></tr>';
    echo '<tr><th colspan="8" style="font-size: 12pt; text-align: center;">' . htmlspecialchars($comp_name) . '</th></tr>';
    echo '<tr><th colspan="8" style="font-size: 10pt; text-align: center;">' . htmlspecialchars($comp_address) . '</th></tr>';
    echo '<tr><th colspan="8" style="text-align: center;">Tanggal: ' . date('d-m-Y H:i') . '</th></tr>';
    echo '<tr></tr>';
    
    echo '<tr><th>No</th><th>Nama Barang</th><th>Kategori</th><th>Stok</th><th>Satuan</th><th>Gudang</th><th>Lokasi</th><th>Serial Number</th></tr>';
    
    $result = $conn->query("SELECT * FROM items ORDER BY name ASC");
    $no = 1;
    while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . $no++ . '</td>';
        echo '<td>' . htmlspecialchars($row['name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['category']) . '</td>';
        echo '<td>' . $row['quantity'] . '</td>';
        echo '<td>' . htmlspecialchars($row['unit']) . '</td>';
        echo '<td>' . htmlspecialchars($row['warehouse_name'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars($row['location'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars($row['serial_number'] ?? '-') . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    exit();
}

// If not excel, show print view for PDF
$result = $conn->query("SELECT * FROM items ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Laporan Stok</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid black; padding: 8px; text-align: left; font-size: 12px; }
        th { background-color: #f0f0f0; }
        .header { text-align: center; margin-bottom: 30px; position: relative; min-height: 100px; padding-bottom: 10px; border-bottom: 2px solid #000; }
        .header img { position: absolute; left: 0; top: 0; max-height: 80px; max-width: 80px; }
        .header h2 { margin: 0 0 10px 0; }
        .header p { margin: 5px 0; }
        .no-print { margin-bottom: 20px; padding: 10px; background: #eee; border: 1px solid #ddd; }
        button { padding: 10px 20px; cursor: pointer; font-size: 14px; margin-right: 10px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">Cetak / Simpan PDF</button>
        <button onclick="window.location.href='export_stock.php?type=excel'">Download Excel</button>
        <button onclick="window.location.href='index.php'">Kembali ke Dashboard</button>
    </div>
    
    <div class="header">
        <?php if (!empty($comp_logo) && file_exists($comp_logo)): ?>
            <img src="<?php echo htmlspecialchars($comp_logo); ?>" alt="Logo">
        <?php endif; ?>
        <h2>LAPORAN STOK GUDANG FTTH</h2>
        <p style="font-size: 18px; font-weight: bold;"><?php echo htmlspecialchars($comp_name); ?></p>
        <p><?php echo htmlspecialchars($comp_address); ?></p>
        <p>Tanggal Cetak: <?php echo date('d-m-Y H:i'); ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>Nama Barang</th>
                <th>Kategori</th>
                <th width="10%">Stok</th>
                <th width="10%">Satuan</th>
                <th>Gudang</th>
                <th>Lokasi</th>
                <th>Serial Number</th>
            </tr>
        </thead>
        <tbody>
            <?php $no=1; while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['category']); ?></td>
                <td><?php echo $row['quantity']; ?></td>
                <td><?php echo htmlspecialchars($row['unit']); ?></td>
                <td><?php echo htmlspecialchars($row['warehouse_name'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($row['location'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($row['serial_number'] ?? '-'); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
