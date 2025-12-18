<?php
require_once 'config.php';
require_once 'includes/auth.php';
checkLogin();

// Fetch items
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$where = "WHERE quantity > 0"; // Only show items with stock
if($search) {
    $where .= " AND (name LIKE '%$search%' OR warehouse_name LIKE '%$search%')";
}
$sql = "SELECT * FROM items $where ORDER BY name ASC";
$result = $conn->query($sql);

// Get Warehouses for filter/datalist
$wh_sql = "SELECT DISTINCT warehouse_name FROM items WHERE warehouse_name IS NOT NULL AND warehouse_name != ''";
$wh_res = $conn->query($wh_sql);
$wh_labels = [];
while($row = $wh_res->fetch_assoc()) $wh_labels[] = $row['warehouse_name'];

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Transfer Stok Antar Gudang</h2>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-10">
                <input type="text" name="search" class="form-control" placeholder="Cari nama barang atau gudang..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Cari</button>
            </div>
        </form>
    </div>
</div>

<?php if(isset($_GET['msg']) && $_GET['msg'] == 'transfer_success'): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    Transfer stok berhasil diproses!
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead class="table-dark">
            <tr>
                <th>Foto</th>
                <th>Nama Barang</th>
                <th>Gudang Asal</th>
                <th>Stok Tersedia</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td>
                        <?php if(!empty($row['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($row['image_path']); ?>" width="50" height="50" class="img-thumbnail" style="object-fit: cover;">
                        <?php else: ?>
                            <span class="text-muted"><i class="fas fa-image fa-2x"></i></span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['warehouse_name']); ?></td>
                    <td><?php echo $row['quantity']; ?> <?php echo htmlspecialchars($row['unit']); ?></td>
                    <td>
                        <button class="btn btn-info text-white btn-sm" data-bs-toggle="modal" data-bs-target="#modalTransfer"
                            data-id="<?php echo $row['id']; ?>"
                            data-name="<?php echo htmlspecialchars($row['name']); ?>"
                            data-wh="<?php echo htmlspecialchars($row['warehouse_name']); ?>"
                            data-max="<?php echo $row['quantity']; ?>">
                            <i class="fas fa-exchange-alt"></i> Transfer
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" class="text-center">Tidak ada data stok tersedia.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
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
            <input type="hidden" name="redirect" value="transfer_stock.php">
            
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
</script>

<?php require_once 'includes/footer.php'; ?>
