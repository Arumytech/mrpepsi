<?php
/**
 * Database Migration & Seeder for MR.PEPSI UPGRADE
 * Native Clean MVC System
 *
 * Catatan: Mulai sekarang, migrasi & seeder ini sudah berjalan OTOMATIS
 * saat aplikasi pertama kali dibuka di browser. File ini tetap disediakan
 * untuk eksekusi manual via browser atau CLI jika diinginkan.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Core/Database.php';
require_once __DIR__ . '/app/Core/DatabaseMigrator.php';

use App\Core\Database;
use App\Core\DatabaseMigrator;

$isCli = (php_sapi_name() === 'cli');

if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
}

echo "=== MR.PEPSI DATABASE INSTALLER & MIGRATOR ===\n";
echo "Target Database: " . DB_NAME . " (" . DB_HOST . ":" . DB_PORT . ")\n\n";

try {
    $pdo = Database::getConnection();
    echo "Connected to database successfully.\n";

    DatabaseMigrator::run($pdo, true);

    echo "\n=== MIGRATION AND SEEDING COMPLETED SUCCESSFULLY! ===\n";
} catch (Throwable $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
    exit(1);
}
