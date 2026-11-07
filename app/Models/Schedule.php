<?php
namespace App\Models;

use App\Core\Model;

class Schedule extends Model {
    protected string $table = 'schedules';

    public function getFullSchedules(?int $periodId = null, string $timeSlot = 'all', string $coach = 'all', string $room = 'all'): array {
        $sql = "SELECT s.*, 
                       p.title AS program_title, p.code AS program_code, p.level_label, p.price, p.orientation,
                       t.name AS tutor_name, t.fee_per_session,
                       r.name AS room_name,
                       per.name AS period_name
                FROM `{$this->table}` s
                JOIN programs p ON s.program_id = p.id
                JOIN tutors t ON s.tutor_id = t.id
                JOIN rooms r ON s.room_id = r.id
                JOIN periods per ON s.period_id = per.id
                WHERE 1=1";
        $params = [];

        if ($periodId !== null) {
            $sql .= " AND s.period_id = ?";
            $params[] = $periodId;
        }
        if ($timeSlot !== 'all' && !empty($timeSlot)) {
            $sql .= " AND s.time_slot = ?";
            $params[] = $timeSlot;
        }
        if ($coach !== 'all' && !empty($coach)) {
            $sql .= " AND t.name = ?";
            $params[] = $coach;
        }
        if ($room !== 'all' && !empty($room)) {
            $sql .= " AND r.name = ?";
            $params[] = $room;
        }

        $sql .= " ORDER BY s.time_slot ASC, r.name ASC";
        return $this->raw($sql, $params);
    }

    public function checkConflict(int $periodId, string $timeSlot, int $roomId, int $tutorId, ?int $excludeId = null): ?string {
        // 1. Check Room Conflict
        $sqlRoom = "SELECT s.*, r.name AS room_name, p.title AS program_title 
                    FROM `{$this->table}` s 
                    JOIN rooms r ON s.room_id = r.id 
                    JOIN programs p ON s.program_id = p.id
                    WHERE s.period_id = ? AND s.time_slot = ? AND s.room_id = ?";
        $paramsRoom = [$periodId, $timeSlot, $roomId];
        if ($excludeId) {
            $sqlRoom .= " AND s.id != ?";
            $paramsRoom[] = $excludeId;
        }
        $existingRoom = $this->raw($sqlRoom, $paramsRoom);
        if (!empty($existingRoom)) {
            $first = $existingRoom[0];
            return "Bentrok Ruangan! Ruang '{$first['room_name']}' sudah dipakai untuk kelas '{$first['program_title']}' pada jam {$timeSlot}.";
        }

        // 2. Check Tutor Conflict
        $sqlTutor = "SELECT s.*, t.name AS tutor_name, p.title AS program_title, r.name AS room_name 
                     FROM `{$this->table}` s 
                     JOIN tutors t ON s.tutor_id = t.id 
                     JOIN programs p ON s.program_id = p.id
                     JOIN rooms r ON s.room_id = r.id
                     WHERE s.period_id = ? AND s.time_slot = ? AND s.tutor_id = ?";
        $paramsTutor = [$periodId, $timeSlot, $tutorId];
        if ($excludeId) {
            $sqlTutor .= " AND s.id != ?";
            $paramsTutor[] = $excludeId;
        }
        $existingTutor = $this->raw($sqlTutor, $paramsTutor);
        if (!empty($existingTutor)) {
            $first = $existingTutor[0];
            return "Bentrok Tutor! Coach '{$first['tutor_name']}' sudah ada jadwal mengajar '{$first['program_title']}' di Ruang '{$first['room_name']}' pada jam {$timeSlot}.";
        }

        return null; // No conflict
    }
}
