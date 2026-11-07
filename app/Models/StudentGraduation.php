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

    // ── Add a graduation record
    public function graduate(int $studentId, array $data, int $createdBy = 0): array {
        $certNo = trim((string)($data['certificate_no'] ?? ''));
        if ($certNo === '') {
            $certNo = $this->generateCertNo($data['program_code'] ?? '');
        }
        $isKid  = self::detectIsKid($data['program_title'] ?? '', $data['program_code'] ?? '') ? 1 : 0;

        $id = $this->create([
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
        ]);

        // Update student status
        $studentModel = new Student();
        $studentModel->update($studentId, ['status' => 'graduated']);

        return ['id' => $id, 'certificate_no' => $certNo, 'is_kid' => $isKid];
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
        return $this->raw($sql, $params);
    }

    // ── Find one with full student info (for certificate print)
    public function findForCertificate(int $id): ?array {
        $sql = "SELECT sg.*, s.full_name, s.student_no, s.phone, s.city, s.gender, s.birth_date,
                       p.level_label AS program_level
                FROM `{$this->table}` sg
                JOIN students s ON sg.student_id = s.id
                LEFT JOIN programs p
                       ON LOWER(TRIM(p.title)) = LOWER(TRIM(sg.program_title))
                WHERE sg.id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $res = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $res ?: null;
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
