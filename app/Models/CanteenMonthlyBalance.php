<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class CanteenMonthlyBalance extends Model {
    protected string $table = 'canteen_monthly_balances';

    public function __construct() {
        parent::__construct();
        $this->ensureSchema();
    }

    public function ensureSchema(): void {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS `{$this->table}` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `year_month` VARCHAR(7) NOT NULL UNIQUE,
                `manual_opening_balance` DECIMAL(15,2) NULL,
                `notes` TEXT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_canteen_ym` (`year_month`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $e) {
            // Abaikan bila sudah ada
        }
    }

    /**
     * Ambil semua daftar bulan (YYYY-MM) yang memiliki aktivitas transaksi POS atau pengeluaran kantin
     */
    public function getAvailableMonths(): array {
        $months = [];

        // Dari pos_transactions
        try {
            $stmt = $this->db->query("SELECT DISTINCT DATE_FORMAT(created_at, '%Y-%m') AS ym FROM pos_transactions WHERE created_at IS NOT NULL");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($row['ym'])) {
                    $months[$row['ym']] = true;
                }
            }
        } catch (\Throwable $e) {}

        // Dari expenses (Operasional Kantin)
        try {
            $stmt = $this->db->query("SELECT DISTINCT DATE_FORMAT(e.expense_date, '%Y-%m') AS ym 
                                      FROM expenses e 
                                      JOIN expense_categories c ON c.id = e.category_id 
                                      WHERE (c.name LIKE '%Kantin%' OR c.name LIKE '%Pepsi Food%')");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($row['ym'])) {
                    $months[$row['ym']] = true;
                }
            }
        } catch (\Throwable $e) {}

        // Dari tabel monthly balances manual
        try {
            $stmt = $this->db->query("SELECT DISTINCT year_month AS ym FROM `{$this->table}`");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($row['ym'])) {
                    $months[$row['ym']] = true;
                }
            }
        } catch (\Throwable $e) {}

        // Selalu sertakan bulan ini
        $months[date('Y-m')] = true;

        $list = array_keys($months);
        sort($list); // Urutan kronologis dari lama ke baru
        return $list;
    }

    /**
     * Hitung ringkasan transaksi & pengeluaran untuk 1 bulan tertentu
     */
    public function getRawMonthData(string $yearMonth): array {
        $startDate = $yearMonth . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        // Penjualan POS & Manual
        $stmtPos = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT t.id) AS total_orders,
                COALESCE(SUM(t.total_amount), 0) AS total_revenue,
                COALESCE(SUM(t.total_cost), 0) AS total_cost,
                COALESCE(SUM(t.gross_profit), 0) AS gross_profit,
                COALESCE(SUM(CASE WHEN t.payment_method = 'cash' THEN t.total_amount ELSE 0 END), 0) AS total_cash,
                COALESCE(SUM(CASE WHEN t.payment_method = 'qris' THEN t.total_amount ELSE 0 END), 0) AS total_qris,
                COALESCE(SUM(CASE WHEN t.is_manual = 1 THEN t.total_amount ELSE 0 END), 0) AS total_manual,
                COALESCE(SUM(CASE WHEN t.is_manual = 0 THEN t.total_amount ELSE 0 END), 0) AS total_pos
            FROM pos_transactions t
            WHERE DATE(t.created_at) BETWEEN ? AND ?
        ");
        $stmtPos->execute([$startDate, $endDate]);
        $pos = $stmtPos->fetch(PDO::FETCH_ASSOC) ?: [];

        // Pengeluaran operasional kantin
        $stmtExp = $this->db->prepare("
            SELECT 
                e.*, 
                c.name AS category_name,
                u.name AS created_by_name
            FROM expenses e
            JOIN expense_categories c ON c.id = e.category_id
            LEFT JOIN users u ON u.id = e.created_by
            WHERE (c.name LIKE '%Kantin%' OR c.name LIKE '%Pepsi Food%')
              AND e.expense_date BETWEEN ? AND ?
            ORDER BY e.expense_date ASC, e.id ASC
        ");
        $stmtExp->execute([$startDate, $endDate]);
        $expenses = $stmtExp->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $expenseTotal = 0.0;
        foreach ($expenses as $ex) {
            $expenseTotal += (float)$ex['amount'];
        }

        $revenue = (float)($pos['total_revenue'] ?? 0);
        $cost = (float)($pos['total_cost'] ?? 0);
        $grossProfit = (float)($pos['gross_profit'] ?? ($revenue - $cost));
        $netProfit = $grossProfit - $expenseTotal;

        // Penjualan per tanggal (daily breakdown)
        $stmtDaily = $this->db->prepare("
            SELECT 
                DATE(t.created_at) AS sale_date,
                COUNT(t.id) AS orders_count,
                COALESCE(SUM(t.total_amount), 0) AS daily_revenue,
                COALESCE(SUM(t.total_cost), 0) AS daily_cost,
                COALESCE(SUM(t.gross_profit), 0) AS daily_profit
            FROM pos_transactions t
            WHERE DATE(t.created_at) BETWEEN ? AND ?
            GROUP BY DATE(t.created_at)
            ORDER BY sale_date ASC
        ");
        $stmtDaily->execute([$startDate, $endDate]);
        $dailySales = $stmtDaily->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Top 10 produk terlaris di bulan ini
        $stmtTop = $this->db->prepare("
            SELECT 
                p.name AS product_name,
                c.name AS category_name,
                SUM(i.quantity) AS total_qty,
                SUM(i.subtotal) AS total_sales,
                SUM(i.subtotal - i.subtotal_cost) AS total_profit
            FROM pos_transaction_items i
            JOIN pos_transactions t ON i.transaction_id = t.id
            LEFT JOIN canteen_products p ON i.product_id = p.id
            LEFT JOIN canteen_categories c ON p.category_id = c.id
            WHERE DATE(t.created_at) BETWEEN ? AND ?
            GROUP BY i.product_id, p.name, c.name
            ORDER BY total_qty DESC
            LIMIT 10
        ");
        $stmtTop->execute([$startDate, $endDate]);
        $topProducts = $stmtTop->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return [
            'year_month'        => $yearMonth,
            'start_date'        => $startDate,
            'end_date'          => $endDate,
            'total_orders'      => (int)($pos['total_orders'] ?? 0),
            'revenue'           => $revenue,
            'cost'              => $cost,
            'gross_profit'      => $grossProfit,
            'gross_margin'      => $revenue > 0 ? round(($grossProfit / $revenue) * 100, 1) : 0,
            'total_cash'        => (float)($pos['total_cash'] ?? 0),
            'total_qris'        => (float)($pos['total_qris'] ?? 0),
            'total_manual'      => (float)($pos['total_manual'] ?? 0),
            'total_pos'         => (float)($pos['total_pos'] ?? 0),
            'expenses'          => $expenses,
            'expense_total'     => $expenseTotal,
            'net_profit'        => $netProfit,
            'net_margin'        => $revenue > 0 ? round(($netProfit / $revenue) * 100, 1) : 0,
            'daily_sales'       => $dailySales,
            'top_products'      => $topProducts,
        ];
    }

    /**
     * Hitung tabel berjalan berkesinambungan (rolling monthly balance)
     * Saldo Awal Bulan N = Saldo Akhir Bulan N-1
     * Kecuali jika admin memasukkan koreksi manual (manual_opening_balance)
     */
    public function getRollingHistory(): array {
        $months = $this->getAvailableMonths();
        if (empty($months)) {
            $months = [date('Y-m')];
        }

        // Ambil data manual opening balance yang disimpan admin
        $manualBalances = [];
        try {
            $stmt = $this->db->query("SELECT year_month, manual_opening_balance, notes FROM `{$this->table}`");
            while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $manualBalances[$r['year_month']] = [
                    'amount' => $r['manual_opening_balance'] !== null ? (float)$r['manual_opening_balance'] : null,
                    'notes'  => $r['notes']
                ];
            }
        } catch (\Throwable $e) {}

        $rolling = [];
        $runningBalance = 0.0;

        foreach ($months as $idx => $ym) {
            $data = $this->getRawMonthData($ym);

            $isManual = false;
            $manualNote = '';

            // Cek apakah ada override manual untuk bulan ini
            if (isset($manualBalances[$ym]) && $manualBalances[$ym]['amount'] !== null) {
                $openingBalance = $manualBalances[$ym]['amount'];
                $isManual = true;
                $manualNote = $manualBalances[$ym]['notes'] ?? '';
            } else {
                $openingBalance = $runningBalance;
            }

            // Saldo Akhir = Saldo Awal + Laba Bersih Bulan Ini
            // (Saldo akumulasi kas & laba kantin)
            $closingBalance = $openingBalance + $data['net_profit'];
            $runningBalance = $closingBalance;

            $rolling[$ym] = array_merge($data, [
                'opening_balance'    => $openingBalance,
                'closing_balance'    => $closingBalance,
                'is_manual_opening'  => $isManual,
                'manual_note'        => $manualNote,
                'month_label'        => $this->formatMonthLabel($ym),
            ]);
        }

        return $rolling;
    }

    /**
     * Ambil data lengkap untuk 1 bulan tertentu beserta saldo berjalannya
     */
    public function getMonthReport(string $yearMonth): array {
        $rolling = $this->getRollingHistory();

        // Jika bulan yang diminta belum ada di rolling history, tambahkan ke perhitungan
        if (!isset($rolling[$yearMonth])) {
            $months = array_keys($rolling);
            $months[] = $yearMonth;
            sort($months);
            // Recompute
            $runningBalance = 0.0;
            $rolling = [];
            foreach ($months as $ym) {
                $data = $this->getRawMonthData($ym);
                $openingBalance = $runningBalance;
                $closingBalance = $openingBalance + $data['net_profit'];
                $runningBalance = $closingBalance;
                $rolling[$ym] = array_merge($data, [
                    'opening_balance'    => $openingBalance,
                    'closing_balance'    => $closingBalance,
                    'is_manual_opening'  => false,
                    'manual_note'        => '',
                    'month_label'        => $this->formatMonthLabel($ym),
                ]);
            }
        }

        $current = $rolling[$yearMonth] ?? $this->getRawMonthData($yearMonth);
        if (!isset($current['opening_balance'])) {
            $current['opening_balance'] = 0.0;
            $current['closing_balance'] = $current['net_profit'];
            $current['is_manual_opening'] = false;
            $current['manual_note'] = '';
            $current['month_label'] = $this->formatMonthLabel($yearMonth);
        }

        // Tentukan bulan sebelumnya & sesudahnya untuk navigasi cepat
        $prevMonth = date('Y-m', strtotime($yearMonth . '-01 -1 month'));
        $nextMonth = date('Y-m', strtotime($yearMonth . '-01 +1 month'));

        return [
            'current'       => $current,
            'rolling'       => $rolling,
            'prev_month'    => $prevMonth,
            'next_month'    => $nextMonth,
            'year_month'    => $yearMonth,
            'month_label'   => $this->formatMonthLabel($yearMonth),
        ];
    }

    /**
     * Simpan / ubah saldo awal manual untuk bulan tertentu
     */
    public function setManualOpeningBalance(string $yearMonth, float $amount, ?string $notes = null): bool {
        $stmt = $this->db->prepare("
            INSERT INTO `{$this->table}` (year_month, manual_opening_balance, notes)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                manual_opening_balance = VALUES(manual_opening_balance),
                notes = VALUES(notes),
                updated_at = NOW()
        ");
        return $stmt->execute([$yearMonth, $amount, $notes]);
    }

    /**
     * Reset saldo awal manual kembali otomatis mengikuti bulan sebelumnya
     */
    public function resetManualOpeningBalance(string $yearMonth): bool {
        $stmt = $this->db->prepare("DELETE FROM `{$this->table}` WHERE year_month = ?");
        return $stmt->execute([$yearMonth]);
    }

    public function formatMonthLabel(string $yearMonth): string {
        $ts = strtotime($yearMonth . '-01');
        return tanggalIndo(date('Y-m-d', $ts), 'F Y');
    }
}
