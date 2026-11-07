<?php
namespace App\Models;

use App\Core\Model;
use PDO;
use Exception;

class PosTransaction extends Model {
    protected string $table = 'pos_transactions';

    public function __construct() {
        parent::__construct();
        // Auto-migrate: pastikan kolom payment_method mendukung QRIS
        try {
            $column = $this->db->query("SHOW COLUMNS FROM `{$this->table}` LIKE 'payment_method'")->fetch();
            if ($column && stripos((string)($column['Type'] ?? ''), "'qris'") === false) {
                $this->db->exec("ALTER TABLE `{$this->table}` MODIFY `payment_method` ENUM('cash','qris') NOT NULL DEFAULT 'cash'");
            }
        } catch (Exception $e) {
            // Tabel belum ada, biarkan — akan dibuat saat migrasi penuh
        }

        // Auto-migrate: kolom untuk penjualan tambahan (input manual admin)
        try {
            $manual = $this->db->query("SHOW COLUMNS FROM `{$this->table}` LIKE 'is_manual'")->fetchAll();
            if (empty($manual)) {
                $this->db->exec("ALTER TABLE `{$this->table}`
                    ADD COLUMN `is_manual` TINYINT(1) NOT NULL DEFAULT 0 AFTER `customer_name`,
                    ADD COLUMN `manual_note` VARCHAR(255) NULL AFTER `is_manual`");
            }
        } catch (Exception $e) {
            // Tabel belum ada, biarkan
        }

        // Auto-migrate: item manual boleh tanpa produk (product_id NULL)
        try {
            $col = $this->db->query("SHOW COLUMNS FROM `pos_transaction_items` LIKE 'product_id'")->fetch();
            if ($col && stripos((string)($col['Null'] ?? ''), 'NO') === 0) {
                $this->db->exec("ALTER TABLE `pos_transaction_items` MODIFY `product_id` INT NULL");
            }
        } catch (Exception $e) {
            // biarkan
        }

        // Auto-migrate: kolom penyesuaian transaksi (salah bayar & salah stok)
        try {
            $adj = $this->db->query("SHOW COLUMNS FROM `{$this->table}` LIKE 'is_adjusted'")->fetchAll();
            if (empty($adj)) {
                $this->db->exec("ALTER TABLE `{$this->table}`
                    ADD COLUMN `is_adjusted` TINYINT(1) NOT NULL DEFAULT 0 AFTER `manual_note`,
                    ADD COLUMN `adjusted_at` DATETIME NULL AFTER `is_adjusted`,
                    ADD COLUMN `adjustment_note` TEXT NULL AFTER `adjusted_at`,
                    ADD COLUMN `adjusted_by` INT NULL AFTER `adjustment_note`");
            }
        } catch (Exception $e) {
            // biarkan
        }
    }

    public function createSale(array $data, array $items): array {
        $this->db->beginTransaction();
        try {
            // Generate Invoice No: e.g. INV-20261008-0001
            $prefix = 'INV-' . date('Ymd') . '-';
            $stmtLast = $this->db->prepare("SELECT invoice_no FROM `{$this->table}` WHERE invoice_no LIKE ? ORDER BY id DESC LIMIT 1");
            $stmtLast->execute([$prefix . '%']);
            $last = $stmtLast->fetchColumn();
            $seq = 1;
            if ($last) {
                $lastSeq = (int)substr($last, strlen($prefix));
                $seq = $lastSeq + 1;
            }
            $invoiceNo = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);

            // Calculate totals and deduct stock
            $totalAmount = 0;
            $totalCost = 0;
            $processedItems = [];

            $prodStmt = $this->db->prepare("SELECT * FROM canteen_products WHERE id = ? FOR UPDATE");
            $stockUpdateStmt = $this->db->prepare("UPDATE canteen_products SET stock = stock - ? WHERE id = ?");
            $logStmt = $this->db->prepare("INSERT INTO canteen_stock_logs (product_id, type, quantity, old_stock, new_stock, notes) VALUES (?, 'sale', ?, ?, ?, ?)");
            $batchModel = new CanteenStockBatch();

            foreach ($items as $item) {
                $productId = (int)$item['product_id'];
                $qty = (int)$item['quantity'];
                if ($qty <= 0) continue;

                $prodStmt->execute([$productId]);
                $product = $prodStmt->fetch();
                if (!$product) {
                    throw new Exception("Produk dengan ID {$productId} tidak ditemukan.");
                }

                $sellingPrice = (float)$product['selling_price'];
                $subtotal = $sellingPrice * $qty;

                // HPP dihitung FIFO dari batch pembelian (batch tertua lebih dulu)
                $fifo = $batchModel->consumeFifo($productId, $qty, (float)$product['cost_price']);
                $subtotalCost = $fifo['cost'];
                $costPrice = $qty > 0 ? $subtotalCost / $qty : (float)$product['cost_price'];

                $totalAmount += $subtotal;
                $totalCost += $subtotalCost;

                // Deduct stock
                $oldStock = (int)$product['stock'];
                $newStock = $oldStock - $qty;
                $stockUpdateStmt->execute([$qty, $productId]);

                // Stock log
                $logStmt->execute([
                    $productId,
                    $qty,
                    $oldStock,
                    $newStock,
                    "Penjualan POS No. {$invoiceNo}"
                ]);

                $processedItems[] = [
                    'product_id' => $productId,
                    'product_name' => $product['name'],
                    'cost_price' => $costPrice,
                    'selling_price' => $sellingPrice,
                    'quantity' => $qty,
                    'subtotal' => $subtotal,
                    'subtotal_cost' => $subtotalCost
                ];
            }

            $grossProfit = $totalAmount - $totalCost;
            $paymentMethod = in_array($data['payment_method'] ?? 'cash', ['cash', 'qris'], true) ? $data['payment_method'] : 'cash';
            if ($paymentMethod === 'cash') {
                $cashPaid = (float)($data['cash_paid'] ?? $totalAmount);
            } else {
                $cashPaid = $totalAmount;
            }
            $changeReturned = max(0, $cashPaid - $totalAmount);

            // Insert transaction
            $txStmt = $this->db->prepare("INSERT INTO `{$this->table}` 
                (shift_id, invoice_no, user_id, total_amount, total_cost, gross_profit, payment_method, cash_paid, change_returned, customer_name) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $txStmt->execute([
                $data['shift_id'] ?? null,
                $invoiceNo,
                $data['user_id'],
                $totalAmount,
                $totalCost,
                $grossProfit,
                $paymentMethod,
                $cashPaid,
                $changeReturned,
                $data['customer_name'] ?? 'Umum / Siswa'
            ]);
            $transactionId = (int)$this->db->lastInsertId();

            // Insert items
            $itemStmt = $this->db->prepare("INSERT INTO pos_transaction_items 
                (transaction_id, product_id, product_name, cost_price, selling_price, quantity, subtotal, subtotal_cost) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($processedItems as $pi) {
                $itemStmt->execute([
                    $transactionId,
                    $pi['product_id'],
                    $pi['product_name'],
                    $pi['cost_price'],
                    $pi['selling_price'],
                    $pi['quantity'],
                    $pi['subtotal'],
                    $pi['subtotal_cost']
                ]);
            }

            $this->db->commit();

            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'invoice_no' => $invoiceNo,
                'total_amount' => $totalAmount,
                'cash_paid' => $cashPaid,
                'change_returned' => $changeReturned,
                'payment_method' => $paymentMethod,
                'items' => $processedItems
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getReceipt(int $transactionId): ?array {
        $tx = $this->find($transactionId);
        if (!$tx) return null;

        $userStmt = $this->db->prepare("SELECT name FROM users WHERE id = ?");
        $userStmt->execute([$tx['user_id']]);
        $tx['cashier_name'] = $userStmt->fetchColumn() ?: 'Kasir';

        $itemStmt = $this->db->prepare("SELECT * FROM pos_transaction_items WHERE transaction_id = ?");
        $itemStmt->execute([$transactionId]);
        $tx['items'] = $itemStmt->fetchAll();

        return $tx;
    }

    public function getProfitLossReport(?string $startDate = null, ?string $endDate = null): array {
        $sql = "SELECT 
                    COUNT(DISTINCT t.id) AS total_orders,
                    COALESCE(SUM(t.total_amount), 0) AS total_revenue,
                    COALESCE(SUM(t.total_cost), 0) AS total_cost,
                    COALESCE(SUM(t.gross_profit), 0) AS gross_profit,
                    COALESCE(SUM(CASE WHEN t.payment_method = 'cash' THEN t.total_amount ELSE 0 END), 0) AS total_cash,
                    COALESCE(SUM(CASE WHEN t.payment_method = 'qris' THEN t.total_amount ELSE 0 END), 0) AS total_qris,
                    COALESCE(SUM(CASE WHEN t.payment_method = 'cash' THEN 1 ELSE 0 END), 0) AS count_cash,
                    COALESCE(SUM(CASE WHEN t.payment_method = 'qris' THEN 1 ELSE 0 END), 0) AS count_qris,
                    COALESCE(SUM(CASE WHEN t.is_manual = 1 THEN t.total_amount ELSE 0 END), 0) AS total_manual,
                    COALESCE(SUM(CASE WHEN t.is_manual = 1 THEN t.total_cost ELSE 0 END), 0) AS total_manual_cost,
                    COALESCE(SUM(CASE WHEN t.is_manual = 0 THEN t.total_amount ELSE 0 END), 0) AS total_pos,
                    COALESCE(SUM(CASE WHEN t.is_manual = 1 THEN 1 ELSE 0 END), 0) AS total_manual_orders,
                    COALESCE(SUM(CASE WHEN t.is_adjusted = 1 THEN 1 ELSE 0 END), 0) AS total_adjusted
                FROM pos_transactions t
                WHERE 1=1";
        $params = [];

        if ($startDate && $endDate) {
            $sql .= " AND DATE(t.created_at) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $summary = $stmt->fetch();

        // Top selling products in this period
        $sqlTop = "SELECT p.name AS product_name, SUM(i.quantity) AS total_qty, SUM(i.subtotal) AS total_sales, SUM(i.subtotal - i.subtotal_cost) AS total_profit
                   FROM pos_transaction_items i
                   JOIN pos_transactions t ON i.transaction_id = t.id
                   JOIN canteen_products p ON i.product_id = p.id
                   WHERE 1=1";
        $pTop = [];
        if ($startDate && $endDate) {
            $sqlTop .= " AND DATE(t.created_at) BETWEEN ? AND ?";
            $pTop[] = $startDate;
            $pTop[] = $endDate;
        }
        $sqlTop .= " GROUP BY p.id ORDER BY total_qty DESC LIMIT 10";
        $stmtTop = $this->db->prepare($sqlTop);
        $stmtTop->execute($pTop);
        $topProducts = $stmtTop->fetchAll();

        return [
            'summary' => $summary,
            'top_products' => $topProducts
        ];
    }

    /**
     * Input penjualan tambahan secara manual oleh admin (setelah kasir menutup POS).
     * Item boleh memilih produk (HPP otomatis) atau isian sendiri (HPP 0).
     * TIDAK memotong stok karena barang sudah terjual sebelumnya.
     */
    public function createManualSale(array $data, array $items): array {
        $this->db->beginTransaction();
        try {
            $saleDate = (string)($data['sale_date'] ?? date('Y-m-d'));
            $ts = strtotime($saleDate);
            if ($ts === false || $saleDate === '') {
                throw new Exception('Tanggal penjualan tidak valid.');
            }
            $saleDate = date('Y-m-d', $ts);

            $note = trim((string)($data['note'] ?? ''));
            $paymentMethod = ($data['payment_method'] ?? 'cash') === 'qris' ? 'qris' : 'cash';

            // Invoice manual: MNL-YYYYMMDD-0001
            $prefix = 'MNL-' . date('Ymd', $ts) . '-';
            $stmtLast = $this->db->prepare("SELECT invoice_no FROM `{$this->table}` WHERE invoice_no LIKE ? ORDER BY id DESC LIMIT 1");
            $stmtLast->execute([$prefix . '%']);
            $last = (string)$stmtLast->fetchColumn();
            $seq = ($last !== '') ? ((int)substr($last, -4) + 1) : 1;
            $invoiceNo = $prefix . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);

            $prodStmt = $this->db->prepare("SELECT * FROM canteen_products WHERE id = ?");

            $totalAmount = 0.0;
            $totalCost = 0.0;
            $processed = [];

            foreach ($items as $item) {
                $qty = (int)($item['quantity'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                $productId = (isset($item['product_id']) && (int)$item['product_id'] > 0) ? (int)$item['product_id'] : null;
                $name = trim((string)($item['name'] ?? ''));
                $price = (float)($item['price'] ?? 0);
                $cost = 0.0;

                if ($productId !== null) {
                    $prodStmt->execute([$productId]);
                    $product = $prodStmt->fetch();
                    if (!$product) {
                        throw new Exception('Produk #' . $productId . ' tidak ditemukan.');
                    }
                    if ($name === '') {
                        $name = (string)$product['name'];
                    }
                    if ($price <= 0) {
                        $price = (float)$product['selling_price'];
                    }
                    $cost = (float)$product['cost_price'];
                }

                if ($name === '') {
                    throw new Exception('Nama item wajib diisi untuk setiap baris penjualan.');
                }
                if ($price <= 0) {
                    throw new Exception('Harga jual "' . $name . '" harus lebih dari 0.');
                }

                $subtotal = $price * $qty;
                $subtotalCost = $cost * $qty;
                $totalAmount += $subtotal;
                $totalCost += $subtotalCost;

                $processed[] = [
                    'product_id' => $productId,
                    'product_name' => $name,
                    'cost_price' => $cost,
                    'selling_price' => $price,
                    'quantity' => $qty,
                    'subtotal' => $subtotal,
                    'subtotal_cost' => $subtotalCost
                ];
            }

            if ($processed === []) {
                throw new Exception('Minimal isi satu item penjualan dengan jumlah minimal 1.');
            }

            $grossProfit = $totalAmount - $totalCost;
            $createdAt = $saleDate . ' ' . date('H:i:s');
            $userId = (int)($data['user_id'] ?? 0);
            if ($userId <= 0) {
                throw new Exception('Sesi admin tidak valid, silakan login ulang.');
            }

            $insertTx = $this->db->prepare("INSERT INTO `{$this->table}`
                (invoice_no, user_id, customer_name, total_amount, total_cost, gross_profit,
                 cash_paid, change_returned, payment_method, shift_id, created_at, is_manual, manual_note)
                VALUES (?, ?, 'Entri Manual', ?, ?, ?, ?, 0, ?, NULL, ?, 1, ?)");
            $insertTx->execute([
                $invoiceNo,
                $userId,
                $totalAmount,
                $totalCost,
                $grossProfit,
                $totalAmount,
                $paymentMethod,
                $createdAt,
                $note !== '' ? $note : null
            ]);
            $transactionId = (int)$this->db->lastInsertId();

            $insertItem = $this->db->prepare("INSERT INTO `pos_transaction_items`
                (transaction_id, product_id, product_name, cost_price, selling_price, quantity, subtotal, subtotal_cost)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($processed as $pi) {
                $insertItem->execute([
                    $transactionId,
                    $pi['product_id'],
                    $pi['product_name'],
                    $pi['cost_price'],
                    $pi['selling_price'],
                    $pi['quantity'],
                    $pi['subtotal'],
                    $pi['subtotal_cost']
                ]);
            }

            $this->db->commit();

            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'invoice_no' => $invoiceNo,
                'total_amount' => $totalAmount,
                'payment_method' => $paymentMethod,
                'sale_date' => $saleDate,
                'items' => $processed
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getManualSales(?string $startDate = null, ?string $endDate = null, int $limit = 100): array {
        $sql = "SELECT t.*, u.name AS admin_name
                FROM `{$this->table}` t
                LEFT JOIN users u ON t.user_id = u.id
                WHERE t.is_manual = 1";
        $params = [];

        if ($startDate && $endDate) {
            $sql .= " AND DATE(t.created_at) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        $sql .= " ORDER BY t.created_at DESC, t.id DESC LIMIT " . (int)$limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $sales = $stmt->fetchAll();

        if ($sales !== []) {
            $itemStmt = $this->db->prepare("SELECT * FROM `pos_transaction_items` WHERE transaction_id = ?");
            foreach ($sales as &$sale) {
                $itemStmt->execute([$sale['id']]);
                $sale['items'] = $itemStmt->fetchAll();
            }
            unset($sale);
        }

        return $sales;
    }

    public function deleteManualSale(int $transactionId): bool {
        $tx = $this->find($transactionId);
        if (!$tx || (int)$tx['is_manual'] !== 1) {
            return false;
        }
        // FK ON DELETE CASCADE menangani pos_transaction_items
        return $this->delete($transactionId);
    }

    /**
     * Ambil riwayat transaksi lengkap dengan rincian item untuk menu Penyesuaian Transaksi.
     */
    public function getTransactionsForAdjustment(array $filters = [], int $limit = 150): array {
        $sql = "SELECT t.*, u.name AS cashier_name, s.status AS shift_status, ua.name AS adjusted_by_name
                FROM `{$this->table}` t
                LEFT JOIN users u ON t.user_id = u.id
                LEFT JOIN users ua ON t.adjusted_by = ua.id
                LEFT JOIN cashier_shifts s ON t.shift_id = s.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['start_date'])) {
            $sql .= " AND DATE(t.created_at) >= ?";
            $params[] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND DATE(t.created_at) <= ?";
            $params[] = $filters['end_date'];
        }
        if (!empty($filters['payment_method']) && in_array($filters['payment_method'], ['cash', 'qris'], true)) {
            $sql .= " AND t.payment_method = ?";
            $params[] = $filters['payment_method'];
        }
        if (isset($filters['type']) && $filters['type'] !== 'all' && $filters['type'] !== '') {
            if ($filters['type'] === 'manual') {
                $sql .= " AND t.is_manual = 1";
            } elseif ($filters['type'] === 'pos') {
                $sql .= " AND t.is_manual = 0";
            }
        }
        if (isset($filters['status_adjusted']) && $filters['status_adjusted'] !== 'all' && $filters['status_adjusted'] !== '') {
            if ($filters['status_adjusted'] === 'adjusted') {
                $sql .= " AND t.is_adjusted = 1";
            } elseif ($filters['status_adjusted'] === 'normal') {
                $sql .= " AND (t.is_adjusted = 0 OR t.is_adjusted IS NULL)";
            }
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (t.invoice_no LIKE ? OR t.customer_name LIKE ? OR t.manual_note LIKE ?)";
            $term = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $sql .= " ORDER BY t.created_at DESC, t.id DESC LIMIT " . (int)$limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $txs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($txs !== []) {
            $itemStmt = $this->db->prepare("SELECT * FROM pos_transaction_items WHERE transaction_id = ? ORDER BY id ASC");
            foreach ($txs as &$tx) {
                $itemStmt->execute([$tx['id']]);
                $tx['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
            }
            unset($tx);
        }

        return $txs;
    }

    /**
     * Hitung ringkasan pendapatan & metode bayar untuk filter penyesuaian transaksi.
     */
    public function getAdjustmentSummary(array $filters = []): array {
        $sql = "SELECT 
                    COUNT(*) AS total_count,
                    COALESCE(SUM(total_amount), 0) AS total_revenue,
                    COALESCE(SUM(gross_profit), 0) AS total_profit,
                    COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN total_amount ELSE 0 END), 0) AS total_cash,
                    COALESCE(SUM(CASE WHEN payment_method = 'qris' THEN total_amount ELSE 0 END), 0) AS total_qris,
                    SUM(CASE WHEN payment_method = 'cash' THEN 1 ELSE 0 END) AS count_cash,
                    SUM(CASE WHEN payment_method = 'qris' THEN 1 ELSE 0 END) AS count_qris,
                    SUM(CASE WHEN is_manual = 1 THEN 1 ELSE 0 END) AS count_manual,
                    SUM(CASE WHEN is_manual = 0 THEN 1 ELSE 0 END) AS count_pos,
                    SUM(CASE WHEN is_adjusted = 1 THEN 1 ELSE 0 END) AS count_adjusted
                FROM `{$this->table}` t
                WHERE 1=1";
        $params = [];

        if (!empty($filters['start_date'])) {
            $sql .= " AND DATE(t.created_at) >= ?";
            $params[] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND DATE(t.created_at) <= ?";
            $params[] = $filters['end_date'];
        }
        if (!empty($filters['payment_method']) && in_array($filters['payment_method'], ['cash', 'qris'], true)) {
            $sql .= " AND t.payment_method = ?";
            $params[] = $filters['payment_method'];
        }
        if (isset($filters['type']) && $filters['type'] !== 'all' && $filters['type'] !== '') {
            if ($filters['type'] === 'manual') {
                $sql .= " AND t.is_manual = 1";
            } elseif ($filters['type'] === 'pos') {
                $sql .= " AND t.is_manual = 0";
            }
        }
        if (isset($filters['status_adjusted']) && $filters['status_adjusted'] !== 'all' && $filters['status_adjusted'] !== '') {
            if ($filters['status_adjusted'] === 'adjusted') {
                $sql .= " AND t.is_adjusted = 1";
            } elseif ($filters['status_adjusted'] === 'normal') {
                $sql .= " AND (t.is_adjusted = 0 OR t.is_adjusted IS NULL)";
            }
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (t.invoice_no LIKE ? OR t.customer_name LIKE ? OR t.manual_note LIKE ? OR t.adjustment_note LIKE ?)";
            $term = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Penyesuaian transaksi kasir (Koreksi salah bayar & koreksi salah stok / item barang).
     * Jika item diubah (qty berubah / dihapus / ditambah):
     *   - Fisik stok di canteen_products otomatis dikembalikan atau dipotong sesuai selisih.
     *   - Riwayat stok dicatat di canteen_stock_logs dan batch FIFO disesuaikan.
     *   - total_amount, total_cost, gross_profit, dan kembalian transaksi dihitung ulang.
     *   - Transaksi ditandai is_adjusted = 1, adjusted_at = NOW(), adjustment_note = alasan.
     */
    public function updateTransactionAdjustment(int $id, array $data, ?array $adjustedItems = null, ?int $userId = null): array {
        $tx = $this->find($id);
        if (!$tx) {
            return ['success' => false, 'error' => 'Transaksi tidak ditemukan.'];
        }

        $inExternalTx = $this->db->inTransaction();
        if (!$inExternalTx) {
            $this->db->beginTransaction();
        }

        try {
            $invoiceNo = $tx['invoice_no'];
            $isManual = (int)($tx['is_manual'] ?? 0);
            $stockChangeNotes = [];

            // Ambil item yang saat ini ada di DB
            $stmtCur = $this->db->prepare("SELECT * FROM pos_transaction_items WHERE transaction_id = ?");
            $stmtCur->execute([$id]);
            $currentItemsRaw = $stmtCur->fetchAll(PDO::FETCH_ASSOC);
            $currentItems = [];
            foreach ($currentItemsRaw as $ci) {
                $currentItems[(int)$ci['id']] = $ci;
            }

            // Jika ada perubahan item/stok yang dikirim
            if ($adjustedItems !== null) {
                $prodStmt = $this->db->prepare("SELECT id, name, stock, cost_price, selling_price FROM canteen_products WHERE id = ? FOR UPDATE");
                $stockUpd = $this->db->prepare("UPDATE canteen_products SET stock = stock + ? WHERE id = ?");
                $logStmt  = $this->db->prepare("INSERT INTO canteen_stock_logs (product_id, type, quantity, old_stock, new_stock, notes) VALUES (?, ?, ?, ?, ?, ?)");
                $batchModel = new CanteenStockBatch();

                $processedItemIds = [];

                foreach ($adjustedItems as $ai) {
                    $itemId = !empty($ai['id']) ? (int)$ai['id'] : 0;
                    $productId = !empty($ai['product_id']) ? (int)$ai['product_id'] : null;
                    $newQty = max(0, (int)($ai['quantity'] ?? 0));

                    // Item sudah ada di transaksi
                    if ($itemId > 0 && isset($currentItems[$itemId])) {
                        $processedItemIds[] = $itemId;
                        $oldItem = $currentItems[$itemId];
                        $oldQty = (int)$oldItem['quantity'];
                        $pId = $oldItem['product_id'] ? (int)$oldItem['product_id'] : null;

                        if ($newQty === 0) {
                            // Hapus item dari transaksi -> kembalikan seluruh stok
                            if ($isManual === 0 && $pId > 0 && $oldQty > 0) {
                                $prodStmt->execute([$pId]);
                                $prod = $prodStmt->fetch(PDO::FETCH_ASSOC);
                                if ($prod) {
                                    $oldStock = (int)$prod['stock'];
                                    $newStock = $oldStock + $oldQty;
                                    $stockUpd->execute([$oldQty, $pId]);
                                    $noteLog = "Penyesuaian Transaksi {$invoiceNo} - Hapus item '{$oldItem['product_name']}' (kembalikan {$oldQty} pcs)";
                                    $logStmt->execute([$pId, 'opname_adjustment', $oldQty, $oldStock, $newStock, $noteLog]);
                                    $batchModel->receive($pId, $oldQty, (float)($oldItem['cost_price'] ?: $prod['cost_price']), 'opname', $noteLog);
                                    $stockChangeNotes[] = "Hapus '{$oldItem['product_name']}' (+{$oldQty} stok)";
                                }
                            }
                            $delStmt = $this->db->prepare("DELETE FROM pos_transaction_items WHERE id = ?");
                            $delStmt->execute([$itemId]);
                        } else {
                            // Qty berubah atau tetap
                            $diff = $newQty - $oldQty;
                            if ($diff !== 0 && $isManual === 0 && $pId > 0) {
                                $prodStmt->execute([$pId]);
                                $prod = $prodStmt->fetch(PDO::FETCH_ASSOC);
                                if ($prod) {
                                    $oldStock = (int)$prod['stock'];
                                    if ($diff < 0) {
                                        // Qty berkurang -> kembalikan ke stok gudang
                                        $retQty = abs($diff);
                                        $newStock = $oldStock + $retQty;
                                        $stockUpd->execute([$retQty, $pId]);
                                        $noteLog = "Penyesuaian Transaksi {$invoiceNo} - Koreksi stok '{$oldItem['product_name']}' ({$oldQty} -> {$newQty} pcs)";
                                        $logStmt->execute([$pId, 'opname_adjustment', $retQty, $oldStock, $newStock, $noteLog]);
                                        $batchModel->receive($pId, $retQty, (float)($oldItem['cost_price'] ?: $prod['cost_price']), 'opname', $noteLog);
                                        $stockChangeNotes[] = "'{$oldItem['product_name']}' berkurang {$retQty} pcs (stok +{$retQty})";
                                    } else {
                                        // Qty bertambah -> potong stok gudang
                                        $addQty = $diff;
                                        $newStock = $oldStock - $addQty;
                                        $stockUpd->execute([-$addQty, $pId]);
                                        $noteLog = "Penyesuaian Transaksi {$invoiceNo} - Tambah belanja '{$oldItem['product_name']}' ({$oldQty} -> {$newQty} pcs)";
                                        $logStmt->execute([$pId, 'sale', $addQty, $oldStock, $newStock, $noteLog]);
                                        $batchModel->consumeFifo($pId, $addQty, (float)($oldItem['cost_price'] ?: $prod['cost_price']));
                                        $stockChangeNotes[] = "'{$oldItem['product_name']}' bertambah {$addQty} pcs (stok -{$addQty})";
                                    }
                                }
                            }

                            // Update pos_transaction_items
                            $sellingPrice = (float)($ai['selling_price'] ?? $oldItem['selling_price']);
                            $costPrice = (float)$oldItem['cost_price'];
                            $subtotal = $newQty * $sellingPrice;
                            $subtotalCost = $newQty * $costPrice;

                            $updItem = $this->db->prepare("UPDATE pos_transaction_items SET quantity = ?, selling_price = ?, subtotal = ?, subtotal_cost = ? WHERE id = ?");
                            $updItem->execute([$newQty, $sellingPrice, $subtotal, $subtotalCost, $itemId]);
                        }
                    } elseif ($productId !== null && $productId > 0 && $newQty > 0) {
                        // Item baru yang ditambahkan ke transaksi
                        $prodStmt->execute([$productId]);
                        $prod = $prodStmt->fetch(PDO::FETCH_ASSOC);
                        if ($prod) {
                            $prodName = (string)$prod['name'];
                            $costPrice = (float)$prod['cost_price'];
                            $sellingPrice = (float)($ai['selling_price'] ?? $prod['selling_price']);
                            if ($sellingPrice <= 0) $sellingPrice = (float)$prod['selling_price'];

                            if ($isManual === 0) {
                                $oldStock = (int)$prod['stock'];
                                $newStock = $oldStock - $newQty;
                                $stockUpd->execute([-$newQty, $productId]);
                                $noteLog = "Penyesuaian Transaksi {$invoiceNo} - Tambah item baru '{$prodName}' ({$newQty} pcs)";
                                $logStmt->execute([$productId, 'sale', $newQty, $oldStock, $newStock, $noteLog]);
                                $batchModel->consumeFifo($productId, $newQty, $costPrice);
                                $stockChangeNotes[] = "Tambah item baru '{$prodName}' {$newQty} pcs (stok -{$newQty})";
                            }

                            $subtotal = $newQty * $sellingPrice;
                            $subtotalCost = $newQty * $costPrice;

                            $insItem = $this->db->prepare("INSERT INTO pos_transaction_items 
                                (transaction_id, product_id, product_name, cost_price, selling_price, quantity, subtotal, subtotal_cost)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                            $insItem->execute([$id, $productId, $prodName, $costPrice, $sellingPrice, $newQty, $subtotal, $subtotalCost]);
                        }
                    }
                }

                // Cek item lama yang tidak ada di submitted array sama sekali (dihapus lewat UI)
                foreach ($currentItems as $cId => $oldItem) {
                    if (!in_array($cId, $processedItemIds, true)) {
                        $pId = $oldItem['product_id'] ? (int)$oldItem['product_id'] : null;
                        $oldQty = (int)$oldItem['quantity'];
                        if ($isManual === 0 && $pId > 0 && $oldQty > 0) {
                            $prodStmt->execute([$pId]);
                            $prod = $prodStmt->fetch(PDO::FETCH_ASSOC);
                            if ($prod) {
                                $oldStock = (int)$prod['stock'];
                                $newStock = $oldStock + $oldQty;
                                $stockUpd->execute([$oldQty, $pId]);
                                $noteLog = "Penyesuaian Transaksi {$invoiceNo} - Hapus item '{$oldItem['product_name']}' (kembalikan {$oldQty} pcs)";
                                $logStmt->execute([$pId, 'opname_adjustment', $oldQty, $oldStock, $newStock, $noteLog]);
                                $batchModel->receive($pId, $oldQty, (float)($oldItem['cost_price'] ?: $prod['cost_price']), 'opname', $noteLog);
                                $stockChangeNotes[] = "Hapus '{$oldItem['product_name']}' (+{$oldQty} stok)";
                            }
                        }
                        $delStmt = $this->db->prepare("DELETE FROM pos_transaction_items WHERE id = ?");
                        $delStmt->execute([$cId]);
                    }
                }
            }

            // Hitung ulang total transaksi dari item yang tersisa
            $stmtRecalc = $this->db->prepare("
                SELECT 
                    COUNT(*) AS count_items,
                    COALESCE(SUM(subtotal), 0) AS total_amount,
                    COALESCE(SUM(subtotal_cost), 0) AS total_cost
                FROM pos_transaction_items 
                WHERE transaction_id = ?
            ");
            $stmtRecalc->execute([$id]);
            $recalc = $stmtRecalc->fetch(PDO::FETCH_ASSOC);

            if ((int)($recalc['count_items'] ?? 0) === 0) {
                throw new \Exception('Transaksi harus memiliki minimal 1 item barang. Jika ingin membatalkan seluruh transaksi, silakan gunakan tombol Batalkan/Void Transaksi.');
            }

            $totalAmount = (float)$recalc['total_amount'];
            $totalCost   = (float)$recalc['total_cost'];
            $grossProfit = $totalAmount - $totalCost;

            // Hitung pembayaran (Tunai vs QRIS)
            $paymentMethod = in_array($data['payment_method'] ?? '', ['cash', 'qris'], true) ? $data['payment_method'] : $tx['payment_method'];

            if ($paymentMethod === 'cash') {
                $cashPaid = isset($data['cash_paid']) ? (float)$data['cash_paid'] : (float)$tx['cash_paid'];
                if ($cashPaid < $totalAmount) {
                    $cashPaid = $totalAmount;
                }
                $changeReturned = max(0, $cashPaid - $totalAmount);
            } else {
                $cashPaid = $totalAmount;
                $changeReturned = 0.0;
            }

            $customerName = trim($data['customer_name'] ?? $tx['customer_name']);
            if ($customerName === '') {
                $customerName = 'Umum / Siswa';
            }

            $reason = trim($data['adjustment_note'] ?? $data['manual_note'] ?? '');
            if ($reason === '' && !empty($stockChangeNotes)) {
                $reason = implode(', ', $stockChangeNotes);
            } elseif ($reason === '') {
                $reason = 'Penyesuaian transaksi kasir';
            }

            // Update pos_transactions
            $stmtUpdTx = $this->db->prepare("
                UPDATE `{$this->table}` SET
                    total_amount = ?,
                    total_cost = ?,
                    gross_profit = ?,
                    payment_method = ?,
                    cash_paid = ?,
                    change_returned = ?,
                    customer_name = ?,
                    is_adjusted = 1,
                    adjusted_at = NOW(),
                    adjustment_note = ?,
                    adjusted_by = ?
                WHERE id = ?
            ");
            $stmtUpdTx->execute([
                $totalAmount,
                $totalCost,
                $grossProfit,
                $paymentMethod,
                $cashPaid,
                $changeReturned,
                $customerName,
                $reason,
                $userId,
                $id
            ]);

            if (!$inExternalTx) {
                $this->db->commit();
            }

            return [
                'success'       => true,
                'total_amount'  => $totalAmount,
                'stock_message' => !empty($stockChangeNotes) ? 'Perubahan stok: ' . implode('; ', $stockChangeNotes) : '',
            ];
        } catch (\Throwable $e) {
            if (!$inExternalTx && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Batalkan / Void Transaksi Salah Input dengan pengembalian stok otomatis.
     */
    public function voidTransaction(int $id, string $reason = ''): array {
        $tx = $this->find($id);
        if (!$tx) {
            return ['success' => false, 'error' => 'Transaksi tidak ditemukan.'];
        }

        $inExternalTx = $this->db->inTransaction();
        if (!$inExternalTx) {
            $this->db->beginTransaction();
        }
        try {
            $invoiceNo = $tx['invoice_no'];
            $isManual = (int)($tx['is_manual'] ?? 0);

            // Jika transaksi POS (memotong stok fisik), kembalikan stok produk & catat log FIFO
            if ($isManual === 0) {
                $itemStmt = $this->db->prepare("SELECT * FROM pos_transaction_items WHERE transaction_id = ?");
                $itemStmt->execute([$id]);
                $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

                $prodStmt = $this->db->prepare("SELECT id, stock, cost_price FROM canteen_products WHERE id = ? FOR UPDATE");
                $stockUpd = $this->db->prepare("UPDATE canteen_products SET stock = stock + ? WHERE id = ?");
                $logStmt = $this->db->prepare("INSERT INTO canteen_stock_logs (product_id, type, quantity, old_stock, new_stock, notes) VALUES (?, 'opname_adjustment', ?, ?, ?, ?)");
                $batchModel = new CanteenStockBatch();

                foreach ($items as $item) {
                    $pid = (int)($item['product_id'] ?? 0);
                    $qty = (int)($item['quantity'] ?? 0);
                    if ($pid > 0 && $qty > 0) {
                        $prodStmt->execute([$pid]);
                        $prod = $prodStmt->fetch(PDO::FETCH_ASSOC);
                        if ($prod) {
                            $oldStock = (int)$prod['stock'];
                            $newStock = $oldStock + $qty;
                            $stockUpd->execute([$qty, $pid]);

                            $note = "Pembatalan/Void Transaksi {$invoiceNo}" . ($reason !== '' ? " - {$reason}" : "");
                            $logStmt->execute([$pid, $qty, $oldStock, $newStock, $note]);

                            // Kembalikan ke batch FIFO aktif
                            $batchCost = (float)($item['cost_price'] ?: $prod['cost_price']);
                            $batchModel->receive($pid, $qty, $batchCost, 'opname', $note);
                        }
                    }
                }
            }

            // Hapus items transaksi
            $delItems = $this->db->prepare("DELETE FROM pos_transaction_items WHERE transaction_id = ?");
            $delItems->execute([$id]);

            // Hapus transaksi
            $delTx = $this->db->prepare("DELETE FROM `{$this->table}` WHERE id = ?");
            $delTx->execute([$id]);

            if (!$inExternalTx) {
                $this->db->commit();
            }
            return ['success' => true, 'invoice_no' => $invoiceNo];
        } catch (\Throwable $e) {
            if (!$inExternalTx && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Laporan Penjualan Harian — per item barang yang terjual.
     * Mengembalikan:
     *   summary   : ringkasan total pendapatan, qty, laba, tunai, qris
     *   items     : setiap baris item yang terjual (group by produk)
     *   hours     : distribusi per jam transaksi
     *   transactions : daftar transaksi lengkap beserta detail item
     *   payment_methods : breakdown metode pembayaran
     */
    public function getDailyItemReport(string $date): array {
        $params = [$date];

        // --- Ringkasan transaksi harian ---
        $stmtSum = $this->db->prepare("
            SELECT
                COUNT(DISTINCT t.id)                                              AS total_orders,
                COALESCE(SUM(t.total_amount), 0)                                  AS total_revenue,
                COALESCE(SUM(t.total_cost), 0)                                    AS total_cost,
                COALESCE(SUM(t.gross_profit), 0)                                  AS gross_profit,
                COALESCE(SUM(CASE WHEN t.payment_method='cash' THEN t.total_amount ELSE 0 END), 0) AS total_cash,
                COALESCE(SUM(CASE WHEN t.payment_method='qris' THEN t.total_amount ELSE 0 END), 0) AS total_qris,
                COALESCE(SUM(CASE WHEN t.is_manual=1 THEN t.total_amount ELSE 0 END), 0) AS total_manual,
                COALESCE(SUM(CASE WHEN t.is_manual=0 THEN t.total_amount ELSE 0 END), 0) AS total_pos,
                COALESCE(SUM(CASE WHEN t.payment_method='cash' THEN 1 ELSE 0 END), 0) AS count_cash,
                COALESCE(SUM(CASE WHEN t.payment_method='qris' THEN 1 ELSE 0 END), 0) AS count_qris,
                COALESCE(SUM(CASE WHEN t.is_adjusted=1 THEN 1 ELSE 0 END), 0) AS total_adjusted
            FROM pos_transactions t
            WHERE DATE(t.created_at) = ?
        ");
        $stmtSum->execute($params);
        $summary = $stmtSum->fetch(PDO::FETCH_ASSOC) ?: [];

        // --- Item terjual per produk (diurutkan terbanyak) ---
        $stmtItems = $this->db->prepare("
            SELECT
                COALESCE(i.product_name, '(Produk Dihapus)') AS product_name,
                i.product_id,
                SUM(i.quantity)                               AS total_qty,
                i.selling_price                               AS unit_price,
                SUM(i.subtotal)                               AS total_sales,
                SUM(i.subtotal_cost)                          AS total_cost,
                SUM(i.subtotal - i.subtotal_cost)             AS total_profit,
                ROUND(SUM(i.subtotal - i.subtotal_cost) / NULLIF(SUM(i.subtotal),0) * 100, 1) AS margin_pct
            FROM pos_transaction_items i
            JOIN pos_transactions t ON i.transaction_id = t.id
            WHERE DATE(t.created_at) = ?
            GROUP BY i.product_id, i.product_name, i.selling_price
            ORDER BY total_qty DESC, total_sales DESC
        ");
        $stmtItems->execute($params);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        // --- Distribusi penjualan per jam (0-23) ---
        $stmtHours = $this->db->prepare("
            SELECT
                HOUR(t.created_at) AS hour,
                COUNT(t.id)        AS tx_count,
                SUM(t.total_amount) AS revenue
            FROM pos_transactions t
            WHERE DATE(t.created_at) = ?
            GROUP BY HOUR(t.created_at)
            ORDER BY hour ASC
        ");
        $stmtHours->execute($params);
        $hoursRaw = $stmtHours->fetchAll(PDO::FETCH_ASSOC);
        // Buat array 0-23 untuk chart
        $hours = array_fill(0, 24, ['hour' => 0, 'tx_count' => 0, 'revenue' => 0]);
        foreach ($hoursRaw as $h) {
            $hrs = (int)$h['hour'];
            $hours[$hrs] = $h;
        }

        // --- Daftar transaksi lengkap dengan item ---
        $stmtTx = $this->db->prepare("
            SELECT t.*, u.name AS cashier_name, ua.name AS adjusted_by_name
            FROM pos_transactions t
            LEFT JOIN users u ON t.user_id = u.id
            LEFT JOIN users ua ON t.adjusted_by = ua.id
            WHERE DATE(t.created_at) = ?
            ORDER BY t.created_at DESC
        ");
        $stmtTx->execute($params);
        $transactions = $stmtTx->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($transactions)) {
            $stmtTxItems = $this->db->prepare("
                SELECT * FROM pos_transaction_items WHERE transaction_id = ? ORDER BY id ASC
            ");
            foreach ($transactions as &$tx) {
                $stmtTxItems->execute([(int)$tx['id']]);
                $tx['items'] = $stmtTxItems->fetchAll(PDO::FETCH_ASSOC);
            }
            unset($tx);
        }

        return [
            'summary'      => $summary,
            'items'        => $items,
            'hours'        => $hours,
            'transactions' => $transactions,
        ];
    }
}
