-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 15, 2026 at 03:24 AM
-- Server version: 8.0.30
-- PHP Version: 8.2.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mrpepsi_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendances`
--

CREATE TABLE `attendances` (
  `id` int NOT NULL,
  `schedule_id` int NOT NULL,
  `attendance_date` date NOT NULL,
  `main_tutor_id` int NOT NULL,
  `status` enum('present','absent','substituted') NOT NULL DEFAULT 'present',
  `substituted_tutor_id` int DEFAULT NULL,
  `actual_tutor_id` int NOT NULL,
  `session_fee` decimal(12,2) NOT NULL DEFAULT '0.00',
  `notes` varchar(255) DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendances`
--

INSERT INTO `attendances` (`id`, `schedule_id`, `attendance_date`, `main_tutor_id`, `status`, `substituted_tutor_id`, `actual_tutor_id`, `session_fee`, `notes`, `created_by`, `created_at`) VALUES
(6, 15, '2026-10-12', 6, 'present', NULL, 6, '40000.00', '', 1, '2026-10-12 13:40:24'),
(7, 16, '2026-10-12', 1, 'present', NULL, 1, '40000.00', '', 1, '2026-10-12 13:40:31'),
(8, 17, '2026-10-12', 4, 'present', NULL, 4, '40000.00', '', 1, '2026-10-12 13:40:37'),
(9, 18, '2026-10-12', 5, 'present', NULL, 5, '40000.00', '', 1, '2026-10-12 13:40:46'),
(10, 19, '2026-10-12', 7, 'present', NULL, 7, '40000.00', '', 1, '2026-10-12 13:40:51'),
(11, 20, '2026-10-12', 2, 'present', NULL, 2, '40000.00', '', 1, '2026-10-12 13:40:59'),
(12, 21, '2026-10-12', 6, 'present', NULL, 6, '40000.00', '', 1, '2026-10-12 13:41:04'),
(13, 22, '2026-10-12', 1, 'present', NULL, 1, '40000.00', '', 1, '2026-10-12 13:41:11'),
(14, 23, '2026-10-12', 8, 'present', NULL, 8, '40000.00', '', 1, '2026-10-12 13:41:18'),
(15, 38, '2026-10-12', 2, 'present', NULL, 2, '40000.00', '', 1, '2026-10-12 13:41:24'),
(16, 37, '2026-10-12', 9, 'present', NULL, 9, '40000.00', '', 1, '2026-10-12 13:41:31'),
(17, 24, '2026-10-12', 9, 'present', NULL, 9, '40000.00', '', 1, '2026-10-12 13:41:37'),
(18, 25, '2026-10-12', 7, 'present', NULL, 7, '40000.00', '', 1, '2026-10-12 13:41:42'),
(19, 26, '2026-10-12', 3, 'present', NULL, 3, '40000.00', '', 1, '2026-10-12 13:41:48'),
(20, 27, '2026-10-12', 5, 'present', NULL, 5, '40000.00', '', 1, '2026-10-12 13:41:54'),
(21, 28, '2026-10-12', 1, 'present', NULL, 1, '40000.00', '', 1, '2026-10-12 13:42:01'),
(22, 29, '2026-10-12', 8, 'present', NULL, 8, '40000.00', '', 1, '2026-10-12 13:42:06'),
(23, 30, '2026-10-12', 3, 'present', NULL, 3, '40000.00', '', 1, '2026-10-12 13:42:11'),
(24, 31, '2026-10-12', 1, 'present', NULL, 1, '40000.00', '', 1, '2026-10-12 13:42:17'),
(25, 32, '2026-10-12', 7, 'present', NULL, 7, '40000.00', '', 1, '2026-10-12 13:42:23'),
(26, 33, '2026-10-12', 3, 'present', NULL, 3, '40000.00', '', 1, '2026-10-12 13:42:29'),
(27, 34, '2026-10-12', 2, 'present', NULL, 2, '40000.00', '', 1, '2026-10-12 13:42:34'),
(28, 35, '2026-10-12', 7, 'present', NULL, 7, '40000.00', '', 1, '2026-10-12 13:42:43'),
(29, 36, '2026-10-12', 3, 'present', NULL, 3, '40000.00', '', 1, '2026-10-12 13:43:27');

-- --------------------------------------------------------

--
-- Table structure for table `canteen_categories`
--

CREATE TABLE `canteen_categories` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `canteen_categories`
--

INSERT INTO `canteen_categories` (`id`, `name`) VALUES
(7, 'Alat Tulis'),
(3, 'Camilan / Snack'),
(5, 'Dessert/ Pudding'),
(8, 'Makanan: Mie instan / Nasi'),
(6, 'Marchendise: Kaos/Totte Bag/ Tumbler/Topi'),
(1, 'Minuman');

-- --------------------------------------------------------

--
-- Table structure for table `canteen_products`
--

CREATE TABLE `canteen_products` (
  `id` int NOT NULL,
  `category_id` int DEFAULT NULL,
  `barcode` varchar(50) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `cost_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `selling_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `stock` int NOT NULL DEFAULT '0',
  `min_stock` int NOT NULL DEFAULT '5',
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `canteen_products`
--

INSERT INTO `canteen_products` (`id`, `category_id`, `barcode`, `name`, `cost_price`, `selling_price`, `stock`, `min_stock`, `image`, `is_active`, `created_at`) VALUES
(1, 1, '8996001600269', 'Air Mineral Le Minerale 600ml', '2500.00', '4000.00', 46, 5, 'uploads/products/prod-20261009-1dd46c3c.jpg', 1, '2026-10-08 10:21:32'),
(2, 1, '8999902', 'Teh Pucuk Harum 350ml', '2500.00', '4000.00', 40, 5, 'uploads/products/prod-20261009-a44df409.jpg', 1, '2026-10-08 10:21:32'),
(3, 1, '8999903', 'Kopi Americano / Cold Brew', '4000.00', '8000.00', 29, 5, 'uploads/products/prod-20261009-85893bde.jpg', 1, '2026-10-08 10:21:32'),
(4, 1, '8999904', 'Matcha Latte Ice', '5500.00', '10000.00', 24, 5, 'uploads/products/prod-20261009-6c933c2a.jpg', 1, '2026-10-08 10:21:32'),
(6, NULL, '8999906', 'Nasi Goreng Spesial Pare', '9000.00', '15000.00', 15, 5, 'uploads/products/prod-20261009-b9bf8321.jpg', 1, '2026-10-08 10:21:32'),
(7, NULL, '8999907', 'Mie Nyemek Telur', '6000.00', '11000.00', 25, 5, 'uploads/products/prod-20261009-05de45cd.jpg', 1, '2026-10-08 10:21:32'),
(8, 3, '8999908', 'Keripik Singkong Balado', '3000.00', '5000.00', 45, 5, 'uploads/products/prod-20261009-9b1c02fe.jpg', 0, '2026-10-08 10:21:32'),
(9, 3, '8999909', 'Basreng Pedas Gurih', '3500.00', '6000.00', 33, 5, 'uploads/products/prod-20261009-3af5ce48.jpg', 1, '2026-10-08 10:21:32'),
(10, NULL, '8999910', 'Notebook Mr. Pepsi + Pen', '7000.00', '12000.00', 29, 5, 'uploads/products/prod-20261009-c708829a.jpg', 1, '2026-10-08 10:21:32'),
(11, 3, '8999811', 'Cheesecuit', '5500.00', '6500.00', 10, 2, 'uploads/products/prod-20261009-4b7f0edc.jpg', 1, '2026-10-09 00:41:47'),
(13, 1, '8994096112605', 'kopi point', '30000.00', '35000.00', 16, 5, 'uploads/products/prod-20261012-17d97729.jpg', 1, '2026-10-12 04:38:15');

-- --------------------------------------------------------

--
-- Table structure for table `canteen_stock_batches`
--

CREATE TABLE `canteen_stock_batches` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `unit_cost` decimal(12,2) NOT NULL DEFAULT '0.00',
  `qty_in` int NOT NULL DEFAULT '0',
  `qty_remaining` int NOT NULL DEFAULT '0',
  `source` enum('initial','restock','opname') NOT NULL DEFAULT 'restock',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `canteen_stock_batches`
--

INSERT INTO `canteen_stock_batches` (`id`, `product_id`, `unit_cost`, `qty_in`, `qty_remaining`, `source`, `notes`, `created_at`) VALUES
(1, 1, '2500.00', 46, 46, 'initial', 'Saldo awal impor', '2026-10-13 11:52:01'),
(2, 2, '2500.00', 40, 40, 'initial', 'Saldo awal impor', '2026-10-13 11:52:01'),
(3, 3, '4000.00', 29, 29, 'initial', 'Saldo awal impor', '2026-10-13 11:52:01'),
(4, 4, '5500.00', 24, 24, 'initial', 'Saldo awal impor', '2026-10-13 11:52:01'),
(5, 6, '9000.00', 18, 15, 'initial', 'Saldo awal impor', '2026-10-13 11:52:01'),
(6, 7, '6000.00', 25, 25, 'initial', 'Saldo awal impor', '2026-10-13 11:52:01'),
(7, 8, '3000.00', 45, 45, 'initial', 'Saldo awal impor', '2026-10-13 11:52:01'),
(8, 9, '3500.00', 33, 33, 'initial', 'Saldo awal impor', '2026-10-13 11:52:01'),
(9, 10, '7000.00', 29, 29, 'initial', 'Saldo awal impor', '2026-10-13 11:52:01'),
(10, 11, '5500.00', 10, 10, 'initial', 'Saldo awal impor', '2026-10-13 11:52:01'),
(11, 13, '30000.00', 16, 16, 'initial', 'Saldo awal impor', '2026-10-13 11:52:01');

-- --------------------------------------------------------

--
-- Table structure for table `canteen_stock_logs`
--

CREATE TABLE `canteen_stock_logs` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `type` enum('in','out','opname_adjustment','sale') NOT NULL,
  `quantity` int NOT NULL,
  `old_stock` int NOT NULL,
  `new_stock` int NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `canteen_stock_logs`
--

INSERT INTO `canteen_stock_logs` (`id`, `product_id`, `type`, `quantity`, `old_stock`, `new_stock`, `notes`, `created_at`) VALUES
(1, 1, 'sale', 2, 50, 48, 'Penjualan POS No. INV-20261008-0001', '2026-10-08 10:41:55'),
(2, 1, 'sale', 1, 48, 47, 'Penjualan POS No. INV-20261008-0002', '2026-10-08 08:45:24'),
(3, 9, 'sale', 1, 35, 34, 'Penjualan POS No. INV-20261008-0002', '2026-10-08 08:45:24'),
(4, 10, 'sale', 1, 30, 29, 'Penjualan POS No. INV-20261008-0002', '2026-10-08 08:45:24'),
(5, 4, 'sale', 1, 25, 24, 'Penjualan POS No. INV-20261008-0003', '2026-10-08 08:46:00'),
(6, 3, 'sale', 1, 30, 29, 'Penjualan POS No. INV-20261008-0003', '2026-10-08 08:46:00'),
(7, 11, 'in', 10, 0, 10, 'Stok awal produk baru', '2026-10-09 00:41:47'),
(8, 1, 'sale', 1, 47, 46, 'Penjualan POS No. INV-20261009-0001', '2026-10-09 01:13:54'),
(9, 9, 'sale', 1, 34, 33, 'Penjualan POS No. INV-20261009-0001', '2026-10-09 01:13:54'),
(11, 13, 'in', 20, 0, 20, 'Stok awal produk baru', '2026-10-12 04:38:15'),
(12, 13, 'sale', 2, 20, 18, 'Penjualan POS No. INV-20261012-0001', '2026-10-12 04:39:44'),
(13, 13, 'sale', 2, 18, 16, 'Penjualan POS No. INV-20261013-0001', '2026-10-13 02:16:31'),
(14, 6, 'sale', 3, 18, 15, 'Penjualan POS No. INV-20261014-0001', '2026-10-14 12:45:05');

-- --------------------------------------------------------

--
-- Table structure for table `cashier_shifts`
--

CREATE TABLE `cashier_shifts` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `start_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `end_time` datetime DEFAULT NULL,
  `starting_cash` decimal(12,2) NOT NULL DEFAULT '0.00',
  `expected_cash` decimal(12,2) NOT NULL DEFAULT '0.00',
  `actual_cash` decimal(12,2) NOT NULL DEFAULT '0.00',
  `cash_difference` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_cash` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_qris` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_sales` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `notes` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cashier_shifts`
--

INSERT INTO `cashier_shifts` (`id`, `user_id`, `start_time`, `end_time`, `starting_cash`, `expected_cash`, `actual_cash`, `cash_difference`, `total_cash`, `total_qris`, `total_sales`, `status`, `notes`) VALUES
(1, 1, '2026-10-08 15:47:18', NULL, '100000.00', '100000.00', '0.00', '0.00', '0.00', '0.00', '0.00', 'open', '');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int NOT NULL,
  `expense_date` date NOT NULL,
  `category_id` int DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `payment_method` enum('tunai','qris','transfer') NOT NULL DEFAULT 'tunai',
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expense_categories`
--

CREATE TABLE `expense_categories` (
  `id` int NOT NULL,
  `name` varchar(80) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expense_categories`
--

INSERT INTO `expense_categories` (`id`, `name`, `description`, `is_active`, `created_at`) VALUES
(1, 'Listrik & Air', 'Tagihan listrik dan air', 1, '2026-10-15 10:08:27'),
(2, 'Internet & Telepon', 'WiFi, pulsa, dan komunikasi', 1, '2026-10-15 10:08:27'),
(3, 'Gaji & Honor', 'Gaji staff, honor tutor, dan upah', 1, '2026-10-15 10:08:27'),
(4, 'Sewa Tempat', 'Sewa gedung, ruangan, atau kos', 1, '2026-10-15 10:08:27'),
(5, 'ATK & Operasional', 'Alat tulis, cetak, dan kebutuhan kantor', 1, '2026-10-15 10:08:27'),
(6, 'Transportasi', 'BBM, parkir, dan perjalanan', 1, '2026-10-15 10:08:27'),
(7, 'Perawatan & Kebersihan', 'Servis, perbaikan, dan kebersihan', 1, '2026-10-15 10:08:27'),
(8, 'Lain-lain', 'Pengeluaran operasional lainnya', 1, '2026-10-15 10:08:27');

-- --------------------------------------------------------

--
-- Table structure for table `periods`
--

CREATE TABLE `periods` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `period_day` int NOT NULL DEFAULT '10',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `periods`
--

INSERT INTO `periods` (`id`, `name`, `period_day`, `start_date`, `end_date`, `is_active`, `created_at`) VALUES
(1, 'Periode 10 Oktober 2026', 10, '2026-10-12', '2026-10-23', 1, '2026-10-08 10:21:32'),
(2, 'Periode 25 Oktober 2026', 25, '2026-10-26', '2026-11-06', 0, '2026-10-08 10:21:32'),
(5, 'Periode 10 November 2026', 10, '2026-11-10', '2026-11-23', 0, '2026-10-08 11:37:09'),
(6, 'Periode 25 November 2026', 25, '2026-11-25', '2026-12-08', 0, '2026-10-08 11:37:09'),
(7, 'Periode 10 Desember 2026', 10, '2026-12-10', '2026-12-23', 0, '2026-10-08 11:37:09'),
(8, 'Periode 25 Desember 2026', 25, '2026-12-25', '2027-01-07', 0, '2026-10-08 11:37:09'),
(9, 'Periode 10 Januari 2027', 10, '2027-01-11', '2027-01-22', 0, '2026-10-08 11:37:09'),
(20, 'Periode 25 September 2026', 25, '2026-09-25', '2026-10-08', 0, '2026-10-12 11:56:22'),
(26, 'Periode 10 September 2026', 10, '2026-09-10', '2026-09-23', 0, '2026-10-12 11:57:02'),
(30, 'Periode 10 Oktober 2026', 10, '2026-10-10', '2026-10-23', 0, '2026-10-12 12:04:50'),
(37, 'Periode 25 Januari 2027', 25, '2027-01-25', '2027-02-07', 0, '2026-10-12 12:06:06');

-- --------------------------------------------------------

--
-- Table structure for table `pos_transactions`
--

CREATE TABLE `pos_transactions` (
  `id` int NOT NULL,
  `shift_id` int DEFAULT NULL,
  `invoice_no` varchar(50) NOT NULL,
  `user_id` int NOT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_cost` decimal(12,2) NOT NULL DEFAULT '0.00',
  `gross_profit` decimal(12,2) NOT NULL DEFAULT '0.00',
  `payment_method` enum('cash','qris') NOT NULL DEFAULT 'cash',
  `cash_paid` decimal(12,2) NOT NULL DEFAULT '0.00',
  `change_returned` decimal(12,2) NOT NULL DEFAULT '0.00',
  `customer_name` varchar(100) NOT NULL DEFAULT 'Umum / Siswa',
  `is_manual` tinyint(1) NOT NULL DEFAULT '0',
  `manual_note` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pos_transactions`
--

INSERT INTO `pos_transactions` (`id`, `shift_id`, `invoice_no`, `user_id`, `total_amount`, `total_cost`, `gross_profit`, `payment_method`, `cash_paid`, `change_returned`, `customer_name`, `is_manual`, `manual_note`, `created_at`) VALUES
(1, NULL, 'INV-20261008-0001', 1, '8000.00', '5000.00', '3000.00', 'cash', '10000.00', '2000.00', 'Budi Santoso (Test Siswa)', 0, NULL, '2026-10-08 10:41:55'),
(2, NULL, 'INV-20261008-0002', 1, '22000.00', '13000.00', '9000.00', 'cash', '50000.00', '28000.00', 'Umum / Siswa', 0, NULL, '2026-10-08 08:45:24'),
(3, NULL, 'INV-20261008-0003', 1, '18000.00', '9500.00', '8500.00', 'qris', '18000.00', '0.00', 'Umum / Siswa', 0, NULL, '2026-10-08 08:46:00'),
(4, 1, 'INV-20261009-0001', 1, '10000.00', '6000.00', '4000.00', 'cash', '10000.00', '0.00', 'Umum / Siswa', 0, NULL, '2026-10-09 01:13:54'),
(5, 1, 'INV-20261012-0001', 1, '70000.00', '60000.00', '10000.00', 'cash', '70000.00', '0.00', 'Umum / Siswa', 0, NULL, '2026-10-12 04:39:44'),
(6, 1, 'INV-20261013-0001', 1, '70000.00', '60000.00', '10000.00', 'cash', '70000.00', '0.00', 'Umum / Siswa', 0, NULL, '2026-10-13 02:16:31'),
(7, NULL, 'INV-20261014-0001', 6, '45000.00', '27000.00', '18000.00', 'cash', '50000.00', '5000.00', 'Umum / Siswa', 0, NULL, '2026-10-14 12:45:05');

-- --------------------------------------------------------

--
-- Table structure for table `pos_transaction_items`
--

CREATE TABLE `pos_transaction_items` (
  `id` int NOT NULL,
  `transaction_id` int NOT NULL,
  `product_id` int DEFAULT NULL,
  `product_name` varchar(150) NOT NULL,
  `cost_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `selling_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `quantity` int NOT NULL DEFAULT '1',
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `subtotal_cost` decimal(12,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pos_transaction_items`
--

INSERT INTO `pos_transaction_items` (`id`, `transaction_id`, `product_id`, `product_name`, `cost_price`, `selling_price`, `quantity`, `subtotal`, `subtotal_cost`) VALUES
(1, 1, 1, 'Air Mineral Le Minerale 600ml', '2500.00', '4000.00', 2, '8000.00', '5000.00'),
(2, 2, 1, 'Air Mineral Le Minerale 600ml', '2500.00', '4000.00', 1, '4000.00', '2500.00'),
(3, 2, 9, 'Basreng Pedas Gurih', '3500.00', '6000.00', 1, '6000.00', '3500.00'),
(4, 2, 10, 'Notebook Mr. Pepsi + Pen', '7000.00', '12000.00', 1, '12000.00', '7000.00'),
(5, 3, 4, 'Matcha Latte Ice', '5500.00', '10000.00', 1, '10000.00', '5500.00'),
(6, 3, 3, 'Kopi Americano / Cold Brew', '4000.00', '8000.00', 1, '8000.00', '4000.00'),
(7, 4, 1, 'Air Mineral Le Minerale 600ml', '2500.00', '4000.00', 1, '4000.00', '2500.00'),
(8, 4, 9, 'Basreng Pedas Gurih', '3500.00', '6000.00', 1, '6000.00', '3500.00'),
(9, 5, 13, 'kopi point', '30000.00', '35000.00', 2, '70000.00', '60000.00'),
(10, 6, 13, 'kopi point', '30000.00', '35000.00', 2, '70000.00', '60000.00'),
(11, 7, 6, 'Nasi Goreng Spesial Pare', '9000.00', '15000.00', 3, '45000.00', '27000.00');

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` int NOT NULL,
  `code` varchar(30) NOT NULL,
  `title` varchar(150) NOT NULL,
  `category_id` int DEFAULT NULL,
  `level_category` varchar(50) NOT NULL DEFAULT 'basic',
  `orientation` enum('speaking','ielts') NOT NULL DEFAULT 'speaking',
  `level_label` varchar(50) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `english_ratio` varchar(20) DEFAULT NULL,
  `description` text NOT NULL,
  `syllabus` text NOT NULL,
  `final_exam` varchar(255) NOT NULL,
  `is_new` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`id`, `code`, `title`, `category_id`, `level_category`, `orientation`, `level_label`, `price`, `english_ratio`, `description`, `syllabus`, `final_exam`, `is_new`, `is_active`, `created_at`) VALUES
(16, 'LT1', 'LET\'S TALK 1', 1, 'basic', 'speaking', 'Basic Level', '150000.00', '30%', 'Focus: Start to speak (Idea), and Simple Sentences Arrangement', '[\"DAY 1: What\\u2019s your name? Can you spell your name? One, Two, Three..., Coloring,\",\"DAY 2: Good Morning, How are you doing? Introduction? What time is it?\",\"DAY 3: Can you show me the way to ....?\",\"DAY 4: Tell me your routines, please!\",\"DAY 5: Reading Passage:My dream job,\",\"DAY 6: 5w+1h question,\",\"DAY 7: My favourite song: Count on me,\",\"DAY 8: Describing Picture,\",\"DAY 9: IF Speech in 2 minutes\",\"DAY 10: FINAL EXAMINATION\"]', 'Face to Face Topics – QUESTION & VERB CARD', 0, 1, '2026-10-12 12:19:43'),
(17, 'ST', 'STARTER', 1, 'pre-intermediate', 'speaking', 'Pre-Intermediate Level', '150000.00', '50%', 'Focus: building confidents to speak, losing filler or stuck, and start to speak with some sentences', '[\"DAY 1: Getting Started with sentences,\",\"DAY 2: Introduction & Dreams,\",\"DAY 3: If I become an artist,\",\"DAY 4: Fun Game & Musics,\",\"DAY 5: Would you rather CARD,\",\"DAY 6: Simple Passage to discuss,\",\"DAY 7: Family Three Role Play,\",\"DAY 8: Country to go someday,\",\"DAY 9: One day in my life\",\"DAY 10: FINAL EXAMINATION\"]', 'Question And Answer CARD', 0, 1, '2026-10-12 12:37:25'),
(18, 'IP', 'IMPROMPTU', 1, 'intermediate', 'ielts', 'Intermediate Level', '160000.00', '50%', 'Focus: Ide Berbicara (Speak English Automatically without thinking), QnA Reaction of Answer IELTS PART 1', '[\"DAY 1: Trigger Card Daily Routines, (IELTS speaking PART 1)\",\"DAY 2: The necessity of grammar, (IELTS speaking PART 1)\",\"DAY 3: who\\u2019s my date, (IELTS speaking PART 1)\",\"DAY 4: Talking about hometown, (IELTS speaking PART 1)\",\"DAY 5: Special Food, (IELTS speaking PART 1)\",\"DAY 6: Friendship & Family, (IELTS speaking PART 1)\",\"DAY 7: The dangerous gadget for children, (IELTS speaking PART 1)\",\"DAY 8: who\\u2019s should get the money, (IELTS speaking PART 1)\",\"DAY 9: Television and Internet, (IELTS speaking PART 1)\",\"day 10: FINAL EXAMINATION\"]', 'PRESENTATION CARD (SUDDEN TOPICS)', 0, 1, '2026-10-12 12:42:02'),
(19, 'GFS1', 'GRAMMAR FOR SPEAKING 1', 1, 'intermediate', 'speaking', 'Intermediate Level', '150000.00', '50%', 'Focus: common 7 Tenses in conversation and speak English correctly in grammar', '[\"DAY 1: I do ... (Simple Present),\",\"DAY 2: I am doing ... (Present Continuous),\",\"DAY 3: Simple present VS Present Continuous,\",\"DAY 4: I did ... (Simple Past),\",\"DAY 5: I was doing ... (Past Continuous) *Konsep 2 Kejadian, I Had done ... (Past Perfect), Simple Past vs Present Perfect, I will do ... (Simple Future),\",\"DAY 6: Active and passive voice Forms,\",\"DAY 7: Question Sentences.\",\"DAY 8: analyze lyric and short story, Retelling.\",\"DAY 9: Online Quizizz Exercise. Flash card.\",\"DAY 10: FINAL EXAMINATION\"]', 'Online Test and Oral Grammar – REAL TIME CHECK', 0, 1, '2026-10-12 12:46:32'),
(20, 'LT2', 'LET\'S TALK 2', 1, 'intermediate', 'speaking', 'Intermediate Level', '150000.00', '50%', 'Focus: English Expressions, and Discussion or presentation with simple topics (Cross Culture Understanding)', '[\"DAY 1: Tell me about yourself! Then, invite me ...,\",\"DAY 2: Could you tell me the time, please?,\",\"DAY 3: Let\\u2019s tell a story!,\",\"DAY 4: What\\u2019s season look like?,\",\"DAY 5: What do you eat? (Discussion),\",\"DAY 6: Why are Finland\\u2019s school successful?\",\"DAY 7: Listening Musics (Grammatical Check: heal the world),\",\"DAY 8: Cross Culture Understanding,\",\"DAY 9: Tell about the factual events (Natural Disaster)\",\"DAY 10: FINAL EXAMINATION\"]', 'Interview review unit – TOPIC CARD', 0, 1, '2026-10-12 12:50:42'),
(21, 'SV', 'SWAP VOCAB', 1, 'intermediate', 'speaking', 'Intermediate Level', '150000.00', '50%', 'Focus: Passage to improve the vocab (No memorizing concept)', '[\"DAY 1: School Life,\",\"DAY 2: Home Life,\",\"DAY 3: Food,\",\"DAY 4: Travelling and Tourism,\",\"DAY 5: Every body\\u2019s Different,\",\"DAY 6: It\\u2019s how you say it,\",\"DAY 7: Synonym and Antonym,\",\"DAY 8: Survival English expressions,\",\"DAY 9: Root Words of English Vocabulary,\",\"DAY 10: FINAL EXAMINATION\"]', 'Story Telling – VOCAB CARD', 0, 1, '2026-10-12 12:55:03'),
(22, 'GFS2', 'GRAMMAR FOR SPEAKING 2', 1, 'pre-advanced', 'ielts', 'Pre-Advanced Level', '160000.00', '70%', 'Focus: 10 Mixed Tenses, complex sentences (Clauses), wide range grammatical form (Modal auxiliary, passive, Comparison, Conditional Sentences), and Passage to speak', '[\"DAY 1: Type of Sentences,\",\"DAY 2: Tenses in Speaking,\",\"DAY 3: Picture Practise (Simple Past & Past Continuous),\",\"DAY 4: Modal -Simplified,\",\"DAY 5: Relative Clauses,\",\"DAY 6: 3 Clauses (Adjective, Noun & Adverbial Clauses),\",\"DAY 7: Grammar Observation,\",\"DAY 8: All Grammar Theory\",\"DAY 9: Mapping IELTS Speaking Part 2\",\"DAY 10: FINAL EXAMINATION\"]', 'Tenses Text Observation – CONNECTED CHECK', 0, 1, '2026-10-12 12:59:04'),
(23, 'SC', 'SNAP CHAT', 1, 'pre-advanced', 'speaking', 'Pre-Advanced Level', '170000.00', '70%', 'Focus: Interactive Speaking Pattern & QnA, Speak about photo, Retelling  written Text, and Elaboration Idea in presentation', '[\"DAY 1: On the move,\",\"DAY 2: surfing scrolling and swiping,\",\"DAY 3: structure and landmarks,\",\"DAY 4: a steady job,\",\"DAY 5: Mind over matters,\",\"DAY 6: Life style choices,\",\"DAY 7: Looking after Yourself,\",\"DAY 8: Cultural Constribution,\",\"DAY 9: Broadening Horizons\",\"DAY 10: FINAL EXAMINATION\"]', 'Brain Mapping – PRESENTATION', 0, 1, '2026-10-12 13:01:48'),
(24, 'SS', 'SMASH SPEAK', 1, 'pre-advanced', 'ielts', 'Pre-Advanced Level', '170000.00', '70%', 'Focus: Structural language IELTS PART 2, Brain mapping (Scheme to speak), Discussion and Speaking with Grammar Accurancy', '[\"DAY 1: How video game change the world? (IELTS SPEAKING PART 2)\",\"DAY 2: the future of spaceflight, (IELTS SPEAKING PART 2)\",\"DAY 3: can technology solve our big problems? (IELTS SPEAKING PART 2)\",\"DAY 4: how to gain control of your free time? (IELTS SPEAKING PART 2)\",\"DAY 5: the smartphone problem, (IELTS SPEAKING PART 2)\",\"DAY 6: your online life: permanent as tattoo, (IELTS SPEAKING PART 2)\",\"DAY 7: the problem with fast fashion, (IELTS SPEAKING PART 2)\",\"DAY 8: online vs traditional shopping (e-commerce vocabulary),(IELTS SPEAKING PART 2)\",\"DAY 9: how false news can spread (hoax), (IELTS SPEAKING PART 2)\",\"DAY 10: FINAL EXAMINATION\"]', 'Presentation a latest issue', 0, 1, '2026-10-12 13:06:08'),
(25, 'AS', 'ACTIVE SPEAKING', 1, 'pre-advanced', 'ielts', 'Pre-Advanced Level', '170000.00', '70%', 'Focus: Delivering opinion (HOTS-High Order Thinking Skills), ELTS PART 3', '[\"Special Days in Abroad, Living in abroad (Shock Culture), Cyber Bullying, Indonesia Archipelago, Education System, Water and Sanitation in the future, Population Rate. Vocab choices for advanced speaking, Daily Reading supplement, IELTS SPEAKING PART 3\"]', 'GLOBAL & LOCAL ISSUES – TOPICS CARD', 0, 1, '2026-10-12 13:07:49'),
(26, 'VFI', 'VOCABULARY FOR IELTS', 1, 'pre-advanced', 'ielts', 'Pre-Advanced Level', '170000.00', '80%', 'Focus: useful phrases in IELTS based on topics (Advanced vocab vs Idioms) and English Expression', '[\"DAY 1: People  and Relationship,\",\"DAY 2: Education,\",\"DAY 3: Health,\",\"DAY 4: Gadget,\",\"DAY 5: Adventures,\",\"DAY 6: Words for describing graphs or diagrams,\",\"DAY 7: words describing cause and effects,\",\"DAY 8: signposting expression for writing,\",\"DAY 9: adverbs, words for talking ideas\",\"DAY 10: FINAL EXAMINATION\"]', 'Mini IELTS simulation (Vocabulary Check)', 0, 1, '2026-10-12 13:11:24'),
(27, 'SFI-GT', 'SPEAKING FOR IELTS (SFI) - GENERAL TRAINING', 1, 'pre-advanced', 'ielts', 'Pre-Advanced Level', '170000.00', '80%', 'Focus: Pattern of Answer Part 1, 2 & 3, Useful Expression for IELTS, Outline Speaking, Mock Up & Feedback', '[\"DAY 1: General Training vs Academics,\",\"DAY 2: QnA Part 1 Useful Phrases,\",\"DAY 3: Mock up Part 1 and feedback, Estimate Band Score\",\"DAY 4: Cue Card Part 2 for Grammar,\",\"day 5: practice PART 2 time management 2 minutes presentation\",\"DAY 6: Mock up PART 1 & 2 and feedback, Estimate Band Score\",\"DAY 7: Opinion Part 3\",\"DAY 8: Mock up PART 1, 2 & 3 and feedback, Estimate Band Score\",\"DAY 9: Review Part 1, 2 & 3\",\"DAY 10: FINAL EXAMINATION\"]', 'Simulation Part 1,2 and 3', 0, 1, '2026-10-12 13:16:40'),
(28, 'JI', 'JOB INTERVIEW', 1, 'advanced', 'speaking', 'Advanced Level', '180000.00', '80%', 'Focus: Pattern of answer Hrd Interview, Technical Question (USER), CV Screening, and Focus Group Discussion', '[\"DAY 1: Introduction of Interview (HRD questions),\",\"DAY 2: Step of Recruitment Process,\",\"DAY 3: Verbal and non-verbal communication,\",\"DAY 4: Quadrant theory of Robert Kyosaki,\",\"DAY 5: Salary Answer,\",\"DAY 6: USER Interview questions,\",\"DAY 7: CV Screening & CV Video,\",\"DAY 8: Focus Group Discussion (FGD), Virtual Interview,\",\"DAY 9: Sending application by Email, Linkedin & Kalibbr\",\"DAY 10: FINAL EXAMINATION\"]', 'USER & FGD – REAL INTERVIEW', 0, 1, '2026-10-12 13:20:41'),
(29, 'SFI-AC', 'SPEAKING FOR IELTS (SFI) - ACADEMICS', 1, 'advanced', 'ielts', 'Advanced Level', '180000.00', '80%', 'Focus: Pattern of Answer Part 1, 2 & 3, Useful Expression for IELTS, Outline Speaking, Mock Up & Feedback', '[\"DAY 1: General Understanding in term of Speaking IELTS \\u2013 British Council,\",\"DAY 2: Warming up questions(Part 1),\",\"DAY 3: How to enhance the part 1 (Simple Topics),\",\"DAY 4: What is the part 2 secret (monologue),\",\"DAY 5: Hints for IELTS speaking Part 3 practice,\",\"DAY 6: Use ful Expressions touse in IELTSSpeaking part 3(Practise: Interpreting video and review),\",\"DAY 7: How to get the pattern, : Part 1 practice, More powerful of part 2, Part 3 ideas to speak\",\"DAY 8: Mock Up Part 1, 2 & 3 and Feedback\",\"DAY 9: Review Mock Up Part 1, 2 & 3 and Feedback\",\"DAY 10: FINAL EXAMINATION\"]', 'Simulation Part 1, 2 and  3', 0, 1, '2026-10-12 13:24:29'),
(30, 'MSFI', 'MOCK UP - SPEAKING FOR IELTS', 1, 'advanced', 'ielts', 'Advanced Level', '200000.00', '80%', 'Focus: Scale up speaking IELTS Part 1, 2 & 3, target band score >6.0 for Academics Purpose (Scholarship & Admission University)', '[\"DAY 1: Fluency and Coherence, Grammatical Range and Accurancy, Lexical Resources, Pronunciation:\",\"DAY 2: Topics to Speak PART 1, 2 & 3 (Hometown, Family, Internet, Food, Hobbies, Internet, Particular Gift, School Subject, Old Person, Special Place and Occasion),\",\"DAY 3: Target to value (double idea, listing, on point speaking\\/direct, General and personal view),\",\"DAY 4: Avoiding low band core (Hestitation, Self Correction, Grammar error, Repeatation, & Time Management)\",\"DAY 5: Mock up Speaking IELTS Part 1, 2 & 3\",\"DAY 6: Mock up Speaking IELTS Part 1, 2 & 3\",\"DAY 7: Mock up Speaking IELTS Part 1, 2 & 3\",\"DAY 8: Mock up Speaking IELTS Part 1, 2 & 3\",\"DAY 9: Mock up Speaking IELTS Part 1, 2 & 3\",\"DAY 10: FINAL EXAMINATION\"]', 'Simulation Part 1, 2 and 3', 0, 1, '2026-10-12 13:28:53'),
(31, 'KIDS1', 'FUN ENGLISH FOR KIDS', 3, 'basic', 'speaking', 'Kids Level', '650000.00', '90%', 'Program speaking seru untuk anak usia 7-12 tahun.', 'Alphabet, Numbers, Daily Conversation, Songs & Games', 'Fun Speaking Test', 1, 1, '2026-10-14 08:47:26');

-- --------------------------------------------------------

--
-- Table structure for table `program_categories`
--

CREATE TABLE `program_categories` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `report_group` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `program_categories`
--

INSERT INTO `program_categories` (`id`, `name`, `report_group`) VALUES
(1, 'OFFLINE', 'offline'),
(2, 'PEPSI ONLINE CLASS', 'online'),
(3, 'PEPSI KIDS', 'offline'),
(7, 'NON KELAS', 'non_kelas');

-- --------------------------------------------------------

--
-- Table structure for table `registrations`
--

CREATE TABLE `registrations` (
  `id` int NOT NULL,
  `registration_no` varchar(50) NOT NULL,
  `period_id` int DEFAULT NULL,
  `period_name` varchar(100) NOT NULL,
  `student_name` varchar(150) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `city` varchar(100) NOT NULL,
  `time_slot` varchar(50) NOT NULL,
  `programs_json` text NOT NULL,
  `total_fee` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `registered_by` enum('guest','admin') NOT NULL DEFAULT 'guest',
  `admin_notes` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registrations`
--

INSERT INTO `registrations` (`id`, `registration_no`, `period_id`, `period_name`, `student_name`, `phone`, `city`, `time_slot`, `programs_json`, `total_fee`, `status`, `registered_by`, `admin_notes`, `created_at`, `updated_at`) VALUES
(1, 'REG-20261008-0001', NULL, 'Periode 10 Oktober 2026', 'Ahmad Fauzi', '081298765432', 'Malang', '08.30-10.00 WIB', '[{\"id\":3,\"title\":\"Impromptu\",\"price\":160000},{\"id\":4,\"title\":\"Grammar for Speaking 1\",\"price\":150000}]', '310000.00', 'approved', 'guest', 'Slot kelas tersedia dan disetujui', '2026-10-08 11:02:14', '2026-10-08 11:02:55'),
(4, 'REG-20261008-0002', 1, 'Periode 10 Oktober 2026', 'caca', '08123562535', 'cirebon', '07.00-08.30 WIB', '[{\"id\":4,\"title\":\"Grammar for Speaking 1\",\"price\":150000},{\"id\":2,\"title\":\"Starter\",\"price\":150000}]', '300000.00', 'approved', 'guest', 'Pendaftaran disetujui dan slot kelas dikonfirmasi', '2026-10-08 07:43:12', '2026-10-08 07:54:58'),
(5, 'REG-20261008-0003', 1, 'Periode 10 Oktober 2026', 'coba', '081111222333', 'Pare', '07.00-08.30 WIB', '[{\"id\":4,\"title\":\"Grammar for Speaking 1\",\"price\":150000}]', '150000.00', 'pending', 'guest', NULL, '2026-10-08 08:00:43', '2026-10-08 08:00:43'),
(6, 'REG-20261008-0004', 1, 'Periode 10 Oktober 2026', 'coba', '081111222333', 'Pare', '07.00-08.30 WIB', '[{\"id\":4,\"title\":\"Grammar for Speaking 1\",\"price\":150000},{\"id\":7,\"title\":\"Grammar for Speaking 2\",\"price\":160000}]', '310000.00', 'approved', 'guest', 'Pendaftaran disetujui dan slot kelas dikonfirmasi', '2026-10-08 08:04:11', '2026-10-09 03:39:30'),
(7, 'REG-20261009-0001', 1, 'Periode 10 Oktober 2026', 'VERONIKACAU', '089765453214', 'BANDUNG', '16.00-17.30 WIB', '[{\"id\":6,\"title\":\"Swap Vocab\",\"price\":150000}]', '150000.00', 'approved', 'guest', 'Pendaftaran disetujui dan slot kelas dikonfirmasi', '2026-10-09 01:51:22', '2026-10-09 01:51:58'),
(8, 'REG-20261010-0001', 1, 'Periode 10 Oktober 2026', 'bilis', '081123451087', 'papua', '08.30-10.00 WIB', '[{\"id\":1,\"title\":\"Let\'s Talk 1\",\"code\":\"LT1\",\"price\":150000}]', '150000.00', 'approved', 'admin', '', '2026-10-10 03:19:35', '2026-10-10 03:19:35'),
(9, 'REG-20261012-0001', 1, 'Periode 10 Oktober 2026', 'Mita Khasanah', '085648077008', 'Pare Kediri', '08.30-10.00 WIB', '[{\"id\":13,\"title\":\"Job Interview\",\"code\":\"JI\",\"price\":180000}]', '180000.00', 'approved', 'admin', 'Uji Coba', '2026-10-12 07:06:35', '2026-10-12 07:06:35'),
(11, 'REG-20261012-0003', 1, 'Periode 10 Oktober 2026', 'Fipsi Endrawan', '081359744144', 'Kediri', '07.00-08.30 WIB', '[{\"id\":19,\"title\":\"GRAMMAR FOR SPEAKING 1\",\"price\":150000},{\"id\":16,\"title\":\"LET\'S TALK 1\",\"price\":150000}]', '300000.00', 'approved', 'guest', 'Pendaftaran disetujui dan slot kelas dikonfirmasi', '2026-10-12 14:03:46', '2026-10-13 12:32:38');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int NOT NULL,
  `role` enum('owner','admin','cashier') NOT NULL,
  `menu_key` varchar(50) NOT NULL,
  `allowed` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role`, `menu_key`, `allowed`) VALUES
(161, 'admin', 'dashboard', 1),
(162, 'admin', 'registrations', 1),
(163, 'admin', 'students', 1),
(164, 'admin', 'graduates', 1),
(165, 'admin', 'programs', 1),
(166, 'admin', 'schedules', 1),
(167, 'admin', 'attendance', 1),
(168, 'admin', 'payroll', 1),
(169, 'admin', 'tutors', 1),
(170, 'admin', 'rooms', 1),
(171, 'admin', 'periods', 1),
(172, 'admin', 'canteen', 1),
(173, 'admin', 'canteen_opname', 1),
(174, 'admin', 'canteen_report', 1),
(175, 'admin', 'canteen_barcode', 1),
(176, 'admin', 'pos', 1),
(177, 'admin', 'reports_students', 1),
(178, 'admin', 'reports_finance', 1),
(179, 'admin', 'settings', 1),
(180, 'admin', 'users', 1),
(181, 'admin', 'permissions', 1),
(182, 'cashier', 'dashboard', 0),
(183, 'cashier', 'registrations', 0),
(184, 'cashier', 'students', 0),
(185, 'cashier', 'graduates', 0),
(186, 'cashier', 'programs', 0),
(187, 'cashier', 'schedules', 0),
(188, 'cashier', 'attendance', 0),
(189, 'cashier', 'payroll', 0),
(190, 'cashier', 'tutors', 0),
(191, 'cashier', 'rooms', 0),
(192, 'cashier', 'periods', 0),
(193, 'cashier', 'canteen', 0),
(194, 'cashier', 'canteen_opname', 0),
(195, 'cashier', 'canteen_report', 0),
(196, 'cashier', 'canteen_barcode', 0),
(197, 'cashier', 'pos', 1),
(198, 'cashier', 'reports_students', 0),
(199, 'cashier', 'reports_finance', 0),
(200, 'cashier', 'settings', 0),
(201, 'cashier', 'users', 0),
(202, 'cashier', 'permissions', 0),
(203, 'admin', 'reports_combined', 1),
(204, 'cashier', 'reports_combined', 0),
(205, 'admin', 'staff_office', 1),
(206, 'cashier', 'staff_office', 0),
(207, 'admin', 'slip_report', 1),
(208, 'cashier', 'slip_report', 0),
(209, 'admin', 'manual_sales', 1),
(210, 'admin', 'expenses', 1),
(211, 'admin', 'expenses_report', 1),
(212, 'admin', 'pos_control', 1),
(213, 'admin', 'database', 1),
(214, 'cashier', 'manual_sales', 0),
(215, 'cashier', 'expenses', 0),
(216, 'cashier', 'expenses_report', 0),
(217, 'cashier', 'pos_control', 0),
(218, 'cashier', 'database', 0);

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `capacity` int NOT NULL DEFAULT '15',
  `area_type` enum('indoor','outdoor') NOT NULL DEFAULT 'indoor',
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `name`, `capacity`, `area_type`, `description`, `is_active`, `created_at`) VALUES
(1, 'Alpha', 18, 'indoor', 'Ruang Kelas Alpha Nyaman (INDOOR)', 1, '2026-10-08 10:21:32'),
(2, 'Bravo', 18, 'indoor', 'Ruang Kelas Bravo Nyaman (INDOOR)', 1, '2026-10-08 10:21:32'),
(3, 'Charlie', 18, 'indoor', 'Ruang Kelas Charlie Nyaman (INDOOR)', 1, '2026-10-08 10:21:32'),
(4, 'Deluxe', 12, 'indoor', 'Ruang Kelas Deluxe Nyaman (OUTDOOR)', 1, '2026-10-08 10:21:32'),
(5, 'Gazebo', 14, 'indoor', 'Ruang Kelas Gazebo Nyaman (OUTDOOR)', 1, '2026-10-08 10:21:32'),
(6, 'Romeo', 10, 'indoor', 'Ruang Kelas Romeo Nyaman (INDOOR)', 1, '2026-10-08 10:21:32');

-- --------------------------------------------------------

--
-- Table structure for table `room_inventories`
--

CREATE TABLE `room_inventories` (
  `id` int NOT NULL,
  `room_id` int NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `condition_status` enum('good','minor_damage','major_damage') NOT NULL DEFAULT 'good',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` int NOT NULL,
  `period_id` int NOT NULL,
  `day_name` varchar(50) NOT NULL DEFAULT 'Senin - Jumat',
  `time_slot` varchar(30) NOT NULL,
  `program_id` int NOT NULL,
  `room_id` int NOT NULL,
  `tutor_id` int NOT NULL,
  `fee_rate` decimal(12,2) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`id`, `period_id`, `day_name`, `time_slot`, `program_id`, `room_id`, `tutor_id`, `fee_rate`, `created_at`) VALUES
(15, 1, 'Senin - Jumat', '07.00-08.30', 21, 1, 6, NULL, '2026-10-12 13:30:04'),
(16, 1, 'Senin - Jumat', '07.00-08.30', 18, 2, 1, NULL, '2026-10-12 13:30:24'),
(17, 1, 'Senin - Jumat', '07.00-08.30', 24, 3, 4, NULL, '2026-10-12 13:30:45'),
(18, 1, 'Senin - Jumat', '07.00-08.30', 19, 4, 5, NULL, '2026-10-12 13:31:10'),
(19, 1, 'Senin - Jumat', '07.00-08.30', 20, 5, 7, NULL, '2026-10-12 13:31:37'),
(20, 1, 'Senin - Jumat', '07.00-08.30', 17, 6, 2, NULL, '2026-10-12 13:32:01'),
(21, 1, 'Senin - Jumat', '08.30-10.00', 17, 1, 6, NULL, '2026-10-12 13:32:23'),
(22, 1, 'Senin - Jumat', '08.30-10.00', 26, 2, 1, NULL, '2026-10-12 13:32:43'),
(23, 1, 'Senin - Jumat', '08.30-10.00', 28, 3, 8, NULL, '2026-10-12 13:33:06'),
(24, 1, 'Senin - Jumat', '08.30-10.00', 19, 4, 9, NULL, '2026-10-12 13:33:28'),
(25, 1, 'Senin - Jumat', '08.30-10.00', 16, 5, 7, NULL, '2026-10-12 13:33:51'),
(26, 1, 'Senin - Jumat', '08.30-10.00', 24, 6, 3, NULL, '2026-10-12 13:34:13'),
(27, 1, 'Senin - Jumat', '10.00-11.30', 20, 1, 5, NULL, '2026-10-12 13:34:35'),
(28, 1, 'Senin - Jumat', '10.00-11.30', 22, 2, 1, NULL, '2026-10-12 13:34:56'),
(29, 1, 'Senin - Jumat', '10.00-11.30', 29, 3, 8, NULL, '2026-10-12 13:35:19'),
(30, 1, 'Senin - Jumat', '10.00-11.30', 25, 5, 3, NULL, '2026-10-12 13:35:40'),
(31, 1, 'Senin - Jumat', '13.00-14.30', 18, 1, 1, NULL, '2026-10-12 13:36:03'),
(32, 1, 'Senin - Jumat', '13.00-14.30', 20, 2, 7, NULL, '2026-10-12 13:36:20'),
(33, 1, 'Senin - Jumat', '13.00-14.30', 24, 3, 3, NULL, '2026-10-12 13:36:40'),
(34, 1, 'Senin - Jumat', '14.30-16.00', 17, 1, 2, NULL, '2026-10-12 13:37:01'),
(35, 1, 'Senin - Jumat', '14.30-16.00', 20, 2, 7, NULL, '2026-10-12 13:37:22'),
(36, 1, 'Senin - Jumat', '14.30-16.00', 18, 3, 3, NULL, '2026-10-12 13:37:44'),
(37, 1, 'Senin - Jumat', '14.30-16.00', 19, 5, 9, NULL, '2026-10-12 13:38:15'),
(38, 1, 'Senin - Jumat', '16.00-17.30', 18, 1, 2, NULL, '2026-10-12 13:38:37');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`) VALUES
(1, 'app_name', 'MR.PEPSI UPGRADE EC'),
(2, 'tagline', 'More Than A Course, This Is A Place Where The Dreams Start To Grow'),
(3, 'admin_wa', '6285648077008'),
(4, 'address', 'Jl. Kemuning, Tulungrejo, Kode Pos 64212, Pare, Kediri, Jawa Timur'),
(5, 'instagram', '@mrpepsiofficial01'),
(6, 'email', 'mrpepsikampunginggris@gmail.com'),
(7, 'sk_diknas', 'SK Diknas No. 421.9/3735/418.20/2026'),
(8, 'npsn', 'NPSN K9997239'),
(9, 'default_tutor_fee', '40000'),
(10, 'office_hours', 'Senin - Jumat: 07.00 - 16.00 WIB\r\nSabtu: 08.00 - 14.00 WIB\r\nIstirahat: 12.00 - 13.00 WIB'),
(11, 'class_rules', '1. Datang tepat waktu kedalam kelas (10 menit konsekuensi keterlambatan).\r\n2. Wajib menggunakan Bahasa Inggris sesuai target rasio kelas.\r\n3. Ujian akhir wajib diikuti untuk mendapatkan sertifikat.\r\n4. ⁠Pendaftaran tidak bisa di cancel/pindah kelas/jam,pindah periode,ganti orang\r\n5. Berpakaian Rapi dan Sopan (TIDAK MENGGUNAKAN CELANA PENDEK Atau SARUNG)\r\n6. Melakukan pemberitahuan di group WA kelas jika berhalangan hadir sebagai bagian dari orang-orang berpendidikan dan respect\r\n7. Harap parkir ditata dengan rapi SEPEDA dibelakang office (Pohon ketepeng varigata), dan MOTOR disamping'),
(12, 'logo_path', 'uploads/logo/logo-20261008-8563a34e.png'),
(19, 'certificate_seq_counter', '2');

-- --------------------------------------------------------

--
-- Table structure for table `staff_offices`
--

CREATE TABLE `staff_offices` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `position` varchar(100) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `salary` decimal(12,2) NOT NULL DEFAULT '0.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_offices`
--

INSERT INTO `staff_offices` (`id`, `name`, `position`, `phone`, `email`, `salary`, `is_active`, `created_at`) VALUES
(2, 'Triana Agnes Damayanti', 'Kasir & Staff Office', NULL, NULL, '0.00', 1, '2026-10-13 08:45:07'),
(3, 'Desy Kurnia Anggreini', 'Staff Administrasi', NULL, NULL, '0.00', 1, '2026-10-13 08:52:03');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int NOT NULL,
  `student_no` varchar(30) NOT NULL COMMENT 'SIS-YYYYMMDD-NNNN',
  `registration_id` int DEFAULT NULL,
  `full_name` varchar(150) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `city` varchar(100) NOT NULL DEFAULT '',
  `gender` enum('L','P','') NOT NULL DEFAULT '',
  `birth_date` date DEFAULT NULL,
  `period_name` varchar(100) NOT NULL DEFAULT '',
  `time_slot` varchar(50) NOT NULL DEFAULT '',
  `programs_json` text NOT NULL,
  `total_fee` decimal(12,2) NOT NULL DEFAULT '0.00',
  `paid_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `payment_status` enum('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
  `payment_method` enum('tunai','qris','transfer') NOT NULL DEFAULT 'tunai',
  `status` enum('active','graduated','dropped') NOT NULL DEFAULT 'active',
  `notes` text,
  `enrolled_at` date NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_no`, `registration_id`, `full_name`, `phone`, `city`, `gender`, `birth_date`, `period_name`, `time_slot`, `programs_json`, `total_fee`, `paid_amount`, `payment_status`, `payment_method`, `status`, `notes`, `enrolled_at`, `created_at`, `updated_at`) VALUES
(1, 'SIS-20261008-0001', 1, 'Ahmad Fauzi', '081298765432', 'Malang', '', NULL, 'Periode 10 Oktober 2026', '08.30-10.00 WIB', '[{\"id\":3,\"title\":\"Impromptu\",\"price\":160000},{\"id\":4,\"title\":\"Grammar for Speaking 1\",\"price\":150000}]', '310000.00', '0.00', 'unpaid', 'tunai', 'graduated', NULL, '2026-10-08', '2026-10-08 11:57:52', '2026-10-08 12:38:30'),
(2, 'SIS-20261008-0002', 4, 'caca', '08123562535', 'cirebon', '', NULL, 'Periode 10 Oktober 2026', '07.00-08.30 WIB', '[{\"id\":4,\"title\":\"Grammar for Speaking 1\",\"price\":150000},{\"id\":2,\"title\":\"Starter\",\"price\":150000}]', '300000.00', '0.00', 'unpaid', 'tunai', 'graduated', NULL, '2026-10-08', '2026-10-08 07:54:58', '2026-10-09 01:46:46'),
(3, 'SIS-20261009-0001', 7, 'VERONIKACAU', '089765453214', 'BANDUNG', '', NULL, 'Periode 10 Oktober 2026', '16.00-17.30 WIB', '[{\"id\":6,\"title\":\"Swap Vocab\",\"price\":150000}]', '150000.00', '0.00', 'unpaid', 'tunai', 'graduated', NULL, '2026-10-09', '2026-10-09 01:51:58', '2026-10-09 01:52:59'),
(6, 'SIS-20261009-0002', NULL, 'popo', '08123456789', 'Kediri', 'L', NULL, 'Periode 10 Oktober 2026', 'sesi 1', '[{\"id\":1,\"title\":\"Let\'s Talk 1\",\"code\":\"LT1\",\"price\":150000}]', '150150.00', '150.00', 'partial', 'tunai', 'graduated', '', '2026-10-09', '2026-10-09 04:22:57', '2026-10-12 07:23:15'),
(7, 'SIS-20261013-0001', 11, 'Fipsi Endrawan', '081359744144', 'Kediri', '', NULL, 'Periode 10 Oktober 2026', '07.00-08.30 WIB', '[{\"id\":19,\"title\":\"GRAMMAR FOR SPEAKING 1\",\"price\":150000},{\"id\":16,\"title\":\"LET\'S TALK 1\",\"price\":150000}]', '300000.00', '300000.00', 'paid', 'tunai', 'graduated', NULL, '2026-10-13', '2026-10-13 12:32:38', '2026-10-13 19:44:45'),
(8, 'SIS-20261014-9001', NULL, 'Kevin Pratama', '081234567890', 'Kediri', 'L', '2018-05-12', 'Oktober 2026', '14:00 - 15:30', '[{\"title\":\"FUN ENGLISH FOR KIDS\",\"code\":\"KIDS1\"}]', '650000.00', '650000.00', 'paid', 'tunai', 'graduated', 'Data dummy untuk uji sertifikat Kids', '2026-10-14', '2026-10-14 08:47:26', '2026-10-14 08:47:26');

-- --------------------------------------------------------

--
-- Table structure for table `student_graduations`
--

CREATE TABLE `student_graduations` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `certificate_no` varchar(60) NOT NULL COMMENT 'CERT-YYYYMMDD-NNNN',
  `program_title` varchar(150) NOT NULL,
  `program_code` varchar(30) NOT NULL DEFAULT '',
  `period_name` varchar(100) NOT NULL DEFAULT '',
  `graduated_at` date NOT NULL,
  `final_score` decimal(5,2) DEFAULT NULL COMMENT 'Nilai akhir 0-100',
  `grade` varchar(10) DEFAULT NULL COMMENT 'A/B/C/D',
  `is_kid` tinyint(1) NOT NULL DEFAULT '0',
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_graduations`
--

INSERT INTO `student_graduations` (`id`, `student_id`, `certificate_no`, `program_title`, `program_code`, `period_name`, `graduated_at`, `final_score`, `grade`, `is_kid`, `notes`, `created_by`, `created_at`) VALUES
(1, 1, 'CERT-20261008-0001', 'English for Speaking - Intermediate', 'ENG-SPK-2', 'Periode 10 Oktober 2026', '2026-10-08', '92.50', 'A', 0, 'Lulus dengan predikat istimewa.', 1, '2026-10-08 12:38:30'),
(2, 1, 'CERT-20261008-0002', 'Impromptu', '', 'Periode 10 Oktober 2026', '2026-10-08', '85.00', 'A', 0, 'Menyelesaikan seluruh silabus program dengan predikat sangat baik.', 1, '2026-10-08 08:35:56'),
(3, 2, 'CERT-20261009-0001', 'Grammar for Speaking 1', 'ENG       -123 -', 'Periode 10 Oktober 2026', '2026-10-09', '88.00', 'A', 0, 'COBA', 1, '2026-10-09 01:46:46'),
(4, 3, 'CERT-20261009-0002', 'Swap Vocab', 'ENG-123-', 'Periode 10 Oktober 2026', '2026-10-09', '75.50', 'B', 0, '', 1, '2026-10-09 01:52:59'),
(5, 6, 'CERT-20261012-0001', 'Let\'s Talk 1', 'LT1', 'Periode 10 Oktober 2026', '2026-10-12', '85.00', 'A', 0, '', 1, '2026-10-12 07:23:15'),
(6, 6, 'CERT-20261012-0002', 'Let\'s Talk 1', 'LT1', 'Periode 10 Oktober 2026', '2026-10-12', '85.00', 'A', 0, 'Menyelesaikan seluruh silabus program dengan predikat sangat baik.', 1, '2026-10-12 15:06:27'),
(7, 7, '00001/MPU/C.eng-spk/X/2026', 'GRAMMAR FOR SPEAKING 1', 'eng-spk', 'Periode 10 Oktober 2026', '2026-10-13', NULL, 'GOOD', 0, NULL, 6, '2026-10-13 19:44:45'),
(8, 8, '00002/MPU/C.KIDS1/X/2026', 'FUN ENGLISH FOR KIDS', 'KIDS1', 'Oktober 2026', '2026-10-14', NULL, 'EXCELLENT', 1, NULL, NULL, '2026-10-14 08:47:26');

-- --------------------------------------------------------

--
-- Table structure for table `tutors`
--

CREATE TABLE `tutors` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `fee_per_session` decimal(12,2) NOT NULL DEFAULT '40000.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tutors`
--

INSERT INTO `tutors` (`id`, `name`, `phone`, `specialization`, `fee_per_session`, `is_active`, `created_at`) VALUES
(1, 'APRULLYA NURIKAN NANDA', '081654909297', 'Impromptu, Smash Speak, Vocabulary for IELTS, Grammar For Speaking 1 & 2', '40000.00', 1, '2026-10-08 10:21:32'),
(2, 'NASHRULLAH', '082227816636', 'Impromptu, Starter, Let\'s Talk 2', '40000.00', 1, '2026-10-08 10:21:32'),
(3, 'ANNISA RAHMA YUNI', '081535301625', 'IELTS Orientation: Impromptu, Smash Speak & Active Speaking', '40000.00', 1, '2026-10-08 10:21:32'),
(4, 'KHANSA NABILAH', '082297008178', 'IELTS Orientation: Smash Speak, Active Speaking, Grammar For Speaking 2', '40000.00', 1, '2026-10-08 10:21:32'),
(5, 'ISNAINI', '081396116360', 'Let\'s Talk 1, Let\'s Talk 2, Grammar For Speaking 1, Pepsi Kids', '40000.00', 1, '2026-10-08 10:21:32'),
(6, 'FIDELA CLARISSA ZERLINDA', '085895713623', 'Let\'s Talk 1, Swap Vocab, Let\'s Talk 2, Starter, Pepsi Kids', '40000.00', 1, '2026-10-08 10:21:32'),
(7, 'NABILAH NUR RAHMAH RAIS', '082351748082', 'Let\'s Talk 1, Let\'s Talk 2, Starter, Pepsi Kids', '40000.00', 1, '2026-10-08 10:21:32'),
(8, 'FIPSI ENDRAWAN', '081359744144', 'Head Coach - IELTS and Job Interview Specialist', '40000.00', 1, '2026-10-08 10:21:32'),
(9, 'MALIK IBRAHIM RAMDHANI', '082337179771', 'Let\'s Talk 1, Let\'s Talk 2, Starter, Grammar For Speaking 1, Snapchat, Pepsi Kids', '40000.00', 1, '2026-10-08 10:21:32'),
(10, 'ANUGRAH IRAWAN', '089507117060', 'Impromptu, Starter, Grammar For Speaking 1, Speaking for IELTS GT', '40000.00', 1, '2026-10-08 10:21:32');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('owner','admin','cashier') NOT NULL DEFAULT 'admin',
  `session_token` varchar(128) DEFAULT NULL,
  `last_seen` datetime DEFAULT NULL,
  `log_login` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `password`, `role`, `session_token`, `last_seen`, `log_login`, `created_at`) VALUES
(1, 'Fipsi Endrawan', 'fipsi', '$2y$10$Q1nkQDPyxrJXFlpK.1YPGeUP8mbgGLiIGueGnNSFGjpMpfQd5dc3q', 'owner', NULL, NULL, 0, '2026-10-08 10:21:32'),
(2, 'Triana Agnes Damayanti', 'triana', '$2y$10$Q1nkQDPyxrJXFlpK.1YPGeUP8mbgGLiIGueGnNSFGjpMpfQd5dc3q', 'cashier', NULL, NULL, 0, '2026-10-08 10:21:32'),
(4, 'Mita Khasanah', 'mita', '$2y$10$Q1nkQDPyxrJXFlpK.1YPGeUP8mbgGLiIGueGnNSFGjpMpfQd5dc3q', 'owner', NULL, NULL, 0, '2026-10-09 11:10:36'),
(5, 'Desy Kurnia Anggreini', 'desy', '$2y$10$Q1nkQDPyxrJXFlpK.1YPGeUP8mbgGLiIGueGnNSFGjpMpfQd5dc3q', 'admin', NULL, NULL, 0, '2026-10-13 08:45:07'),
(6, 'Admin Utama', 'admin', '$2y$10$CVUpilkRqg4itBtBrhN7AOtbY2UkpvQ8SoL61cYTMK1PnlwjsdhR2', 'admin', '3bffc70df8f890c7299624ea7c0a882912b62244bfa3402532aeddb62da6a40e', '2026-10-15 10:16:37', 1, '2026-10-13 19:43:34');

-- --------------------------------------------------------

--
-- Table structure for table `user_permissions`
--

CREATE TABLE `user_permissions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `menu_key` varchar(50) NOT NULL,
  `allowed` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_permissions`
--

INSERT INTO `user_permissions` (`id`, `user_id`, `menu_key`, `allowed`) VALUES
(4, 2, 'registrations', 1),
(5, 2, 'pos', 1),
(6, 2, 'canteen_opname', 1),
(7, 2, 'canteen_report', 1),
(8, 2, 'staff_office', 1),
(33, 5, 'dashboard', 0),
(34, 5, 'registrations', 1),
(35, 5, 'students', 1),
(36, 5, 'graduates', 1),
(37, 5, 'programs', 0),
(38, 5, 'schedules', 0),
(39, 5, 'attendance', 0),
(40, 5, 'payroll', 0),
(41, 5, 'tutors', 0),
(42, 5, 'staff_office', 0),
(43, 5, 'rooms', 0),
(44, 5, 'periods', 0),
(45, 5, 'canteen', 0),
(46, 5, 'canteen_opname', 0),
(47, 5, 'canteen_report', 0),
(48, 5, 'canteen_barcode', 0),
(49, 5, 'pos', 0),
(50, 5, 'reports_students', 0),
(51, 5, 'reports_finance', 0),
(52, 5, 'reports_combined', 0),
(53, 5, 'slip_report', 0),
(54, 5, 'settings', 0),
(55, 5, 'users', 0),
(56, 5, 'permissions', 0),
(57, 2, 'dashboard', 0),
(58, 2, 'students', 0),
(59, 2, 'graduates', 0),
(60, 2, 'programs', 0),
(61, 2, 'schedules', 0),
(62, 2, 'attendance', 0),
(63, 2, 'payroll', 0),
(64, 2, 'tutors', 0),
(65, 2, 'rooms', 0),
(66, 2, 'periods', 0),
(67, 2, 'canteen', 0),
(68, 2, 'manual_sales', 0),
(69, 2, 'canteen_barcode', 0),
(70, 2, 'reports_students', 0),
(71, 2, 'reports_finance', 0),
(72, 2, 'reports_combined', 0),
(73, 2, 'slip_report', 0),
(74, 2, 'expenses', 0),
(75, 2, 'expenses_report', 0),
(76, 2, 'settings', 0),
(77, 2, 'pos_control', 0),
(78, 2, 'users', 0),
(79, 2, 'permissions', 0),
(80, 2, 'database', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendances`
--
ALTER TABLE `attendances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_sched_date` (`schedule_id`,`attendance_date`),
  ADD KEY `fk_att_maintutor` (`main_tutor_id`),
  ADD KEY `fk_att_subtutor` (`substituted_tutor_id`),
  ADD KEY `fk_att_acttutor` (`actual_tutor_id`);

--
-- Indexes for table `canteen_categories`
--
ALTER TABLE `canteen_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `canteen_products`
--
ALTER TABLE `canteen_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `barcode` (`barcode`),
  ADD KEY `fk_prod_cat` (`category_id`);

--
-- Indexes for table `canteen_stock_batches`
--
ALTER TABLE `canteen_stock_batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_batch_product` (`product_id`,`created_at`);

--
-- Indexes for table `canteen_stock_logs`
--
ALTER TABLE `canteen_stock_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_stock_prod` (`product_id`);

--
-- Indexes for table `cashier_shifts`
--
ALTER TABLE `cashier_shifts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_shift_user` (`user_id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_expense_date` (`expense_date`),
  ADD KEY `idx_expense_cat` (`category_id`),
  ADD KEY `fk_expense_user` (`created_by`);

--
-- Indexes for table `expense_categories`
--
ALTER TABLE `expense_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `periods`
--
ALTER TABLE `periods`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pos_transactions`
--
ALTER TABLE `pos_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_no` (`invoice_no`),
  ADD KEY `fk_pos_shift` (`shift_id`),
  ADD KEY `fk_pos_user` (`user_id`);

--
-- Indexes for table `pos_transaction_items`
--
ALTER TABLE `pos_transaction_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_item_tx` (`transaction_id`),
  ADD KEY `fk_item_prod` (`product_id`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `fk_prog_cat` (`category_id`);

--
-- Indexes for table `program_categories`
--
ALTER TABLE `program_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `registrations`
--
ALTER TABLE `registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `registration_no` (`registration_no`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_phone` (`phone`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_role_key` (`role`,`menu_key`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `room_inventories`
--
ALTER TABLE `room_inventories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_inv_room` (`room_id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_room_slot` (`period_id`,`time_slot`,`room_id`),
  ADD UNIQUE KEY `unique_tutor_slot` (`period_id`,`time_slot`,`tutor_id`),
  ADD KEY `fk_sched_program` (`program_id`),
  ADD KEY `fk_sched_room` (`room_id`),
  ADD KEY `fk_sched_tutor` (`tutor_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `staff_offices`
--
ALTER TABLE `staff_offices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_no` (`student_no`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_name` (`full_name`);

--
-- Indexes for table `student_graduations`
--
ALTER TABLE `student_graduations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `certificate_no` (`certificate_no`),
  ADD KEY `fk_grad_student` (`student_id`);

--
-- Indexes for table `tutors`
--
ALTER TABLE `tutors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_user_key` (`user_id`,`menu_key`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendances`
--
ALTER TABLE `attendances`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `canteen_categories`
--
ALTER TABLE `canteen_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `canteen_products`
--
ALTER TABLE `canteen_products`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `canteen_stock_batches`
--
ALTER TABLE `canteen_stock_batches`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `canteen_stock_logs`
--
ALTER TABLE `canteen_stock_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `cashier_shifts`
--
ALTER TABLE `cashier_shifts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expense_categories`
--
ALTER TABLE `expense_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `periods`
--
ALTER TABLE `periods`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `pos_transactions`
--
ALTER TABLE `pos_transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `pos_transaction_items`
--
ALTER TABLE `pos_transaction_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `program_categories`
--
ALTER TABLE `program_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `registrations`
--
ALTER TABLE `registrations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=223;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `room_inventories`
--
ALTER TABLE `room_inventories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `staff_offices`
--
ALTER TABLE `staff_offices`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `student_graduations`
--
ALTER TABLE `student_graduations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tutors`
--
ALTER TABLE `tutors`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_permissions`
--
ALTER TABLE `user_permissions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendances`
--
ALTER TABLE `attendances`
  ADD CONSTRAINT `fk_att_acttutor` FOREIGN KEY (`actual_tutor_id`) REFERENCES `tutors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_att_maintutor` FOREIGN KEY (`main_tutor_id`) REFERENCES `tutors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_att_sched` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_att_subtutor` FOREIGN KEY (`substituted_tutor_id`) REFERENCES `tutors` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `canteen_products`
--
ALTER TABLE `canteen_products`
  ADD CONSTRAINT `fk_prod_cat` FOREIGN KEY (`category_id`) REFERENCES `canteen_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `canteen_stock_batches`
--
ALTER TABLE `canteen_stock_batches`
  ADD CONSTRAINT `fk_batch_prod` FOREIGN KEY (`product_id`) REFERENCES `canteen_products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `canteen_stock_logs`
--
ALTER TABLE `canteen_stock_logs`
  ADD CONSTRAINT `fk_stock_prod` FOREIGN KEY (`product_id`) REFERENCES `canteen_products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cashier_shifts`
--
ALTER TABLE `cashier_shifts`
  ADD CONSTRAINT `fk_shift_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `fk_expense_cat` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_expense_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `pos_transactions`
--
ALTER TABLE `pos_transactions`
  ADD CONSTRAINT `fk_pos_shift` FOREIGN KEY (`shift_id`) REFERENCES `cashier_shifts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_pos_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pos_transaction_items`
--
ALTER TABLE `pos_transaction_items`
  ADD CONSTRAINT `fk_item_prod` FOREIGN KEY (`product_id`) REFERENCES `canteen_products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_item_tx` FOREIGN KEY (`transaction_id`) REFERENCES `pos_transactions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `programs`
--
ALTER TABLE `programs`
  ADD CONSTRAINT `fk_prog_cat` FOREIGN KEY (`category_id`) REFERENCES `program_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `room_inventories`
--
ALTER TABLE `room_inventories`
  ADD CONSTRAINT `fk_inv_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `fk_sched_period` FOREIGN KEY (`period_id`) REFERENCES `periods` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sched_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sched_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sched_tutor` FOREIGN KEY (`tutor_id`) REFERENCES `tutors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_graduations`
--
ALTER TABLE `student_graduations`
  ADD CONSTRAINT `fk_grad_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD CONSTRAINT `fk_userperm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
