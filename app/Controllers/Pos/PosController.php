<?php
namespace App\Controllers\Pos;

use App\Core\Controller;
use App\Models\CanteenProduct;
use App\Models\CanteenCategory;
use App\Models\CashierShift;
use App\Models\PosTransaction;
use App\Models\Setting;

class PosController extends Controller {
    public function index(): void {
        $this->requireAuth(['admin', 'cashier']);

        $productModel = new CanteenProduct();
        $categoryModel = new CanteenCategory();
        $shiftModel = new CashierShift();
        $settingModel = new Setting();

        $userId = (int)$_SESSION['user']['id'];
        $currentShift = $shiftModel->getCurrentOpenShift($userId);

        $products = $productModel->getActiveForPos();
        $categories = $categoryModel->all('name ASC');
        $settings = $settingModel->getAllKeyValue();

        // POS ditutup untuk hari ini: tampilkan layar terkunci,
        // otomatis terbuka kembali esok hari (tanggal berbeda).
        if ($settingModel->isPosClosedToday()) {
            $this->view('pos.closed', [
                'settings' => $settings
            ], 'pos');
            return;
        }

        $this->view('pos.index', [
            'products' => $products,
            'categories' => $categories,
            'currentShift' => $currentShift,
            'settings' => $settings
        ], 'pos');
    }

    /** Tombol kasir: tutup transaksi POS untuk hari ini. */
    public function closeDay(): void {
        $this->requireAuth(['admin', 'cashier']);
        $settingModel = new Setting();
        $settingModel->set('pos_closed_date', date('Y-m-d'));

        $this->setFlash('success', 'Transaksi POS ditutup untuk hari ini. Mesin kasir akan terbuka otomatis besok.');
        $this->redirect('pos');
    }

    public function processCheckout(): void {
        $this->requireAuth(['admin', 'cashier']);

        $settingModel = new Setting();
        if ($settingModel->isPosClosedToday()) {
            $this->json(['success' => false, 'error' => 'POS telah ditutup untuk hari ini.'], 403);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || empty($data['items'])) {
            $this->json(['success' => false, 'error' => 'Keranjang belanja kosong.'], 400);
        }

        $userId = (int)$_SESSION['user']['id'];
        $shiftModel = new CashierShift();
        $currentShift = $shiftModel->getCurrentOpenShift($userId);

        $posModel = new PosTransaction();
        $result = $posModel->createSale([
            'shift_id' => $currentShift['id'] ?? null,
            'user_id' => $userId,
            'payment_method' => $data['payment_method'] ?? 'cash',
            'cash_paid' => (float)($data['cash_paid'] ?? 0),
            'customer_name' => trim($data['customer_name'] ?? 'Umum / Siswa')
        ], $data['items']);

        if ($result['success']) {
            $this->json($result);
        } else {
            $this->json($result, 500);
        }
    }

    public function receipt(int $id): void {
        $this->requireAuth(['admin', 'cashier']);
        $posModel = new PosTransaction();
        $settingModel = new Setting();

        $receipt = $posModel->getReceipt($id);
        $settings = $settingModel->getAllKeyValue();

        if (!$receipt) {
            die("Struk transaksi #{$id} tidak ditemukan.");
        }

        $this->view('pos.receipt', [
            'receipt' => $receipt,
            'settings' => $settings
        ], ''); // clean layout for thermal receipt
    }
}
