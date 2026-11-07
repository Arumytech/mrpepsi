<?php
// Input Pengeluaran Operasional - form + daftar + master kategori
$payLabels = ['tunai' => 'Tunai', 'qris' => 'QRIS', 'transfer' => 'Transfer'];
?>
<div x-data="{ addModal: false, editModal: false, catModal: false, editCatModal: false, editItem: {}, editCat: {} }" class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-heading font-black text-2xl sm:text-3xl text-pepsi-navy">Input Pengeluaran Operasional</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Catat seluruh biaya operasional lembaga (listrik, internet, gaji, sewa, ATK, dan lainnya).
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="catModal = true" class="bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs sm:text-sm px-4 py-2.5 rounded-2xl shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.99 1.99 0 013 12V7a4 4 0 014-4z"/></svg>
                <span>Kelola Kategori</span>
            </button>
            <button @click="addModal = true" class="bg-pepsi-blue hover:bg-blue-900 text-white font-bold text-xs sm:text-sm px-5 py-2.5 rounded-2xl shadow-md flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Catat Pengeluaran</span>
            </button>
        </div>
    </div>

    <!-- Ringkasan -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Pengeluaran (Filter)</span>
            <span class="font-heading font-black text-2xl text-pepsi-red mt-1 block"><?= formatRupiah($summary['amount']) ?></span>
            <span class="text-[11px] text-slate-500"><?= (int)$summary['count'] ?> catatan pengeluaran</span>
        </div>
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Rata-rata per Pengeluaran</span>
            <span class="font-heading font-black text-2xl text-slate-900 mt-1 block"><?= formatRupiah($summary['count'] > 0 ? $summary['amount'] / $summary['count'] : 0) ?></span>
            <span class="text-[11px] text-slate-500">Rata-rata nominal</span>
        </div>
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Jumlah Kategori</span>
            <span class="font-heading font-black text-2xl text-pepsi-blue mt-1 block"><?= count($categories) ?></span>
            <a href="<?= url('admin/expenses/report') ?>" class="text-[11px] font-bold text-pepsi-blue hover:underline">Lihat laporan pengeluaran &rarr;</a>
        </div>
    </div>

    <!-- Filter -->
    <div class="bg-white rounded-2xl p-3.5 border border-slate-200 shadow-sm">
        <form method="GET" action="<?= url('admin/expenses') ?>" class="flex flex-wrap items-center gap-2">
            <div class="flex items-center gap-1.5 bg-white px-3 py-1.5 rounded-xl border border-slate-200 text-xs">
                <input type="date" name="start_date" value="<?= sanitize($filters['start_date']) ?>" class="font-bold text-slate-800 bg-transparent focus:outline-none">
                <span class="text-slate-400 font-bold">s/d</span>
                <input type="date" name="end_date" value="<?= sanitize($filters['end_date']) ?>" class="font-bold text-slate-800 bg-transparent focus:outline-none">
            </div>
            <select name="category_id" class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white focus:outline-none">
                <option value="0">-- Semua Kategori --</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= (int)$filters['category_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= sanitize($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="search" value="<?= sanitize($filters['search']) ?>" placeholder="Cari keterangan..." class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-medium text-slate-800 bg-white focus:outline-none">
            <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition">Filter</button>
            <a href="<?= url('admin/expenses') ?>" class="px-3 py-2 rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 text-xs font-bold transition">Reset</a>
        </form>
    </div>

    <!-- Tabel Pengeluaran -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-medium">
                <thead class="bg-pepsi-navy text-white text-[11px] uppercase tracking-wider font-bold">
                    <tr>
                        <th class="px-5 py-4">Tanggal</th>
                        <th class="px-5 py-4">Kategori</th>
                        <th class="px-5 py-4">Keterangan</th>
                        <th class="px-5 py-4">Metode</th>
                        <th class="px-5 py-4 text-right">Nominal</th>
                        <th class="px-5 py-4">Dicatat Oleh</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($expenses)): ?>
                    <tr>
                        <td colspan="7" class="px-5 py-10 text-center text-slate-400 italic">
                            Belum ada pengeluaran pada filter ini. Klik "Catat Pengeluaran" untuk menambahkan.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($expenses as $e): ?>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-5 py-4 whitespace-nowrap font-bold text-slate-700"><?= tanggalIndo($e['expense_date'], 'd M Y') ?></td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-pepsi-blue-light text-pepsi-blue">
                                <?= sanitize($e['category_name'] ?? 'Tanpa Kategori') ?>
                            </span>
                        </td>
                        <td class="px-5 py-4 text-slate-800">
                            <span class="font-semibold block"><?= sanitize($e['description']) ?></span>
                            <?php if (!empty($e['notes'])): ?>
                                <span class="text-[11px] text-slate-400 block"><?= sanitize($e['notes']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-slate-600 font-semibold"><?= $payLabels[$e['payment_method']] ?? ucfirst($e['payment_method']) ?></td>
                        <td class="px-5 py-4 whitespace-nowrap text-right font-heading font-black text-sm text-pepsi-red"><?= formatRupiah($e['amount']) ?></td>
                        <td class="px-5 py-4 whitespace-nowrap text-slate-500"><?= sanitize($e['created_by_name'] ?? '-') ?></td>
                        <td class="px-5 py-4 text-right whitespace-nowrap space-x-1">
                            <button @click="editItem = <?= htmlspecialchars(json_encode([
                                'id' => $e['id'],
                                'expense_date' => $e['expense_date'],
                                'category_id' => $e['category_id'] ? (int)$e['category_id'] : '',
                                'description' => $e['description'],
                                'amount' => (float)$e['amount'],
                                'payment_method' => $e['payment_method'],
                                'notes' => $e['notes'] ?? '',
                            ])) ?>; editModal = true;" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition">
                                Edit
                            </button>
                            <form action="<?= url('admin/expenses/delete/' . $e['id']) ?>" method="POST" class="inline" onsubmit="return confirm('Hapus pengeluaran ini?')">
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

    <!-- MODAL TAMBAH -->
    <div x-show="addModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="addModal = false" class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-heading font-black text-xl text-pepsi-navy">Catat Pengeluaran Operasional</h3>
                <button @click="addModal = false" class="text-slate-400 hover:text-slate-700">&times;</button>
            </div>

            <form action="<?= url('admin/expenses/store') ?>" method="POST" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Tanggal Pengeluaran *</label>
                    <input type="date" name="expense_date" value="<?= date('Y-m-d') ?>" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kategori</label>
                    <select name="category_id" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                        <option value="">-- Tanpa Kategori --</option>
                        <?php foreach ($categories as $c): ?>
                            <?php if ($c['is_active']): ?>
                                <option value="<?= (int)$c['id'] ?>"><?= sanitize($c['name']) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Keterangan / Uraian *</label>
                    <input type="text" name="description" required placeholder="Contoh: Bayar listrik bulan Juli" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nominal (Rp) *</label>
                    <input type="number" name="amount" min="1" required placeholder="0" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Metode Pembayaran</label>
                    <select name="payment_method" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                        <option value="tunai">Tunai</option>
                        <option value="qris">QRIS</option>
                        <option value="transfer">Transfer</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Catatan Tambahan</label>
                    <textarea name="notes" rows="2" placeholder="Opsional" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50"></textarea>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="addModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-pepsi-blue text-white font-bold">Simpan Pengeluaran</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT -->
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="editModal = false" class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-heading font-black text-xl text-pepsi-navy">Edit Pengeluaran</h3>
                <button @click="editModal = false" class="text-slate-400 hover:text-slate-700">&times;</button>
            </div>

            <form :action="'<?= url('admin/expenses/update/') ?>' + editItem.id" method="POST" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Tanggal Pengeluaran *</label>
                    <input type="date" name="expense_date" x-model="editItem.expense_date" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kategori</label>
                    <select name="category_id" x-model="editItem.category_id" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                        <option value="">-- Tanpa Kategori --</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int)$c['id'] ?>"><?= sanitize($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Keterangan / Uraian *</label>
                    <input type="text" name="description" x-model="editItem.description" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nominal (Rp) *</label>
                    <input type="number" name="amount" min="1" x-model="editItem.amount" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Metode Pembayaran</label>
                    <select name="payment_method" x-model="editItem.payment_method" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                        <option value="tunai">Tunai</option>
                        <option value="qris">QRIS</option>
                        <option value="transfer">Transfer</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Catatan Tambahan</label>
                    <textarea name="notes" rows="2" x-model="editItem.notes" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50"></textarea>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="editModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-pepsi-blue text-white font-bold">Perbarui</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL KELOLA KATEGORI -->
    <div x-show="catModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="catModal = false" class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-heading font-black text-xl text-pepsi-navy">Kelola Kategori Pengeluaran</h3>
                <button @click="catModal = false" class="text-slate-400 hover:text-slate-700">&times;</button>
            </div>

            <form action="<?= url('admin/expenses/store-category') ?>" method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-2 items-end text-xs bg-slate-50 p-3 rounded-2xl">
                <div class="sm:col-span-1">
                    <label class="block font-bold text-slate-700 mb-1">Nama Kategori *</label>
                    <input type="text" name="name" required placeholder="Contoh: Listrik" class="w-full rounded-xl border border-slate-200 p-2.5 bg-white">
                </div>
                <div class="sm:col-span-1">
                    <label class="block font-bold text-slate-700 mb-1">Deskripsi</label>
                    <input type="text" name="description" placeholder="Opsional" class="w-full rounded-xl border border-slate-200 p-2.5 bg-white">
                </div>
                <div>
                    <button type="submit" class="w-full px-4 py-2.5 rounded-xl bg-pepsi-blue text-white font-bold">Tambah</button>
                </div>
            </form>

            <div class="overflow-x-auto rounded-2xl border border-slate-200">
                <table class="w-full text-left text-xs font-medium">
                    <thead class="bg-slate-50 text-slate-500 uppercase font-bold text-[10px]">
                        <tr>
                            <th class="px-4 py-3">Nama Kategori</th>
                            <th class="px-4 py-3">Deskripsi</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($categories)): ?>
                            <tr><td colspan="4" class="p-6 text-center text-slate-400 italic">Belum ada kategori.</td></tr>
                        <?php else: ?>
                            <?php foreach ($categories as $c): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-bold text-slate-900"><?= sanitize($c['name']) ?></td>
                                <td class="px-4 py-3 text-slate-500"><?= sanitize($c['description'] ?? '-') ?></td>
                                <td class="px-4 py-3">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold <?= $c['is_active'] ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-400' ?>">
                                        <?= $c['is_active'] ? 'Aktif' : 'Non-Aktif' ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap space-x-1">
                                    <button @click="editCat = <?= htmlspecialchars(json_encode([
                                        'id' => $c['id'],
                                        'name' => $c['name'],
                                        'description' => $c['description'] ?? '',
                                        'is_active' => (bool)$c['is_active'],
                                    ])) ?>; editCatModal = true;" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition">Edit</button>
                                    <form action="<?= url('admin/expenses/delete-category/' . $c['id']) ?>" method="POST" class="inline" onsubmit="return confirm('Hapus kategori ini? Pengeluaran terkait akan menjadi tanpa kategori.')">
                                        <button type="submit" class="px-3 py-1.5 rounded-xl bg-red-50 hover:bg-red-100 text-pepsi-red font-bold transition">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end">
                <button type="button" @click="catModal = false" class="px-5 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs">Tutup</button>
            </div>
        </div>
    </div>

    <!-- MODAL EDIT KATEGORI -->
    <div x-show="editCatModal" x-cloak class="fixed inset-0 z-[60] overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="editCatModal = false" class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-heading font-black text-xl text-pepsi-navy">Edit Kategori</h3>
                <button @click="editCatModal = false" class="text-slate-400 hover:text-slate-700">&times;</button>
            </div>

            <form :action="'<?= url('admin/expenses/update-category/') ?>' + editCat.id" method="POST" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Kategori *</label>
                    <input type="text" name="name" x-model="editCat.name" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Deskripsi</label>
                    <input type="text" name="description" x-model="editCat.description" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>
                <div>
                    <label class="flex items-center gap-2 font-bold text-slate-700 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" :checked="editCat.is_active" class="rounded">
                        <span>Status Aktif</span>
                    </label>
                </div>
                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="editCatModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-pepsi-blue text-white font-bold">Perbarui</button>
                </div>
            </form>
        </div>
    </div>

</div>
