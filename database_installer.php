<?php
/**
 * Database Migration & Seeder for MR.PEPSI UPGRADE
 * Native Clean MVC System
 */

$host = '127.0.0.1';
$port = '3306';
$user = 'root';
$pass = '';
$dbname = 'mrpepsi_db';

try {
    $pdo = new PDO("mysql:host=$host;port=$port", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);

    echo "Connected to MySQL server.\n";

    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database `$dbname` ensured.\n";

    $pdo->exec("USE `$dbname`");

    // Table: users
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `role` ENUM('owner', 'admin', 'cashier') NOT NULL DEFAULT 'admin',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Table: settings
    $pdo->exec("CREATE TABLE IF NOT EXISTS `settings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `setting_key` VARCHAR(50) NOT NULL UNIQUE,
        `setting_value` TEXT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Table: periods
    $pdo->exec("CREATE TABLE IF NOT EXISTS `periods` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `period_day` INT NOT NULL DEFAULT 10,
        `start_date` DATE NOT NULL,
        `end_date` DATE NOT NULL,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Table: tutors
    $pdo->exec("CREATE TABLE IF NOT EXISTS `tutors` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `phone` VARCHAR(30) NULL,
        `specialization` VARCHAR(100) NULL,
        `fee_per_session` DECIMAL(12,2) NOT NULL DEFAULT 40000.00,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Table: staff_offices (Karyawan Kantor)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `staff_offices` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `position` VARCHAR(100) NULL,
        `phone` VARCHAR(30) NULL,
        `email` VARCHAR(120) NULL,
        `salary` DECIMAL(12,2) NOT NULL DEFAULT 0,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Table: rooms
    $pdo->exec("CREATE TABLE IF NOT EXISTS `rooms` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(50) NOT NULL UNIQUE,
        `capacity` INT NOT NULL DEFAULT 15,
        `area_type` ENUM('indoor','outdoor') NOT NULL DEFAULT 'indoor',
        `description` VARCHAR(255) NULL,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Table: room_inventories (Daftar Inventaris Barang per Ruangan)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `room_inventories` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `room_id` INT NOT NULL,
        `item_name` VARCHAR(150) NOT NULL,
        `quantity` INT NOT NULL DEFAULT 1,
        `condition_status` ENUM('good','minor_damage','major_damage') NOT NULL DEFAULT 'good',
        `notes` VARCHAR(255) NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT `fk_inv_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Table: program_categories
    $pdo->exec("CREATE TABLE IF NOT EXISTS `program_categories` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(50) NOT NULL UNIQUE,
        `report_group` VARCHAR(20) NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Table: programs
    $pdo->exec("CREATE TABLE IF NOT EXISTS `programs` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Table: schedules
    $pdo->exec("CREATE TABLE IF NOT EXISTS `schedules` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Table: attendances (presensi harian & substitusi)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `attendances` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Table: canteen_categories
    $pdo->exec("CREATE TABLE IF NOT EXISTS `canteen_categories` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(50) NOT NULL UNIQUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Table: canteen_products
    $pdo->exec("CREATE TABLE IF NOT EXISTS `canteen_products` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Table: canteen_stock_logs
    $pdo->exec("CREATE TABLE IF NOT EXISTS `canteen_stock_logs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `product_id` INT NOT NULL,
        `type` ENUM('in', 'out', 'opname_adjustment', 'sale') NOT NULL,
        `quantity` INT NOT NULL,
        `old_stock` INT NOT NULL,
        `new_stock` INT NOT NULL,
        `notes` VARCHAR(255) NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT `fk_stock_prod` FOREIGN KEY (`product_id`) REFERENCES `canteen_products` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Table: canteen_stock_batches (FIFO)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `canteen_stock_batches` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Table: cashier_shifts
    $pdo->exec("CREATE TABLE IF NOT EXISTS `cashier_shifts` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Table: pos_transactions
    $pdo->exec("CREATE TABLE IF NOT EXISTS `pos_transactions` (
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
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT `fk_pos_shift` FOREIGN KEY (`shift_id`) REFERENCES `cashier_shifts` (`id`) ON DELETE SET NULL,
        CONSTRAINT `fk_pos_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Table: pos_transaction_items
    $pdo->exec("CREATE TABLE IF NOT EXISTS `pos_transaction_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `transaction_id` INT NOT NULL,
        `product_id` INT NOT NULL,
        `product_name` VARCHAR(150) NOT NULL,
        `cost_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        `selling_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        `quantity` INT NOT NULL DEFAULT 1,
        `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        `subtotal_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        CONSTRAINT `fk_item_tx` FOREIGN KEY (`transaction_id`) REFERENCES `pos_transactions` (`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_item_prod` FOREIGN KEY (`product_id`) REFERENCES `canteen_products` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Table: role_permissions (Matrik Hak Akses Menu per Role)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `role_permissions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `role` ENUM('owner', 'admin', 'cashier') NOT NULL,
        `menu_key` VARCHAR(50) NOT NULL,
        `allowed` TINYINT(1) NOT NULL DEFAULT 0,
        UNIQUE KEY `uk_role_key` (`role`, `menu_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Table: user_permissions (Override hak akses menu per user)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `user_permissions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `menu_key` VARCHAR(50) NOT NULL,
        `allowed` TINYINT(1) NOT NULL DEFAULT 0,
        UNIQUE KEY `uk_user_key` (`user_id`, `menu_key`),
        CONSTRAINT `fk_userperm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Table: expense_categories (Master Kategori Pengeluaran Operasional)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `expense_categories` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(80) NOT NULL UNIQUE,
        `description` VARCHAR(255) NULL,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Table: expenses (Input Pengeluaran Operasional)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `expenses` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Table: payrolls (Penggajian Manual Tutor & Karyawan)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `payrolls` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `payroll_type` ENUM('tutor','staff') NOT NULL DEFAULT 'tutor',
        `tutor_id` INT NULL,
        `staff_id` INT NULL,
        `recipient_name` VARCHAR(150) NOT NULL,
        `recipient_role` VARCHAR(100) NULL,
        `is_kids` TINYINT(1) NOT NULL DEFAULT 0,
        `period_id` INT NULL,
        `payment_date` DATE NOT NULL,
        `basic_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        `bonus_reward` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        `overtime` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        `deduction` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        `payment_method` ENUM('tunai','transfer','qris') NOT NULL DEFAULT 'tunai',
        `notes` TEXT NULL,
        `expense_id` INT NULL,
        `created_by` INT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY `idx_payroll_type` (`payroll_type`),
        KEY `idx_payroll_period` (`period_id`),
        KEY `idx_payroll_date` (`payment_date`),
        KEY `idx_payroll_expense` (`expense_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    echo "All tables verified and created successfully.\n";

    // SEEDING DATA
    // 1. Users (4 akun default) + hak akses per-user
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `users`");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $pass = password_hash('pepsi123', PASSWORD_BCRYPT);
        $insertUser = $pdo->prepare("INSERT INTO `users` (`name`, `username`, `password`, `role`) VALUES (?, ?, ?, ?)");

        $insertUser->execute(['Fipsi Endrawan', 'fipsi', $pass, 'owner']);
        $insertUser->execute(['Mita Khasanah', 'mita', $pass, 'owner']);
        $insertUser->execute(['Desy Kurnia Anggreini', 'desy', $pass, 'admin']);
        $desyId = (int)$pdo->lastInsertId();
        $insertUser->execute(['Triana Agnes Damayanti', 'triana', $pass, 'cashier']);
        $trianaId = (int)$pdo->lastInsertId();

        // Override hak akses per user (menimpa default role)
        $insertPerm = $pdo->prepare("INSERT INTO `user_permissions` (`user_id`, `menu_key`, `allowed`) VALUES (?, ?, 1)");
        foreach (['registrations', 'students', 'graduates'] as $k) {
            $insertPerm->execute([$desyId, $k]); // Desy: pendaftaran + rekam pembayaran + cetak sertifikat
        }
        foreach (['registrations', 'pos', 'canteen_opname', 'canteen_report', 'staff_office'] as $k) {
            $insertPerm->execute([$trianaId, $k]); // Triana: pendaftaran, kasir POS, opname, laba rugi, staff office
        }

        echo "Default users seeded (fipsi, mita, desy, triana) dengan hak akses per-user.\n";
    }

    // 1b. Staff Office default (Desy & Triana)
    $stmtSO = $pdo->prepare("SELECT COUNT(*) FROM `staff_offices`");
    $stmtSO->execute();
    if ($stmtSO->fetchColumn() == 0) {
        $insSO = $pdo->prepare("INSERT INTO `staff_offices` (`name`, `position`, `salary`, `is_active`) VALUES (?, ?, 0, 1)");
        $insSO->execute(['Desy Kurnia Anggreini', 'Staff Administrasi']);
        $insSO->execute(['Triana Agnes Damayanti', 'Kasir & Staff Office']);
        echo "Staff Office default seeded (desy, triana).\n";
    }

    // 1c. Kategori pengeluaran operasional default
    $stmtEC = $pdo->prepare("SELECT COUNT(*) FROM `expense_categories`");
    $stmtEC->execute();
    if ($stmtEC->fetchColumn() == 0) {
        $insEC = $pdo->prepare("INSERT IGNORE INTO `expense_categories` (`name`, `description`) VALUES (?, ?)");
        foreach ([
            ['Listrik & Air', 'Tagihan listrik dan air'],
            ['Internet & Telepon', 'WiFi, pulsa, dan komunikasi'],
            ['Gaji & Honor', 'Gaji staff, honor tutor, dan upah'],
            ['Sewa Tempat', 'Sewa gedung, ruangan, atau kos'],
            ['ATK & Operasional', 'Alat tulis, cetak, dan kebutuhan kantor'],
            ['Transportasi', 'BBM, parkir, dan perjalanan'],
            ['Perawatan & Kebersihan', 'Servis, perbaikan, dan kebersihan'],
            ['Lain-lain', 'Pengeluaran operasional lainnya'],
        ] as $ec) {
            $insEC->execute($ec);
        }
        echo "Kategori pengeluaran default seeded.\n";
    }

    // Matrik hak akses role (admin = semua menu, cashier = POS saja)
    $menuKeys = array_keys(require __DIR__ . '/config/menus.php');
    $rpStmt = $pdo->prepare("INSERT IGNORE INTO `role_permissions` (`role`, `menu_key`, `allowed`) VALUES (?, ?, ?)");
    foreach ($menuKeys as $k) {
        $rpStmt->execute(['admin', $k, 1]);
        $rpStmt->execute(['cashier', $k, $k === 'pos' ? 1 : 0]);
    }

    // 2. Settings
    $settings = [
        'app_name' => 'MR.PEPSI UPGRADE',
        'tagline' => 'Kursus Speaking & IELTS di Kampung Inggris Pare',
        'admin_wa' => '6285648077008',
        'address' => 'Jl. Kemuning, Tulungrejo, Kode Pos 64212, Pare, Kediri, Jawa Timur',
        'instagram' => '@mrpepsiofficial01',
        'email' => 'mrpepsikampunginggris@gmail.com',
        'sk_diknas' => 'SK Diknas No. 421.9/3735/418.20/2026',
        'npsn' => 'NPSN K9997239',
        'default_tutor_fee' => '40000',
        'certificate_format' => '{seq}/MPU/C.{code}/{rm}/{Y}',
        'certificate_seq_digits' => '5',
        'pos_receipt_mode' => 'ask',
        'office_hours' => "Senin - Jumat: 07.00 - 16.00 WIB\nSabtu: 08.00 - 14.00 WIB\nIstirahat: 12.00 - 13.00 WIB",
        'class_rules' => "1. Hadir 10 menit sebelum kelas dimulai.\n2. Wajib menggunakan Bahasa Inggris sesuai target rasio kelas.\n3. Ujian akhir wajib diikuti untuk sertifikat kelulusan.\n4. Kelas reschedule hanya dapat dilakukan melalui persetujuan admin."
    ];
    $stmt = $pdo->prepare("INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES (?, ?)");
    foreach ($settings as $k => $v) {
        $stmt->execute([$k, $v]);
    }
    echo "Settings seeded.\n";

    // 3. Rooms
    $rooms = [
        ['Alpha', 'indoor'],
        ['Bravo', 'indoor'],
        ['Charlie', 'indoor'],
        ['Deluxe', 'indoor'],
        ['Gazebo', 'outdoor'],
        ['Romeo', 'indoor'],
    ];
    $stmt = $pdo->prepare("INSERT IGNORE INTO `rooms` (`name`, `capacity`, `area_type`, `description`) VALUES (?, ?, ?, ?)");
    foreach ($rooms as $r) {
        $stmt->execute([$r[0], 15, $r[1], "Ruang {$r[0]} nyaman"]);
    }
    echo "Rooms seeded.\n";

    // 4. Tutors
    $coaches = [
        ['name' => 'Aprul', 'specialization' => 'Speaking Basic & Confidence', 'fee' => 40000],
        ['name' => 'Arul', 'specialization' => 'IELTS Mock Up & Speaking', 'fee' => 45000],
        ['name' => 'Annisa', 'specialization' => 'Impromptu & Fluency', 'fee' => 40000],
        ['name' => 'Khansa', 'specialization' => 'Grammar for Speaking', 'fee' => 40000],
        ['name' => 'Isna', 'specialization' => 'Snap Chat & Modern Tests', 'fee' => 42000],
        ['name' => 'Clarissa', 'specialization' => 'Public Speaking & Presentation', 'fee' => 42000],
        ['name' => 'Nabila', 'specialization' => 'Active Speaking & Debates', 'fee' => 42000],
        ['name' => 'Pepsi', 'specialization' => 'Head Coach - IELTS Specialist', 'fee' => 50000],
        ['name' => 'Dhani', 'specialization' => 'IELTS General Training', 'fee' => 45000],
        ['name' => 'Anugrah', 'specialization' => 'Job Interview & Career English', 'fee' => 45000],
    ];
    $stmt = $pdo->prepare("INSERT IGNORE INTO `tutors` (`name`, `phone`, `specialization`, `fee_per_session`) VALUES (?, ?, ?, ?)");
    foreach ($coaches as $c) {
        $stmt->execute([$c['name'], '0856' . rand(1000000, 9999999), $c['specialization'], $c['fee']]);
    }
    echo "Tutors seeded.\n";

    // 5. Periods
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `periods`");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $currentMonth = date('F Y');
        $nextMonth = date('F Y', strtotime('+1 month'));
        $p1 = [
            'name' => "Periode 10 $currentMonth",
            'period_day' => 10,
            'start_date' => date('Y-m-10'),
            'end_date' => date('Y-m-24'),
            'is_active' => 1
        ];
        $p2 = [
            'name' => "Periode 25 $currentMonth",
            'period_day' => 25,
            'start_date' => date('Y-m-25'),
            'end_date' => date('Y-m-t'),
            'is_active' => 1
        ];
        $pIns = $pdo->prepare("INSERT INTO `periods` (`name`, `period_day`, `start_date`, `end_date`, `is_active`) VALUES (?, ?, ?, ?, ?)");
        $pIns->execute([$p1['name'], $p1['period_day'], $p1['start_date'], $p1['end_date'], $p1['is_active']]);
        $pIns->execute([$p2['name'], $p2['period_day'], $p2['start_date'], $p2['end_date'], $p2['is_active']]);
        echo "Periods seeded.\n";
    }

    // 6. Programs from index.html
    $programs = [
        [
            'code' => 'LT1',
            'title' => "Let's Talk 1",
            'level_category' => 'basic',
            'orientation' => 'speaking',
            'level_label' => 'Basic Level',
            'price' => 150000,
            'english_ratio' => '30%',
            'description' => 'Membangun pondasi percakapan harian awal dengan rasa percaya diri penuh.',
            'syllabus' => json_encode([
                'Basic Introduction & Small Talk',
                'Daily Routine Vocabulary',
                'Asking for & Giving Directions',
                'Expressing Preferences & Likes'
            ]),
            'final_exam' => 'Face to Face Topics - Question & Verb Card',
            'is_new' => 0
        ],
        [
            'code' => 'ST',
            'title' => 'Starter',
            'level_category' => 'pre-intermediate',
            'orientation' => 'speaking',
            'level_label' => 'Pre-Intermediate',
            'price' => 150000,
            'english_ratio' => '50%',
            'description' => 'Memperluas kosa kata dan transisi menuju percakapan Bahasa Inggris yang lebih alami.',
            'syllabus' => json_encode([
                'Past Experience Storytelling',
                'Opinion Exchange on Daily Topics',
                'Handling Common Social Situations',
                'Sentence Structure & Fluency Drills'
            ]),
            'final_exam' => 'Question and Answer Card',
            'is_new' => 0
        ],
        [
            'code' => 'IP',
            'title' => 'Impromptu',
            'level_category' => 'intermediate',
            'orientation' => 'speaking',
            'level_label' => 'Intermediate',
            'price' => 160000,
            'english_ratio' => '50%',
            'description' => 'Melatih kemampuan berpikir cepat dan berbicara secara spontan tanpa teks.',
            'syllabus' => json_encode([
                'Spontaneous Topic Presentation',
                'Mind Mapping under Time Constraint',
                'Overcoming Hesitation & Fillers',
                'Structure of Quick Speech'
            ]),
            'final_exam' => 'Presentation Card (Sudden Topics)',
            'is_new' => 0
        ],
        [
            'code' => 'GFS1',
            'title' => 'Grammar for Speaking 1',
            'level_category' => 'intermediate',
            'orientation' => 'speaking',
            'level_label' => 'Intermediate',
            'price' => 150000,
            'english_ratio' => '50%',
            'description' => 'Menyelaraskan tata bahasa langsung ke dalam penerapan percakapan lisan.',
            'syllabus' => json_encode([
                'Tenses Application in Speech',
                'Modal Verbs for Politeness',
                'Conditional Sentences in Practice',
                'Real-time Grammar Correction'
            ]),
            'final_exam' => 'Online Test & Oral Grammar - Real Time Check',
            'is_new' => 0
        ],
        [
            'code' => 'LT2',
            'title' => "Let's Talk 2",
            'level_category' => 'intermediate',
            'orientation' => 'speaking',
            'level_label' => 'Intermediate',
            'price' => 150000,
            'english_ratio' => '50%',
            'description' => 'Pendalaman komunikasi aktif dengan topik-topik sosial yang lebih beragam.',
            'syllabus' => json_encode([
                'Group Discussions & Arguments',
                'Describing Complex Experiences',
                'Interviews & Questioning Techniques',
                'Idiomatic Expressions'
            ]),
            'final_exam' => 'Interview Review Unit - Topic Card',
            'is_new' => 0
        ],
        [
            'code' => 'SV',
            'title' => 'Swap Vocab',
            'level_category' => 'intermediate',
            'orientation' => 'speaking',
            'level_label' => 'Intermediate',
            'price' => 150000,
            'english_ratio' => '50%',
            'description' => 'Memperkaya variasi kosakata dan sinonim untuk ekspresi yang berkelas.',
            'syllabus' => json_encode([
                'Vocabulary Expansion & Synonyms',
                'Collocations & Phrasal Verbs',
                'Contextual Storytelling',
                'Word Swap Games & Drills'
            ]),
            'final_exam' => 'Story Telling - Vocab Card',
            'is_new' => 0
        ],
        [
            'code' => 'GFS2',
            'title' => 'Grammar for Speaking 2',
            'level_category' => 'pre-advanced',
            'orientation' => 'speaking',
            'level_label' => 'Pre-Advanced',
            'price' => 160000,
            'english_ratio' => '70%',
            'description' => 'Tingkat mahir tata bahasa lisan untuk diskusi akademis dan formal.',
            'syllabus' => json_encode([
                'Complex Tenses & Relative Clauses',
                'Passive Voice in Speech',
                'Subjunctive & Advanced Structures',
                'Connected Speech Precision'
            ]),
            'final_exam' => 'Tenses Text Observation - Connected Check',
            'is_new' => 0
        ],
        [
            'code' => 'SC',
            'title' => 'Snap Chat',
            'level_category' => 'pre-advanced',
            'orientation' => 'speaking',
            'level_label' => 'Pre-Advanced',
            'price' => 170000,
            'english_ratio' => '70%',
            'description' => 'Program adaptif baru termasuk pengenalan tes modern PTE & Duolingo.',
            'syllabus' => json_encode([
                'Rapid Response Brain Mapping',
                'Intro to PTE & Duolingo Speaking Formats',
                'Interactive Speed Presentations',
                'Fluency & Pronunciation Tuning'
            ]),
            'final_exam' => 'Brain Mapping - Presentation (PTE & Duolingo intro)',
            'is_new' => 1
        ],
        [
            'code' => 'SS',
            'title' => 'Smash Speak',
            'level_category' => 'pre-advanced',
            'orientation' => 'speaking',
            'level_label' => 'Pre-Advanced',
            'price' => 170000,
            'english_ratio' => '70%',
            'description' => 'Melatih kemampuan public speaking mendalam mengenai isu-isu hangat terkini.',
            'syllabus' => json_encode([
                'Current Affairs Analysis',
                'Persuasive Speech Techniques',
                'Public Speaking Delivery & Gestures',
                'Handling Q&A Sessions'
            ]),
            'final_exam' => 'Presentation on Latest Issues',
            'is_new' => 0
        ],
        [
            'code' => 'AS',
            'title' => 'Active Speaking',
            'level_category' => 'pre-advanced',
            'orientation' => 'speaking',
            'level_label' => 'Pre-Advanced',
            'price' => 170000,
            'english_ratio' => '70%',
            'description' => 'Diskusi kritis isu global & lokal untuk mempertajam kelancaran komunikasi.',
            'syllabus' => json_encode([
                'Global & Local Debate Topics',
                'Critical Thinking & Argument Building',
                'Expressing Nuanced Opinions',
                'Active Listening & Rebuttals'
            ]),
            'final_exam' => 'Global & Local Issues - Topics Card',
            'is_new' => 0
        ],
        [
            'code' => 'SFI-V',
            'title' => 'Vocabulary for IELTS',
            'level_category' => 'pre-advanced',
            'orientation' => 'ielts',
            'level_label' => 'Pre-Advanced',
            'price' => 170000,
            'english_ratio' => '80%',
            'description' => 'Penguasaan kosakata khusus bertema IELTS untuk menaikkan band score.',
            'syllabus' => json_encode([
                'IELTS Topic-based Vocabulary (Education, Tech, Environment)',
                'Academic & Collocational Lexicon',
                'Avoiding Repetition in Speaking',
                'Band 7.0+ Vocabulary Upgrades'
            ]),
            'final_exam' => 'Mini IELTS Simulation (Vocabulary Check)',
            'is_new' => 0
        ],
        [
            'code' => 'SFI-GT',
            'title' => 'Speaking for IELTS (General Training)',
            'level_category' => 'pre-advanced',
            'orientation' => 'ielts',
            'level_label' => 'Pre-Advanced',
            'price' => 170000,
            'english_ratio' => '80%',
            'description' => 'Persiapan simulasi tes IELTS Speaking untuk jalur General Training.',
            'syllabus' => json_encode([
                'IELTS Speaking Part 1 Strategies',
                'Part 2 Cue Card Long Turn Masterclass',
                'Part 3 Discussion & Elaboration',
                'Fluency, Coherence, & Lexical Resource'
            ]),
            'final_exam' => 'Simulation Part 1, 2, and 3',
            'is_new' => 0
        ],
        [
            'code' => 'JI',
            'title' => 'Job Interview',
            'level_category' => 'advanced',
            'orientation' => 'speaking',
            'level_label' => 'Advanced Level',
            'price' => 180000,
            'english_ratio' => '80%',
            'description' => 'Persiapan wawancara kerja profesional & Focus Group Discussion (FGD).',
            'syllabus' => json_encode([
                'Behavioral & STAR Method Interview Questions',
                'FGD (Focus Group Discussion) Tactics',
                'Professional Pitching & Self Intro',
                'Salary Negotiation & Professional Etiquette'
            ]),
            'final_exam' => 'USER & FGD - Real Interview Simulation',
            'is_new' => 0
        ],
        [
            'code' => 'SFI-AC',
            'title' => 'Speaking for IELTS (Academics)',
            'level_category' => 'advanced',
            'orientation' => 'ielts',
            'level_label' => 'Advanced Level',
            'price' => 180000,
            'english_ratio' => '80%',
            'description' => 'Penggemblengan IELTS Speaking khusus kebutuhan beasiswa & studi luar negeri.',
            'syllabus' => json_encode([
                'High-level Academic Discussion',
                'Coherence & Cohesion under Exam Conditions',
                'Grammatical Range & Accuracy Drills',
                'Pronunciation & Intonation Mastery'
            ]),
            'final_exam' => 'Simulation Part 1, 2, and 3',
            'is_new' => 0
        ],
        [
            'code' => 'MSFI',
            'title' => 'Mock Up - Speaking for IELTS',
            'level_category' => 'advanced',
            'orientation' => 'ielts',
            'level_label' => 'Advanced Level',
            'price' => 180000,
            'english_ratio' => '80%',
            'description' => 'Simulasi ujian IELTS Speaking intensif dengan penilaian standar kriteria asli.',
            'syllabus' => json_encode([
                'Real Exam Room Atmosphere Practice',
                'Individual Scoring & Detailed Diagnostic Feedback',
                'Time Management on Cue Cards',
                'Score Maximization Tips'
            ]),
            'final_exam' => 'Full Mock Simulation Part 1, 2, and 3',
            'is_new' => 1
        ]
    ];

    // Seed Program Categories (dengan grup laporan)
    $colReportGroup = $pdo->query("SHOW COLUMNS FROM `program_categories` LIKE 'report_group'")->fetchAll();
    if (empty($colReportGroup)) {
        $pdo->exec("ALTER TABLE `program_categories` ADD COLUMN `report_group` VARCHAR(20) NULL AFTER `name`");
    }
    $stmtProgCat = $pdo->prepare("INSERT IGNORE INTO `program_categories` (`name`, `report_group`) VALUES (?, ?)");
    foreach ([
        ['PEPSI OFFLINE', 'offline'],
        ['PEPSI ONLINE CLASS', 'online'],
        ['PEPSI KIDS', 'offline'],
        ['NON KELAS', 'non_kelas'],
    ] as $progCat) {
        $stmtProgCat->execute($progCat);
    }
    $pdo->exec("UPDATE `program_categories` SET `report_group`='offline' WHERE `name`='PEPSI OFFLINE'");
    $pdo->exec("UPDATE `program_categories` SET `report_group`='online' WHERE `name`='PEPSI ONLINE CLASS'");
    $pdo->exec("UPDATE `program_categories` SET `report_group`='offline' WHERE `name`='PEPSI KIDS'");
    $pdo->exec("UPDATE `program_categories` SET `report_group`='non_kelas' WHERE `name`='NON KELAS'");

    $stmtProg = $pdo->prepare("INSERT INTO `programs` 
        (`code`, `title`, `level_category`, `orientation`, `level_label`, `price`, `english_ratio`, `description`, `syllabus`, `final_exam`, `is_new`) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE `title`=VALUES(`title`), `price`=VALUES(`price`), `description`=VALUES(`description`), `syllabus`=VALUES(`syllabus`)");
    foreach ($programs as $p) {
        $stmtProg->execute([
            $p['code'], $p['title'], $p['level_category'], $p['orientation'], $p['level_label'],
            $p['price'], $p['english_ratio'], $p['description'], $p['syllabus'], $p['final_exam'], $p['is_new']
        ]);
    }
    echo "Programs seeded.\n";

    // Backfill kategori default (Kelas Offline) untuk program lama/tanpa kategori
    $pdo->exec("UPDATE `programs` SET `category_id` = (SELECT `id` FROM `program_categories` WHERE `name`='PEPSI OFFLINE' LIMIT 1) WHERE `category_id` IS NULL");

    // 7. Seed Schedules from index.html
    // Fetch lookup maps
    $periodId = $pdo->query("SELECT id FROM periods ORDER BY id ASC LIMIT 1")->fetchColumn();
    $progMap = $pdo->query("SELECT code, id FROM programs")->fetchAll(PDO::FETCH_KEY_PAIR);
    $roomMap = $pdo->query("SELECT name, id FROM rooms")->fetchAll(PDO::FETCH_KEY_PAIR);
    $tutorMap = $pdo->query("SELECT name, id FROM tutors")->fetchAll(PDO::FETCH_KEY_PAIR);

    $schedulesRaw = [
        ['time' => '07.00-08.30', 'code' => 'LT1', 'tutor' => 'Aprul', 'room' => 'Alpha'],
        ['time' => '07.00-08.30', 'code' => 'ST', 'tutor' => 'Arul', 'room' => 'Bravo'],
        ['time' => '08.30-10.00', 'code' => 'IP', 'tutor' => 'Annisa', 'room' => 'Charlie'],
        ['time' => '08.30-10.00', 'code' => 'GFS1', 'tutor' => 'Khansa', 'room' => 'Deluxe'],
        ['time' => '10.00-11.30', 'code' => 'SC', 'tutor' => 'Isna', 'room' => 'Gazebo'],
        ['time' => '10.00-11.30', 'code' => 'SS', 'tutor' => 'Clarissa', 'room' => 'Romeo'],
        ['time' => '13.00-14.30', 'code' => 'AS', 'tutor' => 'Nabila', 'room' => 'Alpha'],
        ['time' => '13.00-14.30', 'code' => 'SFI-V', 'tutor' => 'Pepsi', 'room' => 'Bravo'],
        ['time' => '14.30-16.00', 'code' => 'SFI-GT', 'tutor' => 'Dhani', 'room' => 'Charlie'],
        ['time' => '14.30-16.00', 'code' => 'JI', 'tutor' => 'Anugrah', 'room' => 'Deluxe'],
        ['time' => '16.00-17.30', 'code' => 'SFI-AC', 'tutor' => 'Pepsi', 'room' => 'Romeo'],
        ['time' => '16.00-17.30', 'code' => 'MSFI', 'tutor' => 'Arul', 'room' => 'Deluxe']
    ];

    $stmtSched = $pdo->prepare("INSERT IGNORE INTO `schedules` 
        (`period_id`, `day_name`, `time_slot`, `program_id`, `room_id`, `tutor_id`) 
        VALUES (?, 'Senin - Jumat', ?, ?, ?, ?)");
    foreach ($schedulesRaw as $s) {
        if (isset($progMap[$s['code']], $roomMap[$s['room']], $tutorMap[$s['tutor']])) {
            $stmtSched->execute([
                $periodId,
                $s['time'],
                $progMap[$s['code']],
                $roomMap[$s['room']],
                $tutorMap[$s['tutor']]
            ]);
        }
    }
    echo "Schedules matrix seeded.\n";

    // 8. Seed Canteen Categories & Products
    $categories = ['Minuman Dingin & Panas', 'Makanan Berat & Roti', 'Camilan / Snack', 'Merchandise & Alat Tulis'];
    $stmtCat = $pdo->prepare("INSERT IGNORE INTO `canteen_categories` (`name`) VALUES (?)");
    foreach ($categories as $cat) {
        $stmtCat->execute([$cat]);
    }
    $catMap = $pdo->query("SELECT name, id FROM canteen_categories")->fetchAll(PDO::FETCH_KEY_PAIR);

    $products = [
        ['name' => 'Air Mineral Le Minerale 600ml', 'barcode' => '8999901', 'cat' => 'Minuman Dingin & Panas', 'cost' => 2500, 'price' => 4000, 'stock' => 50],
        ['name' => 'Teh Pucuk Harum 350ml', 'barcode' => '8999902', 'cat' => 'Minuman Dingin & Panas', 'cost' => 2500, 'price' => 4000, 'stock' => 40],
        ['name' => 'Kopi Americano / Cold Brew', 'barcode' => '8999903', 'cat' => 'Minuman Dingin & Panas', 'cost' => 4000, 'price' => 8000, 'stock' => 30],
        ['name' => 'Matcha Latte Ice', 'barcode' => '8999904', 'cat' => 'Minuman Dingin & Panas', 'cost' => 5500, 'price' => 10000, 'stock' => 25],
        ['name' => 'Roti Bakar Coklat Keju', 'barcode' => '8999905', 'cat' => 'Makanan Berat & Roti', 'cost' => 7000, 'price' => 12000, 'stock' => 20],
        ['name' => 'Nasi Goreng Spesial Pare', 'barcode' => '8999906', 'cat' => 'Makanan Berat & Roti', 'cost' => 9000, 'price' => 15000, 'stock' => 18],
        ['name' => 'Mie Nyemek Telur', 'barcode' => '8999907', 'cat' => 'Makanan Berat & Roti', 'cost' => 6000, 'price' => 11000, 'stock' => 25],
        ['name' => 'Keripik Singkong Balado', 'barcode' => '8999908', 'cat' => 'Camilan / Snack', 'cost' => 3000, 'price' => 5000, 'stock' => 45],
        ['name' => 'Basreng Pedas Gurih', 'barcode' => '8999909', 'cat' => 'Camilan / Snack', 'cost' => 3500, 'price' => 6000, 'stock' => 35],
        ['name' => 'Notebook Mr. Pepsi + Pen', 'barcode' => '8999910', 'cat' => 'Merchandise & Alat Tulis', 'cost' => 7000, 'price' => 12000, 'stock' => 30],
    ];

    $stmtProd = $pdo->prepare("INSERT INTO `canteen_products` 
        (`category_id`, `barcode`, `name`, `cost_price`, `selling_price`, `stock`, `min_stock`) 
        VALUES (?, ?, ?, ?, ?, ?, 5)
        ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `cost_price`=VALUES(`cost_price`), `selling_price`=VALUES(`selling_price`)");
    foreach ($products as $pr) {
        $stmtProd->execute([
            $catMap[$pr['cat']] ?? null,
            $pr['barcode'],
            $pr['name'],
            $pr['cost'],
            $pr['price'],
            $pr['stock']
        ]);
    }
    echo "Canteen products seeded.\n";

    // Seed batch FIFO awal untuk tiap produk (hanya bila belum ada batch)
    $batchCheck = $pdo->prepare("SELECT COUNT(*) FROM `canteen_stock_batches` WHERE product_id = ?");
    $batchIns = $pdo->prepare("INSERT INTO `canteen_stock_batches` (`product_id`, `unit_cost`, `qty_in`, `qty_remaining`, `source`, `notes`) VALUES (?, ?, ?, ?, 'initial', 'Stok awal (seeder)')");
    foreach ($pdo->query("SELECT id, cost_price, stock FROM `canteen_products`")->fetchAll(PDO::FETCH_ASSOC) as $pr) {
        $batchCheck->execute([$pr['id']]);
        if ((int)$batchCheck->fetchColumn() > 0) {
            continue;
        }
        $stock = (int)$pr['stock'];
        if ($stock <= 0) {
            continue;
        }
        $batchIns->execute([$pr['id'], $pr['cost_price'], $stock, $stock]);
    }
    echo "Canteen product batches seeded.\n";

    echo "\n=== MIGRATION AND SEEDING COMPLETED SUCCESSFULLY! ===\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
