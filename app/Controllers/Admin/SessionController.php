<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\User;

class SessionController extends Controller {

    /**
     * Halaman Manajemen & Release Sesi Login
     */
    public function index(): void {
        $this->requireAuth(['admin']);

        $userModel = new User();
        $users = $userModel->all('id ASC');

        $activeCount = 0;
        $idleCount = 0;
        $offlineCount = 0;
        $currentUserId = (int)($_SESSION['user']['id'] ?? 0);

        foreach ($users as &$u) {
            unset($u['password']);
            $u['is_online'] = $userModel->isOnline($u);
            $u['has_session'] = ((int)($u['log_login'] ?? 0) === 1) || !empty($u['session_token']);

            if ($u['is_online']) {
                $activeCount++;
            } elseif ($u['has_session']) {
                $idleCount++;
            } else {
                $offlineCount++;
            }
        }
        unset($u);

        $this->view('admin.sessions.index', [
            'users'          => $users,
            'activeCount'    => $activeCount,
            'idleCount'      => $idleCount,
            'offlineCount'   => $offlineCount,
            'totalCount'     => count($users),
            'currentUserId'  => $currentUserId,
        ], 'admin');
    }

    /**
     * Lepaskan (Release) sesi satu user tertentu
     */
    public function release(int $id): void {
        $this->requireAuth(['admin']);

        $userModel = new User();
        $user = $userModel->find($id);

        if (!$user) {
            $this->setFlash('error', 'User tidak ditemukan.');
            $this->redirect('admin/sessions');
            return;
        }

        $currentUserId = (int)($_SESSION['user']['id'] ?? 0);

        // Jika merilis sesi sendiri, lakukan logout penuh
        if ($currentUserId === $id) {
            $userModel->clearSession($id);
            unset($_SESSION['user'], $_SESSION['session_token'], $_SESSION['_touch']);
            if (function_exists('device_clear_cookie')) {
                device_clear_cookie();
            }
            session_destroy();
            $this->redirect('login');
            return;
        }

        $userModel->clearSession($id);
        $this->setFlash('success', "Sesi login untuk user \"{$user['name']}\" ({$user['username']}) berhasil dilepaskan. User sekarang dapat login kembali di perangkat mana pun.");
        $this->redirect('admin/sessions');
    }

    /**
     * Lepaskan (Release) semua sesi login aktif sekaligus
     */
    public function releaseAll(): void {
        $this->requireAuth(['admin']);

        $userModel = new User();
        $currentUserId = (int)($_SESSION['user']['id'] ?? 0);
        
        // Pilihan: apakah sesi akun saat ini tetap dipertahankan
        $keepSelf = !isset($_POST['include_self']) || (int)$_POST['include_self'] === 0;

        $count = $userModel->clearAllSessions($keepSelf ? $currentUserId : null);

        if (!$keepSelf) {
            unset($_SESSION['user'], $_SESSION['session_token'], $_SESSION['_touch']);
            if (function_exists('device_clear_cookie')) {
                device_clear_cookie();
            }
            session_destroy();
            $this->redirect('login');
            return;
        }

        $this->setFlash('success', "Berhasil melepaskan {$count} sesi user lain. Semua akun yang terkunci sekarang dapat login kembali.");
        $this->redirect('admin/sessions');
    }
}
