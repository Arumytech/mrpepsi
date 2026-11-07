<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Tutor;

class TutorController extends Controller {
    public function index(): void {
        $this->requireAuth(['admin']);
        $tutorModel = new Tutor();
        $tutors = $tutorModel->all('name ASC');

        $this->view('admin.tutors.index', [
            'tutors' => $tutors
        ], 'admin');
    }

    public function store(): void {
        $this->requireAuth(['admin']);
        $tutorModel = new Tutor();

        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $specialization = trim($_POST['specialization'] ?? '');
        $fee = (float)($_POST['fee_per_session'] ?? 40000);

        if (empty($name)) {
            $this->setFlash('error', 'Nama tutor wajib diisi.');
            $this->redirect('admin/tutors');
        }

        $tutorModel->create([
            'name' => $name,
            'phone' => $phone,
            'specialization' => $specialization,
            'fee_per_session' => $fee,
            'is_active' => 1
        ]);

        $this->setFlash('success', "Tutor Coach {$name} berhasil ditambahkan!");
        $this->redirect('admin/tutors');
    }

    public function update(int $id): void {
        $this->requireAuth(['admin']);
        $tutorModel = new Tutor();

        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $specialization = trim($_POST['specialization'] ?? '');
        $fee = (float)($_POST['fee_per_session'] ?? 40000);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $tutorModel->update($id, [
            'name' => $name,
            'phone' => $phone,
            'specialization' => $specialization,
            'fee_per_session' => $fee,
            'is_active' => $isActive
        ]);

        $this->setFlash('success', "Data Coach {$name} berhasil diperbarui!");
        $this->redirect('admin/tutors');
    }

    public function delete(int $id): void {
        $this->requireAuth(['admin']);
        $tutorModel = new Tutor();
        $tutorModel->delete($id);
        $this->setFlash('success', 'Tutor berhasil dihapus.');
        $this->redirect('admin/tutors');
    }
}
