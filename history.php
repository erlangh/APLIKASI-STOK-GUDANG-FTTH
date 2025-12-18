<?php
require_once 'config.php';
require_once 'includes/auth.php';
checkLogin();

// Pagination
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filter
$type_filter = isset($_GET['type']) ? $conn->real_escape_string($_GET['type']) : 'out'; // Default tampilkan barang keluar saja
$where_clause = "WHERE 1=1";

// Jika user memilih 'all', tampilkan semua. Jika tidak ada pilihan atau pilih 'out', tampilkan 'out'
if ($type_filter != 'all') {
    $where_clause .= " AND t.type = '$type_filter'";
}

// Query Data
$sql = "SELECT t.*, i.name as item_name, i.unit, u.username 
        FROM transactions t 
        JOIN items i ON t.item_id = i.id 
        JOIN users u ON t.user_id = u.id 
        $where_clause 
        ORDER BY t.date DESC 
        LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Hitung total halaman
$sql_count = "SELECT COUNT(*) as total FROM transactions t $where_clause";
$total_rows = $conn->query($sql_count)->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Riwayat Transaksi</h2>
    <div class="btn-group">
        <button onclick="window.print()" class="btn btn-outline-secondary"><i class="fas fa-print"></i> Cetak</button>
    </div>
</div>

<div class="card card-custom mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3 align-items-center">
            <div class="col-auto">
                <label class="col-form-label">Filter Tipe:</label>
            </div>
            <div class="col-auto">
                <select name="type" class="form-select" onchange="this.form.submit()">
                    <option value="all" <?php if($type_filter == 'all') echo 'selected'; ?>>Semua Transaksi</option>
                    <option value="out" <?php if($type_filter == 'out') echo 'selected'; ?>>Barang Keluar (Usage)</option>
                    <option value="in" <?php if($type_filter == 'in') echo 'selected'; ?>>Barang Masuk (Purchase)</option>
                    <option value="transfer_in" <?php if($type_filter == 'transfer_in') echo 'selected'; ?>>Transfer Masuk</option>
                    <option value="transfer_out" <?php if($type_filter == 'transfer_out') echo 'selected'; ?>>Transfer Keluar</option>
                </select>
            </div>
        </form>
    </div>
</div>

<div class="card card-custom">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Tanggal</th>
                        <th>User</th>
                        <th>Barang</th>
                        <th>Tipe</th>
                        <th>Jumlah</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('d-m-Y H:i', strtotime($row['date'])); ?></td>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                                <td>
                                    <?php if ($row['type'] == 'in'): ?>
                                        <span class="badge bg-success">Masuk</span>
                                    <?php elseif ($row['type'] == 'out'): ?>
                                        <span class="badge bg-warning text-dark">Keluar</span>
                                    <?php elseif ($row['type'] == 'transfer_in'): ?>
                                        <span class="badge bg-info text-dark">Transfer Masuk</span>
                                    <?php elseif ($row['type'] == 'transfer_out'): ?>
                                        <span class="badge bg-secondary">Transfer Keluar</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($row['type']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo $row['quantity']; ?></strong> <?php echo $row['unit']; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['notes']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">Belum ada riwayat transaksi.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php if($page == $i) echo 'active'; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&type=<?php echo $type_filter; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
