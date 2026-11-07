<?php
namespace App\Models;

use App\Core\Model;

class Registration extends Model {
    protected string $table = 'registrations';

    public function generateRegistrationNo(): string {
        $prefix = 'REG-' . date('Ymd') . '-';
        $stmt = $this->db->prepare("SELECT registration_no FROM `{$this->table}` WHERE registration_no LIKE ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$prefix . '%']);
        $last = $stmt->fetchColumn();

        $seq = 1;
        if ($last) {
            $lastSeq = (int)substr($last, strlen($prefix));
            $seq = $lastSeq + 1;
        }

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function register(array $data): array {
        $regNo = $this->generateRegistrationNo();
        
        $programsJson = is_array($data['programs']) ? json_encode($data['programs']) : $data['programs'];
        
        $periodId = $data['period_id'] ?? null;
        if (!$periodId && !empty($data['period_name'])) {
            $find = $this->db->prepare("SELECT id FROM periods WHERE name = ? LIMIT 1");
            $find->execute([$data['period_name']]);
            $periodId = $find->fetchColumn() ?: null;
        }

        $id = $this->create([
            'registration_no' => $regNo,
            'period_id' => $periodId,
            'period_name' => $data['period_name'] ?? 'Periode Aktif',
            'student_name' => trim($data['student_name']),
            'phone' => trim($data['phone']),
            'city' => trim($data['city']),
            'time_slot' => trim($data['time_slot']),
            'programs_json' => $programsJson,
            'total_fee' => (float)$data['total_fee'],
            'status' => $data['status'] ?? 'pending',
            'registered_by' => $data['registered_by'] ?? 'guest',
            'admin_notes' => $data['admin_notes'] ?? null
        ]);

        return [
            'id' => $id,
            'registration_no' => $regNo
        ];
    }

    public function getFiltered(string $status = 'all', string $search = ''): array {
        $sql = "SELECT * FROM `{$this->table}` WHERE 1=1";
        $params = [];

        if ($status !== 'all' && !empty($status)) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        if (!empty($search)) {
            $sql .= " AND (student_name LIKE ? OR registration_no LIKE ? OR phone LIKE ? OR city LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " ORDER BY id DESC";
        return $this->raw($sql, $params);
    }

    public function getStats(): array {
        $sql = "SELECT 
                    COUNT(*) AS total,
                    COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) AS pending_count,
                    COALESCE(SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END), 0) AS approved_count,
                    COALESCE(SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END), 0) AS rejected_count
                FROM `{$this->table}`";
        $stmt = $this->db->query($sql);
        $res = $stmt->fetch();
        return [
            'total' => (int)($res['total'] ?? 0),
            'pending' => (int)($res['pending_count'] ?? 0),
            'approved' => (int)($res['approved_count'] ?? 0),
            'rejected' => (int)($res['rejected_count'] ?? 0)
        ];
    }

    public function updateStatus(int $id, string $status, ?string $adminNotes = null): bool {
        $updateData = ['status' => $status];
        if ($adminNotes !== null) {
            $updateData['admin_notes'] = $adminNotes;
        }
        return $this->update($id, $updateData);
    }
}
