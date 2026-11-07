<?php
// Admin Periods Management View
?>
<div x-data="{ addModal: false, editModal: false, editPer: {} }" class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-heading font-black text-2xl sm:text-3xl text-pepsi-navy">Periode Pendaftaran & Kursus</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Periode <b class="text-pepsi-blue">Tanggal 10</b> &amp; <b class="text-pepsi-red">Tanggal 25</b> dibuat otomatis setiap bulan berjalan (1 bulan ke belakang s/d 3 bulan ke depan) dan periode aktif otomatis berganti jika sudah lewat. Jika tanggal 10/25 jatuh pada Sabtu/Minggu, mulai belajar otomatis digeser ke Senin berikutnya dan selesai hari Jumat (2 minggu / 10 sesi). Periode bersifat otomatis sehingga <b>tidak dapat dihapus</b>.
            </p>
        </div>
        <button @click="addModal = true" class="bg-pepsi-blue hover:bg-blue-900 text-white font-bold text-xs sm:text-sm px-5 py-2.5 rounded-2xl shadow-md flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Tambah Periode Baru</span>
        </button>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-medium">
                <thead class="bg-pepsi-navy text-white text-[11px] uppercase tracking-wider font-bold">
                    <tr>
                        <th class="px-5 py-4">Nama Periode</th>
                        <th class="px-5 py-4">Tipe Periode</th>
                        <th class="px-5 py-4">Tanggal Mulai</th>
                        <th class="px-5 py-4">Tanggal Selesai</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($periods as $per): ?>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="font-bold text-slate-900 text-sm block"><?= sanitize($per['name']) ?></span>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold <?= $per['period_day'] == 10 ? 'bg-blue-100 text-pepsi-blue' : 'bg-red-100 text-pepsi-red' ?>">
                                Periode <?= $per['period_day'] ?>
                            </span>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-slate-700 font-semibold">
                            <?= sanitize(tanggalIndo($per['start_date'], 'd F Y')) ?>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-slate-700 font-semibold">
                            <?= sanitize(tanggalIndo($per['end_date'], 'd F Y')) ?>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <?php
                            $todayStr = date('Y-m-d');
                            if ($per['is_active']):
                            ?>
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 inline-flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-600 animate-pulse"></span>
                                    <span>Aktif Saat Ini</span>
                                </span>
                            <?php elseif ($per['start_date'] > $todayStr): ?>
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-pepsi-blue">
                                    Akan Dibuka
                                </span>
                            <?php elseif ($per['end_date'] >= $todayStr): ?>
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700">
                                    Sedang Berjalan
                                </span>
                            <?php else: ?>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500">
                                    Selesai
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-4 text-right whitespace-nowrap space-x-1">
                            <button @click="editPer = <?= htmlspecialchars(json_encode([
                                'id' => $per['id'],
                                'name' => $per['name'],
                                'period_day' => (int)$per['period_day'],
                                'start_date' => $per['start_date'],
                                'end_date' => $per['end_date'],
                                'is_active' => (bool)$per['is_active']
                            ])) ?>; editModal = true;" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition">
                                Edit
                            </button>
                            <?php if (!$per['is_active']): ?>
                            <a href="<?= url('admin/periods/activate/' . $per['id']) ?>" class="px-3 py-1.5 rounded-xl bg-green-50 hover:bg-green-100 text-green-700 font-bold transition">
                                Jadikan Aktif
                            </a>
                            <?php else: ?>
                            <span class="text-xs text-green-600 font-bold">&check; Utama</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL TAMBAH PERIODE -->
    <div x-show="addModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="addModal = false" class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-heading font-black text-xl text-pepsi-navy">Tambah Periode Baru</h3>
                <button @click="addModal = false" class="text-slate-400 hover:text-slate-700">&times;</button>
            </div>

            <form action="<?= url('admin/periods/store') ?>" method="POST" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Periode *</label>
                    <input type="text" name="name" required placeholder="Contoh: Periode 10 November 2026" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Tipe Periode (Hari) *</label>
                    <select name="period_day" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                        <option value="10">Periode 10 (Mulai Tgl 10)</option>
                        <option value="25">Periode 25 (Mulai Tgl 25)</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tanggal Mulai *</label>
                        <input type="date" name="start_date" required value="<?= date('Y-m-10') ?>" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tanggal Selesai *</label>
                        <input type="date" name="end_date" required value="<?= date('Y-m-24') ?>" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="addModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-pepsi-blue text-white font-bold">Simpan Periode</button>
                </div>
            </form>
        </div>
    </div>
    <!-- MODAL EDIT PERIODE -->
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="editModal = false" class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-heading font-black text-xl text-pepsi-navy">Edit Periode</h3>
                <button @click="editModal = false" class="text-slate-400 hover:text-slate-700">&times;</button>
            </div>

            <form :action="'<?= url('admin/periods/update/') ?>' + editPer.id" method="POST" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Periode *</label>
                    <input type="text" name="name" x-model="editPer.name" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Tipe Periode (Hari) *</label>
                    <select name="period_day" x-model.number="editPer.period_day" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                        <option value="10">Periode 10 (Mulai Tgl 10)</option>
                        <option value="25">Periode 25 (Mulai Tgl 25)</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tanggal Mulai *</label>
                        <input type="date" name="start_date" x-model="editPer.start_date" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tanggal Selesai *</label>
                        <input type="date" name="end_date" x-model="editPer.end_date" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                    </div>
                </div>
                <div>
                    <label class="flex items-center gap-2 font-bold text-slate-700 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" :checked="editPer.is_active" class="rounded">
                        <span>Jadikan Periode Aktif (hanya 1 yang aktif)</span>
                    </label>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="editModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-pepsi-blue text-white font-bold">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

</div>
