<?php
// session_start();

// // Check if user is logged in and has kitchen role
// if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
//     header('Location: login.php');
//     exit;
// }

include 'config.php';

/* ==============================
   HANDLE UPDATE STATUS PESANAN
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    $order_id       = (int) $_POST['order_id'];
    $new_status_id  = (int) $_POST['new_status_id'];
    $old_status_id  = isset($_POST['old_status_id']) ? (int) $_POST['old_status_id'] : null;

    // Update status order
    $stmt = $koneksi->prepare(
        "UPDATE orders 
         SET status_id = ?, updated_at = CURRENT_TIMESTAMP 
         WHERE id = ?"
    );
    $stmt->bind_param("ii", $new_status_id, $order_id);
    $stmt->execute();
    $stmt->close();

    // Kurangi stok SAAT status berubah ke DIMASAK (2)
    if ($new_status_id === 2 && $old_status_id !== 2) {

        $detail_stmt = $koneksi->prepare(
            "SELECT menu_id, jumlah 
             FROM order_details 
             WHERE order_id = ?"
        );
        $detail_stmt->bind_param("i", $order_id);
        $detail_stmt->execute();
        $detail_result = $detail_stmt->get_result();

        while ($item = $detail_result->fetch_assoc()) {
            $stock_stmt = $koneksi->prepare(
                "UPDATE menu 
                 SET stok = GREATEST(0, stok - ?) 
                 WHERE id = ?"
            );
            $stock_stmt->bind_param("ii", $item['jumlah'], $item['menu_id']);
            $stock_stmt->execute();
            $stock_stmt->close();
        }

        $detail_stmt->close();
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?filter=" . ($_GET['filter'] ?? 'all'));
    exit;
}

/* ==============================
   FILTER & PARAMETER
================================ */
$filter = $_GET['filter'] ?? 'all';

$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date   = $_GET['end_date']   ?? date('Y-m-d');
$start_time = $_GET['start_time'] ?? '00:00';
$end_time   = $_GET['end_time']   ?? '23:59';

/* ==============================
   QUERY PESANAN (DINAMIS)
================================ */
$query = "
    SELECT o.*, so.nama_status, m.nomor_meja
    FROM orders o
    LEFT JOIN status_order so ON o.status_id = so.id
    LEFT JOIN meja m ON o.meja_id = m.id
    WHERE 1=1
";

$params = [];
$types  = "";

/* Filter status */
if ($filter !== 'all') {
    $query  .= " AND o.status_id = ?";
    $params[] = (int) $filter;
    $types   .= "i";
}

/* Filter tanggal & waktu KHUSUS status SELESAI */
if ($filter === '4') {
    $query .= "
        AND DATE(o.tanggal_order) BETWEEN ? AND ?
        AND TIME(o.tanggal_order) BETWEEN ? AND ?
    ";
    $params[] = $start_date;
    $params[] = $end_date;
    $params[] = $start_time;
    $params[] = $end_time;
    $types   .= "ssss";
}

$query .= " ORDER BY o.tanggal_order DESC";

/* ==============================
   EXECUTE QUERY (AMAN)
================================ */
$stmt = $koneksi->prepare($query);

if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
$stmt->close();

/* ==============================
   HITUNG JUMLAH STATUS
================================ */
$status_counts = [
    'all' => 0,
    '1' => 0,
    '2' => 0,
    '3' => 0,
    '4' => 0
];

$count_result = $koneksi->query(
    "SELECT status_id, COUNT(*) AS total 
     FROM orders 
     GROUP BY status_id"
);

if ($count_result) {
    while ($row = $count_result->fetch_assoc()) {
        $status_counts[$row['status_id']] = $row['total'];
        $status_counts['all'] += $row['total'];
    }
}

/* ==============================
   FUNCTION DETAIL PESANAN
================================ */
function getOrderDetails($koneksi, $order_id) {
    $stmt = $koneksi->prepare(
        "SELECT od.*, m.nama_menu
         FROM order_details od
         JOIN menu m ON od.menu_id = m.id
         WHERE od.order_id = ?"
    );
    $stmt->bind_param("i", $order_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $details = [];

    while ($row = $result->fetch_assoc()) {
        $details[] = $row;
    }

    $stmt->close();
    return $details;
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kopi Janti - Dashboard Kitchen</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* ================= FILTER HISTORI ================= */

.filter-section {
    display: flex;
    gap: 20px;
    align-items: center;
    padding: 5px;
    border-radius: 20px;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.filter-label {
    font-weight: 600;
    color: #5d4037;
}

.date-input,
.time-input {
    padding: 10px 14px;
    border-radius: 20px;
    border: none;
    outline: none;
    font-size: 14px;
}

.filter-actions {
    margin-left: auto;   
    display: flex;
    align-items: center;
}

.btn-icon {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    border: none;
    background: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}


        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5e6d3 0%, #e8d4c0 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 100px;
            height: 100vh;
            background: #f5e6d3;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 30px 0;
            gap: 14px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 100;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            cursor: pointer;
            text-decoration: none;
            color: #5d4037;
            padding: 10px;
            border-radius: 15px;
            transition: all 0.3s;
        }

        .nav-item:hover, .nav-item.active {
            background: #d4b5a0;
        }

        .nav-item.end {
            margin-top: auto;
        }

        .nav-label {
            font-size: 12px;
            font-weight: 500;
        }

        .nav-icon img {
            width: 24px;
            height: 24px;
            filter: grayscale(100%) brightness(0) saturate(100%);
        }

        .main-content {
            margin-left: 120px;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .header-left h1 {
            font-size: 48px;
            color: #3e2723;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .search-bar {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .notification-btn {
            width: 45px;
            height: 45px;
            background: #8d9e92;
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            position: relative;
            transition: all 0.3s;
        }

        .notification-btn:hover {
            transform: scale(1.1);
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #e53935;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            border: 2px solid white;
        }

        .search-wrapper {
            position: relative;
        }

        .search-input {
            padding: 12px 40px 12px 20px;
            border: none;
            border-radius: 25px;
            background: white;
            width: 250px;
            font-size: 14px;
        }

        .search-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            filter: grayscale(100%) brightness(0) saturate(100%);
        }

        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 12px 24px;
            border: 2px solid #8d6e63;
            border-radius: 25px;
            background: white;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            color: #3e2723;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .filter-btn.active {
            background: #8d6e63;
            color: white;
        }

        .filter-btn .count {
            background: #5d4037;
            color: white;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 13px;
            min-width: 24px;
            text-align: center;
        }

        .filter-btn.active .count {
            background: white;
            color: #8d6e63;
        }

        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .order-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.3s;
            cursor: pointer;
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }

        .order-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f0f0f0;
        }

        .customer-name {
            font-size: 18px;
            font-weight: 700;
            color: #3e2723;
            margin-bottom: 5px;
        }

        .order-number {
            font-size: 15px;
            font-weight: 600;
            color: #8d6e63;
        }

        .order-type-badge {
            font-size: 12px;
            color: #666;
            background: #f5f5f5;
            padding: 4px 10px;
            border-radius: 12px;
            margin-top: 4px;
            display: inline-block;
        }

        .order-info {
            font-size: 13px;
            color: #666;
            margin-bottom: 3px;
        }

        .order-items-section {
            margin: 12px 0;
        }

        .order-title {
            font-weight: 600;
            font-size: 14px;
            color: #3e2723;
            margin-bottom: 8px;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 13px;
            color: #444;
        }

        .item-name {
            flex: 1;
        }

        .item-price {
            font-weight: 600;
            color: #5d4037;
            margin-left: 10px;
        }

        .item-notes {
            font-size: 12px;
            color: #999;
            font-style: italic;
            margin-top: 2px;
        }

        .see-more {
            text-align: center;
            color: #8d6e63;
            font-size: 12px;
            margin: 8px 0;
            font-weight: 600;
            cursor: pointer;
        }

        .status-btn-single {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 10px;
            cursor: default;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 12px;
        }

        .status-btn-single.baru {
            background: #e3f2fd;
            color: #1565c0;
        }

        .status-btn-single.dimasak {
            background: #fff3e0;
            color: #e65100;
        }

        .status-btn-single.siap {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-btn-single.selesai {
            background: #f3e5f5;
            color: #6a1b9a;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 0;
            max-width: 500px;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
            position: relative;
        }

        .modal-header {
            background: #8d6e63;
            color: white;
            padding: 25px;
            border-radius: 20px 20px 0 0;
            position: relative;
        }

        .close-modal {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 28px;
            cursor: pointer;
            border: none;
            background: rgba(255,255,255,0.2);
            color: white;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s;
        }

        .close-modal:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.1);
        }

        .modal-customer-name {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 8px;
            padding-right: 40px;
        }

        .modal-order-number {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .modal-body {
            padding: 25px;
        }

        .modal-info-section {
            background: #f8f8f8;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .modal-info {
            font-size: 13px;
            color: #666;
            margin-bottom: 5px;
        }

        .modal-info strong {
            color: #3e2723;
        }

        .modal-items {
            margin: 20px 0;
        }

        .total-section {
            border-top: 2px solid #e0e0e0;
            padding-top: 15px;
            margin-top: 15px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }

        .total-price {
            display: flex;
            justify-content: space-between;
            font-size: 20px;
            font-weight: 700;
            color: #3e2723;
            padding-top: 10px;
            border-top: 2px solid #3e2723;
        }

        .status-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .status-action-btn {
            flex: 1;
            min-width: 100px;
            padding: 12px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .status-action-btn.dimasak {
            background: #fff3e0;
            color: #e65100;
        }

        .status-action-btn.siap {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-action-btn.selesai {
            background: #f3e5f5;
            color: #6a1b9a;
        }

        .status-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .empty-state-text {
            font-size: 18px;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                bottom: 0;
                top: auto;
                flex-direction: row;
                padding: 15px;
                gap: 10px;
                justify-content: space-around;
            }

            .main-content {
                margin-left: 0;
                margin-bottom: 100px;
            }

            .orders-grid {
                grid-template-columns: 1fr;
            }

            .header-left h1 {
                font-size: 32px;
            }

            .header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="nav-item logo">             
            <img src="assets/logo.png" alt="Kopi Janti" style="width: 70px; height: 70px;">
        </div>
        <a href="dashboard_kitchen.php" class="nav-item active">
            <div class="nav-icon"><img src="assets/Home.png" alt="Home"></div>
            <div class="nav-label">Home</div>
        </a>
        <a href="menu.php" class="nav-item">
            <div class="nav-icon"><img src="assets/menu.png" alt="Menu"></div>
            <div class="nav-label">Menu</div>
        </a>
        <a href="logout.php" class="nav-item end">
            <div class="nav-icon"><img src="assets/logout.png" alt="Logout"></div>
            <div class="nav-label">Logout</div>
        </a>
    </div>

    <div class="main-content">
        <div class="header">
            <div class="header-left">
                <h1>Order List</h1>
               
            </div>
            <div class="search-bar">
                <button class="notification-btn">
                    <img src="assets/Bell.png" alt="Notifications" style="width: 20px; height: 20px;">
                    <?php if ($status_counts['1'] > 0): ?>
                        <span class="notification-badge"><?= $status_counts['1'] ?></span>
                    <?php endif; ?>
                </button>
                <div class="search-wrapper">
                    <input type="text" class="search-input" placeholder="Cari pesanan...">
                    <img src="assets/Search.png" alt="Search" class="search-icon">
                </div>
            </div>
        </div>

        <div class="filters">
            <a href="?filter=all" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">
                Semua <span class="count"><?= $status_counts['all'] ?></span>
            </a>
            <a href="?filter=1" class="filter-btn <?= $filter === '1' ? 'active' : '' ?>">
                Baru <span class="count"><?= $status_counts['1'] ?></span>
            </a>
            <a href="?filter=2" class="filter-btn <?= $filter === '2' ? 'active' : '' ?>">
                Dimasak <span class="count"><?= $status_counts['2'] ?></span>
            </a>
            <a href="?filter=3" class="filter-btn <?= $filter === '3' ? 'active' : '' ?>">
                Siap Dihidangkan <span class="count"><?= $status_counts['3'] ?></span>
            </a>
            <a href="?filter=4" class="filter-btn <?= $filter === '4' ? 'active' : '' ?>">
                Selesai <span class="count"><?= $status_counts['4'] ?></span>
            </a>
        </div>

<?php if ($filter === '4'): ?>
<form method="GET" class="filter-section">
    <input type="hidden" name="filter" value="4">

    <div class="filter-group">
        <span class="filter-label">Tanggal:</span>
        <input type="date" name="start_date" value="<?= $start_date ?>" class="date-input">
        <span>-</span>
        <input type="date" name="end_date" value="<?= $end_date ?>" class="date-input">
    </div>

    <div class="filter-group">
        <span class="filter-label">Waktu:</span>
        <input type="time" name="start_time" value="<?= $start_time ?>" class="time-input">
        <span>-</span>
        <input type="time" name="end_time" value="<?= $end_time ?>" class="time-input">
    </div>

    <div class="filter-actions">
        <button type="submit" class="btn-icon">
            <img src="assets/filter.png" width="20">
        </button>
    </div>
</form>
<?php endif; ?>

        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="empty-state-text">Tidak ada pesanan untuk ditampilkan</div>
            </div>
        <?php else: ?>
            <div class="orders-grid">
                <?php foreach ($orders as $order): ?>
                    <?php 
                        $details = getOrderDetails($koneksi, $order['id']);
                        $item_count = count($details);
                    ?>
                    <div class="order-card" onclick="showModal(<?= $order['id'] ?>)">
                        <div class="order-card-header">
                            <div>
                                <div class="customer-name"><?= htmlspecialchars($order['nama_pelanggan'] ?: 'Guest') ?></div>
                                <div class="order-info"><?= date('d/m/Y, H:i', strtotime($order['tanggal_order'])) ?></div>
                                <?php if ($order['tipe_order'] === 'Dine In' && $order['nomor_meja']): ?>
                                    <div class="order-info">Meja: <?= htmlspecialchars($order['nomor_meja']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div style="text-align: right;">
                                <div class="order-number">#<?= htmlspecialchars($order['nomor_struk']) ?></div>
                                <div class="order-type-badge"><?= $order['tipe_order'] ?></div>
                            </div>
                        </div>

                        <div class="order-items-section">
                            <div class="order-title">Pesanan (<?= $item_count ?>)</div>
                            <?php foreach (array_slice($details, 0, 3) as $item): ?>
                                <div class="order-item">
                                    <div class="item-name"><?= $item['jumlah'] ?>x <?= htmlspecialchars($item['nama_menu']) ?></div>
                                    <div class="item-price">Rp <?= number_format($item['harga_satuan'], 0, ',', '.') ?></div>
                                </div>
                                <?php if (!empty($item['catatan'])): ?>
                                    <div class="item-notes">Catatan: <?= htmlspecialchars($item['catatan']) ?></div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php if ($item_count > 3): ?>
                                <div class="see-more">Lihat Detail</div>
                            <?php endif; ?>
                        </div>

                        <?php
                            $status_class = '';
                            $status_text = '';

                            if ($order['status_id'] == 1) {
                                $status_class = 'baru';
                                $status_text = 'Pesanan Baru';
                            } elseif ($order['status_id'] == 2) {
                                $status_class = 'dimasak';
                                $status_text = 'Sedang Dimasak';
                            } elseif ($order['status_id'] == 3) {
                                $status_class = 'siap';
                                $status_text = 'Siap Dihidangkan';
                            } else {
                                $status_class = 'selesai';
                                $status_text = 'Selesai';
                            }
                        ?>

                        <div class="status-btn-single <?= $status_class ?>">
                            <?= $status_text ?>
                        </div>
                    </div>

                    <!-- Modal for this order -->
                    <div class="modal" id="modal-<?= $order['id'] ?>" onclick="event.target === this && closeModal(<?= $order['id'] ?>)">
                        <div class="modal-content" onclick="event.stopPropagation()">
                            <div class="modal-header">
                                <button class="close-modal" onclick="closeModal(<?= $order['id'] ?>)">×</button>
                                <div class="modal-customer-name"><?= htmlspecialchars($order['nama_pelanggan'] ?: 'Guest') ?></div>
                                <div class="modal-order-number">Order #<?= htmlspecialchars($order['nomor_struk']) ?></div>
                            </div>
                            
                            <div class="modal-body">
                                <div class="modal-info-section">
                                    <div class="modal-info"><strong>Tanggal:</strong> <?= date('d/m/Y, H:i', strtotime($order['tanggal_order'])) ?></div>
                                    <div class="modal-info"><strong>Tipe:</strong> <?= $order['tipe_order'] ?></div>
                                    <?php if ($order['tipe_order'] === 'Dine In' && $order['nomor_meja']): ?>
                                        <div class="modal-info"><strong>Meja:</strong> <?= htmlspecialchars($order['nomor_meja']) ?></div>
                                    <?php endif; ?>
                                    <div class="modal-info"><strong>Status:</strong> <?= htmlspecialchars($order['nama_status']) ?></div>
                                </div>

                                <div class="modal-items">
                                    <div class="order-title">Detail Pesanan</div>
                                    <?php foreach ($details as $item): ?>
                                        <div class="order-item">
                                            <div class="item-name"><?= $item['jumlah'] ?>x <?= htmlspecialchars($item['nama_menu']) ?></div>
                                            <div class="item-price">Rp <?= number_format($item['harga_satuan'], 0, ',', '.') ?></div>
                                        </div>
                                        <?php if (!empty($item['catatan'])): ?>
                                            <div class="item-notes">Catatan: <?= htmlspecialchars($item['catatan']) ?></div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>

                                <div class="total-section">
                                    <div class="total-row">
                                        <span>Subtotal</span>
                                        <span>Rp <?= number_format($order['subtotal'], 0, ',', '.') ?></span>
                                    </div>
                                    <?php if ($order['diskon'] > 0): ?>
                                        <div class="total-row">
                                            <span>Diskon</span>
                                            <span>- Rp <?= number_format($order['diskon'], 0, ',', '.') ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($order['pajak'] > 0): ?>
                                        <div class="total-row">
                                            <span>Pajak</span>
                                            <span>Rp <?= number_format($order['pajak'], 0, ',', '.') ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="total-price">
                                        <span>Total</span>
                                        <span>Rp <?= number_format($order['total'], 0, ',', '.') ?></span>
                                    </div>
                                </div>

                                <?php if ($order['status_id'] < 4): ?>
                                    <form method="POST" class="status-actions">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <input type="hidden" name="old_status_id" value="<?= $order['status_id'] ?>">
                                        
                                        <?php if ($order['status_id'] == 1): ?>
                                            <button type="submit" name="new_status_id" value="2" class="status-action-btn dimasak">
                                                Mulai Masak
                                            </button>
                                            <button type="submit" name="new_status_id" value="3" class="status-action-btn siap">
                                                Siap Hidang
                                            </button>
                                            <button type="submit" name="new_status_id" value="4" class="status-action-btn selesai">
                                                Selesai
                                            </button>
                                        <?php elseif ($order['status_id'] == 2): ?>
                                            <button type="submit" name="new_status_id" value="3" class="status-action-btn siap">
                                                Siap Hidang
                                            </button>
                                            <button type="submit" name="new_status_id" value="4" class="status-action-btn selesai">
                                                Selesai
                                            </button>
                                        <?php elseif ($order['status_id'] == 3): ?>
                                            <button type="submit" name="new_status_id" value="4" class="status-action-btn selesai">
                                                Selesai
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function showModal(orderId) {
            document.getElementById('modal-' + orderId).classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(orderId) {
            document.getElementById('modal-' + orderId).classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Search functionality
        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase();
                document.querySelectorAll('.order-card').forEach(card => {
                    const text = card.textContent.toLowerCase();
                    card.style.display = text.includes(searchTerm) ? 'block' : 'none';
                });
            });
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.active').forEach(modal => {
                    modal.classList.remove('active');
                    document.body.style.overflow = 'auto';
                });
            }
        });
    </script>
</body>
</html>