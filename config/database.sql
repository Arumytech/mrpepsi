-- MR.PEPSI UPGRADE Database Dump
-- Native Clean MVC
CREATE DATABASE IF NOT EXISTS `mrpepsi_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `mrpepsi_db`;

-- 1. Users
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('owner', 'admin', 'cashier') NOT NULL DEFAULT 'admin',
  `session_token` VARCHAR(128) NULL COMMENT 'Token sesi aktif (penegakan 1 user = 1 device)',
  `last_seen`     DATETIME     NULL COMMENT 'Aktivitas terakhir sesi aktif',
  `log_login` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = sedang login, 0 = tidak (penegakan 1 user = 1 device)',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Settings
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(50) NOT NULL UNIQUE,
  `setting_value` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Periods
CREATE TABLE IF NOT EXISTS `periods` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `period_day` INT NOT NULL DEFAULT 10,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tutors
CREATE TABLE IF NOT EXISTS `tutors` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(30) NULL,
  `specialization` VARCHAR(100) NULL,
  `fee_per_session` DECIMAL(12,2) NOT NULL DEFAULT 40000.00,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4a. Staff Office (Karyawan Kantor)
CREATE TABLE IF NOT EXISTS `staff_offices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `position` VARCHAR(100) NULL,
  `phone` VARCHAR(30) NULL,
  `email` VARCHAR(120) NULL,
  `salary` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Rooms
CREATE TABLE IF NOT EXISTS `rooms` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `capacity` INT NOT NULL DEFAULT 15,
  `area_type` ENUM('indoor', 'outdoor') NOT NULL DEFAULT 'indoor',
  `description` VARCHAR(255) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5b. Room Inventories (Daftar Inventaris Barang per Ruangan)
CREATE TABLE IF NOT EXISTS `room_inventories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `room_id` INT NOT NULL,
  `item_name` VARCHAR(150) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `condition_status` ENUM('good', 'minor_damage', 'major_damage') NOT NULL DEFAULT 'good',
  `notes` VARCHAR(255) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_inv_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Programs
CREATE TABLE IF NOT EXISTS `programs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(30) NOT NULL UNIQUE,
  `title` VARCHAR(150) NOT NULL,
  `category_id` INT NULL,
  `level_category` VARCHAR(50) NOT NULL DEFAULT 'basic',
  `orientation` ENUM('speaking', 'ielts') NOT NULL DEFAULT 'speaking',
  `level_label` VARCHAR(50) NOT NULL,
  `price` DECIMAL(12,2) NOT NULL,
  `english_ratio` VARCHAR(20) NULL,
  `description` TEXT NOT NULL,
  `syllabus` TEXT NOT NULL,
  `final_exam` VARCHAR(255) NOT NULL,
  `is_new` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_prog_cat` FOREIGN KEY (`category_id`) REFERENCES `program_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6a. Program Categories
CREATE TABLE IF NOT EXISTS `program_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `report_group` VARCHAR(20) NULL COMMENT 'online|offline|non_kelas untuk pengelompokan Laporan Keuangan Kursus'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Schedules (Schedule Matrix with Anti-Bentrok Constraints)
CREATE TABLE IF NOT EXISTS `schedules` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `period_id` INT NOT NULL,
  `day_name` VARCHAR(50) NOT NULL DEFAULT 'Senin - Jumat',
  `time_slot` VARCHAR(30) NOT NULL,
  `program_id` INT NOT NULL,
  `room_id` INT NOT NULL,
  `tutor_id` INT NOT NULL,
  `fee_rate` DECIMAL(12,2) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_sched_period` FOREIGN KEY (`period_id`) REFERENCES `periods` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sched_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sched_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sched_tutor` FOREIGN KEY (`tutor_id`) REFERENCES `tutors` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_room_slot` (`period_id`, `time_slot`, `room_id`),
  UNIQUE KEY `unique_tutor_slot` (`period_id`, `time_slot`, `tutor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Attendances (Checklist Presensi & Tutor Pengganti / Substitusi)
CREATE TABLE IF NOT EXISTS `attendances` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `schedule_id` INT NOT NULL,
  `attendance_date` DATE NOT NULL,
  `main_tutor_id` INT NOT NULL,
  `status` ENUM('present', 'absent', 'substituted') NOT NULL DEFAULT 'present',
  `substituted_tutor_id` INT NULL,
  `actual_tutor_id` INT NOT NULL,
  `session_fee` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `notes` VARCHAR(255) NULL,
  `created_by` INT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_att_sched` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_att_maintutor` FOREIGN KEY (`main_tutor_id`) REFERENCES `tutors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_att_subtutor` FOREIGN KEY (`substituted_tutor_id`) REFERENCES `tutors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_att_acttutor` FOREIGN KEY (`actual_tutor_id`) REFERENCES `tutors` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_sched_date` (`schedule_id`, `attendance_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Canteen Categories & Products
CREATE TABLE IF NOT EXISTS `canteen_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `canteen_products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NULL,
  `barcode` VARCHAR(50) NULL UNIQUE,
  `name` VARCHAR(150) NOT NULL,
  `cost_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `selling_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `stock` INT NOT NULL DEFAULT 0,
  `min_stock` INT NOT NULL DEFAULT 5,
  `image` VARCHAR(255) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_prod_cat` FOREIGN KEY (`category_id`) REFERENCES `canteen_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `canteen_stock_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `type` ENUM('in', 'out', 'opname_adjustment', 'sale') NOT NULL,
  `quantity` INT NOT NULL,
  `old_stock` INT NOT NULL,
  `new_stock` INT NOT NULL,
  `notes` VARCHAR(255) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_stock_prod` FOREIGN KEY (`product_id`) REFERENCES `canteen_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Batch stok per pembelian (metode FIFO)
CREATE TABLE IF NOT EXISTS `canteen_stock_batches` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `unit_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `qty_in` INT NOT NULL DEFAULT 0,
  `qty_remaining` INT NOT NULL DEFAULT 0,
  `source` ENUM('initial', 'restock', 'opname') NOT NULL DEFAULT 'restock',
  `notes` VARCHAR(255) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_batch_prod` FOREIGN KEY (`product_id`) REFERENCES `canteen_products` (`id`) ON DELETE CASCADE,
  INDEX `idx_batch_product` (`product_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Cashier Shifts & POS Transactions
CREATE TABLE IF NOT EXISTS `cashier_shifts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `start_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `end_time` DATETIME NULL,
  `starting_cash` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `expected_cash` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `actual_cash` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `cash_difference` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_cash` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_qris` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_sales` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('open', 'closed') NOT NULL DEFAULT 'open',
  `notes` TEXT NULL,
  CONSTRAINT `fk_shift_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pos_transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `shift_id` INT NULL,
  `invoice_no` VARCHAR(50) NOT NULL UNIQUE,
  `user_id` INT NOT NULL,
  `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `gross_profit` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` ENUM('cash', 'qris') NOT NULL DEFAULT 'cash',
  `cash_paid` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `change_returned` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `customer_name` VARCHAR(100) NOT NULL DEFAULT 'Umum / Siswa',
  `is_manual` TINYINT(1) NOT NULL DEFAULT 0,
  `manual_note` VARCHAR(255) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_pos_shift` FOREIGN KEY (`shift_id`) REFERENCES `cashier_shifts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pos_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pos_transaction_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `transaction_id` INT NOT NULL,
  `product_id` INT NULL,
  `product_name` VARCHAR(150) NOT NULL,
  `cost_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `selling_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `quantity` INT NOT NULL DEFAULT 1,
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `subtotal_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  CONSTRAINT `fk_item_tx` FOREIGN KEY (`transaction_id`) REFERENCES `pos_transactions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_item_prod` FOREIGN KEY (`product_id`) REFERENCES `canteen_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Registrations (Pendaftaran Siswa dari Guest & Admin)
CREATE TABLE IF NOT EXISTS `registrations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `registration_no` VARCHAR(50) NOT NULL UNIQUE,
  `period_id` INT NULL,
  `period_name` VARCHAR(100) NOT NULL,
  `student_name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `time_slot` VARCHAR(50) NOT NULL,
  `programs_json` TEXT NOT NULL,
  `total_fee` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `registered_by` ENUM('guest', 'admin') NOT NULL DEFAULT 'guest',
  `admin_notes` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_status` (`status`),
  KEY `idx_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. Master Data Siswa Aktif
CREATE TABLE IF NOT EXISTS `students` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `student_no`       VARCHAR(30)  NOT NULL UNIQUE COMMENT 'SIS-YYYYMMDD-NNNN',
  `registration_id`  INT          NULL,
  `full_name`        VARCHAR(150) NOT NULL,
  `phone`            VARCHAR(30)  NOT NULL,
  `city`             VARCHAR(100) NOT NULL DEFAULT '',
  `gender`           ENUM('L','P','') NOT NULL DEFAULT '',
  `birth_date`       DATE         NULL,
  `period_name`      VARCHAR(100) NOT NULL DEFAULT '',
  `time_slot`        VARCHAR(50)  NOT NULL DEFAULT '',
  `programs_json`    TEXT         NOT NULL,
  `total_fee`        DECIMAL(12,2) NOT NULL DEFAULT 0,
  `paid_amount`      DECIMAL(12,2) NOT NULL DEFAULT 0,
  `payment_status`   ENUM('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
  `payment_method`   ENUM('tunai','qris','transfer') NOT NULL DEFAULT 'tunai',
  `status`           ENUM('active','graduated','dropped') NOT NULL DEFAULT 'active',
  `notes`            TEXT         NULL,
  `enrolled_at`      DATE         NOT NULL,
  `created_at`       DATETIME     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_status`   (`status`),
  KEY `idx_name`     (`full_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. Kelulusan Siswa & Cetak Sertifikat
CREATE TABLE IF NOT EXISTS `student_graduations` (
  `id`                INT AUTO_INCREMENT PRIMARY KEY,
  `student_id`        INT          NOT NULL,
  `certificate_no`    VARCHAR(60)  NOT NULL UNIQUE COMMENT 'NNNNN/MPU/C.<KODE>/<BULAN_ROMAWI>/<TAHUN>',
  `program_title`     VARCHAR(150) NOT NULL,
  `program_code`      VARCHAR(30)  NOT NULL DEFAULT '',
  `period_name`       VARCHAR(100) NOT NULL DEFAULT '',
  `graduated_at`      DATE         NOT NULL,
  `final_score`       DECIMAL(5,2) NULL COMMENT 'Tidak dipakai lagi (nilai ujian kini berupa kategori predikat)',
  `grade`             VARCHAR(10)  NULL COMMENT 'EXCELLENT/VERY GOOD/GOOD/AVERAGE/POOR',
  `notes`             TEXT         NULL COMMENT 'Tidak dipakai lagi',
  `created_by`        INT          NULL,
  `created_at`        DATETIME     DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_grad_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13b. Expense Categories (Master Kategori Pengeluaran Operasional)
CREATE TABLE IF NOT EXISTS `expense_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(80) NOT NULL UNIQUE,
  `description` VARCHAR(255) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13c. Expenses (Input Pengeluaran Operasional)
CREATE TABLE IF NOT EXISTS `expenses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `expense_date` DATE NOT NULL,
  `category_id` INT NULL,
  `description` VARCHAR(255) NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` ENUM('tunai','qris','transfer') NOT NULL DEFAULT 'tunai',
  `notes` TEXT NULL,
  `created_by` INT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_expense_date` (`expense_date`),
  KEY `idx_expense_cat` (`category_id`),
  CONSTRAINT `fk_expense_cat` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_expense_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. Role Permissions (Matrik Hak Akses Menu per Role)
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `role` ENUM('owner', 'admin', 'cashier') NOT NULL,
  `menu_key` VARCHAR(50) NOT NULL,
  `allowed` TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY `uk_role_key` (`role`, `menu_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 15. User Permissions (Override hak akses menu per user; menimpa matrik role bila diisi)
CREATE TABLE IF NOT EXISTS `user_permissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `menu_key` VARCHAR(50) NOT NULL,
  `allowed` TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY `uk_user_key` (`user_id`, `menu_key`),
  CONSTRAINT `fk_userperm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default permission:
-- Administrator: semua menu (termasuk Hak Akses); Kasir: hanya POS. Owner selalu penuh (tidak disimpan).
INSERT INTO `role_permissions` (`role`, `menu_key`, `allowed`) VALUES
('admin', 'dashboard', 1),
('admin', 'registrations', 1),
('admin', 'students', 1),
('admin', 'graduates', 1),
('admin', 'programs', 1),
('admin', 'schedules', 1),
('admin', 'attendance', 1),
('admin', 'payroll', 1),
('admin', 'tutors', 1),
('admin', 'staff_office', 1),
('admin', 'rooms', 1),
('admin', 'periods', 1),
('admin', 'canteen', 1),
('admin', 'canteen_opname', 1),
('admin', 'canteen_report', 1),
('admin', 'canteen_barcode', 1),
('admin', 'pos', 1),
('admin', 'reports_students', 1),
('admin', 'reports_finance', 1),
('admin', 'reports_combined', 1),
('admin', 'slip_report', 1),
('admin', 'settings', 1),
('admin', 'users', 1),
('admin', 'permissions', 1),
('admin', 'expenses', 1),
('admin', 'expenses_report', 1),
('cashier', 'pos', 1);

-- Kategori pengeluaran operasional default
INSERT INTO `expense_categories` (`name`, `description`) VALUES
('Listrik & Air', 'Tagihan listrik dan air'),
('Internet & Telepon', 'WiFi, pulsa, dan komunikasi'),
('Gaji & Honor', 'Gaji staff, honor tutor, dan upah'),
('Sewa Tempat', 'Sewa gedung, ruangan, atau kos'),
('ATK & Operasional', 'Alat tulis, cetak, dan kebutuhan kantor'),
('Transportasi', 'BBM, parkir, dan perjalanan'),
('Perawatan & Kebersihan', 'Servis, perbaikan, dan kebersihan'),
('Lain-lain', 'Pengeluaran operasional lainnya');

-- Tabel Saldo Bulanan Kantin (Pepsi Food & Drink - Sistem Berlanjut)
CREATE TABLE IF NOT EXISTS `canteen_monthly_balances` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `year_month` VARCHAR(7) NOT NULL UNIQUE,
    `manual_opening_balance` DECIMAL(15,2) NULL,
    `notes` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_canteen_ym` (`year_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

