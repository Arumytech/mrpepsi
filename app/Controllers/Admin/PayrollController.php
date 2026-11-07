<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Payroll;
use App\Models\Period;
use App\Models\Tutor;
use App\Models\StaffOffice;
use App\Models\Setting;

class PayrollController extends Controller {
    private Payroll $payrollModel;
    private Period $periodModel;
    private Tutor $tutorModel;
    private StaffOffice $staffModel;
    private Setting $settingModel;

    public function __construct() {
        $this->payrollModel = new Payroll();
        $this->periodModel = new Period();
        $this->tutorModel = new Tutor();
        $this->staffModel = new StaffOffice();
        $this->settingModel = new Setting();

        try {
            $this->payrollModel->ensureSchema();
        } catch (\Throwable $e) {
            // Schema auto-creation error handling
        }
    }

    /**
     * Halaman Utama Penggajian Tutor & Karyawan (CRUD Manual)
     */
    public function index(): void {
        $this->requireAuth(['admin']);

        $periods = $this->periodModel->all('ABS(DATEDIFF(start_date, CURDATE())) ASC');
        $activePeriodDefault = $this->periodModel->getLatestActive();

        $selectedPeriodId = isset($_GET['period_id']) ? (int)$_GET['period_id'] : ($activePeriodDefault['id'] ?? ($periods[0]['id'] ?? 0));
        $payrollType = trim($_GET['payroll_type'] ?? 'all');
        $startDate = trim($_GET['start_date'] ?? '');
        $endDate = trim($_GET['end_date'] ?? '');
        $search = trim($_GET['search'] ?? '');

        // Jangan batasi rentang tanggal otomatis ke filter agar penggajian karyawan bulanan tidak tersembunyi
        $filters = [
            'period_id'    => $selectedPeriodId > 0 ? $selectedPeriodId : null,
            'payroll_type' => $payrollType,
            'start_date'   => $startDate !== '' ? $startDate : null,
            'end_date'     => $endDate !== '' ? $endDate : null,
            'search'       => $search,
        ];

        $payrolls = $this->payrollModel->getFiltered($filters);
        $summary = $this->payrollModel->getSummary($filters);

        $tutors = $this->tutorModel->getActive();
        $staff = $this->staffModel->getActive();

        $this->view('admin.payroll.index', [
            'payrolls'         => $payrolls,
            'summary'          => $summary,
            'periods'          => $periods,
            'tutors'           => $tutors,
            'staff'            => $staff,
            'selectedPeriodId' => $selectedPeriodId,
            'payrollType'      => $payrollType,
            'startDate'        => $startDate,
            'endDate'          => $endDate,
            'search'           => $search,
        ], 'admin');
    }

    /**
     * Simpan Penggajian Baru (Tutor / Karyawan)
     */
    public function store(): void {
        $this->requireAuth(['admin']);

        $type = in_array($_POST['payroll_type'] ?? '', ['tutor', 'staff'], true) ? $_POST['payroll_type'] : 'tutor';
        $tutorId = !empty($_POST['tutor_id']) ? (int)$_POST['tutor_id'] : null;
        $staffId = !empty($_POST['staff_id']) ? (int)$_POST['staff_id'] : null;
        $periodId = !empty($_POST['period_id']) ? (int)$_POST['period_id'] : null;
        $paymentDate = trim($_POST['payment_date'] ?? date('Y-m-d'));
        $recipientName = trim($_POST['recipient_name'] ?? '');
        $recipientRole = trim($_POST['recipient_role'] ?? '');

        // Jika nama kosong, ambil dari master Tutor atau Staff
        if ($recipientName === '') {
            if ($type === 'tutor' && $tutorId) {
                $tutor = $this->tutorModel->find($tutorId);
                if ($tutor) {
                    $recipientName = $tutor['name'];
                    if ($recipientRole === '') {
                        $recipientRole = $tutor['specialization'] ?: 'Coach / Tutor';
                    }
                }
            } elseif ($type === 'staff' && $staffId) {
                $st = $this->staffModel->find($staffId);
                if ($st) {
                    $recipientName = $st['name'];
                    if ($recipientRole === '') {
                        $recipientRole = $st['position'] ?: 'Staff Office';
                    }
                }
            }
        }

        if ($recipientName === '') {
            $this->setFlash('error', 'Nama penerima gaji wajib diisi atau dipilih dari data tutor/karyawan.');
            $this->redirect('admin/payroll');
            return;
        }

        $salaryMonth = trim($_POST['salary_month'] ?? '');
        if ($salaryMonth === '') {
            $salaryMonth = date('Y-m', strtotime($paymentDate));
        }

        $isKids = ($type === 'tutor' && isset($_POST['is_kids'])) ? 1 : 0;
        if ($isKids) {
            $periodId = null;
        }

        // Jika period_id tidak diisi dan BUKAN program kids, tautkan otomatis ke periode yang mencakup tanggal bayar atau periode aktif
        if ($periodId === null && !$isKids) {
            if ($paymentDate !== '') {
                $matchedPeriod = $this->periodModel->raw(
                    "SELECT id FROM periods WHERE ? BETWEEN start_date AND end_date LIMIT 1",
                    [$paymentDate]
                );
                if (!empty($matchedPeriod[0]['id'])) {
                    $periodId = (int)$matchedPeriod[0]['id'];
                }
            }
            if ($periodId === null) {
                $activePeriod = $this->periodModel->getLatestActive();
                if ($activePeriod) {
                    $periodId = (int)$activePeriod['id'];
                }
            }
        }

        $basicSalary = max(0, (float)($_POST['basic_salary'] ?? 0));
        $bonusReward = max(0, (float)($_POST['bonus_reward'] ?? 0));
        $overtime    = ($type === 'staff') ? max(0, (float)($_POST['overtime'] ?? 0)) : 0.0;
        $deduction   = max(0, (float)($_POST['deduction'] ?? 0));
        $totalAmount = max(0, ($basicSalary + $bonusReward + $overtime) - $deduction);

        $paymentMethod = in_array($_POST['payment_method'] ?? '', ['tunai', 'transfer', 'qris'], true) ? $_POST['payment_method'] : 'tunai';
        $notes = trim($_POST['notes'] ?? '');
        $syncExpense = isset($_POST['sync_expense']) ? (int)$_POST['sync_expense'] : 1;

        try {
            $insertData = [
                'payroll_type'   => $type,
                'tutor_id'       => ($type === 'tutor') ? $tutorId : null,
                'staff_id'       => ($type === 'staff') ? $staffId : null,
                'recipient_name' => $recipientName,
                'recipient_role' => $recipientRole !== '' ? $recipientRole : ($type === 'tutor' ? 'Coach / Tutor' : 'Staff Office'),
                'is_kids'        => $isKids,
                'period_id'      => $periodId,
                'salary_month'   => $salaryMonth,
                'payment_date'   => $paymentDate !== '' ? $paymentDate : date('Y-m-d'),
                'basic_salary'   => $basicSalary,
                'bonus_reward'   => $bonusReward,
                'overtime'       => $overtime,
                'deduction'      => $deduction,
                'total_amount'   => $totalAmount,
                'payment_method' => $paymentMethod,
                'notes'          => $notes !== '' ? $notes : null,
                'expense_id'     => null,
                'created_by'     => (int)($_SESSION['user']['id'] ?? 0) ?: null,
            ];

            $payrollId = $this->payrollModel->create($insertData);

            // Sinkronkan ke laporan pengeluaran (tabel expenses)
            if ($syncExpense && $payrollId > 0) {
                $expenseId = $this->payrollModel->syncExpense($payrollId, $insertData);
                if ($expenseId) {
                    $this->payrollModel->update($payrollId, ['expense_id' => $expenseId]);
                }
            }

            $this->setFlash('success', "Penggajian {$recipientName} sebesar " . formatRupiah($totalAmount) . " berhasil disimpan dan terintegrasi ke Laporan Pengeluaran!");
        } catch (\Exception $e) {
            $this->setFlash('error', 'Gagal menyimpan data penggajian: ' . $e->getMessage());
        }

        $redirectParams = [];
        if ($periodId) $redirectParams[] = 'period_id=' . $periodId;
        if ($isKids) $redirectParams[] = 'payroll_type=kids';
        elseif ($type === 'staff') $redirectParams[] = 'payroll_type=staff';
        $this->redirect('admin/payroll' . (!empty($redirectParams) ? '?' . implode('&', $redirectParams) : ''));
    }

    /**
     * Perbarui Data Penggajian
     */
    public function update(int $id): void {
        $this->requireAuth(['admin']);

        $current = $this->payrollModel->find($id);
        if (!$current) {
            $this->setFlash('error', 'Data penggajian tidak ditemukan.');
            $this->redirect('admin/payroll');
            return;
        }

        $type = in_array($_POST['payroll_type'] ?? '', ['tutor', 'staff'], true) ? $_POST['payroll_type'] : $current['payroll_type'];
        $tutorId = !empty($_POST['tutor_id']) ? (int)$_POST['tutor_id'] : null;
        $staffId = !empty($_POST['staff_id']) ? (int)$_POST['staff_id'] : null;
        $periodId = !empty($_POST['period_id']) ? (int)$_POST['period_id'] : null;
        $paymentDate = trim($_POST['payment_date'] ?? $current['payment_date']);
        $recipientName = trim($_POST['recipient_name'] ?? $current['recipient_name']);
        $recipientRole = trim($_POST['recipient_role'] ?? $current['recipient_role']);

        if ($recipientName === '') {
            $this->setFlash('error', 'Nama penerima gaji wajib diisi.');
            $this->redirect('admin/payroll');
            return;
        }

        $isKids = ($type === 'tutor' && isset($_POST['is_kids'])) ? 1 : 0;
        if ($isKids) {
            $periodId = null;
        }

        $salaryMonth = trim($_POST['salary_month'] ?? ($current['salary_month'] ?: date('Y-m', strtotime($paymentDate))));
        if ($periodId === null && !$isKids) {
            $periodId = $current['period_id'] ? (int)$current['period_id'] : null;
            if ($periodId === null && $paymentDate !== '') {
                $matchedPeriod = $this->periodModel->raw(
                    "SELECT id FROM periods WHERE ? BETWEEN start_date AND end_date LIMIT 1",
                    [$paymentDate]
                );
                if (!empty($matchedPeriod[0]['id'])) {
                    $periodId = (int)$matchedPeriod[0]['id'];
                }
            }
        }

        $basicSalary = max(0, (float)($_POST['basic_salary'] ?? 0));
        $bonusReward = max(0, (float)($_POST['bonus_reward'] ?? 0));
        $overtime    = ($type === 'staff') ? max(0, (float)($_POST['overtime'] ?? 0)) : 0.0;
        $deduction   = max(0, (float)($_POST['deduction'] ?? 0));
        $totalAmount = max(0, ($basicSalary + $bonusReward + $overtime) - $deduction);

        $paymentMethod = in_array($_POST['payment_method'] ?? '', ['tunai', 'transfer', 'qris'], true) ? $_POST['payment_method'] : 'tunai';
        $notes = trim($_POST['notes'] ?? '');

        try {
            $updateData = [
                'payroll_type'   => $type,
                'tutor_id'       => ($type === 'tutor') ? $tutorId : null,
                'staff_id'       => ($type === 'staff') ? $staffId : null,
                'recipient_name' => $recipientName,
                'recipient_role' => $recipientRole !== '' ? $recipientRole : ($type === 'tutor' ? 'Coach / Tutor' : 'Staff Office'),
                'is_kids'        => $isKids,
                'period_id'      => $periodId,
                'salary_month'   => $salaryMonth,
                'payment_date'   => $paymentDate,
                'basic_salary'   => $basicSalary,
                'bonus_reward'   => $bonusReward,
                'overtime'       => $overtime,
                'deduction'      => $deduction,
                'total_amount'   => $totalAmount,
                'payment_method' => $paymentMethod,
                'notes'          => $notes !== '' ? $notes : null,
            ];

            // Sinkronkan ke tabel expenses
            $expenseId = $this->payrollModel->syncExpense($id, $updateData, $current['expense_id'] ? (int)$current['expense_id'] : null);
            if ($expenseId) {
                $updateData['expense_id'] = $expenseId;
            }

            $this->payrollModel->update($id, $updateData);
            $this->setFlash('success', "Data penggajian {$recipientName} berhasil diperbarui!");
        } catch (\Exception $e) {
            $this->setFlash('error', 'Gagal memperbarui data penggajian: ' . $e->getMessage());
        }

        $redirectParams = [];
        if ($periodId) $redirectParams[] = 'period_id=' . $periodId;
        if ($isKids) $redirectParams[] = 'payroll_type=kids';
        elseif ($type === 'staff') $redirectParams[] = 'payroll_type=staff';
        $this->redirect('admin/payroll' . (!empty($redirectParams) ? '?' . implode('&', $redirectParams) : ''));
    }

    /**
     * Hapus Data Penggajian
     */
    public function delete(int $id): void {
        $this->requireAuth(['admin']);

        $current = $this->payrollModel->find($id);
        if (!$current) {
            $this->setFlash('error', 'Data penggajian tidak ditemukan.');
            $this->redirect('admin/payroll');
            return;
        }

        try {
            $name = $current['recipient_name'];
            $this->payrollModel->deleteWithExpense($id);
            $this->setFlash('success', "Data penggajian {$name} beserta catatan pengeluarannya berhasil dihapus.");
        } catch (\Exception $e) {
            $this->setFlash('error', 'Gagal menghapus data penggajian: ' . $e->getMessage());
        }

        $this->redirect('admin/payroll');
    }

    /**
     * Cetak Slip Gaji Perorangan (Tutor / Karyawan)
     */
    public function slip(int $id): void {
        $this->requireAuth(['admin']);

        $payroll = $this->payrollModel->findWithDetails($id);

        if (!$payroll) {
            $this->setFlash('error', 'Data penggajian tidak ditemukan.');
            $this->redirect('admin/payroll');
            return;
        }

        $settings = $this->settingModel->getAllKeyValue();

        $this->view('admin.payroll.slip', [
            'payroll'  => $payroll,
            'settings' => $settings,
        ], ''); // clean layout for print / PDF
    }

    /**
     * Cetak Laporan Rekap Tanda Terima Gaji (Tutor & Staff)
     */
    public function report(): void {
        $this->requireAuth(['admin']);

        $periods = $this->periodModel->all('ABS(DATEDIFF(start_date, CURDATE())) ASC');
        $selectedPeriodId = isset($_GET['period_id']) && $_GET['period_id'] !== '' ? (int)$_GET['period_id'] : null;
        $period = $selectedPeriodId ? $this->periodModel->find($selectedPeriodId) : null;
        $reportType = isset($_GET['type']) && in_array($_GET['type'], ['tutor', 'kids', 'staff', 'all'], true) ? $_GET['type'] : 'all';

        // Ambil data payroll yang telah diinput untuk periode ini
        $payrollList = $this->payrollModel->getFiltered([
            'period_id' => $selectedPeriodId,
        ]);

        $summary = $this->payrollModel->getSummary([
            'period_id' => $selectedPeriodId,
        ]);

        // Tutor & Staff master sebagai cadangan jika periode belum memiliki inputan
        $tutors = $this->tutorModel->getActive();
        $staff = $this->staffModel->getActive();

        $this->view('admin.payroll.report', [
            'payrollList'      => $payrollList,
            'summary'          => $summary,
            'periods'          => $periods,
            'selectedPeriodId' => $selectedPeriodId,
            'period'           => $period,
            'reportType'       => $reportType,
            'tutors'           => $tutors,
            'staff'            => $staff,
            'settings'         => $this->settingModel->getAllKeyValue(),
        ], '');
    }
}
