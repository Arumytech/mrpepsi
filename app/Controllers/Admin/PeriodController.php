<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Period;

class PeriodController extends Controller {
    public function index(): void {
        $this->requireAuth(['admin']);
        $periodModel = new Period();
        $periods = $periodModel->all('ABS(DATEDIFF(start_date, CURDATE())) ASC');

        $this->view('admin.periods.index', [
            'periods' => $periods
        ], 'admin');
    }

    public function store(): void {
        $this->requireAuth(['admin']);
        $periodModel = new Period();

        $name = trim($_POST['name'] ?? '');
        $periodDay = (int)($_POST['period_day'] ?? 10);
        $startDate = trim($_POST['start_date'] ?? date('Y-m-d'));
        $endDate = trim($_POST['end_date'] ?? date('Y-m-d'));

        if (empty($name)) {
            $this->setFlash('error', 'Nama periode wajib diisi.');
            $this->redirect('admin/periods');
        }

        $newId = $periodModel->create([
            'name' => $name,
            'period_day' => $periodDay,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_active' => 1
        ]);

        // Hanya boleh ada satu periode aktif
        $periodModel->raw("UPDATE periods SET is_active = 0 WHERE id <> ?", [$newId]);

        $this->setFlash('success', "Periode {$name} berhasil ditambahkan!");
        $this->redirect('admin/periods');
    }

    public function update(int $id): void {
        $this->requireAuth(['admin']);
        $periodModel = new Period();

        $current = $periodModel->find($id);
        if (!$current) {
            $this->setFlash('error', 'Periode tidak ditemukan.');
            $this->redirect('admin/periods');
        }

        $name = trim($_POST['name'] ?? '');
        $periodDay = (int)($_POST['period_day'] ?? 10);
        $startDate = trim($_POST['start_date'] ?? $current['start_date']);
        $endDate = trim($_POST['end_date'] ?? $current['end_date']);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name)) {
            $this->setFlash('error', 'Nama periode wajib diisi.');
            $this->redirect('admin/periods');
        }

        if ($startDate && $endDate && $endDate < $startDate) {
            $this->setFlash('error', 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.');
            $this->redirect('admin/periods');
        }

        try {
            $periodModel->update($id, [
                'name' => $name,
                'period_day' => $periodDay,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_active' => $isActive
            ]);

            if ($isActive) {
                $periodModel->raw("UPDATE periods SET is_active = 0 WHERE id <> ?", [$id]);
            }

            $this->setFlash('success', "Periode '{$name}' berhasil diperbarui!");
        } catch (\Exception $e) {
            $this->setFlash('error', "Gagal memperbarui periode: " . $e->getMessage());
        }

        $this->redirect('admin/periods');
    }

    public function activate(int $id): void {
        $this->requireAuth(['admin']);
        $periodModel = new Period();
        
        // Deactivate all first
        $periodModel->raw("UPDATE periods SET is_active = 0");
        $periodModel->update($id, ['is_active' => 1]);

        $this->setFlash('success', 'Periode aktif berhasil diubah.');
        $this->redirect('admin/periods');
    }
}
