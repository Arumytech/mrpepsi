<?php
namespace App\Models;

use App\Core\Model;

class Setting extends Model {
    protected string $table = 'settings';

    public function getAllKeyValue(): array {
        $rows = $this->all('setting_key ASC');
        $map = [];
        foreach ($rows as $r) {
            $map[$r['setting_key']] = $r['setting_value'];
        }
        return $map;
    }

    public function get(string $key, string $default = ''): string {
        $stmt = $this->db->prepare("SELECT setting_value FROM `{$this->table}` WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    }

    public function set(string $key, string $value): bool {
        $stmt = $this->db->prepare("INSERT INTO `{$this->table}` (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        return $stmt->execute([$key, $value, $value]);
    }

    /**
     * Apakah transaksi POS ditutup untuk tanggal hari ini?
     *
     * POS ditutup dengan menyimpan tanggal penutupan di `pos_closed_date`.
     * Bila tanggal tersimpan sama dengan hari ini, POS dianggap tertutup dan
     * otomatis terbuka kembali esok hari (tanggal berbeda).
     */
    public function isPosClosedToday(): bool {
        return trim($this->get('pos_closed_date', '')) === date('Y-m-d');
    }
}
