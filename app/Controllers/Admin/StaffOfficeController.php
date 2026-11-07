<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\StaffOffice;

class StaffOfficeController extends Controller {
    public function index(): void {
        $this->requireAuth(['admin']);
        $staffModel = new StaffOffice();
        $staff = $staffModel->all('is_active DESC, name ASC');

        $this->view('admin.staff.index', [
            'staff' => $staff
        ], 'admin');
    }

    public function store(): void {
        $this->requireAuth(['admin']);
        $staffModel = new StaffOffice();

        $name = trim($_POST['name'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $salary = (float)($_POST['salary'] ?? 0);

        if ($name === '') {
            $this->setFlash('error', 'Nama staff wajib diisi.');
            $this->redirect('admin/staff');
        }

        $staffModel->create([
            'name' => $name,
            'position' => $position,
            'phone' => $phone,
            'email' => $email,
            'salary' => $salary,
            'is_active' => 1
        ]);

        $this->setFlash('success', "Staff Office {$name} berhasil ditambahkan!");
        $this->redirect('admin/staff');
    }

    public function update(int $id): void {
        $this->requireAuth(['admin']);
        $staffModel = new StaffOffice();

        $name = trim($_POST['name'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $salary = (float)($_POST['salary'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($name === '') {
            $this->setFlash('error', 'Nama staff wajib diisi.');
            $this->redirect('admin/staff');
        }

        $staffModel->update($id, [
            'name' => $name,
            'position' => $position,
            'phone' => $phone,
            'email' => $email,
            'salary' => $salary,
            'is_active' => $isActive
        ]);

        $this->setFlash('success', "Data Staff Office {$name} berhasil diperbarui!");
        $this->redirect('admin/staff');
    }

    public function delete(int $id): void {
        $this->requireAuth(['admin']);
        $staffModel = new StaffOffice();
        $staffModel->delete($id);
        $this->setFlash('success', 'Data Staff Office berhasil dihapus.');
        $this->redirect('admin/staff');
    }
}
