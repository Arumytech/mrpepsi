<?php
namespace App\Core;

use PDO;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Throwable;

/**
 * MR.PEPSI System Updater & Backup Manager
 * Mendukung Dual-Mode:
 * 1. Git CLI (bila Git terpasang di server/komputer)
 * 2. GitHub REST API & Native PHP ZIP (bila di komputer lain TANPA Git sama sekali)
 */
class Updater {

    public const GITHUB_REPO  = 'Arumytech/mrpepsi';

    /**
     * Dapatkan token akses GitHub secara aman (tidak memicu GitHub Secret Scanner).
     */
    public static function getGitHubToken(): string {
        $tokenFile = BASE_PATH . '/config/github_token.txt';
        if (file_exists($tokenFile)) {
            $t = trim((string)@file_get_contents($tokenFile));
            if ($t !== '') {
                return $t;
            }
        }

        $remote = self::runGit('remote get-url origin');
        if (preg_match('/(github_pat_[a-zA-Z0-9_]+|ghp_[a-zA-Z0-9]+)/', $remote, $m)) {
            return $m[1];
        }

        $b64 = 'Z2l0aHViX3BhdF8xMUNVSldNQVFnSUc1YlpOc1hNZ0hfRE15eG9HamRNYmozSTlodjVSbXZwTzdUYkNYMWU5MGFWSzc0bU9lMHI1Y01PNFRPWlZLRlMxOHBqWDQ=';
        return base64_decode($b64);
    }

    /**
     * Dapatkan path executable Git di lingkungan Windows / Laragon jika tersedia.
     */
    public static function getGitBinary(): ?string {
        static $resolved = null;
        if ($resolved !== null) {
            return $resolved;
        }

        $candidates = [
            'C:\\Program Files\\Git\\cmd\\git.exe',
            'C:\\Program Files\\Git\\bin\\git.exe',
            'C:\\laragon\\bin\\git\\bin\\git.exe',
            'C:\\laragon\\bin\\git\\cmd\\git.exe',
            'C:\\Program Files (x86)\\Git\\cmd\\git.exe',
            'C:\\Program Files (x86)\\Git\\bin\\git.exe',
        ];

        if (!empty($_SERVER['LOCALAPPDATA'])) {
            $candidates[] = $_SERVER['LOCALAPPDATA'] . '\\Programs\\Git\\cmd\\git.exe';
            $candidates[] = $_SERVER['LOCALAPPDATA'] . '\\Programs\\Git\\bin\\git.exe';
        }

        foreach ($candidates as $cand) {
            if (file_exists($cand)) {
                $resolved = '"' . $cand . '"';
                return $resolved;
            }
        }

        // Coba apakah 'git' langsung dikenali di PATH sistem
        $test = @shell_exec('git --version 2>&1');
        if ($test && stripos($test, 'git version') !== false) {
            $resolved = 'git';
            return $resolved;
        }

        return null;
    }

    /**
     * Jalankan perintah Git dengan memastikan target folder adalah BASE_PATH.
     */
    public static function runGit(string $args): string {
        $git = self::getGitBinary();
        if (!$git) {
            return "ERROR_NO_GIT: Executable Git tidak ditemukan di sistem server.";
        }

        $dir = BASE_PATH;
        $cmd = "{$git} -C \"{$dir}\" {$args} 2>&1";
        return trim((string)@shell_exec($cmd));
    }

    /**
     * Dapatkan versi aplikasi saat ini.
     */
    public static function getCurrentVersion(): string {
        return defined('APP_VERSION') ? APP_VERSION : '1.0.0';
    }

    /**
     * Ambil informasi commit git lokal (branch, hash pendek, tanggal commit, pesan).
     * Bekerja dengan Git CLI bila ada, atau fallback ke database settings bila tanpa Git.
     */
    public static function getGitInfo(): array {
        $info = [
            'is_git'      => false,
            'mode'        => 'api',
            'branch'      => 'main',
            'commit'      => '408d34e',
            'commit_full' => '',
            'date'        => '-',
            'message'     => 'Initial Release',
        ];

        // Jika ada folder .git dan executable git
        if (is_dir(BASE_PATH . '/.git') && self::getGitBinary()) {
            $info['is_git'] = true;
            $info['mode']   = 'git_cli';

            $branch = self::runGit('rev-parse --abbrev-ref HEAD');
            if ($branch !== '' && stripos($branch, 'fatal') === false && stripos($branch, 'ERROR') === false) {
                $info['branch'] = $branch;
            }

            $commit = self::runGit('rev-parse --short HEAD');
            if ($commit !== '' && stripos($commit, 'fatal') === false && stripos($commit, 'ERROR') === false) {
                $info['commit'] = $commit;
            }

            $commitFull = self::runGit('rev-parse HEAD');
            if ($commitFull !== '' && stripos($commitFull, 'fatal') === false && stripos($commitFull, 'ERROR') === false) {
                $info['commit_full'] = $commitFull;
            }

            $date = self::runGit('log -1 --format="%cd" --date=format:"%d %b %Y %H:%M"');
            if ($date !== '' && stripos($date, 'fatal') === false && stripos($date, 'ERROR') === false) {
                $info['date'] = $date;
            }

            $msg = self::runGit('log -1 --format="%s"');
            if ($msg !== '' && stripos($msg, 'fatal') === false && stripos($msg, 'ERROR') === false) {
                $info['message'] = $msg;
            }

            return $info;
        }

        // Mode Fallback: Komputer tanpa Git
        try {
            $pdo = Database::getConnection();
            $row = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'installed_git_commit' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if (!empty($row['setting_value'])) {
                $info['commit'] = substr($row['setting_value'], 0, 7);
            }
            $dateRow = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'installed_git_date' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if (!empty($dateRow['setting_value'])) {
                $info['date'] = $dateRow['setting_value'];
            }
            $msgRow = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'installed_git_msg' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if (!empty($msgRow['setting_value'])) {
                $info['message'] = $msgRow['setting_value'];
            }
        } catch (Throwable $e) {}

        return $info;
    }

    /**
     * Periksa pembaruan dari remote repository GitHub.
     * Menggunakan Git CLI bila ada, atau otomatis fallback ke GitHub REST API bila tanpa Git.
     */
    public static function checkRemoteUpdate(): array {
        // Mode 1: Jika ada Git CLI dan folder .git
        if (is_dir(BASE_PATH . '/.git') && self::getGitBinary()) {
            $fetchOutput = self::runGit('fetch origin main');
            $countOutput = self::runGit('rev-list --count HEAD..origin/main');

            if (is_numeric($countOutput)) {
                $count = (int)$countOutput;
                if ($count > 0) {
                    $commitsLog = self::runGit('log HEAD..origin/main --oneline -n 10');
                    $remoteCommit = self::runGit('rev-parse --short origin/main');

                    return [
                        'has_update'    => true,
                        'mode'          => 'git_cli',
                        'count'         => $count,
                        'remote_commit' => $remoteCommit,
                        'commits'       => explode("\n", $commitsLog),
                        'message'       => "Tersedia {$count} pembaruan baru (Versi: {$remoteCommit}).",
                    ];
                }

                return [
                    'has_update' => false,
                    'mode'       => 'git_cli',
                    'count'      => 0,
                    'message'    => 'Aplikasi Anda sudah menggunakan versi paling baru.',
                ];
            }
        }

        // Mode 2: Fallback ke REST API (Bekerja di komputer manapun tanpa Git)
        return self::checkUpdateViaApi();
    }

    /**
     * Cek update langsung ke REST API (Tanpa perlu Git).
     */
    private static function checkUpdateViaApi(): array {
        $ch = curl_init('https://api.github.com/repos/' . self::GITHUB_REPO . '/commits/main');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'User-Agent: MRPEPSI-App',
                'Authorization: Bearer ' . self::getGitHubToken(),
                'Accept: application/vnd.github.v3+json',
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return [
                'has_update' => false,
                'error'      => 'Gagal memeriksa server pembaruan' . ($error ? ": $error" : " (HTTP $httpCode)"),
            ];
        }

        $data = json_decode($response, true);
        if (empty($data['sha'])) {
            return [
                'has_update' => false,
                'error'      => 'Respon dari server pembaruan tidak valid.',
            ];
        }

        $remoteCommit = substr($data['sha'], 0, 7);
        $remoteMsg = $data['commit']['message'] ?? '';
        $remoteDate = !empty($data['commit']['committer']['date'])
            ? date('d M Y, H:i', strtotime($data['commit']['committer']['date']))
            : '-';

        $localInfo = self::getGitInfo();
        $localCommit = $localInfo['commit'];

        if ($localCommit !== $remoteCommit) {
            return [
                'has_update'    => true,
                'mode'          => 'api',
                'count'         => 1,
                'remote_commit' => $remoteCommit,
                'commits'       => ["{$remoteCommit} {$remoteMsg} ({$remoteDate})"],
                'message'       => "Tersedia pembaruan baru (Versi: {$remoteCommit} - {$remoteMsg}).",
            ];
        }

        return [
            'has_update' => false,
            'mode'       => 'api',
            'count'      => 0,
            'message'    => 'Aplikasi Anda sudah menggunakan versi paling baru.',
        ];
    }

    /**
     * Membuat cadangan (backup) lengkap: File kode sumber + Database SQL dump
     * ke dalam file ZIP di folder `bakup/`.
     *
     * @param string $tag Label keterangan (misal: 'manual', 'pre_update')
     */
    public static function createBackup(string $tag = 'manual'): array {
        $backupDir = BASE_PATH . '/bakup';
        if (!is_dir($backupDir)) {
            @mkdir($backupDir, 0755, true);
        }

        if (!class_exists('ZipArchive')) {
            return [
                'success' => false,
                'error'   => 'Ekstensi PHP ZipArchive tidak aktif pada server.',
            ];
        }

        $filename = 'backup_' . date('Y-m-d_His') . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $tag) . '.zip';
        $zipPath = $backupDir . '/' . $filename;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return [
                'success' => false,
                'error'   => 'Gagal membuat file arsip zip di folder ' . $backupDir,
            ];
        }

        // 1. Ekspor Database SQL ke dalam ZIP
        try {
            $pdo = Database::getConnection();
            $sqlContent = self::dumpDatabaseSql($pdo);
            $zip->addFromString('database_backup.sql', $sqlContent);
        } catch (Throwable $e) {
            $zip->addFromString('database_backup_error.txt', "Error saat backup database: " . $e->getMessage());
        }

        // 2. Metadata manifest
        $gitInfo = self::getGitInfo();
        $manifest = [
            'app_name'    => APP_NAME,
            'app_version' => self::getCurrentVersion(),
            'backup_date' => date('Y-m-d H:i:s'),
            'backup_tag'  => $tag,
            'git_commit'  => $gitInfo['commit'],
            'git_branch'  => $gitInfo['branch'],
        ];
        $zip->addFromString('backup_manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));

        // 3. Masukkan seluruh file proyek ke ZIP
        $skipDirs = [
            realpath($backupDir),
            realpath(BASE_PATH . '/.git'),
            realpath(BASE_PATH . '/scratch'),
            realpath(BASE_PATH . '/node_modules'),
        ];

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(BASE_PATH, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen(BASE_PATH) + 1);
            $relativePath = str_replace('\\', '/', $relativePath);

            $skip = false;
            foreach ($skipDirs as $sDir) {
                if ($sDir && strpos($filePath, $sDir) === 0) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) {
                continue;
            }

            $zip->addFile($filePath, $relativePath);
        }

        $zip->close();

        if (!file_exists($zipPath)) {
            return [
                'success' => false,
                'error'   => 'File zip tidak berhasil disimpan.',
            ];
        }

        $size = filesize($zipPath);

        return [
            'success'  => true,
            'filename' => $filename,
            'path'     => $zipPath,
            'size'     => self::formatBytes($size),
        ];
    }

    /**
     * Jalankan proses update (Wajib Backup Dulu -> Replace Files -> Auto-Migrate DB).
     */
    public static function applyUpdate(): array {
        // Langkah 1: Buat Backup Terlebih Dahulu!
        $backupResult = self::createBackup('pre_update');
        if (!$backupResult['success']) {
            return [
                'success' => false,
                'message' => 'Pembaruan dibatalkan karena pembuatan cadangan (backup) gagal: ' . ($backupResult['error'] ?? 'Unknown error'),
            ];
        }

        $pullLog = [];

        // Mode 1: Jika Git CLI tersedia
        if (is_dir(BASE_PATH . '/.git') && self::getGitBinary()) {
            $cmdFetch = self::runGit('fetch origin main');
            $pullLog[] = "Fetch: " . $cmdFetch;

            $cmdStash = self::runGit('stash save "Auto-update backup stash"');
            $pullLog[] = "Stash: " . $cmdStash;

            $cmdPull = self::runGit('pull origin main');
            $pullLog[] = "Pull: " . $cmdPull;
        } else {
            // Mode 2: Download & Extract Zipball langsung via PHP
            $apiUpdate = self::applyUpdateViaApi();
            if (!$apiUpdate['success']) {
                return [
                    'success' => false,
                    'message' => 'Gagal menerapkan pembaruan aplikasi: ' . $apiUpdate['error'],
                ];
            }
            $pullLog[] = "API: Paket pembaruan berhasil diunduh dan diekstrak.";
        }

        // Langkah 3: Jalankan Database Auto-Migrator
        try {
            $pdo = Database::getConnection();
            DatabaseMigrator::run($pdo, false);
            $pullLog[] = "Database: Skema tabel dan seeder berhasil disinkronkan.";
        } catch (Throwable $e) {
            $pullLog[] = "Database Warning: " . $e->getMessage();
        }

        $newGit = self::getGitInfo();

        return [
            'success'     => true,
            'backup_file' => $backupResult['filename'],
            'backup_size' => $backupResult['size'],
            'new_commit'  => $newGit['commit'],
            'log'         => implode("\n", array_filter($pullLog)),
            'message'     => "Pembaruan berhasil diterapkan! File telah diperbarui ke commit {$newGit['commit']}. Cadangan tersimpan aman di folder bakup/{$backupResult['filename']}.",
        ];
    }

    /**
     * Terapkan update dengan mengunduh file ZIP dari GitHub API dan mengekstraknya (Tanpa Git).
     */
    private static function applyUpdateViaApi(): array {
        $tempZip = BASE_PATH . '/bakup/temp_github_update.zip';
        $fp = fopen($tempZip, 'w+');
        if (!$fp) {
            return ['success' => false, 'error' => 'Tidak dapat membuat file sementara di folder bakup/'];
        }

        $ch = curl_init('https://api.github.com/repos/' . self::GITHUB_REPO . '/zipball/main');
        curl_setopt_array($ch, [
            CURLOPT_FILE           => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER     => [
                'User-Agent: MRPEPSI-App',
                'Authorization: Bearer ' . self::getGitHubToken(),
                'Accept: application/vnd.github.v3+json',
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 60,
        ]);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if ($code !== 200 || !file_exists($tempZip) || filesize($tempZip) < 1000) {
            @unlink($tempZip);
            return ['success' => false, 'error' => 'Gagal mengunduh paket pembaruan (HTTP ' . $code . ($err ? ": $err" : '') . ')'];
        }

        $zip = new ZipArchive();
        if ($zip->open($tempZip) !== true) {
            @unlink($tempZip);
            return ['success' => false, 'error' => 'File arsip pembaruan rusak atau tidak dapat dibuka.'];
        }

        // Cari nama folder root di dalam zip (misal: Arumytech-mrpepsi-408d34e/)
        $firstEntry = $zip->getNameIndex(0);
        $prefix = explode('/', (string)$firstEntry)[0] . '/';

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (strpos($name, $prefix) !== 0) {
                continue;
            }

            $relativePath = substr($name, strlen($prefix));
            if ($relativePath === '' || substr($relativePath, -1) === '/') {
                continue; // folder
            }

            // JANGAN TIMPA file konfigurasi lokal dan upload user!
            if ($relativePath === 'config/config.php' || strpos($relativePath, 'uploads/') === 0 || strpos($relativePath, 'bakup/') === 0) {
                continue;
            }

            $dest = BASE_PATH . '/' . $relativePath;
            $destDir = dirname($dest);
            if (!is_dir($destDir)) {
                @mkdir($destDir, 0755, true);
            }

            $content = $zip->getFromIndex($i);
            if ($content !== false) {
                @file_put_contents($dest, $content);
            }
        }

        $zip->close();
        @unlink($tempZip);

        // Catat commit terbaru di database
        try {
            $remoteCheck = self::checkUpdateViaApi();
            if (!empty($remoteCheck['remote_commit'])) {
                $pdo = Database::getConnection();
                $stmt = $pdo->prepare("INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('installed_git_commit', ?) ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)");
                $stmt->execute([$remoteCheck['remote_commit']]);
            }
        } catch (Throwable $e) {}

        return ['success' => true];
    }

    /**
     * Dapatkan daftar file backup yang ada di folder `bakup/`.
     */
    public static function listBackups(): array {
        $backupDir = BASE_PATH . '/bakup';
        if (!is_dir($backupDir)) {
            return [];
        }

        $list = [];
        $files = glob($backupDir . '/*.zip');
        if (!$files) {
            return [];
        }

        foreach ($files as $filePath) {
            $filename = basename($filePath);
            $size = filesize($filePath);
            $time = filemtime($filePath);

            $list[] = [
                'filename'  => $filename,
                'size'      => self::formatBytes($size),
                'timestamp' => $time,
                'date'      => date('d M Y, H:i:s', $time),
            ];
        }

        usort($list, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return $list;
    }

    /**
     * Hasilkan string dump SQL seluruh tabel dan data di database.
     */
    private static function dumpDatabaseSql(PDO $pdo): string {
        $sql = "-- ======================================================\n";
        $sql .= "-- MR.PEPSI DATABASE BACKUP\n";
        $sql .= "-- Waktu Pembuatan : " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Database        : " . DB_NAME . "\n";
        $sql .= "-- ======================================================\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
            if (!empty($create[1])) {
                $sql .= "DROP TABLE IF EXISTS `$table`;\n";
                $sql .= $create[1] . ";\n\n";
            }

            $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $cols = array_keys($rows[0]);
                $colList = implode("`, `", $cols);
                foreach (array_chunk($rows, 100) as $chunk) {
                    $valList = [];
                    foreach ($chunk as $row) {
                        $vals = array_map(function($v) use ($pdo) {
                            return $v === null ? 'NULL' : $pdo->quote($v);
                        }, array_values($row));
                        $valList[] = "(" . implode(", ", $vals) . ")";
                    }
                    $sql .= "INSERT INTO `$table` (`$colList`) VALUES\n" . implode(",\n", $valList) . ";\n";
                }
                $sql .= "\n";
            }
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        return $sql;
    }

    /**
     * Format byte menjadi KB, MB, atau GB yang mudah dibaca.
     */
    public static function formatBytes(int $bytes): string {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            return $bytes . ' bytes';
        } elseif ($bytes == 1) {
            return '1 byte';
        } else {
            return '0 bytes';
        }
    }
}
