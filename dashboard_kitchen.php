<?php
// Include config
include 'config.php';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $order_id = $_POST['order_id'];
    $new_status_id = $_POST['new_status_id'];
    
    $stmt = $koneksi->prepare("UPDATE orders SET status_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->bind_param("ii", $new_status_id, $order_id);
    $stmt->execute();
    $stmt->close();
    
    header('Location: ' . $_SERVER['PHP_SELF'] . (isset($_GET['filter']) ? '?filter=' . $_GET['filter'] : ''));
    exit;
}

// Get filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Build query based on filter
$query = "SELECT o.*, so.nama_status, m.nomor_meja 
          FROM orders o 
          LEFT JOIN status_order so ON o.status_id = so.id 
          LEFT JOIN meja m ON o.meja_id = m.id 
          WHERE 1=1";

if ($filter !== 'all') {
    $query .= " AND o.status_id = " . intval($filter);
}
$query .= " ORDER BY o.tanggal_order DESC";

$result = $koneksi->query($query);
$orders = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

// Count orders by status
$status_counts = [
    'all' => 0,
    '1' => 0,  // Baru
    '2' => 0,  // Dimasak
    '3' => 0,  // Siap dihidangkan
    '4' => 0   // Selesai
];

$count_result = $koneksi->query("SELECT status_id, COUNT(*) as count FROM orders GROUP BY status_id");
if ($count_result) {
    while ($row = $count_result->fetch_assoc()) {
        $status_counts[$row['status_id']] = $row['count'];
        $status_counts['all'] += $row['count'];
    }
}

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
    <title>Kopi Janti - Order List Kitchen</title>
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
            font-variant-emoji: text;
            
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

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .logo-header {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
        }

        .logo-text {
            font-size: 14px;
            font-weight: 600;
            color: #3e2723;
        }

        h1 {
            font-size: 48px;
            color: #3e2723;
            font-weight: 700;
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
            width: 200px;
            font-size: 14px;
        }

        .search-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            pointer-events: none;
        }

        /* Monochrome icon helper: convert UI icons to black-and-white */
        .icon-mono {
            filter: grayscale(100%) brightness(0) saturate(100%);
            /* keep natural size and preserve transparency */
        }

        /* Use inverted monochrome on dark backgrounds if needed */
        .icon-mono.invert {
            filter: grayscale(100%) brightness(0) invert(1) saturate(100%);
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
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .order-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            cursor: pointer;
        }

        .order-card:hover {
            transform: translateY(-5px);
        }

        .order-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e0e0e0;
        }

        .customer-name {
            font-size: 16px;
            font-weight: 700;
            color: #3e2723;
            margin-bottom: 5px;
        }

        .order-number {
            font-size: 15px;
            font-weight: 600;
            color: #666;
        }

        .order-type-badge {
            font-size: 11px;
            color: #666;
            margin-top: 2px;
        }

        .order-info {
            font-size: 12px;
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

        .see-more {
            text-align: center;
            color: #666;
            font-size: 12px;
            margin: 8px 0;
            text-decoration: underline;
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
            background: #a8d5f7;
            color: #1565c0;
        }

        .status-btn-single.dimasak {
            background: #ffe0b2;
            color: #d84315;
        }

        .status-btn-single.siap {
            background: #b3e5fc;
            color: #0277bd;
        }

        .status-btn-single.selesai {
            background: #c8e6c9;
            color: #2e7d32;
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
            padding: 25px;
            max-width: 400px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }

        .close-modal {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            border: none;
            background: none;
            color: #666;
            width: 30px;
            height: 30px;
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
            margin-bottom: 15px;
            padding-bottom: 15px;
            padding-top: 20px;
            border-bottom: 2px solid #e0e0e0;
        }

        .modal-customer-name {
            font-size: 18px;
            font-weight: 700;
            color: #3e2723;
            margin-bottom: 5px;
        }

        .modal-order-number {
            font-size: 15px;
            font-weight: 600;
            color: #666;
            float: right;
            margin-top: -25px;
        }

        .modal-type-badge {
            display: inline-block;
            font-size: 11px;
            color: #666;
            background: #f5f5f5;
            padding: 3px 8px;
            border-radius: 5px;
            margin-top: 5px;
        }

        .modal-info {
            font-size: 12px;
            color: #666;
            margin-bottom: 3px;
        }

        .modal-items {
            margin: 15px 0;
        }

        .total-price {
            border-top: 2px solid #e0e0e0;
            padding-top: 12px;
            margin-top: 12px;
            display: flex;
            justify-content: space-between;
            font-size: 16px;
            font-weight: 700;
            color: #3e2723;
        }

        .status-actions {
            display: flex;
            gap: 8px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .status-action-btn {
            flex: 1;
            min-width: 100px;
            padding: 10px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.3s;
        }

        .status-action-btn.dimasak {
            background: #ffe0b2;
            color: #d84315;
        }

        .status-action-btn.siap {
            background: #b3e5fc;
            color: #0277bd;
        }

        .status-action-btn.selesai {
            background: #c8e6c9;
            color: #2e7d32;
        }

        .status-action-btn:hover {
            opacity: 0.8;
            transform: scale(1.05);
        }

        .btn-simpan {
            background: #a8d5f7;
            color: #1565c0;
            margin-top: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
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

            h1 {
                font-size: 32px;
            }

            .header {
                flex-direction: column;
                gap: 15px;
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
            <img src="assets/Home.png" alt="Home" class="icon-mono" style="width: 20px; height: 20px;">
            <div class="nav-label">Home</div>
        </a>
        <a href="menu.php" class="nav-item">
            <img src="assets/menu.png" alt="Menu" class="icon-mono" style="width: 20px; height: 20px;">
            <div class="nav-label">Menu</div>
        </a>
        <a href="history.php" class="nav-item">
            <img src="assets/histori.png" alt="History" class="icon-mono" style="width: 20px; height: 20px;">
            <div class="nav-label">History</div>
        </a>
        <a href="logout.php" class="nav-item end">
            <img src="assets/logout.png" alt="Logout" class="icon-mono" style="width: 20px; height: 20px;">
            <div class="nav-label">Log Out</div>
        </a>
    </div>

    <div class="main-content">
        <div class="header">
            <div class="logo-header">
                <div>
                    <h1>Order List</h1>
                </div>
            </div>
            <div class="search-bar">
                <button class="notification-btn">
                    <img src="assets/Bell.png" alt="Notifications" class="icon-mono" style="width: 20px; height: 20px;">
                    <?php if ($status_counts['1'] > 0): ?>
                        <span class="notification-badge"><?= $status_counts['1'] ?></span>
                    <?php endif; ?>
                </button>
                <div class="search-wrapper">
                    <input type="text" class="search-input" placeholder="Value">
                    <img src="assets/Search.png" alt="Search" class="search-icon icon-mono">
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
                Siap dihidangkan <span class="count"><?= $status_counts['3'] ?></span>
            </a>
            <a href="?filter=4" class="filter-btn <?= $filter === '4' ? 'active' : '' ?>">
                Selesai <span class="count"><?= $status_counts['4'] ?></span>
            </a>
        </div>

        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="empty-state-text">Tidak ada pesanan</div>
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
                                <div class="order-info">⏰ <?= date('d/m/Y, H:i', strtotime($order['tanggal_order'])) ?></div>
                                <?php if ($order['tipe_order'] === 'Dine In' && $order['nomor_meja']): ?>
                                    <div class="order-info">No Meja: <?= htmlspecialchars($order['nomor_meja']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="order-number">#<?= htmlspecialchars($order['nomor_struk']) ?></div>
                                <div class="order-type-badge"><?= $order['tipe_order'] ?></div>
                            </div>
                        </div>

                        <div class="order-items-section">
                            <div class="order-title">Order (<?= $item_count ?>)</div>
                            <?php foreach (array_slice($details, 0, 3) as $item): ?>
                                <div class="order-item">
                                    <div class="item-name"><?= $item['jumlah'] ?>x <?= htmlspecialchars($item['nama_menu']) ?></div>
                                    <div class="item-price">Rp. <?= number_format($item['harga_satuan'], 0, ',', '.') ?></div>
                                </div>
                            <?php endforeach; ?>
                            <?php if ($item_count > 3): ?>
                                <div class="see-more">See More</div>
                            <?php endif; ?>
                        </div>

                        <?php
                            $status_class = '';
                            $status_text = '';
                            $status_icon = '';

                            
// 🔧 DIUBAH: emoji dibungkus agar hitam putih
if ($order['status_id'] == 1) {
    $status_class = 'baru';
    $status_icon = '<span class="emoji">✨</span>';
    $status_text = 'Baru';
} elseif ($order['status_id'] == 2) {
    $status_class = 'dimasak';
    $status_icon = '<span class="emoji">🍳</span>';
    $status_text = 'Dimasak';
} elseif ($order['status_id'] == 3) {
    $status_class = 'siap';
    $status_icon = '<span class="emoji">🏠</span>';
    $status_text = 'Siap dihidangkan';
} else {
    $status_class = 'selesai';
    $status_icon = '<span class="emoji">✓</span>';
    $status_text = 'Selesai';
}
?>

                        <div class="status-btn-single <?= $status_class ?>">
                            <?= $status_icon ?> <?= $status_text ?>
                        </div>
                    </div>

                    <!-- Modal for this order -->
                    <div class="modal" id="modal-<?= $order['id'] ?>" onclick="event.target === this && closeModal(<?= $order['id'] ?>)">
                        <div class="modal-content" onclick="event.stopPropagation()">
                            <button class="close-modal" onclick="closeModal(<?= $order['id'] ?>)">×</button>
                            
                            <div class="modal-header">
                                <div class="modal-customer-name"><?= htmlspecialchars($order['nama_pelanggan'] ?: 'Guest') ?></div>
                                <div class="modal-order-number">#<?= htmlspecialchars($order['nomor_struk']) ?></div>
                                <div class="modal-type-badge"><?= $order['tipe_order'] ?></div>
                                <div class="modal-info">⏰ <?= date('d/m/Y, H:i', strtotime($order['tanggal_order'])) ?></div>
                                <?php if ($order['tipe_order'] === 'Dine In' && $order['nomor_meja']): ?>
                                    <div class="modal-info">No Meja: <?= htmlspecialchars($order['nomor_meja']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="modal-items">
                                <?php foreach ($details as $item): ?>
                                    <div class="order-item">
                                        <div class="item-name"><?= $item['jumlah'] ?>x <?= htmlspecialchars($item['nama_menu']) ?></div>
                                        <div class="item-price">Rp. <?= number_format($item['harga_satuan'], 0, ',', '.') ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="total-price">
                                Rp. <?= number_format($order['total'], 0, ',', '.') ?>
                            </div>

                            <form method="POST" class="status-actions">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                
                                <?php if ($order['status_id'] == 1): ?>
                                    <button type="submit" name="new_status_id" value="2" class="status-action-btn dimasak">
                                        🍳 Dimasak
                                    </button>
                                    <button type="submit" name="new_status_id" value="3" class="status-action-btn siap">
                                        🏠 Siap dihidangkan
                                    </button>
                                    <button type="submit" name="new_status_id" value="4" class="status-action-btn selesai">
                                        ✓ Selesai
                                    </button>
                                <?php elseif ($order['status_id'] == 2): ?>
                                    <button type="submit" name="new_status_id" value="3" class="status-action-btn siap">
                                        🏠 Siap dihidangkan
                                    </button>
                                    <button type="submit" name="new_status_id" value="4" class="status-action-btn selesai">
                                        ✓ Selesai
                                    </button>
                                <?php elseif ($order['status_id'] == 3): ?>
                                    <button type="submit" name="new_status_id" value="4" class="status-action-btn selesai">
                                        ✓ Selesai
                                    </button>
                                <?php endif; ?>
                                
                                <button type="button" class="status-action-btn btn-simpan" onclick="closeModal(<?= $order['id'] ?>)">
                                    💾 Simpan
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function showModal(orderId) {
            document.getElementById('modal-' + orderId).classList.add('active');
        }

        function closeModal(orderId) {
            document.getElementById('modal-' + orderId).classList.remove('active');
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
    </script>
</body>
</html>