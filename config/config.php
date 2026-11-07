<?php

/**
 * Application Configuration
 * Native PHP Clean MVC - Tanpa .env
 */

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Database Settings
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'mrpepsi_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// define('DB_PORT', '3306');
// define('DB_NAME', 'aino_mrpepsi');
// define('DB_USER', 'aino_mrpepsi');
// define('DB_PASS', 'aino_mrpepsi@2026');

// App Info
define('APP_NAME', 'MR.PEPSI UPGRADE');
define('APP_TAGLINE', 'Kursus Speaking & IELTS di Kampung Inggris Pare');
define('ADMIN_PHONE', '6285648077008');

// Base URL detection
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$baseUrl = rtrim($protocol . $host . $scriptDir, '/');
define('BASE_URL', $baseUrl);

// Path dasar aplikasi & folder upload (web accessible, tanpa .env)
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_DIR', BASE_PATH . '/uploads');
define('UPLOAD_URL', 'uploads');
define('LOGO_MAX_SIZE', 3 * 1024 * 1024); // 3 MB

// Helpers
function url($path = '')
{
    $path = ltrim($path, '/');
    return BASE_URL . '/' . $path;
}

function asset($path = '')
{
    $path = ltrim($path, '/');
    return BASE_URL . '/assets/' . $path;
}

// Token perangkat (persisten) untuk penegakan 1 user = 1 device.
// Berbeda dari PHPSESSID yang hilang saat browser ditutup, cookie ini
// bertahan sehingga user yang menutup browser bisa login lagi di device
// yang sama tanpa dianggap "login di tempat lain".
function device_cookie_name(): string
{
    return 'mrpepsi_device';
}

function device_set_cookie(string $token): void
{
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? 80) == 443);
    if (!headers_sent()) {
        setcookie(device_cookie_name(), $token, [
            'expires'  => time() + 60 * 60 * 24 * 365,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    $_COOKIE[device_cookie_name()] = $token;
}

function device_token(bool $create = true): ?string
{
    $name = device_cookie_name();
    if (!empty($_COOKIE[$name]) && preg_match('/^[a-f0-9]{32,64}$/', (string)$_COOKIE[$name])) {
        return $_COOKIE[$name];
    }
    if (!$create) {
        return null;
    }
    $token = bin2hex(random_bytes(32));
    device_set_cookie($token);
    return $token;
}

function device_clear_cookie(): void
{
    if (!headers_sent()) {
        setcookie(device_cookie_name(), '', [
            'expires'  => time() - 3600,
            'path'     => '/',
        ]);
    }
    unset($_COOKIE[device_cookie_name()]);
}

/**
 * URL logo lembaga dari Pengaturan Umum (upload admin).
 * Mengembalikan null jika belum ada atau file-nya sudah tidak ada.
 */
function logo_url(): ?string
{
    static $done = false;
    static $resolved = null;

    if ($done) {
        return $resolved;
    }
    $done = true;
    $resolved = null;

    $path = '';
    try {
        if (class_exists(\App\Models\Setting::class)) {
            $path = trim((new \App\Models\Setting())->get('logo_path', ''));
        }
    } catch (\Throwable $e) {
        $path = '';
    }

    if ($path === '') {
        return null;
    }
    if (preg_match('#^https?://#i', $path)) {
        $resolved = $path;
        return $resolved;
    }

    $relative = ltrim(str_replace('\\', '/', $path), '/');
    if (!is_file(BASE_PATH . '/' . $relative)) {
        return null;
    }

    $resolved = BASE_URL . '/' . $relative;
    return $resolved;
}

/**
 * Tag <img> logo lembaga, atau string kosong jika belum ada logo.
 */
function logo_img(string $class = 'w-10 h-10 object-contain'): string
{
    $logo = logo_url();
    if (!$logo) {
        return '';
    }
    return '<img src="' . htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') . '" alt="'
        . htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') . '" class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '">';
}

function formatRupiah($number)
{
    return 'Rp ' . number_format($number, 0, ',', '.');
}

/**
 * Tanggal dalam Bahasa Indonesia, contoh: 10 Oktober 2026
 */
function tanggalIndo($date, string $format = 'd M Y'): string
{
    if (!$date) {
        return '-';
    }
    $ts = strtotime($date);
    if ($ts === false) {
        return (string)$date;
    }
    $bulan = [
        1 => 'Jan',
        'Feb',
        'Mar',
        'Apr',
        'Mei',
        'Jun',
        'Jul',
        'Agu',
        'Sep',
        'Okt',
        'Nov',
        'Des'
    ];
    $bulanPanjang = [
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];

    $out = str_replace(
        ['d', 'M', 'F', 'Y'],
        [
            date('d', $ts),
            $bulan[(int)date('n', $ts)],
            $bulanPanjang[(int)date('n', $ts)],
            date('Y', $ts)
        ],
        $format
    );
    return $out;
}

function sanitize($data)
{
    return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Cek apakah role yang sedang login boleh mengakses suatu menu/permission key.
 * (Owner selalu boleh; admin & kasir mengikuti matrik di Kelola Hak Akses.)
 */
function can(string $key): bool
{
    return \App\Core\Permission::can($key);
}

/**
 * Inisial nama produk (maksimal 2 huruf) untuk placeholder foto.
 */
function product_initials($name): string
{
    $words = preg_split('/\s+/', trim((string)$name));
    $initials = '';
    foreach ($words as $word) {
        if ($word === '') {
            continue;
        }
        $first = function_exists('mb_substr') ? mb_substr($word, 0, 1) : substr($word, 0, 1);
        $initials .= strtoupper($first);
        if (function_exists('mb_strlen') ? mb_strlen($initials) >= 2 : strlen($initials) >= 2) {
            break;
        }
    }
    return $initials !== '' ? $initials : 'PR';
}

/**
 * Kelas gradien Tailwind yang konsisten untuk tiap produk (placeholder foto).
 */
function product_gradient($product): string
{
    $gradients = [
        'from-sky-500 to-indigo-600',
        'from-emerald-500 to-teal-600',
        'from-amber-500 to-orange-600',
        'from-rose-500 to-pink-600',
        'from-violet-500 to-purple-600',
        'from-cyan-500 to-blue-600',
        'from-lime-500 to-emerald-600',
        'from-fuchsia-500 to-purple-600'
    ];

    $id = (int)($product['id'] ?? 0);
    if ($id <= 0) {
        $id = (int)crc32((string)($product['name'] ?? 'produk'));
    }

    return $gradients[$id % count($gradients)];
}

/**
 * URL foto produk. Mengembalikan null jika belum ada atau file sudah tidak ada.
 */
function product_photo_url($path): ?string
{
    $path = trim((string)$path);
    if ($path === '') {
        return null;
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    $relative = ltrim(str_replace('\\', '/', $path), '/');
    if (!is_file(BASE_PATH . '/' . $relative)) {
        return null;
    }

    return BASE_URL . '/' . $relative;
}

/**
 * Tag gambar produk: foto asli jika ada, placeholder gradien + inisial jika belum.
 */
function product_photo_html(array $product, string $imgClass = 'w-full h-full object-cover', string $textClass = 'text-xl font-black'): string
{
    $url = product_photo_url($product['image'] ?? null);
    if ($url !== null) {
        return '<img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" alt="" class="'
            . htmlspecialchars($imgClass, ENT_QUOTES, 'UTF-8') . '" loading="lazy">';
    }

    return '<div class="w-full h-full grid place-items-center bg-gradient-to-br '
        . product_gradient($product) . ' text-white select-none">'
        . '<span class="' . htmlspecialchars($textClass, ENT_QUOTES, 'UTF-8')
        . ' drop-shadow-sm">' . htmlspecialchars(product_initials($product['name'] ?? ''), ENT_QUOTES, 'UTF-8')
        . '</span></div>';
}

/**
 * Konversi nominal angka ke kata terbilang Rupiah Bahasa Indonesia.
 */
function terbilang($angka): string
{
    $angka = abs((float)$angka);
    $baca = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
    $hasil = '';

    if ($angka < 12) {
        $hasil = ' ' . $baca[(int)$angka];
    } elseif ($angka < 20) {
        $hasil = terbilang($angka - 10) . ' Belas';
    } elseif ($angka < 100) {
        $hasil = terbilang(floor($angka / 10)) . ' Puluh ' . terbilang((int)$angka % 10);
    } elseif ($angka < 200) {
        $hasil = ' Seratus ' . terbilang($angka - 100);
    } elseif ($angka < 1000) {
        $hasil = terbilang(floor($angka / 100)) . ' Ratus ' . terbilang((int)$angka % 100);
    } elseif ($angka < 2000) {
        $hasil = ' Seribu ' . terbilang($angka - 1000);
    } elseif ($angka < 1000000) {
        $hasil = terbilang(floor($angka / 1000)) . ' Ribu ' . terbilang(fmod($angka, 1000));
    } elseif ($angka < 1000000000) {
        $hasil = terbilang(floor($angka / 1000000)) . ' Juta ' . terbilang(fmod($angka, 1000000));
    } elseif ($angka < 1000000000000) {
        $hasil = terbilang(floor($angka / 1000000000)) . ' Miliar ' . terbilang(fmod($angka, 1000000000));
    } else {
        $hasil = terbilang(floor($angka / 1000000000000)) . ' Triliun ' . terbilang(fmod($angka, 1000000000000));
    }
    return trim(preg_replace('/\s+/', ' ', $hasil));
}

/**
 * Format string YYYY-MM (misal '2026-10') ke 'Oktober 2026'
 */
function formatBulanTahun(?string $ym): string
{
    if (!$ym) {
        return '-';
    }
    $parts = explode('-', trim($ym));
    if (count($parts) !== 2) {
        return (string)$ym;
    }
    $bulanIndo = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    $m = (int)$parts[1];
    $y = $parts[0];
    return ($bulanIndo[$m] ?? $parts[1]) . ' ' . $y;
}


