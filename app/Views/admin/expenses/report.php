<?php
// Laporan Pengeluaran Operasional
$payLabels = ['tunai' => 'Tunai', 'qris' => 'QRIS', 'transfer' => 'Transfer'];
$grandTotal = (float)$summary['amount'];
?>
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-heading font-black text-2xl sm:text-3xl text-pepsi-navy">Laporan Pengeluaran Operasional</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Rekapitulasi seluruh biaya operasional lembaga pada rentang tanggal terpilih.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <form method="GET" action="<?= url('admin/expenses/report') ?>" class="flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-1.5 bg-white px-3 py-1.5 rounded-2xl border border-slate-200 text-xs shadow-sm">
                    <span class="font-bold text-slate-500">Dari:</span>
                    <input type="date" name="start_date" value="<?= sanitize($startDate) ?>" class="font-bold text-slate-800 bg-transparent focus:outline-none">
                    <span class="font-bold text-slate-500">s/d:</span>
                    <input type="date" name="end_date" value="<?= sanitize($endDate) ?>" class="font-bold text-slate-800 bg-transparent focus:outline-none">
                </div>
                <select name="category_id" class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white focus:outline-none shadow-sm">
                    <option value="0">-- Semua Kategori --</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= (int)$categoryId === (int)$c['id'] ? 'selected' : '' ?>><?= sanitize($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="px-4 py-2 bg-pepsi-blue hover:bg-blue-900 text-white font-bold text-xs rounded-xl shadow-sm transition">
                    Filter
                </button>
            </form>
            <a href="<?= url('admin/expenses/report/pdf?start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate) . '&category_id=' . (int)$categoryId) ?>" target="_blank"
               class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl shadow-sm transition flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2h-2M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-6 0V4a2 2 0 012-2h2a2 2 0 012 2v1m-6 0h6"/></svg>
                <span>Cetak PDF</span>
            </a>
        </div>
    </div>

    <!-- Ringkasan -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Pengeluaran</span>
            <span class="font-heading font-black text-2xl text-pepsi-red mt-1 block"><?= formatRupiah($grandTotal) ?></span>
            <span class="text-[11px] text-slate-500">Periode <?= tanggalIndo($startDate, 'd M Y') ?> &ndash; <?= tanggalIndo($endDate, 'd M Y') ?></span>
        </div>
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Jumlah Catatan</span>
            <span class="font-heading font-black text-2xl text-slate-900 mt-1 block"><?= (int)$summary['count'] ?></span>
            <span class="text-[11px] text-slate-500">Transaksi pengeluaran</span>
        </div>
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Rata-rata per Pengeluaran</span>
            <span class="font-heading font-black text-2xl text-pepsi-blue mt-1 block"><?= formatRupiah($summary['average'] ?? 0) ?></span>
            <span class="text-[11px] text-slate-500">Rata-rata nominal</span>
        </div>
    </div>

    <!-- Rekap per Kategori -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 space-y-4">
        <h3 class="font-heading font-bold text-base text-slate-900">Rekapitulasi per Kategori</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-medium">
                <thead class="bg-slate-50 text-slate-500 uppercase font-bold text-[10px]">
                    <tr>
                        <th class="px-4 py-3">Kategori</th>
                        <th class="px-4 py-3 text-center">Jumlah</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3 text-right">Porsi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($categoryTotals)): ?>
                        <tr><td colspan="4" class="p-6 text-center text-slate-400 italic">Belum ada pengeluaran pada rentang tanggal ini.</td></tr>
                    <?php else: ?>
                        <?php foreach ($categoryTotals as $ct): ?>
                        <?php $porsi = $grandTotal > 0 ? round(((float)$ct['total_amount'] / $grandTotal) * 100, 1) : 0; ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-bold text-slate-900"><?= sanitize($ct['category_name']) ?></td>
                            <td class="px-4 py-3 text-center text-slate-600"><?= (int)$ct['total_count'] ?>x</td>
                            <td class="px-4 py-3 text-right font-bold text-pepsi-red"><?= formatRupiah($ct['total_amount']) ?></td>
                            <td class="px-4 py-3 text-right text-slate-500"><?= $porsi ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="bg-slate-900 text-white">
                        <td class="px-4 py-3 font-black uppercase text-[10px] tracking-wider" colspan="2">Total Pengeluaran</td>
                        <td class="px-4 py-3 text-right font-black"><?= formatRupiah($grandTotal) ?></td>
                        <td class="px-4 py-3 text-right font-bold">100%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Rincian Pengeluaran -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 space-y-4">
        <h3 class="font-heading font-bold text-base text-slate-900">Rincian Pengeluaran</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-medium">
                <thead class="bg-slate-50 text-slate-500 uppercase font-bold text-[10px]">
                    <tr>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Kategori</th>
                        <th class="px-4 py-3">Keterangan</th>
                        <th class="px-4 py-3">Metode</th>
                        <th class="px-4 py-3 text-right">Nominal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($expenses)): ?>
                        <tr><td colspan="5" class="p-6 text-center text-slate-400 italic">Belum ada data pengeluaran pada rentang tanggal ini.</td></tr>
                    <?php else: ?>
                        <?php foreach ($expenses as $e): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 whitespace-nowrap font-bold text-slate-700"><?= tanggalIndo($e['expense_date'], 'd M Y') ?></td>
                            <td class="px-4 py-3 whitespace-nowrap"><?= sanitize($e['category_name'] ?? 'Tanpa Kategori') ?></td>
                            <td class="px-4 py-3 text-slate-800"><?= sanitize($e['description']) ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-slate-600"><?= $payLabels[$e['payment_method']] ?? ucfirst($e['payment_method']) ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-right font-bold text-pepsi-red"><?= formatRupiah($e['amount']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="bg-slate-50">
                        <td colspan="4" class="px-4 py-3 text-right font-bold text-slate-700">TOTAL</td>
                        <td class="px-4 py-3 text-right font-black text-pepsi-red"><?= formatRupiah($grandTotal) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>
