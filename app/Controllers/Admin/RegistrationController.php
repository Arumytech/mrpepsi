<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Registration;
use App\Models\Program;
use App\Models\Period;
use App\Models\Setting;
use App\Models\Student;

class RegistrationController extends Controller {
    public function index(): void {
        $this->requireAuth(['admin']);

        $regModel = new Registration();
        $programModel = new Program();
        $periodModel = new Period();
        $settingModel = new Setting();

        $status = $_GET['status'] ?? 'all';
        $search = trim($_GET['search'] ?? '');

        $registrations = $regModel->getFiltered($status, $search);
        $stats = $regModel->getStats();
        $programs = $programModel->getActivePrograms();
        $periods = $periodModel->all('ABS(DATEDIFF(start_date, CURDATE())) ASC');
        $settings = $settingModel->getAllKeyValue();

        $this->view('admin.registrations.index', [
            'registrations' => $registrations,
            'stats' => $stats,
            'currentStatus' => $status,
            'search' => $search,
            'programs' => $programs,
            'periods' => $periods,
            'settings' => $settings
        ], 'admin');
    }

    public function store(): void {
        $this->requireAuth(['admin']);

        $regModel = new Registration();
        $programModel = new Program();

        $studentName = trim($_POST['student_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $periodName = trim($_POST['period_name'] ?? 'Periode Aktif');
        $adminNotes = trim($_POST['admin_notes'] ?? 'Didaftarkan langsung oleh Admin');

        // Jam sesi per program: time_slots[program_id] => slot
        $rawTimeSlots = $_POST['time_slots'] ?? [];
        if (!is_array($rawTimeSlots)) {
            $rawTimeSlots = [];
        }
        $timeSlots = [];
        foreach ($rawTimeSlots as $pid => $slot) {
            $slot = trim((string)$slot);
            if ($slot !== '') $timeSlots[(int)$pid] = $slot;
        }
        $fallbackTimeSlot = trim($_POST['time_slot'] ?? '');

        $rawProgramIds = $_POST['program_ids'] ?? [];
        if (!is_array($rawProgramIds)) {
            $rawProgramIds = [$rawProgramIds];
        }
        $selectedProgramIds = array_filter(array_map('intval', $rawProgramIds));

        // Wajib: setiap program yang dipilih harus punya jam sesi sendiri
        $missingSlot = false;
        foreach ($selectedProgramIds as $pid) {
            if (empty($timeSlots[$pid])) $missingSlot = true;
        }

        if (empty($studentName) || empty($phone) || empty($selectedProgramIds) || ($missingSlot && empty($fallbackTimeSlot))) {
            $this->setFlash('error', 'Nama siswa, nomor telepon, minimal 1 program kursus, dan jam sesi untuk tiap program wajib diisi.');
            $this->redirect('admin/students');
        }

        // Fetch program details and calculate total fee (each program carries its own time slot)
        $programsData = [];
        $totalFee = 0;
        $isKidsProgram = false;
        foreach ($selectedProgramIds as $pid) {
            $p = $programModel->find((int)$pid);
            if ($p) {
                $programsData[] = [
                    'id' => $p['id'],
                    'title' => $p['title'],
                    'code' => $p['code'],
                    'price' => (float)$p['price'],
                    'time_slot' => $timeSlots[$pid] ?? $fallbackTimeSlot
                ];
                $totalFee += (float)$p['price'];
                if (stripos($p['title'] ?? '', 'KID') !== false || stripos($p['code'] ?? '', 'KID') !== false || (int)($p['category_id'] ?? 0) === 3) {
                    $isKidsProgram = true;
                }
            }
        }

        if ($isKidsProgram) {
            $periodName = '';
        }

        // Slot utama (untuk master data) = sesi program pertama
        $timeSlot = '';
        foreach ($programsData as $pd) {
            if (!empty($pd['time_slot'])) { $timeSlot = $pd['time_slot']; break; }
        }
        if ($timeSlot === '') $timeSlot = $fallbackTimeSlot;

        if (isset($_POST['total_fee']) && is_numeric($_POST['total_fee']) && (float)$_POST['total_fee'] > 0) {
            $totalFee = (float)$_POST['total_fee'];
        }

        $paidAmt = (float)($_POST['paid_amount'] ?? 0);
        $paymentMethod = trim($_POST['payment_method'] ?? 'tunai');
        if (!in_array($paymentMethod, ['tunai', 'transfer'], true)) $paymentMethod = 'tunai';

        $gender = trim($_POST['gender'] ?? '');
        $birthDate = trim($_POST['birth_date'] ?? '') ?: null;

        // Validasi aturan: Siswa tidak diperkenankan mengambil program yang sama kembali
        $studentModel = new Student();
        $repeatCheck = $studentModel->checkRepeatPrograms($phone, $studentName, $selectedProgramIds);
        if ($repeatCheck['has_repeat'] && empty($_POST['allow_repeat_override'])) {
            $this->setFlash('error', '⚠️ PERINGATAN: ' . nl2br(sanitize($repeatCheck['message'])) . ' <br><span class="text-xs font-normal">Centang opsi "Dispensasi Khusus" jika pendaftaran ini disetujui pimpinan lembaga.</span>');
            $this->redirect('admin/registrations');
            return;
        }

        try {
            $result = $regModel->register([
                'student_name' => $studentName,
                'phone' => $phone,
                'city' => $city,
                'time_slot' => $timeSlot,
                'period_name' => $periodName,
                'programs' => $programsData,
                'total_fee' => $totalFee,
                'status' => 'approved',
                'registered_by' => 'admin',
                'admin_notes' => $adminNotes
            ]);

            // Pendaftaran direct admin LANGSUNG DISETUJUI → otomatis masuk Master Data Siswa
            $reg = $regModel->find((int)$result['id']);
            $studentModel = new Student();
            $studentId = $studentModel->createFromRegistration($reg, [
                'total_fee' => $totalFee,
                'paid_amount' => $paidAmt,
                'payment_method' => $paymentMethod,
                'gender' => $gender,
                'birth_date' => $birthDate,
                'notes' => $adminNotes
            ]);

            $receiptUrl = url('admin/students/receipt/' . $studentId);

            $shouldPrint = !empty($_POST['print_receipt']) || (($_POST['action'] ?? '') === 'save_and_print');
            if ($shouldPrint) {
                $this->redirect('admin/students/receipt/' . $studentId);
            }

            $this->setFlash('success', "Pendaftaran siswa '{$studentName}' berhasil dibuat & LANGSUNG MASUK ke Master Siswa! <a href=\"{$receiptUrl}\" target=\"_blank\" class=\"font-black underline ml-2 inline-flex items-center gap-1 bg-white text-emerald-800 px-2.5 py-1 rounded-lg text-xs shadow-xs\">🖨️ Cetak Kwitansi</a>");
        } catch (\Exception $e) {
            $this->setFlash('error', "Gagal membuat pendaftaran: " . $e->getMessage());
        }

        $this->redirect('admin/students');
    }

    public function update(int $id): void {
        $this->requireAuth(['admin']);

        $regModel = new Registration();
        $programModel = new Program();

        $reg = $regModel->find($id);
        if (!$reg) {
            $this->setFlash('error', 'Data pendaftaran tidak ditemukan.');
            $this->redirect('admin/registrations');
        }

        $studentName = trim($_POST['student_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $periodName = trim($_POST['period_name'] ?? $reg['period_name']);
        $adminNotes = trim($_POST['admin_notes'] ?? '');

        $rawTimeSlots = $_POST['time_slots'] ?? [];
        if (!is_array($rawTimeSlots)) {
            $rawTimeSlots = [];
        }
        $timeSlots = [];
        foreach ($rawTimeSlots as $pid => $slot) {
            $slot = trim((string)$slot);
            if ($slot !== '') $timeSlots[(int)$pid] = $slot;
        }
        $fallbackTimeSlot = trim($_POST['time_slot'] ?? '');

        $rawProgramIds = $_POST['program_ids'] ?? [];
        if (!is_array($rawProgramIds)) {
            $rawProgramIds = [$rawProgramIds];
        }
        $selectedProgramIds = array_filter(array_map('intval', $rawProgramIds));

        $missingSlot = false;
        foreach ($selectedProgramIds as $pid) {
            if (empty($timeSlots[$pid])) $missingSlot = true;
        }

        if (empty($studentName) || empty($phone) || empty($selectedProgramIds) || ($missingSlot && empty($fallbackTimeSlot))) {
            $this->setFlash('error', 'Nama siswa, nomor telepon, minimal 1 program kursus, dan jam sesi untuk tiap program wajib diisi.');
            $this->redirect('admin/registrations');
        }

        $programsData = [];
        $totalFee = 0;
        $isKidsProgram = false;
        foreach ($selectedProgramIds as $pid) {
            $p = $programModel->find((int)$pid);
            if ($p) {
                $programsData[] = [
                    'id' => $p['id'],
                    'title' => $p['title'],
                    'code' => $p['code'],
                    'price' => (float)$p['price'],
                    'time_slot' => $timeSlots[$pid] ?? $fallbackTimeSlot
                ];
                $totalFee += (float)$p['price'];
                if (stripos($p['title'] ?? '', 'KID') !== false || stripos($p['code'] ?? '', 'KID') !== false || (int)($p['category_id'] ?? 0) === 3) {
                    $isKidsProgram = true;
                }
            }
        }

        if ($isKidsProgram) {
            $periodName = '';
        }

        // Slot utama = sesi program pertama
        $timeSlot = '';
        foreach ($programsData as $pd) {
            if (!empty($pd['time_slot'])) { $timeSlot = $pd['time_slot']; break; }
        }
        if ($timeSlot === '') $timeSlot = $fallbackTimeSlot ?: $reg['time_slot'];

        $periodId = null;
        if (!empty($periodName)) {
            $find = $regModel->raw("SELECT id FROM periods WHERE name = ? LIMIT 1", [$periodName]);
            $periodId = !empty($find[0]['id']) ? (int)$find[0]['id'] : null;
        }

        try {
            $regModel->update($id, [
                'period_id' => $periodId,
                'period_name' => $periodName,
                'student_name' => $studentName,
                'phone' => $phone,
                'city' => $city,
                'time_slot' => $timeSlot,
                'programs_json' => json_encode($programsData),
                'total_fee' => $totalFee,
                'admin_notes' => $adminNotes !== '' ? $adminNotes : $reg['admin_notes']
            ]);

            $this->setFlash('success', "Pendaftaran {$reg['registration_no']} berhasil diperbarui!");
        } catch (\Exception $e) {
            $this->setFlash('error', "Gagal memperbarui pendaftaran: " . $e->getMessage());
        }

        $this->redirect('admin/registrations');
    }

    public function approve(int $id): void {
        $this->requireAuth(['admin']);

        $regModel = new Registration();
        $notes = trim($_POST['notes'] ?? 'Disetujui oleh Administrator');

        $reg = $regModel->find($id);
        if (!$reg) {
            $this->setFlash('error', 'Data pendaftaran tidak ditemukan.');
            $this->redirect('admin/registrations');
        }

        $regModel->updateStatus($id, 'approved', $notes);

        // ── Auto-create student in master data when approved
        $reg = $regModel->find($id); // re-fetch updated record
        $studentModel = new Student();
        $studentModel->createFromRegistration($reg);

        $this->setFlash('success', "Pendaftaran {$reg['student_name']} ({$reg['registration_no']}) BERHASIL DISETUJUI! Siswa ditambahkan ke Master Data.");
        $this->redirect('admin/registrations');
    }

    public function reject(int $id): void {
        $this->requireAuth(['admin']);

        $regModel = new Registration();
        $notes = trim($_POST['notes'] ?? 'Pendaftaran ditolak');

        $reg = $regModel->find($id);
        if (!$reg) {
            $this->setFlash('error', 'Data pendaftaran tidak ditemukan.');
            $this->redirect('admin/registrations');
        }

        $regModel->updateStatus($id, 'rejected', $notes);
        $this->setFlash('success', "Pendaftaran {$reg['student_name']} ({$reg['registration_no']}) telah ditolak/dibatalkan.");
        $this->redirect('admin/registrations');
    }

    public function delete(int $id): void {
        $this->requireAuth(['admin']);

        $regModel = new Registration();
        $regModel->delete($id);

        $this->setFlash('success', 'Data pendaftaran berhasil dihapus.');
        $this->redirect('admin/registrations');
    }

    // ──────────────────────────────────────────────────
    // PROOF — Cetak Bukti Pendaftaran Siswa
    // ──────────────────────────────────────────────────
    public function proof(int $id): void {
        $this->requireAuth(['admin']);

        $regModel    = new Registration();
        $settingModel = new Setting();

        $reg = $regModel->find($id);
        if (!$reg) {
            $this->setFlash('error', 'Data pendaftaran tidak ditemukan.');
            $this->redirect('admin/registrations');
        }

        $settings = $settingModel->getAllKeyValue();

        // Standalone print page (layout 'receipt' = no wrapper)
        $this->view('admin.registrations.proof', [
            'reg'      => $reg,
            'settings' => $settings,
        ], 'receipt');
    }

    /**
     * API JSON Live Check: Cek apakah siswa pernah mengambil program tertentu
     */
    public function checkRepeatProgram(): void {
        header('Content-Type: application/json; charset=utf-8');
        $phone = trim($_GET['phone'] ?? $_POST['phone'] ?? '');
        $name = trim($_GET['name'] ?? $_POST['name'] ?? '');
        $programsRaw = $_GET['programs'] ?? $_POST['programs'] ?? [];
        if (is_string($programsRaw)) {
            $programIds = array_filter(array_map('intval', explode(',', $programsRaw)));
        } else {
            $programIds = array_filter(array_map('intval', (array)$programsRaw));
        }
        $excludeId = isset($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : null;

        $studentModel = new Student();
        $result = $studentModel->checkRepeatPrograms($phone, $name, $programIds, $excludeId);
        echo json_encode($result);
        exit;
    }
}
