<?php
namespace App\Models;

use App\Core\Model;

class Attendance extends Model {
    protected string $table = 'attendances';

    public function getDailyAttendance(string $date, ?int $periodId = null): array {
        $sql = "SELECT s.id AS schedule_id, s.time_slot, s.day_name,
                       p.title AS program_title, p.code AS program_code,
                       r.name AS room_name,
                       t_main.id AS main_tutor_id, t_main.name AS main_tutor_name, t_main.fee_per_session AS main_fee,
                       COALESCE(s.fee_rate, t_main.fee_per_session) AS default_fee,
                       a.id AS attendance_id, a.attendance_date, a.status, 
                       a.substituted_tutor_id, t_sub.name AS substituted_tutor_name,
                       a.actual_tutor_id, t_act.name AS actual_tutor_name,
                       a.session_fee, a.notes
                FROM schedules s
                JOIN programs p ON s.program_id = p.id
                JOIN rooms r ON s.room_id = r.id
                JOIN tutors t_main ON s.tutor_id = t_main.id
                LEFT JOIN attendances a ON s.id = a.schedule_id AND a.attendance_date = ?
                LEFT JOIN tutors t_sub ON a.substituted_tutor_id = t_sub.id
                LEFT JOIN tutors t_act ON a.actual_tutor_id = t_act.id
                WHERE 1=1";
        $params = [$date];

        if ($periodId) {
            $sql .= " AND s.period_id = ?";
            $params[] = $periodId;
        }

        $sql .= " ORDER BY s.time_slot ASC, r.name ASC";
        return $this->raw($sql, $params);
    }

    /**
     * Daftar seluruh sesi jadwal (opsional per periode), digabung dengan
     * catatan presensi terakhir. Dipakai untuk tampilan presensi per tutor.
     */
    public function getSessionsByTutor(?int $periodId = null): array {
        $sql = "SELECT s.id AS schedule_id, s.time_slot, s.day_name, s.period_id,
                       p.title AS program_title, p.code AS program_code,
                       r.name AS room_name,
                       t_main.id AS main_tutor_id, t_main.name AS main_tutor_name, t_main.fee_per_session AS main_fee,
                       COALESCE(s.fee_rate, t_main.fee_per_session) AS default_fee,
                       a.id AS attendance_id, a.attendance_date, a.status,
                       a.substituted_tutor_id, t_sub.name AS substituted_tutor_name,
                       a.actual_tutor_id, t_act.name AS actual_tutor_name,
                       a.session_fee, a.notes
                FROM schedules s
                JOIN programs p ON s.program_id = p.id
                JOIN rooms r ON s.room_id = r.id
                JOIN tutors t_main ON s.tutor_id = t_main.id
                LEFT JOIN attendances a ON a.id = (SELECT MAX(a2.id) FROM attendances a2 WHERE a2.schedule_id = s.id)
                LEFT JOIN tutors t_sub ON a.substituted_tutor_id = t_sub.id
                LEFT JOIN tutors t_act ON a.actual_tutor_id = t_act.id
                WHERE 1=1";
        $params = [];

        if ($periodId) {
            $sql .= " AND s.period_id = ?";
            $params[] = $periodId;
        }

        $sql .= " ORDER BY t_main.name ASC, s.time_slot ASC, r.name ASC";
        return $this->raw($sql, $params);
    }

    public function recordAttendance(int $scheduleId, string $date, string $status, ?int $substituteTutorId, ?string $notes, $manualFee = null, ?int $userId = null): bool {
        // Fetch schedule and tutor fee
        $schedStmt = $this->db->prepare("SELECT s.*, t.fee_per_session FROM schedules s JOIN tutors t ON s.tutor_id = t.id WHERE s.id = ? LIMIT 1");
        $schedStmt->execute([$scheduleId]);
        $sched = $schedStmt->fetch();
        if (!$sched) return false;

        $mainTutorId = (int)$sched['tutor_id'];
        $baseFee = (float)($sched['fee_rate'] ?? $sched['fee_per_session']);
        $hasManual = ($manualFee !== null && $manualFee !== '' && (float)$manualFee > 0);

        // Determine actual tutor and fee transfer
        if ($status === 'substituted' && $substituteTutorId) {
            $actualTutorId = (int)$substituteTutorId;
            if ($hasManual) {
                $feeToPay = (float)$manualFee;
            } else {
                // Lookup substitute tutor custom fee or default to base fee
                $subFeeStmt = $this->db->prepare("SELECT fee_per_session FROM tutors WHERE id = ?");
                $subFeeStmt->execute([$substituteTutorId]);
                $subFee = $subFeeStmt->fetchColumn();
                $feeToPay = $subFee ? (float)$subFee : $baseFee;
            }
        } elseif ($status === 'present') {
            $actualTutorId = $mainTutorId;
            $substituteTutorId = null;
            $feeToPay = $hasManual ? (float)$manualFee : $baseFee;
        } else {
            // Absent: no fee paid
            $actualTutorId = $mainTutorId;
            $substituteTutorId = null;
            $feeToPay = 0.00;
        }

        // Satu sesi hanya menyimpan satu catatan presensi (bukan per-hari).
        $this->db->prepare("DELETE FROM `{$this->table}` WHERE schedule_id = ?")->execute([$scheduleId]);

        $sql = "INSERT INTO `attendances` 
                (`schedule_id`, `attendance_date`, `main_tutor_id`, `status`, `substituted_tutor_id`, `actual_tutor_id`, `session_fee`, `notes`, `created_by`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $scheduleId, $date, $mainTutorId, $status, $substituteTutorId, $actualTutorId, $feeToPay, $notes, $userId
        ]);
    }

    public function deleteRecord(int $scheduleId, ?string $date = null): bool {
        $stmt = $this->db->prepare("DELETE FROM `{$this->table}` WHERE schedule_id = ?");
        $stmt->execute([$scheduleId]);
        return $stmt->rowCount() > 0;
    }

    public function getPayrollSummary(?string $startDate = null, ?string $endDate = null, ?int $periodId = null): array {
        // Find all tutors
        $tutors = $this->db->query("SELECT * FROM tutors WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

        $summary = [];
        foreach ($tutors as $tutor) {
            $tutorId = (int)$tutor['id'];

            // 1. Sesi mengajar utama yang dihadiri sendiri
            $sqlMainPresent = "SELECT COUNT(*) AS total_count, COALESCE(SUM(session_fee), 0) AS total_fee 
                               FROM attendances a
                               JOIN schedules s ON a.schedule_id = s.id
                               WHERE a.actual_tutor_id = ? AND a.main_tutor_id = ? AND a.status = 'present'";
            $pMain = [$tutorId, $tutorId];
            if ($startDate && $endDate) {
                $sqlMainPresent .= " AND a.attendance_date BETWEEN ? AND ?";
                $pMain[] = $startDate;
                $pMain[] = $endDate;
            }
            if ($periodId) {
                $sqlMainPresent .= " AND s.period_id = ?";
                $pMain[] = $periodId;
            }
            $stmt = $this->db->prepare($sqlMainPresent);
            $stmt->execute($pMain);
            $mainPresent = $stmt->fetch();

            // 2. Sesi sebagai Tutor Pengganti (Substitusi mengajar kelas orang lain) -> DAPAT HONOR TAMBAHAN
            $sqlSubPresent = "SELECT COUNT(*) AS total_count, COALESCE(SUM(session_fee), 0) AS total_fee 
                              FROM attendances a
                              JOIN schedules s ON a.schedule_id = s.id
                              WHERE a.actual_tutor_id = ? AND a.main_tutor_id != ? AND a.status = 'substituted'";
            $pSub = [$tutorId, $tutorId];
            if ($startDate && $endDate) {
                $sqlSubPresent .= " AND a.attendance_date BETWEEN ? AND ?";
                $pSub[] = $startDate;
                $pSub[] = $endDate;
            }
            if ($periodId) {
                $sqlSubPresent .= " AND s.period_id = ?";
                $pSub[] = $periodId;
            }
            $stmt = $this->db->prepare($sqlSubPresent);
            $stmt->execute($pSub);
            $subPresent = $stmt->fetch();

            // 3. Sesi Utama yang Berhalangan / Digantikan orang lain (HONOR HILANG / BERPINDAH KE PENGGANTI)
            $sqlReplaced = "SELECT COUNT(*) AS total_count 
                            FROM attendances a
                            JOIN schedules s ON a.schedule_id = s.id
                            WHERE a.main_tutor_id = ? AND a.status = 'substituted'";
            $pRep = [$tutorId];
            if ($startDate && $endDate) {
                $sqlReplaced .= " AND a.attendance_date BETWEEN ? AND ?";
                $pRep[] = $startDate;
                $pRep[] = $endDate;
            }
            if ($periodId) {
                $sqlReplaced .= " AND s.period_id = ?";
                $pRep[] = $periodId;
            }
            $stmt = $this->db->prepare($sqlReplaced);
            $stmt->execute($pRep);
            $replaced = $stmt->fetch();

            // 4. Sesi Utama yang Absent tanpa pengganti
            $sqlAbsent = "SELECT COUNT(*) AS total_count 
                          FROM attendances a
                          JOIN schedules s ON a.schedule_id = s.id
                          WHERE a.main_tutor_id = ? AND a.status = 'absent'";
            $pAbs = [$tutorId];
            if ($startDate && $endDate) {
                $sqlAbsent .= " AND a.attendance_date BETWEEN ? AND ?";
                $pAbs[] = $startDate;
                $pAbs[] = $endDate;
            }
            if ($periodId) {
                $sqlAbsent .= " AND s.period_id = ?";
                $pAbs[] = $periodId;
            }
            $stmt = $this->db->prepare($sqlAbsent);
            $stmt->execute($pAbs);
            $absent = $stmt->fetch();

            $totalSessionsTaught = (int)$mainPresent['total_count'] + (int)$subPresent['total_count'];
            $totalFeeEarned = (float)$mainPresent['total_fee'] + (float)$subPresent['total_fee'];

            $summary[] = [
                'tutor' => $tutor,
                'main_present_count' => (int)$mainPresent['total_count'],
                'main_present_fee' => (float)$mainPresent['total_fee'],
                'sub_present_count' => (int)$subPresent['total_count'],
                'sub_present_fee' => (float)$subPresent['total_fee'],
                'replaced_count' => (int)$replaced['total_count'],
                'absent_count' => (int)$absent['total_count'],
                'total_sessions' => $totalSessionsTaught,
                'total_salary' => $totalFeeEarned
            ];
        }

        return $summary;
    }

    public function getTutorPayrollDetail(int $tutorId, ?string $startDate = null, ?string $endDate = null, ?int $periodId = null): array {
        $tutor = $this->db->prepare("SELECT * FROM tutors WHERE id = ?");
        $tutor->execute([$tutorId]);
        $tutorData = $tutor->fetch();

        $sql = "SELECT a.*, 
                       s.time_slot, s.day_name,
                       p.title AS program_title, p.code AS program_code,
                       r.name AS room_name,
                       t_main.name AS main_tutor_name,
                       t_sub.name AS sub_tutor_name
                FROM attendances a
                JOIN schedules s ON a.schedule_id = s.id
                JOIN programs p ON s.program_id = p.id
                JOIN rooms r ON s.room_id = r.id
                JOIN tutors t_main ON a.main_tutor_id = t_main.id
                LEFT JOIN tutors t_sub ON a.substituted_tutor_id = t_sub.id
                WHERE (a.actual_tutor_id = ? OR a.main_tutor_id = ?)";
        $params = [$tutorId, $tutorId];

        if ($startDate && $endDate) {
            $sql .= " AND a.attendance_date BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }
        if ($periodId) {
            $sql .= " AND s.period_id = ?";
            $params[] = $periodId;
        }

        $sql .= " ORDER BY a.attendance_date DESC, s.time_slot ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        return [
            'tutor' => $tutorData,
            'sessions' => $items
        ];
    }
}
