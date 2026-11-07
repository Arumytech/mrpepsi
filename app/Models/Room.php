<?php
namespace App\Models;

use App\Core\Model;

class Room extends Model {
    protected string $table = 'rooms';

    public function getActive(): array {
        return $this->where('is_active', 1, 'name ASC');
    }

    // ── Jumlah siswa aktif yang menempati tiap ruangan pada suatu periode
    public function getRoomOccupancy(?int $periodId = null): array {
        $rooms = $this->getActive();

        $periodName = null;
        if ($periodId !== null) {
            $stmt = $this->db->prepare("SELECT name FROM periods WHERE id = ?");
            $stmt->execute([$periodId]);
            $periodName = $stmt->fetchColumn() ?: null;
        }

        // Peta relasi "time_slot|program_id" => room_id dari jadwal
        $sqlSched = "SELECT time_slot, program_id, room_id FROM schedules";
        $schedParams = [];
        if ($periodId !== null) {
            $sqlSched .= " WHERE period_id = ?";
            $schedParams[] = $periodId;
        }
        $roomMap = [];
        foreach ($this->raw($sqlSched, $schedParams) as $sc) {
            $key = $this->normalizeSlot($sc['time_slot']) . '|' . (int)$sc['program_id'];
            $roomMap[$key] = (int)$sc['room_id'];
        }

        // Siswa aktif pada periode tersebut
        $sqlStu = "SELECT time_slot, programs_json FROM students WHERE status = 'active'";
        $stuParams = [];
        if ($periodName !== null) {
            $sqlStu .= " AND period_name = ?";
            $stuParams[] = $periodName;
        }
        $students = $this->raw($sqlStu, $stuParams);

        $counts = [];
        $unassigned = 0;
        foreach ($students as $st) {
            $slot = $this->normalizeSlot($st['time_slot']);
            $progs = json_decode($st['programs_json'] ?? '[]', true) ?: [];
            $matched = false;
            foreach ($progs as $p) {
                $key = $slot . '|' . (int)($p['id'] ?? 0);
                if (isset($roomMap[$key])) {
                    $rid = $roomMap[$key];
                    $counts[$rid] = ($counts[$rid] ?? 0) + 1;
                    $matched = true;
                }
            }
            if (!$matched) $unassigned++;
        }

        foreach ($rooms as &$r) {
            $r['used']     = $counts[(int)$r['id']] ?? 0;
            $r['capacity'] = (int)$r['capacity'];
            $r['percent']  = $r['capacity'] > 0 ? (int)round($r['used'] / $r['capacity'] * 100) : 0;
        }
        unset($r);

        return [
            'rooms'          => $rooms,
            'total_students' => count($students),
            'unassigned'     => $unassigned,
        ];
    }

    private function normalizeSlot(string $slot): string {
        return trim(preg_replace('/\s*WIB\s*$/i', '', $slot));
    }
}
