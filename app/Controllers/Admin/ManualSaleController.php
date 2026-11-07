<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\CanteenProduct;
use App\Models\PosTransaction;

/**
 * Input Penjualan Tambahan (khusus Admin / Owner).
 *
 * Setelah kasir menutup POS, transaksi yang masuk di hari berikutnya
 * dicatat manual di sini. Tanggal penjualan bisa diisi mundur (default kemarin)
 * sehingga ikut terhitung pada laporan harian/range tanggal terkait.
 * Penjualan manual TIDAK memotong stok.
 */
class ManualSaleController extends Controller {

    public function index(): void {
        $this->requireAuth(['admin']);

        $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate   = $_GET['end_date'] ?? date('Y-m-d');
        if (strtotime($startDate) === false) $startDate = date('Y-m-d', strtotime('-30 days'));
        if (strtotime($endDate) === false)   $endDate   = date('Y-m-d');
        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $productModel = new CanteenProduct();
        $posModel = new PosTransaction();

        $this->view('admin.manual_sales.index', [
            'products'      => $productModel->getActiveForPos(),
            'sales'         => $posModel->getManualSales($startDate, $endDate),
            'startDate'     => $startDate,
            'endDate'       => $endDate,
            'defaultDate'   => date('Y-m-d', strtotime('-1 day')),
        ], 'admin');
    }

    public function store(): void {
        $this->requireAuth(['admin']);

        $data = [
            'sale_date'      => trim((string)($_POST['sale_date'] ?? '')),
            'payment_method' => (string)($_POST['payment_method'] ?? 'cash'),
            'note'           => trim((string)($_POST['note'] ?? '')),
            'user_id'        => (int)($_SESSION['user']['id'] ?? 0),
        ];

        $rawItems = $_POST['items'] ?? [];
        $items = [];
        if (is_array($rawItems)) {
            foreach ($rawItems as $raw) {
                if (!is_array($raw)) continue;
                $items[] = [
                    'product_id' => (int)($raw['product_id'] ?? 0),
                    'name'       => (string)($raw['name'] ?? ''),
                    'quantity'   => (int)($raw['quantity'] ?? 0),
                    'price'      => (float)($raw['price'] ?? 0),
                ];
            }
        }

        if ($data['sale_date'] === '' || strtotime($data['sale_date']) === false) {
            $this->setFlash('error', 'Tanggal penjualan wajib diisi dengan format yang valid.');
            $this->redirect('admin/manual-sales');
        }

        $result = (new PosTransaction())->createManualSale($data, $items);

        if (!$result['success']) {
            $this->setFlash('error', 'Gagal menyimpan penjualan tambahan: ' . $result['error']);
            $this->redirect('admin/manual-sales');
        }

        $this->setFlash(
            'success',
            'Penjualan tambahan ' . $result['invoice_no'] . ' pada ' . tanggalIndo($result['sale_date'], 'd F Y')
            . ' berhasil disimpan (total ' . formatRupiah($result['total_amount']) . ').'
        );
        $this->redirect('admin/manual-sales');
    }

    public function delete(int $id): void {
        $this->requireAuth(['admin']);

        if (!(new PosTransaction())->deleteManualSale($id)) {
            $this->setFlash('error', 'Data tidak ditemukan atau bukan penjualan tambahan manual.');
        } else {
            $this->setFlash('success', 'Penjualan tambahan berhasil dihapus dari laporan.');
        }
        $this->redirect('admin/manual-sales');
    }
}
