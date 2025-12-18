<?php
require_once 'config.php';
require_once 'includes/auth.php';
checkLogin();

// Fitur Pencarian dan Filter
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';

$where_clause = "WHERE 1=1";
if (!empty($search)) {
    $where_clause .= " AND (name LIKE '%$search%' OR description LIKE '%$search%')";
}
if (!empty($category_filter)) {
    $where_clause .= " AND category = '$category_filter'";
}

$sql = "SELECT * FROM items $where_clause ORDER BY name ASC";
$result = $conn->query($sql);

// Statistik Ringkas
$sql_total = "SELECT COUNT(*) as total FROM items";
$total_items = $conn->query($sql_total)->fetch_assoc()['total'];

$sql_low = "SELECT COUNT(*) as total FROM items WHERE quantity <= 5";
$low_stock = $conn->query($sql_low)->fetch_assoc()['total'];

$sql_asset = "SELECT SUM(quantity) as total FROM items";
$total_asset_res = $conn->query($sql_asset)->fetch_assoc();
$total_asset = $total_asset_res ? $total_asset_res['total'] : 0;

// Data untuk Grafik
$chart_cat_sql = "SELECT category, COUNT(*) as count FROM items GROUP BY category";
$chart_cat_res = $conn->query($chart_cat_sql);
$cat_labels = [];
$cat_data = [];
while($row = $chart_cat_res->fetch_assoc()) {
    $cat_labels[] = $row['category'];
    $cat_data[] = $row['count'];
}

$chart_wh_sql = "SELECT warehouse_name, SUM(quantity) as count FROM items GROUP BY warehouse_name";
$chart_wh_res = $conn->query($chart_wh_sql);
$wh_labels = [];
$wh_data = [];
while($row = $chart_wh_res->fetch_assoc()) {
    $wh_labels[] = $row['warehouse_name'] ? $row['warehouse_name'] : 'Belum ditentukan';
    $wh_data[] = $row['count'];
}

// Transaksi Terakhir (5 data)
$sql_recent = "SELECT t.*, i.name as item_name, u.username 
               FROM transactions t 
               LEFT JOIN items i ON t.item_id = i.id 
               LEFT JOIN users u ON t.user_id = u.id 
               ORDER BY t.date DESC LIMIT 5";
$recent_transactions = $conn->query($sql_recent);

require_once 'includes/header.php';
?>

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'transaction_success'): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <strong>Berhasil!</strong> Transaksi stok berhasil disimpan.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<!-- Statistik Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-white bg-primary mb-3 shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-boxes"></i> Total Jenis Barang</h5>
                <p class="card-text fs-4 fw-bold"><?php echo $total_items; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success mb-3 shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-layer-group"></i> Total Stok Fisik</h5>
                <p class="card-text fs-4 fw-bold"><?php echo $total_asset; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-danger mb-3 shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-exclamation-triangle"></i> Stok Menipis (<= 5)</h5>
                <p class="card-text fs-4 fw-bold"><?php echo $low_stock; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Grafik Visualisasi -->
<div class="row mb-4">
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Distribusi Kategori Barang</h6>
            </div>
            <div class="card-body">
                <div class="chart-pie pt-4 pb-2" style="height: 300px;">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Stok per Gudang</h6>
            </div>
            <div class="card-body">
                <div class="chart-bar" style="height: 300px;">
                    <canvas id="warehouseChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Transaksi Terakhir -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Riwayat Transaksi Terakhir</h6>
                <a href="history.php" class="btn btn-sm btn-primary shadow-sm">Lihat Semua</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" width="100%" cellspacing="0">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Barang</th>
                                <th>Tipe</th>
                                <th>Jumlah</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_transactions && $recent_transactions->num_rows > 0): ?>
                                <?php while($rt = $recent_transactions->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($rt['date'])); ?></td>
                                        <td><?php echo htmlspecialchars($rt['item_name']); ?></td>
                                        <td>
                                            <?php if ($rt['type'] == 'in'): ?>
                                                <span class="badge bg-success">Masuk</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Keluar</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $rt['quantity']; ?></td>
                                        <td><?php echo htmlspecialchars($rt['username']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">Belum ada transaksi.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Daftar Stok Barang</h2>
    <div>
        <a href="export_stock.php" class="btn btn-primary me-2"><i class="fas fa-file-export"></i> Laporan Stok</a>
        <a href="add_item.php" class="btn btn-success"><i class="fas fa-plus"></i> Tambah Barang Baru</a>
    </div>
</div>

<!-- Scan Barcode Cepat -->
<div class="card card-custom mb-4 bg-light border-primary">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-3">
                <h5 class="mb-0 text-primary"><i class="fas fa-barcode"></i> Scan Cepat</h5>
            </div>
            <div class="col-md-9">
                <div class="input-group">
                    <span class="input-group-text bg-white">Mode:</span>
                    <div class="input-group-text bg-white">
                        <div class="form-check form-check-inline mb-0">
                            <input class="form-check-input" type="radio" name="scanMode" id="scanOut" value="out" checked>
                            <label class="form-check-label text-danger fw-bold" for="scanOut">Keluar</label>
                        </div>
                        <div class="form-check form-check-inline mb-0">
                            <input class="form-check-input" type="radio" name="scanMode" id="scanIn" value="in">
                            <label class="form-check-label text-success fw-bold" for="scanIn">Masuk</label>
                        </div>
                    </div>
                    <input type="text" id="scanInput" class="form-control form-control-lg" placeholder="Scan Barcode di sini..." autofocus>
                    <button class="btn btn-primary" type="button" id="btnScanManual">Cari</button>
                </div>
                <small class="text-muted">Arahkan kursor ke kolom ini dan scan barcode barang untuk transaksi cepat.</small>
            </div>
        </div>
    </div>
</div>

<!-- Filter dan Pencarian -->
<div class="card card-custom mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <select name="category" class="form-select">
                    <option value="">Semua Kategori</option>
                    <option value="Kabel Drop Core" <?php if($category_filter == 'Kabel Drop Core') echo 'selected'; ?>>Kabel Drop Core</option>
                    <option value="Precon" <?php if($category_filter == 'Precon') echo 'selected'; ?>>Precon</option>
                    <option value="Kabel Fiber Optik" <?php if($category_filter == 'Kabel Fiber Optik') echo 'selected'; ?>>Kabel Fiber Optik</option>
                    <option value="Kabel Feeder" <?php if($category_filter == 'Kabel Feeder') echo 'selected'; ?>>Kabel Feeder</option>
                    <option value="Patchcord" <?php if($category_filter == 'Patchcord') echo 'selected'; ?>>Patchcord</option>
                    <option value="Modem ONT" <?php if($category_filter == 'Modem ONT') echo 'selected'; ?>>Modem ONT</option>
                    <option value="Router" <?php if($category_filter == 'Router') echo 'selected'; ?>>Router</option>
                    <option value="Konektor" <?php if($category_filter == 'Konektor') echo 'selected'; ?>>Konektor</option>
                    <option value="ODP" <?php if($category_filter == 'ODP') echo 'selected'; ?>>ODP</option>
                    <option value="ODC" <?php if($category_filter == 'ODC') echo 'selected'; ?>>ODC</option>
                    <option value="Closure" <?php if($category_filter == 'Closure') echo 'selected'; ?>>Closure</option>
                    <option value="Tiang" <?php if($category_filter == 'Tiang') echo 'selected'; ?>>Tiang</option>
                    <option value="Aksesoris" <?php if($category_filter == 'Aksesoris') echo 'selected'; ?>>Aksesoris</option>
                    <option value="Tools" <?php if($category_filter == 'Tools') echo 'selected'; ?>>Tools</option>
                    <option value="Lainnya" <?php if($category_filter == 'Lainnya') echo 'selected'; ?>>Lainnya</option>
                </select>
            </div>
            <div class="col-md-6">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Cari nama barang..." value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-outline-primary" type="submit"><i class="fas fa-search"></i> Cari</button>
                </div>
            </div>
            <div class="col-md-2">
                <a href="index.php" class="btn btn-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Tabel Barang -->
<div class="card card-custom">
    <div class="card-body">
        <?php if (isset($_GET['msg'])): ?>
            <?php if ($_GET['msg'] == 'deleted'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Barang berhasil dihapus.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php elseif ($_GET['msg'] == 'transaction_success'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Transaksi berhasil disimpan! Stok telah diperbarui.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Foto</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th>SN</th>
                        <th>Lokasi</th>
                        <th class="text-center">Stok</th>
                        <th>Satuan</th>
                        <th>Aksi Cepat</th>
                        <th>Opsi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <?php
                                // Safe checks for optional columns
                                $serial_number = isset($row['serial_number']) ? $row['serial_number'] : '';
                                $warehouse_name = isset($row['warehouse_name']) ? $row['warehouse_name'] : '';
                                $location = isset($row['location']) ? $row['location'] : '';
                                $map_link = isset($row['map_link']) ? $row['map_link'] : '';
                                $image_path = isset($row['image_path']) ? $row['image_path'] : '';
                            ?>
                            <tr>
                                <td>
                                    <?php if(!empty($image_path)): ?>
                                        <img src="<?php echo htmlspecialchars($image_path); ?>" alt="Img" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;" data-bs-toggle="modal" data-bs-target="#modalImage<?php echo $row['id']; ?>">
                                        
                                        <!-- Modal Preview Image -->
                                        <div class="modal fade" id="modalImage<?php echo $row['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-body text-center">
                                                        <img src="<?php echo htmlspecialchars($image_path); ?>" class="img-fluid" alt="Full Image">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted"><i class="fas fa-image fa-2x"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($row['description']); ?></small>
                                </td>
                                <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($row['category']); ?></span></td>
                                <td>
                                    <?php if(!empty($serial_number)): ?>
                                        <small class="font-monospace text-break"><?php echo htmlspecialchars($serial_number); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if(!empty($warehouse_name)): ?>
                                        <div><i class="fas fa-warehouse text-secondary"></i> <?php echo htmlspecialchars($warehouse_name); ?></div>
                                    <?php endif; ?>
                                    <?php if(!empty($location)): ?>
                                        <small class="text-muted"><i class="fas fa-map-pin"></i> <?php echo htmlspecialchars($location); ?></small>
                                    <?php endif; ?>
                                    <?php if(!empty($map_link)): ?>
                                        <a href="<?php echo htmlspecialchars($map_link); ?>" target="_blank" class="text-danger ms-1" title="Lihat di Peta"><i class="fas fa-map-marker-alt"></i></a>
                                    <?php endif; ?>
                                    <?php if(empty($warehouse_name) && empty($location)): ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php 
                                        $badgeClass = 'bg-success';
                                        if ($row['quantity'] <= 5) $badgeClass = 'bg-danger';
                                        elseif ($row['quantity'] <= 10) $badgeClass = 'bg-warning text-dark';
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?> fs-6"><?php echo $row['quantity']; ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($row['unit']); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalTransaction" 
                                            data-id="<?php echo $row['id']; ?>" 
                                            data-name="<?php echo htmlspecialchars($row['name']); ?>" 
                                            data-type="in">
                                        <i class="fas fa-plus"></i> Masuk
                                    </button>
                                    <button type="button" class="btn btn-sm btn-warning text-dark" data-bs-toggle="modal" data-bs-target="#modalTransaction" 
                                            data-id="<?php echo $row['id']; ?>" 
                                            data-name="<?php echo htmlspecialchars($row['name']); ?>" 
                                            data-type="out">
                                        <i class="fas fa-minus"></i> Keluar
                                    </button>
                                    <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#modalTransfer"
                                            data-id="<?php echo $row['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($row['name']); ?>"
                                            data-wh="<?php echo htmlspecialchars($warehouse_name); ?>"
                                            data-max="<?php echo $row['quantity']; ?>">
                                        <i class="fas fa-exchange-alt"></i> Transfer
                                    </button>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="edit_item.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="delete_item.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin ingin menghapus barang ini?')" title="Hapus"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="fas fa-box-open fa-3x text-muted mb-3"></i><br>
                                Tidak ada data barang ditemukan.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Transaksi -->
<div class="modal fade" id="modalTransaction" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="process_transaction.php" method="POST">
          <div class="modal-header">
            <h5 class="modal-title" id="modalTitle">Transaksi Barang</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="item_id" id="itemId">
            <input type="hidden" name="type" id="transactionType">
            
            <div class="mb-3">
                <label class="form-label">Nama Barang</label>
                <input type="text" class="form-control" id="itemName" readonly>
            </div>
            
            <div class="mb-3">
                <label class="form-label" id="qtyLabel">Jumlah</label>
                <input type="number" class="form-control" name="quantity" min="1" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Catatan (Opsional)</label>
                <textarea class="form-control" name="notes" placeholder="Contoh: Pembelian baru, Dipakai untuk instalasi di jalan X..."></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary" id="btnSave">Simpan</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Transfer -->
<div class="modal fade" id="modalTransfer" tabindex="-1" aria-labelledby="modalTransferLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="process_transfer.php" method="POST">
          <div class="modal-header bg-info text-white">
            <h5 class="modal-title" id="modalTransferLabel">Transfer Stok Antar Gudang</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="item_id" id="transferItemId">
            
            <div class="mb-3">
                <label class="form-label">Nama Barang</label>
                <input type="text" class="form-control" id="transferItemName" readonly>
            </div>

            <div class="mb-3">
                <label class="form-label">Gudang Asal</label>
                <input type="text" class="form-control" id="transferSourceWh" readonly>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Gudang Tujuan</label>
                <input class="form-control" list="warehouseOptions" name="target_warehouse" placeholder="Pilih atau ketik gudang tujuan" required>
                <datalist id="warehouseOptions">
                    <?php foreach($wh_labels as $wh): ?>
                        <?php if($wh !== 'Belum ditentukan'): ?>
                            <option value="<?php echo htmlspecialchars($wh); ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>
                </datalist>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Jumlah Transfer</label>
                <input type="number" class="form-control" name="quantity" id="transferQty" min="1" required>
                <div class="form-text">Maksimal: <span id="transferMaxQty"></span></div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Catatan</label>
                <textarea class="form-control" name="notes" placeholder="Alasan transfer..."></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary">Proses Transfer</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script>
    // Inisialisasi Grafik
    document.addEventListener("DOMContentLoaded", function() {
        // Grafik Kategori
        const ctxCat = document.getElementById('categoryChart').getContext('2d');
        new Chart(ctxCat, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($cat_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($cat_data); ?>,
                    backgroundColor: [
                        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                        '#858796', '#5a5c69', '#f8f9fc', '#2e59d9', '#17a673'
                    ],
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        display: true
                    }
                }
            }
        });

        // Grafik Gudang
        const ctxWh = document.getElementById('warehouseChart').getContext('2d');
        new Chart(ctxWh, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($wh_labels); ?>,
                datasets: [{
                    label: "Total Stok",
                    backgroundColor: "#4e73df",
                    borderColor: "#4e73df",
                    data: <?php echo json_encode($wh_data); ?>,
                    borderWidth: 1
                }],
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    });

    var modalTransaction = document.getElementById('modalTransaction');
    var transactionModal; // Initialize lazily to avoid error before Bootstrap loads

    // Modal Transfer Logic
    var modalTransfer = document.getElementById('modalTransfer');
    modalTransfer.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var name = button.getAttribute('data-name');
        var wh = button.getAttribute('data-wh');
        var max = button.getAttribute('data-max');
        
        var inputId = modalTransfer.querySelector('#transferItemId');
        var inputName = modalTransfer.querySelector('#transferItemName');
        var inputSource = modalTransfer.querySelector('#transferSourceWh');
        var inputQty = modalTransfer.querySelector('#transferQty');
        var spanMax = modalTransfer.querySelector('#transferMaxQty');
        
        inputId.value = id;
        inputName.value = name;
        inputSource.value = wh;
        inputQty.max = max;
        spanMax.textContent = max;
    });

    modalTransaction.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        if (!button) return; // Triggered manually via script

        var id = button.getAttribute('data-id');
        var name = button.getAttribute('data-name');
        var type = button.getAttribute('data-type');
        
        setupModal(id, name, type);
    });

    function setupModal(id, name, type) {
        var modalTitle = modalTransaction.querySelector('.modal-title');
        var inputId = modalTransaction.querySelector('#itemId');
        var inputName = modalTransaction.querySelector('#itemName');
        var inputType = modalTransaction.querySelector('#transactionType');
        var btnSave = modalTransaction.querySelector('#btnSave');
        var header = modalTransaction.querySelector('.modal-header');

        inputId.value = id;
        inputName.value = name;
        inputType.value = type;

        if (type === 'in') {
            modalTitle.textContent = 'Barang Masuk (Tambah Stok)';
            header.className = 'modal-header bg-success text-white';
            btnSave.className = 'btn btn-success';
            btnSave.textContent = 'Tambah Stok';
        } else {
            modalTitle.textContent = 'Barang Keluar (Kurangi Stok)';
            header.className = 'modal-header bg-warning';
            btnSave.className = 'btn btn-warning';
            btnSave.textContent = 'Kurangi Stok';
        }
    }

    // Barcode Scanner Logic
    const scanInput = document.getElementById('scanInput');
    const btnScanManual = document.getElementById('btnScanManual');

    function handleScan() {
        const barcode = scanInput.value.trim();
        if (!barcode) return;

        const mode = document.querySelector('input[name="scanMode"]:checked').value;

        // Initialize modal instance if needed (Bootstrap should be loaded by now)
        if (!transactionModal) {
            transactionModal = new bootstrap.Modal(modalTransaction);
        }

        fetch('get_item_by_barcode.php?barcode=' + encodeURIComponent(barcode))
            .then(response => response.json())
            .then(data => {
                if (data.status === 'found') {
                    // Open modal
                    setupModal(data.data.id, data.data.name, mode);
                    transactionModal.show();
                    scanInput.value = ''; // Clear input
                    
                    // Focus on quantity after modal opens
                    setTimeout(() => {
                        document.querySelector('input[name="quantity"]').focus();
                    }, 500);
                } else {
                    alert('Barang dengan barcode tersebut tidak ditemukan!');
                    scanInput.select();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat mencari data.');
            });
    }

    scanInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            handleScan();
        }
    });

    btnScanManual.addEventListener('click', handleScan);
</script>

<?php require_once 'includes/footer.php'; ?>