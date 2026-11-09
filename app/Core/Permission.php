<?php
namespace App\Core;

use App\Models\RolePermission;
use App\Models\UserPermission;

/**
 * Sistem Hak Akses berbasis Menu.
 *
 * - role `owner` selalu punya akses penuh ke semua menu.
 * - role `admin` & `cashier` diatur lewat tabel role_permissions (matrik checkbox).
 * - setiap user bisa punya override per-user lewat tabel user_permissions (menimpa role).
 * - guard URL memakai longest-prefix match terhadap definisi menu di config/menus.php.
 */
class Permission {
    private static ?array $menuCache = null;
    private static array $roleCache = [];
    private static array $userCache = [];
    private static ?array $pathIndex = null;
    private static ?string $currentKey = null;
    private static bool $syncedKeys = false;

    /** Daftar role yang ada di sistem. */
    public static function roles(): array {
        return [
            'owner'   => 'Owner',
            'admin'   => 'Administrator',
            'cashier' => 'Kasir Pepsi Food & Drink',
        ];
    }

    public static function roleLabel(string $role): string {
        return self::roles()[$role] ?? $role;
    }

    public static function menus(): array {
        if (self::$menuCache === null) {
            self::$menuCache = require BASE_PATH . '/config/menus.php';
        }
        return self::$menuCache;
    }

    /** Key menu yang diperbolehkan untuk suatu role. */
    public static function allowedFor(string $role): array {
        if ($role === 'owner') {
            return array_keys(self::menus());
        }
        if (isset(self::$roleCache[$role])) {
            return self::$roleCache[$role];
        }
        $keys = [];
        try {
            $rp = new RolePermission();
            $keys = $rp->isConfigured($role) ? $rp->allowedKeys($role) : self::defaultFor($role);
        } catch (\Throwable $e) {
            $keys = self::defaultFor($role);
        }
        return self::$roleCache[$role] = $keys;
    }

    /** Default permission saat belum pernah dikonfigurasi. */
    public static function defaultFor(string $role): array {
        if ($role === 'owner' || $role === 'admin') {
            return array_keys(self::menus());
        }
        return ['pos']; // cashier
    }

    /** Bolehkah user sesi mengakses key menu ini? */
    public static function can(?string $key): bool {
        if ($key === null || $key === '') {
            return true;
        }
        $user = $_SESSION['user'] ?? null;
        if ($user === null || !isset($user['role'])) {
            return false;
        }
        if ($user['role'] === 'owner') {
            return true;
        }
        $uid = (int)($user['id'] ?? 0);
        // Menu baru disinkronkan ke matrik agar role/user terkonfigurasi tidak kehilangan aksesnya
        self::syncNewMenuKeys((string)$user['role'], $uid);
        // Override per-user lebih diutamakan daripada matrik role
        if ($uid > 0) {
            $override = self::userAllowed($uid);
            if ($override !== null) {
                return in_array($key, $override, true);
            }
        }
        return in_array($key, self::allowedFor($user['role']), true);
    }

    /**
     * Isi baris permission untuk menu_key yang belum pernah tersimpan (menu yang baru
     * ditambahkan) dengan nilai default, agar hak akses yang sudah pernah disimpan owner
     * tidak membuat menu baru disembunyikan/ditolak selamanya. Hanya jalan sekali per request.
     */
    private static function syncNewMenuKeys(string $role, int $userId): void {
        if (self::$syncedKeys || $role === 'owner') {
            self::$syncedKeys = true;
            return;
        }
        self::$syncedKeys = true;
        $allKeys   = array_keys(self::menus());
        $defaults  = self::defaultFor($role);
        try {
            $rp = new RolePermission();
            if ($rp->isConfigured($role)) {
                $rp->syncMissingKeys($role, $allKeys, $defaults);
            }
            if ($userId > 0) {
                $up = new UserPermission();
                if ($up->isConfigured($userId)) {
                    $up->syncMissingKeys($userId, $allKeys, $defaults);
                }
            }
            self::resetCache();
        } catch (\Throwable $e) {
            // tabel permission belum ada — abaikan
        }
    }

    /**
     * Key menu yang diizinkan untuk satu user spesifik dari tabel user_permissions.
     * Mengembalikan null bila user tidak punya override (pakai default role).
     */
    public static function userAllowed(int $userId): ?array {
        if (array_key_exists($userId, self::$userCache)) {
            return self::$userCache[$userId];
        }
        try {
            $up = new UserPermission();
            $keys = $up->isConfigured($userId) ? $up->allowedKeys($userId) : null;
        } catch (\Throwable $e) {
            $keys = null;
        }
        return self::$userCache[$userId] = $keys;
    }

    /**
     * Cek apakah request berjalan adalah AJAX / JSON request.
     */
    public static function isAjax(): bool {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    }

    /** Petakan URI ke key menu (null = rute publik / tanpa proteksi). */
    public static function keyForPath(string $uri): ?string {
        $uri = '/' . trim($uri, '/');

        // Rute utilitas & update sistem dikecualikan dari guard menu (diotorisasi oleh controller via requireAuth)
        if (
            $uri === '/admin/settings/check-update' ||
            $uri === '/admin/check-update' ||
            $uri === '/admin/settings/apply-update' ||
            $uri === '/admin/apply-update'
        ) {
            return null;
        }

        if (self::$pathIndex === null) {
            $index = [];
            foreach (self::menus() as $key => $menu) {
                foreach ($menu['paths'] ?? [] as $p) {
                    $index['/' . trim($p, '/')] = $key;
                }
            }
            // longest-prefix: path yang lebih spesifik didahulukan
            uksort($index, fn($a, $b) => strlen($b) <=> strlen($a));
            self::$pathIndex = $index;
        }
        foreach (self::$pathIndex as $prefix => $key) {
            if ($uri === $prefix || strpos($uri, $prefix . '/') === 0) {
                return $key;
            }
        }
        return null;
    }

    public static function resolveCurrentKey(string $uri): ?string {
        self::$currentKey = self::keyForPath($uri);
        return self::$currentKey;
    }

    /** Key menu untuk request berjalan (dipakai requireAuth sbg fallback). */
    public static function currentKey(): ?string {
        return self::$currentKey;
    }

    /** Bersihkan cache in-memory (dipanggil setelah simpan permission). */
    public static function resetCache(): void {
        self::$roleCache = [];
        self::$userCache = [];
    }

    /** Halaman awal yang aman untuk sebuah role (hindari redirect loop). */
    public static function landingFor(string $role): string {
        $menus = self::menus();
        $prefer = $role === 'cashier' ? 'pos' : 'dashboard';
        if (isset($menus[$prefer]) && self::can($prefer)) {
            return $menus[$prefer]['url'];
        }
        foreach ($menus as $key => $m) {
            if ($key !== 'permissions' && self::can($key)) {
                return $m['url'];
            }
        }
        return 'logout';
    }

    /** Guard setiap request pada route yang dilindungi menu. */
    public static function guardPath(string $uri): void {
        $key = self::resolveCurrentKey($uri);
        if ($key === null) {
            // Rute utilitas admin tanpa menu spesifik tetap membutuhkan login
            $cleanUri = '/' . trim($uri, '/');
            if (strpos($cleanUri, '/admin') === 0 && !isset($_SESSION['user'])) {
                if (self::isAjax()) {
                    http_response_code(401);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['error' => 'Sesi login telah berakhir. Silakan muat ulang halaman untuk login kembali.']);
                    exit;
                }
                $_SESSION['flash_error'] = 'Silakan login terlebih dahulu.';
                header('Location: ' . url('login'));
                exit;
            }
            return; // rute publik / tanpa proteksi menu
        }
        if (!isset($_SESSION['user'])) {
            if (self::isAjax()) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Sesi login telah berakhir. Silakan muat ulang halaman untuk login kembali.']);
                exit;
            }
            $_SESSION['flash_error'] = 'Silakan login terlebih dahulu.';
            header('Location: ' . url('login'));
            exit;
        }
        if (self::can($key)) {
            return;
        }
        if (self::isAjax()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Akses ditolak. Akun Anda tidak memiliki izin untuk fitur ini.']);
            exit;
        }
        $_SESSION['flash_error'] = 'Akses ditolak. Role Anda tidak diberi izin untuk menu ini.';
        header('Location: ' . url(self::landingFor($_SESSION['user']['role'])));
        exit;
    }
}