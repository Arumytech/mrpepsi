<?php
/**
 * MR.PEPSI UPGRADE - Front Controller
 * Native PHP Clean MVC Architecture (Tanpa .env)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load Configuration
require_once __DIR__ . '/config/config.php';

// PSR-4 Style Autoloader for App\* namespace
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\ProgramController;
use App\Controllers\Admin\TutorController;
use App\Controllers\Admin\StaffOfficeController;
use App\Controllers\Admin\RoomController;
use App\Controllers\Admin\PeriodController;
use App\Controllers\Admin\ScheduleController;
use App\Controllers\Admin\AttendanceController;
use App\Controllers\Admin\PayrollController;
use App\Controllers\Admin\CanteenController;
use App\Controllers\Admin\SettingController;
use App\Controllers\Admin\RegistrationController;
use App\Controllers\Admin\StudentController;
use App\Controllers\Admin\ReportController;
use App\Controllers\Admin\ExpenseController;
use App\Controllers\Admin\UserController;
use App\Controllers\Admin\PermissionController;
use App\Controllers\Admin\SessionController;
use App\Controllers\Admin\DatabaseController;
use App\Controllers\Admin\ManualSaleController;
use App\Controllers\Pos\PosController;
use App\Controllers\Pos\ShiftController;

$router = new Router();

// Guard hak akses global: cek permission menu sebelum route dieksekusi
$router->setGuard(fn(string $uri) => \App\Core\Permission::guardPath($uri));

// ==========================================
// 1. RUTE PUBLIK (Guest / Calon Siswa)
// ==========================================
$router->get('/', function () {
    header('Location: ' . url('login'));
    exit;
});
$router->get('/home', [HomeController::class, 'index']);
$router->post('/register', [HomeController::class, 'register']);
$router->get('/api/program/{id}', [HomeController::class, 'getProgramDetail']);
$router->get('/api/check-repeat-program', [RegistrationController::class, 'checkRepeatProgram']);
$router->post('/api/check-repeat-program', [RegistrationController::class, 'checkRepeatProgram']);

// ==========================================
// RUTE AUTHENTICATION (Login / Logout)
// ==========================================
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);

// ==========================================
// 2. RUTE ADMIN UTAMA
// ==========================================
// Dashboard
$router->get('/admin', [DashboardController::class, 'index']);
$router->get('/admin/dashboard', [DashboardController::class, 'index']);

// Kelola Program & Harga
$router->get('/admin/programs', [ProgramController::class, 'index']);
$router->post('/admin/programs/store', [ProgramController::class, 'store']);
$router->post('/admin/programs/update/{id}', [ProgramController::class, 'update']);
$router->post('/admin/programs/delete/{id}', [ProgramController::class, 'delete']);
$router->post('/admin/programs/store-category', [ProgramController::class, 'storeCategory']);
$router->post('/admin/programs/update-category/{id}', [ProgramController::class, 'updateCategory']);
$router->post('/admin/programs/delete-category/{id}', [ProgramController::class, 'deleteCategory']);

// Master Tutor
$router->get('/admin/tutors', [TutorController::class, 'index']);
$router->post('/admin/tutors/store', [TutorController::class, 'store']);
$router->post('/admin/tutors/update/{id}', [TutorController::class, 'update']);
$router->post('/admin/tutors/delete/{id}', [TutorController::class, 'delete']);

// Master Staff Office
$router->get('/admin/staff', [StaffOfficeController::class, 'index']);
$router->post('/admin/staff/store', [StaffOfficeController::class, 'store']);
$router->post('/admin/staff/update/{id}', [StaffOfficeController::class, 'update']);
$router->post('/admin/staff/delete/{id}', [StaffOfficeController::class, 'delete']);

// Master Ruangan
$router->get('/admin/rooms', [RoomController::class, 'index']);
$router->get('/admin/rooms/detail/{id}', [RoomController::class, 'detail']);
$router->post('/admin/rooms/store', [RoomController::class, 'store']);
$router->post('/admin/rooms/update/{id}', [RoomController::class, 'update']);
$router->post('/admin/rooms/delete/{id}', [RoomController::class, 'delete']);
$router->post('/admin/rooms/inventory/store', [RoomController::class, 'storeItem']);
$router->post('/admin/rooms/inventory/update/{id}', [RoomController::class, 'updateItem']);
$router->post('/admin/rooms/inventory/delete/{id}', [RoomController::class, 'deleteItem']);

// Master Periode
$router->get('/admin/periods', [PeriodController::class, 'index']);
$router->post('/admin/periods/store', [PeriodController::class, 'store']);
$router->post('/admin/periods/update/{id}', [PeriodController::class, 'update']);
$router->get('/admin/periods/activate/{id}', [PeriodController::class, 'activate']);

// Schedule Builder (Matriks & Conflict Prevention)
$router->get('/admin/schedules', [ScheduleController::class, 'index']);
$router->post('/admin/schedules/store', [ScheduleController::class, 'store']);
$router->post('/admin/schedules/update/{id}', [ScheduleController::class, 'update']);
$router->post('/admin/schedules/delete/{id}', [ScheduleController::class, 'delete']);
$router->get('/admin/schedules/check-conflict', [ScheduleController::class, 'checkConflict']);
$router->get('/admin/schedules/print-daily', [ScheduleController::class, 'printDaily']);

// Presensi & Tutor Pengganti (Substitusi)
$router->get('/admin/attendance', [AttendanceController::class, 'index']);
$router->post('/admin/attendance/save-bulk', [AttendanceController::class, 'saveBulk']);
$router->post('/admin/attendance/save', [AttendanceController::class, 'save']);
$router->post('/admin/attendance/delete', [AttendanceController::class, 'delete']);

// Cetak Lembar Presensi Kelas & Nilai Ujian Siswa
$router->get('/admin/attendance/class-sheet', [AttendanceController::class, 'classSheet']);
$router->get('/admin/attendance/print-class-sheet', [AttendanceController::class, 'printClassSheet']);

// Penggajian & Slip Gaji (Payroll) - Tutor & Karyawan
$router->get('/admin/payroll', [PayrollController::class, 'index']);
$router->post('/admin/payroll/store', [PayrollController::class, 'store']);
$router->post('/admin/payroll/update/{id}', [PayrollController::class, 'update']);
$router->post('/admin/payroll/delete/{id}', [PayrollController::class, 'delete']);
$router->get('/admin/payroll/report', [PayrollController::class, 'report']);
$router->get('/admin/payroll/slip/{id}', [PayrollController::class, 'slip']);

// Pendaftaran Siswa (Guest & Admin + Approval)
$router->get('/admin/registrations', [RegistrationController::class, 'index']);
$router->post('/admin/registrations/store', [RegistrationController::class, 'store']);
$router->post('/admin/registrations/update/{id}', [RegistrationController::class, 'update']);
$router->post('/admin/registrations/approve/{id}', [RegistrationController::class, 'approve']);
$router->post('/admin/registrations/reject/{id}', [RegistrationController::class, 'reject']);
$router->get('/admin/registrations/proof/{id}', [RegistrationController::class, 'proof']);
$router->post('/admin/registrations/delete/{id}', [RegistrationController::class, 'delete']);

// Master Data Siswa Aktif & Lulusan
$router->get('/admin/students', [StudentController::class, 'index']);
$router->post('/admin/students/store', [StudentController::class, 'store']);
$router->post('/admin/students/update/{id}', [StudentController::class, 'update']);
$router->post('/admin/students/pay/{id}', [StudentController::class, 'pay']);
$router->post('/admin/students/delete/{id}', [StudentController::class, 'delete']);
$router->get('/admin/students/graduates', [StudentController::class, 'graduates']);
$router->post('/admin/students/issue-cert', [StudentController::class, 'issueCert']);
$router->get('/admin/students/certificate/{id}', [StudentController::class, 'certificate']);
$router->get('/admin/students/receipt/{id}', [StudentController::class, 'receipt']);
$router->post('/admin/students/delete-grad/{id}', [StudentController::class, 'deleteGrad']);

// Laporan Cetak (Print) — Data Siswa & Keuangan Kursus
$router->get('/admin/reports/students', [ReportController::class, 'students']);
$router->get('/admin/reports/finance', [ReportController::class, 'finance']);
$router->get('/admin/reports/combined', [ReportController::class, 'combined']);
$router->get('/admin/reports/canteen', [ReportController::class, 'canteen']);
$router->get('/admin/reports/canteen-monthly', [ReportController::class, 'canteenMonthly']);

// Manajemen Kantin & Stok
$router->get('/admin/canteen', [CanteenController::class, 'index']);
$router->post('/admin/canteen/store-product', [CanteenController::class, 'storeProduct']);
$router->post('/admin/canteen/update-product/{id}', [CanteenController::class, 'updateProduct']);
$router->post('/admin/canteen/delete-product/{id}', [CanteenController::class, 'deleteProduct']);
$router->post('/admin/canteen/store-category', [CanteenController::class, 'storeCategory']);
$router->post('/admin/canteen/update-category/{id}', [CanteenController::class, 'updateCategory']);
$router->post('/admin/canteen/delete-category/{id}', [CanteenController::class, 'deleteCategory']);
$router->get('/admin/canteen/opname', [CanteenController::class, 'showOpname']);
$router->post('/admin/canteen/opname', [CanteenController::class, 'opname']);
$router->post('/admin/canteen/restock', [CanteenController::class, 'restock']);
$router->get('/admin/canteen/report', [CanteenController::class, 'report']);
$router->get('/admin/canteen/report/pdf', [CanteenController::class, 'reportPdf']);
$router->get('/admin/canteen/monthly-report', [CanteenController::class, 'monthlyReport']);
$router->post('/admin/canteen/monthly-report/set-balance', [CanteenController::class, 'setMonthlyBalance']);
$router->get('/admin/canteen/monthly-report/pdf', [CanteenController::class, 'monthlyReportPdf']);
$router->get('/admin/canteen/barcode', [CanteenController::class, 'barcode']);
$router->get('/admin/canteen/transactions', [CanteenController::class, 'transactions']);
$router->post('/admin/canteen/transactions/update/{id}', [CanteenController::class, 'updateTransaction']);
$router->post('/admin/canteen/transactions/delete/{id}', [CanteenController::class, 'deleteTransaction']);
$router->get('/admin/canteen/daily-report', [CanteenController::class, 'dailyReport']);

// Pengeluaran Operasional
$router->get('/admin/expenses', [ExpenseController::class, 'index']);
$router->post('/admin/expenses/store', [ExpenseController::class, 'store']);
$router->post('/admin/expenses/update/{id}', [ExpenseController::class, 'update']);
$router->post('/admin/expenses/delete/{id}', [ExpenseController::class, 'delete']);
$router->post('/admin/expenses/store-category', [ExpenseController::class, 'storeCategory']);
$router->post('/admin/expenses/update-category/{id}', [ExpenseController::class, 'updateCategory']);
$router->post('/admin/expenses/delete-category/{id}', [ExpenseController::class, 'deleteCategory']);
$router->get('/admin/expenses/report', [ExpenseController::class, 'report']);
$router->get('/admin/expenses/report/pdf', [ExpenseController::class, 'reportPdf']);

// Pengaturan Umum & Pembaruan Sistem
$router->get('/admin/settings', [SettingController::class, 'index']);
$router->post('/admin/settings/update', [SettingController::class, 'update']);
$router->post('/admin/settings/pos-close', [SettingController::class, 'posClose']);
$router->post('/admin/settings/pos-open', [SettingController::class, 'posOpen']);
$router->get('/admin/pos-control', [SettingController::class, 'posControl']);
$router->get('/admin/settings/check-update', [SettingController::class, 'checkUpdate']);
$router->post('/admin/settings/check-update', [SettingController::class, 'checkUpdate']);
$router->post('/admin/settings/apply-update', [SettingController::class, 'applyUpdate']);
$router->post('/admin/settings/backup', [SettingController::class, 'createBackup']);
$router->get('/admin/settings/download-backup/{file}', [SettingController::class, 'downloadBackup']);

// Manajemen User & Akun (Admin / Kasir)
$router->get('/admin/users', [UserController::class, 'index']);
$router->post('/admin/users/store', [UserController::class, 'store']);
$router->post('/admin/users/update/{id}', [UserController::class, 'update']);
$router->post('/admin/users/delete/{id}', [UserController::class, 'delete']);
$router->post('/admin/users/permissions/{id}', [UserController::class, 'savePermissions']);
$router->post('/admin/users/permissions/{id}/reset', [UserController::class, 'resetPermissions']);

// Hak Akses & Menu (Matrik Checkbox - khusus Owner)
$router->get('/admin/permissions', [PermissionController::class, 'index']);
$router->post('/admin/permissions/save', [PermissionController::class, 'save']);

// Release Sesi Login (Reset status login akun terkunci)
$router->get('/admin/sessions', [SessionController::class, 'index']);
$router->post('/admin/sessions/release/{id}', [SessionController::class, 'release']);
$router->post('/admin/sessions/release-all', [SessionController::class, 'releaseAll']);

// Bersihkan Database (dengan verifikasi password admin)
$router->get('/admin/database', [DatabaseController::class, 'index']);
$router->post('/admin/database/clean', [DatabaseController::class, 'clean']);

// Input Penjualan Tambahan Manual (khusus Admin, setelah kasir tutup POS)
$router->get('/admin/manual-sales', [ManualSaleController::class, 'index']);
$router->post('/admin/manual-sales/store', [ManualSaleController::class, 'store']);
$router->post('/admin/manual-sales/delete/{id}', [ManualSaleController::class, 'delete']);

// ==========================================
// 3. RUTE KASIR KANTIN (POS OPERASIONAL)
// ==========================================
$router->get('/pos', [PosController::class, 'index']);
$router->post('/pos/checkout', [PosController::class, 'processCheckout']);
$router->post('/pos/close-day', [PosController::class, 'closeDay']);
$router->get('/pos/receipt/{id}', [PosController::class, 'receipt']);
$router->get('/pos/shift', [ShiftController::class, 'index']);
$router->post('/pos/shift/open', [ShiftController::class, 'open']);
$router->post('/pos/shift/close', [ShiftController::class, 'close']);

// Dispatch Request
// Pastikan periode tanggal 10 & 25 tersedia untuk bulan berjalan s/d beberapa bulan ke depan
try {
    (new \App\Models\Period())->ensureGenerated();
} catch (\Throwable $e) {
    // Database belum terinstall / belum siap - abaikan, halaman tetap dilayani
}

// ── Penegakan 1 user = 1 sesi/device + heartbeat ──
// Jika tidak ada flash sesi valid, cek apakah token sesi masih cocok dengan
// yang tersimpan di tabel users. Bila tidak, paksa logout (karena akun dipakai
// login di perangkat lain atau sesi sudah digantikan).
if (!empty($_SESSION['user']['id'])) {
    try {
        $userModel = new \App\Models\User();
        $row   = $userModel->find((int)$_SESSION['user']['id']);
        $token = (string)($_SESSION['session_token'] ?? '');

        if (!$row || empty($row['session_token']) || !hash_equals((string)$row['session_token'], $token)) {
            unset($_SESSION['user'], $_SESSION['session_token'], $_SESSION['_touch']);
            $_SESSION['flash_error'] = 'Sesi Anda berakhir karena akun ini login di perangkat lain.';
            header('Location: ' . url('login'));
            exit;
        }

        $now = time();
        if (empty($_SESSION['_touch']) || $now - (int)$_SESSION['_touch'] > 60) {
            $_SESSION['_touch'] = $now;
            $userModel->touchSession((int)$row['id']);
        }
    } catch (\Throwable $e) {
        // Database belum siap — abaikan agar halaman tetap dilayani
    }
}

$router->dispatch();
