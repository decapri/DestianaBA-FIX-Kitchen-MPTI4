
<?php
include 'config.php';

// Initialize menu_varian data for drinks if not exists
$init_query = "
    INSERT IGNORE INTO menu_varian (menu_id, ukuran_id, harga, stok, aktif)
    SELECT m.id, u.id, m.harga, 20, 1
    FROM menu m
    CROSS JOIN ukuran u
    WHERE m.kategori_id = 1 AND m.aktif = 1
    AND NOT EXISTS (
        SELECT 1 FROM menu_varian mv 
        WHERE mv.menu_id = m.id AND mv.ukuran_id = u.id
    )
";
$koneksi->query($init_query);

// Get filter
$filter = isset($_GET['category']) ? $_GET['category'] : 'all';

// Build query
$query = "SELECT m.*, k.nama_kategori 
          FROM menu m 
          LEFT JOIN kategori_menu k ON m.kategori_id = k.id 
          WHERE m.aktif = 1";

if ($filter !== 'all') {
    $query .= " AND m.kategori_id = " . intval($filter);
}
$query .= " ORDER BY k.urutan, m.nama_menu";

$result = $koneksi->query($query);
$menus = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Get varian data for drinks
        if ($row['kategori_id'] == 1) {
            $varian_query = "SELECT mv.*, u.nama_ukuran 
                            FROM menu_varian mv
                            JOIN ukuran u ON mv.ukuran_id = u.id
                            WHERE mv.menu_id = ? AND mv.aktif = 1
                            ORDER BY u.id";
            $stmt = $koneksi->prepare($varian_query);
            $stmt->bind_param("i", $row['id']);
            $stmt->execute();
            $varian_result = $stmt->get_result();
            
            $row['varian'] = [];
            while ($varian = $varian_result->fetch_assoc()) {
                $row['varian'][$varian['nama_ukuran']] = [
                    'id' => $varian['id'],
                    'stok' => $varian['stok'],
                    'harga' => $varian['harga']
                ];
            }
            $stmt->close();
        }
        $menus[] = $row;
    }
}

// Get categories with counts
$categories = [];
$cat_result = $koneksi->query("SELECT * FROM kategori_menu ORDER BY urutan");
if ($cat_result) {
    while ($row = $cat_result->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kopi Janti - Menu</title>
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

        h1 {
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
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            filter: grayscale(100%) brightness(0) saturate(100%);
        }

        .categories {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .category-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 25px;
            background: #8d6e63;
            color: white;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .category-btn.active {
            background: #5d4037;
        }

        .category-btn .count {
            background: white;
            color: #8d6e63;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 13px;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }

        .menu-card {
            background: #5d4037;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            transition: transform 0.3s;
            color: white;
            cursor: pointer;
        }

        .menu-card:hover {
            transform: translateY(-5px);
        }

        .menu-image {
            width: 100%;
            height: 200px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .menu-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .menu-info {
            padding: 20px;
            text-align: center;
        }

        .menu-name {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .menu-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
        }

        .menu-stock {
            color: #ddd;
        }

        .menu-price {
            font-weight: 700;
            font-size: 16px;
        }

        .size-badges {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .size-badge {
            background: rgba(255,255,255,0.2);
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background-color: #fff;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 0;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            animation: slideIn 0.3s;
            max-height: 90vh;
            overflow-y: auto;
        }

        @keyframes slideIn {
            from {
                transform: translate(-50%, -60%);
                opacity: 0;
            }
            to {
                transform: translate(-50%, -50%);
                opacity: 1;
            }
        }

        .modal-header {
            background: #5d4037;
            color: white;
            padding: 20px;
            border-radius: 20px 20px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 24px;
            flex: 1;
            margin-right: 12px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .close {
            color: white;
            font-size: 28px;
            font-weight: 700;
            cursor: pointer;
            line-height: 1;
            transition: transform 0.2s;
            padding: 6px 10px;
            border-radius: 8px;
            background: rgba(255,255,255,0.06);
            flex: 0 0 auto;
            margin-left: 8px;
        }

        .close:hover {
            transform: scale(1.2);
        }

        .modal-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #5d4037;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #5d4037;
        }

        .form-group input[readonly] {
            background-color: #f5f5f5;
            cursor: not-allowed;
        }

        .stock-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stock-btn {
            width: 45px;
            height: 45px;
            border: none;
            border-radius: 50%;
            background: #8d6e63;
            color: white;
            font-size: 24px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stock-btn:hover {
            background: #5d4037;
            transform: scale(1.1);
        }

        .stock-input {
            flex: 1;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
        }

        .size-section {
            background: #f8f8f8;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 15px;
        }

        .size-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .size-label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            color: #5d4037;
        }

        .size-badge-modal {
            background: #8d6e63;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
            background: #4caf50;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .submit-btn:hover {
            background: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            display: none;
        }

        .alert.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .info-text {
            color: #777;
            font-size: 12px;
            text-align: center;
            margin-top: 8px;
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

            .menu-grid {
                grid-template-columns: 1fr;
            }

            .modal-content {
                width: 95%;
                max-height: 85vh;
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
            <div class="nav-icon"><img src="assets/Home.png" alt="Home"></div>
            <div class="nav-label">Home</div>
        </a>
        <a href="menu.php" class="nav-item active">
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
            <h1>Menu & Stok Management</h1>
            <div class="search-bar">
                <button class="notification-btn">
                    <img src="assets/Bell.png" alt="Notifications" style="width: 20px; height: 20px;">
                </button>
                <div class="search-wrapper">
                    <input type="text" class="search-input" placeholder="Cari menu...">
                    <img src="assets/Search.png" alt="Search" class="search-icon">
                </div>
            </div>
        </div>

        <div class="categories">
            <a href="?category=all" class="category-btn <?= $filter === 'all' ? 'active' : '' ?>">
                Semua <span class="count"><?= count($menus) ?></span>
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="?category=<?= $cat['id'] ?>" class="category-btn <?= $filter == $cat['id'] ? 'active' : '' ?>">
                    <?= htmlspecialchars($cat['nama_kategori']) ?> <span class="count"><?= count(array_filter($menus, function($m) use ($cat) { return $m['kategori_id'] == $cat['id']; })) ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="menu-grid">
            <?php foreach ($menus as $menu): ?>
                <div class="menu-card" onclick='openModal(<?= json_encode($menu, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                    <div class="menu-image">
                        <?php if (!empty($menu['gambar'])): ?>
                            <img src="<?= htmlspecialchars($menu['gambar']) ?>" alt="<?= htmlspecialchars($menu['nama_menu']) ?>" onerror="this.parentElement.innerHTML='<div style=\'font-size: 60px;\'>☕</div>'">
                        <?php else: ?>
                            <div style="font-size: 60px;">☕</div>
                        <?php endif; ?>
                    </div>
                    <div class="menu-info">
                        <div class="menu-name"><?= htmlspecialchars($menu['nama_menu']) ?></div>
                        <div class="menu-details">
                            <?php if ($menu['kategori_id'] == 1 && !empty($menu['varian'])): ?>
                                <div class="menu-stock">
                                    <div class="size-badges">
                                        <?php if (isset($menu['varian']['S'])): ?>
                                            <span class="size-badge">S: <span class="stock-s-<?= $menu['id'] ?>"><?= $menu['varian']['S']['stok'] ?></span></span>
                                        <?php endif; ?>
                                        <?php if (isset($menu['varian']['M'])): ?>
                                            <span class="size-badge">M: <span class="stock-m-<?= $menu['id'] ?>"><?= $menu['varian']['M']['stok'] ?></span></span>
                                        <?php endif; ?>
                                        <?php if (isset($menu['varian']['L'])): ?>
                                            <span class="size-badge">L: <span class="stock-l-<?= $menu['id'] ?>"><?= $menu['varian']['L']['stok'] ?></span></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="menu-stock">Sisa: <span class="stock-value-<?= $menu['id'] ?>"><?= $menu['stok'] ?></span> Porsi</div>
                            <?php endif; ?>
                            <div class="menu-price">Rp<?= number_format($menu['harga'], 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="stockModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Update Stok Menu</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div id="alertBox" class="alert"></div>
                <form id="stockForm">
                    <input type="hidden" id="menuId" name="menu_id">
                    <input type="hidden" id="menuKategori" name="kategori_id">
                    
                    <div class="form-group">
                        <label>Nama Menu</label>
                        <input type="text" id="menuName" readonly>
                    </div>

                    <div id="drinkStockForm" style="display: none;">
                        <input type="hidden" id="varianIdS" name="varian_id_s">
                        <input type="hidden" id="varianIdM" name="varian_id_m">
                        <input type="hidden" id="varianIdL" name="varian_id_l">
                        
                        <div class="size-section">
                            <div class="size-header">
                                <div class="size-label">
                                    <span class="size-badge-modal">S</span>
                                    <span>Small</span>
                                </div>
                            </div>
                            <div class="stock-controls">
                                <button type="button" class="stock-btn" onclick="decreaseStock('s')">−</button>
                                <input type="number" id="stockS" name="stok_s" class="stock-input" value="0" min="0">
                                <button type="button" class="stock-btn" onclick="increaseStock('s')">+</button>
                            </div>
                        </div>

                        <div class="size-section">
                            <div class="size-header">
                                <div class="size-label">
                                    <span class="size-badge-modal">M</span>
                                    <span>Medium</span>
                                </div>
                            </div>
                            <div class="stock-controls">
                                <button type="button" class="stock-btn" onclick="decreaseStock('m')">−</button>
                                <input type="number" id="stockM" name="stok_m" class="stock-input" value="0" min="0">
                                <button type="button" class="stock-btn" onclick="increaseStock('m')">+</button>
                            </div>
                        </div>

                        <div class="size-section">
                            <div class="size-header">
                                <div class="size-label">
                                    <span class="size-badge-modal">L</span>
                                    <span>Large</span>
                                </div>
                            </div>
                            <div class="stock-controls">
                                <button type="button" class="stock-btn" onclick="decreaseStock('l')">−</button>
                                <input type="number" id="stockL" name="stok_l" class="stock-input" value="0" min="0">
                                <button type="button" class="stock-btn" onclick="increaseStock('l')">+</button>
                            </div>
                        </div>
                        <p class="info-text">Gunakan tombol +/- atau ketik langsung untuk mengubah stok per ukuran</p>
                    </div>

                    <div id="foodStockForm" style="display: none;">
                        <div class="form-group">
                            <label>Stok Saat Ini</label>
                            <input type="number" id="currentStock" readonly>
                        </div>

                        <div class="form-group">
                            <label>Atur Stok Baru</label>
                            <div class="stock-controls">
                                <button type="button" class="stock-btn" onclick="decreaseStock('food')">−</button>
                                <input type="number" id="newStock" name="new_stock" class="stock-input" value="0" min="0">
                                <button type="button" class="stock-btn" onclick="increaseStock('food')">+</button>
                            </div>
                            <p class="info-text">Gunakan tombol +/- atau ketik langsung untuk mengubah stok</p>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">Simpan Perubahan</button>
                </form>
            </div>
        </div>
    </div>

    <script>
function openModal(menuData) {
    currentMenuData = menuData;

    document.getElementById('menuId').value = menuData.id;
    document.getElementById('menuKategori').value = menuData.kategori_id;
    document.getElementById('menuName').value = menuData.nama_menu;
    document.getElementById('alertBox').style.display = 'none';

    if (menuData.kategori_id == 1) {
        // === MINUMAN ===
        document.getElementById('drinkStockForm').style.display = 'block';
        document.getElementById('foodStockForm').style.display = 'none';

        document.getElementById('stockS').value =
            menuData.varian?.S?.stok ?? 0;
        document.getElementById('stockM').value =
            menuData.varian?.M?.stok ?? 0;
        document.getElementById('stockL').value =
            menuData.varian?.L?.stok ?? 0;

    } else {
        // === MAKANAN ===
        document.getElementById('drinkStockForm').style.display = 'none';
        document.getElementById('foodStockForm').style.display = 'block';

        document.getElementById('currentStock').value = menuData.stok;
        document.getElementById('newStock').value = menuData.stok;
    }

    document.getElementById('stockModal').style.display = 'block';
}


        function closeModal() {
            document.getElementById('stockModal').style.display = 'none';
            currentMenuData = null;
        }

        function increaseStock(type) {
            let input;
            if (type === 's') {
                input = document.getElementById('stockS');
            } else if (type === 'm') {
                input = document.getElementById('stockM');
            } else if (type === 'l') {
                input = document.getElementById('stockL');
            } else if (type === 'food') {
                input = document.getElementById('newStock');
            }
            
            if (input) {
                input.value = parseInt(input.value) + 1;
            }
        }

        function decreaseStock(type) {
            let input;
            if (type === 's') {
                input = document.getElementById('stockS');
            } else if (type === 'm') {
                input = document.getElementById('stockM');
            } else if (type === 'l') {
                input = document.getElementById('stockL');
            } else if (type === 'food') {
                input = document.getElementById('newStock');
            }
            
            if (input) {
                const currentValue = parseInt(input.value);
                if (currentValue > 0) {
                    input.value = currentValue - 1;
                }
            }
        }

        // Handle form submission
        document.getElementById('stockForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('update_stok.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const alertBox = document.getElementById('alertBox');
                alertBox.style.display = 'block';
                
                if (data.success) {
                    alertBox.className = 'alert success';
                    alertBox.textContent = data.message;
                    
                    // Update stock display in the card
                    if (currentMenuData) {
                        if (currentMenuData.kategori_id == 1) {
                            // Update drink sizes
                            document.querySelector('.stock-s-' + currentMenuData.id).textContent = data.data.stok_s;
                            document.querySelector('.stock-m-' + currentMenuData.id).textContent = data.data.stok_m;
                            document.querySelector('.stock-l-' + currentMenuData.id).textContent = data.data.stok_l;
                        } else {
                            // Update food stock
                            document.querySelector('.stock-value-' + currentMenuData.id).textContent = data.data.new_stock;
                        }
                    }
                    
                    // Close modal after 1.5 seconds
                    setTimeout(() => {
                        closeModal();
                    }, 1500);
                } else {
                    alertBox.className = 'alert error';
                    alertBox.textContent = data.message;
                }
            })
            .catch(error => {
                const alertBox = document.getElementById('alertBox');
                alertBox.style.display = 'block';
                alertBox.className = 'alert error';
                alertBox.textContent = 'Terjadi kesalahan saat memproses data.';
                console.error('Error:', error);
            });
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('stockModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // Search functionality
        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase();
                document.querySelectorAll('.menu-card').forEach(card => {
                    const text = card.textContent.toLowerCase();
                    card.style.display = text.includes(searchTerm) ? 'block' : 'none';
                });
            });
        }
    </script>
</body>
</html>