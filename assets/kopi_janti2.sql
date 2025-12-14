-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Dec 14, 2025 at 01:36 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kopi_janti2`
--

-- --------------------------------------------------------

--
-- Table structure for table `diskon`
--

CREATE TABLE `diskon` (
  `id` int(11) NOT NULL,
  `kode` varchar(50) NOT NULL COMMENT 'Kode promo unik (huruf besar disarankan)',
  `nilai` int(11) NOT NULL COMMENT 'Nilai diskon dalam rupiah (contoh: 10000 untuk Rp10.000)',
  `jenis_diskon` enum('nominal','persen') DEFAULT 'nominal' COMMENT 'Jenis diskon: nominal atau persen',
  `min_transaksi` int(11) NOT NULL DEFAULT 0 COMMENT 'Minimum subtotal transaksi agar diskon berlaku',
  `maksimal_penggunaan` int(11) DEFAULT NULL COMMENT 'Batas maksimal penggunaan (NULL = tak terbatas)',
  `sudah_dipakai` int(11) DEFAULT 0 COMMENT 'Jumlah sudah digunakan',
  `tanggal_mulai` date DEFAULT NULL COMMENT 'Tanggal mulai berlaku (NULL = langsung aktif)',
  `tanggal_akhir` date DEFAULT NULL COMMENT 'Tanggal akhir berlaku (NULL = selamanya)',
  `aktif` tinyint(1) DEFAULT 1 COMMENT '1 = aktif, 0 = nonaktif',
  `keterangan` text DEFAULT NULL COMMENT 'Catatan tambahan',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `diskon`
--

INSERT INTO `diskon` (`id`, `kode`, `nilai`, `jenis_diskon`, `min_transaksi`, `maksimal_penggunaan`, `sudah_dipakai`, `tanggal_mulai`, `tanggal_akhir`, `aktif`, `keterangan`, `created_at`, `updated_at`) VALUES
(1, 'KOPI10', 10000, 'nominal', 50000, NULL, 0, '2025-12-01', '2025-12-31', 1, 'Diskon Rp10.000 untuk pembelian minimal Rp50.000', '2025-12-13 12:01:40', '2025-12-13 12:01:40'),
(2, 'FREEDRINK', 20000, 'nominal', 100000, NULL, 0, NULL, NULL, 1, 'Diskon Rp20.000 (gratis minuman) untuk pembelian minimal Rp100.000', '2025-12-13 12:01:40', '2025-12-13 12:01:40'),
(3, 'WELCOME20', 20000, 'nominal', 75000, NULL, 0, '2025-12-13', '2026-02-28', 1, 'Diskon spesial pelanggan baru Rp20.000', '2025-12-13 12:01:40', '2025-12-13 12:01:40'),
(4, 'TEHGRATIS', 15000, 'nominal', 60000, NULL, 0, NULL, '2025-12-31', 1, 'Diskon teh Rp15.000', '2025-12-13 12:01:40', '2025-12-13 12:01:40'),
(5, 'HAPPYHOUR', 25000, 'nominal', 120000, NULL, 0, NULL, NULL, 1, 'Happy Hour: Diskon Rp25.000 setiap hari', '2025-12-13 12:01:40', '2025-12-13 12:01:40'),
(6, 'NEWYEAR', 30000, 'nominal', 150000, NULL, 0, '2025-12-30', '2026-01-05', 1, 'Diskon spesial Tahun Baru Rp30.000', '2025-12-13 12:01:40', '2025-12-13 12:01:40');

-- --------------------------------------------------------

--
-- Table structure for table `kategori_menu`
--

CREATE TABLE `kategori_menu` (
  `id` int(11) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `urutan` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kategori_menu`
--

INSERT INTO `kategori_menu` (`id`, `nama_kategori`, `urutan`) VALUES
(1, 'Minuman', 1),
(2, 'Makanan', 2);

-- --------------------------------------------------------

--
-- Table structure for table `meja`
--

CREATE TABLE `meja` (
  `id` int(11) NOT NULL,
  `nomor_meja` varchar(20) NOT NULL,
  `kapasitas` int(11) DEFAULT 4,
  `aktif` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `meja`
--

INSERT INTO `meja` (`id`, `nomor_meja`, `kapasitas`, `aktif`) VALUES
(1, 'Meja 01', 4, 1),
(2, 'Meja 7', 4, 1);

-- --------------------------------------------------------

--
-- Table structure for table `menu`
--

CREATE TABLE `menu` (
  `id` int(11) NOT NULL,
  `nama_menu` varchar(150) NOT NULL,
  `harga` decimal(12,2) NOT NULL,
  `kategori_id` int(11) NOT NULL,
  `stok` int(11) DEFAULT 20,
  `gambar` varchar(255) DEFAULT NULL,
  `aktif` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menu`
--

INSERT INTO `menu` (`id`, `nama_menu`, `harga`, `kategori_id`, `stok`, `gambar`, `aktif`) VALUES
(9, 'Avocado', 20000.00, 1, 61, 'https://raw.githubusercontent.com/dellapuji87/menukopijanti/main/Minuman/avocado.png', 1),
(10, 'Cappucina', 25000.00, 1, 20, 'https://raw.githubusercontent.com/dellapuji87/menukopijanti/main/Minuman/cappucina.png', 1),
(11, 'Caramel Latte', 25000.00, 1, 20, 'https://raw.githubusercontent.com/dellapuji87/menukopijanti/main/Minuman/caramel%20latte.png', 1),
(12, 'Cocoa', 20000.00, 1, 20, 'https://raw.githubusercontent.com/dellapuji87/menukopijanti/main/Minuman/cocoa.png', 1),
(13, 'Dalgona', 20000.00, 1, 20, 'https://raw.githubusercontent.com/dellapuji87/menukopijanti/main/Minuman/dalgona.png', 1),
(14, 'Espreso', 20000.00, 1, 20, 'https://raw.githubusercontent.com/dellapuji87/menukopijanti/main/Minuman/espreso.png', 1),
(15, 'Esteh', 20000.00, 1, 20, 'https://raw.githubusercontent.com/dellapuji87/menukopijanti/main/Minuman/esteh.png', 1),
(16, 'Latte', 25000.00, 1, 20, 'https://raw.githubusercontent.com/dellapuji87/menukopijanti/main/Minuman/latte.png', 1),
(17, 'Lime', 20000.00, 1, 20, 'https://raw.githubusercontent.com/dellapuji87/menukopijanti/main/Minuman/lime.png', 1),
(18, 'Matcha', 20000.00, 1, 20, 'https://raw.githubusercontent.com/dellapuji87/menukopijanti/main/Minuman/matcha.png', 1),
(19, 'Orenji', 20000.00, 1, 20, 'https://raw.githubusercontent.com/dellapuji87/menukopijanti/main/Minuman/orenji.png', 1),
(20, 'Stoberi', 20000.00, 1, 20, 'https://raw.githubusercontent.com/dellapuji87/menukopijanti/main/Minuman/stoberi.png', 1),
(21, 'Taitea', 20000.00, 1, 20, 'https://raw.githubusercontent.com/dellapuji87/menukopijanti/main/Minuman/taitea.png', 1),
(22, 'Cheese Cake', 25000.00, 2, 20, 'https://raw.githubusercontent.com/dellapuji87/menukopijanti/main/makanan/cheesecake.png', 1),
(23, 'Curos', 20000.00, 2, 20, 'https://raw.githubusercontent.com/dellapuji87/menukopijanti/main/makanan/curos.png', 1),
(24, 'Donat', 20000.00, 2, 20, 'https://raw.githubusercontent.com/dellapuji87/menukopijanti/main/makanan/donat.png', 1),
(25, 'Kentang', 20000.00, 2, 20, 'https://raw.githubusercontent.com/dellapuji87/menukopijanti/main/makanan/kentang.png', 1),
(26, 'Kosang', 20000.00, 2, 20, 'https://raw.githubusercontent.com/dellapuji87/menukopijanti/main/makanan/kosang.png', 1),
(27, 'Kroket', 20000.00, 2, 20, 'https://raw.githubusercontent.com/dellapuji87/menukopijanti/main/makanan/kroket.png', 1);

-- --------------------------------------------------------

--
-- Table structure for table `menu_varian`
--

CREATE TABLE `menu_varian` (
  `id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `ukuran_id` int(11) NOT NULL,
  `harga` decimal(12,2) NOT NULL,
  `stok` int(11) DEFAULT 20,
  `aktif` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menu_varian`
--

INSERT INTO `menu_varian` (`id`, `menu_id`, `ukuran_id`, `harga`, `stok`, `aktif`) VALUES
(1, 11, 3, 30000.00, 20, 1),
(2, 9, 1, 20000.00, 21, 1),
(3, 9, 2, 20000.00, 20, 1),
(4, 9, 3, 20000.00, 20, 1),
(5, 10, 1, 25000.00, 20, 1),
(6, 10, 2, 25000.00, 20, 1),
(7, 10, 3, 25000.00, 20, 1),
(8, 11, 1, 25000.00, 20, 1),
(9, 11, 2, 25000.00, 20, 1),
(10, 12, 1, 20000.00, 20, 1),
(11, 12, 2, 20000.00, 20, 1),
(12, 12, 3, 20000.00, 20, 1),
(13, 13, 1, 20000.00, 20, 1),
(14, 13, 2, 20000.00, 20, 1),
(15, 13, 3, 20000.00, 20, 1),
(16, 14, 1, 20000.00, 20, 1),
(17, 14, 2, 20000.00, 20, 1),
(18, 14, 3, 20000.00, 20, 1),
(19, 15, 1, 20000.00, 20, 1),
(20, 15, 2, 20000.00, 20, 1),
(21, 15, 3, 20000.00, 20, 1),
(22, 16, 1, 25000.00, 20, 1),
(23, 16, 2, 25000.00, 20, 1),
(24, 16, 3, 25000.00, 20, 1),
(25, 17, 1, 20000.00, 20, 1),
(26, 17, 2, 20000.00, 20, 1),
(27, 17, 3, 20000.00, 20, 1),
(28, 18, 1, 20000.00, 20, 1),
(29, 18, 2, 20000.00, 20, 1),
(30, 18, 3, 20000.00, 20, 1),
(31, 19, 1, 20000.00, 20, 1),
(32, 19, 2, 20000.00, 20, 1),
(33, 19, 3, 20000.00, 20, 1),
(34, 20, 1, 20000.00, 20, 1),
(35, 20, 2, 20000.00, 20, 1),
(36, 20, 3, 20000.00, 20, 1),
(37, 21, 1, 20000.00, 20, 1),
(38, 21, 2, 20000.00, 20, 1),
(39, 21, 3, 20000.00, 20, 1);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `nomor_struk` varchar(20) NOT NULL,
  `tanggal_order` datetime NOT NULL DEFAULT current_timestamp(),
  `nama_pelanggan` varchar(150) DEFAULT NULL,
  `tipe_order` enum('Dine In','Take Away') NOT NULL,
  `meja_id` int(11) DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `diskon` decimal(12,2) DEFAULT 0.00,
  `pajak` decimal(12,2) DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL,
  `status_id` int(11) NOT NULL DEFAULT 1,
  `kasir_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `nomor_struk`, `tanggal_order`, `nama_pelanggan`, `tipe_order`, `meja_id`, `subtotal`, `diskon`, `pajak`, `total`, `status_id`, `kasir_id`, `created_at`, `updated_at`) VALUES
(1, '001', '2025-12-14 16:58:44', 'Yangyang', 'Dine In', 1, 30000.00, 0.00, 0.00, 30000.00, 4, 1, '2025-12-14 09:58:44', '2025-12-14 12:20:35');

-- --------------------------------------------------------

--
-- Table structure for table `order_details`
--

CREATE TABLE `order_details` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `harga_satuan` decimal(12,2) NOT NULL,
  `catatan` text DEFAULT NULL,
  `subtotal_item` decimal(12,2) GENERATED ALWAYS AS (`jumlah` * `harga_satuan`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_details`
--

INSERT INTO `order_details` (`id`, `order_id`, `menu_id`, `jumlah`, `harga_satuan`, `catatan`) VALUES
(1, 1, 11, 1, 30000.00, 'Less Sugar');

-- --------------------------------------------------------

--
-- Table structure for table `pembayaran`
--

CREATE TABLE `pembayaran` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `metode_pembayaran` enum('Tunai','QRIS','Transfer','Kartu') NOT NULL,
  `jumlah_dibayar` decimal(12,2) DEFAULT NULL,
  `kembalian` decimal(12,2) DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `diskon_nominal` decimal(12,2) DEFAULT 0.00,
  `pajak_nominal` decimal(12,2) DEFAULT 0.00,
  `total_akhir` decimal(12,2) NOT NULL,
  `kode_diskon` varchar(50) DEFAULT NULL,
  `status_pembayaran` enum('Lunas','Belum Lunas','Gagal') DEFAULT 'Lunas',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nama_role` varchar(50) NOT NULL,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `nama_role`, `keterangan`) VALUES
(1, 'kasir', NULL),
(2, 'dapur', NULL),
(3, 'pemilik', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `status_order`
--

CREATE TABLE `status_order` (
  `id` int(11) NOT NULL,
  `nama_status` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `status_order`
--

INSERT INTO `status_order` (`id`, `nama_status`) VALUES
(1, 'Baru'),
(2, 'Dimasak'),
(4, 'Selesai'),
(3, 'Siap dihidangkan');

-- --------------------------------------------------------

--
-- Table structure for table `ukuran`
--

CREATE TABLE `ukuran` (
  `id` int(11) NOT NULL,
  `nama_ukuran` varchar(20) NOT NULL,
  `keterangan` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ukuran`
--

INSERT INTO `ukuran` (`id`, `nama_ukuran`, `keterangan`) VALUES
(1, 'S', 'Small'),
(2, 'M', 'Medium'),
(3, 'L', 'Large');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(150) NOT NULL,
  `role_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `role_id`, `created_at`) VALUES
(1, 'Woodz', 'kasieone', 'Woodsyahda', 1, '2025-12-14 09:57:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `diskon`
--
ALTER TABLE `diskon`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode` (`kode`),
  ADD KEY `idx_kode_aktif` (`kode`,`aktif`);

--
-- Indexes for table `kategori_menu`
--
ALTER TABLE `kategori_menu`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `meja`
--
ALTER TABLE `meja`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nomor_meja` (`nomor_meja`);

--
-- Indexes for table `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kategori_id` (`kategori_id`);

--
-- Indexes for table `menu_varian`
--
ALTER TABLE `menu_varian`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_menu_ukuran` (`menu_id`,`ukuran_id`),
  ADD KEY `ukuran_id` (`ukuran_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nomor_struk` (`nomor_struk`),
  ADD KEY `meja_id` (`meja_id`),
  ADD KEY `status_id` (`status_id`),
  ADD KEY `kasir_id` (`kasir_id`);

--
-- Indexes for table `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `menu_id` (`menu_id`);

--
-- Indexes for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`),
  ADD KEY `kode_diskon` (`kode_diskon`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_role` (`nama_role`);

--
-- Indexes for table `status_order`
--
ALTER TABLE `status_order`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_status` (`nama_status`);

--
-- Indexes for table `ukuran`
--
ALTER TABLE `ukuran`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `diskon`
--
ALTER TABLE `diskon`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `kategori_menu`
--
ALTER TABLE `kategori_menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `meja`
--
ALTER TABLE `meja`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `menu`
--
ALTER TABLE `menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `menu_varian`
--
ALTER TABLE `menu_varian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pembayaran`
--
ALTER TABLE `pembayaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `status_order`
--
ALTER TABLE `status_order`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ukuran`
--
ALTER TABLE `ukuran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `menu`
--
ALTER TABLE `menu`
  ADD CONSTRAINT `menu_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori_menu` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `menu_varian`
--
ALTER TABLE `menu_varian`
  ADD CONSTRAINT `menu_varian_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `menu_varian_ibfk_2` FOREIGN KEY (`ukuran_id`) REFERENCES `ukuran` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`meja_id`) REFERENCES `meja` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`status_id`) REFERENCES `status_order` (`id`),
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`kasir_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_details_ibfk_2` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`);

--
-- Constraints for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD CONSTRAINT `pembayaran_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pembayaran_ibfk_2` FOREIGN KEY (`kode_diskon`) REFERENCES `diskon` (`kode`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
