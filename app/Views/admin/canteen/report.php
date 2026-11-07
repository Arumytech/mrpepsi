<?php
// Admin Canteen Profit & Loss Report View — Laporan Pendapatan & Pengeluaran Pepsi Food & Drink
$canteenExpenseRows = $canteenExpenses['rows'] ?? [];
?>
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-heading font-black text-2xl sm:text-3xl text-pepsi-navy">Laporan Pendapatan &amp; Pengeluaran Pepsi Food &amp; Drink</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Rekap omset penjualan, modal (HPP), pengeluaran operasional, dan laba bersih akhir Pepsi Food &amp; Drink.
            </p>
        </div>

        <!-- Filter Date Range -->
        <div class="flex flex-wrap items-center gap-2">
            <form method="GET" action="<?= url('admin/canteen/report') ?>" class="flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-1.5 bg-white px-3 py-1.5 rounded-2xl border border-slate-200 text-xs shadow-sm">
                    <span class="font-bold text-slate-500">Dari:</span>
                    <input type="date" name="start_date" value="<?= $startDate ?>" class="font-bold text-slate-800 bg-transparent focus:outline-none">
                    <span class="font-bold text-slate-500">s/d:</span>
                    <input type="date" name="end_date" value="<?= $endDate ?>" class="font-bold text-slate-800 bg-transparent focus:outline-none">
                </div>
                <button type="submit" class="px-4 py-2 bg-pepsi-blue hover:bg-blue-900 text-white font-bold text-xs rounded-xl shadow-sm transition">
                    Filter
                </button>
            </form>
            <a href="<?= url('admin/canteen/report/pdf?start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate)) ?>" target="_blank"
               class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl shadow-sm transition flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2h-2M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-6 0V4a2 2 0 012-2h2a2 2 0 012 2v1m-6 0h6"/></svg>
                <span>Cetak PDF</span>
            </a>
        </div>
    </div>

    <!-- Profit Summary Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Pendapatan (Omset)</span>
            <span class="font-heading font-black text-2xl text-slate-900 mt-1 block"><?= formatRupiah($summary['total_revenue']) ?></span>
            <span class="text-[11px] text-slate-500"><?= $summary['total_orders'] ?> Transaksi berhasil</span>
            <span class="text-[11px] text-slate-600 block mt-1">
                POS Kasir: <b><?= formatRupiah($summary['total_pos'] ?? $summary['total_revenue']) ?></b>
                &middot; Penjualan Tambahan: <b class="text-amber-600"><?= formatRupiah($summary['total_manual'] ?? 0) ?></b>
            </span>
        </div>

        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Modal / HPP</span>
            <span class="font-heading font-black text-2xl text-slate-600 mt-1 block"><?= formatRupiah($summary['total_cost']) ?></span>
            <span class="text-[11px] text-slate-400">Harga Pokok Penjualan</span>
        </div>

        <div class="bg-white p-5 rounded-3xl border border-emerald-200 shadow-sm">
            <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider block">Laba Kotor Penjualan</span>
            <span class="font-heading font-black text-2xl text-emerald-700 mt-1 block"><?= formatRupiah($grossProfit) ?></span>
            <span class="text-[11px] text-emerald-600 font-bold">
                Margin Kotor: <?= $grossMargin ?>%
            </span>
        </div>
    </div>

    <!-- Row 2: Pengeluaran Operasional Pepsi Food & Drink & Laba Bersih Akhir -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <div class="bg-white p-5 rounded-3xl border border-red-200 shadow-sm">
            <span class="text-xs font-bold text-red-500 uppercase tracking-wider block">Pengeluaran Operasional Pepsi Food &amp; Drink</span>
            <span class="font-heading font-black text-2xl text-red-600 mt-1 block"><?= formatRupiah($canteenExpenseTotal) ?></span>
            <span class="text-[11px] text-red-400"><?= (int)($canteenExpenses['count'] ?? 0) ?> item pengeluaran</span>
        </div>

        <div class="bg-gradient-to-br from-emerald-50 to-green-50 p-5 rounded-3xl border border-emerald-300 shadow-sm">
            <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider block">Laba Bersih Akhir Pepsi Food &amp; Drink</span>
            <span class="font-heading font-black text-2xl <?= $netProfit >= 0 ? 'text-emerald-800' : 'text-red-700' ?> mt-1 block"><?= formatRupiah($netProfit) ?></span>
            <span class="text-[11px] font-bold <?= $netMargin >= 0 ? 'text-emerald-600' : 'text-red-500' ?>">
                Margin Bersih: <?= $netMargin ?>%
            </span>
            <span class="text-[10px] text-slate-400 block mt-1">= Laba Kotor − Pengeluaran Operasional Pepsi Food &amp; Drink</span>
        </div>

        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Metode Pembayaran</span>
            <div class="mt-2 text-xs space-y-1">
                <div class="flex justify-between">
                    <span class="text-slate-500">Tunai &amp; Cash:</span>
                    <span class="font-bold text-slate-900"><?= formatRupiah($summary['total_cash']) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">QRIS:</span>
                    <span class="font-bold text-slate-900"><?= formatRupiah($summary['total_qris'] ?? 0) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Products Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 space-y-4">
        <h3 class="font-heading font-bold text-base text-slate-900">10 Produk Pepsi Food &amp; Drink Paling Laris</h3>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-medium">
                <thead class="bg-slate-50 text-slate-500 uppercase font-bold text-[10px]">
                    <tr>
                        <th class="px-4 py-3">Nama Produk</th>
                        <th class="px-4 py-3">Jumlah Terjual</th>
                        <th class="px-4 py-3">Total Penjualan</th>
                        <th class="px-4 py-3 text-right">Kontribusi Laba</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($topProducts)): ?>
                    <tr><td colspan="4" class="p-6 text-center text-slate-400 italic">Belum ada transaksi pada rentang tanggal ini.</td></tr>
                    <?php else: ?>
                    <?php foreach ($topProducts as $tp): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-bold text-slate-900"><?= sanitize($tp['product_name']) ?></td>
                        <td class="px-4 py-3 font-bold text-pepsi-blue"><?= $tp['total_qty'] ?> pcs</td>
                        <td class="px-4 py-3 text-slate-700"><?= formatRupiah($tp['total_sales']) ?></td>
                        <td class="px-4 py-3 text-right font-bold text-green-700">+<?= formatRupiah($tp['total_profit']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Rincian Pengeluaran Operasional Pepsi Food & Drink -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <h3 class="font-heading font-bold text-base text-slate-900">Rincian Pengeluaran Operasional Pepsi Food &amp; Drink</h3>
            <a href="<?= url('admin/expenses?category_id=9') ?>" class="px-4 py-2 bg-red-50 hover:bg-red-100 text-red-700 font-bold text-xs rounded-xl border border-red-200 transition flex items-center gap-1.5 w-fit">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                <span>Catat Pengeluaran Pepsi Food &amp; Drink</span>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-medium">
                <thead class="bg-red-50 text-red-600 uppercase font-bold text-[10px]">
                    <tr>
                        <th class="px-4 py-3 w-10">No</th>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Keterangan</th>
                        <th class="px-4 py-3">Metode</th>
                        <th class="px-4 py-3 text-right">Nominal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($canteenExpenseRows)): ?>
                    <tr><td colspan="5" class="p-6 text-center text-slate-400 italic">Belum ada pengeluaran operasional Pepsi Food &amp; Drink pada periode ini.</td></tr>
                    <?php else: ?>
                    <?php foreach ($canteenExpenseRows as $idx => $ce): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 text-slate-400"><?= $idx + 1 ?></td>
                        <td class="px-4 py-3 text-slate-700 font-semibold"><?= date('d/m/Y', strtotime($ce['expense_date'])) ?></td>
                        <td class="px-4 py-3 text-slate-900">
                            <?= sanitize($ce['description']) ?>
                            <?php if (!empty($ce['notes'])): ?>
                                <span class="text-slate-400 text-[10px]">(<?= sanitize($ce['notes']) ?>)</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase
                                <?= ($ce['payment_method'] ?? 'tunai') === 'tunai' ? 'bg-green-100 text-green-700' : (($ce['payment_method'] ?? '') === 'qris' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700') ?>">
                                <?= ucfirst($ce['payment_method'] ?? 'tunai') ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-red-600">-<?= formatRupiah($ce['amount']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="bg-red-50">
                        <td colspan="4" class="px-4 py-3 text-right font-black text-red-700 uppercase text-[11px]">Total Pengeluaran Operasional Pepsi Food &amp; Drink</td>
                        <td class="px-4 py-3 text-right font-black text-red-700"><?= formatRupiah($canteenExpenseTotal) ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
