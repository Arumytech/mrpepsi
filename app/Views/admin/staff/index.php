<?php
// Admin Staff Office Management View
?>
<div x-data="{ addModal: false, editModal: false, editItem: {} }" class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-heading font-black text-2xl sm:text-3xl text-pepsi-navy">Master Data Staff Office</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Kelola profil karyawan kantor, jabatan, kontak, dan gaji pokok bulanan.
            </p>
        </div>
        <button @click="addModal = true" class="bg-pepsi-blue hover:bg-blue-900 text-white font-bold text-xs sm:text-sm px-5 py-2.5 rounded-2xl shadow-md flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Tambah Staff Baru</span>
        </button>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-medium">
                <thead class="bg-pepsi-navy text-white text-[11px] uppercase tracking-wider font-bold">
                    <tr>
                        <th class="px-5 py-4">Nama Staff</th>
                        <th class="px-5 py-4">Jabatan</th>
                        <th class="px-5 py-4">Nomor WhatsApp</th>
                        <th class="px-5 py-4">Email</th>
                        <th class="px-5 py-4">Gaji Pokok / Bulan</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($staff)): ?>
                    <tr>
                        <td colspan="7" class="px-5 py-10 text-center text-slate-400 italic">
                            Belum ada data staff office. Klik "Tambah Staff Baru" untuk menambahkan.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($staff as $s): ?>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-pepsi-blue text-white flex items-center justify-center font-bold text-xs">
                                    <?= sanitize(substr($s['name'], 0, 1)) ?>
                                </div>
                                <span class="font-bold text-slate-900 text-sm"><?= sanitize($s['name']) ?></span>
                            </div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-slate-700 font-semibold">
                            <?= sanitize($s['position'] ?? '-') ?>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-slate-600">
                            <?= sanitize($s['phone'] ?? '-') ?>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-slate-600">
                            <?= sanitize($s['email'] ?? '-') ?>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap font-heading font-black text-sm text-pepsi-navy">
                            <?= formatRupiah($s['salary']) ?>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold <?= $s['is_active'] ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-400' ?>">
                                <?= $s['is_active'] ? 'Aktif' : 'Non-Aktif' ?>
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right whitespace-nowrap space-x-1">
                            <button @click="editItem = <?= htmlspecialchars(json_encode([
                                'id' => $s['id'],
                                'name' => $s['name'],
                                'position' => $s['position'] ?? '',
                                'phone' => $s['phone'] ?? '',
                                'email' => $s['email'] ?? '',
                                'salary' => $s['salary'],
                                'is_active' => (bool)$s['is_active']
                            ])) ?>; editModal = true;" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition">
                                Edit
                            </button>
                            <form action="<?= url('admin/staff/delete/' . $s['id']) ?>" method="POST" class="inline" onsubmit="return confirm('Hapus data staff office ini?')">
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

    <!-- MODAL TAMBAH STAFF OFFICE -->
    <div x-show="addModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="addModal = false" class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-heading font-black text-xl text-pepsi-navy">Tambah Staff Baru</h3>
                <button @click="addModal = false" class="text-slate-400 hover:text-slate-700">&times;</button>
            </div>

            <form action="<?= url('admin/staff/store') ?>" method="POST" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Staff *</label>
                    <input type="text" name="name" required placeholder="Contoh: Rina / Bagus" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Jabatan / Posisi</label>
                    <input type="text" name="position" placeholder="Contoh: Admin Kantor, Keuangan, Marketing" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nomor WhatsApp</label>
                    <input type="tel" name="phone" placeholder="Contoh: 085648077008" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Email</label>
                    <input type="email" name="email" placeholder="Contoh: staff@mrpepsi.com" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Gaji Pokok / Bulan (Rp) *</label>
                    <input type="number" name="salary" value="0" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="addModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-pepsi-blue text-white font-bold">Simpan Staff</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT STAFF OFFICE -->
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="editModal = false" class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-heading font-black text-xl text-pepsi-navy">Edit Data Staff Office</h3>
                <button @click="editModal = false" class="text-slate-400 hover:text-slate-700">&times;</button>
            </div>

            <form :action="'<?= url('admin/staff/update/') ?>' + editItem.id" method="POST" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Staff *</label>
                    <input type="text" name="name" x-model="editItem.name" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Jabatan / Posisi</label>
                    <input type="text" name="position" x-model="editItem.position" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nomor WhatsApp</label>
                    <input type="tel" name="phone" x-model="editItem.phone" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Email</label>
                    <input type="email" name="email" x-model="editItem.email" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Gaji Pokok / Bulan (Rp) *</label>
                    <input type="number" name="salary" x-model="editItem.salary" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                </div>

                <div>
                    <label class="flex items-center gap-2 font-bold text-slate-700 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" :checked="editItem.is_active" class="rounded">
                        <span>Status Aktif</span>
                    </label>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="editModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-pepsi-blue text-white font-bold">Perbarui Staff</button>
                </div>
            </form>
        </div>
    </div>

</div>
