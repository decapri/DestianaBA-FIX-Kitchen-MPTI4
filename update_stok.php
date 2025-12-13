<?php
include 'config.php';

// Set response header to JSON
header('Content-Type: application/json');

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get POST data
$menu_id = isset($_POST['menu_id']) ? intval($_POST['menu_id']) : 0;
$kategori_id = isset($_POST['kategori_id']) ? intval($_POST['kategori_id']) : 0;

// Validate input
if ($menu_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID menu tidak valid'
    ]);
    exit;
}

// Get current menu data
$query = "SELECT nama_menu, stok, stok_s, stok_m, stok_l, kategori_id FROM menu WHERE id = ?";
$stmt = $koneksi->prepare($query);
$stmt->bind_param("i", $menu_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Menu tidak ditemukan'
    ]);
    exit;
}

$menu = $result->fetch_assoc();

// Check if it's a drink (kategori_id = 1) or food
if ($menu['kategori_id'] == 1) {
    // Handle drink with sizes
    $stok_s = isset($_POST['stok_s']) ? intval($_POST['stok_s']) : 0;
    $stok_m = isset($_POST['stok_m']) ? intval($_POST['stok_m']) : 0;
    $stok_l = isset($_POST['stok_l']) ? intval($_POST['stok_l']) : 0;

    // Validate
    if ($stok_s < 0 || $stok_m < 0 || $stok_l < 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Stok tidak boleh negatif'
        ]);
        exit;
    }

    // Calculate total stock
    $total_stok = $stok_s + $stok_m + $stok_l;

    // Update stock in database
    $update_query = "UPDATE menu SET stok = ?, stok_s = ?, stok_m = ?, stok_l = ? WHERE id = ?";
    $update_stmt = $koneksi->prepare($update_query);
    $update_stmt->bind_param("iiiii", $total_stok, $stok_s, $stok_m, $stok_l, $menu_id);

    if ($update_stmt->execute()) {
        // Calculate changes
        $change_s = $stok_s - ($menu['stok_s'] ?? 0);
        $change_m = $stok_m - ($menu['stok_m'] ?? 0);
        $change_l = $stok_l - ($menu['stok_l'] ?? 0);

        $changes = [];
        if ($change_s != 0) $changes[] = "S: " . ($change_s > 0 ? "+$change_s" : $change_s);
        if ($change_m != 0) $changes[] = "M: " . ($change_m > 0 ? "+$change_m" : $change_m);
        if ($change_l != 0) $changes[] = "L: " . ($change_l > 0 ? "+$change_l" : $change_l);

        $change_text = !empty($changes) ? implode(', ', $changes) : 'tidak berubah';

        echo json_encode([
            'success' => true,
            'message' => 'Stok berhasil diupdate! ' . $menu['nama_menu'] . ' (' . $change_text . ')',
            'data' => [
                'menu_id' => $menu_id,
                'menu_name' => $menu['nama_menu'],
                'stok_s' => $stok_s,
                'stok_m' => $stok_m,
                'stok_l' => $stok_l,
                'total_stok' => $total_stok
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal mengupdate stok: ' . $koneksi->error
        ]);
    }

    $update_stmt->close();

} else {
    // Handle food without sizes
    $new_stock = isset($_POST['new_stock']) ? intval($_POST['new_stock']) : 0;

    // Validate
    if ($new_stock < 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Stok tidak boleh negatif'
        ]);
        exit;
    }

    $current_stock = $menu['stok'];
    $stock_change = $new_stock - $current_stock;

    // Update stock in database
    $update_query = "UPDATE menu SET stok = ? WHERE id = ?";
    $update_stmt = $koneksi->prepare($update_query);
    $update_stmt->bind_param("ii", $new_stock, $menu_id);

    if ($update_stmt->execute()) {
        $change_text = '';
        if ($stock_change > 0) {
            $change_text = 'ditambah ' . $stock_change;
        } elseif ($stock_change < 0) {
            $change_text = 'dikurangi ' . abs($stock_change);
        } else {
            $change_text = 'tidak berubah';
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Stok berhasil diupdate! ' . $menu['nama_menu'] . ' ' . $change_text . ', sekarang memiliki ' . $new_stock . ' porsi',
            'data' => [
                'menu_id' => $menu_id,
                'menu_name' => $menu['nama_menu'],
                'old_stock' => $current_stock,
                'stock_change' => $stock_change,
                'new_stock' => $new_stock
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal mengupdate stok: ' . $koneksi->error
        ]);
    }

    $update_stmt->close();
}

$stmt->close();
$koneksi->close();
?>