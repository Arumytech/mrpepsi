<?php
// Admin Tutors Management View
?>
<div x-data="{ addModal: false, editModal: false, editItem: {} }" class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-heading font-black text-2xl sm:text-3xl text-pepsi-navy">Master Data Tutor & Coach</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Kelola profil pengajar, nomor kontak WhatsApp, spesialisasi kurikulum, dan tarif gaji per sesi mengajar.
            </p>
        </div>
        <button @click="addModal = true" class="bg-pepsi-blue hover:bg-blue-900 text-white font-bold text-xs sm:text-sm px-5 py-2.5 rounded-2xl shadow-md flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Tambah Coach Baru</span>
        </button>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-medium">
                <thead class="bg-pepsi-navy text-white text-[11px] uppercase tracking-wider font-bold">
                    <tr>
                        <th class="px-5 py-4">Nama Coach</th>
                        <th class="px-5 py-4">Nomor WhatsApp</th>
                        <th class="px-5 py-4">Bidang Spesialisasi</th>
                        <th class="px-5 py-4">Tarif Gaji Standar</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($tutors as $t): ?>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-pepsi-blue text-white flex items-center justify-center font-bold text-xs">
                                    <?= substr($t['name'], 0, 1) ?>
                                </div>
                                <span class="font-bold text-slate-900 text-sm">Coach <?= sanitize($t['name']) ?></span>
                            </div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-slate-600">
                            <?= sanitize($t['phone'] ?? '-') ?>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-slate-700 font-semibold">
                            <?= sanitize($t['specialization'] ?? 'Speaking Specialist') ?>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap font-heading font-black text-sm text-pepsi-navy">
                            <?= formatRupiah($t['fee_per_session']) ?> / sesi
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold <?= $t['is_active'] ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-400' ?>">
                                <?= $t['is_active'] ? 'Aktif Mengajar' : 'Non-Aktif' ?>
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right whitespace-nowrap space-x-1">
                            <button @click="editItem = <?= htmlspecialchars(json_encode([
                                'id' => $t['id'],
                                'name' => $t['name'],
                                'phone' => $t['phone'] ?? '',
                                'specialization' => $t['specialization'] ?? '',
                                'fee_per_session' => $t['fee_per_session'],
                                'is_active' => (bool)$t['is_active']
                            ])) ?>; editModal = true;" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition">
                                Edit
                            </button>
                            <form action="<?= url('admin/tutors/delete/' . $t['id']) ?>" method="POST" class="inline" onsubmit="return confirm('Hapus data tutor ini?')">
                                <button type="submit" class="px-3 py-1.5 rounded-xl bg-red-50 hover:bg-red-100 text-pepsi-red font-bold transition">
                                    Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL TAMBAH TUTOR -->
    <div x-show="addModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="addModal = false" class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-heading font-black text-xl text-pepsi-navy">Tambah Coach Baru</h3>
                <button @click="addModal = false" class="text-slate-400 hover:text-slate-700">&times;</button>
            </div>

            <form action="<?= url('admin/tutors/store') ?>" method="POST" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Coach *</label>
                    <input type="text" name="name" required placeholder="Contoh: Aprilia / Anugrah" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nomor WhatsApp</label>
                    <input type="tel" name="phone" placeholder="Contoh: 085648077008" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Spesialisasi Mengajar</label>
                    <input type="text" name="specialization" placeholder="Contoh: IELTS Speaking, Impromptu, Job Interview" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Tarif Gaji per Sesi (Rp) *</label>
                    <input type="number" name="fee_per_session" value="40000" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="addModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-pepsi-blue text-white font-bold">Simpan Coach</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT TUTOR -->
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="editModal = false" class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-heading font-black text-xl text-pepsi-navy">Edit Data Coach</h3>
                <button @click="editModal = false" class="text-slate-400 hover:text-slate-700">&times;</button>
            </div>

            <form :action="'<?= url('admin/tutors/update/') ?>' + editItem.id" method="POST" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Coach *</label>
                    <input type="text" name="name" x-model="editItem.name" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nomor WhatsApp</label>
                    <input type="tel" name="phone" x-model="editItem.phone" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Spesialisasi Mengajar</label>
                    <input type="text" name="specialization" x-model="editItem.specialization" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Tarif Gaji per Sesi (Rp) *</label>
                    <input type="number" name="fee_per_session" x-model="editItem.fee_per_session" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                </div>

                <div>
                    <label class="flex items-center gap-2 font-bold text-slate-700 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" :checked="editItem.is_active" class="rounded">
                        <span>Status Aktif Mengajar</span>
                    </label>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="editModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-pepsi-blue text-white font-bold">Perbarui Coach</button>
                </div>
            </form>
        </div>
    </div>

</div>
