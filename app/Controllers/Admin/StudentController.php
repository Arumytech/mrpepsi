<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Student;
use App\Models\StudentGraduation;
use App\Models\Period;
use App\Models\Program;
use App\Models\Setting;

class StudentController extends Controller {

    // ──────────────────────────────────────────────────
    // INDEX — Master Data Siswa Aktif
    // ──────────────────────────────────────────────────
    public function index(): void {
        $this->requireAuth(['admin']);

        $model  = new Student();
        $settingModel = new Setting();

        $status     = $_GET['status']  ?? 'all';
        $search     = trim($_GET['search']  ?? '');
        $period     = trim($_GET['period']  ?? '');
        $startDate  = trim($_GET['start_date'] ?? '');
        $endDate    = trim($_GET['end_date'] ?? '');

        // Auto-swap jika tanggal mulai lebih besar dari tanggal akhir
        if (!empty($startDate) && !empty($endDate) && $startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $students   = $model->getFiltered($status, $search, $period, $startDate, $endDate);
        $stats      = $model->getStats();
        $periods    = $model->getDistinctPeriods();
        $settings   = $settingModel->getAllKeyValue();
        $programs   = (new Program())->getActivePrograms();
        $regPeriods = (new Period())->all('ABS(DATEDIFF(start_date, CURDATE())) ASC');

        // Hubungkan setiap siswa dengan detail kelulusan program (is_issued, grade, cert_no)
        $gradModel = new StudentGraduation();
        $issuedByStudent = $gradModel->getGraduationsGroupedByStudent();
        foreach ($students as &$s) {
            $studentId = (int)$s['id'];
            $issuedRows = $issuedByStudent[$studentId] ?? [];
            $s['enriched_programs'] = $gradModel->enrichStudentPrograms($s, $issuedRows, $programs);
            $s['unissued_count'] = count(array_filter($s['enriched_programs'], fn($p) => empty($p['is_issued'])));
            $s['total_programs'] = count($s['enriched_programs']);
        }
        unset($s);

        // Ambil distinct time_slot dari schedules untuk dropdown
        $pdo = Database::getConnection();
        $tsStmt = $pdo->query(
            "SELECT DISTINCT time_slot FROM schedules WHERE time_slot != '' ORDER BY time_slot ASC"
        );
        $timeSlots = $tsStmt ? $tsStmt->fetchAll(\PDO::FETCH_COLUMN) : [];
        // Tambah juga dari data siswa yang sudah ada (jaga-jaga ada slot lama)
        $oldSlots = $pdo->query(
            "SELECT DISTINCT time_slot FROM students WHERE time_slot != '' ORDER BY time_slot ASC"
        );
        $oldSlotsArr = $oldSlots ? $oldSlots->fetchAll(\PDO::FETCH_COLUMN) : [];
        $timeSlots = array_unique(array_filter(array_merge($timeSlots, $oldSlotsArr)));
        sort($timeSlots);

        $this->view('admin.students.index', [
            'students'       => $students,
            'stats'          => $stats,
            'periods'        => $periods,
            'currentStatus'  => $status,
            'search'         => $search,
            'currentPeriod'  => $period,
            'startDate'      => $startDate,
            'endDate'        => $endDate,
            'settings'       => $settings,
            'programs'       => $programs,
            'regPeriods'     => $regPeriods,
            'timeSlots'      => $timeSlots,
        ], 'admin');
    }

    // ──────────────────────────────────────────────────
    // STORE — Add student manually by admin
    // ──────────────────────────────────────────────────
    public function store(): void {
        $this->requireAuth(['admin']);

        $model  = new Student();

        $fullName  = trim($_POST['full_name'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        $city      = trim($_POST['city'] ?? '');
        $gender    = trim($_POST['gender'] ?? '');
        $birthDate = trim($_POST['birth_date'] ?? '') ?: null;
        $periodName = trim($_POST['period_name'] ?? '');
        $timeSlot  = trim($_POST['time_slot'] ?? '');
        $totalFee  = (float)($_POST['total_fee'] ?? 0);
        $paidAmt   = (float)($_POST['paid_amount'] ?? 0);
        $notes     = trim($_POST['notes'] ?? '');
        $enrolledAt = trim($_POST['enrolled_at'] ?? date('Y-m-d'));

        // Build programs from selected program_ids
        $programIds = $_POST['program_ids'] ?? [];
        $programs = [];
        $isKids = false;
        if (!empty($programIds)) {
            $progModel = new Program();
            foreach ($programIds as $pid) {
                $p = $progModel->find((int)$pid);
                if ($p) {
                    $programs[] = ['id' => $p['id'], 'title' => $p['title'], 'code' => $p['code'], 'price' => (float)$p['price']];
                    $totalFee += (float)$p['price'];
                    if (stripos($p['title'] ?? '', 'KID') !== false || stripos($p['code'] ?? '', 'KID') !== false || (int)($p['category_id'] ?? 0) === 3) {
                        $isKids = true;
                    }
                }
            }
        }
        if ($isKids) {
            $periodName = '';
        }

        if (empty($fullName) || empty($phone)) {
            $this->setFlash('error', 'Nama lengkap dan nomor telepon wajib diisi.');
            $this->redirect('admin/students');
        }

        // Validasi: Siswa tidak diperkenankan mengambil program yang sama kembali
        $repeatCheck = $model->checkRepeatPrograms($phone, $fullName, $programIds);
        if ($repeatCheck['has_repeat'] && empty($_POST['allow_repeat_override'])) {
            $this->setFlash('error', '⚠️ PERINGATAN: ' . nl2br(sanitize($repeatCheck['message'])) . ' <br><span class="text-xs font-normal">Centang opsi "Dispensasi Khusus" jika pendaftaran ini disetujui pimpinan lembaga.</span>');
            $this->redirect('admin/students');
            return;
        }

        $paymentMethod = trim($_POST['payment_method'] ?? 'tunai');
        if (!in_array($paymentMethod, ['tunai', 'transfer'], true)) $paymentMethod = 'tunai';

        $paymentStatus = 'unpaid';
        if ($paidAmt >= $totalFee && $totalFee > 0) $paymentStatus = 'paid';
        elseif ($paidAmt > 0) $paymentStatus = 'partial';

        $newId = $model->create([
            'student_no'     => $model->generateStudentNo(),
            'registration_id'=> null,
            'full_name'      => $fullName,
            'phone'          => $phone,
            'city'           => $city,
            'gender'         => $gender,
            'birth_date'     => $birthDate,
            'period_name'    => $periodName,
            'time_slot'      => $timeSlot,
            'programs_json'  => json_encode($programs),
            'total_fee'      => $totalFee,
            'paid_amount'    => $paidAmt,
            'payment_status' => $paymentStatus,
            'payment_method' => $paymentMethod,
            'status'         => 'active',
            'notes'          => $notes,
            'enrolled_at'    => $enrolledAt,
        ]);

        $shouldPrint = !empty($_POST['print_receipt']) || (($_POST['action'] ?? '') === 'save_and_print');
        if ($shouldPrint) {
            $this->redirect('admin/students/receipt/' . $newId);
        }

        $receiptUrl = url('admin/students/receipt/' . $newId);
        $this->setFlash('success', "Siswa \"{$fullName}\" berhasil ditambahkan! <a href=\"{$receiptUrl}\" target=\"_blank\" class=\"font-black underline ml-2 inline-flex items-center gap-1 bg-white text-emerald-800 px-2.5 py-1 rounded-lg text-xs shadow-xs\">🖨️ Cetak Kwitansi</a>");
        $this->redirect('admin/students');
    }

    // ──────────────────────────────────────────────────
    // UPDATE — Edit student data
    // ──────────────────────────────────────────────────
    public function update(int $id): void {
        $this->requireAuth(['admin']);

        $model = new Student();
        $s = $model->find($id);
        if (!$s) {
            $this->setFlash('error', 'Data siswa tidak ditemukan.');
            $this->redirect('admin/students');
        }

        $paidAmt = (float)($_POST['paid_amount'] ?? $s['paid_amount']);
        $totalFee = (float)($s['total_fee']);

        $paymentStatus = 'unpaid';
        if ($paidAmt >= $totalFee && $totalFee > 0) $paymentStatus = 'paid';
        elseif ($paidAmt > 0) $paymentStatus = 'partial';

        $paymentMethod = trim($_POST['payment_method'] ?? $s['payment_method'] ?? 'tunai');
        if (!in_array($paymentMethod, ['tunai', 'transfer'], true)) $paymentMethod = 'tunai';

        $model->update($id, [
            'full_name'      => trim($_POST['full_name'] ?? $s['full_name']),
            'phone'          => trim($_POST['phone'] ?? $s['phone']),
            'city'           => trim($_POST['city'] ?? $s['city']),
            'gender'         => trim($_POST['gender'] ?? $s['gender']),
            'birth_date'     => trim($_POST['birth_date'] ?? '') ?: $s['birth_date'],
            'time_slot'      => trim($_POST['time_slot'] ?? $s['time_slot']),
            'paid_amount'    => $paidAmt,
            'payment_status' => $paymentStatus,
            'payment_method' => $paymentMethod,
            'status'         => trim($_POST['status'] ?? $s['status']),
            'notes'          => trim($_POST['notes'] ?? $s['notes']),
        ]);

        $this->setFlash('success', "Data siswa berhasil diperbarui.");
        $this->redirect('admin/students');
    }

    // ──────────────────────────────────────────────────
    // PAY — Catat pembayaran siswa (tambah ke paid_amount)
    // ──────────────────────────────────────────────────
    public function pay(int $id): void {
        $this->requireAuth(['admin']);

        $model = new Student();
        $s = $model->find($id);
        if (!$s) {
            $this->setFlash('error', 'Data siswa tidak ditemukan.');
            $this->redirect('admin/students');
        }

        $amount = (float)str_replace([',', ' '], '', trim($_POST['pay_amount'] ?? '0'));
        if ($amount <= 0) {
            $this->setFlash('error', "Nominal pembayaran harus lebih dari 0.");
            $this->redirect('admin/students');
        }

        $totalFee = (float)$s['total_fee'];
        $paidNow  = min($amount, max(0, $totalFee - (float)$s['paid_amount']));
        $newPaid  = (float)$s['paid_amount'] + $paidNow;

        $paymentStatus = 'unpaid';
        if ($newPaid >= $totalFee && $totalFee > 0) $paymentStatus = 'paid';
        elseif ($newPaid > 0) $paymentStatus = 'partial';

        $paymentMethod = trim($_POST['payment_method'] ?? 'tunai');
        if (!in_array($paymentMethod, ['tunai', 'transfer'], true)) $paymentMethod = 'tunai';

        $model->update($id, [
            'paid_amount'    => $newPaid,
            'payment_status' => $paymentStatus,
            'payment_method' => $paymentMethod,
        ]);

        $receiptUrl = url('admin/students/receipt/' . $id);
        $methodLabel = strtoupper($paymentMethod);
        $flashMsg = "Pembayaran {$s['full_name']} dicatat via {$methodLabel}: " . number_format($paidNow, 0, ',', '.') .
                    ". Status: " . strtoupper($paymentStatus) .
                    " <a href=\"{$receiptUrl}\" target=\"_blank\" class=\"font-black underline ml-2 inline-flex items-center gap-1 bg-white text-emerald-800 px-2.5 py-1 rounded-lg text-xs shadow-xs\">🖨️ Cetak Kwitansi</a>";

        if (!empty($_POST['print_receipt'])) {
            $this->redirect('admin/students/receipt/' . $id);
        }
        $this->setFlash('success', $flashMsg);
        $this->redirect('admin/students');
    }

    // ──────────────────────────────────────────────────
    // DELETE — Remove student
    // ──────────────────────────────────────────────────
    public function delete(int $id): void {
        $this->requireAuth(['admin']);
        (new Student())->delete($id);
        $this->setFlash('success', 'Data siswa berhasil dihapus.');
        $this->redirect('admin/students');
    }

    // ──────────────────────────────────────────────────
    // GRADUATES — Daftar Siswa Lulus + Sertifikat
    // ──────────────────────────────────────────────────
    public function graduates(): void {
        $this->requireAuth(['admin']);

        $gradModel   = new StudentGraduation();
        $settingModel = new Setting();
        $programModel = new Program();

        $search  = trim($_GET['search'] ?? '');
        $period  = trim($_GET['period'] ?? '');

        $graduations = $gradModel->getAllWithStudent($search, $period);
        $gradStats   = $gradModel->getStats();
        $periods     = $gradModel->getDistinctPeriods();
        $programs    = $programModel->getActivePrograms();
        $settings    = $settingModel->getAllKeyValue();
        $lastCert    = $gradModel->getLastCertInfo();
        $nextCertPreview = $gradModel->previewNextCertNo();

        // Siswa yang berhak lulus atau masih memiliki program kursus yang belum diterbitkan sertifikatnya
        $studentModel    = new Student();
        $allStudentsList = $studentModel->all('full_name ASC');
        $issuedByStudent = $gradModel->getGraduationsGroupedByStudent();

        $eligibleStudents  = [];
        $completedStudents = [];

        foreach ($allStudentsList as &$s) {
            $studentId  = (int)$s['id'];
            $issuedRows = $issuedByStudent[$studentId] ?? [];
            $enriched   = $gradModel->enrichStudentPrograms($s, $issuedRows, $programs);

            $s['enriched_programs']  = $enriched;
            $s['issued_graduations'] = $issuedRows;

            $totalProgs    = count($enriched);
            $unissuedProgs = count(array_filter($enriched, fn($p) => empty($p['is_issued'])));
            $s['total_programs'] = $totalProgs;
            $s['unissued_count'] = $unissuedProgs;

            // Perbaikan otomatis: jika siswa berstatus 'graduated' padahal masih ada program yang belum terbit sertifikatnya
            if ($s['status'] === 'graduated' && $unissuedProgs > 0) {
                $studentModel->update($studentId, ['status' => 'active']);
                $s['status'] = 'active';
            }

            // Siswa dianggap lulus penuh jika semua program di programs_json sudah diterbitkan sertifikatnya
            $isFullyGrad = ($totalProgs > 0 && $unissuedProgs === 0) || ($totalProgs === 0 && !empty($issuedRows));
            $s['is_fully_graduated'] = $isFullyGrad;

            if ($isFullyGrad) {
                $completedStudents[] = $s;
            } else {
                $eligibleStudents[] = $s;
            }
        }
        unset($s);

        // Urutkan siswa eligible: yang memiliki program belum terbit terbanyak di atas
        usort($eligibleStudents, function($a, $b) {
            if ($a['unissued_count'] !== $b['unissued_count']) {
                return $b['unissued_count'] <=> $a['unissued_count'];
            }
            return strcasecmp($a['full_name'], $b['full_name']);
        });

        $this->view('admin.students.graduates', [
            'graduations'        => $graduations,
            'gradStats'          => $gradStats,
            'periods'            => $periods,
            'programs'           => $programs,
            'activeStudents'     => $eligibleStudents, // kompatibilitas
            'eligibleStudents'   => $eligibleStudents,
            'completedStudents'  => $completedStudents,
            'allStudentsForCert' => array_merge($eligibleStudents, $completedStudents),
            'search'             => $search,
            'currentPeriod'      => $period,
            'settings'           => $settings,
            'lastCert'           => $lastCert,
            'nextCertPreview'    => $nextCertPreview,
        ], 'admin');
    }

    // ──────────────────────────────────────────────────
    // ISSUE CERTIFICATE — Add graduation record
    // ──────────────────────────────────────────────────
    public function issueCert(): void {
        $this->requireAuth(['admin']);

        $studentId          = (int)($_POST['student_id'] ?? 0);
        $programTitles      = $_POST['program_title'] ?? [];
        $programCodes       = $_POST['program_code'] ?? [];
        $programLevels      = $_POST['program_level'] ?? [];
        $programGrades      = $_POST['program_grade'] ?? [];
        $periodName         = trim($_POST['period_name'] ?? '');
        $graduatedAt        = trim($_POST['graduated_at'] ?? date('Y-m-d'));
        $globalGrade        = trim($_POST['grade'] ?? 'GOOD');
        $certMode           = trim($_POST['cert_mode'] ?? 'auto'); // 'auto' | 'manual'
        $manualCertNo       = trim($_POST['manual_cert_no'] ?? '');
        $manualCertNoItems  = $_POST['manual_cert_no_item'] ?? [];
        $redirectTo         = trim($_POST['redirect_to'] ?? 'admin/students/graduates');

        // Dukung input tunggal maupun banyak program sekaligus
        if (!is_array($programTitles))     $programTitles     = [$programTitles];
        if (!is_array($programCodes))      $programCodes      = [$programCodes];
        if (!is_array($programLevels))     $programLevels     = [$programLevels];
        if (!is_array($programGrades))     $programGrades     = [$programGrades];
        if (!is_array($manualCertNoItems)) $manualCertNoItems = [$manualCertNoItems];
        $programTitles     = array_values($programTitles);
        $programCodes      = array_values($programCodes);
        $programLevels     = array_values($programLevels);
        $programGrades     = array_values($programGrades);
        $manualCertNoItems = array_values($manualCertNoItems);

        if (!$studentId) {
            $this->setFlash('error', 'Siswa wajib dipilih.');
            $this->redirect($redirectTo);
            return;
        }

        $gradModel = new StudentGraduation();
        $issued = [];

        // Siapkan peta kode program aktif sebagai fallback jika kode kosong
        $activePrograms = (new Program())->getActivePrograms();
        $codeMap = [];
        foreach ($activePrograms as $ap) {
            if (!empty($ap['code'])) {
                $codeMap[strtolower(trim($ap['title']))] = (string)$ap['code'];
            }
        }

        foreach ($programTitles as $i => $title) {
            $title = trim((string)$title);
            if ($title === '') continue;

            $code = trim((string)($programCodes[$i] ?? ''));
            if ($code === '' && isset($codeMap[strtolower($title)])) {
                $code = $codeMap[strtolower($title)];
            }

            // Ambil predikat nilai spesifik untuk program ini, fallback ke predikat global
            $progGrade = trim((string)($programGrades[$i] ?? ''));
            if ($progGrade === '') {
                $progGrade = $globalGrade;
            }

            $certNoToUse = '';
            if ($certMode === 'manual') {
                $itemManual = trim((string)($manualCertNoItems[$i] ?? ''));
                if ($itemManual !== '') {
                    $certNoToUse = $itemManual;
                } elseif (count($programTitles) === 1 && $manualCertNo !== '') {
                    $certNoToUse = $manualCertNo;
                } else {
                    $this->setFlash('error', "Nomor sertifikat manual wajib diisi secara spesifik untuk program '{$title}'.");
                    $this->redirect($redirectTo);
                    return;
                }

                if (in_array($certNoToUse, $issued, true)) {
                    $this->setFlash('error', "Nomor sertifikat '{$certNoToUse}' duplikat di antara program yang dipilih. Setiap program harus memiliki nomor sertifikat unik.");
                    $this->redirect($redirectTo);
                    return;
                }

                if ($gradModel->isCertNoExists($certNoToUse)) {
                    $this->setFlash('error', "Nomor sertifikat '{$certNoToUse}' sudah pernah digunakan pada sertifikat lain. Silakan gunakan nomor lain.");
                    $this->redirect($redirectTo);
                    return;
                }
            }

            $progLevel = trim((string)($programLevels[$i] ?? ''));
            if ($progLevel === '') {
                $progLevel = StudentGraduation::resolveLevel($title, $code);
            }

            $gradData = [
                'program_title' => $title,
                'program_code'  => $code,
                'program_level' => $progLevel,
                'period_name'   => $periodName,
                'graduated_at'  => $graduatedAt,
                'grade'         => $progGrade,
            ];
            if ($certNoToUse !== '') {
                $gradData['certificate_no'] = $certNoToUse;
            }

            $result = $gradModel->graduate($studentId, $gradData, $_SESSION['user']['id'] ?? 0);
            $issued[] = $result['certificate_no'];
        }

        if (empty($issued)) {
            $this->setFlash('error', 'Pilih minimal satu program untuk diterbitkan sertifikatnya.');
            $this->redirect($redirectTo);
            return;
        }

        if (count($issued) === 1) {
            $this->setFlash('success', "Sertifikat kelulusan {$issued[0]} berhasil diterbitkan!");
        } else {
            $this->setFlash('success', count($issued) . ' sertifikat berhasil diterbitkan: ' . implode(', ', $issued));
        }
        $this->redirect($redirectTo);
    }

    // ──────────────────────────────────────────────────
    // CERTIFICATE PRINT — HTML print-ready view
    // ──────────────────────────────────────────────────
    public function certificate(int $id): void {
        $this->requireAuth(['admin']);

        $gradModel    = new StudentGraduation();
        $settingModel = new Setting();

        $grad = $gradModel->findForCertificate($id);
        if (!$grad) {
            $this->setFlash('error', 'Data sertifikat tidak ditemukan.');
            $this->redirect('admin/students/graduates');
        }

        $settings = $settingModel->getAllKeyValue();

        // Varian template sertifikat non-kid: 'default' (tanpa ttd) atau 'ttd'
        $variant = strtolower(trim((string)($_GET['v'] ?? $_GET['variant'] ?? 'default')));
        if (!in_array($variant, ['default', 'ttd'], true)) {
            $variant = 'default';
        }

        // Render certificate without layout wrapper (standalone print page)
        $this->view('admin.students.certificate', [
            'grad'     => $grad,
            'settings' => $settings,
            'variant'  => $variant,
        ], 'certificate');
    }

    // ──────────────────────────────────────────────────
    // DELETE GRADUATION record
    // ──────────────────────────────────────────────────
    public function deleteGrad(int $id): void {
        $this->requireAuth(['admin']);

        $gradModel = new StudentGraduation();
        $grad = $gradModel->find($id);
        if ($grad) {
            $gradModel->delete($id);
            // Perbarui status siswa secara dinamis: jika masih ada sisa sertifikat atau program belum lengkap
            $gradModel->updateStudentStatusAfterGraduation((int)$grad['student_id']);
        }

        $this->setFlash('success', 'Catatan kelulusan berhasil dihapus.');
        $this->redirect('admin/students/graduates');
    }

    // ──────────────────────────────────────────────────
    // RECEIPT — Cetak Kwitansi / e-Nota Pembayaran Siswa
    // ──────────────────────────────────────────────────
    public function receipt(int $id): void {
        $this->requireAuth(['admin']);

        $model        = new Student();
        $settingModel = new Setting();

        $student = $model->find($id);
        if (!$student) {
            $this->setFlash('error', 'Data siswa tidak ditemukan.');
            $this->redirect('admin/students');
        }

        $settings = $settingModel->getAllKeyValue();
        $student['programs'] = json_decode($student['programs_json'] ?? '[]', true) ?: [];

        // Invoice Number INV-XXXXX (e.g. INV-10000 + ID like in official e-Nota)
        $invoiceNo = 'INV-' . (10000 + (int)$student['id']);

        // Standalone receipt view
        $this->view('admin.students.receipt', [
            'student'   => $student,
            'invoiceNo' => $invoiceNo,
            'settings'  => $settings,
        ], 'receipt');
    }
}
