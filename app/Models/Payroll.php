<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class Payroll extends Model {
    protected string $table = 'payrolls';

    /**
     * Pastikan tabel penggajian tersedia secara otomatis (self-healing).
     */
    public function ensureSchema(): void {
        $this->db->exec("CREATE TABLE IF NOT EXISTS `payrolls` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `payroll_type` ENUM('tutor','staff') NOT NULL DEFAULT 'tutor',
            `tutor_id` INT NULL,
            `staff_id` INT NULL,
            `recipient_name` VARCHAR(150) NOT NULL,
            `recipient_role` VARCHAR(100) NULL,
            `is_kids` TINYINT(1) NOT NULL DEFAULT 0,
            `period_id` INT NULL,
            `salary_month` VARCHAR(20) NULL,
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
            KEY `idx_payroll_month` (`salary_month`),
            KEY `idx_payroll_date` (`payment_date`),
            KEY `idx_payroll_expense` (`expense_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        try {
            $this->db->exec("ALTER TABLE `payrolls` ADD COLUMN `salary_month` VARCHAR(20) NULL AFTER `period_id`");
        } catch (\Throwable $e) {}

        try {
            $this->db->exec("ALTER TABLE `payrolls` ADD COLUMN `is_kids` TINYINT(1) NOT NULL DEFAULT 0 AFTER `recipient_role`");
        } catch (\Throwable $e) {}
    }

    /**
     * Ambil data payroll dengan filter periode, tipe, tanggal, dan pencarian.
     */
    public function getFiltered(array $filters = []): array {
        $this->ensureSchema();
        $sql = "SELECT p.*, per.name AS period_name, per.start_date AS period_start, per.end_date AS period_end,
                       u.name AS creator_name
                FROM `{$this->table}` p
                LEFT JOIN `periods` per ON p.period_id = per.id
                LEFT JOIN `users` u ON p.created_by = u.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['period_id'])) {
            $pId = (int)$filters['period_id'];
            $pStmt = $this->db->prepare("SELECT start_date, end_date FROM periods WHERE id = ? LIMIT 1");
            $pStmt->execute([$pId]);
            $perRow = $pStmt->fetch(PDO::FETCH_ASSOC);

            if ($perRow) {
                $month = substr($perRow['start_date'], 0, 7);
                $sql .= " AND (p.period_id = ? OR ((p.payroll_type = 'staff' OR p.is_kids = 1) AND (p.salary_month = ? OR p.payment_date LIKE ? OR p.payment_date BETWEEN ? AND ?)))";
                $params[] = $pId;
                $params[] = $month;
                $params[] = $month . '%';
                $params[] = $perRow['start_date'];
                $params[] = $perRow['end_date'];
            } else {
                $sql .= " AND (p.period_id = ? OR p.is_kids = 1)";
                $params[] = $pId;
            }
        }

        if (!empty($filters['payroll_type']) && in_array($filters['payroll_type'], ['tutor', 'staff', 'kids'], true)) {
            if ($filters['payroll_type'] === 'kids') {
                $sql .= " AND p.is_kids = 1";
            } elseif ($filters['payroll_type'] === 'tutor') {
                $sql .= " AND p.payroll_type = 'tutor'";
            } else {
                $sql .= " AND p.payroll_type = ?";
                $params[] = $filters['payroll_type'];
            }
        }

        if (!empty($filters['start_date'])) {
            $sql .= " AND p.payment_date >= ?";
            $params[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $sql .= " AND p.payment_date <= ?";
            $params[] = $filters['end_date'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (p.recipient_name LIKE ? OR p.recipient_role LIKE ? OR p.notes LIKE ?)";
            $term = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $sql .= " ORDER BY p.payment_date DESC, p.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ringkasan nominal pengeluaran gaji per periode/filter.
     */
    public function getSummary(array $filters = []): array {
        $this->ensureSchema();
        $sql = "SELECT 
                    COUNT(*) AS total_count,
                    COALESCE(SUM(total_amount), 0) AS grand_total,
                    COALESCE(SUM(basic_salary), 0) AS total_basic,
                    COALESCE(SUM(bonus_reward), 0) AS total_bonus,
                    COALESCE(SUM(overtime), 0) AS total_overtime,
                    COALESCE(SUM(deduction), 0) AS total_deduction,
                    SUM(CASE WHEN payroll_type = 'tutor' THEN 1 ELSE 0 END) AS tutor_count,
                    COALESCE(SUM(CASE WHEN payroll_type = 'tutor' THEN total_amount ELSE 0 END), 0) AS tutor_total,
                    SUM(CASE WHEN is_kids = 1 THEN 1 ELSE 0 END) AS kids_count,
                    COALESCE(SUM(CASE WHEN is_kids = 1 THEN total_amount ELSE 0 END), 0) AS kids_total,
                    SUM(CASE WHEN payroll_type = 'staff' THEN 1 ELSE 0 END) AS staff_count,
                    COALESCE(SUM(CASE WHEN payroll_type = 'staff' THEN total_amount ELSE 0 END), 0) AS staff_total
                FROM `{$this->table}` p
                WHERE 1=1";
        $params = [];

        if (!empty($filters['period_id'])) {
            $pId = (int)$filters['period_id'];
            $pStmt = $this->db->prepare("SELECT start_date, end_date FROM periods WHERE id = ? LIMIT 1");
            $pStmt->execute([$pId]);
            $perRow = $pStmt->fetch(PDO::FETCH_ASSOC);

            if ($perRow) {
                $month = substr($perRow['start_date'], 0, 7);
                $sql .= " AND (p.period_id = ? OR ((p.payroll_type = 'staff' OR p.is_kids = 1) AND (p.salary_month = ? OR p.payment_date LIKE ? OR p.payment_date BETWEEN ? AND ?)))";
                $params[] = $pId;
                $params[] = $month;
                $params[] = $month . '%';
                $params[] = $perRow['start_date'];
                $params[] = $perRow['end_date'];
            } else {
                $sql .= " AND (p.period_id = ? OR p.is_kids = 1)";
                $params[] = $pId;
            }
        }

        if (!empty($filters['payroll_type']) && in_array($filters['payroll_type'], ['tutor', 'staff', 'kids'], true)) {
            if ($filters['payroll_type'] === 'kids') {
                $sql .= " AND p.is_kids = 1";
            } elseif ($filters['payroll_type'] === 'tutor') {
                $sql .= " AND p.payroll_type = 'tutor'";
            } else {
                $sql .= " AND p.payroll_type = ?";
                $params[] = $filters['payroll_type'];
            }
        }

        if (!empty($filters['start_date'])) {
            $sql .= " AND p.payment_date >= ?";
            $params[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $sql .= " AND p.payment_date <= ?";
            $params[] = $filters['end_date'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (p.recipient_name LIKE ? OR p.recipient_role LIKE ? OR p.notes LIKE ?)";
            $term = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        return $res ?: [
            'total_count' => 0,
            'grand_total' => 0,
            'total_basic' => 0,
            'total_bonus' => 0,
            'total_overtime' => 0,
            'total_deduction' => 0,
            'tutor_count' => 0,
            'tutor_total' => 0,
            'staff_count' => 0,
            'staff_total' => 0,
        ];
    }

    /**
     * Ambil 1 data payroll lengkap dengan nama periode, info kontak, dan pembuat.
     */
    public function findWithDetails(int $id): ?array {
        $this->ensureSchema();
        $sql = "SELECT p.*, per.name AS period_name, per.start_date AS period_start, per.end_date AS period_end,
                       u.name AS creator_name,
                       t.phone AS tutor_phone, t.specialization AS tutor_spec,
                       s.phone AS staff_phone, s.email AS staff_email
                FROM `{$this->table}` p
                LEFT JOIN `periods` per ON p.period_id = per.id
                LEFT JOIN `users` u ON p.created_by = u.id
                LEFT JOIN `tutors` t ON p.tutor_id = t.id
                LEFT JOIN `staff_offices` s ON p.staff_id = s.id
                WHERE p.id = ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Sinkronkan data payroll ke tabel `expenses` agar otomatis masuk ke laporan pengeluaran.
     */
    public function syncExpense(int $payrollId, array $data, ?int $existingExpenseId = null): ?int {
        $expenseModel = new Expense();
        $expenseModel->ensureSchema();

        // Cari atau buat kategori "Gaji & Honor"
        $categoryStmt = $this->db->prepare("SELECT id FROM `expense_categories` WHERE `name` LIKE '%Gaji%' OR `name` LIKE '%Honor%' LIMIT 1");
        $categoryStmt->execute();
        $catId = $categoryStmt->fetchColumn();

        if (!$catId) {
            $this->db->prepare("INSERT INTO `expense_categories` (`name`, `description`, `is_active`) VALUES (?, ?, 1)")
                ->execute(['Gaji & Honor', 'Gaji staff kantor dan honor tutor']);
            $catId = (int)$this->db->lastInsertId();
        }

        // Nama periode jika ada
        $periodName = '';
        if (!empty($data['period_id'])) {
            $pStmt = $this->db->prepare("SELECT name FROM `periods` WHERE `id` = ?");
            $pStmt->execute([(int)$data['period_id']]);
            $periodName = (string)$pStmt->fetchColumn();
        }

        if ($data['payroll_type'] === 'staff') {
            $monthVal = !empty($data['salary_month']) ? $data['salary_month'] : date('Y-m', strtotime($data['payment_date']));
            $monthName = formatBulanTahun($monthVal);
            $desc = "Gaji Karyawan: {$data['recipient_name']} (Bulan {$monthName})";
        } else {
            $kidsPrefix = !empty($data['is_kids']) ? " [Kids]" : '';
            $periodSuffix = (!empty($data['is_kids'])) ? '' : ($periodName !== '' ? " ({$periodName})" : '');
            $desc = "Gaji Tutor: {$data['recipient_name']}{$kidsPrefix}{$periodSuffix}";
        }

        $notesBreakdown = "Payroll #{$payrollId} | Pokok: " . formatRupiah($data['basic_salary']) . 
                          " | Bonus: " . formatRupiah($data['bonus_reward']) .
                          ($data['payroll_type'] === 'staff' ? " | Lembur: " . formatRupiah($data['overtime']) : '') .
                          ($data['deduction'] > 0 ? " | Potongan: -" . formatRupiah($data['deduction']) : '') .
                          (!empty($data['notes']) ? " | Catatan: {$data['notes']}" : '');

        $expensePayload = [
            'expense_date'   => $data['payment_date'],
            'category_id'    => (int)$catId,
            'description'    => $desc,
            'amount'         => (float)$data['total_amount'],
            'payment_method' => in_array($data['payment_method'], ['tunai', 'transfer', 'qris'], true) ? $data['payment_method'] : 'tunai',
            'notes'          => $notesBreakdown,
            'created_by'     => $data['created_by'] ?? null,
        ];

        if ($existingExpenseId) {
            $chk = $expenseModel->find($existingExpenseId);
            if ($chk) {
                $expenseModel->update($existingExpenseId, $expensePayload);
                return $existingExpenseId;
            }
        }

        $newExpId = $expenseModel->create($expensePayload);
        return $newExpId ?: null;
    }

    /**
     * Hapus payroll sekaligus pengeluaran terkait di tabel `expenses`.
     */
    public function deleteWithExpense(int $id): bool {
        $row = $this->find($id);
        if (!$row) {
            return false;
        }

        if (!empty($row['expense_id'])) {
            try {
                $expModel = new Expense();
                $expModel->delete((int)$row['expense_id']);
            } catch (\Throwable $e) {
                // Abaikan jika expense sudah tidak ada
            }
        }

        return $this->delete($id);
    }
}
