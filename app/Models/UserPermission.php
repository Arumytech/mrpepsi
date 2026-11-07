<?php
namespace App\Models;

use App\Core\Model;

/**
 * Override hak akses menu per user.
 * Bila seorang user punya baris di tabel ini, daftar menu-nya menimpa matrik role.
 */
class UserPermission extends Model {
    protected string $table = 'user_permissions';

    public function isConfigured(int $userId): bool {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM `{$this->table}` WHERE `user_id` = ?");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function allowedKeys(int $userId): array {
        $rows = $this->raw("SELECT menu_key FROM `{$this->table}` WHERE `user_id` = ? AND `allowed` = 1", [$userId]);
        return array_map(fn($r) => $r['menu_key'], $rows);
    }

    /** Peta menu_key => bool (untuk mengisi checkbox di UI). */
    public function mapForUser(int $userId): array {
        $rows = $this->raw("SELECT menu_key, allowed FROM `{$this->table}` WHERE `user_id` = ?", [$userId]);
        $out = [];
        foreach ($rows as $r) {
            $out[$r['menu_key']] = (bool)(int)$r['allowed'];
        }
        return $out;
    }

    /**
     * Tambahkan baris untuk menu_key yang belum pernah tersimpan pada override user ini
     * (menu baru setelah terakhir kali hak akses user disimpan), memakai nilai default.
     */
    public function syncMissingKeys(int $userId, array $allKeys, array $defaultAllowed): bool {
        $rows = $this->raw("SELECT menu_key FROM `{$this->table}` WHERE `user_id` = ?", [$userId]);
        $existing = array_flip(array_column($rows, 'menu_key'));
        $missing = array_values(array_diff($allKeys, array_keys($existing)));
        if ($missing === []) {
            return true;
        }
        $allowed = array_flip($defaultAllowed);
        $stmt = $this->db->prepare("INSERT INTO `{$this->table}` (`user_id`, `menu_key`, `allowed`) VALUES (?, ?, ?)");
        foreach ($missing as $key) {
            $stmt->execute([$userId, $key, isset($allowed[$key]) ? 1 : 0]);
        }
        return true;
    }

    /** Simpan seluruh state menu untuk satu user (0/1 untuk semua key). */
    public function saveUser(int $userId, array $allKeys, array $allowedKeys): bool {
        $this->db->beginTransaction();
        try {
            $this->db->prepare("DELETE FROM `{$this->table}` WHERE `user_id` = ?")->execute([$userId]);
            if (!empty($allKeys)) {
                $allowed = array_flip($allowedKeys);
                $stmt = $this->db->prepare("INSERT INTO `{$this->table}` (`user_id`, `menu_key`, `allowed`) VALUES (?, ?, ?)");
                foreach ($allKeys as $key) {
                    $stmt->execute([$userId, $key, isset($allowed[$key]) ? 1 : 0]);
                }
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
        return true;
    }

    /** Hapus override -> user kembali memakai default role. */
    public function resetUser(int $userId): bool {
        return $this->db->prepare("DELETE FROM `{$this->table}` WHERE `user_id` = ?")->execute([$userId]);
    }
}
