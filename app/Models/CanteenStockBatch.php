<?php
namespace App\Models;

use App\Core\Model;

class CanteenStockBatch extends Model {
    protected string $table = 'canteen_stock_batches';

    public function receive(int $productId, int $qty, float $unitCost, string $source = 'restock', ?string $notes = null): int {
        return $this->create([
            'product_id' => $productId,
            'unit_cost' => $unitCost,
            'qty_in' => $qty,
            'qty_remaining' => $qty,
            'source' => $source,
            'notes' => $notes,
        ]);
    }

    /**
     * Konsumsi stok secara FIFO (batch tertua lebih dulu).
     * Wajib dipanggil di dalam transaksi DB bila dipakai saat penjualan.
     *
     * @return array{cost: float, consumed: int, shortfall: int}
     */
    public function consumeFifo(int $productId, int $qty, float $fallbackCost = 0.0): array {
        $need = max(0, $qty);
        $cost = 0.0;
        $consumed = 0;
        $shortfall = 0;

        if ($need > 0) {
            $stmt = $this->db->prepare(
                "SELECT id, qty_remaining, unit_cost FROM `{$this->table}`
                 WHERE product_id = ? AND qty_remaining > 0
                 ORDER BY created_at ASC, id ASC FOR UPDATE"
            );
            $stmt->execute([$productId]);
            $upd = $this->db->prepare("UPDATE `{$this->table}` SET qty_remaining = qty_remaining - ? WHERE id = ?");

            foreach ($stmt->fetchAll() as $b) {
                if ($need <= 0) {
                    break;
                }
                $take = min($need, (int)$b['qty_remaining']);
                $cost += $take * (float)$b['unit_cost'];
                $upd->execute([$take, $b['id']]);
                $need -= $take;
                $consumed += $take;
            }
        }

        if ($need > 0) {
            // Batch tidak cukup (mis. data lama sebelum FIFO) -> pakai HPP cadangan.
            $cost += $need * $fallbackCost;
            $consumed += $need;
            $shortfall = $need;
        }

        return ['cost' => $cost, 'consumed' => $consumed, 'shortfall' => $shortfall];
    }

    public function remainingQty(int $productId): int {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(qty_remaining), 0) FROM `{$this->table}` WHERE product_id = ?");
        $stmt->execute([$productId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * HPP rata-rata dari batch yang masih tersisa (null bila stok habis).
     */
    public function averageCost(int $productId): ?float {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(qty_remaining * unit_cost), 0) AS total_value,
                    COALESCE(SUM(qty_remaining), 0) AS total_qty
             FROM `{$this->table}` WHERE product_id = ? AND qty_remaining > 0"
        );
        $stmt->execute([$productId]);
        $row = $stmt->fetch();
        $qty = (int)($row['total_qty'] ?? 0);
        if ($qty <= 0) {
            return null;
        }
        return (float)$row['total_value'] / $qty;
    }

    /**
     * HPP rata-rata & jumlah batch aktif seluruh produk (1 query).
     * @return array<int, array{avg_cost: float, active_qty: int, batch_count: int}>
     */
    public function averageCostsAll(): array {
        $rows = $this->raw(
            "SELECT product_id,
                    COALESCE(SUM(qty_remaining * unit_cost), 0) AS total_value,
                    COALESCE(SUM(qty_remaining), 0) AS total_qty,
                    COUNT(*) AS batch_count
             FROM `{$this->table}`
             WHERE qty_remaining > 0
             GROUP BY product_id"
        );
        $out = [];
        foreach ($rows as $r) {
            $qty = (int)$r['total_qty'];
            $out[(int)$r['product_id']] = [
                'avg_cost' => $qty > 0 ? (float)$r['total_value'] / $qty : 0.0,
                'active_qty' => $qty,
                'batch_count' => (int)$r['batch_count'],
            ];
        }
        return $out;
    }

    public function getActiveByProduct(int $productId): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}` WHERE product_id = ? AND qty_remaining > 0
             ORDER BY created_at ASC, id ASC"
        );
        $stmt->execute([$productId]);
        return $stmt->fetchAll() ?: [];
    }

    public function getActiveWithProduct(int $limit = 100): array {
        $sql = "SELECT b.*, p.name AS product_name, p.barcode
                FROM `{$this->table}` b
                JOIN canteen_products p ON b.product_id = p.id
                WHERE b.qty_remaining > 0
                ORDER BY p.name ASC, b.created_at ASC
                LIMIT " . (int)$limit;
        return $this->raw($sql);
    }
}
