<?php
namespace App\Models;

use App\Core\Model;

class Student extends Model {
    protected string $table = 'students';

    // ── Generate student number SIS-YYYYMMDD-NNNN
    public function generateStudentNo(): string {
        $prefix = 'SIS-' . date('Ymd') . '-';
        $stmt = $this->db->prepare("SELECT student_no FROM `{$this->table}` WHERE student_no LIKE ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$prefix . '%']);
        $last = $stmt->fetchColumn();
        $seq = $last ? ((int)substr($last, strlen($prefix)) + 1) : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    // ── Create student record from approved registration
    public function createFromRegistration(array $reg, array $extra = []): int {
        // Avoid duplicate
        $check = $this->db->prepare("SELECT id FROM `{$this->table}` WHERE registration_id = ? LIMIT 1");
        $check->execute([$reg['id']]);
        $existing = $check->fetchColumn();
        if ($existing) return (int)$existing;

        $studentNo = $this->generateStudentNo();
        $totalFee = (float)($extra['total_fee'] ?? $reg['total_fee'] ?? 0);
        $paidAmt = (float)($extra['paid_amount'] ?? 0);
        $paymentStatus = 'unpaid';
        if ($paidAmt >= $totalFee && $totalFee > 0) $paymentStatus = 'paid';
        elseif ($paidAmt > 0) $paymentStatus = 'partial';

        return $this->create([
            'student_no'      => $studentNo,
            'registration_id' => $reg['id'],
            'full_name'       => $reg['student_name'],
            'phone'           => $reg['phone'],
            'city'            => $reg['city'],
            'gender'          => $extra['gender'] ?? '',
            'birth_date'      => $extra['birth_date'] ?? null,
            'period_name'     => $reg['period_name'],
            'time_slot'       => $reg['time_slot'],
            'programs_json'   => $reg['programs_json'],
            'total_fee'       => $totalFee,
            'paid_amount'     => $paidAmt,
            'payment_status'  => $paymentStatus,
            'payment_method'  => $extra['payment_method'] ?? 'tunai',
            'status'          => 'active',
            'notes'           => $extra['notes'] ?? $reg['admin_notes'] ?? null,
            'enrolled_at'     => date('Y-m-d'),
        ]);
    }

    // ── Get with graduation counts & search/filter
    public function getFiltered(string $status = 'all', string $search = '', string $period = '', string $startDate = '', string $endDate = ''): array {
        $sql = "SELECT s.*,
                    (SELECT COUNT(*) FROM student_graduations sg WHERE sg.student_id = s.id) AS grad_count
                FROM `{$this->table}` s
                WHERE 1=1";
        $params = [];

        if ($status !== 'all') {
            $sql .= " AND s.status = ?";
            $params[] = $status;
        }
        if (!empty($search)) {
            $sql .= " AND (s.full_name LIKE ? OR s.student_no LIKE ? OR s.phone LIKE ? OR s.city LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if (!empty($period)) {
            $sql .= " AND s.period_name LIKE ?";
            $params[] = "%$period%";
        }
        if (!empty($startDate)) {
            $sql .= " AND s.enrolled_at >= ?";
            $params[] = $startDate;
        }
        if (!empty($endDate)) {
            $sql .= " AND s.enrolled_at <= ?";
            $params[] = $endDate;
        }
        $sql .= " ORDER BY s.enrolled_at DESC, s.id DESC";
        return $this->raw($sql, $params);
    }

    // ── Stats summary
    public function getStats(): array {
        $stmt = $this->db->query("SELECT
            COUNT(*) AS total,
            SUM(status='active') AS active,
            SUM(status='graduated') AS graduated,
            SUM(status='dropped') AS dropped,
            COALESCE(SUM(enrolled_at = CURDATE() OR DATE(created_at) = CURDATE()), 0) AS today,
            COALESCE(SUM(total_fee),0) AS total_revenue
            FROM `{$this->table}`");
        $stats = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];

        // Hitung total program kursus yang terjual hari ini
        $todayRows = $this->raw("SELECT programs_json FROM `{$this->table}` WHERE enrolled_at = CURDATE() OR DATE(created_at) = CURDATE()");
        $todayPrograms = 0;
        foreach ($todayRows as $r) {
            $progs = json_decode($r['programs_json'] ?? '[]', true) ?: [];
            $todayPrograms += max(1, count($progs));
        }
        $stats['today_programs'] = $todayPrograms;

        // Hitung total program kursus keseluruhan
        $allRows = $this->raw("SELECT programs_json FROM `{$this->table}`");
        $totalPrograms = 0;
        foreach ($allRows as $r) {
            $progs = json_decode($r['programs_json'] ?? '[]', true) ?: [];
            $totalPrograms += max(1, count($progs));
        }
        $stats['total_programs'] = $totalPrograms;

        return $stats;
    }

    // ── Siswa dengan sisa tagihan (belum bayar / bayar sebagian), semua status
    public function getUnpaidStudents(int $limit = 10): array {
        $sql = "SELECT s.*,
                    (SELECT COUNT(*) FROM student_graduations sg WHERE sg.student_id = s.id) AS grad_count
                FROM `{$this->table}` s
                WHERE s.total_fee > 0 AND s.payment_status != 'paid'
                ORDER BY s.payment_status = 'unpaid' DESC, s.enrolled_at DESC, s.id DESC
                LIMIT " . (int)$limit;
        return $this->raw($sql);
    }

    // ── Ringkasan piutang siswa yang belum lunas
    public function getUnpaidSummary(): array {
        $stmt = $this->db->query("SELECT
            COUNT(*) AS count,
            COALESCE(SUM(total_fee - paid_amount),0) AS total_due
            FROM `{$this->table}`
            WHERE total_fee > 0 AND payment_status != 'paid'");
        $r = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
        return [
            'count'     => (int)($r['count'] ?? 0),
            'total_due' => (float)($r['total_due'] ?? 0),
        ];
    }

    // ── Find student with graduation list
    public function findWithDetails(int $id): ?array {
        $s = $this->find($id);
        if (!$s) return null;
        $s['programs'] = json_decode($s['programs_json'] ?? '[]', true) ?: [];
        $gradModel = new StudentGraduation();
        $s['graduations'] = $gradModel->getByStudent($id);
        return $s;
    }

    // ── Graduate a student (mark all programs as passed, update status)
    public function markGraduated(int $id): bool {
        return $this->update($id, ['status' => 'graduated']);
    }

    // ── Distinct periods for filter
    public function getDistinctPeriods(): array {
        $stmt = $this->db->query("SELECT DISTINCT period_name FROM `{$this->table}` WHERE period_name != '' ORDER BY period_name DESC");
        return $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
    }

    // ── Flexible query for printable reports (student list & finance)
    public function queryReport(array $f = []): array {
        $sql = "SELECT s.*,
                    (SELECT COUNT(*) FROM student_graduations sg WHERE sg.student_id = s.id) AS grad_count
                FROM `{$this->table}` s
                WHERE 1=1";
        $params = [];

        $status = $f['status'] ?? 'all';
        if (!empty($status) && $status !== 'all') {
            $sql .= " AND s.status = ?";
            $params[] = $status;
        }
        if (!empty($f['search'])) {
            $sql .= " AND (s.full_name LIKE ? OR s.student_no LIKE ? OR s.phone LIKE ? OR s.city LIKE ?)";
            $like = '%' . $f['search'] . '%';
            array_push($params, $like, $like, $like, $like);
        }
        if (!empty($f['period'])) {
            $sql .= " AND s.period_name = ?";
            $params[] = $f['period'];
        }
        if (!empty($f['payment_status']) && $f['payment_status'] !== 'all') {
            $sql .= " AND s.payment_status = ?";
            $params[] = $f['payment_status'];
        }
        if (!empty($f['payment_method']) && $f['payment_method'] !== 'all') {
            $sql .= " AND s.payment_method = ?";
            $params[] = $f['payment_method'];
        }
        if (!empty($f['start_date'])) {
            $sql .= " AND s.enrolled_at >= ?";
            $params[] = $f['start_date'];
        }
        if (!empty($f['end_date'])) {
            $sql .= " AND s.enrolled_at <= ?";
            $params[] = $f['end_date'];
        }

        $sql .= " ORDER BY s.enrolled_at ASC, s.id ASC";
        return $this->raw($sql, $params);
    }

    /**
     * Periksa apakah siswa (berdasarkan nomor HP atau nama) sudah pernah mengambil
     * program kelas yang sama sebelumnya (walaupun sudah lama / sudah lulus).
     *
     * @param string $phone
     * @param string $name
     * @param array<int|string> $requestedProgramIds Daftar ID program yang dipilih saat ini
     * @param int|null $excludeStudentId Abaikan ID siswa ini jika sedang edit siswa
     * @return array{has_repeat: bool, count: int, matches: array, message: string}
     */
    public function checkRepeatPrograms(string $phone, string $name = '', array $requestedProgramIds = [], ?int $excludeStudentId = null): array {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        $cleanName  = strtolower(trim($name));

        if (strlen($cleanPhone) < 7 && strlen($cleanName) < 3) {
            return ['has_repeat' => false, 'count' => 0, 'matches' => [], 'message' => ''];
        }

        // Ambil data program yang sedang didaftarkan
        $progModel = new Program();
        $targetPrograms = [];
        foreach ($requestedProgramIds as $pid) {
            $pidInt = (int)$pid;
            if ($pidInt > 0) {
                $p = $progModel->find($pidInt);
                if ($p) {
                    $targetPrograms[$pidInt] = [
                        'id'    => $p['id'],
                        'title' => trim($p['title']),
                        'code'  => trim($p['code'] ?? ''),
                    ];
                }
            }
        }

        if (empty($targetPrograms)) {
            return ['has_repeat' => false, 'count' => 0, 'matches' => [], 'message' => ''];
        }

        // Siapkan pencarian nomor HP (cocokkan 8-9 digit terakhir untuk mengantisipasi 08 vs 628)
        $phonePattern = '';
        if (strlen($cleanPhone) >= 7) {
            $lastDigits = substr($cleanPhone, -8);
            $phonePattern = '%' . $lastDigits . '%';
        }

        $conditions = [];
        $params = [];

        if ($phonePattern !== '') {
            $conditions[] = "REPLACE(REPLACE(REPLACE(phone, '-', ''), ' ', ''), '+', '') LIKE ?";
            $params[] = $phonePattern;
        }

        if (strlen($cleanName) >= 3) {
            $conditions[] = "LOWER(TRIM(full_name)) = ?";
            $params[] = $cleanName;
        }

        if (empty($conditions)) {
            return ['has_repeat' => false, 'count' => 0, 'matches' => [], 'message' => ''];
        }

        $sql = "SELECT id, student_no, full_name, phone, period_name, programs_json, enrolled_at, status
                FROM `{$this->table}`
                WHERE (" . implode(' OR ', $conditions) . ")";

        if ($excludeStudentId) {
            $sql .= " AND id != " . (int)$excludeStudentId;
        }

        $matchedStudents = $this->raw($sql, $params);
        $repeatMatches = [];

        foreach ($matchedStudents as $stu) {
            $historyProgs = json_decode($stu['programs_json'] ?? '[]', true) ?: [];
            foreach ($historyProgs as $hp) {
                $hpTitle = trim($hp['title'] ?? '');
                $hpCode  = trim($hp['code'] ?? '');
                $hpId    = (int)($hp['id'] ?? 0);

                foreach ($targetPrograms as $targetId => $tp) {
                    $isMatch = false;
                    if ($hpId > 0 && $hpId === $targetId) {
                        $isMatch = true;
                    } elseif ($hpTitle !== '' && strcasecmp($hpTitle, $tp['title']) === 0) {
                        $isMatch = true;
                    } elseif ($hpCode !== '' && $tp['code'] !== '' && strcasecmp($hpCode, $tp['code']) === 0) {
                        $isMatch = true;
                    }

                    if ($isMatch) {
                        $key = $stu['id'] . '_' . $tp['title'];
                        if (!isset($repeatMatches[$key])) {
                            $repeatMatches[$key] = [
                                'program_id'    => $targetId,
                                'program_title' => $tp['title'],
                                'student_id'    => $stu['id'],
                                'student_no'    => $stu['student_no'],
                                'student_name'  => $stu['full_name'],
                                'phone'         => $stu['phone'],
                                'period_name'   => $stu['period_name'] ?: 'Periode Lalu',
                                'date'          => $stu['enrolled_at'] ? date('d/m/Y', strtotime($stu['enrolled_at'])) : '',
                                'status'        => $stu['status'] === 'graduated' ? 'Sudah Lulus' : ($stu['status'] === 'active' ? 'Aktif' : ucfirst($stu['status'])),
                                'source'        => 'master_siswa'
                            ];
                        }
                    }
                }
            }
        }

        // Cek juga di tabel kelulusan sertifikat (student_graduations)
        try {
            $gradConditions = [];
            $gradParams = [];
            if ($phonePattern !== '') {
                $gradConditions[] = "REPLACE(REPLACE(REPLACE(s.phone, '-', ''), ' ', ''), '+', '') LIKE ?";
                $gradParams[] = $phonePattern;
            }
            if (strlen($cleanName) >= 3) {
                $gradConditions[] = "LOWER(TRIM(s.full_name)) = ?";
                $gradParams[] = $cleanName;
            }

            if (!empty($gradConditions)) {
                $sqlGrad = "SELECT sg.id, sg.student_id, sg.program_title, sg.program_code, sg.period_name, sg.graduated_at,
                                   s.student_no, s.full_name, s.phone
                            FROM student_graduations sg
                            JOIN students s ON sg.student_id = s.id
                            WHERE (" . implode(' OR ', $gradConditions) . ")";
                if ($excludeStudentId) {
                    $sqlGrad .= " AND sg.student_id != " . (int)$excludeStudentId;
                }
                $gradRows = $this->raw($sqlGrad, $gradParams);

                foreach ($gradRows as $gr) {
                    $grTitle = trim($gr['program_title'] ?? '');
                    $grCode  = trim($gr['program_code'] ?? '');

                    foreach ($targetPrograms as $targetId => $tp) {
                        if (
                            ($grTitle !== '' && strcasecmp($grTitle, $tp['title']) === 0) ||
                            ($grCode !== '' && $tp['code'] !== '' && strcasecmp($grCode, $tp['code']) === 0)
                        ) {
                            $key = $gr['student_id'] . '_' . $tp['title'];
                            if (!isset($repeatMatches[$key])) {
                                $repeatMatches[$key] = [
                                    'program_id'    => $targetId,
                                    'program_title' => $tp['title'],
                                    'student_id'    => $gr['student_id'],
                                    'student_no'    => $gr['student_no'],
                                    'student_name'  => $gr['full_name'],
                                    'phone'         => $gr['phone'],
                                    'period_name'   => $gr['period_name'] ?: 'Periode Lalu',
                                    'date'          => $gr['graduated_at'] ? date('d/m/Y', strtotime($gr['graduated_at'])) : '',
                                    'status'        => 'Sudah Lulus / Bersertifikat',
                                    'source'        => 'sertifikat_kelulusan'
                                ];
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {}

        $matches = array_values($repeatMatches);
        $hasRepeat = !empty($matches);

        $message = '';
        if ($hasRepeat) {
            $msgLines = [];
            foreach ($matches as $m) {
                $msgLines[] = "• Program \"{$m['program_title']}\" pernah diambil oleh {$m['student_name']} pada {$m['period_name']} ({$m['status']})";
            }
            $message = "Siswa ini tercatat sudah pernah mengambil program kelas yang sama:\n" . implode("\n", $msgLines) . "\n(Peraturan: Siswa tidak diperkenankan mengambil program yang sama kembali).";
        }

        return [
            'has_repeat' => $hasRepeat,
            'count'      => count($matches),
            'matches'    => $matches,
            'message'    => $message,
        ];
    }

    /**
     * Ambil daftar siswa berdasarkan Program, Periode, dan Jam/Sesi untuk Lembar Presensi Kelas.
     *
     * @param string|null $periodName Nama atau bagian nama periode (contoh: "Periode 10 Oktober 2026")
     * @param int $programId ID program di tabel programs
     * @param string $programTitle Judul program
     * @param string $programCode Kode program (contoh: SFI-AC)
     * @param string|null $timeSlot Sesi jam (contoh: "10.00-11.30")
     * @return array
     */
    public function getStudentsForClassSheet(?string $periodName = null, int $programId = 0, string $programTitle = '', string $programCode = '', ?string $timeSlot = null): array {
        $sql = "SELECT s.* FROM `{$this->table}` s WHERE s.status != 'dropped'";
        $params = [];

        // Jika periode dispesifikasikan (kecuali 'all')
        if (!empty($periodName) && $periodName !== 'all') {
            $cleanP = trim(str_ireplace('periode', '', $periodName));
            $sql .= " AND (s.period_name LIKE ? OR s.period_name LIKE ?)";
            $params[] = "%$periodName%";
            $params[] = "%$cleanP%";
        }

        $sql .= " ORDER BY s.full_name ASC";
        $rows = $this->raw($sql, $params);

        $matched = [];
        $cleanSlot = (!empty($timeSlot) && $timeSlot !== 'all') ? $this->normalizeTimeSlot($timeSlot) : '';

        foreach ($rows as $s) {
            $progs = json_decode($s['programs_json'] ?? '[]', true) ?: [];
            $hasProg = false;

            foreach ($progs as $p) {
                $pid    = (int)($p['id'] ?? 0);
                $pTitle = trim($p['title'] ?? '');
                $pCode  = trim($p['code'] ?? '');

                $isThisProg = false;
                if ($programId > 0 && $pid === $programId) {
                    $isThisProg = true;
                } elseif ($programTitle !== '' && $pTitle !== '' && strcasecmp($pTitle, $programTitle) === 0) {
                    $isThisProg = true;
                } elseif ($programCode !== '' && $pCode !== '' && strcasecmp($pCode, $programCode) === 0) {
                    $isThisProg = true;
                }

                if ($isThisProg) {
                    $hasProg = true;
                    // Cek filter slot jam jika diset
                    if ($cleanSlot !== '') {
                        $pSlot = !empty($p['time_slot']) ? $this->normalizeTimeSlot($p['time_slot']) : $this->normalizeTimeSlot($s['time_slot'] ?? '');
                        if ($pSlot !== '') {
                            if ($pSlot !== $cleanSlot && !str_contains($pSlot, $cleanSlot) && !str_contains($cleanSlot, $pSlot)) {
                                $hasProg = false;
                            }
                        }
                    }
                    if ($hasProg) {
                        break;
                    }
                }
            }

            if ($hasProg) {
                $matched[] = $s;
            }
        }

        return $matched;
    }

    public function normalizeTimeSlot(string $slot): string {
        $s = strtoupper(trim($slot));
        $s = str_replace(['WIB', 'WITA', 'WIT'], '', $s);
        $s = str_replace(':', '.', $s);
        $s = preg_replace('/\s+/', '', $s);
        return trim($s);
    }
}
