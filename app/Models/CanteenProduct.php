<?php
namespace App\Models;

use App\Core\Model;

class CanteenProduct extends Model {
    protected string $table = 'canteen_products';

    public function getWithCategory(): array {
        $sql = "SELECT p.*, c.name AS category_name 
                FROM `{$this->table}` p 
                LEFT JOIN canteen_categories c ON p.category_id = c.id 
                ORDER BY p.name ASC";
        return $this->raw($sql);
    }

    public function getActiveForPos(): array {
        $sql = "SELECT p.*, c.name AS category_name 
                FROM `{$this->table}` p 
                LEFT JOIN canteen_categories c ON p.category_id = c.id 
                WHERE p.is_active = 1 
                ORDER BY p.name ASC";
        return $this->raw($sql);
    }

    public function findByBarcodeOrName(string $query): array {
        $sql = "SELECT p.*, c.name AS category_name 
                FROM `{$this->table}` p 
                LEFT JOIN canteen_categories c ON p.category_id = c.id 
                WHERE p.is_active = 1 AND (p.barcode = ? OR p.name LIKE ?)
                LIMIT 10";
        return $this->raw($sql, [$query, "%$query%"]);
    }

    public function adjustStock(int $productId, int $newStock, string $type, ?string $notes = null): bool {
        $p = $this->find($productId);
        if (!$p) return false;

        $oldStock = (int)$p['stock'];
        $diff = $newStock - $oldStock;
        $cost = (float)$p['cost_price'];

        $inExternalTx = $this->db->inTransaction();
        if (!$inExternalTx) {
            $this->db->beginTransaction();
        }
        try {
            $this->update($productId, ['stock' => $newStock]);

            if ($diff !== 0) {
                $batchModel = new CanteenStockBatch();
                if ($diff < 0) {
                    // Stok berkurang (susut / koreksi opname) -> konsumsi batch secara FIFO
                    $batchModel->consumeFifo($productId, -$diff, $cost);
                } else {
                    // Stok bertambah tanpa pembelian tercatat -> batch baru pakai HPP terkini
                    $batchModel->receive($productId, $diff, $cost, 'opname', $notes ?? 'Penyesuaian stok opname (bertambah)');
                }
            }

            $validType = in_array($type, ['in', 'out', 'opname_adjustment', 'sale'], true) ? $type : 'opname_adjustment';

            $logModel = new CanteenStockLog();
            $logModel->create([
                'product_id' => $productId,
                'type' => $validType,
                'quantity' => abs($diff),
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'notes' => $notes ?? 'Penyesuaian stok manual'
            ]);

            if (!$inExternalTx) {
                $this->db->commit();
            }
        } catch (\Throwable $e) {
            if (!$inExternalTx && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("adjustStock error: " . $e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Barang masuk / restock: tambah stok, buat batch FIFO baru dengan harga beli,
     * dan catat mutasi stok. HPP produk (cost_price) diperbarui ke harga beli terakhir
     * sebagai acuan/cadangan batch.
     */
    public function receiveStock(int $productId, int $qty, float $unitCost, ?string $notes = null): bool {
        $p = $this->find($productId);
        if (!$p || $qty <= 0) return false;

        $oldStock = (int)$p['stock'];
        $newStock = $oldStock + $qty;
        $note = ($notes !== null && trim($notes) !== '') ? trim($notes) : 'Barang masuk / restock';

        $this->db->beginTransaction();
        try {
            $this->update($productId, ['stock' => $newStock, 'cost_price' => $unitCost]);

            $batchModel = new CanteenStockBatch();
            $batchModel->receive($productId, $qty, $unitCost, 'restock', $note);

            $logModel = new CanteenStockLog();
            $logModel->create([
                'product_id' => $productId,
                'type' => 'in',
                'quantity' => $qty,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'notes' => $note . ' @ ' . number_format($unitCost, 0, ',', '.')
            ]);

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }

        return true;
    }
}
