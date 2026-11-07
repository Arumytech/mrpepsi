<?php
namespace App\Models;

use App\Core\Model;

class RoomInventory extends Model {
    protected string $table = 'room_inventories';

    public function getByRoom(int $roomId): array {
        return $this->where('room_id', $roomId, 'item_name ASC');
    }

    // Jumlah item inventaris per ruangan: [room_id => total_item]
    public function countsByRoom(): array {
        $rows = $this->raw("SELECT room_id, COUNT(*) AS total FROM `{$this->table}` GROUP BY room_id");
        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r['room_id']] = (int)$r['total'];
        }
        return $out;
    }
}
