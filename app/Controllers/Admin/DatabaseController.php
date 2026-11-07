<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Models\User;
use PDO;
use Throwable;

/**
 * Bersihkan Database — hapus isi tabel terpilih dengan verifikasi password admin.
 *
 * Tabel inti sistem (users, settings, role_permissions, user_permissions)
 * selalu dilindungi agar admin tidak terkunci keluar dari aplikasi.
 */
class DatabaseController extends Controller {

    /** Tabel yang tidak boleh dibersihkan. */
    private const PROTECTED = ['users', 'settings', 'role_permissions', 'user_permissions'];

    /** Label ramah untuk tiap tabel. */
    private const LABELS = [
        'periods'               => 'Periode Pendaftaran',
        'program_categories'    => 'Kategori Program',
        'programs'              => 'Program & Harga',
        'tutors'                => 'Data Tutor / Coach',
        'staff_offices'         => 'Master Staff Office',
        'rooms'                 => 'Data Ruangan',
        'room_inventories'      => 'Inventaris Ruangan',
        'schedules'             => 'Jadwal Kelas',
        'attendances'           => 'Presensi / Absensi',
        'registrations'         => 'Pendaftaran Siswa',
        'students'              => 'Data Siswa Aktif',
        'student_graduations'   => 'Siswa Lulus / Sertifikat',
        'canteen_categories'    => 'Kategori Produk Pepsi Food & Drink',
        'canteen_products'      => 'Produk Pepsi Food & Drink',
        'canteen_stock_batches' => 'Batch Stok Pepsi Food & Drink',
        'canteen_stock_logs'    => 'Riwayat Stok Pepsi Food & Drink',
        'cashier_shifts'        => 'Shift Kasir',
        'pos_transactions'      => 'Transaksi POS',
        'pos_transaction_items' => 'Item Transaksi POS',
        'expense_categories'    => 'Kategori Pengeluaran',
        'expenses'              => 'Pengeluaran Operasional',
        'payrolls'              => 'Penggajian (Payroll)',
    ];

    /** Pengelompokan tabel pada tampilan. */
    private const GROUPS = [
        'Akademik & Siswa' => [
            'periods', 'program_categories', 'programs',
            'schedules', 'attendances',
            'registrations', 'students', 'student_graduations'
        ],
        'Master Data' => [
            'tutors', 'staff_offices', 'rooms', 'room_inventories'
        ],
        'Pepsi Food & Drink & POS' => [
            'canteen_categories', 'canteen_products',
            'canteen_stock_batches', 'canteen_stock_logs',
            'cashier_shifts', 'pos_transactions', 'pos_transaction_items'
        ],
        'Keuangan' => [
            'expense_categories', 'expenses', 'payrolls'
        ],
    ];

    public function index(): void {
        $this->requireAuth(['admin']);

        $this->view('admin.database.index', [
            'groups'    => $this->groupedTables(),
            'protected' => $this->protectedTables(),
        ], 'admin');
    }

    public function clean(): void {
        $this->requireAuth(['admin']);

        $selected = $_POST['tables'] ?? [];
        $password = (string)($_POST['password'] ?? '');
        $confirm  = ($_POST['confirm'] ?? '') === '1';

        // ── Verifikasi password admin ──
        $userId   = (int)($_SESSION['user']['id'] ?? 0);
        $user     = (new User())->find($userId);
        if (!$user || !password_verify($password, (string)$user['password'])) {
            $this->setFlash('error', 'Verifikasi gagal: password admin tidak sesuai.');
            $this->redirect('admin/database');
        }

        if (!$confirm) {
            $this->setFlash('error', 'Centang konfirmasi bahwa Anda memahami data akan dihapus permanen.');
            $this->redirect('admin/database');
        }

        if (!is_array($selected) || empty($selected)) {
            $this->setFlash('error', 'Pilih minimal satu tabel yang ingin dibersihkan.');
            $this->redirect('admin/database');
        }

        // ── Ambil daftar tabel asli & saring dari tabel terlindungi ──
        $existing = array_column($this->rawTables(), 'name');
        $targets  = [];
        foreach ($selected as $table) {
            $table = (string)$table;
            if (!in_array($table, $existing, true)) {
                continue;
            }
            if (in_array($table, self::PROTECTED, true)) {
                continue;
            }
            $targets[] = $table;
        }
        $targets = array_values(array_unique($targets));

        if (empty($targets)) {
            $this->setFlash('error', 'Tidak ada tabel valid yang dipilih untuk dibersihkan.');
            $this->redirect('admin/database');
        }

        // ── Eksekusi pembersihan (nonaktifkan cek FK sementara) ──
        $db = Database::getConnection();
        $cleaned = [];
        try {
            $db->exec('SET FOREIGN_KEY_CHECKS = 0');
            foreach ($targets as $table) {
                $db->exec("TRUNCATE TABLE `{$table}`");
                $cleaned[] = $table;
            }
        } catch (Throwable $e) {
            $this->setFlash('error', 'Gagal membersihkan database: ' . $e->getMessage());
            $this->redirect('admin/database');
        } finally {
            try {
                $db->exec('SET FOREIGN_KEY_CHECKS = 1');
            } catch (Throwable $e) {
                // abaikan
            }
        }

        $labels = array_map(fn($t) => self::LABELS[$t] ?? $t, $cleaned);
        $this->setFlash(
            'success',
            count($cleaned) . ' tabel berhasil dibersihkan: ' . implode(', ', $labels) . '.'
        );
        $this->redirect('admin/database');
    }

    /** Ambil daftar tabel dari database (base table saja). */
    private function rawTables(): array {
        $db = Database::getConnection();
        $stmt = $db->query(
            "SELECT TABLE_NAME AS name FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'
             ORDER BY TABLE_NAME ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Tabel yang dapat dibersihkan, dikelompokkan untuk tampilan. */
    private function groupedTables(): array {
        $rows = $this->rawTables();
        $info = [];
        $used = [];

        $db = Database::getConnection();
        foreach ($rows as $row) {
            $name = $row['name'];
            if (in_array($name, self::PROTECTED, true)) {
                continue;
            }
            $count = (int)$db->query("SELECT COUNT(*) FROM `{$name}`")->fetchColumn();
            $info[$name] = ['name' => $name, 'label' => self::LABELS[$name] ?? $name, 'rows' => $count];
        }

        $groups = [];
        foreach (self::GROUPS as $groupName => $tables) {
            $items = [];
            foreach ($tables as $t) {
                if (isset($info[$t])) {
                    $items[] = $info[$t];
                    $used[$t] = true;
                }
            }
            if (!empty($items)) {
                $groups[$groupName] = $items;
            }
        }

        // Tabel lain yang belum terdaftar (mis. tabel baru / tambahan)
        $others = [];
        foreach ($info as $name => $data) {
            if (!isset($used[$name])) {
                $others[] = $data;
            }
        }
        if (!empty($others)) {
            $groups['Tabel Lainnya'] = $others;
        }

        return $groups;
    }

    /** Info tabel terlindungi untuk ditampilkan (disabled). */
    private function protectedTables(): array {
        $db = Database::getConnection();
        $rows = $this->rawTables();
        $out = [];
        foreach ($rows as $row) {
            $name = $row['name'];
            if (!in_array($name, self::PROTECTED, true)) {
                continue;
            }
            $count = (int)$db->query("SELECT COUNT(*) FROM `{$name}`")->fetchColumn();
            $out[] = ['name' => $name, 'label' => self::LABELS[$name] ?? $name, 'rows' => $count];
        }
        return $out;
    }
}
