<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Setting;
use App\Libraries\PdfWriter;

class ExpenseController extends Controller {
    private Expense $expenseModel;
    private ExpenseCategory $categoryModel;

    public function __construct() {
        $this->expenseModel = new Expense();
        $this->categoryModel = new ExpenseCategory();
        try {
            $this->expenseModel->ensureSchema();
        } catch (\Throwable $e) {
            // Database belum siap — biarkan halaman memberi pesan error yang jelas
        }
    }

    // ──────────────────────────────────────────────────
    // INPUT PENGELUARAN OPERASIONAL
    // ──────────────────────────────────────────────────
    public function index(): void {
        $this->requireAuth(['admin']);

        $filters = [
            'start_date'  => trim($_GET['start_date'] ?? date('Y-m-01')),
            'end_date'    => trim($_GET['end_date'] ?? date('Y-m-d')),
            'category_id' => (int)($_GET['category_id'] ?? 0),
            'search'      => trim($_GET['search'] ?? ''),
        ];

        $expenses = $this->expenseModel->getFiltered($filters);
        $categories = $this->categoryModel->all('name ASC');
        $summary = $this->expenseModel->summary($filters['start_date'], $filters['end_date']);

        $this->view('admin.expenses.index', [
            'expenses'   => $expenses,
            'categories' => $categories,
            'summary'    => $summary,
            'filters'    => $filters,
        ], 'admin');
    }

    public function store(): void {
        $this->requireAuth(['admin']);

        $date = trim($_POST['expense_date'] ?? '');
        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $description = trim($_POST['description'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);
        $paymentMethod = $_POST['payment_method'] ?? 'tunai';
        $notes = trim($_POST['notes'] ?? '');

        if ($date === '' || $description === '' || $amount <= 0) {
            $this->setFlash('error', 'Tanggal, keterangan, dan nominal pengeluaran wajib diisi dengan benar.');
            $this->redirect('admin/expenses');
        }

        if (!in_array($paymentMethod, ['tunai', 'qris', 'transfer'], true)) {
            $paymentMethod = 'tunai';
        }

        try {
            $this->expenseModel->create([
                'expense_date'   => $date,
                'category_id'    => $categoryId,
                'description'    => $description,
                'amount'         => $amount,
                'payment_method' => $paymentMethod,
                'notes'          => $notes !== '' ? $notes : null,
                'created_by'     => (int)($_SESSION['user']['id'] ?? 0) ?: null,
            ]);
            $this->setFlash('success', 'Pengeluaran berhasil dicatat: ' . $description . ' (' . formatRupiah($amount) . ').');
        } catch (\Exception $e) {
            $this->setFlash('error', 'Gagal menyimpan pengeluaran: ' . $e->getMessage());
        }

        $this->redirect('admin/expenses');
    }

    public function update(int $id): void {
        $this->requireAuth(['admin']);

        $current = $this->expenseModel->find($id);
        if (!$current) {
            $this->setFlash('error', 'Data pengeluaran tidak ditemukan.');
            $this->redirect('admin/expenses');
        }

        $date = trim($_POST['expense_date'] ?? '');
        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $description = trim($_POST['description'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);
        $paymentMethod = $_POST['payment_method'] ?? 'tunai';
        $notes = trim($_POST['notes'] ?? '');

        if ($date === '' || $description === '' || $amount <= 0) {
            $this->setFlash('error', 'Tanggal, keterangan, dan nominal pengeluaran wajib diisi dengan benar.');
            $this->redirect('admin/expenses');
        }

        if (!in_array($paymentMethod, ['tunai', 'qris', 'transfer'], true)) {
            $paymentMethod = 'tunai';
        }

        try {
            $this->expenseModel->update($id, [
                'expense_date'   => $date,
                'category_id'    => $categoryId,
                'description'    => $description,
                'amount'         => $amount,
                'payment_method' => $paymentMethod,
                'notes'          => $notes !== '' ? $notes : null,
            ]);
            $this->setFlash('success', 'Pengeluaran berhasil diperbarui.');
        } catch (\Exception $e) {
            $this->setFlash('error', 'Gagal memperbarui pengeluaran: ' . $e->getMessage());
        }

        $this->redirect('admin/expenses');
    }

    public function delete(int $id): void {
        $this->requireAuth(['admin']);

        $current = $this->expenseModel->find($id);
        if (!$current) {
            $this->setFlash('error', 'Data pengeluaran tidak ditemukan.');
            $this->redirect('admin/expenses');
        }

        try {
            $this->expenseModel->delete($id);
            $this->setFlash('success', 'Pengeluaran berhasil dihapus.');
        } catch (\Exception $e) {
            $this->setFlash('error', 'Gagal menghapus pengeluaran: ' . $e->getMessage());
        }

        $this->redirect('admin/expenses');
    }

    // ──────────────────────────────────────────────────
    // MASTER KATEGORI PENGELUARAN
    // ──────────────────────────────────────────────────
    public function storeCategory(): void {
        $this->requireAuth(['admin']);

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($name === '') {
            $this->setFlash('error', 'Nama kategori wajib diisi.');
            $this->redirect('admin/expenses');
        }
        if (mb_strlen($name) > 80) {
            $this->setFlash('error', 'Nama kategori maksimal 80 karakter.');
            $this->redirect('admin/expenses');
        }
        if (!empty($this->categoryModel->where('name', $name, 'id ASC'))) {
            $this->setFlash('error', "Kategori '{$name}' sudah ada.");
            $this->redirect('admin/expenses');
        }

        try {
            $this->categoryModel->create([
                'name' => $name,
                'description' => $description !== '' ? $description : null,
                'is_active' => 1,
            ]);
            $this->setFlash('success', "Kategori '{$name}' berhasil ditambahkan!");
        } catch (\Exception $e) {
            $this->setFlash('error', 'Gagal menambah kategori: ' . $e->getMessage());
        }

        $this->redirect('admin/expenses');
    }

    public function updateCategory(int $id): void {
        $this->requireAuth(['admin']);

        $current = $this->categoryModel->find($id);
        if (!$current) {
            $this->setFlash('error', 'Kategori tidak ditemukan.');
            $this->redirect('admin/expenses');
        }

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($name === '') {
            $this->setFlash('error', 'Nama kategori wajib diisi.');
            $this->redirect('admin/expenses');
        }
        if (mb_strlen($name) > 80) {
            $this->setFlash('error', 'Nama kategori maksimal 80 karakter.');
            $this->redirect('admin/expenses');
        }

        foreach ($this->categoryModel->where('name', $name, 'id ASC') as $row) {
            if ((int)$row['id'] !== $id) {
                $this->setFlash('error', "Kategori '{$name}' sudah ada.");
                $this->redirect('admin/expenses');
            }
        }

        try {
            $this->categoryModel->update($id, [
                'name' => $name,
                'description' => $description !== '' ? $description : null,
                'is_active' => $isActive,
            ]);
            $this->setFlash('success', "Kategori berhasil diperbarui menjadi '{$name}'!");
        } catch (\Exception $e) {
            $this->setFlash('error', 'Gagal memperbarui kategori: ' . $e->getMessage());
        }

        $this->redirect('admin/expenses');
    }

    public function deleteCategory(int $id): void {
        $this->requireAuth(['admin']);

        $current = $this->categoryModel->find($id);
        if (!$current) {
            $this->setFlash('error', 'Kategori tidak ditemukan.');
            $this->redirect('admin/expenses');
        }

        $row = $this->expenseModel->raw("SELECT COUNT(*) AS total FROM `expenses` WHERE `category_id` = ?", [$id]);
        $used = (int)($row[0]['total'] ?? 0);

        try {
            $this->categoryModel->delete($id);
            $suffix = $used > 0 ? " {$used} pengeluaran menjadi tanpa kategori." : '';
            $this->setFlash('success', "Kategori '{$current['name']}' berhasil dihapus." . $suffix);
        } catch (\Exception $e) {
            $this->setFlash('error', 'Gagal menghapus kategori: ' . $e->getMessage());
        }

        $this->redirect('admin/expenses');
    }

    // ──────────────────────────────────────────────────
    // LAPORAN PENGELUARAN OPERASIONAL
    // ──────────────────────────────────────────────────
    public function report(): void {
        $this->requireAuth(['admin']);

        $startDate = trim($_GET['start_date'] ?? date('Y-m-01'));
        $endDate   = trim($_GET['end_date'] ?? date('Y-m-d'));
        $categoryId = (int)($_GET['category_id'] ?? 0);

        $expenses = $this->expenseModel->getFiltered([
            'start_date'  => $startDate,
            'end_date'    => $endDate,
            'category_id' => $categoryId,
        ]);
        $categoryTotals = $this->expenseModel->getCategoryTotals($startDate, $endDate);
        $summary = $this->expenseModel->summary($startDate, $endDate);
        $summary['average'] = $summary['count'] > 0 ? $summary['amount'] / $summary['count'] : 0;

        $this->view('admin.expenses.report', [
            'expenses'       => $expenses,
            'categoryTotals' => $categoryTotals,
            'summary'        => $summary,
            'categories'     => $this->categoryModel->all('name ASC'),
            'startDate'      => $startDate,
            'endDate'        => $endDate,
            'categoryId'     => $categoryId,
        ], 'admin');
    }

    public function reportPdf(): void {
        $this->requireAuth(['admin']);

        $settingModel = new Setting();

        $startDate = trim($_GET['start_date'] ?? date('Y-m-01'));
        $endDate   = trim($_GET['end_date'] ?? date('Y-m-d'));
        $categoryId = (int)($_GET['category_id'] ?? 0);

        $expenses = $this->expenseModel->getFiltered([
            'start_date'  => $startDate,
            'end_date'    => $endDate,
            'category_id' => $categoryId,
        ]);
        $categoryTotals = $this->expenseModel->getCategoryTotals($startDate, $endDate);
        $summary = $this->expenseModel->summary($startDate, $endDate);

        $settings = $settingModel->getAllKeyValue();
        $appName = (string)($settings['app_name'] ?? APP_NAME);
        $address = (string)($settings['address'] ?? '');

        $periodLabel = tanggalIndo($startDate, 'd F Y') . ' s/d ' . tanggalIndo($endDate, 'd F Y');

        $pdf = new PdfWriter('P', 'a4');
        $pdf->setTitle('LAPORAN PENGELUARAN OPERASIONAL');

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

        $title = 'LAPORAN PENGELUARAN OPERASIONAL';
        $pdf->setFont(true, 14);
        $pdf->setTextColor(0.09, 0.16, 0.31);
        $pdf->text(($pdf->pageW - $pdf->textWidth($title, true, 14)) / 2, $pdf->y, $title);

        $pdf->setY($pdf->y - 15);
        $sub = 'Periode: ' . $periodLabel;
        $pdf->setFont(false, 9);
        $pdf->setTextColor(0.45, 0.48, 0.55);
        $pdf->text(($pdf->pageW - $pdf->textWidth($sub, false, 9)) / 2, $pdf->y, $sub);

        $pdf->setY($pdf->y - 10);
        $pdf->line($pdf->lMargin, $pdf->y, $pdf->pageW - $pdf->rMargin, $pdf->y, 0.09, 0.16, 0.31, 1.1);
        $pdf->setY($pdf->y - 16);
        $pdf->setTextColor(0, 0, 0);

        // Ringkasan
        $summaryColumns = [
            ['label' => 'Ringkasan Pengeluaran', 'w' => 330],
            ['label' => 'Nilai', 'w' => 180, 'align' => 'right'],
        ];
        $summaryRows = [
            ['Total Pengeluaran Operasional', formatRupiah($summary['amount'])],
            ['Jumlah Transaksi Pengeluaran', (int)$summary['count'] . ' catatan'],
            ['Rata-rata per Pengeluaran', formatRupiah($summary['count'] > 0 ? $summary['amount'] / $summary['count'] : 0)],
        ];
        $pdf->table($summaryColumns, $summaryRows, ['fontSize' => 9.5, 'headerFontSize' => 9.5]);

        // Rekap per kategori
        $pdf->setY($pdf->y - 20);
        $pdf->setFont(true, 10);
        $pdf->setTextColor(0.09, 0.16, 0.31);
        $pdf->text($pdf->lMargin, $pdf->y, 'Rekapitulasi per Kategori');
        $pdf->setY($pdf->y - 12);
        $pdf->setTextColor(0, 0, 0);

        $catColumns = [
            ['label' => 'No', 'w' => 28, 'align' => 'center'],
            ['label' => 'Kategori', 'w' => 300],
            ['label' => 'Jumlah', 'w' => 80, 'align' => 'center'],
            ['label' => 'Total', 'w' => 102, 'align' => 'right'],
        ];
        $catRows = [];
        $no = 0;
        foreach ($categoryTotals as $ct) {
            $no++;
            $catRows[] = [
                (string)$no,
                (string)$ct['category_name'],
                (int)$ct['total_count'] . 'x',
                formatRupiah($ct['total_amount']),
            ];
        }
        if ($catRows === []) {
            $catRows[] = 'Belum ada pengeluaran pada rentang tanggal ini.';
        }
        $pdf->table($catColumns, $catRows, ['fontSize' => 9, 'headerFontSize' => 9]);

        // Rincian pengeluaran
        $pdf->setY($pdf->y - 20);
        $pdf->setFont(true, 10);
        $pdf->setTextColor(0.09, 0.16, 0.31);
        $pdf->text($pdf->lMargin, $pdf->y, 'Rincian Pengeluaran');
        $pdf->setY($pdf->y - 12);
        $pdf->setTextColor(0, 0, 0);

        $detailColumns = [
            ['label' => 'No', 'w' => 26, 'align' => 'center'],
            ['label' => 'Tanggal', 'w' => 62, 'align' => 'center'],
            ['label' => 'Kategori', 'w' => 110],
            ['label' => 'Keterangan', 'w' => 190],
            ['label' => 'Nominal', 'w' => 122, 'align' => 'right'],
        ];
        $detailRows = [];
        $no = 0;
        foreach ($expenses as $e) {
            $no++;
            $detailRows[] = [
                (string)$no,
                date('d/m/Y', strtotime((string)$e['expense_date'])),
                (string)($e['category_name'] ?? 'Tanpa Kategori'),
                (string)$e['description'],
                formatRupiah($e['amount']),
            ];
        }
        if ($detailRows === []) {
            $detailRows[] = 'Belum ada data pengeluaran pada rentang tanggal ini.';
        }
        $pdf->table($detailColumns, $detailRows, ['fontSize' => 8.5, 'headerFontSize' => 8.5]);

        $bin = $pdf->output();
        $filename = 'Laporan-Pengeluaran-Operasional-' . $startDate . '_' . $endDate . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($bin));
        header('Cache-Control: private, max-age=0, must-revalidate');
        echo $bin;
        exit;
    }
}
