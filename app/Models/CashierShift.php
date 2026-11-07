<?php
namespace App\Models;

use App\Core\Model;

class CashierShift extends Model {
    protected string $table = 'cashier_shifts';

    public function __construct() {
        parent::__construct();
        // Auto-migrate: pastikan kolom rekap QRIS tersedia
        try {
            $cols = $this->db->query("SHOW COLUMNS FROM `{$this->table}` LIKE 'total_qris'")->fetchAll();
            if (empty($cols)) {
                $this->db->exec("ALTER TABLE `{$this->table}` ADD COLUMN `total_qris` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `total_cash`");
            }
        } catch (\Exception $e) {
            // Tabel belum ada, biarkan — akan dibuat saat migrasi penuh
        }
    }

    public function getCurrentOpenShift(int $userId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM `{$this->table}` WHERE user_id = ? AND status = 'open' ORDER BY id DESC LIMIT 1");
        $stmt->execute([$userId]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function openShift(int $userId, float $startingCash, ?string $notes = null): int {
        return $this->create([
            'user_id' => $userId,
            'start_time' => date('Y-m-d H:i:s'),
            'starting_cash' => $startingCash,
            'expected_cash' => $startingCash,
            'status' => 'open',
            'notes' => $notes
        ]);
    }

    public function calculateShiftTotals(int $shiftId): array {
        $shift = $this->find($shiftId);
        if (!$shift) return [];

        $sql = "SELECT 
                    COUNT(*) AS total_transactions,
                    COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN total_amount ELSE 0 END), 0) AS total_cash_sales,
                    COALESCE(SUM(CASE WHEN payment_method = 'qris' THEN total_amount ELSE 0 END), 0) AS total_qris_sales,
                    COALESCE(SUM(total_amount), 0) AS total_sales
                FROM pos_transactions 
                WHERE shift_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$shiftId]);
        $stats = $stmt->fetch();

        $startingCash = (float)$shift['starting_cash'];
        $cashSales = (float)$stats['total_cash_sales'];
        $expectedCash = $startingCash + $cashSales;

        return [
            'total_transactions' => (int)$stats['total_transactions'],
            'starting_cash' => $startingCash,
            'total_cash_sales' => $cashSales,
            'total_qris_sales' => (float)$stats['total_qris_sales'],
            'total_sales' => (float)$stats['total_sales'],
            'expected_cash' => $expectedCash
        ];
    }

    public function closeShift(int $shiftId, float $actualCash, ?string $closingNotes = null): bool {
        $totals = $this->calculateShiftTotals($shiftId);
        $expectedCash = $totals['expected_cash'] ?? 0;
        $diff = $actualCash - $expectedCash;

        $sql = "UPDATE `{$this->table}` SET 
                end_time = ?,
                expected_cash = ?,
                actual_cash = ?,
                cash_difference = ?,
                total_cash = ?,
                total_qris = ?,
                total_sales = ?,
                status = 'closed',
                notes = CONCAT(COALESCE(notes, ''), '\n', ?)
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            date('Y-m-d H:i:s'),
            $expectedCash,
            $actualCash,
            $diff,
            $totals['total_cash_sales'] ?? 0,
            $totals['total_qris_sales'] ?? 0,
            $totals['total_sales'] ?? 0,
            "Tutup Shift: " . ($closingNotes ?? '-'),
            $shiftId
        ]);
    }
}
