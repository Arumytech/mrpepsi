<?php
// Bersihkan Database — Form pembersihan tabel dengan verifikasi password admin.
$allTables = [];
foreach ($groups as $items) {
    foreach ($items as $t) {
        $allTables[] = ['id' => $t['name'], 'label' => $t['label'], 'rows' => (int)$t['rows']];
    }
}
$totalRows = array_sum(array_map(fn($t) => (int)$t['rows'], $allTables));
$totalTables = count($allTables);
?>
<div class="space-y-6" x-data="dbCleaner(<?= sanitize(json_encode($allTables)) ?>)">

    <div>
        <h1 class="font-heading font-black text-2xl sm:text-3xl text-pepsi-navy">Bersihkan Database</h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-1">
            Pilih tabel yang ingin dikosongkan (reset data), lalu verifikasi dengan password admin Anda.
        </p>
    </div>

    <!-- Peringatan -->
    <div class="p-4 sm:p-5 rounded-3xl bg-red-50 border border-red-200 flex items-start gap-3">
        <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
        </svg>
        <div class="text-xs text-red-800 space-y-1">
            <p class="font-bold">Data yang dihapus tidak dapat dikembalikan!</p>
            <p>Isi tabel terpilih akan dikosongkan total beserta penomoran otomatisnya. Pastikan Anda sudah memiliki cadangan (backup) bila perlu. Tabel akun &amp; pengaturan sistem selalu dilindungi.</p>
        </div>
    </div>

    <!-- Ringkasan -->
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs">
        <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-sm">
            <span class="text-slate-400 block font-semibold">Tabel Dapat Dibersihkan</span>
            <span class="font-heading font-black text-2xl text-pepsi-navy block mt-1"><?= $totalTables ?></span>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-sm">
            <span class="text-slate-400 block font-semibold">Total Baris Data</span>
            <span class="font-heading font-black text-2xl text-pepsi-navy block mt-1"><?= number_format($totalRows, 0, ',', '.') ?></span>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-sm col-span-2 sm:col-span-1">
            <span class="text-slate-400 block font-semibold">Terpilih</span>
            <span class="font-heading font-black text-2xl text-red-600 block mt-1" x-text="selected.length"></span>
        </div>
    </div>

    <form action="<?= url('admin/database/clean') ?>" method="POST" class="space-y-6">

        <!-- Pilih semua -->
        <div class="flex items-center justify-between bg-white rounded-2xl border border-slate-200 shadow-sm px-5 py-3.5">
            <label class="flex items-center gap-2.5 text-xs font-bold text-slate-700 cursor-pointer select-none">
                <input type="checkbox" @change="toggleAll($event)" :checked="allChecked" class="w-4 h-4 rounded accent-pepsi-red">
                <span>Pilih semua tabel</span>
            </label>
            <button type="button" @click="selected = []" class="text-[11px] font-bold text-slate-400 hover:text-slate-700 transition">Reset pilihan</button>
        </div>

        <!-- Daftar tabel per kelompok -->
        <?php foreach ($groups as $groupName => $items): ?>
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-heading font-bold text-sm text-slate-900"><?= sanitize($groupName) ?></h3>
                <span class="text-[10px] font-bold text-slate-400"><?= count($items) ?> tabel</span>
            </div>
            <div class="divide-y divide-slate-100">
                <?php foreach ($items as $t): ?>
                <label class="flex items-center justify-between gap-4 px-5 py-3.5 hover:bg-slate-50/70 cursor-pointer transition">
                    <span class="flex items-center gap-3 min-w-0">
                        <input type="checkbox" name="tables[]" value="<?= sanitize($t['name']) ?>" x-model="selected" class="w-4 h-4 rounded accent-pepsi-red flex-shrink-0">
                        <span class="min-w-0">
                            <span class="block text-xs font-bold text-slate-800 truncate"><?= sanitize($t['label']) ?></span>
                            <span class="block text-[10px] font-mono text-slate-400"><?= sanitize($t['name']) ?></span>
                        </span>
                    </span>
                    <span class="text-[11px] font-bold text-slate-500 bg-slate-100 rounded-full px-2.5 py-1 flex-shrink-0">
                        <?= number_format((int)$t['rows'], 0, ',', '.') ?> baris
                    </span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (!empty($protected)): ?>
        <!-- Tabel terlindungi -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-heading font-bold text-sm text-slate-900">Sistem (Dilindungi)</h3>
                <span class="text-[10px] font-bold text-slate-400">Tidak dapat dibersihkan</span>
            </div>
            <div class="divide-y divide-slate-100">
                <?php foreach ($protected as $t): ?>
                <div class="flex items-center justify-between gap-4 px-5 py-3.5 opacity-70">
                    <span class="flex items-center gap-3 min-w-0">
                        <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <span class="min-w-0">
                            <span class="block text-xs font-bold text-slate-600 truncate"><?= sanitize($t['label']) ?></span>
                            <span class="block text-[10px] font-mono text-slate-400"><?= sanitize($t['name']) ?></span>
                        </span>
                    </span>
                    <span class="text-[11px] font-bold text-slate-400 bg-slate-100 rounded-full px-2.5 py-1 flex-shrink-0">
                        <?= number_format((int)$t['rows'], 0, ',', '.') ?> baris
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tombol aksi -->
        <div class="sticky bottom-4 bg-white rounded-2xl border border-slate-200 shadow-lg px-5 py-4 flex flex-col sm:flex-row items-center justify-between gap-3">
            <span class="text-xs font-semibold text-slate-500">
                <b class="text-slate-800" x-text="selected.length"></b> tabel dipilih untuk dibersihkan
            </span>
            <button type="button" @click="openVerify()"
                    :disabled="selected.length === 0"
                    :class="selected.length === 0 ? 'bg-slate-300 cursor-not-allowed' : 'bg-red-600 hover:bg-red-700 shadow-red-600/30'"
                    class="w-full sm:w-auto px-6 py-3 rounded-xl text-white font-bold text-sm shadow-lg transition">
                Bersihkan Tabel Terpilih
            </button>
        </div>

        <!-- MODAL VERIFIKASI ADMIN -->
        <div x-show="showVerify" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div @click.away="closeVerify()" class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="font-heading font-black text-xl text-pepsi-navy">Verifikasi Admin</h3>
                    <button type="button" @click="closeVerify()" class="text-slate-400 hover:text-slate-700 text-xl leading-none">&times;</button>
                </div>

                <div class="p-4 rounded-2xl bg-red-50 border border-red-100 text-xs text-red-800 space-y-1">
                    <p class="font-bold">Anda akan menghapus permanen isi <span x-text="selected.length"></span> tabel:</p>
                    <ul class="list-disc list-inside max-h-32 overflow-y-auto font-semibold">
                        <template x-for="id in selected" :key="id">
                            <li x-text="labelOf(id)"></li>
                        </template>
                    </ul>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Password Admin *</label>
                        <input type="password" name="password" x-model="password" autocomplete="off"
                               placeholder="Masukkan password akun Anda"
                               class="w-full rounded-xl border border-slate-300 p-3 bg-slate-50 font-bold">
                    </div>

                    <label class="flex items-start gap-2.5 p-3 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer select-none">
                        <input type="checkbox" name="confirm" value="1" x-model="confirmed" class="w-4 h-4 rounded accent-red-600 mt-0.5">
                        <span class="text-slate-700 font-semibold">Saya memahami bahwa data yang dipilih akan dihapus permanen dan tidak dapat dikembalikan.</span>
                    </label>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" @click="confirmSubmit($event)"
                            :disabled="!confirmed || password.length === 0"
                            :class="(!confirmed || password.length === 0) ? 'bg-slate-300 cursor-not-allowed' : 'bg-red-600 hover:bg-red-700 shadow-red-600/30'"
                            class="flex-1 py-3.5 rounded-2xl text-white font-bold text-sm shadow-lg transition">
                        HAPUS PERMANEN
                    </button>
                    <button type="button" @click="closeVerify()" class="px-5 py-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm rounded-2xl transition">
                        Batal
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function dbCleaner(tables) {
    return {
        tables: tables || [],
        selected: [],
        showVerify: false,
        password: '',
        confirmed: false,
        get allChecked() {
            return this.tables.length > 0 && this.selected.length === this.tables.length;
        },
        toggleAll(e) {
            this.selected = e.target.checked ? this.tables.map(t => t.id) : [];
        },
        labelOf(id) {
            const t = this.tables.find(x => x.id === id);
            return t ? (t.label + ' (' + t.rows + ' baris)') : id;
        },
        openVerify() {
            if (this.selected.length === 0) return;
            this.password = '';
            this.confirmed = false;
            this.showVerify = true;
        },
        closeVerify() {
            this.showVerify = false;
        },
        confirmSubmit(e) {
            if (!this.confirmed || this.password.length === 0) {
                e.preventDefault();
                return;
            }
            if (!confirm('Yakin menghapus permanen isi ' + this.selected.length + ' tabel terpilih?')) {
                e.preventDefault();
            }
        }
    };
}
</script>
