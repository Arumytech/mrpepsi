<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Program;
use App\Models\ProgramCategory;

class ProgramController extends Controller {
    public function index(): void {
        $this->requireAuth(['admin']);
        $programModel = new Program();
        $categoryModel = new ProgramCategory();
        $programs = $programModel->all('price ASC, title ASC');
        $categories = $categoryModel->all('name ASC');

        $this->view('admin.programs.index', [
            'programs'   => $programs,
            'categories' => $categories
        ], 'admin');
    }

    public function store(): void {
        $this->requireAuth(['admin']);
        $programModel = new Program();

        $code = trim($_POST['code'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $levelCat = trim($_POST['level_category'] ?? 'basic');
        $levelLabel = trim($_POST['level_label'] ?? 'Basic Level');
        $orientation = trim($_POST['orientation'] ?? 'speaking');
        $price = (float)($_POST['price'] ?? 0);
        $englishRatio = trim($_POST['english_ratio'] ?? '50%');
        $description = trim($_POST['description'] ?? '');
        $finalExam = trim($_POST['final_exam'] ?? '');
        $isNew = isset($_POST['is_new']) ? 1 : 0;

        // Process syllabus lines into JSON
        $syllabusRaw = trim($_POST['syllabus'] ?? '');
        $syllabusLines = array_values(array_filter(array_map('trim', explode("\n", $syllabusRaw))));

        if (empty($code) || empty($title) || $price <= 0) {
            $this->setFlash('error', 'Kode program, nama, dan harga wajib diisi.');
            $this->redirect('admin/programs');
        }

        try {
            $programModel->create([
                'code' => $code,
                'title' => $title,
                'category_id' => $categoryId,
                'level_category' => $levelCat,
                'level_label' => $levelLabel,
                'orientation' => $orientation,
                'price' => $price,
                'english_ratio' => $englishRatio,
                'description' => $description,
                'syllabus' => json_encode($syllabusLines),
                'final_exam' => $finalExam,
                'is_new' => $isNew,
                'is_active' => 1
            ]);
            $this->setFlash('success', "Program {$title} ({$code}) berhasil ditambahkan!");
        } catch (\Exception $e) {
            $this->setFlash('error', "Gagal menambah program: " . $e->getMessage());
        }

        $this->redirect('admin/programs');
    }

    public function update(int $id): void {
        $this->requireAuth(['admin']);
        $programModel = new Program();
        $existing = $programModel->find($id);

        if (!$existing) {
            $this->setFlash('error', 'Program tidak ditemukan.');
            $this->redirect('admin/programs');
        }

        $code = trim($_POST['code'] ?? $existing['code']);
        $title = trim($_POST['title'] ?? $existing['title']);
        $categoryId = array_key_exists('category_id', $_POST) ? (!empty($_POST['category_id']) ? (int)$_POST['category_id'] : null) : ($existing['category_id'] ?? null);
        $levelCat = trim($_POST['level_category'] ?? $existing['level_category']);
        $levelLabel = trim($_POST['level_label'] ?? $existing['level_label']);
        $orientation = trim($_POST['orientation'] ?? $existing['orientation']);
        $price = (float)($_POST['price'] ?? $existing['price']);
        $englishRatio = trim($_POST['english_ratio'] ?? $existing['english_ratio']);
        $description = trim($_POST['description'] ?? $existing['description']);
        $finalExam = trim($_POST['final_exam'] ?? $existing['final_exam']);
        $isNew = isset($_POST['is_new']) ? 1 : 0;
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $syllabusRaw = trim($_POST['syllabus'] ?? '');
        $syllabusLines = array_values(array_filter(array_map('trim', explode("\n", $syllabusRaw))));

        try {
            $programModel->update($id, [
                'code' => $code,
                'title' => $title,
                'category_id' => $categoryId,
                'level_category' => $levelCat,
                'level_label' => $levelLabel,
                'orientation' => $orientation,
                'price' => $price,
                'english_ratio' => $englishRatio,
                'description' => $description,
                'syllabus' => json_encode($syllabusLines),
                'final_exam' => $finalExam,
                'is_new' => $isNew,
                'is_active' => $isActive
            ]);
            $this->setFlash('success', "Program {$title} berhasil diperbarui!");
        } catch (\Exception $e) {
            $this->setFlash('error', "Gagal memperbarui program: " . $e->getMessage());
        }

        $this->redirect('admin/programs');
    }

    public function delete(int $id): void {
        $this->requireAuth(['admin']);
        $programModel = new Program();
        $programModel->delete($id);
        $this->setFlash('success', 'Program berhasil dihapus.');
        $this->redirect('admin/programs');
    }

    public function storeCategory(): void {
        $this->requireAuth(['admin']);
        $catModel = new ProgramCategory();

        $name = trim($_POST['name'] ?? '');
        $reportGroup = trim($_POST['report_group'] ?? '');
        if (!in_array($reportGroup, ['online', 'offline', 'non_kelas'], true)) {
            $reportGroup = null;
        }
        if ($name === '' || mb_strlen($name) > 50) {
            $this->setFlash('error', 'Nama kategori wajib diisi (maks 50 karakter).');
            $this->redirect('admin/programs');
        }

        try {
            $existing = $catModel->where('name', $name, 'id ASC');
            if (!empty($existing)) {
                $this->setFlash('error', 'Nama kategori sudah terdaftar.');
                $this->redirect('admin/programs');
            }
            $catModel->create(['name' => $name, 'report_group' => $reportGroup]);
            $this->setFlash('success', "Kategori {$name} berhasil ditambahkan!");
        } catch (\Exception $e) {
            $this->setFlash('error', "Gagal menambah kategori: " . $e->getMessage());
        }
        $this->redirect('admin/programs');
    }

    public function updateCategory(int $id): void {
        $this->requireAuth(['admin']);
        $catModel = new ProgramCategory();
        $existing = $catModel->find($id);
        if (!$existing) {
            $this->setFlash('error', 'Kategori tidak ditemukan.');
            $this->redirect('admin/programs');
        }

        $name = trim($_POST['name'] ?? '');
        $reportGroup = trim($_POST['report_group'] ?? '');
        if (!in_array($reportGroup, ['online', 'offline', 'non_kelas'], true)) {
            $reportGroup = null;
        }
        if ($name === '' || mb_strlen($name) > 50) {
            $this->setFlash('error', 'Nama kategori wajib diisi (maks 50 karakter).');
            $this->redirect('admin/programs');
        }

        try {
            $list = $catModel->where('name', $name, 'id ASC');
            foreach ($list as $c) {
                if ((int)$c['id'] !== $id) {
                    $this->setFlash('error', 'Nama kategori sudah terdaftar.');
                    $this->redirect('admin/programs');
                    return;
                }
            }
            $catModel->update($id, ['name' => $name, 'report_group' => $reportGroup]);
            $this->setFlash('success', 'Kategori berhasil diperbarui!');
        } catch (\Exception $e) {
            $this->setFlash('error', "Gagal memperbarui kategori: " . $e->getMessage());
        }
        $this->redirect('admin/programs');
    }

    public function deleteCategory(int $id): void {
        $this->requireAuth(['admin']);
        $catModel = new ProgramCategory();
        $programModel = new Program();
        $cat = $catModel->find($id);
        if (!$cat) {
            $this->setFlash('error', 'Kategori tidak ditemukan.');
            $this->redirect('admin/programs');
        }

        try {
            $used = $programModel->raw("SELECT COUNT(*) AS total FROM `programs` WHERE category_id = ?", [$id]);
            $totalUsed = (int)($used[0]['total'] ?? 0);
            if ($totalUsed > 0) {
                $this->setFlash('error', "Kategori tidak dapat dihapus karena masih digunakan oleh {$totalUsed} program.");
                $this->redirect('admin/programs');
                return;
            }
            $catModel->delete($id);
            $this->setFlash('success', 'Kategori berhasil dihapus!');
        } catch (\Exception $e) {
            $this->setFlash('error', "Gagal menghapus kategori: " . $e->getMessage());
        }
        $this->redirect('admin/programs');
    }
}
