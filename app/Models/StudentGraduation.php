<?php
namespace App\Models;

use App\Core\Model;

class StudentGraduation extends Model {
    protected string $table = 'student_graduations';

    public function __construct() {
        parent::__construct();
        // Auto-migrate: tambahkan kolom is_kid jika belum ada
        try {
            $cols = $this->db->query("SHOW COLUMNS FROM `{$this->table}` LIKE 'is_kid'")->fetchAll();
            if (empty($cols)) {
                $this->db->exec("ALTER TABLE `{$this->table}` ADD COLUMN `is_kid` TINYINT(1) NOT NULL DEFAULT 0 AFTER `grade`");
            }
        } catch (\Exception $e) {
            // Tabel belum ada, biarkan — akan dibuat saat migrasi penuh
        }

        // Auto-migrate: tambahkan kolom program_level jika belum ada
        try {
            $cols = $this->db->query("SHOW COLUMNS FROM `{$this->table}` LIKE 'program_level'")->fetchAll();
            if (empty($cols)) {
                $this->db->exec("ALTER TABLE `{$this->table}` ADD COLUMN `program_level` VARCHAR(100) NULL AFTER `program_code`");
            }
        } catch (\Exception $e) {
            // Tabel belum ada, biarkan — akan dibuat saat migrasi penuh
        }
    }

    // ── Kategori predikat nilai ujian akhir (bukan angka)
    public const GRADE_CATEGORIES = ['EXCELLENT', 'VERY GOOD', 'GOOD', 'AVERAGE', 'POOR'];

    // ── Map nilai/gradasi lama (A-E) ke kategori predikat
    public const LEGACY_GRADE_MAP = [
        'A' => 'EXCELLENT',
        'B' => 'VERY GOOD',
        'C' => 'GOOD',
        'D' => 'AVERAGE',
        'E' => 'POOR',
    ];

    // ── Generate certificate number from configurable format
    //    Token yang didukung:
    //      {Y}      → tahun 4 digit (2026)
    //      {y}      → tahun 2 digit (26)
    //      {m}      → bulan 2 digit (01-12)
    //      {d}      → hari 2 digit (01-31)
    //      {rm}     → bulan Romawi (I - XII), contoh Oktober → X
    //      {code}   → kode program sertifikat (contoh: GFS)
    //      {seq}    → nomor urut otomatis (digit dari pengaturan certificate_seq_digits)
    //      {seq:N}  → nomor urut dengan N digit (contoh: {seq:5})
    public function generateCertNo(string $programCode = ''): string {
        $setting       = new Setting();
        $format        = trim($setting->get('certificate_format', '{seq}/MPU/C.{code}/{rm}/{Y}'));
        $defaultDigits = (int)$setting->get('certificate_seq_digits', '5');
        if ($format === '') $format = '{seq}/MPU/C.{code}/{rm}/{Y}';
        if ($defaultDigits < 1) $defaultDigits = 5;

        // Tentukan token nomor urut & jumlah digit
        $seqToken = '{seq}';
        $digits   = $defaultDigits;
        if (preg_match('/\{seq:(\d+)\}/i', $format, $m)) {
            $seqToken = $m[0];
            $digits   = max(1, (int)$m[1]);
        }
        if (strpos($format, $seqToken) === false) {
            $seqToken = '{seq}';
            $digits   = $defaultDigits;
            $format  .= $seqToken; // fallback: sisipkan di akhir bila admin lupa
        }

        $pos   = strpos($format, $seqToken);
        $before = $this->resolveCertTokens(substr($format, 0, $pos), $programCode);
        $after  = $this->resolveCertTokens(substr($format, $pos + strlen($seqToken)), $programCode);

        // Nomor urut keluar sertifikat disimpan sebagai counter global agar tetap
        // berurutan walau kode program / bulan / tahun berganti (1 sertifikat = 1 nomor).
        $counter = trim($setting->get('certificate_seq_counter', ''));
        if ($counter === '') {
            $seq = (int)$setting->get('certificate_seq_start', '1');
        } else {
            $seq = (int)$counter + 1;
        }
        if ($seq < 1) $seq = 1;
        $setting->set('certificate_seq_counter', (string)$seq);

        return $before . str_pad((string)$seq, $digits, '0', STR_PAD_LEFT) . $after;
    }

    // ── Preview nomor sertifikat berikutnya yang akan di-generate tanpa menaikkan counter
    public function previewNextCertNo(string $programCode = ''): string {
        $setting       = new Setting();
        $format        = trim($setting->get('certificate_format', '{seq}/MPU/C.{code}/{rm}/{Y}'));
        $defaultDigits = (int)$setting->get('certificate_seq_digits', '5');
        if ($format === '') $format = '{seq}/MPU/C.{code}/{rm}/{Y}';
        if ($defaultDigits < 1) $defaultDigits = 5;

        $seqToken = '{seq}';
        $digits   = $defaultDigits;
        if (preg_match('/\{seq:(\d+)\}/i', $format, $m)) {
            $seqToken = $m[0];
            $digits   = max(1, (int)$m[1]);
        }
        if (strpos($format, $seqToken) === false) {
            $seqToken = '{seq}';
            $digits   = $defaultDigits;
            $format  .= $seqToken;
        }

        $pos   = strpos($format, $seqToken);
        $before = $this->resolveCertTokens(substr($format, 0, $pos), $programCode);
        $after  = $this->resolveCertTokens(substr($format, $pos + strlen($seqToken)), $programCode);

        $counter = trim($setting->get('certificate_seq_counter', ''));
        if ($counter === '') {
            $seq = (int)$setting->get('certificate_seq_start', '1');
        } else {
            $seq = (int)$counter + 1;
        }
        if ($seq < 1) $seq = 1;

        return $before . str_pad((string)$seq, $digits, '0', STR_PAD_LEFT) . $after;
    }

    // ── Ambil info sertifikat terakhir yang telah diterbitkan
    public function getLastCertInfo(): ?array {
        $sql = "SELECT sg.id, sg.certificate_no, sg.program_title, sg.graduated_at, sg.created_at, s.full_name, s.student_no
                FROM `{$this->table}` sg
                LEFT JOIN students s ON sg.student_id = s.id
                ORDER BY sg.id DESC LIMIT 1";
        $stmt = $this->db->query($sql);
        $res = $stmt ? $stmt->fetch(\PDO::FETCH_ASSOC) : null;
        return $res ?: null;
    }

    // ── Cek apakah nomor sertifikat sudah digunakan sebelumnya
    public function isCertNoExists(string $certNo, ?int $excludeId = null): bool {
        $certNo = trim($certNo);
        if ($certNo === '') return false;
        $sql = "SELECT id FROM `{$this->table}` WHERE certificate_no = ?";
        $params = [$certNo];
        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        $sql .= " LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
    }

    // ── Ganti token pada template nomor sertifikat (tanggal, bulan Romawi, kode program)
    private function resolveCertTokens(string $tpl, string $programCode = ''): string {
        $now = time();
        $map = [
            '{Y}'    => date('Y', $now),
            '{y}'    => date('y', $now),
            '{m}'    => date('m', $now),
            '{d}'    => date('d', $now),
            '{rm}'   => $this->romanMonth((int)date('n', $now)),
            '{code}' => $programCode,
        ];
        return strtr($tpl, $map);
    }

    // ── Konversi angka bulan (1-12) ke angka Romawi (I - XII)
    private function romanMonth(int $month): string {
        $map = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        return $map[$month] ?? '';
    }

    // ── Deteksi apakah program termasuk kategori Kids
    public static function detectIsKid(string $programTitle, string $programCode = ''): bool {
        $haystack = strtoupper($programTitle . ' ' . $programCode);
        return str_contains($haystack, 'KID');
    }

    // ── Normalisasi label level program ke format kanonikal resmi
    public static function normalizeLevelLabel(?string $label): string {
        $label = trim((string)$label);
        if ($label === '') return '';

        $key = strtolower(preg_replace('/\s+/', ' ', $label));
        $canonical = [
            'basic'                  => 'Basic Level',
            'basic level'            => 'Basic Level',
            'pre-intermediate'       => 'Pre-Intermediate Level',
            'pre-intermediate level' => 'Pre-Intermediate Level',
            'pre intermediate'       => 'Pre-Intermediate Level',
            'pre intermediate level' => 'Pre-Intermediate Level',
            'intermediate'           => 'Intermediate Level',
            'intermediate level'     => 'Intermediate Level',
            'pre-advanced'           => 'Pre-Advanced Level',
            'pre-advanced level'     => 'Pre-Advanced Level',
            'pre advanced'           => 'Pre-Advanced Level',
            'pre advanced level'     => 'Pre-Advanced Level',
            'advanced'               => 'Advanced Level',
            'advanced level'         => 'Advanced Level',
            'kids'                   => 'Kids Level',
            'kids level'             => 'Kids Level',
            'kid'                    => 'Kids Level',
            'kid level'              => 'Kids Level',
        ];

        if (isset($canonical[$key])) {
            return $canonical[$key];
        }

        $formatted = ucwords($key, " \t\r\n\f\v-");
        if (!preg_match('/\blevel$/i', $formatted)) {
            $formatted .= ' Level';
        }
        return $formatted;
    }

    // ── Resolusi cerdas jenjang level program kursus (misal Speaking for IELTS -> Advanced Level)
    public static function resolveLevel(?string $title, ?string $code = ''): string {
        $title = trim((string)$title);
        $code  = strtoupper(trim((string)$code));

        try {
            $db = \App\Core\Database::getConnection();

            // 1. Cek langsung ke database tabel programs berdasarkan code (jika diisi)
            if ($code !== '') {
                $stmt = $db->prepare("SELECT level_label, level_category FROM programs WHERE UPPER(TRIM(code)) = ? AND is_active = 1 LIMIT 1");
                $stmt->execute([$code]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($row) {
                    $lvl = trim((string)($row['level_label'] ?? ''));
                    if ($lvl === '' && !empty($row['level_category'])) {
                        $lvl = $row['level_category'];
                    }
                    if ($lvl !== '') return self::normalizeLevelLabel($lvl);
                }
            }

            // 2. Cek ke database programs berdasarkan judul persis
            if ($title !== '') {
                $stmt = $db->prepare("SELECT level_label, level_category FROM programs WHERE LOWER(TRIM(title)) = LOWER(?) LIMIT 1");
                $stmt->execute([$title]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($row) {
                    $lvl = trim((string)($row['level_label'] ?? ''));
                    if ($lvl === '' && !empty($row['level_category'])) {
                        $lvl = $row['level_category'];
                    }
                    if ($lvl !== '') return self::normalizeLevelLabel($lvl);
                }
            }
        } catch (\Throwable $e) {
            // Fallback ke aturan heuristik jika query DB terkendala
        }

        // 3. Aturan Heuristik & Pemetaan Khusus Mr. Pepsi
        $cleanTitle = strtoupper(preg_replace('/[^a-zA-Z0-9\s]/', ' ', $title));
        $combined   = strtoupper($code . ' ' . $cleanTitle);

        // IELTS programs (Speaking for IELTS, Vocabulary for IELTS, Mock Up)
        if (str_contains($combined, 'IELTS') || str_contains($code, 'SFI') || str_contains($combined, 'VFI') || str_contains($combined, 'MSFI')) {
            // General Training / GT -> Pre-Advanced Level
            if (str_contains($combined, 'GENERAL') || str_contains($code, 'GT') || str_contains($combined, 'SFI GT')) {
                return 'Pre-Advanced Level';
            }
            // Vocabulary for IELTS -> Pre-Advanced Level
            if (str_contains($combined, 'VOCAB') || $code === 'VFI' || $code === 'SFI-V') {
                return 'Pre-Advanced Level';
            }
            // Speaking for IELTS (Academics / Mock Up / Umum tanpa embel-embel GT) -> Advanced Level
            return 'Advanced Level';
        }

        // Job Interview
        if ($code === 'JI' || str_contains($combined, 'JOB INTERVIEW')) {
            return 'Advanced Level';
        }

        // Pre-Advanced: GFS2, Snap Chat (SC), Smash Speak (SS), Active Speaking (AS)
        if (
            $code === 'GFS2' || str_contains($combined, 'GRAMMAR FOR SPEAKING 2') ||
            $code === 'SC'   || str_contains($combined, 'SNAP CHAT') ||
            $code === 'SS'   || str_contains($combined, 'SMASH SPEAK') ||
            $code === 'AS'   || str_contains($combined, 'ACTIVE SPEAKING')
        ) {
            return 'Pre-Advanced Level';
        }

        // Intermediate: Impromptu (IP), GFS1, Let's Talk 2 (LT2), Swap Vocab (SV)
        if (
            $code === 'IP'   || str_contains($combined, 'IMPROMPTU') ||
            $code === 'GFS1' || str_contains($combined, 'GRAMMAR FOR SPEAKING 1') ||
            $code === 'GFS'  || str_contains($combined, 'GRAMMAR FOR SPEAKING') ||
            $code === 'LT2'  || str_contains($combined, 'LET S TALK 2') || str_contains($combined, 'LETS TALK 2') ||
            $code === 'SV'   || str_contains($combined, 'SWAP VOCAB')
        ) {
            return 'Intermediate Level';
        }

        // Pre-Intermediate: Starter (ST, S4T)
        if ($code === 'ST' || $code === 'S4T' || str_contains($combined, 'STARTER')) {
            return 'Pre-Intermediate Level';
        }

        // Basic: Let's Talk 1 (LT1, SZ)
        if (
            $code === 'LT1' || $code === 'SZ' ||
            str_contains($combined, 'LET S TALK 1') || str_contains($combined, 'LETS TALK 1') ||
            str_contains($combined, 'LET S TALK') || str_contains($combined, 'LETS TALK')
        ) {
            return 'Basic Level';
        }

        // Kids: Fun English for Kids
        if (str_contains($combined, 'KID')) {
            return 'Kids Level';
        }

        // 4. Pencocokan kata kunci pada tabel programs
        try {
            if ($title !== '') {
                $words = array_filter(explode(' ', trim($title)), fn($w) => strlen($w) >= 3);
                if (!empty($words) && isset($db)) {
                    $like = '%' . implode('%', array_slice($words, 0, 2)) . '%';
                    $stmt = $db->prepare("SELECT level_label, level_category FROM programs WHERE title LIKE ? LIMIT 1");
                    $stmt->execute([$like]);
                    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                    if ($row) {
                        $lvl = trim((string)($row['level_label'] ?? ''));
                        if ($lvl === '' && !empty($row['level_category'])) {
                            $lvl = $row['level_category'];
                        }
                        if ($lvl !== '') return self::normalizeLevelLabel($lvl);
                    }
                }
            }
        } catch (\Throwable $e) {}

        return '';
    }

    // ── Add a graduation record
    public function graduate(int $studentId, array $data, int $createdBy = 0): array {
        $certNo = trim((string)($data['certificate_no'] ?? ''));
        if ($certNo === '') {
            $certNo = $this->generateCertNo($data['program_code'] ?? '');
        }
        $isKid  = self::detectIsKid($data['program_title'] ?? '', $data['program_code'] ?? '') ? 1 : 0;

        $progLevel = trim((string)($data['program_level'] ?? ''));
        if ($progLevel === '') {
            $progLevel = self::resolveLevel($data['program_title'] ?? '', $data['program_code'] ?? '');
        } else {
            $progLevel = self::normalizeLevelLabel($progLevel);
        }

        $createData = [
            'student_id'     => $studentId,
            'certificate_no' => $certNo,
            'program_title'  => $data['program_title'],
            'program_code'   => $data['program_code'] ?? '',
            'period_name'    => $data['period_name'] ?? '',
            'graduated_at'   => $data['graduated_at'] ?? date('Y-m-d'),
            'final_score'    => null,
            'grade'          => $this->normalizeGrade($data['grade'] ?? ''),
            'is_kid'         => $isKid,
            'notes'          => null,
            'created_by'     => $createdBy ?: null,
        ];
        if ($progLevel !== '') {
            $createData['program_level'] = $progLevel;
        }

        $id = $this->create($createData);

        // Update status siswa: hanya berstatus 'graduated' jika SEMUA program yang diambil telah terbit sertifikatnya.
        // Jika masih ada program lain yang belum selesai, siswa tetap berstatus 'active'.
        $this->updateStudentStatusAfterGraduation($studentId);

        return ['id' => $id, 'certificate_no' => $certNo, 'is_kid' => $isKid, 'program_level' => $progLevel];
    }

    // ── Update status siswa berdasarkan kelengkapan sertifikat seluruh program yang didaftarkan
    public function updateStudentStatusAfterGraduation(int $studentId): void {
        $studentModel = new Student();
        $student = $studentModel->find($studentId);
        if (!$student) return;

        $programs   = json_decode($student['programs_json'] ?? '[]', true) ?: [];
        $issuedList = $this->getByStudent($studentId);

        // Jika sama sekali belum memiliki sertifikat yang diterbitkan: status tetap active
        if (empty($issuedList)) {
            if ($student['status'] !== 'active') {
                $studentModel->update($studentId, ['status' => 'active']);
            }
            return;
        }

        // Jika programs_json kosong tapi ada sertifikat terbit: anggap graduated
        if (empty($programs)) {
            if ($student['status'] !== 'graduated') {
                $studentModel->update($studentId, ['status' => 'graduated']);
            }
            return;
        }

        // Cek apakah setiap program yang didaftarkan sudah diterbitkan sertifikatnya
        $enriched = $this->enrichStudentPrograms($student, $issuedList);
        $allIssued = true;
        foreach ($enriched as $ep) {
            if (empty($ep['is_issued'])) {
                $allIssued = false;
                break;
            }
        }

        $newStatus = $allIssued ? 'graduated' : 'active';
        if ($student['status'] !== $newStatus) {
            $studentModel->update($studentId, ['status' => $newStatus]);
        }
    }

    // ── Ambil semua data sertifikat kelulusan dikelompokkan per student_id
    public function getGraduationsGroupedByStudent(): array {
        $rows = $this->raw("SELECT id, student_id, certificate_no, program_title, program_code, grade, graduated_at, is_kid, created_at FROM `{$this->table}` ORDER BY graduated_at DESC, id DESC");
        $byStudent = [];
        foreach ($rows as $r) {
            $byStudent[(int)$r['student_id']][] = $r;
        }
        return $byStudent;
    }

    // ── Enrich daftar program siswa dengan status penerbitan sertifikat (is_issued, certificate_no, grade)
    public function enrichStudentPrograms(array $student, array $issuedRows = [], array $activePrograms = []): array {
        $programs = json_decode($student['programs_json'] ?? '[]', true) ?: [];
        if (empty($issuedRows) && !empty($student['id'])) {
            $issuedRows = $this->getByStudent((int)$student['id']);
        }

        // Peta kode program dari activePrograms
        $progCodeById = [];
        $progCodeByTitle = [];
        foreach ($activePrograms as $ap) {
            if (!empty($ap['id']) && !empty($ap['code'])) {
                $progCodeById[(int)$ap['id']] = (string)$ap['code'];
            }
            if (!empty($ap['title']) && !empty($ap['code'])) {
                $progCodeByTitle[strtolower(trim((string)$ap['title']))] = (string)$ap['code'];
            }
        }

        $usedGradIds = [];
        $enriched = [];

        foreach ($programs as $prog) {
            $title = trim((string)($prog['title'] ?? ''));
            $code  = trim((string)($prog['code'] ?? ''));

            // Auto-isi kode program jika di programs_json masih kosong
            if ($code === '') {
                $progId = (int)($prog['id'] ?? 0);
                if ($progId && isset($progCodeById[$progId])) {
                    $code = $progCodeById[$progId];
                } elseif ($title !== '' && isset($progCodeByTitle[strtolower($title)])) {
                    $code = $progCodeByTitle[strtolower($title)];
                }
            }

            $matched = null;
            foreach ($issuedRows as $g) {
                if (in_array((int)$g['id'], $usedGradIds, true)) continue;

                $gTitle = trim((string)($g['program_title'] ?? ''));
                $gCode  = trim((string)($g['program_code'] ?? ''));

                $matchTitle = ($title !== '' && strcasecmp($title, $gTitle) === 0);
                $matchCode  = ($code !== '' && strcasecmp($code, $gCode) === 0);

                if ($matchTitle || $matchCode) {
                    $matched = $g;
                    $usedGradIds[] = (int)$g['id'];
                    break;
                }
            }

            $isIssued = ($matched !== null);
            $enriched[] = [
                'id'             => $prog['id'] ?? null,
                'title'          => $title,
                'code'           => $code,
                'is_issued'      => $isIssued,
                'certificate_id' => $matched ? (int)$matched['id'] : null,
                'certificate_no' => $matched['certificate_no'] ?? '',
                'grade'          => $matched ? $this->normalizeGrade($matched['grade'] ?? 'GOOD') : 'GOOD',
                'graduated_at'   => $matched['graduated_at'] ?? null,
                'checked'        => !$isIssued,
                'manual_cert_no' => '',
            ];
        }

        return $enriched;
    }

    // ── Normalisasi predikat kelulusan (hanya kategori yang diizinkan)
    public function normalizeGrade(?string $grade): string {
        $grade = strtoupper(trim((string)$grade));
        if (isset(self::LEGACY_GRADE_MAP[$grade])) {
            return self::LEGACY_GRADE_MAP[$grade];
        }
        return in_array($grade, self::GRADE_CATEGORIES, true) ? $grade : 'GOOD';
    }

    // ── Get all graduations for a student
    public function getByStudent(int $studentId): array {
        return $this->raw("SELECT * FROM `{$this->table}` WHERE student_id = ? ORDER BY graduated_at DESC", [$studentId]);
    }

    // ── Get full list with student info (for graduate list)
    public function getAllWithStudent(string $search = '', string $period = ''): array {
        $sql = "SELECT sg.*, s.full_name, s.student_no, s.phone, s.city, s.time_slot
                FROM `{$this->table}` sg
                JOIN students s ON sg.student_id = s.id
                WHERE 1=1";
        $params = [];
        if (!empty($search)) {
            $sql .= " AND (s.full_name LIKE ? OR sg.certificate_no LIKE ? OR sg.program_title LIKE ? OR s.student_no LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if (!empty($period)) {
            $sql .= " AND sg.period_name LIKE ?";
            $params[] = "%$period%";
        }
        $sql .= " ORDER BY sg.graduated_at DESC, sg.id DESC";
        $rows = $this->raw($sql, $params);
        foreach ($rows as &$r) {
            $lvl = trim((string)($r['program_level'] ?? ''));
            if ($lvl === '') {
                $r['program_level'] = self::resolveLevel($r['program_title'] ?? '', $r['program_code'] ?? '');
            } else {
                $r['program_level'] = self::normalizeLevelLabel($lvl);
            }
        }
        unset($r);
        return $rows;
    }

    // ── Find one with full student info (for certificate print)
    public function findForCertificate(int $id): ?array {
        $sql = "SELECT sg.*, s.full_name, s.student_no, s.phone, s.city, s.gender, s.birth_date,
                       p.level_label AS p_level_label,
                       p.level_category AS p_level_category,
                       p.title AS p_title,
                       p.code AS p_code
                FROM `{$this->table}` sg
                JOIN students s ON sg.student_id = s.id
                LEFT JOIN programs p
                       ON (
                           LOWER(TRIM(p.title)) = LOWER(TRIM(sg.program_title))
                           OR (sg.program_code != '' AND UPPER(TRIM(p.code)) = UPPER(TRIM(sg.program_code)))
                       )
                WHERE sg.id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $res = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$res) return null;

        // Tentukan level_label secara bertingkat & akurat
        $storedLevel = trim((string)($res['program_level'] ?? ''));
        $level = $storedLevel;

        if ($level === '') {
            $level = trim((string)($res['p_level_label'] ?? ''));
        }
        if ($level === '' && !empty($res['p_level_category'])) {
            $level = self::normalizeLevelLabel($res['p_level_category']);
        }
        if ($level === '') {
            $level = self::resolveLevel($res['program_title'] ?? '', $res['program_code'] ?? '');
        } else {
            $level = self::normalizeLevelLabel($level);
        }

        $res['program_level'] = $level;

        // Auto-backfill ke database jika kolom program_level di record ini masih kosong
        if ($storedLevel === '' && $level !== '' && !empty($res['id'])) {
            try {
                $this->update((int)$res['id'], ['program_level' => $level]);
            } catch (\Throwable $e) {}
        }

        return $res;
    }

    // ── Stats
    public function getStats(): array {
        $stmt = $this->db->query("SELECT COUNT(*) AS total,
            COUNT(DISTINCT student_id) AS unique_students
            FROM `{$this->table}`");
        $stats = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];

        $dist = $this->db->query("SELECT grade, COUNT(*) AS total FROM `{$this->table}` GROUP BY grade ORDER BY total DESC, grade ASC");
        $stats['grades'] = $dist ? $dist->fetchAll(\PDO::FETCH_ASSOC) : [];

        return $stats;
    }

    // ── Distinct periods
    public function getDistinctPeriods(): array {
        $stmt = $this->db->query("SELECT DISTINCT period_name FROM `{$this->table}` WHERE period_name != '' ORDER BY period_name DESC");
        return $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
    }
}
