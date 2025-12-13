<!-- FILE: dashboard_kitchen.php (sudah ada, tidak perlu diubah) -->
<!-- Ini adalah file yang sudah Anda miliki -->

<!-- ================================ -->
<!-- FILE: history.php (BARU) -->
<!-- ================================ -->

<?php
// Include config
include 'config.php';

// Get date filter
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$start_time = isset($_GET['start_time']) ? $_GET['start_time'] : '00:00';
$end_time = isset($_GET['end_time']) ? $_GET['end_time'] : '23:59';

// Build query with date filter
$query = "SELECT o.*, so.nama_status, m.nomor_meja 
          FROM orders o 
          LEFT JOIN status_order so ON o.status_id = so.id 
          LEFT JOIN meja m ON o.meja_id = m.id 
          WHERE DATE(o.tanggal_order) BETWEEN ? AND ?
          AND TIME(o.tanggal_order) BETWEEN ? AND ?
          ORDER BY o.tanggal_order DESC";

$stmt = $koneksi->prepare($query);
$stmt->bind_param("ssss", $start_date, $end_date, $start_time, $end_time);
$stmt->execute();
$result = $stmt->get_result();
$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
$stmt->close();

// Get order details function
function getOrderDetails($koneksi, $order_id) {
    $stmt = $koneksi->prepare("
        SELECT od.*, m.nama_menu 
        FROM order_details od 
        JOIN menu m ON od.menu_id = m.id 
        WHERE od.order_id = ?
    ");
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
    <title>Kopi Janti - Riwayat Pembelian</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
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

        .main-content {
            margin-left: 120px;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        h1 {
            font-size: 48px;
            color: #3e2723;
            font-weight: 700;
        }

        .header-actions {
            display: flex;
            gap: 15px;
        }

        .date-time-badge {
            background: white;
            padding: 12px 20px;
            border-radius: 25px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #5d4037;
            font-weight: 500;
        }

        .btn-order {
            background: #a8d5f7;
            color: #1565c0;
            padding: 12px 24px;
            border-radius: 25px;
            border: none;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-order:hover {
            opacity: 0.8;
            transform: scale(1.05);
        }

        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
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
            font-size: 14px;
        }

        .date-input, .time-input {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            color: #5d4037;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            margin-left: auto;
        }

        .btn-icon {
            width: 40px;
            height: 40px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .btn-search {
            background: #8d9e92;
        }

        .btn-filter {
            background: #d4b5a0;
        }

        .history-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #d4b5a0;
        }

        th {
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            color: #3e2723;
            font-size: 14px;
        }

        td {
            padding: 18px 15px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
            color: #444;
        }

        tbody tr:hover {
            background: #faf8f5;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .status-selesai {
            background: #c8e6c9;
            color: #2e7d32;
        }

        .status-dimasak {
            background: #ffe0b2;
            color: #d84315;
        }

        .status-siap {
            background: #b3e5fc;
            color: #0277bd;
        }

        .btn-detail {
            color: #4da6ff;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-detail:hover {
            text-decoration: underline;
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
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }

        .close-modal {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 28px;
            cursor: pointer;
            border: none;
            background: none;
            color: #666;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s;
        }

        .close-modal:hover {
            background: #f0f0f0;
        }

        .modal-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }

        .modal-order-number {
            font-size: 20px;
            font-weight: 700;
            color: #3e2723;
            margin-bottom: 5px;
        }

        .modal-info {
            font-size: 13px;
            color: #666;
            margin-bottom: 3px;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 14px;
            color: #444;
        }

        .item-name {
            flex: 1;
        }

        .item-price {
            font-weight: 600;
            color: #5d4037;
        }

        .total-price {
            border-top: 2px solid #e0e0e0;
            padding-top: 15px;
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
            font-size: 18px;
            font-weight: 700;
            color: #3e2723;
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

            h1 {
                font-size: 32px;
            }

            .filter-section {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-actions {
                margin-left: 0;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 12px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="nav-item logo">             
            <img src="assets/logo.png" alt="Kopi Janti" style="width: 70px; height: 70px;">
        </div>
        <a href="dashboard_kitchen.php" class="nav-item">
            <img src="assets/Home.png" alt="Home" style="width: 20px; height: 20px;">
            <div class="nav-label">Home</div>
        </a>
        <a href="menu.php" class="nav-item">
            <img src="assets/menu.png" alt="Menu" style="width: 20px; height: 20px;">
            <div class="nav-label">Menu</div>
        </a>
        <div class="nav-item active">
            <img src="assets/histori.png" alt="History" style="width: 20px; height: 20px;">
            <div class="nav-label">History</div>
        </div>
        <a href="logout.php" class="nav-item end">
            <img src="assets/logout.png" alt="Logout" style="width: 20px; height: 20px;">
            <div class="nav-label">Log Out</div>
        </a>
    </div>

    <div class="main-content">
        <div class="header-section">
            <h1>Riwayat Pembelian</h1>
            <div class="header-actions">
                <div class="date-time-badge">
                    🕐 <?= date('l, d F Y') ?>
                </div>
                <button class="btn-order">
                    <span>💡</span> Open Order
                </button>
            </div>
        </div>

        <form method="GET" class="filter-section">
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
                <button type="submit" class="btn-icon btn-search">
                    <img src="assets/Search.png" alt="Search" style="width: 20px; height: 20px;">
                </button>
                <button type="button" class="btn-icon btn-filter">
                    <span style="font-size: 20px;">⚙</span>
                </button>
            </div>
        </form>

        <div class="history-table">
            <table>
                <thead>
                    <tr>
                        <th>No. Struk</th>
                        <th>Tanggal & Waktu</th>
                        <th>Nama Pelanggan</th>
                        <th>Status</th>
                        <th>Layanan</th>
                        <th>Total</th>
                        <th>Pesanan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #999;">
                                Tidak ada data riwayat pembelian
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?= htmlspecialchars($order['nomor_struk']) ?></td>
                                <td><?= date('d/m/Y-H:i A', strtotime($order['tanggal_order'])) ?></td>
                                <td><?= htmlspecialchars($order['nama_pelanggan'] ?: 'Guest') ?></td>
                                <td>
                                    <?php 
                                        $status_class = '';
                                        if ($order['status_id'] == 1) $status_class = 'status-baru';
                                        elseif ($order['status_id'] == 2) $status_class = 'status-dimasak';
                                        elseif ($order['status_id'] == 3) $status_class = 'status-siap';
                                        else $status_class = 'status-selesai';
                                    ?>
                                    <span class="status-badge <?= $status_class ?>">
                                        <?php if ($order['status_id'] == 4): ?>
                                            ✓ <?= $order['nama_status'] ?>
                                        <?php elseif ($order['status_id'] == 2): ?>
                                            🍳 <?= $order['nama_status'] ?>
                                        <?php elseif ($order['status_id'] == 3): ?>
                                            🏠 <?= $order['nama_status'] ?>
                                        <?php else: ?>
                                            <?= $order['nama_status'] ?>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td><?= $order['tipe_order'] ?></td>
                                <td>Rp <?= number_format($order['total'], 0, ',', '.') ?></td>
                                <td>
                                    <a href="#" onclick="showModal(<?= $order['id'] ?>); return false;" class="btn-detail">Detail</a>
                                </td>
                            </tr>

                            <!-- Modal for this order -->
                            <div class="modal" id="modal-<?= $order['id'] ?>">
                                <div class="modal-content">
                                    <button class="close-modal" onclick="closeModal(<?= $order['id'] ?>)">×</button>
                                    <div class="modal-header">
                                        <div class="modal-order-number">#<?= htmlspecialchars($order['nomor_struk']) ?></div>
                                        <div class="modal-info">🕐 <?= date('d/m/Y, H:i', strtotime($order['tanggal_order'])) ?></div>
                                        <div class="modal-info">Pelanggan: <?= htmlspecialchars($order['nama_pelanggan'] ?: 'Guest') ?></div>
                                        <?php if ($order['tipe_order'] === 'Dine In' && $order['nomor_meja']): ?>
                                            <div class="modal-info">No Meja: <?= htmlspecialchars($order['nomor_meja']) ?></div>
                                        <?php endif; ?>
                                        <div class="modal-info">Layanan: <?= $order['tipe_order'] ?></div>
                                    </div>

                                    <div class="modal-items">
                                        <?php 
                                            $details = getOrderDetails($koneksi, $order['id']);
                                            foreach ($details as $item): 
                                        ?>
                                            <div class="order-item">
                                                <div class="item-name"><?= $item['jumlah'] ?>x <?= htmlspecialchars($item['nama_menu']) ?></div>
                                                <div class="item-price">Rp. <?= number_format($item['harga_satuan'], 0, ',', '.') ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <div class="total-price">
                                        <span>Total</span>
                                        <span>Rp. <?= number_format($order['total'], 0, ',', '.') ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function showModal(orderId) {
            document.getElementById('modal-' + orderId).classList.add('active');
        }

        function closeModal(orderId) {
            document.getElementById('modal-' + orderId).classList.remove('active');
        }

        // Close modal when clicking outside
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>