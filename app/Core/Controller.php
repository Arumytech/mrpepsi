<?php
namespace App\Core;

abstract class Controller {
    public function view(string $viewPath, array $data = [], string $layout = 'guest'): void {
        // Pastikan 'settings' selalu tersedia agar branding (nama app di sidebar)
        // konsisten di semua halaman, tidak fallback ke APP_NAME.
        if (!array_key_exists('settings', $data)) {
            $data['settings'] = (new \App\Models\Setting())->getAllKeyValue();
        }
        extract($data);
        
        // Start output buffering for content view
        $viewFile = __DIR__ . '/../Views/' . str_replace('.', '/', $viewPath) . '.php';
        if (!file_exists($viewFile)) {
            die("View file [{$viewFile}] tidak ditemukan.");
        }
        
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Load layout
        if ($layout) {
            $layoutFile = __DIR__ . '/../Views/layouts/' . $layout . '.php';
            if (file_exists($layoutFile)) {
                require $layoutFile;
                return;
            }
        }
        echo $content;
    }

    public function json(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    public function redirect(string $path): void {
        header('Location: ' . url($path));
        exit;
    }

    public function requireAuth(array $roles = []): void {
        if (!isset($_SESSION['user'])) {
            if (\App\Core\Permission::isAjax()) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Sesi login telah berakhir. Silakan muat ulang halaman untuk login kembali.']);
                exit;
            }
            $_SESSION['flash_error'] = 'Silakan login terlebih dahulu.';
            $this->redirect('login');
            return;
        }

        $role = $_SESSION['user']['role'];

        // Owner = super admin, selalu diizinkan kemana pun
        if ($role === 'owner') {
            return;
        }

        // Fallback permission: jika menu request sudah diotorisasi via matrik hak akses
        $menuKey = \App\Core\Permission::currentKey();
        if ($menuKey !== null && \App\Core\Permission::can($menuKey)) {
            return;
        }

        if (!empty($roles) && !in_array($role, $roles)) {
            if (\App\Core\Permission::isAjax()) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Akses ditolak. Akun Anda tidak memiliki izin untuk aksi ini.']);
                exit;
            }
            $_SESSION['flash_error'] = 'Akses ditolak. Anda tidak memiliki izin untuk halaman ini.';
            $this->redirect($role === 'cashier' ? 'pos' : 'admin');
            return;
        }
    }

    public function setFlash(string $key, string $message): void {
        $_SESSION['flash_' . $key] = $message;
    }

    public function getFlash(string $key): ?string {
        if (isset($_SESSION['flash_' . $key])) {
            $msg = $_SESSION['flash_' . $key];
            unset($_SESSION['flash_' . $key]);
            return $msg;
        }
        return null;
    }
}
