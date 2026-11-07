<?php
namespace App\Models;

use App\Core\Model;

class Program extends Model {
    protected string $table = 'programs';

    public function getActivePrograms(): array {
        return $this->where('is_active', 1, 'price ASC, title ASC');
    }

    // Peta id program -> grup laporan (online|offline|non_kelas), fallback offline
    public function getReportGroupMap(): array {
        $rows = $this->raw("SELECT p.`id`, COALESCE(c.`report_group`, 'offline') AS `report_group`
                            FROM `{$this->table}` p
                            LEFT JOIN `program_categories` c ON c.`id` = p.`category_id`");
        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['id']] = $r['report_group'];
        }
        return $map;
    }

    public function filterPrograms(string $orientation = 'all', string $level = 'all', string $keyword = ''): array {
        $sql = "SELECT * FROM `{$this->table}` WHERE is_active = 1";
        $params = [];

        if ($orientation !== 'all' && !empty($orientation)) {
            $sql .= " AND orientation = ?";
            $params[] = $orientation;
        }

        if ($level !== 'all' && !empty($level)) {
            $sql .= " AND level_category = ?";
            $params[] = $level;
        }

        if (!empty($keyword)) {
            $sql .= " AND (title LIKE ? OR code LIKE ? OR description LIKE ?)";
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
        }

        $sql .= " ORDER BY price ASC, title ASC";
        return $this->raw($sql, $params);
    }
}
