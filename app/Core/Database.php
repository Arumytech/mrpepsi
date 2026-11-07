<?php
namespace App\Core;

use PDO;
use PDOException;
use Throwable;

require_once __DIR__ . '/DatabaseMigrator.php';

class Database {
    private static ?PDO $instance = null;
    private static bool $checked = false;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                // Jika database belum dibuat (error 1049 / unknown database), buat otomatis!
                if ($e->getCode() == 1049 || stripos($e->getMessage(), 'Unknown database') !== false) {
                    self::$instance = self::createDatabaseAndConnect();
                } else {
                    die("Koneksi Database Gagal: " . $e->getMessage());
                }
            }

            // Jalankan migrasi & seeder otomatis jika diperlukan (hanya 1x per proses PHP)
            if (!self::$checked) {
                self::$checked = true;
                DatabaseMigrator::autoMigrateIfNeeded(self::$instance);
            }
        }
        return self::$instance;
    }

    /**
     * Membuat database di MySQL server jika belum ada, lalu mengembalikan koneksi PDO.
     */
    private static function createDatabaseAndConnect(): PDO {
        try {
            $rootDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
            $serverPdo = new PDO($rootDsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $dbname = DB_NAME;
            $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            unset($serverPdo);

            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            return new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (Throwable $ex) {
            die("Gagal membuat/menghubungkan ke database: " . $ex->getMessage());
        }
    }
}
