<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Setting;
use App\Models\Period;

class SettingController extends Controller {
    public function index(): void {
        $this->requireAuth(['admin']);
        $settingModel = new Setting();
        $periodModel = new Period();

        $settings = $settingModel->getAllKeyValue();
        $periods = $periodModel->all('ABS(DATEDIFF(start_date, CURDATE())) ASC');

        $this->view('admin.settings.index', [
            'settings' => $settings,
            'periods' => $periods
        ], 'admin');
    }

    public function update(): void {
        $this->requireAuth(['admin']);
        $settingModel = new Setting();

        // 1. Proses upload logo terlebih dahulu (validasi gagal = batal simpan semua)
        $logoError = $this->handleLogoUpload($settingModel);
        if ($logoError !== null) {
            $this->setFlash('error', $logoError);
            $this->redirect('admin/settings');
        }

        // 2. Simpan pengaturan teks
        $keys = [
            'app_name',
            'tagline',
            'admin_wa',
            'address',
            'instagram',
            'email',
            'office_hours',
            'class_rules',
            'sk_diknas',
            'npsn',
            'default_tutor_fee'
        ];

        foreach ($keys as $k) {
            if (isset($_POST[$k])) {
                $settingModel->set($k, trim($_POST[$k]));
            }
        }

        // 3. Pengaturan format nomor sertifikat
        if (isset($_POST['certificate_format'])) {
            $fmt = trim($_POST['certificate_format']);
            if ($fmt === '') {
                $fmt = '{seq}/MPU/C.{code}/{rm}/{Y}';
            } elseif (stripos($fmt, '{seq') === false) {
                $fmt .= '{seq}'; // pastikan selalu ada token nomor urut
            }
            $settingModel->set('certificate_format', $fmt);
        }
        if (isset($_POST['certificate_seq_digits'])) {
            $digits = (int)$_POST['certificate_seq_digits'];
            if ($digits < 1) $digits = 1;
            if ($digits > 10) $digits = 10;
            $settingModel->set('certificate_seq_digits', (string)$digits);
        }
        if (isset($_POST['certificate_seq_start'])) {
            $start = (int)$_POST['certificate_seq_start'];
            if ($start < 1) $start = 1;
            // Set nomor urut berikutnya hanya jika counter belum berjalan
            $existing = trim($settingModel->get('certificate_seq_counter', ''));
            if ($existing === '') {
                $settingModel->set('certificate_seq_counter', (string)max(1, $start - 1));
            }
        }

        // 4. Pengaturan POS — mode cetak struk/nota kasir
        if (isset($_POST['pos_receipt_mode'])) {
            $mode = trim($_POST['pos_receipt_mode']);
            if (!in_array($mode, ['always', 'ask', 'never'], true)) {
                $mode = 'ask';
            }
            $settingModel->set('pos_receipt_mode', $mode);
        }

        // Active period update if provided
        if (!empty($_POST['active_period_id'])) {
            $periodModel = new Period();
            $periodModel->raw("UPDATE periods SET is_active = 0");
            $periodModel->update((int)$_POST['active_period_id'], ['is_active' => 1]);
        }

        $this->setFlash('success', 'Pengaturan sistem berhasil diperbarui!');
        $this->redirect('admin/settings');
    }

    /** Admin: halaman kontrol buka/tutup operasional POS. */
    public function posControl(): void {
        $this->requireAuth(['admin']);
        $settings = (new Setting())->getAllKeyValue();

        $this->view('admin.settings.pos_control', [
            'settings' => $settings
        ], 'admin');
    }

    /** Admin: tutup operasional POS untuk hari ini. */
    public function posClose(): void {
        $this->requireAuth(['admin']);
        (new Setting())->set('pos_closed_date', date('Y-m-d'));
        $this->setFlash('success', 'POS berhasil ditutup untuk hari ini. Mesin kasir akan terbuka otomatis besok.');
        $this->redirect('admin/settings');
    }

    /** Admin: buka kembali operasional POS (batalkan penutupan hari ini). */
    public function posOpen(): void {
        $this->requireAuth(['admin']);
        (new Setting())->set('pos_closed_date', '');
        $this->setFlash('success', 'POS berhasil dibuka kembali. Kasir dapat bertransaksi lagi.');
        $this->redirect('admin/settings');
    }

    /**
     * Upload / hapus logo lembaga.
     * @return string|null pesan error, null jika aman
     */
    private function handleLogoUpload(Setting $settingModel): ?string {
        $current = trim($settingModel->get('logo_path', ''));
        $file = $_FILES['logo'] ?? null;
        $hasFile = $file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        // Opsi hapus logo
        if (!empty($_POST['remove_logo']) && !$hasFile) {
            $this->deleteLogoFile($current);
            $settingModel->set('logo_path', '');
            return null;
        }

        if (!$hasFile) {
            return null;
        }

        $allowed = [
            'image/png'     => 'png',
            'image/jpeg'    => 'jpg',
            'image/webp'    => 'webp',
            'image/gif'     => 'gif',
            'image/svg+xml' => 'svg'
        ];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
                return 'Ukuran file logo melebihi batas. Maksimal ' . (LOGO_MAX_SIZE / 1024 / 1024) . ' MB.';
            }
            return 'Upload logo gagal (kode error ' . (int)$file['error'] . '). Silakan coba lagi.';
        }

        if ($file['size'] > LOGO_MAX_SIZE) {
            return 'Ukuran file logo maksimal ' . (LOGO_MAX_SIZE / 1024 / 1024) . ' MB.';
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            return 'File logo tidak valid.';
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $ext = $allowed[$mime] ?? null;

        if ($ext === null) {
            return 'Format file tidak didukung. Gunakan gambar PNG, JPG, WEBP, GIF, atau SVG.';
        }

        if ($ext === 'svg' && $file['size'] > 512 * 1024) {
            return 'File SVG terlalu besar. Maksimal 512 KB.';
        }

        $dir = UPLOAD_DIR . '/logo';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return 'Folder upload tidak dapat dibuat di server.';
        }

        $name = 'logo-' . date('Ymd') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
            return 'Gagal menyimpan file logo. Pastikan folder uploads/ dapat ditulis (writable).';
        }
        @chmod($dir . '/' . $name, 0644);

        // Bersihkan logo lama agar tidak menumpuk
        $this->deleteLogoFile($current);
        $settingModel->set('logo_path', 'uploads/logo/' . $name);

        return null;
    }

    private function deleteLogoFile(string $relativePath): void {
        $relativePath = trim($relativePath);
        if ($relativePath === '' || preg_match('#^https?://#i', $relativePath)) {
            return;
        }

        $relative = ltrim(str_replace('\\', '/', $relativePath), '/');
        // Hanya file di dalam folder upload logo yang boleh dihapus
        if (strpos($relative, 'uploads/logo/') !== 0) {
            return;
        }

        $absolute = BASE_PATH . '/' . $relative;
        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }
}
