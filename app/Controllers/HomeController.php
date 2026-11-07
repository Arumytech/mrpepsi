<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Program;
use App\Models\Schedule;
use App\Models\Period;
use App\Models\Tutor;
use App\Models\Room;
use App\Models\Setting;
use App\Models\Registration;

class HomeController extends Controller {
    public function index(): void {
        $programModel = new Program();
        $scheduleModel = new Schedule();
        $periodModel = new Period();
        $tutorModel = new Tutor();
        $roomModel = new Room();
        $settingModel = new Setting();

        $activePeriod = $periodModel->getLatestActive();
        $upcomingPeriods = $periodModel->getUpcoming(6);
        $nextPeriod = $upcomingPeriods[0] ?? $activePeriod;
        $nextDays = !empty($nextPeriod['start_date']) ? Period::daysUntil($nextPeriod['start_date']) : null;
        $programs = $programModel->getActivePrograms();
        $schedules = $scheduleModel->getFullSchedules($activePeriod['id'] ?? null);
        $tutors = $tutorModel->getActive();
        $rooms = $roomModel->getActive();
        $settings = $settingModel->getAllKeyValue();

        // Extract distinct times from schedules
        $timeSlots = array_values(array_unique(array_column($schedules, 'time_slot')));
        sort($timeSlots);

        $this->view('guest.index', [
            'programs' => $programs,
            'schedules' => $schedules,
            'tutors' => $tutors,
            'rooms' => $rooms,
            'timeSlots' => $timeSlots,
            'activePeriod' => $activePeriod,
            'upcomingPeriods' => $upcomingPeriods,
            'nextPeriod' => $nextPeriod,
            'nextDays' => $nextDays,
            'settings' => $settings
        ], 'guest');
    }

    public function register(): void {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            $data = $_POST;
        }

        $studentName = trim($data['student_name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $city = trim($data['city'] ?? '');
        $periodName = trim($data['period_name'] ?? 'Periode Aktif');
        $timeSlot = trim($data['time_slot'] ?? '');
        $programs = $data['programs'] ?? [];

        if (empty($studentName) || empty($phone) || empty($timeSlot) || empty($programs)) {
            $this->json([
                'success' => false,
                'error' => 'Nama lengkap, no WA, jam kelas, dan minimal 1 program wajib diisi.'
            ], 400);
        }

        $regModel = new Registration();
        $settingModel = new Setting();
        $settings = $settingModel->getAllKeyValue();

        $totalFee = 0;
        foreach ($programs as $p) {
            $totalFee += (float)($p['price'] ?? 0);
        }

        // Cek apakah siswa sudah pernah mengambil program yang sama
        $targetProgramIds = array_column($programs, 'id');
        $studentModel = new \App\Models\Student();
        $repeatCheck = $studentModel->checkRepeatPrograms($phone, $studentName, $targetProgramIds);
        if ($repeatCheck['has_repeat']) {
            $this->json([
                'success' => false,
                'error' => 'Pendaftaran tidak dapat dilanjutkan: Nomor WhatsApp atau Nama Anda tercatat sudah pernah mengikuti program yang sama (Peraturan: Siswa tidak diperkenankan mengulang program yang sama). Silakan pilih program level lanjutan atau hubungi Admin.'
            ], 422);
            return;
        }

        try {
            $saved = $regModel->register([
                'student_name' => $studentName,
                'phone' => $phone,
                'city' => $city,
                'period_name' => $periodName,
                'time_slot' => $timeSlot,
                'programs' => $programs,
                'total_fee' => $totalFee,
                'status' => 'pending',
                'registered_by' => 'guest'
            ]);

            $waAdmin = $settings['admin_wa'] ?? ADMIN_PHONE;
            $programListTxt = implode("\n", array_map(function($p) {
                return "- " . $p['title'] . " (Rp " . number_format($p['price'], 0, ',', '.') . ")";
            }, $programs));

            $waMessage = implode("\n", [
                'Halo Admin *MR.PEPSI UPGRADE*,',
                'Saya telah mengisi pendaftaran online dengan data:',
                '',
                "*Nomor Pendaftaran:* {$saved['registration_no']}",
                "*Nama Lengkap:* {$studentName}",
                "*No. WhatsApp:* {$phone}",
                "*Kota Domisili:* {$city}",
                "*Periode Belajar:* {$periodName}",
                "*Pilihan Jam Kelas:* {$timeSlot}",
                '',
                '*Program Kursus:*',
                $programListTxt,
                "*Total Biaya:* Rp " . number_format($totalFee, 0, ',', '.'),
                '',
                'Data sudah tersimpan di sistem pendaftaran online. Mohon konfirmasi persetujuan (approval) & instruksi pembayaran. Terima kasih!'
            ]);

            $waUrl = "https://wa.me/{$waAdmin}?text=" . urlencode($waMessage);

            $this->json([
                'success' => true,
                'registration_no' => $saved['registration_no'],
                'student_name' => $studentName,
                'total_fee' => $totalFee,
                'wa_url' => $waUrl
            ]);
        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Gagal menyimpan pendaftaran: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getProgramDetail(int $id): void {
        $programModel = new Program();
        $program = $programModel->find($id);
        if (!$program) {
            $this->json(['error' => 'Program not found'], 404);
        }
        $program['syllabus_list'] = json_decode($program['syllabus'], true) ?: [];
        $this->json($program);
    }
}
