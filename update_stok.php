<?php
include 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$menu_id = intval($_POST['menu_id'] ?? 0);

if ($menu_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID menu tidak valid']);
    exit;
}

// Ambil data menu
$stmt = $koneksi->prepare("SELECT nama_menu, kategori_id FROM menu WHERE id = ?");
$stmt->bind_param("i", $menu_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Menu tidak ditemukan']);
    exit;
}

$menu = $result->fetch_assoc();
$stmt->close();

/* =========================================================
   MENU MINUMAN (pakai menu_varian)
   ========================================================= */
if ($menu['kategori_id'] == 1) {

    $stok_s = intval($_POST['stok_s'] ?? 0);
    $stok_m = intval($_POST['stok_m'] ?? 0);
    $stok_l = intval($_POST['stok_l'] ?? 0);

    if ($stok_s < 0 || $stok_m < 0 || $stok_l < 0) {
        echo json_encode(['success' => false, 'message' => 'Stok tidak boleh negatif']);
        exit;
    }

    $sizes = [
        ['ukuran_id' => 1, 'stok' => $stok_s, 'label' => 'S'],
        ['ukuran_id' => 2, 'stok' => $stok_m, 'label' => 'M'],
        ['ukuran_id' => 3, 'stok' => $stok_l, 'label' => 'L'],
    ];

    $koneksi->begin_transaction();

    try {
        foreach ($sizes as $size) {

            // Cek apakah varian sudah ada
            $check = $koneksi->prepare(
                "SELECT id FROM menu_varian WHERE menu_id = ? AND ukuran_id = ?"
            );
            $check->bind_param("ii", $menu_id, $size['ukuran_id']);
            $check->execute();
            $res = $check->get_result();

            if ($res->num_rows > 0) {
                // UPDATE
                $row = $res->fetch_assoc();
                $update = $koneksi->prepare(
                    "UPDATE menu_varian SET stok = ? WHERE id = ?"
                );
                $update->bind_param("ii", $size['stok'], $row['id']);
                $update->execute();
                $update->close();
            } else {
                // INSERT (AMAN karena sudah dicek)
                $insert = $koneksi->prepare(
                    "INSERT INTO menu_varian (menu_id, ukuran_id, harga, stok, aktif)
                     SELECT id, ?, harga, ?, 1 FROM menu WHERE id = ?"
                );
                $insert->bind_param("iii", $size['ukuran_id'], $size['stok'], $menu_id);
                $insert->execute();
                $insert->close();
            }

            $check->close();
        }

        // Update total stok menu
        $total_stok = $stok_s + $stok_m + $stok_l;
        $update_menu = $koneksi->prepare("UPDATE menu SET stok = ? WHERE id = ?");
        $update_menu->bind_param("ii", $total_stok, $menu_id);
        $update_menu->execute();
        $update_menu->close();

        $koneksi->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Stok minuman berhasil diperbarui',
            'data' => [
                'stok_s' => $stok_s,
                'stok_m' => $stok_m,
                'stok_l' => $stok_l,
                'total_stok' => $total_stok
            ]
        ]);

    } catch (Exception $e) {
        $koneksi->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

}
/* =========================================================
   MENU MAKANAN (langsung menu.stok)
   ========================================================= */
else {

    $new_stock = intval($_POST['new_stock'] ?? 0);

    if ($new_stock < 0) {
        echo json_encode(['success' => false, 'message' => 'Stok tidak boleh negatif']);
        exit;
    }

    $update = $koneksi->prepare("UPDATE menu SET stok = ? WHERE id = ?");
    $update->bind_param("ii", $new_stock, $menu_id);

    if ($update->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Stok makanan berhasil diperbarui',
            'data' => [
                'menu_id' => $menu_id,
                'stok' => $new_stock
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal update stok']);
    }

    $update->close();
}

$koneksi->close();
?>