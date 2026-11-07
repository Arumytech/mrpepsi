<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Schedule;
use App\Models\Period;
use App\Models\Program;
use App\Models\Tutor;
use App\Models\Room;
use App\Models\Setting;
use App\Libraries\PdfWriter;

class ScheduleController extends Controller {
    public function index(): void {
        $this->requireAuth(['admin']);

        $scheduleModel = new Schedule();
        $periodModel = new Period();
        $programModel = new Program();
        $tutorModel = new Tutor();
        $roomModel = new Room();

        $periods = $periodModel->all('ABS(DATEDIFF(start_date, CURDATE())) ASC');
        $activePeriodDefault = $periodModel->getLatestActive();
        $selectedPeriodId = isset($_GET['period_id']) ? (int)$_GET['period_id'] : ($activePeriodDefault['id'] ?? ($periods[0]['id'] ?? null));

        $schedules = $scheduleModel->getFullSchedules($selectedPeriodId);
        $programs = $programModel->getActivePrograms();
        $tutors = $tutorModel->getActive();
        $rooms = $roomModel->getActive();

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

        $this->view('admin.schedules.index', [
            'schedules' => $schedules,
            'periods' => $periods,
            'selectedPeriodId' => $selectedPeriodId,
            'programs' => $programs,
            'tutors' => $tutors,
            'rooms' => $rooms,
            'timeSlots' => $standardTimeSlots
        ], 'admin');
    }

    public function store(): void {
        $this->requireAuth(['admin']);
        $scheduleModel = new Schedule();

        $periodId = (int)($_POST['period_id'] ?? 0);
        $timeSlot = trim($_POST['time_slot'] ?? '');
        $programId = (int)($_POST['program_id'] ?? 0);
        $roomId = (int)($_POST['room_id'] ?? 0);
        $tutorId = (int)($_POST['tutor_id'] ?? 0);
        $dayName = trim($_POST['day_name'] ?? 'Senin - Jumat');

        if (!$periodId || empty($timeSlot) || !$programId || !$roomId || !$tutorId) {
            $this->setFlash('error', 'Semua bidang jadwal wajib diisi.');
            $this->redirect('admin/schedules?period_id=' . $periodId);
        }

        // Conflict Prevention Check
        $conflict = $scheduleModel->checkConflict($periodId, $timeSlot, $roomId, $tutorId);
        if ($conflict) {
            $this->setFlash('error', $conflict);
            $this->redirect('admin/schedules?period_id=' . $periodId);
        }

        try {
            $scheduleModel->create([
                'period_id' => $periodId,
                'day_name' => $dayName,
                'time_slot' => $timeSlot,
                'program_id' => $programId,
                'room_id' => $roomId,
                'tutor_id' => $tutorId
            ]);
            $this->setFlash('success', "Jadwal sesi {$timeSlot} berhasil ditambahkan!");
        } catch (\Exception $e) {
            $this->setFlash('error', "Gagal menambah jadwal: " . $e->getMessage());
        }

        $this->redirect('admin/schedules?period_id=' . $periodId);
    }

    public function update(int $id): void {
        $this->requireAuth(['admin']);
        $scheduleModel = new Schedule();

        $periodId = (int)($_POST['period_id'] ?? 0);
        $timeSlot = trim($_POST['time_slot'] ?? '');
        $programId = (int)($_POST['program_id'] ?? 0);
        $roomId = (int)($_POST['room_id'] ?? 0);
        $tutorId = (int)($_POST['tutor_id'] ?? 0);
        $dayName = trim($_POST['day_name'] ?? 'Senin - Jumat');

        // Check Conflict excluding current id
        $conflict = $scheduleModel->checkConflict($periodId, $timeSlot, $roomId, $tutorId, $id);
        if ($conflict) {
            $this->setFlash('error', $conflict);
            $this->redirect('admin/schedules?period_id=' . $periodId);
        }

        try {
            $scheduleModel->update($id, [
                'period_id' => $periodId,
                'day_name' => $dayName,
                'time_slot' => $timeSlot,
                'program_id' => $programId,
                'room_id' => $roomId,
                'tutor_id' => $tutorId
            ]);
            $this->setFlash('success', "Jadwal berhasil diperbarui!");
        } catch (\Exception $e) {
            $this->setFlash('error', "Gagal memperbarui jadwal: " . $e->getMessage());
        }

        $this->redirect('admin/schedules?period_id=' . $periodId);
    }

    public function delete(int $id): void {
        $this->requireAuth(['admin']);
        $scheduleModel = new Schedule();
        $sched = $scheduleModel->find($id);
        $periodId = $sched['period_id'] ?? null;
        
        $scheduleModel->delete($id);
        $this->setFlash('success', 'Jadwal berhasil dihapus.');
        $this->redirect('admin/schedules' . ($periodId ? '?period_id=' . $periodId : ''));
    }

    public function checkConflict(): void {
        $this->requireAuth(['admin']);
        $scheduleModel = new Schedule();

        $periodId = (int)($_GET['period_id'] ?? 0);
        $timeSlot = trim($_GET['time_slot'] ?? '');
        $roomId = (int)($_GET['room_id'] ?? 0);
        $tutorId = (int)($_GET['tutor_id'] ?? 0);
        $excludeId = isset($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : null;

        $conflict = $scheduleModel->checkConflict($periodId, $timeSlot, $roomId, $tutorId, $excludeId);
        $this->json([
            'has_conflict' => $conflict !== null,
            'message' => $conflict ?? 'Jadwal tersedia tanpa bentrok.'
        ]);
    }

    public function printDaily(): void {
        $this->requireAuth(['admin']);

        $dateStr = trim($_GET['date'] ?? '');
        if ($dateStr !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
            $this->setFlash('error', 'Tanggal tidak valid.');
            $this->redirect('admin/schedules');
        }

        $ts = $dateStr !== '' ? strtotime($dateStr) : time();
        if ($ts === false) {
            $this->setFlash('error', 'Tanggal tidak valid.');
            $this->redirect('admin/schedules');
        }

        $dayNames   = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
        $monthNames = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $weekday    = (int)date('w', $ts);

        $scheduleModel = new Schedule();
        $periodModel   = new Period();
        $settingModel  = new Setting();

        $periods       = $periodModel->all('ABS(DATEDIFF(start_date, CURDATE())) ASC');
        $activePeriod  = $periodModel->getLatestActive();
        $periodId      = isset($_GET['period_id']) ? (int)$_GET['period_id'] : ($activePeriod['id'] ?? ($periods[0]['id'] ?? null));

        $period = null;
        foreach ($periods as $p) {
            if ((int)$p['id'] === (int)$periodId) { $period = $p; break; }
        }
        if ($period === null && !empty($periods)) {
            $period   = $periods[0];
            $periodId = (int)$period['id'];
        }

        $all  = $scheduleModel->getFullSchedules($periodId);
        $rows = array_values(array_filter($all, fn($s) => self::matchesDay((string)$s['day_name'], $weekday)));
        usort($rows, fn($a, $b) => [$a['time_slot'], $a['room_name']] <=> [$b['time_slot'], $b['room_name']]);

        $settings   = $settingModel->getAllKeyValue();
        $appName    = (string)($settings['app_name']  ?? APP_NAME);
        $address    = (string)($settings['address']   ?? '');
        $skDiknas   = (string)($settings['sk_diknas'] ?? '');
        $npsn       = (string)($settings['npsn']      ?? '');
        $adminWa    = (string)($settings['admin_wa']  ?? '');
        $email      = (string)($settings['email']     ?? '');
        $instagram  = (string)($settings['instagram'] ?? '');
        $classRules = (string)($settings['class_rules'] ?? '');
        $periodName = (string)($period['name'] ?? '-');

        // Hitung jumlah pertemuan dari periode (opsional, ambil dari nama periode jika ada)
        $pertemuanInfo = '';
        if (!empty($period['start_date']) && !empty($period['end_date'])) {
            $startTs   = strtotime($period['start_date']);
            $endTs     = strtotime($period['end_date']);
            $diffDays  = max(1, (int)round(($endTs - $startTs) / 86400));
            $weekCount = (int)ceil($diffDays / 7);
            // Hitung berapa hari per minggu aktif (dari semua jadwal)
            $daySet = [];
            foreach ($all as $s) { $daySet[$s['day_name']] = true; }
            $activeWeekdays = max(1, count($daySet));
            $sessions = $weekCount * $activeWeekdays;
            // Prefer simpler: just show period date range
            $startFmt = (int)date('j', $startTs) . ' ' . $monthNames[(int)date('n', $startTs) - 1] . ' ' . date('Y', $startTs);
            $endFmt   = (int)date('j', $endTs)   . ' ' . $monthNames[(int)date('n', $endTs)   - 1] . ' ' . date('Y', $endTs);
            $pertemuanInfo = "PERIODE: {$startFmt} s/d {$endFmt}";
        } else {
            $pertemuanInfo = 'PERIODE: ' . $periodName;
        }

        // Cari logo (path absolut ke file gambar)
        $logoAbsPath = '';
        $logoPath    = trim($settings['logo_path'] ?? '');
        if ($logoPath !== '') {
            $rel = ltrim(str_replace('\\', '/', $logoPath), '/');
            $abs = rtrim(BASE_PATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
            if (is_file($abs)) {
                $logoAbsPath = $abs;
            }
        }

        // ---------------------------------------------------------------
        // Bangun tabel: kelompokkan per slot, dan pastikan minimal N baris
        // agar semua slot jam punya tinggi yang seragam
        // ---------------------------------------------------------------

        // Kumpulkan unique slots (urut)
        $slotGroups = [];
        foreach ($rows as $s) {
            $slot = (string)$s['time_slot'];
            $slotGroups[$slot][] = $s;
        }

        // Susun data tabel akhir
        // Setiap elemen: ['no'=>int, 'slot'=>str, 'is_slot_row'=>bool, 'program'=>str, 'tutor'=>str, 'room'=>str]
        $tableData = [];
        $slotNo    = 0;
        foreach ($slotGroups as $slot => $classes) {
            $slotNo++;
            $count = count($classes);  // Tidak ada baris kosong — hanya baris aktual
            $total = $count;
            for ($i = 0; $i < $total; $i++) {
                // Tutor: "COACH" + kata pertama nama saja (2 kata)
                $tutorRaw   = strtoupper(trim((string)$classes[$i]['tutor_name']));
                $tutorFirst = explode(' ', $tutorRaw)[0] ?? $tutorRaw;
                $tableData[] = [
                    'no'          => $slotNo,
                    'slot'        => $slot,
                    'is_first'    => ($i === 0),
                    'slot_span'   => $total,
                    'program'     => isset($classes[$i]) ? strtoupper((string)$classes[$i]['program_title']) : '',
                    'tutor'       => isset($classes[$i]) ? 'COACH ' . $tutorFirst : '',
                    'room'        => isset($classes[$i]) ? strtoupper((string)$classes[$i]['room_name'])  : '',
                ];
            }
        }

        if (empty($slotGroups)) {
            // tidak ada kelas hari ini
            $tableData[] = [
                'no' => 1, 'slot' => '-', 'is_first' => true, 'slot_span' => 1,
                'program' => 'Tidak ada jadwal kelas pada hari ini.', 'tutor' => '', 'room' => '',
            ];
        }

        // ---------------------------------------------------------------
        // Mulai render PDF (A4 portrait)
        // ---------------------------------------------------------------
        $pdf = new PdfWriter('P', 'a4');
        $pdf->setTitle('INFORMASI RUANG KELAS - ' . $periodName);
        $pdf->lMargin = 36.0;
        $pdf->rMargin = 36.0;
        $pdf->tMargin = 36.0;
        $pdf->bMargin = 48.0;

        // warna utama: pepsi navy #172851 ≈ 0.09,0.16,0.31
        $navy  = [0.09, 0.16, 0.31];
        $blue  = [0.08, 0.40, 0.75];
        $gray  = [0.45, 0.48, 0.55];
        $light = [0.90, 0.93, 0.97];   // header tabel
        $zebra = [0.96, 0.97, 0.99];
        $black = [0.10, 0.12, 0.16];
        $grid  = [0.65, 0.68, 0.73];

        $pageW  = $pdf->pageW;
        $lM     = $pdf->lMargin;
        $rM     = $pdf->rMargin;
        $usable = $pageW - $lM - $rM;

        // ── HEADER: logo kiri + nama + info kanan ──────────────────────
        $pdf->y = $pdf->pageH - $pdf->tMargin;
        $headerTop = $pdf->y;

        // === LOGO di pojok kiri atas ===
        $logoDisplayW = 52.0;
        $logoDisplayH = 52.0;
        $logoName = null;
        if ($logoAbsPath !== '') {
            $logoName = $pdf->addImage($logoAbsPath);
        }
        $textStartX = $lM; // X mulai teks (geser kanan jika ada logo)
        if ($logoName !== null) {
            $logoYBottom = $headerTop - $logoDisplayH;
            $pdf->drawImage($logoName, $lM, $logoYBottom, $logoDisplayW, $logoDisplayH);
            $textStartX = $lM + $logoDisplayW + 6.0;
        }

        // Nama lembaga besar di kiri (di sebelah kanan logo)
        $pdf->setFont(true, 13);
        $pdf->setTextColor($navy[0], $navy[1], $navy[2]);
        $pdf->text($textStartX, $headerTop, $appName);

        // Info sekolah di kanan (multi-baris kecil)
        $infoLines = [];
        if ($skDiknas !== '')    $infoLines[] = 'SK DIKNAS NO ' . $skDiknas . ($npsn !== '' ? '  NPSN: ' . $npsn : '');
        elseif ($npsn !== '')    $infoLines[] = 'NPSN: ' . $npsn;
        if ($address !== '')     $infoLines[] = $address;
        if ($adminWa !== '')     $infoLines[] = 'Telp.|SMS/WA. ' . $adminWa;
        if ($email !== '')       $infoLines[] = 'Email: ' . $email;

        $infoFs   = 7.5;
        $infoLead = 10.0;
        $infoRX   = $pageW - $rM;
        $iy = $headerTop;
        foreach ($infoLines as $ln) {
            $pdf->setFont(false, $infoFs);
            $pdf->setTextColor($gray[0], $gray[1], $gray[2]);
            $tw = $pdf->textWidth($ln, false, $infoFs);
            $pdf->text($infoRX - $tw, $iy, $ln);
            $iy -= $infoLead;
        }

        // Garis bawah header (turun sejauh logo atau info, mana yang lebih besar)
        $headerH = max($logoDisplayH, count($infoLines) * $infoLead + 4);
        $pdf->setY($headerTop - $headerH);
        $pdf->line($lM, $pdf->y, $pageW - $rM, $pdf->y, $navy[0], $navy[1], $navy[2], 1.2);
        $pdf->setY($pdf->y - 4);
        $pdf->line($lM, $pdf->y, $pageW - $rM, $pdf->y, $navy[0], $navy[1], $navy[2], 0.4);
        $pdf->setY($pdf->y - 14);

        // ── JUDUL ───────────────────────────────────────────────────────
        $title = 'INFORMASI RUANG KELAS';
        $pdf->setFont(true, 16);
        $pdf->setTextColor($navy[0], $navy[1], $navy[2]);
        $tw = $pdf->textWidth($title, true, 16);
        $pdf->text(($pageW - $tw) / 2, $pdf->y, $title);

        $pdf->setY($pdf->y - 16);

        // Garis bawah judul (underline effect)
        $pdf->line(($pageW - $tw) / 2, $pdf->y + 2, ($pageW + $tw) / 2, $pdf->y + 2, $navy[0], $navy[1], $navy[2], 1.0);

        $pdf->setY($pdf->y - 8);
        // Sub-judul periode
        $pdf->setFont(true, 8.5);
        $pdf->setTextColor($black[0], $black[1], $black[2]);
        $sw = $pdf->textWidth($pertemuanInfo, true, 8.5);
        $pdf->text(($pageW - $sw) / 2, $pdf->y, $pertemuanInfo);
        $pdf->setY($pdf->y - 14);

        // ── TABEL ───────────────────────────────────────────────────────
        // Kolom: NO | JAM | PROGRAM CLASS | NAMA TUTOR | ROOM
        $col = [
            'no'      => 28.0,
            'jam'     => 72.0,
            'program' => 198.0,
            'tutor'   => 130.0,
            'room'    => 0.0,   // sisa
        ];
        $col['room'] = $usable - $col['no'] - $col['jam'] - $col['program'] - $col['tutor'];

        $headers = ['NO', 'JAM', 'PROGRAM CLASS', 'NAMA TUTOR', 'ROOM'];
        $colKeys = ['no', 'jam', 'program', 'tutor', 'room'];
        $colW    = [$col['no'], $col['jam'], $col['program'], $col['tutor'], $col['room']];

        $rowH    = 16.0;   // tinggi 1 baris data
        $hdrH    = 24.0;   // tinggi header tabel
        $hdrFs   = 8.5;
        $dataFs  = 8.0;
        $pad     = 5.0;

        $x0      = $lM;
        $tableTop = $pdf->y;

        // ─ Gambar header tabel ─
        // Background header: navy
        $pdf->fillRect($x0, $tableTop - $hdrH, $usable, $hdrH, $navy[0], $navy[1], $navy[2]);
        $pdf->setFont(true, $hdrFs);
        $pdf->setTextColor(1, 1, 1);
        $cx = $x0;
        foreach ($headers as $hi => $hdr) {
            $tw  = $pdf->textWidth($hdr, true, $hdrFs);
            $tx  = $cx + ($colW[$hi] - $tw) / 2;
            $ty  = $tableTop - ($hdrH + $hdrFs * 0.85) / 2;
            $pdf->text($tx, $ty, $hdr);
            $cx += $colW[$hi];
        }
        // Grid header
        $bx = $x0;
        foreach ($colW as $w) {
            $pdf->line($bx, $tableTop, $bx, $tableTop - $hdrH, $grid[0], $grid[1], $grid[2], 0.5);
            $bx += $w;
        }
        $pdf->line($bx, $tableTop, $bx, $tableTop - $hdrH, $grid[0], $grid[1], $grid[2], 0.5);
        $pdf->line($x0, $tableTop,        $x0 + $usable, $tableTop,        $grid[0], $grid[1], $grid[2], 0.5);
        $pdf->line($x0, $tableTop - $hdrH, $x0 + $usable, $tableTop - $hdrH, $grid[0], $grid[1], $grid[2], 0.5);

        $pdf->setY($tableTop - $hdrH);

        // ─ Gambar baris data ─
        $rowIdx = 0;
        // Kelompokkan tableData per slot untuk menghitung span y
        // Kita render baris satu per satu
        // Tracking slot-span: slot pertama perlu menggambar sel NO+JAM sekaligus (merged height)
        // Namun PdfWriter tidak punya rowspan native, jadi kita gambar teks di tengah span.
        // Strategy: saat 'is_first', simpan y, gambar teks NO+JAM setelah semua baris slot selesai.
        $pendingSpan = null; // ['y_top', 'slot_span', 'no', 'slot']

        $flushSpan = function() use (&$pendingSpan, &$pdf, $x0, $colW, $rowH, $dataFs, $pad, $navy, $black) {
            if ($pendingSpan === null) return;
            ['y_top' => $yTop, 'slot_span' => $span, 'no' => $no, 'slot' => $slot] = $pendingSpan;
            $cellH = $span * $rowH;
            $midY  = $yTop - $cellH / 2;

            // NO
            $pdf->setFont(true, $dataFs);
            $pdf->setTextColor($navy[0], $navy[1], $navy[2]);
            $tw = $pdf->textWidth((string)$no, true, $dataFs);
            $pdf->text($x0 + ($colW[0] - $tw) / 2, $midY - $dataFs * 0.35, (string)$no);

            // JAM
            $pdf->setFont(true, $dataFs);
            $pdf->setTextColor(0.08, 0.40, 0.75);
            // Format: "07.00 - 08.30"
            $slotFmt = str_replace('-', ' - ', $slot);
            $tw = $pdf->textWidth($slotFmt, true, $dataFs);
            $pdf->text($x0 + $colW[0] + ($colW[1] - $tw) / 2, $midY - $dataFs * 0.35, $slotFmt);

            $pendingSpan = null;
        };

        foreach ($tableData as $rIdx => $row) {
            $top    = $pdf->y;
            $bottom = $top - $rowH;

            // Zebra
            if ($rowIdx % 2 === 1) {
                $pdf->fillRect($x0, $bottom, $usable, $rowH, $zebra[0], $zebra[1], $zebra[2]);
            }

            // Saat is_first: flush span sebelumnya, simpan span baru
            if ($row['is_first']) {
                $flushSpan();
                $pendingSpan = [
                    'y_top'     => $top,
                    'slot_span' => $row['slot_span'],
                    'no'        => $row['no'],
                    'slot'      => $row['slot'],
                ];
            }

            // Kolom PROGRAM CLASS
            $pdf->setFont(true, $dataFs);
            $pdf->setTextColor($black[0], $black[1], $black[2]);
            $progX = $x0 + $colW[0] + $colW[1] + $pad;
            $progY = $top - ($rowH + $dataFs * 0.85) / 2;
            if ($row['program'] !== '') {
                $pdf->text($progX, $progY, $row['program']);
            }

            // Kolom NAMA TUTOR
            $pdf->setFont(false, $dataFs);
            $pdf->setTextColor($black[0], $black[1], $black[2]);
            $tutorX = $x0 + $colW[0] + $colW[1] + $colW[2] + $pad;
            if ($row['tutor'] !== '') {
                $pdf->text($tutorX, $progY, $row['tutor']);
            }

            // Kolom ROOM
            $pdf->setFont(true, $dataFs);
            $pdf->setTextColor($navy[0], $navy[1], $navy[2]);
            $roomX = $x0 + $colW[0] + $colW[1] + $colW[2] + $colW[3];
            $tw    = $pdf->textWidth($row['room'], true, $dataFs);
            if ($row['room'] !== '') {
                $pdf->text($roomX + ($colW[4] - $tw) / 2, $progY, $row['room']);
            }

            // Grid: garis bawah baris
            $pdf->line($x0, $bottom, $x0 + $usable, $bottom, $grid[0], $grid[1], $grid[2], 0.4);

            // Grid: garis vertikal (hanya untuk kolom 2,3,4,5 - NO dan JAM digambar per slot)
            $bx = $x0;
            foreach ($colW as $wi => $w) {
                if ($wi === 0 && !$row['is_first']) {
                    // tidak gambar vline kolom NO di tengah slot (biar terlihat merged)
                }
                $pdf->line($bx, $top, $bx, $bottom, $grid[0], $grid[1], $grid[2], 0.4);
                $bx += $w;
            }
            $pdf->line($bx, $top, $bx, $bottom, $grid[0], $grid[1], $grid[2], 0.4);

            $pdf->setY($bottom);
            $rowIdx++;
        }

        // Flush span terakhir
        $flushSpan();

        // Garis kanan + kiri tabel keseluruhan
        $tableBottom = $pdf->y;
        $pdf->line($x0,            $tableTop, $x0,            $tableBottom, $grid[0], $grid[1], $grid[2], 0.5);
        $pdf->line($x0 + $usable,  $tableTop, $x0 + $usable,  $tableBottom, $grid[0], $grid[1], $grid[2], 0.5);
        // Top line
        $pdf->line($x0, $tableTop, $x0 + $usable, $tableTop, $grid[0], $grid[1], $grid[2], 0.5);

        // ── CATATAN ─────────────────────────────────────────────────────
        $pdf->setY($pdf->y - 10);

        if ($classRules !== '') {
            // Tulis header CATATAN
            $pdf->setFont(true, 8.0);
            $pdf->setTextColor($black[0], $black[1], $black[2]);
            $pdf->text($lM, $pdf->y, 'CATATAN :');

            // Pecah rules per baris (pisah \n)
            $ruleLines = array_filter(array_map('trim', preg_split('/[\r\n]+/', $classRules)));
            $pdf->setY($pdf->y - 11);
            $bNo = 1;
            foreach ($ruleLines as $rl) {
                $pdf->setFont(false, 7.5);
                $pdf->setTextColor($black[0], $black[1], $black[2]);
                $wrapped = $pdf->wrap($rl, $usable - 14, false, 7.5);
                foreach ($wrapped as $wi => $wl) {
                    $lineX = ($wi === 0) ? $lM + 14 : $lM + 20;
                    if ($wi === 0) {
                        $pdf->setFont(false, 7.5);
                        $pdf->text($lM, $pdf->y, $bNo . '.');
                    }
                    $pdf->text($lineX, $pdf->y, $wl);
                    $pdf->setY($pdf->y - 10);
                }
                $bNo++;
            }
        }

        // Garis penutup & ucapan terima kasih
        $pdf->setY($pdf->y - 4);
        $pdf->line($lM, $pdf->y, $pageW - $rM, $pdf->y, $navy[0], $navy[1], $navy[2], 0.6);
        $pdf->setY($pdf->y - 12);

        $thanks = 'TERIMA KASIH ATAS DOA DAN KERJASAMANYA';
        $pdf->setFont(true, 9.0);
        $pdf->setTextColor($navy[0], $navy[1], $navy[2]);
        $tw = $pdf->textWidth($thanks, true, 9.0);
        $pdf->text(($pageW - $tw) / 2, $pdf->y, $thanks);

        // Tagline & Instagram di bagian bawah
        $pdf->setY($pdf->y - 18);
        $tagline = 'You are upgraded #Feel Good';
        $pdf->setFont(false, 8.0);
        $pdf->setTextColor($gray[0], $gray[1], $gray[2]);
        $tw = $pdf->textWidth($tagline, false, 8.0);
        $pdf->text(($pageW - $tw) / 2, $pdf->y, $tagline);

        if ($instagram !== '') {
            $pdf->setY($pdf->y - 11);
            $pdf->setFont(true, 8.0);
            $pdf->setTextColor($blue[0], $blue[1], $blue[2]);
            $ig = $instagram;
            $tw = $pdf->textWidth($ig, true, 8.0);
            $pdf->text(($pageW - $tw) / 2, $pdf->y, $ig);
        }

        // ── OUTPUT ──────────────────────────────────────────────────────
        $bin      = $pdf->output();
        $filename = 'Jadwal-Ruang-Kelas-' . date('Y-m-d') . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($bin));
        header('Cache-Control: private, max-age=0, must-revalidate');
        echo $bin;
        exit;
    }


    public static function matchesDay(string $dayName, int $weekday): bool {
        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $parts = preg_split('/[,;]/u', $dayName) ?: [];
        $parsed = false;

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            if (strpos($part, '-') !== false) {
                [$a, $b] = array_map('trim', explode('-', $part, 2));
                $ai = self::dayIndex($a);
                $bi = self::dayIndex($b);
                if ($ai === null || $bi === null) {
                    continue;
                }
                $parsed = true;
                if ($ai <= $bi) {
                    if ($weekday >= $ai && $weekday <= $bi) {
                        return true;
                    }
                } elseif ($weekday >= $ai || $weekday <= $bi) {
                    return true;
                }
            } else {
                $i = self::dayIndex($part);
                if ($i === null) {
                    continue;
                }
                $parsed = true;
                if ($i === $weekday) {
                    return true;
                }
            }
        }

        if (!$parsed) {
            return stripos($dayName, $days[$weekday]) !== false;
        }
        return false;
    }

    private static function dayIndex(string $name): ?int {
        $name = trim(str_replace(["'", "\u{2019}"], '', $name));
        foreach (['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $i => $d) {
            if (strcasecmp($name, $d) === 0) {
                return $i;
            }
        }
        return null;
    }
}
