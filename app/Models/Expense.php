<?php
namespace App\Models;

use App\Core\Model;

class Expense extends Model {
    protected string $table = 'expenses';

    /**
     * Pastikan tabel pengeluaran operasional tersedia (self-healing untuk
     * instalasi lama yang belum menjalankan ulang database_installer).
     */
    public function ensureSchema(): void {
        $this->db->exec("CREATE TABLE IF NOT EXISTS `expense_categories` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(80) NOT NULL UNIQUE,
            `description` VARCHAR(255) NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->exec("CREATE TABLE IF NOT EXISTS `expenses` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Seed kategori default bila belum ada.
        $count = (int)$this->db->query("SELECT COUNT(*) FROM `expense_categories`")->fetchColumn();
        if ($count === 0) {
            $stmt = $this->db->prepare("INSERT IGNORE INTO `expense_categories` (`name`, `description`) VALUES (?, ?)");
            foreach ([
                ['Listrik & Air', 'Tagihan listrik dan air'],
                ['Internet & Telepon', 'WiFi, pulsa, dan komunikasi'],
                ['Gaji & Honor', 'Gaji staff, honor tutor, dan upah'],
                ['Sewa Tempat', 'Sewa gedung, ruangan, atau kos'],
                ['ATK & Operasional', 'Alat tulis, cetak, dan kebutuhan kantor'],
                ['Transportasi', 'BBM, parkir, dan perjalanan'],
                ['Perawatan & Kebersihan', 'Servis, perbaikan, dan kebersihan'],
                ['Operasional Kantin', 'Pengeluaran operasional & belanja kebutuhan kantin'],
                ['Lain-lain', 'Pengeluaran operasional lainnya'],
            ] as $row) {
                $stmt->execute($row);
            }
        }

        // Pastikan kategori Operasional Kantin selalu ada
        try {
            $this->db->exec("INSERT IGNORE INTO `expense_categories` (`name`, `description`) VALUES ('Operasional Kantin', 'Pengeluaran operasional & belanja kebutuhan kantin')");
        } catch (\Throwable $e) {}

        // Beri hak akses menu baru ke role admin yang sudah pernah dikonfigurasi.
        try {
            $adminConfigured = (int)$this->db->query("SELECT COUNT(*) FROM `role_permissions` WHERE `role` = 'admin'")->fetchColumn();
            if ($adminConfigured > 0) {
                $grant = $this->db->prepare("INSERT IGNORE INTO `role_permissions` (`role`, `menu_key`, `allowed`) VALUES ('admin', ?, 1)");
                foreach (['expenses', 'expenses_report'] as $key) {
                    $grant->execute([$key]);
                }
            }
        } catch (\Throwable $e) {
            // tabel role_permissions mungkin belum ada - abaikan
        }
    }

    /**
     * Ambil daftar pengeluaran beserta nama kategorinya.
     *
     * @param array{start_date?:string,end_date?:string,category_id?:int|string,search?:string} $f
     */
    public function getFiltered(array $f = []): array {
        $where = [];
        $params = [];

        $start = trim((string)($f['start_date'] ?? ''));
        $end   = trim((string)($f['end_date'] ?? ''));
        $cat   = (int)($f['category_id'] ?? 0);
        $search = trim((string)($f['search'] ?? ''));

        if ($start !== '') {
            $where[] = 'e.`expense_date` >= ?';
            $params[] = $start;
        }
        if ($end !== '') {
            $where[] = 'e.`expense_date` <= ?';
            $params[] = $end;
        }
        if ($cat > 0) {
            $where[] = 'e.`category_id` = ?';
            $params[] = $cat;
        }
        if ($search !== '') {
            $where[] = '(e.`description` LIKE ? OR e.`notes` LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $sql = "SELECT e.*, c.`name` AS category_name, u.`name` AS created_by_name
                FROM `expenses` e
                LEFT JOIN `expense_categories` c ON c.`id` = e.`category_id`
                LEFT JOIN `users` u ON u.`id` = e.`created_by`";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY e.`expense_date` DESC, e.`id` DESC';

        return $this->raw($sql, $params);
    }

    /** Total & jumlah pengeluaran pada rentang tanggal (opsional per kategori). */
    public function summary(string $start = '', string $end = ''): array {
        $where = [];
        $params = [];
        if ($start !== '') {
            $where[] = '`expense_date` >= ?';
            $params[] = $start;
        }
        if ($end !== '') {
            $where[] = '`expense_date` <= ?';
            $params[] = $end;
        }
        $sql = "SELECT COUNT(*) AS total_count, COALESCE(SUM(`amount`), 0) AS total_amount FROM `expenses`";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $row = $this->raw($sql, $params)[0] ?? [];

        return [
            'count'  => (int)($row['total_count'] ?? 0),
            'amount' => (float)($row['total_amount'] ?? 0),
        ];
    }

    /** Rekap pengeluaran per kategori dalam rentang tanggal. */
    public function getCategoryTotals(string $start = '', string $end = ''): array {
        $where = [];
        $params = [];
        if ($start !== '') {
            $where[] = 'e.`expense_date` >= ?';
            $params[] = $start;
        }
        if ($end !== '') {
            $where[] = 'e.`expense_date` <= ?';
            $params[] = $end;
        }
        $sql = "SELECT COALESCE(c.`name`, 'Tanpa Kategori') AS category_name,
                       COUNT(*) AS total_count,
                       COALESCE(SUM(e.`amount`), 0) AS total_amount
                FROM `expenses` e
                LEFT JOIN `expense_categories` c ON c.`id` = e.`category_id`";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' GROUP BY e.`category_id`, c.`name` ORDER BY total_amount DESC';

        return $this->raw($sql, $params);
    }

    /**
     * Ambil daftar dan total pengeluaran khusus operasional kantin (Pepsi Food & Drink)
     */
    public function getCanteenExpenses(string $start = '', string $end = ''): array {
        $where = ["(c.`name` LIKE '%Kantin%' OR c.`name` LIKE '%Pepsi Food%')"];
        $params = [];
        if ($start !== '') {
            $where[] = 'e.`expense_date` >= ?';
            $params[] = $start;
        }
        if ($end !== '') {
            $where[] = 'e.`expense_date` <= ?';
            $params[] = $end;
        }

        $sql = "SELECT e.*, c.`name` AS category_name, u.`name` AS created_by_name
                FROM `expenses` e
                JOIN `expense_categories` c ON c.`id` = e.`category_id`
                LEFT JOIN `users` u ON u.`id` = e.`created_by`
                WHERE " . implode(' AND ', $where) . "
                ORDER BY e.`expense_date` DESC, e.`id` DESC";

        $rows = $this->raw($sql, $params);
        $total = 0.0;
        foreach ($rows as $r) {
            $total += (float)$r['amount'];
        }

        return [
            'rows'  => $rows,
            'total' => $total,
            'count' => count($rows),
        ];
    }
}
