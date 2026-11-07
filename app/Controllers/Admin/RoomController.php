<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Room;
use App\Models\RoomInventory;

class RoomController extends Controller {
    private function areaType(): string {
        $type = $_POST['area_type'] ?? 'indoor';
        return in_array($type, ['indoor', 'outdoor'], true) ? $type : 'indoor';
    }

    public function index(): void {
        $this->requireAuth(['admin']);
        $roomModel = new Room();
        $invModel = new RoomInventory();

        $rooms = $roomModel->all('name ASC');
        $counts = $invModel->countsByRoom();
        foreach ($rooms as &$r) {
            $r['inventory_count'] = $counts[(int)$r['id']] ?? 0;
        }
        unset($r);

        $this->view('admin.rooms.index', [
            'rooms' => $rooms
        ], 'admin');
    }

    public function detail(int $id): void {
        $this->requireAuth(['admin']);
        $roomModel = new Room();
        $invModel = new RoomInventory();

        $room = $roomModel->find($id);
        if (!$room) {
            $this->setFlash('error', 'Ruangan tidak ditemukan.');
            $this->redirect('admin/rooms');
            return;
        }

        $this->view('admin.rooms.detail', [
            'room' => $room,
            'inventories' => $invModel->getByRoom($id)
        ], 'admin');
    }

    public function store(): void {
        $this->requireAuth(['admin']);
        $roomModel = new Room();

        $name = trim($_POST['name'] ?? '');
        $capacity = (int)($_POST['capacity'] ?? 15);
        $desc = trim($_POST['description'] ?? '');

        if (empty($name)) {
            $this->setFlash('error', 'Nama ruangan wajib diisi.');
            $this->redirect('admin/rooms');
            return;
        }

        $roomModel->create([
            'name' => $name,
            'capacity' => $capacity,
            'area_type' => $this->areaType(),
            'description' => $desc,
            'is_active' => 1
        ]);

        $this->setFlash('success', "Ruangan {$name} berhasil ditambahkan!");
        $this->redirect('admin/rooms');
    }

    public function update(int $id): void {
        $this->requireAuth(['admin']);
        $roomModel = new Room();

        $name = trim($_POST['name'] ?? '');
        $capacity = (int)($_POST['capacity'] ?? 15);
        $desc = trim($_POST['description'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $roomModel->update($id, [
            'name' => $name,
            'capacity' => $capacity,
            'area_type' => $this->areaType(),
            'description' => $desc,
            'is_active' => $isActive
        ]);

        $this->setFlash('success', "Ruangan {$name} berhasil diperbarui!");
        $this->redirect('admin/rooms');
    }

    public function delete(int $id): void {
        $this->requireAuth(['admin']);
        $roomModel = new Room();
        $roomModel->delete($id);
        $this->setFlash('success', 'Ruangan berhasil dihapus.');
        $this->redirect('admin/rooms');
    }

    // ── Inventaris Barang per Ruangan ──────────────────────

    public function storeItem(): void {
        $this->requireAuth(['admin']);
        $invModel = new RoomInventory();

        $roomId = (int)($_POST['room_id'] ?? 0);
        $itemName = trim($_POST['item_name'] ?? '');
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));
        $condition = in_array($_POST['condition_status'] ?? '', ['good', 'minor_damage', 'major_damage'], true)
            ? $_POST['condition_status'] : 'good';
        $notes = trim($_POST['notes'] ?? '');

        if ($roomId <= 0 || $itemName === '') {
            $this->setFlash('error', 'Nama barang wajib diisi.');
            $this->redirect('admin/rooms/detail/' . $roomId);
            return;
        }

        $invModel->create([
            'room_id' => $roomId,
            'item_name' => $itemName,
            'quantity' => $quantity,
            'condition_status' => $condition,
            'notes' => $notes
        ]);

        $this->setFlash('success', "Barang {$itemName} berhasil ditambahkan ke inventaris.");
        $this->redirect('admin/rooms/detail/' . $roomId);
    }

    public function updateItem(int $id): void {
        $this->requireAuth(['admin']);
        $invModel = new RoomInventory();

        $item = $invModel->find($id);
        if (!$item) {
            $this->setFlash('error', 'Data inventaris tidak ditemukan.');
            $this->redirect('admin/rooms');
            return;
        }

        $itemName = trim($_POST['item_name'] ?? '');
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));
        $condition = in_array($_POST['condition_status'] ?? '', ['good', 'minor_damage', 'major_damage'], true)
            ? $_POST['condition_status'] : 'good';
        $notes = trim($_POST['notes'] ?? '');

        $invModel->update($id, [
            'item_name' => $itemName,
            'quantity' => $quantity,
            'condition_status' => $condition,
            'notes' => $notes
        ]);

        $this->setFlash('success', "Inventaris {$itemName} berhasil diperbarui.");
        $this->redirect('admin/rooms/detail/' . (int)$item['room_id']);
    }

    public function deleteItem(int $id): void {
        $this->requireAuth(['admin']);
        $invModel = new RoomInventory();

        $item = $invModel->find($id);
        $roomId = $item ? (int)$item['room_id'] : 0;

        $invModel->delete($id);
        $this->setFlash('success', 'Barang inventaris berhasil dihapus.');
        $this->redirect('admin/rooms/detail/' . $roomId);
    }
}
