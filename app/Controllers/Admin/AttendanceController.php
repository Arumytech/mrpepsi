<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Attendance;
use App\Models\Period;
use App\Models\Program;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Tutor;

class AttendanceController extends Controller {
    public function index(): void {
        $this->requireAuth(['admin']);

        $attModel = new Attendance();
        $periodModel = new Period();
        $tutorModel = new Tutor();

        $periods = $periodModel->all('ABS(DATEDIFF(start_date, CURDATE())) ASC');
        $activePeriodDefault = $periodModel->getLatestActive();
        $selectedPeriodId = isset($_GET['period_id']) ? (int)$_GET['period_id'] : ($activePeriodDefault['id'] ?? ($periods[0]['id'] ?? null));
        $inputDate = trim($_GET['date'] ?? date('Y-m-d'));

        $rows = $attModel->getSessionsByTutor($selectedPeriodId);
        $tutors = $tutorModel->getActive();

        // Kelompokkan sesi berdasarkan tutor utama
        $groups = [];
        foreach ($rows as $r) {
            $tid = (int)$r['main_tutor_id'];
            if (!isset($groups[$tid])) {
                $groups[$tid] = [
                    'tutor_id' => $tid,
                    'tutor_name' => $r['main_tutor_name'],
                    'tutor_fee' => (float)$r['main_fee'],
                    'sessions' => [],
                    'total' => 0,
                    'hadir' => 0,
                    'substitusi' => 0,
                    'izin' => 0,
                    'belum' => 0,
                    'gaji' => 0.0,
                ];
            }
            $groups[$tid]['sessions'][] = $r;
            $groups[$tid]['total']++;
            $st = $r['status'] ?? '';
            if ($st === 'present') {
                $groups[$tid]['hadir']++;
            } elseif ($st === 'substituted') {
                $groups[$tid]['substitusi']++;
            } elseif ($st === 'absent') {
                $groups[$tid]['izin']++;
            } else {
                $groups[$tid]['belum']++;
            }
            $groups[$tid]['gaji'] += (float)($r['session_fee'] ?? 0);
        }

        $this->view('admin.attendance.index', [
            'groups' => array_values($groups),
            'periods' => $periods,
            'selectedPeriodId' => $selectedPeriodId,
            'inputDate' => $inputDate,
            'tutors' => $tutors
        ], 'admin');
    }

    public function saveBulk(): void {
        $this->requireAuth(['admin']);
        $attModel = new Attendance();

        $periodId = $_POST['period_id'] ?? null;
        $date = trim($_POST['date'] ?? date('Y-m-d'));
        $statuses = $_POST['status'] ?? [];
        $subs = $_POST['substituted_tutor_id'] ?? [];
        $fees = $_POST['session_fee'] ?? [];
        $notes = $_POST['notes'] ?? [];

        $saved = 0;
        $skippedSub = 0;

        if (is_array($statuses)) {
            foreach ($statuses as $scheduleId => $status) {
                $scheduleId = (int)$scheduleId;
                $status = trim((string)$status);
                if (!in_array($status, ['present', 'substituted', 'absent'], true)) {
                    continue;
                }

                $subId = null;
                if ($status === 'substituted') {
                    $subId = !empty($subs[$scheduleId]) ? (int)$subs[$scheduleId] : null;
                    if (!$subId) {
                        $skippedSub++;
                        continue;
                    }
                }

                $fee = null;
                if (isset($fees[$scheduleId]) && $fees[$scheduleId] !== '') {
                    $fee = (float)$fees[$scheduleId];
                }
                $note = trim((string)($notes[$scheduleId] ?? ''));

                if ($attModel->recordAttendance($scheduleId, $date, $status, $subId, $note, $fee, $_SESSION['user']['id'] ?? null)) {
                    $saved++;
                }
            }
        }

        if ($saved > 0) {
            $msg = $saved . ' presensi tutor berhasil disimpan.';
            if ($skippedSub > 0) {
                $msg .= ' ' . $skippedSub . ' sesi substitusi dilewati (tutor pengganti belum dipilih).';
            }
            $this->setFlash('success', $msg);
        } elseif ($skippedSub > 0) {
            $this->setFlash('error', $skippedSub . ' sesi substitusi dilewati: pilih Tutor Pengganti terlebih dahulu.');
        } else {
            $this->setFlash('error', 'Tidak ada presensi yang disimpan.');
        }

        $this->redirect('admin/attendance?period_id=' . (int)$periodId . '&date=' . $date);
    }

    public function save(): void {
        $this->requireAuth(['admin']);
        $attModel = new Attendance();

        $scheduleId = (int)($_POST['schedule_id'] ?? 0);
        $date = trim($_POST['date'] ?? date('Y-m-d'));
        $status = trim($_POST['status'] ?? 'present');
        $substituteTutorId = !empty($_POST['substituted_tutor_id']) ? (int)$_POST['substituted_tutor_id'] : null;
        $notes = trim($_POST['notes'] ?? '');
        $periodId = $_POST['period_id'] ?? null;

        if (!$scheduleId || empty($date)) {
            $this->setFlash('error', 'Parameter presensi tidak lengkap.');
            $this->redirect('admin/attendance?date=' . $date);
        }

        // If status substituted but no substitute tutor selected
        if ($status === 'substituted' && !$substituteTutorId) {
            $this->setFlash('error', 'Silakan pilih Tutor Pengganti jika status Digantikan (Substitusi).');
            $this->redirect('admin/attendance?date=' . $date . ($periodId ? '&period_id=' . $periodId : ''));
        }

        $ok = $attModel->recordAttendance($scheduleId, $date, $status, $substituteTutorId, $notes, null, $_SESSION['user']['id'] ?? null);

        if ($ok) {
            $this->setFlash('success', 'Presensi dan status gaji sesi berhasil disimpan!');
        } else {
            $this->setFlash('error', 'Gagal menyimpan presensi.');
        }

        $this->redirect('admin/attendance?date=' . $date . ($periodId ? '&period_id=' . $periodId : ''));
    }

    public function delete(): void {
        $this->requireAuth(['admin']);
        $attModel = new Attendance();

        $scheduleId = (int)($_POST['schedule_id'] ?? 0);
        $date = trim($_POST['date'] ?? date('Y-m-d'));
        $periodId = $_POST['period_id'] ?? null;

        if (!$scheduleId) {
            $this->setFlash('error', 'Parameter presensi tidak lengkap.');
            $this->redirect('admin/attendance?date=' . $date . ($periodId ? '&period_id=' . $periodId : ''));
        }

        $ok = $attModel->deleteRecord($scheduleId);

        if ($ok) {
            $this->setFlash('success', 'Presensi sesi berhasil dihapus dan gaji sesi dibatalkan.');
        } else {
            $this->setFlash('error', 'Gagal menghapus presensi.');
        }

        $this->redirect('admin/attendance?date=' . $date . ($periodId ? '&period_id=' . $periodId : ''));
    }

    // ──────────────────────────────────────────────────
    // LEMBAR PRESENSI KELAS & NILAI UJIAN (Preview & Filter)
    // ──────────────────────────────────────────────────
    public function classSheet(): void {
        $this->requireAuth(['admin']);

        $periodModel   = new Period();
        $programModel  = new Program();
        $roomModel     = new Room();
        $tutorModel    = new Tutor();
        $scheduleModel = new Schedule();
        $studentModel  = new Student();
        $settingModel  = new Setting();

        $periods   = $periodModel->all('ABS(DATEDIFF(start_date, CURDATE())) ASC');
        $programs  = $programModel->getActivePrograms();
        $rooms     = $roomModel->all('name ASC');
        $tutors    = $tutorModel->getActive();
        $settings  = $settingModel->getAllKeyValue();

        // Daftar sesi jam standar
        $standardTimeSlots = [
            '07.00-08.30',
            '08.30-10.00',
            '10.00-11.30',
            '13.00-14.30',
            '14.30-16.00',
            '16.00-17.30',
            '16.15-17.30',
            '19.00-20.00',
            '20.00-21.00'
        ];

        // Ambil unique time_slots dari schedules jika ada jadwal tambahan
        $pdo = Database::getConnection();
        $tsStmt = $pdo->query("SELECT DISTINCT time_slot FROM schedules WHERE time_slot != ''");
        $dbSlots = $tsStmt ? $tsStmt->fetchAll(\PDO::FETCH_COLUMN) : [];

        $allSlots = array_values(array_unique(array_filter(array_merge($standardTimeSlots, $dbSlots))));
        usort($allSlots, function($a, $b) {
            return strcmp($a, $b);
        });
        $timeSlots = $allSlots;

        // Default Periode
        $activePeriod = $periodModel->getLatestActive();
        $selectedPeriodId = isset($_GET['period_id']) && $_GET['period_id'] !== ''
            ? (int)$_GET['period_id']
            : ($activePeriod['id'] ?? ($periods[0]['id'] ?? 0));

        $chosenPeriod = null;
        foreach ($periods as $p) {
            if ((int)$p['id'] === $selectedPeriodId) {
                $chosenPeriod = $p;
                break;
            }
        }
        $periodName = $chosenPeriod['name'] ?? ($periods[0]['name'] ?? '10 Oktober 2026');

        // Program yang dipilih
        $selectedProgramId = isset($_GET['program_id']) && $_GET['program_id'] !== ''
            ? (int)$_GET['program_id']
            : ($programs[0]['id'] ?? 0);

        $chosenProgram = null;
        foreach ($programs as $pr) {
            if ((int)$pr['id'] === $selectedProgramId) {
                $chosenProgram = $pr;
                break;
            }
        }

        // Jam / Sesi yang dipilih
        $selectedTimeSlot = trim($_GET['time_slot'] ?? '');

        // Cari jadwal yang cocok untuk pre-populate Ruangan & Coach otomatis berdasarkan program
        $schedMatches = $scheduleModel->getFullSchedules($selectedPeriodId);
        $matchedSchedule = null;
        foreach ($schedMatches as $sc) {
            if ((int)$sc['program_id'] === $selectedProgramId) {
                if ($selectedTimeSlot === '' || $selectedTimeSlot === 'all' || $sc['time_slot'] === $selectedTimeSlot) {
                    $matchedSchedule = $sc;
                    break;
                }
            }
        }

        // Ruangan & Coach: jika ada inputan dari user di URL/form, pakai inputan itu. Jika tidak, pakai auto-fill dari jadwal
        $roomInput = isset($_GET['room_name']) ? trim($_GET['room_name']) : ($matchedSchedule['room_name'] ?? '');
        $coachInput = isset($_GET['coach_name']) ? trim($_GET['coach_name']) : ($matchedSchedule['tutor_name'] ?? '');
        if ($selectedTimeSlot === '' && $matchedSchedule) {
            $selectedTimeSlot = $matchedSchedule['time_slot'];
        }

        // Ambil daftar siswa untuk program ini
        $students = [];
        if ($chosenProgram) {
            $students = $studentModel->getStudentsForClassSheet(
                $periodName,
                (int)$chosenProgram['id'],
                (string)$chosenProgram['title'],
                (string)($chosenProgram['code'] ?? ''),
                $selectedTimeSlot
            );
        }

        // Jadwal map JSON untuk auto-fill di browser ketika program atau jam diubah
        $schedulesJson = json_encode(array_map(function($sc) {
            return [
                'period_id'   => (int)$sc['period_id'],
                'program_id'  => (int)$sc['program_id'],
                'time_slot'   => (string)$sc['time_slot'],
                'room_name'   => (string)$sc['room_name'],
                'tutor_name'  => (string)$sc['tutor_name'],
            ];
        }, $schedMatches));

        $this->view('admin.attendance.class_sheet', [
            'periods'           => $periods,
            'programs'          => $programs,
            'rooms'             => $rooms,
            'tutors'            => $tutors,
            'timeSlots'         => $timeSlots,
            'settings'          => $settings,
            'selectedPeriodId'  => $selectedPeriodId,
            'chosenPeriod'      => $chosenPeriod,
            'selectedProgramId' => $selectedProgramId,
            'chosenProgram'     => $chosenProgram,
            'selectedTimeSlot'  => $selectedTimeSlot,
            'roomInput'         => $roomInput,
            'coachInput'        => $coachInput,
            'students'          => $students,
            'schedulesJson'     => $schedulesJson,
        ], 'admin');
    }

    // ──────────────────────────────────────────────────
    // CETAK LEMBAR PRESENSI KELAS & NILAI UJIAN (Stand-alone Printable A4)
    // ──────────────────────────────────────────────────
    public function printClassSheet(): void {
        $this->requireAuth(['admin']);

        $periodModel   = new Period();
        $programModel  = new Program();
        $studentModel  = new Student();
        $settingModel  = new Setting();

        $periods   = $periodModel->all('ABS(DATEDIFF(start_date, CURDATE())) ASC');
        $programs  = $programModel->getActivePrograms();
        $settings  = $settingModel->getAllKeyValue();

        $selectedPeriodId = (int)($_GET['period_id'] ?? 0);
        $chosenPeriod = null;
        foreach ($periods as $p) {
            if ((int)$p['id'] === $selectedPeriodId) {
                $chosenPeriod = $p;
                break;
            }
        }
        $periodName = $chosenPeriod['name'] ?? trim($_GET['period_name'] ?? '10 OKTOBER 2026');

        $selectedProgramId = (int)($_GET['program_id'] ?? 0);
        $chosenProgram = null;
        foreach ($programs as $pr) {
            if ((int)$pr['id'] === $selectedProgramId) {
                $chosenProgram = $pr;
                break;
            }
        }

        $programName = $chosenProgram['title'] ?? trim($_GET['program_name'] ?? 'SPEAKING FOR IELTS (AC)');
        $selectedTimeSlot = trim($_GET['time_slot'] ?? '');
        $roomName = trim($_GET['room_name'] ?? 'CHARLIE');
        $coachName = trim($_GET['coach_name'] ?? 'MR. PEPSI');

        // Ambil siswa terdaftar
        $students = [];
        if ($chosenProgram || $selectedProgramId > 0) {
            $students = $studentModel->getStudentsForClassSheet(
                $periodName,
                (int)($chosenProgram['id'] ?? $selectedProgramId),
                (string)($chosenProgram['title'] ?? $programName),
                (string)($chosenProgram['code'] ?? ''),
                $selectedTimeSlot
            );
        }

        $this->view('admin.attendance.print_class_sheet', [
            'periodName'        => $periodName,
            'programName'       => $programName,
            'timeSlot'          => $selectedTimeSlot,
            'roomName'          => $roomName,
            'coachName'         => $coachName,
            'students'          => $students,
            'settings'          => $settings,
            'selectedPeriodId'  => $selectedPeriodId,
            'selectedProgramId' => $selectedProgramId,
        ], '');
    }
}
