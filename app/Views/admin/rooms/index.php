<?php
// Admin Rooms Management View
?>
<div x-data="{ addModal: false, editModal: false, editItem: {} }" class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-heading font-black text-2xl sm:text-3xl text-pepsi-navy">Master Data Ruangan</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Kelola semua ruangan (kelas, kantor, gazebo, aula, dll) beserta tipe area (Indoor/Outdoor),
                kapasitas, dan daftar inventaris barang di dalamnya.
            </p>
        </div>
        <button @click="addModal = true" class="bg-pepsi-blue hover:bg-blue-900 text-white font-bold text-xs sm:text-sm px-5 py-2.5 rounded-2xl shadow-md flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Tambah Ruangan</span>
        </button>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-medium">
                <thead class="bg-pepsi-navy text-white text-[11px] uppercase tracking-wider font-bold">
                    <tr>
                        <th class="px-5 py-4">Nama Ruangan</th>
                        <th class="px-5 py-4">Tipe Area</th>
                        <th class="px-5 py-4">Kapasitas Kursi</th>
                        <th class="px-5 py-4">Deskripsi / Fasilitas</th>
                        <th class="px-5 py-4">Inventaris</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($rooms)): ?>
                    <tr>
                        <td colspan="7" class="px-5 py-10 text-center text-slate-400 italic">
                            Belum ada data ruangan. Klik "Tambah Ruangan" untuk menambahkan.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($rooms as $r): ?>
                    <?php $isOutdoor = ($r['area_type'] ?? 'indoor') === 'outdoor'; ?>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="font-bold text-slate-900 text-sm">Ruang <?= sanitize($r['name']) ?></span>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold <?= $isOutdoor ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700' ?>">
                                <?= $isOutdoor ? 'Outdoor' : 'Indoor' ?>
                            </span>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap font-bold text-pepsi-blue">
                            <?= (int)$r['capacity'] ?> Siswa
                        </td>
                        <td class="px-5 py-4 text-slate-600">
                            <?= sanitize($r['description'] ?? '') !== '' ? sanitize($r['description']) : '-' ?>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <a href="<?= url('admin/rooms/detail/' . $r['id']) ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                <?= (int)($r['inventory_count'] ?? 0) ?> Barang
                            </a>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold <?= $r['is_active'] ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-400' ?>">
                                <?= $r['is_active'] ? 'Tersedia' : 'Dalam Perbaikan' ?>
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right whitespace-nowrap space-x-1">
                            <button @click="editItem = <?= htmlspecialchars(json_encode([
                                'id' => $r['id'],
                                'name' => $r['name'],
                                'capacity' => $r['capacity'],
                                'area_type' => $r['area_type'] ?? 'indoor',
                                'description' => $r['description'] ?? '',
                                'is_active' => (bool)$r['is_active']
                            ])) ?>; editModal = true;" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition">
                                Edit
                            </button>
                            <form action="<?= url('admin/rooms/delete/' . $r['id']) ?>" method="POST" class="inline" onsubmit="return confirm('Hapus ruangan ini beserta seluruh inventarisnya?')">
                                <button type="submit" class="px-3 py-1.5 rounded-xl bg-red-50 hover:bg-red-100 text-pepsi-red font-bold transition">
                                    Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL TAMBAH RUANGAN -->
    <div x-show="addModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="addModal = false" class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-heading font-black text-xl text-pepsi-navy">Tambah Ruangan</h3>
                <button @click="addModal = false" class="text-slate-400 hover:text-slate-700">&times;</button>
            </div>

            <form action="<?= url('admin/rooms/store') ?>" method="POST" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Ruangan *</label>
                    <input type="text" name="name" required placeholder="Contoh: Echo, Gazebo, Aula, Kantor" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Tipe Area *</label>
                    <select name="area_type" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-semibold">
                        <option value="indoor">Indoor (Dalam Ruangan)</option>
                        <option value="outdoor">Outdoor (Luar Ruangan)</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kapasitas Siswa</label>
                    <input type="number" name="capacity" value="15" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Deskripsi / Fasilitas</label>
                    <input type="text" name="description" placeholder="Contoh: Whiteboard, LCD proyektor, audio speaker" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="addModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-pepsi-blue text-white font-bold">Simpan Ruangan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT RUANGAN -->
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="editModal = false" class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-heading font-black text-xl text-pepsi-navy">Edit Ruangan</h3>
                <button @click="editModal = false" class="text-slate-400 hover:text-slate-700">&times;</button>
            </div>

            <form :action="'<?= url('admin/rooms/update/') ?>' + editItem.id" method="POST" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Ruangan *</label>
                    <input type="text" name="name" x-model="editItem.name" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Tipe Area *</label>
                    <select name="area_type" x-model="editItem.area_type" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-semibold">
                        <option value="indoor">Indoor (Dalam Ruangan)</option>
                        <option value="outdoor">Outdoor (Luar Ruangan)</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kapasitas Siswa</label>
                    <input type="number" name="capacity" x-model="editItem.capacity" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Deskripsi / Fasilitas</label>
                    <input type="text" name="description" x-model="editItem.description" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>
                <div>
                    <label class="flex items-center gap-2 font-bold text-slate-700 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" :checked="editItem.is_active" class="rounded">
                        <span>Ruangan Aktif Digunakan</span>
                    </label>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="editModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-pepsi-blue text-white font-bold">Perbarui Ruangan</button>
                </div>
            </form>
        </div>
    </div>

</div>
