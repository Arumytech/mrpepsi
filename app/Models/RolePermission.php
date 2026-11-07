<?php
namespace App\Models;

use App\Core\Model;

class RolePermission extends Model {
    protected string $table = 'role_permissions';

    public function isConfigured(string $role): bool {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM `{$this->table}` WHERE `role` = ?");
        $stmt->execute([$role]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function allowedKeys(string $role): array {
        $rows = $this->raw("SELECT menu_key FROM `{$this->table}` WHERE `role` = ? AND `allowed` = 1", [$role]);
        return array_map(fn($r) => $r['menu_key'], $rows);
    }

    /**
     * Tambahkan baris untuk menu_key yang belum pernah tersimpan di matrik role ini
     * (menu baru setelah terakhir kali owner menyimpan hak akses), memakai nilai default.
     */
    public function syncMissingKeys(string $role, array $allKeys, array $defaultAllowed): bool {
        $rows = $this->raw("SELECT menu_key FROM `{$this->table}` WHERE `role` = ?", [$role]);
        $existing = array_flip(array_column($rows, 'menu_key'));
        $missing = array_values(array_diff($allKeys, array_keys($existing)));
        if ($missing === []) {
            return true;
        }
        $allowed = array_flip($defaultAllowed);
        $stmt = $this->db->prepare("INSERT INTO `{$this->table}` (`role`, `menu_key`, `allowed`) VALUES (?, ?, ?)");
        foreach ($missing as $key) {
            $stmt->execute([$role, $key, isset($allowed[$key]) ? 1 : 0]);
        }
        return true;
    }

    // Simpan seluruh state menu untuk satu role (0/1 utk semua key)
    public function saveRole(string $role, array $allKeys, array $allowedKeys): bool {
        $this->db->prepare("DELETE FROM `{$this->table}` WHERE `role` = ?")->execute([$role]);
        if (empty($allKeys)) {
            return true;
        }
        $allowed = array_flip($allowedKeys);
        $stmt = $this->db->prepare("INSERT INTO `{$this->table}` (role, menu_key, allowed) VALUES (?, ?, ?)");
        foreach ($allKeys as $key) {
            $stmt->execute([$role, $key, isset($allowed[$key]) ? 1 : 0]);
        }
        return true;
    }
}