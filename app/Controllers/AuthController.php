<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Permission;
use App\Models\User;

class AuthController extends Controller {
    public function showLogin(): void {
        if (isset($_SESSION['user'])) {
            $this->redirect(Permission::landingFor($_SESSION['user']['role']));
        }
        $this->view('auth.login', [], 'auth');
    }

    public function login(): void {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            $this->setFlash('error', 'Username dan password wajib diisi.');
            $this->redirect('login');
        }

        $userModel = new User();
        $user = $userModel->authenticate($username, $password);

        if ($user) {
            // Penegakan 1 user = 1 device berbasis kolom `log_login`.
            // Bila akun sedang aktif login di perangkat lain, tolak login.
            // Perangkat yang sama (cookie token cocok) tetap boleh login ulang,
            // dan sesi yang sudah idle > 30 menit otomatis dibebaskan agar tidak terkunci.
            $currentDevice = function_exists('device_token') ? device_token(false) : null;
            $forceReset    = !empty($_POST['force_reset']);

            if (!$forceReset && $userModel->isLoggedInElsewhere((int)$user['id'], $currentDevice)) {
                $_SESSION['locked_account'] = [
                    'username' => $username,
                    'name'     => $user['name']
                ];
                $this->setFlash('error', 'Akun ini sedang aktif di perangkat lain atau jendela browser sebelumnya tertutup tanpa logout. Centang "Reset sesi jika akun terkunci" di bawah lalu masukkan password kembali untuk langsung masuk.');
                $this->redirect('login');
                return;
            }

            // Jika meminta reset sesi lama atau login diizinkan
            if ($forceReset) {
                $userModel->clearSession((int)$user['id']);
            }
            unset($_SESSION['locked_account']);

            $token = function_exists('device_token') ? device_token() : bin2hex(random_bytes(32));

            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'username' => $user['username'],
                'role' => $user['role']
            ];
            $_SESSION['session_token'] = $token;
            $_SESSION['_touch'] = time();
            $userModel->startSession((int)$user['id'], $token);

            $this->setFlash('success', 'Selamat datang, ' . $user['name'] . '!');
            $this->redirect(Permission::landingFor($user['role']));
        } else {
            $this->setFlash('error', 'Username atau password salah.');
            $this->redirect('login');
        }
    }

    public function logout(): void {
        if (!empty($_SESSION['user']['id'])) {
            try {
                (new User())->clearSession((int)$_SESSION['user']['id']);
            } catch (\Throwable $e) {
                // abaikan bila DB tidak siap
            }
        }
        unset($_SESSION['user'], $_SESSION['session_token'], $_SESSION['_touch']);
        if (function_exists('device_clear_cookie')) {
            device_clear_cookie();
        }
        session_destroy();
        $this->redirect('login');
    }
}
