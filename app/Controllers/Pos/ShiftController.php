<?php
namespace App\Controllers\Pos;

use App\Core\Controller;
use App\Models\CashierShift;
use App\Models\Setting;

class ShiftController extends Controller {
    public function index(): void {
        $this->requireAuth(['admin', 'cashier']);
        $shiftModel = new CashierShift();
        $settingModel = new Setting();

        $userId = (int)$_SESSION['user']['id'];
        $currentShift = $shiftModel->getCurrentOpenShift($userId);
        $shiftTotals = $currentShift ? $shiftModel->calculateShiftTotals($currentShift['id']) : null;

        // Shift history
        $history = $shiftModel->raw("SELECT s.*, u.name AS cashier_name 
                                    FROM cashier_shifts s 
                                    JOIN users u ON s.user_id = u.id 
                                    ORDER BY s.id DESC LIMIT 20");

        $this->view('pos.shift', [
            'currentShift' => $currentShift,
            'shiftTotals' => $shiftTotals,
            'history' => $history,
            'settings' => $settingModel->getAllKeyValue()
        ], 'pos');
    }

    public function open(): void {
        $this->requireAuth(['admin', 'cashier']);
        $shiftModel = new CashierShift();

        $userId = (int)$_SESSION['user']['id'];
        $existing = $shiftModel->getCurrentOpenShift($userId);
        if ($existing) {
            $this->setFlash('error', 'Anda sudah memiliki shift yang sedang aktif.');
            $this->redirect('pos/shift');
        }

        $startingCash = (float)($_POST['starting_cash'] ?? 0);
        $notes = trim($_POST['notes'] ?? 'Buka shift kasir');

        $shiftModel->openShift($userId, $startingCash, $notes);
        $this->setFlash('success', 'Shift kasir berhasil dibuka dengan modal awal ' . formatRupiah($startingCash) . '.');
        $this->redirect('pos');
    }

    public function close(): void {
        $this->requireAuth(['admin', 'cashier']);
        $shiftModel = new CashierShift();

        $shiftId = (int)($_POST['shift_id'] ?? 0);
        $actualCash = (float)($_POST['actual_cash'] ?? 0);
        $notes = trim($_POST['closing_notes'] ?? '');

        if (!$shiftId) {
            $this->setFlash('error', 'Data shift tidak valid.');
            $this->redirect('pos/shift');
        }

        $shiftModel->closeShift($shiftId, $actualCash, $notes);
        $this->setFlash('success', 'Shift kasir resmi ditutup dan rekap kas telah disimpan!');
        $this->redirect('pos/shift');
    }
}
