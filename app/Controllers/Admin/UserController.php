<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Permission;
use App\Models\User;
use App\Models\UserPermission;

class UserController extends Controller {

    // ──────────────────────────────────────────────────
    // INDEX — Daftar akun admin & kasir
    // ──────────────────────────────────────────────────
    public function index(): void {
        $this->requireAuth(['admin']);

        $model = new User();
        $users = $model->all('id ASC');

        $adminCount  = 0;
        $cashierCount = 0;
        $ownerCount   = 0;
        foreach ($users as &$u) {
            unset($u['password']); // jangan kirim hash ke view
            $u['online'] = $model->isOnline($u);
            if ($u['role'] === 'admin') $adminCount++;
            elseif ($u['role'] === 'cashier') $cashierCount++;
            elseif ($u['role'] === 'owner') $ownerCount++;
        }
        unset($u);

        // Hak akses efektif tiap user + status override per-user
        $upModel = new UserPermission();
        $userPerms = [];
        $userPermConfigured = [];
        foreach ($users as $u) {
            $uid = (int)$u['id'];
            $configured = $upModel->isConfigured($uid);
            if ($configured) {
                // satukan semua key menu; benar bila allowed
                $map = array_fill_keys(array_keys(Permission::menus()), false);
                foreach ($upModel->mapForUser($uid) as $k => $v) {
                    $map[$k] = $v;
                }
                $userPerms[$uid] = $map;
            } else {
                $userPerms[$uid] = array_fill_keys(Permission::allowedFor($u['role']), true)
                    + array_fill_keys(array_keys(Permission::menus()), false);
            }
            $userPermConfigured[$uid] = $configured;
        }

        // Kelompokkan menu untuk modal hak akses
        $permGroups = [];
        foreach (Permission::menus() as $key => $menu) {
            $permGroups[$menu['group'] ?? 'Lainnya'][$key] = $menu;
        }

        $this->view('admin.users.index', [
            'users'               => $users,
            'adminCount'          => $adminCount,
            'cashierCount'        => $cashierCount,
            'ownerCount'          => $ownerCount,
            'currentUserId'       => (int)($_SESSION['user']['id'] ?? 0),
            'userPerms'           => $userPerms,
            'userPermConfigured'  => $userPermConfigured,
            'permGroups'          => $permGroups,
        ], 'admin');
    }

    // ──────────────────────────────────────────────────
    // SAVE PERMISSIONS — Set override hak akses per user
    // ──────────────────────────────────────────────────
    public function savePermissions(int $id): void {
        $this->requireAuth(['admin']);
        $model = new User();

        $user = $model->find($id);
        if (!$user) {
            $this->setFlash('error', 'User tidak ditemukan.');
            $this->redirect('admin/users');
        }
        if (($user['role'] ?? '') === 'owner') {
            $this->setFlash('error', 'Hak akses akun Owner selalu penuh dan tidak bisa diubah.');
            $this->redirect('admin/users');
        }
        if ((int)($_SESSION['user']['id'] ?? 0) === $id) {
            $this->setFlash('error', 'Tidak bisa mengubah hak akses akun Anda sendiri.');
            $this->redirect('admin/users');
        }

        $menuKeys = array_keys(Permission::menus());
        $submitted = $_POST['perm'] ?? [];
        if (!is_array($submitted)) {
            $submitted = [];
        }
        $allowed = array_values(array_intersect($menuKeys, array_keys($submitted)));

        $upModel = new UserPermission();
        if ($upModel->saveUser($id, $menuKeys, $allowed)) {
            Permission::resetCache();
            $this->setFlash('success', "Hak akses user \"{$user['name']}\" berhasil diperbarui.");
        } else {
            $this->setFlash('error', 'Gagal menyimpan hak akses user.');
        }
        $this->redirect('admin/users');
    }

    // ──────────────────────────────────────────────────
    // RESET PERMISSIONS — Kembalikan ke default role
    // ──────────────────────────────────────────────────
    public function resetPermissions(int $id): void {
        $this->requireAuth(['admin']);
        $model = new User();

        $user = $model->find($id);
        if (!$user) {
            $this->setFlash('error', 'User tidak ditemukan.');
            $this->redirect('admin/users');
        }
        if ((int)($_SESSION['user']['id'] ?? 0) === $id) {
            $this->setFlash('error', 'Tidak bisa mengubah hak akses akun Anda sendiri.');
            $this->redirect('admin/users');
        }

        (new UserPermission())->resetUser($id);
        Permission::resetCache();
        $this->setFlash('success', "Hak akses user \"{$user['name']}\" dikembalikan ke default role.");
        $this->redirect('admin/users');
    }

    // ──────────────────────────────────────────────────
    // STORE — Tambah user baru
    // ──────────────────────────────────────────────────
    public function store(): void {
        $this->requireAuth(['admin']);
        $model = new User();

        $name     = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $role     = in_array($_POST['role'] ?? 'admin', ['admin', 'cashier', 'owner'], true) ? $_POST['role'] : 'admin';

        if ($name === '' || $username === '' || $password === '') {
            $this->setFlash('error', 'Nama, username, dan password wajib diisi.');
            $this->redirect('admin/users');
        }
        if (!preg_match('/^[A-Za-z0-9._-]+$/', $username)) {
            $this->setFlash('error', 'Username hanya boleh huruf, angka, titik, underscore, dan strip.');
            $this->redirect('admin/users');
        }
        if (strlen($password) < 5) {
            $this->setFlash('error', 'Password minimal 5 karakter.');
            $this->redirect('admin/users');
        }
        if ($role === 'owner' && ($_SESSION['user']['role'] ?? '') !== 'owner') {
            $this->setFlash('error', 'Hanya role Owner yang bisa membuat akun Owner.');
            $this->redirect('admin/users');
        }
        if ($model->findByUsername($username)) {
            $this->setFlash('error', "Username \"{$username}\" sudah digunakan.");
            $this->redirect('admin/users');
        }

        $model->create([
            'name'     => $name,
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role'     => $role,
        ]);

        $this->setFlash('success', "User \"{$name}\" berhasil ditambahkan!");
        $this->redirect('admin/users');
    }

    // ──────────────────────────────────────────────────
    // UPDATE — Edit user (nama, username, role, reset password opsional)
    // ──────────────────────────────────────────────────
    public function update(int $id): void {
        $this->requireAuth(['admin']);
        $model = new User();

        $user = $model->find($id);
        if (!$user) {
            $this->setFlash('error', 'User tidak ditemukan.');
            $this->redirect('admin/users');
        }

        $name     = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $role     = in_array($_POST['role'] ?? 'admin', ['admin', 'cashier', 'owner'], true) ? $_POST['role'] : 'admin';
        $password = (string)($_POST['password'] ?? '');

        if ($name === '' || $username === '') {
            $this->setFlash('error', 'Nama dan username wajib diisi.');
            $this->redirect('admin/users');
        }
        if (!preg_match('/^[A-Za-z0-9._-]+$/', $username)) {
            $this->setFlash('error', 'Username hanya boleh huruf, angka, titik, underscore, dan strip.');
            $this->redirect('admin/users');
        }

        // Non-owner tidak boleh mengubah/menurunkan akun owner
        if ($user['role'] === 'owner' && ($_SESSION['user']['role'] ?? '') !== 'owner') {
            $this->setFlash('error', 'Hanya role Owner yang bisa mengubah akun Owner.');
            $this->redirect('admin/users');
        }

        $existing = $model->findByUsername($username);
        if ($existing && (int)$existing['id'] !== $id) {
            $this->setFlash('error', "Username \"{$username}\" sudah digunakan.");
            $this->redirect('admin/users');
        }

        // Jangan sampai akun admin terakhir dihilangkan
        if ($user['role'] === 'admin' && $role !== 'admin' && $model->countAdmins() <= 1) {
            $this->setFlash('error', 'Tidak bisa mengubah role: minimal harus ada 1 akun admin.');
            $this->redirect('admin/users');
        }

        // Jangan sampai akun owner terakhir dihilangkan
        if ($user['role'] === 'owner' && $role !== 'owner' && $model->countOwners() <= 1) {
            $this->setFlash('error', 'Tidak bisa mengubah role: minimal harus ada 1 akun owner.');
            $this->redirect('admin/users');
        }

        $data = ['name' => $name, 'username' => $username, 'role' => $role];
        if ($password !== '') {
            if (strlen($password) < 5) {
                $this->setFlash('error', 'Password baru minimal 5 karakter.');
                $this->redirect('admin/users');
            }
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $model->update($id, $data);

        // Perbarui sesi bila mengubah akun sendiri
        if ((int)($_SESSION['user']['id'] ?? 0) === $id) {
            $_SESSION['user']['name']     = $name;
            $_SESSION['user']['username'] = $username;
            $_SESSION['user']['role']     = $role;
        }

        $this->setFlash('success', "User \"{$name}\" berhasil diperbarui.");
        $this->redirect('admin/users');
    }

    // ──────────────────────────────────────────────────
    // DELETE — Hapus user
    // ──────────────────────────────────────────────────
    public function delete(int $id): void {
        $this->requireAuth(['admin']);
        $model = new User();

        if ((int)($_SESSION['user']['id'] ?? 0) === $id) {
            $this->setFlash('error', 'Tidak bisa menghapus akun yang sedang Anda gunakan.');
            $this->redirect('admin/users');
        }

        $user = $model->find($id);
        if (!$user) {
            $this->setFlash('error', 'User tidak ditemukan.');
            $this->redirect('admin/users');
        }

        // Non-owner tidak boleh menghapus akun owner
        if ($user['role'] === 'owner' && ($_SESSION['user']['role'] ?? '') !== 'owner') {
            $this->setFlash('error', 'Hanya role Owner yang bisa menghapus akun Owner.');
            $this->redirect('admin/users');
        }

        if ($user['role'] === 'admin' && $model->countAdmins() <= 1) {
            $this->setFlash('error', 'Tidak bisa menghapus: minimal harus ada 1 akun admin.');
            $this->redirect('admin/users');
        }

        if ($user['role'] === 'owner' && $model->countOwners() <= 1) {
            $this->setFlash('error', 'Tidak bisa menghapus: minimal harus ada 1 akun owner.');
            $this->redirect('admin/users');
        }

        (new UserPermission())->resetUser($id);
        $model->delete($id);
        Permission::resetCache();
        $this->setFlash('success', "User \"{$user['name']}\" berhasil dihapus.");
        $this->redirect('admin/users');
    }
}
