<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\CanteenProduct;
use App\Models\CanteenCategory;
use App\Models\CanteenStockLog;
use App\Models\CanteenStockBatch;
use App\Models\PosTransaction;
use App\Models\Setting;
use App\Models\Expense;
use App\Models\CanteenMonthlyBalance;
use App\Libraries\PdfWriter;

class CanteenController extends Controller {
    public function index(): void {
        $this->requireAuth(['admin']);
        $productModel = new CanteenProduct();
        $categoryModel = new CanteenCategory();

        $products = $productModel->getWithCategory();
        $categories = $categoryModel->all('name ASC');

        // HPP rata-rata (FIFO) dari batch stok yang masih tersisa
        $batchModel = new CanteenStockBatch();
        $avgCosts = $batchModel->averageCostsAll();
        foreach ($products as &$p) {
            $info = $avgCosts[(int)$p['id']] ?? null;
            $p['avg_cost'] = $info['avg_cost'] ?? (float)$p['cost_price'];
            $p['batch_count'] = $info['batch_count'] ?? 0;
        }
        unset($p);

        $counts = [];
        foreach ($categoryModel->raw("SELECT category_id, COUNT(*) AS total FROM canteen_products GROUP BY category_id") as $row) {
            $counts[(int)$row['category_id']] = (int)$row['total'];
        }

        $this->view('admin.canteen.index', [
            'products' => $products,
            'categories' => $categories,
            'categoryCounts' => $counts
        ], 'admin');
    }

    public function storeProduct(): void {
        $this->requireAuth(['admin']);
        $productModel = new CanteenProduct();

        $name = trim($_POST['name'] ?? '');
        $barcode = trim($_POST['barcode'] ?? '') ?: null;
        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $costPrice = (float)($_POST['cost_price'] ?? 0);
        $sellingPrice = (float)($_POST['selling_price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $minStock = (int)($_POST['min_stock'] ?? 5);

        if (empty($name) || $sellingPrice <= 0) {
            $this->setFlash('error', 'Nama produk dan harga jual wajib diisi.');
            $this->redirect('admin/canteen');
        }

        $upload = $this->handleProductImageUpload();
        if ($upload['error'] !== null) {
            $this->setFlash('error', $upload['error']);
            $this->redirect('admin/canteen');
        }

        try {
            $id = $productModel->create([
                'category_id' => $categoryId,
                'barcode' => $barcode,
                'name' => $name,
                'cost_price' => $costPrice,
                'selling_price' => $sellingPrice,
                'stock' => $stock,
                'min_stock' => $minStock,
                'image' => $upload['path'],
                'is_active' => 1
            ]);

            // Create initial stock log if stock > 0
            if ($stock > 0) {
                $logModel = new CanteenStockLog();
                $logModel->create([
                    'product_id' => $id,
                    'type' => 'in',
                    'quantity' => $stock,
                    'old_stock' => 0,
                    'new_stock' => $stock,
                    'notes' => 'Stok awal produk baru'
                ]);

                // Batch FIFO awal
                $batchModel = new CanteenStockBatch();
                $batchModel->receive($id, $stock, $costPrice, 'initial', 'Stok awal produk baru');
            }

            $this->setFlash('success', "Produk '{$name}' berhasil ditambahkan!");
        } catch (\Exception $e) {
            $this->setFlash('error', "Gagal menambah produk: " . $e->getMessage());
        }

        $this->redirect('admin/canteen');
    }

    public function updateProduct(int $id): void {
        $this->requireAuth(['admin']);
        $productModel = new CanteenProduct();

        $name = trim($_POST['name'] ?? '');
        $barcode = trim($_POST['barcode'] ?? '') ?: null;
        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $costPrice = (float)($_POST['cost_price'] ?? 0);
        $sellingPrice = (float)($_POST['selling_price'] ?? 0);
        $minStock = (int)($_POST['min_stock'] ?? 5);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $current = $productModel->find($id);
        if (!$current) {
            $this->setFlash('error', 'Produk tidak ditemukan.');
            $this->redirect('admin/canteen');
        }

        $oldImage = trim($current['image'] ?? '');
        $newImage = $oldImage;

        $upload = $this->handleProductImageUpload();
        if ($upload['error'] !== null) {
            $this->setFlash('error', $upload['error']);
            $this->redirect('admin/canteen');
        }

        if ($upload['path'] !== null) {
            $newImage = $upload['path'];
            $this->deleteProductImageFile($oldImage);
        } elseif (!empty($_POST['remove_image'])) {
            $newImage = null;
            $this->deleteProductImageFile($oldImage);
        }

        try {
            $productModel->update($id, [
                'category_id' => $categoryId,
                'barcode' => $barcode,
                'name' => $name,
                'cost_price' => $costPrice,
                'selling_price' => $sellingPrice,
                'min_stock' => $minStock,
                'image' => $newImage,
                'is_active' => $isActive
            ]);

            // Jika stok diubah pada modal edit produk, lakukan penyesuaian stok otomatis & sinkronisasi FIFO
            if (isset($_POST['stock'])) {
                $newStock = max(0, (int)$_POST['stock']);
                if ($newStock !== (int)$current['stock']) {
                    $adjusted = $productModel->adjustStock($id, $newStock, 'opname_adjustment', 'Koreksi stok pada edit produk');
                    if (!$adjusted) {
                        $this->setFlash('error', "Gagal memperbarui stok produk.");
                        $this->redirect('admin/canteen');
                        return;
                    }
                }
            }

            $this->setFlash('success', "Produk '{$name}' berhasil diperbarui!");
        } catch (\Exception $e) {
            $this->setFlash('error', "Gagal memperbarui produk: " . $e->getMessage());
        }

        $this->redirect('admin/canteen');
    }

    public function deleteProduct(int $id): void {
        $this->requireAuth(['admin']);
        $productModel = new CanteenProduct();

        $current = $productModel->find($id);
        if (!$current) {
            $this->setFlash('error', 'Produk tidak ditemukan.');
            $this->redirect('admin/canteen');
        }

        try {
            // Bersihkan data terkait agar produk bisa dihapus dari database
            $productModel->raw("DELETE FROM pos_transaction_items WHERE product_id = ?", [$id]);
            $productModel->raw("DELETE FROM canteen_stock_logs WHERE product_id = ?", [$id]);
            $productModel->raw("DELETE FROM canteen_stock_batches WHERE product_id = ?", [$id]);

            // Hapus transaksi POS yang menjadi kosong (tanpa item lagi)
            $productModel->raw(
                "DELETE FROM pos_transactions WHERE id NOT IN (SELECT DISTINCT transaction_id FROM pos_transaction_items)"
            );

            $productModel->delete($id);
            $this->deleteProductImageFile(trim($current['image'] ?? ''));
            $this->setFlash('success', "Produk '{$current['name']}' berhasil dihapus.");
        } catch (\Exception $e) {
            $this->setFlash('error', "Gagal menghapus produk: " . $e->getMessage());
        }

        $this->redirect('admin/canteen');
    }

    public function storeCategory(): void {
        $this->requireAuth(['admin']);
        $categoryModel = new CanteenCategory();

        $name = trim($_POST['name'] ?? '');

        if ($name === '') {
            $this->setFlash('error', 'Nama kategori wajib diisi.');
            $this->redirect('admin/canteen');
        }

        if (mb_strlen($name) > 50) {
            $this->setFlash('error', 'Nama kategori maksimal 50 karakter.');
            $this->redirect('admin/canteen');
        }

        if (!empty($categoryModel->where('name', $name, 'id ASC'))) {
            $this->setFlash('error', "Kategori '{$name}' sudah ada.");
            $this->redirect('admin/canteen');
        }

        try {
            $categoryModel->create(['name' => $name]);
            $this->setFlash('success', "Kategori '{$name}' berhasil ditambahkan!");
        } catch (\Exception $e) {
            $this->setFlash('error', "Gagal menambah kategori: " . $e->getMessage());
        }

        $this->redirect('admin/canteen');
    }

    public function updateCategory(int $id): void {
        $this->requireAuth(['admin']);
        $categoryModel = new CanteenCategory();

        $current = $categoryModel->find($id);
        if (!$current) {
            $this->setFlash('error', 'Kategori tidak ditemukan.');
            $this->redirect('admin/canteen');
        }

        $name = trim($_POST['name'] ?? '');

        if ($name === '') {
            $this->setFlash('error', 'Nama kategori wajib diisi.');
            $this->redirect('admin/canteen');
        }

        if (mb_strlen($name) > 50) {
            $this->setFlash('error', 'Nama kategori maksimal 50 karakter.');
            $this->redirect('admin/canteen');
        }

        foreach ($categoryModel->where('name', $name, 'id ASC') as $row) {
            if ((int)$row['id'] !== $id) {
                $this->setFlash('error', "Kategori '{$name}' sudah ada.");
                $this->redirect('admin/canteen');
            }
        }

        try {
            $categoryModel->update($id, ['name' => $name]);
            $this->setFlash('success', "Kategori berhasil diperbarui menjadi '{$name}'!");
        } catch (\Exception $e) {
            $this->setFlash('error', "Gagal memperbarui kategori: " . $e->getMessage());
        }

        $this->redirect('admin/canteen');
    }

    public function deleteCategory(int $id): void {
        $this->requireAuth(['admin']);
        $categoryModel = new CanteenCategory();

        $current = $categoryModel->find($id);
        if (!$current) {
            $this->setFlash('error', 'Kategori tidak ditemukan.');
            $this->redirect('admin/canteen');
        }

        $productModel = new CanteenProduct();
        $row = $productModel->raw("SELECT COUNT(*) AS total FROM canteen_products WHERE category_id = ?", [$id]);
        $used = (int)($row[0]['total'] ?? 0);

        try {
            $categoryModel->delete($id);
            $suffix = $used > 0 ? " {$used} produk menjadi tanpa kategori." : '';
            $this->setFlash('success', "Kategori '{$current['name']}' berhasil dihapus." . $suffix);
        } catch (\Exception $e) {
            $this->setFlash('error', "Gagal menghapus kategori: " . $e->getMessage());
        }

        $this->redirect('admin/canteen');
    }

    public function opname(): void {
        $this->requireAuth(['admin']);
        $productModel = new CanteenProduct();
        $logModel = new CanteenStockLog();

        $productId = (int)($_POST['product_id'] ?? 0);
        $newStock = (int)($_POST['actual_stock'] ?? 0);
        $notes = trim($_POST['notes'] ?? 'Stok opname berkala');

        if (!$productId || $newStock < 0) {
            $this->setFlash('error', 'Pilih produk dan masukkan jumlah stok yang valid.');
            $this->redirect('admin/canteen/opname');
        }

        $productModel->adjustStock($productId, $newStock, 'opname_adjustment', $notes);
        $this->setFlash('success', 'Stok fisik berhasil disesuaikan dan dicatat di log opname.');
        $this->redirect('admin/canteen/opname');
    }

    public function restock(): void {
        $this->requireAuth(['admin']);
        $productModel = new CanteenProduct();

        $productId = (int)($_POST['product_id'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 0);
        $unitCost = (float)($_POST['unit_cost'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        $product = $productModel->find($productId);
        if (!$product || $qty <= 0) {
            $this->setFlash('error', 'Pilih produk dan masukkan jumlah barang masuk yang valid.');
            $this->redirect('admin/canteen/opname');
        }

        if ($unitCost < 0) {
            $this->setFlash('error', 'Harga beli tidak boleh negatif.');
            $this->redirect('admin/canteen/opname');
        }

        if ($productModel->receiveStock($productId, $qty, $unitCost, $notes !== '' ? $notes : null)) {
            $this->setFlash('success', "Barang masuk {$qty} pcs '{$product['name']}' berhasil dicatat (harga beli " . formatRupiah($unitCost) . ").");
        } else {
            $this->setFlash('error', 'Gagal mencatat barang masuk.');
        }

        $this->redirect('admin/canteen/opname');
    }

    public function showOpname(): void {
        $this->requireAuth(['admin']);
        $productModel = new CanteenProduct();
        $logModel = new CanteenStockLog();
        $batchModel = new CanteenStockBatch();

        $products = $productModel->getWithCategory();
        $logs = $logModel->getLogs(40);
        $batches = $batchModel->getActiveWithProduct(200);

        $this->view('admin.canteen.opname', [
            'products' => $products,
            'logs' => $logs,
            'batches' => $batches
        ], 'admin');
    }

    public function report(): void {
        $this->requireAuth(['admin']);
        $posModel = new PosTransaction();
        $expenseModel = new Expense();

        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');

        $report = $posModel->getProfitLossReport($startDate, $endDate);
        $summary = $report['summary'];
        $topProducts = $report['top_products'];

        $canteenExpenses = $expenseModel->getCanteenExpenses($startDate, $endDate);
        $grossProfit = (float)($summary['gross_profit'] ?? 0);
        $canteenExpenseTotal = (float)($canteenExpenses['total'] ?? 0);
        $netProfit = $grossProfit - $canteenExpenseTotal;
        $totalRevenue = (float)($summary['total_revenue'] ?? 0);
        $netMargin = $totalRevenue > 0 ? round(($netProfit / $totalRevenue) * 100, 1) : 0;
        $grossMargin = $totalRevenue > 0 ? round(($grossProfit / $totalRevenue) * 100, 1) : 0;

        $this->view('admin.canteen.report', [
            'summary' => $summary,
            'topProducts' => $topProducts,
            'canteenExpenses' => $canteenExpenses,
            'grossProfit' => $grossProfit,
            'grossMargin' => $grossMargin,
            'canteenExpenseTotal' => $canteenExpenseTotal,
            'netProfit' => $netProfit,
            'netMargin' => $netMargin,
            'startDate' => $startDate,
            'endDate' => $endDate
        ], 'admin');
    }

    public function reportPdf(): void {
        $this->requireAuth(['admin']);

        $posModel = new PosTransaction();
        $settingModel = new Setting();
        $expenseModel = new Expense();

        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');

        $report = $posModel->getProfitLossReport($startDate, $endDate);
        $summary = $report['summary'];
        $topProducts = $report['top_products'];
        $canteenExpenses = $expenseModel->getCanteenExpenses($startDate, $endDate);

        $grossProfit = (float)($summary['gross_profit'] ?? 0);
        $canteenExpenseTotal = (float)($canteenExpenses['total'] ?? 0);
        $netProfit = $grossProfit - $canteenExpenseTotal;
        $totalRevenue = (float)($summary['total_revenue'] ?? 0);
        $grossMargin = $totalRevenue > 0 ? round(($grossProfit / $totalRevenue) * 100, 1) : 0;
        $netMargin = $totalRevenue > 0 ? round(($netProfit / $totalRevenue) * 100, 1) : 0;

        $settings = $settingModel->getAllKeyValue();
        $appName = (string)($settings['app_name'] ?? APP_NAME);
        $address = (string)($settings['address'] ?? '');

        $periodLabel = tanggalIndo($startDate, 'd F Y') . ' s/d ' . tanggalIndo($endDate, 'd F Y');

        $pdf = new PdfWriter('P', 'a4');
        $pdf->setTitle('LAPORAN PENDAPATAN DAN PENGELUARAN PEPSI FOOD & DRINK');

        // Kop surat
        $top = $pdf->y;
        $pdf->setFont(true, 12);
        $pdf->setTextColor(0.09, 0.16, 0.31);
        $pdf->text($pdf->lMargin, $top, $appName);
        if ($address !== '') {
            $pdf->setFont(false, 8);
            $pdf->setTextColor(0.45, 0.48, 0.55);
            $pdf->text($pdf->lMargin, $top - 11, $address);
        }
        $pdf->setTextColor(0, 0, 0);
        $pdf->setY($top - 30);

        $title = 'LAPORAN PENDAPATAN & PENGELUARAN PEPSI FOOD & DRINK';
        $pdf->setFont(true, 13);
        $pdf->setTextColor(0.09, 0.16, 0.31);
        $pdf->text(($pdf->pageW - $pdf->textWidth($title, true, 13)) / 2, $pdf->y, $title);

        $pdf->setY($pdf->y - 15);
        $sub = 'Periode: ' . $periodLabel;
        $pdf->setFont(false, 9);
        $pdf->setTextColor(0.45, 0.48, 0.55);
        $pdf->text(($pdf->pageW - $pdf->textWidth($sub, false, 9)) / 2, $pdf->y, $sub);

        $pdf->setY($pdf->y - 10);
        $pdf->line($pdf->lMargin, $pdf->y, $pdf->pageW - $pdf->rMargin, $pdf->y, 0.09, 0.16, 0.31, 1.1);
        $pdf->setY($pdf->y - 16);
        $pdf->setTextColor(0, 0, 0);

        $summaryColumns = [
            ['label' => 'Ringkasan Laba Rugi Pepsi Food & Drink', 'w' => 300],
            ['label' => 'Nilai', 'w' => 210, 'align' => 'right'],
        ];
        $summaryRows = [
            ['Total Pendapatan Penjualan (Omset)', formatRupiah($summary['total_revenue'])],
            ['  - Penjualan Kasir (POS)', formatRupiah($summary['total_pos'] ?? $summary['total_revenue'])],
            ['  - Penjualan Tambahan (Manual)', formatRupiah($summary['total_manual'] ?? 0)],
            ['Total Modal Pokok / HPP Produk Terjual', '(' . formatRupiah($summary['total_cost']) . ')'],
            ['Laba Kotor Penjualan (Omset - HPP)', formatRupiah($grossProfit)],
            ['Margin Laba Kotor', $grossMargin . '%'],
            ['Pengeluaran Operasional Pepsi Food & Drink (Beban Operasional)', '(' . formatRupiah($canteenExpenseTotal) . ')'],
            ['Laba Bersih Akhir Pepsi Food & Drink', formatRupiah($netProfit)],
            ['Margin Laba Bersih Akhir', $netMargin . '%'],
            ['Total Transaksi Penjualan', (int)$summary['total_orders'] . ' transaksi'],
            ['Pembayaran Tunai & Cash', formatRupiah($summary['total_cash'])],
            ['Pembayaran QRIS', formatRupiah($summary['total_qris'] ?? 0)],
        ];
        $pdf->table($summaryColumns, $summaryRows, ['fontSize' => 9, 'headerFontSize' => 9]);

        // Tabel Pengeluaran Operasional Kantin (bila ada)
        if (!empty($canteenExpenses['rows'])) {
            $pdf->setY($pdf->y - 16);
            $pdf->setFont(true, 10);
            $pdf->setTextColor(0.09, 0.16, 0.31);
            $pdf->text($pdf->lMargin, $pdf->y, 'Rincian Pengeluaran Operasional Pepsi Food & Drink');
            $pdf->setY($pdf->y - 12);
            $pdf->setTextColor(0, 0, 0);

            $expCols = [
                ['label' => 'No', 'w' => 28, 'align' => 'center'],
                ['label' => 'Tanggal', 'w' => 65],
                ['label' => 'Keterangan Pengeluaran', 'w' => 237],
                ['label' => 'Metode', 'w' => 65],
                ['label' => 'Nominal', 'w' => 115, 'align' => 'right'],
            ];
            $expRows = [];
            $noE = 0;
            foreach ($canteenExpenses['rows'] as $ce) {
                $noE++;
                $expRows[] = [
                    (string)$noE,
                    date('d/m/Y', strtotime($ce['expense_date'])),
                    (string)$ce['description'] . (!empty($ce['notes']) ? ' (' . $ce['notes'] . ')' : ''),
                    ucfirst((string)($ce['payment_method'] ?? 'tunai')),
                    formatRupiah($ce['amount']),
                ];
            }
            $expRows[] = [
                '',
                '',
                'TOTAL PENGELUARAN OPERASIONAL PEPSI FOOD & DRINK',
                '',
                formatRupiah($canteenExpenseTotal),
            ];
            $pdf->table($expCols, $expRows, ['fontSize' => 8.5, 'headerFontSize' => 8.5]);
        }

        $pdf->setY($pdf->y - 16);
        $pdf->setFont(true, 10);
        $pdf->setTextColor(0.09, 0.16, 0.31);
        $pdf->text($pdf->lMargin, $pdf->y, '10 Produk Paling Laris');
        $pdf->setY($pdf->y - 12);
        $pdf->setTextColor(0, 0, 0);

        $productColumns = [
            ['label' => 'No', 'w' => 28, 'align' => 'center'],
            ['label' => 'Nama Produk', 'w' => 227],
            ['label' => 'Terjual', 'w' => 65, 'align' => 'center'],
            ['label' => 'Total Penjualan', 'w' => 115, 'align' => 'right'],
            ['label' => 'Kontribusi Laba', 'w' => 75, 'align' => 'right'],
        ];
        $productRows = [];
        $no = 0;
        foreach ($topProducts as $tp) {
            $no++;
            $productRows[] = [
                (string)$no,
                (string)$tp['product_name'],
                (int)$tp['total_qty'] . ' pcs',
                formatRupiah($tp['total_sales']),
                formatRupiah($tp['total_profit']),
            ];
        }
        if ($productRows === []) {
            $productRows[] = 'Belum ada transaksi pada rentang tanggal ini.';
        }
        $pdf->table($productColumns, $productRows, ['fontSize' => 9, 'headerFontSize' => 9]);

        $bin = $pdf->output();
        $filename = 'Laporan-Pendapatan-Pengeluaran-PepsiFoodDrink-' . $startDate . '_' . $endDate . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($bin));
        header('Cache-Control: private, max-age=0, must-revalidate');
        echo $bin;
        exit;
    }

    /**
     * Laporan Bulanan Pepsi Food & Drink (1 Bulan Sekali dengan Saldo Berjalan Tiap Bulan)
     */
    public function monthlyReport(): void {
        $this->requireAuth(['admin']);
        $balanceModel = new CanteenMonthlyBalance();
        $settingModel = new Setting();

        $month = trim($_GET['month'] ?? date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        $report = $balanceModel->getMonthReport($month);

        $this->view('admin.canteen.monthly_report', [
            'report'      => $report,
            'current'     => $report['current'],
            'rolling'     => $report['rolling'],
            'yearMonth'   => $report['year_month'],
            'monthLabel'  => $report['month_label'],
            'prevMonth'   => $report['prev_month'],
            'nextMonth'   => $report['next_month'],
            'months'      => $balanceModel->getAvailableMonths(),
            'settings'    => $settingModel->getAllKeyValue(),
        ], 'admin');
    }

    /**
     * Simpan / Koreksi Saldo Awal Manual Kantin untuk Bulan Tertentu
     */
    public function setMonthlyBalance(): void {
        $this->requireAuth(['admin']);
        $balanceModel = new CanteenMonthlyBalance();

        $yearMonth = trim($_POST['year_month'] ?? date('Y-m'));
        $action = trim($_POST['action'] ?? 'save');
        $notes = trim($_POST['notes'] ?? '');

        if ($action === 'reset') {
            $balanceModel->resetManualOpeningBalance($yearMonth);
            $this->setFlash('success', "Saldo awal untuk bulan {$yearMonth} telah di-reset mengikuti saldo akhir bulan sebelumnya.");
        } else {
            $amount = (float)($_POST['opening_balance'] ?? 0);
            $balanceModel->setManualOpeningBalance($yearMonth, $amount, $notes ?: null);
            $this->setFlash('success', "Saldo awal bulan {$yearMonth} berhasil disimpan: " . formatRupiah($amount));
        }

        $this->redirect('admin/canteen/monthly-report?month=' . urlencode($yearMonth));
    }

    /**
     * Cetak PDF Resmi Laporan Bulanan Pepsi Food & Drink (A4)
     */
    public function monthlyReportPdf(): void {
        $this->requireAuth(['admin']);
        $balanceModel = new CanteenMonthlyBalance();
        $settingModel = new Setting();

        $month = trim($_GET['month'] ?? date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        $report = $balanceModel->getMonthReport($month);
        $cur = $report['current'];
        $settings = $settingModel->getAllKeyValue();
        $appName = (string)($settings['app_name'] ?? APP_NAME);
        $address = (string)($settings['address'] ?? '');

        $pdf = new PdfWriter('P', 'a4');
        $pdf->setTitle('LAPORAN BULANAN PEPSI FOOD & DRINK - ' . $cur['month_label']);

        // Kop surat
        $top = $pdf->y;
        $pdf->setFont(true, 12);
        $pdf->setTextColor(0.09, 0.16, 0.31);
        $pdf->text($pdf->lMargin, $top, $appName);
        if ($address !== '') {
            $pdf->setFont(false, 8);
            $pdf->setTextColor(0.45, 0.48, 0.55);
            $pdf->text($pdf->lMargin, $top - 11, $address);
        }
        $pdf->setTextColor(0, 0, 0);
        $pdf->setY($top - 30);

        $title = 'LAPORAN BULANAN KEUANGAN PEPSI FOOD & DRINK';
        $pdf->setFont(true, 13);
        $pdf->setTextColor(0.09, 0.16, 0.31);
        $pdf->text(($pdf->pageW - $pdf->textWidth($title, true, 13)) / 2, $pdf->y, $title);

        $pdf->setY($pdf->y - 15);
        $sub = 'Periode: ' . $cur['month_label'] . ' (' . date('01/m/Y', strtotime($month . '-01')) . ' s/d ' . date('t/m/Y', strtotime($month . '-01')) . ')';
        $pdf->setFont(false, 9);
        $pdf->setTextColor(0.45, 0.48, 0.55);
        $pdf->text(($pdf->pageW - $pdf->textWidth($sub, false, 9)) / 2, $pdf->y, $sub);

        $pdf->setY($pdf->y - 10);
        $pdf->line($pdf->lMargin, $pdf->y, $pdf->pageW - $pdf->rMargin, $pdf->y, 0.09, 0.16, 0.31, 1.1);
        $pdf->setY($pdf->y - 16);
        $pdf->setTextColor(0, 0, 0);

        // Tabel Kesinambungan Saldo
        $summaryColumns = [
            ['label' => 'Arus Kas & Laba Berjalan (Sistem Berlanjut Tiap Bulan)', 'w' => 310],
            ['label' => 'Nominal (Rp)', 'w' => 200, 'align' => 'right'],
        ];
        $summaryRows = [
            ['SALDO BULAN LALU / SALDO AWAL', formatRupiah($cur['opening_balance']) . ($cur['is_manual_opening'] ? ' (Koreksi/Modal Awal)' : '')],
            ['Total Pendapatan Penjualan (Omset Pepsi Food & Drink)', formatRupiah($cur['revenue'])],
            ['  - Penjualan Kasir POS', formatRupiah($cur['total_pos'])],
            ['  - Penjualan Tambahan Manual', formatRupiah($cur['total_manual'])],
            ['Total Modal Pokok / HPP Produk Terjual', '(' . formatRupiah($cur['cost']) . ')'],
            ['Laba Kotor Penjualan (Omset - HPP)', formatRupiah($cur['gross_profit']) . ' (' . $cur['gross_margin'] . '%)'],
            ['Pengeluaran Operasional Pepsi Food & Drink (Beban Operasional)', '(' . formatRupiah($cur['expense_total']) . ')'],
            ['Laba Bersih Bulan Ini', formatRupiah($cur['net_profit']) . ' (' . $cur['net_margin'] . '%)'],
            ['SALDO AKHIR BULAN INI (Berlanjut ke Bulan Berikutnya)', formatRupiah($cur['closing_balance'])],
        ];
        $pdf->table($summaryColumns, $summaryRows, ['fontSize' => 9, 'headerFontSize' => 9]);

        // Rincian Pengeluaran Operasional Kantin
        if (!empty($cur['expenses'])) {
            $pdf->setY($pdf->y - 16);
            $pdf->setFont(true, 10);
            $pdf->setTextColor(0.09, 0.16, 0.31);
            $pdf->text($pdf->lMargin, $pdf->y, 'Rincian Pengeluaran Operasional Pepsi Food & Drink - ' . $cur['month_label']);
            $pdf->setY($pdf->y - 12);
            $pdf->setTextColor(0, 0, 0);

            $expCols = [
                ['label' => 'No', 'w' => 28, 'align' => 'center'],
                ['label' => 'Tanggal', 'w' => 65],
                ['label' => 'Keterangan Pengeluaran', 'w' => 237],
                ['label' => 'Metode', 'w' => 65],
                ['label' => 'Nominal', 'w' => 115, 'align' => 'right'],
            ];
            $expRows = [];
            $noE = 0;
            foreach ($cur['expenses'] as $ce) {
                $noE++;
                $expRows[] = [
                    (string)$noE,
                    date('d/m/Y', strtotime($ce['expense_date'])),
                    (string)$ce['description'] . (!empty($ce['notes']) ? ' (' . $ce['notes'] . ')' : ''),
                    ucfirst((string)($ce['payment_method'] ?? 'tunai')),
                    formatRupiah($ce['amount']),
                ];
            }
            $expRows[] = [
                '',
                '',
                'TOTAL PENGELUARAN OPERASIONAL PEPSI FOOD & DRINK',
                '',
                formatRupiah($cur['expense_total']),
            ];
            $pdf->table($expCols, $expRows, ['fontSize' => 8.5, 'headerFontSize' => 8.5]);
        }

        // Histori Berjalan Bulan ke Bulan (Continuous Rolling Summary)
        if (!empty($report['rolling'])) {
            $pdf->setY($pdf->y - 16);
            $pdf->setFont(true, 10);
            $pdf->setTextColor(0.09, 0.16, 0.31);
            $pdf->text($pdf->lMargin, $pdf->y, 'Rekapitulasi Saldo Berlanjut Antar Bulan (Continuous Rolling Ledger)');
            $pdf->setY($pdf->y - 12);
            $pdf->setTextColor(0, 0, 0);

            $rollCols = [
                ['label' => 'Bulan', 'w' => 85],
                ['label' => 'Saldo Awal', 'w' => 85, 'align' => 'right'],
                ['label' => 'Omset', 'w' => 85, 'align' => 'right'],
                ['label' => 'HPP Terjual', 'w' => 75, 'align' => 'right'],
                ['label' => 'Operasional', 'w' => 80, 'align' => 'right'],
                ['label' => 'Saldo Akhir', 'w' => 100, 'align' => 'right'],
            ];
            $rollRows = [];
            foreach ($report['rolling'] as $rym => $rData) {
                $isTarget = ($rym === $month);
                $rollRows[] = [
                    $rData['month_label'] . ($isTarget ? ' (*)' : ''),
                    formatRupiah($rData['opening_balance']),
                    formatRupiah($rData['revenue']),
                    formatRupiah($rData['cost']),
                    formatRupiah($rData['expense_total']),
                    formatRupiah($rData['closing_balance']),
                ];
            }
            $pdf->table($rollCols, $rollRows, ['fontSize' => 8, 'headerFontSize' => 8]);
        }

        $bin = $pdf->output();
        $filename = 'Laporan-Bulanan-PepsiFoodDrink-' . $month . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($bin));
        header('Cache-Control: private, max-age=0, must-revalidate');
        echo $bin;
        exit;
    }

    public function barcode(): void {
        $this->requireAuth(['admin']);
        $productModel = new CanteenProduct();
        $categoryModel = new CanteenCategory();

        $products = $productModel->getWithCategory();
        $categories = $categoryModel->all('name ASC');

        $this->view('admin.canteen.barcode', [
            'products' => $products,
            'categories' => $categories
        ], 'admin');
    }

    /**
     * Upload foto produk baru dari form tambah / edit.
     * @return array{path: ?string, error: ?string} path relatif (uploads/products/...) atau pesan error
     */
    private function handleProductImageUpload(): array {
        $file = $_FILES['image'] ?? null;
        $noFile = !$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE;

        if ($noFile) {
            return ['path' => null, 'error' => null];
        }

        $maxSize = 3 * 1024 * 1024;

        if ($file['error'] !== UPLOAD_ERR_OK) {
            if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
                return ['path' => null, 'error' => 'Ukuran foto produk melebihi batas. Maksimal ' . ($maxSize / 1024 / 1024) . ' MB.'];
            }
            return ['path' => null, 'error' => 'Upload foto produk gagal (kode error ' . (int)$file['error'] . ').'];
        }

        if ($file['size'] > $maxSize) {
            return ['path' => null, 'error' => 'Ukuran foto produk maksimal ' . ($maxSize / 1024 / 1024) . ' MB.'];
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            return ['path' => null, 'error' => 'File foto produk tidak valid.'];
        }

        $allowed = [
            'image/png'  => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            'image/gif'  => 'gif'
        ];

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $ext = $allowed[$mime] ?? null;

        if ($ext === null) {
            return ['path' => null, 'error' => 'Format foto tidak didukung. Gunakan PNG, JPG, WEBP, atau GIF.'];
        }

        $dir = UPLOAD_DIR . '/products';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return ['path' => null, 'error' => 'Folder upload tidak dapat dibuat di server.'];
        }

        $name = 'prod-' . date('Ymd') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
            return ['path' => null, 'error' => 'Gagal menyimpan foto. Pastikan folder uploads/ dapat ditulis (writable).'];
        }
        @chmod($dir . '/' . $name, 0644);

        return ['path' => 'uploads/products/' . $name, 'error' => null];
    }

    private function deleteProductImageFile(string $relativePath): void {
        $relativePath = trim($relativePath);
        if ($relativePath === '' || preg_match('#^https?://#i', $relativePath)) {
            return;
        }

        $relative = ltrim(str_replace('\\', '/', $relativePath), '/');
        if (strpos($relative, 'uploads/products/') !== 0) {
            return;
        }

        $absolute = BASE_PATH . '/' . $relative;
        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }

    /**
     * Laporan Penjualan Harian Per Produk Pepsi Food & Drink
     */
    public function dailyReport(): void {
        $this->requireAuth(['admin']);
        $posModel = new PosTransaction();
        $settingModel = new Setting();

        $date = $_GET['date'] ?? date('Y-m-d');
        // Validasi format tanggal
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        $report = $posModel->getDailyItemReport($date);

        $this->view('admin.canteen.daily_report', [
            'date'         => $date,
            'summary'      => $report['summary'],
            'items'        => $report['items'],
            'hours'        => $report['hours'],
            'transactions' => $report['transactions'],
            'settings'     => $settingModel->getAllKeyValue(),
        ], 'admin');
    }

    /**
     * Halaman Menu Penyesuaian & Riwayat Transaksi Pepsi Food & Drink
     */
    public function transactions(): void {
        $this->requireAuth(['admin']);
        $posModel = new PosTransaction();
        $productModel = new CanteenProduct();

        $startDate = trim($_GET['start_date'] ?? date('Y-m-01'));
        $endDate = trim($_GET['end_date'] ?? date('Y-m-d'));
        $paymentMethod = trim($_GET['payment_method'] ?? 'all');
        $type = trim($_GET['type'] ?? 'all');
        $statusAdjusted = trim($_GET['status_adjusted'] ?? 'all');
        $search = trim($_GET['search'] ?? '');

        $filters = [
            'start_date'      => $startDate !== '' ? $startDate : null,
            'end_date'        => $endDate !== '' ? $endDate : null,
            'payment_method'  => $paymentMethod !== 'all' ? $paymentMethod : null,
            'type'            => $type,
            'status_adjusted' => $statusAdjusted,
            'search'          => $search,
        ];

        $transactions = $posModel->getTransactionsForAdjustment($filters);
        $summary = $posModel->getAdjustmentSummary($filters);
        $products = $productModel->getActiveForPos();

        $this->view('admin.canteen.transactions', [
            'transactions'    => $transactions,
            'summary'         => $summary,
            'products'        => $products,
            'startDate'       => $startDate,
            'endDate'         => $endDate,
            'paymentMethod'   => $paymentMethod,
            'type'            => $type,
            'statusAdjusted'  => $statusAdjusted,
            'search'          => $search,
        ], 'admin');
    }

    /**
     * Update Penyesuaian Transaksi (Metode Bayar Tunai <-> QRIS, cash paid, rincian item & stok barang)
     */
    public function updateTransaction(int $id): void {
        $this->requireAuth(['admin']);
        $posModel = new PosTransaction();

        $tx = $posModel->find($id);
        if (!$tx) {
            $this->setFlash('error', 'Transaksi tidak ditemukan.');
            $this->redirect('admin/canteen/transactions');
            return;
        }

        $paymentMethod = in_array($_POST['payment_method'] ?? '', ['cash', 'qris'], true) ? $_POST['payment_method'] : $tx['payment_method'];
        $cashPaid = isset($_POST['cash_paid']) ? (float)$_POST['cash_paid'] : (float)$tx['cash_paid'];
        $customerName = trim($_POST['customer_name'] ?? $tx['customer_name']);
        $adjustmentNote = trim($_POST['adjustment_note'] ?? $_POST['manual_note'] ?? '');
        $userId = (int)($_SESSION['user']['id'] ?? 1);

        $items = null;
        if (isset($_POST['items'])) {
            $items = is_string($_POST['items']) ? json_decode($_POST['items'], true) : $_POST['items'];
        }

        $data = [
            'payment_method'  => $paymentMethod,
            'cash_paid'       => $cashPaid,
            'customer_name'   => $customerName,
            'adjustment_note' => $adjustmentNote,
        ];

        $res = $posModel->updateTransactionAdjustment($id, $data, $items, $userId);

        if ($res['success']) {
            $msg = "Transaksi {$tx['invoice_no']} berhasil disesuaikan!";
            if (!empty($res['stock_message'])) {
                $msg .= " " . $res['stock_message'];
            }
            $this->setFlash('success', $msg);
        } else {
            $this->setFlash('error', 'Gagal memperbarui transaksi: ' . ($res['error'] ?? 'Terjadi kesalahan sistem.'));
        }

        // Pertahankan query string jika ada
        $redirectUrl = 'admin/canteen/transactions';
        $qs = !empty($_POST['redirect_qs']) ? '?' . ltrim($_POST['redirect_qs'], '?') : '';
        $this->redirect($redirectUrl . $qs);
    }

    /**
     * Batalkan / Hapus Transaksi (Void) dengan Pengembalian Stok Otomatis
     */
    public function deleteTransaction(int $id): void {
        $this->requireAuth(['admin']);
        $posModel = new PosTransaction();

        $reason = trim($_POST['void_reason'] ?? 'Salah transaksi bayar / dibatalkan');
        $res = $posModel->voidTransaction($id, $reason);

        if ($res['success']) {
            $this->setFlash('success', "Transaksi {$res['invoice_no']} berhasil dibatalkan dan stok produk telah dikembalikan ke inventaris!");
        } else {
            $this->setFlash('error', 'Gagal membatalkan transaksi: ' . ($res['error'] ?? 'Terjadi kesalahan sistem.'));
        }

        $redirectUrl = 'admin/canteen/transactions';
        $qs = !empty($_POST['redirect_qs']) ? '?' . ltrim($_POST['redirect_qs'], '?') : '';
        $this->redirect($redirectUrl . $qs);
    }
}
