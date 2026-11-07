<?php
namespace App\Models;

use App\Core\Model;

class User extends Model {
    protected string $table = 'users';

    // Durasi sesi dianggap aktif sejak aktivitas terakhir (detik).
    // Dipakai agar sesi yang ditinggal (browser ditutup tanpa logout)
    // tidak mengunci user selamanya.
    public const SESSION_TIMEOUT = 1800; // 30 menit

    public function __construct() {
        parent::__construct();
        // Auto-migrate: kolom untuk penegakan 1 user = 1 sesi/device
        try {
            $cols = $this->db->query("SHOW COLUMNS FROM `{$this->table}` LIKE 'session_token'")->fetchAll();
            if (empty($cols)) {
                $this->db->exec("ALTER TABLE `{$this->table}`
                    ADD COLUMN `session_token` VARCHAR(128) NULL AFTER `role`,
                    ADD COLUMN `last_seen` DATETIME NULL AFTER `session_token`");
            }

            $logCol = $this->db->query("SHOW COLUMNS FROM `{$this->table}` LIKE 'log_login'")->fetchAll();
            if (empty($logCol)) {
                $this->db->exec("ALTER TABLE `{$this->table}`
                    ADD COLUMN `log_login` TINYINT(1) NOT NULL DEFAULT 0 AFTER `last_seen`");
            }
        } catch (\Exception $e) {
            // Tabel belum ada — biarkan, akan dibuat saat migrasi penuh
        }
    }

    /** Sesi login user yang masih dianggap aktif (belum timeout), atau null. */
    public function activeSession(int $id, int $timeout = self::SESSION_TIMEOUT): ?array {
        $row = $this->find($id);
        if (!$row || empty($row['session_token']) || (int)($row['log_login'] ?? 0) !== 1) {
            return null;
        }
        if (!empty($row['last_seen']) && (time() - strtotime($row['last_seen'])) > $timeout) {
            return null; // sudah kedaluwarsa
        }
        return $row;
    }

    /**
     * Apakah user sedang login di perangkat lain?
     * Return true bila ada sesi aktif (log_login = 1, belum timeout) yang token-nya
     * berbeda dari token perangkat saat ini. Perangkat yang sama selalu boleh re-login.
     */
    public function isLoggedInElsewhere(int $id, ?string $currentDeviceToken, int $timeout = self::SESSION_TIMEOUT): bool {
        $row = $this->find($id);
        if (!$row || (int)($row['log_login'] ?? 0) !== 1 || empty($row['session_token'])) {
            return false;
        }
        // Sesi sudah kedaluwarsa -> bebaskan (sekalian bersihkan flag)
        if (!empty($row['last_seen']) && (time() - strtotime($row['last_seen'])) > $timeout) {
            $this->clearSession($id);
            return false;
        }
        // Perangkat yang sama (cookie token cocok) -> boleh login ulang
        if ($currentDeviceToken !== null && hash_equals((string)$row['session_token'], $currentDeviceToken)) {
            return false;
        }
        return true;
    }

    /** Status online sebuah baris user (dipakai daftar manajemen user). */
    public function isOnline(array $row, int $timeout = self::SESSION_TIMEOUT): bool {
        if ((int)($row['log_login'] ?? 0) !== 1 || empty($row['session_token'])) {
            return false;
        }
        if (!empty($row['last_seen']) && (time() - strtotime($row['last_seen'])) > $timeout) {
            return false;
        }
        return true;
    }

    /** Tandai device/sesi ini sebagai sesi aktif untuk user. */
    public function startSession(int $id, string $token): void {
        $this->update($id, [
            'session_token' => $token,
            'last_seen'     => date('Y-m-d H:i:s'),
            'log_login'     => 1,
        ]);
    }

    /** Perbarui waktu aktivitas terakhir (heartbeat). */
    public function touchSession(int $id): void {
        $this->update($id, [
            'last_seen' => date('Y-m-d H:i:s'),
            'log_login' => 1,
        ]);
    }

    /** Lepaskan sesi (dipanggil saat logout atau reset sesi admin). */
    public function clearSession(int $id): void {
        $this->update($id, ['session_token' => null, 'last_seen' => null, 'log_login' => 0]);
    }

    /** Lepaskan semua sesi aktif sekaligus. */
    public function clearAllSessions(?int $exceptUserId = null): int {
        if ($exceptUserId !== null && $exceptUserId > 0) {
            $stmt = $this->db->prepare("UPDATE `{$this->table}` SET `session_token` = NULL, `last_seen` = NULL, `log_login` = 0 WHERE `id` != ?");
            $stmt->execute([$exceptUserId]);
            return $stmt->rowCount();
        }
        $stmt = $this->db->query("UPDATE `{$this->table}` SET `session_token` = NULL, `last_seen` = NULL, `log_login` = 0");
        return $stmt->rowCount();
    }

    public function findByUsername(string $username): ?array {
        $stmt = $this->db->prepare("SELECT * FROM `{$this->table}` WHERE `username` = ? LIMIT 1");
        $stmt->execute([$username]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function authenticate(string $username, string $password): ?array {
        $user = $this->findByUsername($username);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return null;
    }

    public function countAdmins(): int {
        $stmt = $this->db->query("SELECT COUNT(*) FROM `{$this->table}` WHERE `role` = 'admin'");
        return (int)$stmt->fetchColumn();
    }

    public function countOwners(): int {
        $stmt = $this->db->query("SELECT COUNT(*) FROM `{$this->table}` WHERE `role` = 'owner'");
        return (int)$stmt->fetchColumn();
    }
}
