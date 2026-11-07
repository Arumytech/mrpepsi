<?php
// Admin Room Detail & Inventory View
$isOutdoor = ($room['area_type'] ?? 'indoor') === 'outdoor';

$condLabel = [
    'good' => 'Baik',
    'minor_damage' => 'Rusak Ringan',
    'major_damage' => 'Rusak Berat',
];
$condColor = [
    'good' => 'bg-green-100 text-green-700',
    'minor_damage' => 'bg-amber-100 text-amber-700',
    'major_damage' => 'bg-red-100 text-red-700',
];

$totalQty = 0;
foreach ($inventories as $inv) {
    $totalQty += (int)$inv['quantity'];
}
?>
<div x-data="{ addModal: false, editModal: false, editItem: {} }" class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <a href="<?= url('admin/rooms') ?>" class="text-xs font-bold text-slate-500 hover:text-pepsi-blue inline-flex items-center gap-1 mb-1">
                &larr; Kembali ke Master Data Ruangan
            </a>
            <h1 class="font-heading font-black text-2xl sm:text-3xl text-pepsi-navy flex items-center gap-3">
                Ruang <?= sanitize($room['name']) ?>
                <span class="px-3 py-1 rounded-full text-[11px] font-bold <?= $isOutdoor ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700' ?>">
                    <?= $isOutdoor ? 'Outdoor' : 'Indoor' ?>
                </span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Inventaris barang di ruangan ini. Total: <b><?= (int)$totalQty ?></b> unit dari <b><?= count($inventories) ?></b> jenis barang.
            </p>
        </div>
        <button @click="addModal = true" class="bg-pepsi-blue hover:bg-blue-900 text-white font-bold text-xs sm:text-sm px-5 py-2.5 rounded-2xl shadow-md flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Tambah Barang</span>
        </button>
    </div>

    <!-- Info Ruangan -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="bg-white rounded-2xl border border-slate-200 p-4">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Kapasitas Kursi</span>
            <span class="font-heading font-black text-lg text-pepsi-blue"><?= (int)$room['capacity'] ?> Siswa</span>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Deskripsi / Fasilitas</span>
            <span class="font-bold text-sm text-slate-700"><?= sanitize($room['description'] ?? '') !== '' ? sanitize($room['description']) : '-' ?></span>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Status</span>
            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold <?= $room['is_active'] ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-400' ?>">
                <?= $room['is_active'] ? 'Tersedia' : 'Dalam Perbaikan' ?>
            </span>
        </div>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100">
            <h3 class="font-heading font-bold text-base text-slate-900">Daftar Inventaris Barang</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-medium">
                <thead class="bg-pepsi-navy text-white text-[11px] uppercase tracking-wider font-bold">
                    <tr>
                        <th class="px-5 py-4">No</th>
                        <th class="px-5 py-4">Nama Barang</th>
                        <th class="px-5 py-4">Jumlah</th>
                        <th class="px-5 py-4">Kondisi</th>
                        <th class="px-5 py-4">Catatan</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($inventories)): ?>
                    <tr>
                        <td colspan="6" class="px-5 py-10 text-center text-slate-400 italic">
                            Belum ada barang terdaftar di ruangan ini. Klik "Tambah Barang" untuk menambahkan.
                        </td>
                    </tr>
                    <?php else: $no = 1; foreach ($inventories as $inv): ?>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-5 py-4 text-slate-500 font-bold"><?= $no++ ?></td>
                        <td class="px-5 py-4 whitespace-nowrap font-bold text-slate-900"><?= sanitize($inv['item_name']) ?></td>
                        <td class="px-5 py-4 whitespace-nowrap font-bold text-pepsi-blue"><?= (int)$inv['quantity'] ?> unit</td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold <?= $condColor[$inv['condition_status']] ?? 'bg-slate-100 text-slate-500' ?>">
                                <?= $condLabel[$inv['condition_status']] ?? sanitize($inv['condition_status']) ?>
                            </span>
                        </td>
                        <td class="px-5 py-4 text-slate-600"><?= sanitize($inv['notes'] ?? '') !== '' ? sanitize($inv['notes']) : '-' ?></td>
                        <td class="px-5 py-4 text-right whitespace-nowrap space-x-1">
                            <button @click="editItem = <?= htmlspecialchars(json_encode([
                                'id' => $inv['id'],
                                'item_name' => $inv['item_name'],
                                'quantity' => $inv['quantity'],
                                'condition_status' => $inv['condition_status'],
                                'notes' => $inv['notes'] ?? ''
                            ])) ?>; editModal = true;" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition">
                                Edit
                            </button>
                            <form action="<?= url('admin/rooms/inventory/delete/' . $inv['id']) ?>" method="POST" class="inline" onsubmit="return confirm('Hapus barang ini dari inventaris?')">
                                <button type="submit" class="px-3 py-1.5 rounded-xl bg-red-50 hover:bg-red-100 text-pepsi-red font-bold transition">
                                    Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL TAMBAH BARANG -->
    <div x-show="addModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="addModal = false" class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-heading font-black text-xl text-pepsi-navy">Tambah Barang Inventaris</h3>
                <button @click="addModal = false" class="text-slate-400 hover:text-slate-700">&times;</button>
            </div>

            <form action="<?= url('admin/rooms/inventory/store') ?>" method="POST" class="space-y-4 text-xs">
                <input type="hidden" name="room_id" value="<?= (int)$room['id'] ?>">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Barang *</label>
                    <input type="text" name="item_name" required placeholder="Contoh: Kursi lipat, AC, Proyektor" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Jumlah *</label>
                        <input type="number" name="quantity" value="1" min="1" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kondisi *</label>
                        <select name="condition_status" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-semibold">
                            <option value="good">Baik</option>
                            <option value="minor_damage">Rusak Ringan</option>
                            <option value="major_damage">Rusak Berat</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Catatan</label>
                    <input type="text" name="notes" placeholder="Opsional" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="addModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-pepsi-blue text-white font-bold">Simpan Barang</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT BARANG -->
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="editModal = false" class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-heading font-black text-xl text-pepsi-navy">Edit Barang Inventaris</h3>
                <button @click="editModal = false" class="text-slate-400 hover:text-slate-700">&times;</button>
            </div>

            <form :action="'<?= url('admin/rooms/inventory/update/') ?>' + editItem.id" method="POST" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Barang *</label>
                    <input type="text" name="item_name" x-model="editItem.item_name" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Jumlah *</label>
                        <input type="number" name="quantity" x-model="editItem.quantity" min="1" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kondisi *</label>
                        <select name="condition_status" x-model="editItem.condition_status" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-semibold">
                            <option value="good">Baik</option>
                            <option value="minor_damage">Rusak Ringan</option>
                            <option value="major_damage">Rusak Berat</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Catatan</label>
                    <input type="text" name="notes" x-model="editItem.notes" placeholder="Opsional" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="editModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-pepsi-blue text-white font-bold">Perbarui Barang</button>
                </div>
            </form>
        </div>
    </div>

</div>
