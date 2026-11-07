<?php
// Admin Panel: Laporan Bulanan Pepsi Food & Drink (Berlanjut Tiap Bulan)
$cur = $current;
?>

<div x-data="{ balanceModal: false }" class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-xl bg-orange-100 text-orange-800 text-[10px] font-black uppercase tracking-wider">
                    Pepsi Food &amp; Drink
                </span>
                <span class="px-2.5 py-1 rounded-xl bg-blue-100 text-pepsi-blue text-[10px] font-black uppercase tracking-wider">
                    Sistem Berlanjut Tiap Bulan
                </span>
            </div>
            <h1 class="font-heading font-black text-2xl sm:text-3xl text-pepsi-navy mt-1">Laporan Bulanan Pepsi Food &amp; Drink</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                Rekapitulasi keuangan Pepsi Food &amp; Drink 1 bulan sekali: saldo bulan lalu/saldo awal, omset, HPP, operasional, laba bersih, dan saldo akhir berlanjut.
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap items-center gap-2">
            <button @click="balanceModal = true" class="px-4 py-2.5 rounded-2xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs shadow-md transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                <span>Atur Saldo Awal / Modal</span>
            </button>
            <a href="<?= url('admin/canteen/monthly-report/pdf?month=' . urlencode($yearMonth)) ?>" target="_blank" class="px-4 py-2.5 rounded-2xl bg-pepsi-blue hover:bg-blue-900 text-white font-bold text-xs shadow-md transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Unduh PDF Resmi</span>
            </a>
            <a href="<?= url('admin/reports/canteen-monthly?month=' . urlencode($yearMonth)) ?>" target="_blank" class="px-4 py-2.5 rounded-2xl bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs shadow-md transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Cetak A4</span>
            </a>
        </div>
    </div>

    <!-- Month Navigation Bar -->
    <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
        <!-- Prev / Next Month Nav -->
        <div class="flex items-center gap-2 w-full md:w-auto">
            <a href="<?= url('admin/canteen/monthly-report?month=' . urlencode($prevMonth)) ?>" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition flex items-center gap-1.5 shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                <span>Bulan Sebelumnya</span>
            </a>
            <div class="px-4 py-2 rounded-xl bg-pepsi-navy text-white font-heading font-black text-xs sm:text-sm text-center flex-1 md:flex-initial">
                <?= $monthLabel ?>
            </div>
            <a href="<?= url('admin/canteen/monthly-report?month=' . urlencode($nextMonth)) ?>" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition flex items-center gap-1.5 shadow-xs">
                <span>Bulan Berikutnya</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        <!-- Jump to Specific Month Select -->
        <form method="GET" action="<?= url('admin/canteen/monthly-report') ?>" class="flex items-center gap-2 w-full md:w-auto">
            <label class="text-xs font-bold text-slate-500 whitespace-nowrap">Pilih Bulan:</label>
            <input type="month" name="month" value="<?= $yearMonth ?>" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold bg-slate-50 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-pepsi-blue">
            <button type="submit" class="px-3.5 py-2 rounded-xl bg-pepsi-blue hover:bg-blue-900 text-white font-bold text-xs transition">
                Tampilkan
            </button>
        </form>
    </div>

    <!-- Continuity Statement Banner -->
    <div class="bg-gradient-to-r from-blue-900 via-pepsi-navy to-indigo-950 text-white p-5 rounded-3xl shadow-md">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="text-xs font-black uppercase tracking-wider text-blue-200">Kesinambungan Saldo Berlanjut (Rolling Balance)</span>
                </div>
                <div class="text-sm sm:text-base font-bold text-slate-100">
                    Periode: <span class="text-white font-extrabold"><?= $monthLabel ?></span> (<?= date('01/m/Y', strtotime($cur['start_date'])) ?> &mdash; <?= date('d/m/Y', strtotime($cur['end_date'])) ?>)
                </div>
                <p class="text-xs text-slate-300">
                    Saldo awal diambil otomatis dari saldo akhir bulan sebelumnya. Saldo akhir bulan ini akan otomatis menjadi saldo awal bulan depan.
                </p>
            </div>
            <div class="flex items-center gap-3 bg-white/10 backdrop-blur-md px-4 py-3 rounded-2xl border border-white/20">
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-300 block">Saldo Akhir Berjalan Bulan Ini</span>
                    <span class="font-heading font-black text-xl sm:text-2xl <?= $cur['closing_balance'] >= 0 ? 'text-emerald-400' : 'text-rose-400' ?>">
                        <?= formatRupiah($cur['closing_balance']) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- 7 Metric Cards Section -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Saldo Bulan Lalu / Saldo Awal -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">1. Saldo Bulan Lalu</span>
                    <span class="text-xs font-semibold text-slate-500">(Saldo Awal Bulan Ini)</span>
                </div>
                <span class="p-2 rounded-xl bg-blue-50 text-pepsi-blue">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <span class="font-heading font-black text-xl sm:text-2xl text-slate-900 block">
                    <?= formatRupiah($cur['opening_balance']) ?>
                </span>
                <?php if ($cur['is_manual_opening']): ?>
                    <span class="inline-block mt-1 text-[10px] font-extrabold text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full">
                        ⚠️ Saldo Diatur Manual <?= $cur['manual_note'] ? '(' . sanitize($cur['manual_note']) . ')' : '' ?>
                    </span>
                <?php else: ?>
                    <span class="text-[10px] text-emerald-600 font-bold mt-1 block">
                        &check; Otomatis dari Saldo Akhir Bulan Lalu
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- 2. Omset Penjualan Pepsi Food & Drink -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">2. Total Omset</span>
                    <span class="text-xs font-semibold text-slate-500">Pendapatan Penjualan</span>
                </div>
                <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <span class="font-heading font-black text-xl sm:text-2xl text-emerald-600 block">
                    + <?= formatRupiah($cur['revenue']) ?>
                </span>
                <span class="text-[10px] text-slate-500 mt-1 block">
                    <?= $cur['total_orders'] ?> Transaksi (Kasir: <?= formatRupiah($cur['total_pos']) ?>)
                </span>
            </div>
        </div>

        <!-- 3. Modal Pokok / HPP Terjual -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">3. Total HPP Produk</span>
                    <span class="text-xs font-semibold text-slate-500">Modal Barang Terjual</span>
                </div>
                <span class="p-2 rounded-xl bg-rose-50 text-rose-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <span class="font-heading font-black text-xl sm:text-2xl text-rose-600 block">
                    - <?= formatRupiah($cur['cost']) ?>
                </span>
                <span class="text-[10px] text-slate-500 mt-1 block">
                    Laba Kotor: <b><?= formatRupiah($cur['gross_profit']) ?></b> (<?= $cur['gross_margin'] ?>%)
                </span>
            </div>
        </div>

        <!-- 4. Pengeluaran Operasional Pepsi Food & Drink -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">4. Operasional Pepsi Food &amp; Drink</span>
                    <span class="text-xs font-semibold text-slate-500">Beban Belanja &amp; Operasional</span>
                </div>
                <span class="p-2 rounded-xl bg-amber-50 text-amber-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <span class="font-heading font-black text-xl sm:text-2xl text-amber-700 block">
                    - <?= formatRupiah($cur['expense_total']) ?>
                </span>
                <span class="text-[10px] text-slate-500 mt-1 block">
                    <?= count($cur['expenses']) ?> Item Pengeluaran Operasional
                </span>
            </div>
        </div>
    </div>

    <!-- Secondary Row: Laba Bersih & Saldo Akhir -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Laba Bersih Bulan Ini -->
        <div class="p-6 rounded-3xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-emerald-800 uppercase tracking-wider block">Laba Bersih Bulan Ini (Net Profit)</span>
                <div class="font-heading font-black text-2xl sm:text-3xl text-emerald-700">
                    <?= formatRupiah($cur['net_profit']) ?>
                </div>
                <p class="text-xs text-slate-500">
                    Rumus: <b>Omset (<?= formatRupiah($cur['revenue']) ?>)</b> &minus; <b>HPP (<?= formatRupiah($cur['cost']) ?>)</b> &minus; <b>Operasional (<?= formatRupiah($cur['expense_total']) ?>)</b>
                </p>
            </div>
            <div class="text-right">
                <span class="text-xs text-slate-400 block font-semibold">Net Profit Margin</span>
                <span class="font-heading font-black text-2xl text-emerald-600 block"><?= $cur['net_margin'] ?>%</span>
            </div>
        </div>

        <!-- Saldo Akhir Bulan Ini (Berlanjut) -->
        <div class="p-6 rounded-3xl border border-blue-200 bg-gradient-to-br from-blue-50 to-white shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-pepsi-blue uppercase tracking-wider block">Saldo Akhir Berjalan (Closing Balance)</span>
                <div class="font-heading font-black text-2xl sm:text-3xl text-pepsi-navy">
                    <?= formatRupiah($cur['closing_balance']) ?>
                </div>
                <p class="text-xs text-slate-500">
                    Rumus: <b>Saldo Awal (<?= formatRupiah($cur['opening_balance']) ?>)</b> + <b>Laba Bersih (<?= formatRupiah($cur['net_profit']) ?>)</b>
                </p>
            </div>
            <div class="text-right">
                <span class="text-[10px] text-blue-700 font-bold bg-blue-100 px-2.5 py-1 rounded-full uppercase tracking-wider block">
                    Berlanjut ke Bulan Depan &rarr;
                </span>
            </div>
        </div>
    </div>

    <!-- TABEL 1: Rekapitulasi Berkelanjutan Antar Bulan (Continuous Rolling Monthly Ledger) -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h3 class="font-heading font-black text-base text-pepsi-navy">Rekapitulasi Saldo Berlanjut Tiap Bulan (Continuous Rolling Ledger)</h3>
                <p class="text-xs text-slate-400">Tabel kesinambungan saldo antar bulan — saldo akhir tiap bulan menjadi saldo awal bulan berikutnya.</p>
            </div>
            <span class="text-xs text-slate-500 font-semibold bg-slate-100 px-3 py-1 rounded-xl">
                <?= count($rolling) ?> Bulan Terdata
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-medium">
                <thead class="bg-pepsi-navy text-white text-[11px] uppercase tracking-wider font-bold">
                    <tr>
                        <th class="px-5 py-3.5">Periode Bulan</th>
                        <th class="px-5 py-3.5 text-right">Saldo Awal (Bulan Lalu)</th>
                        <th class="px-5 py-3.5 text-right">Omset Penjualan</th>
                        <th class="px-5 py-3.5 text-right">HPP Terjual</th>
                        <th class="px-5 py-3.5 text-right">Laba Kotor</th>
                        <th class="px-5 py-3.5 text-right">Operasional</th>
                        <th class="px-5 py-3.5 text-right">Laba Bersih</th>
                        <th class="px-5 py-3.5 text-right">Saldo Akhir (Berlanjut)</th>
                        <th class="px-5 py-3.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($rolling as $ym => $row): 
                        $isActive = ($ym === $yearMonth);
                    ?>
                    <tr class="transition <?= $isActive ? 'bg-blue-50/70 font-bold' : 'hover:bg-slate-50' ?>">
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <?php if ($isActive): ?>
                                    <span class="w-2 h-2 rounded-full bg-pepsi-blue"></span>
                                <?php endif; ?>
                                <span class="font-bold text-slate-900"><?= $row['month_label'] ?></span>
                                <?php if ($isActive): ?>
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-pepsi-blue text-white uppercase">Aktif</span>
                                <?php endif; ?>
                            </div>
                            <span class="text-[10px] text-slate-400 font-mono"><?= $ym ?></span>
                        </td>
                        <td class="px-5 py-3.5 text-right whitespace-nowrap font-mono">
                            <span class="font-bold text-slate-800"><?= formatRupiah($row['opening_balance']) ?></span>
                            <?php if ($row['is_manual_opening']): ?>
                                <span class="block text-[9px] text-amber-600 font-bold">Koreksi Manual</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-3.5 text-right whitespace-nowrap font-mono text-emerald-600 font-bold">
                            + <?= formatRupiah($row['revenue']) ?>
                        </td>
                        <td class="px-5 py-3.5 text-right whitespace-nowrap font-mono text-rose-500">
                            - <?= formatRupiah($row['cost']) ?>
                        </td>
                        <td class="px-5 py-3.5 text-right whitespace-nowrap font-mono text-slate-700">
                            <?= formatRupiah($row['gross_profit']) ?>
                        </td>
                        <td class="px-5 py-3.5 text-right whitespace-nowrap font-mono text-amber-700">
                            - <?= formatRupiah($row['expense_total']) ?>
                        </td>
                        <td class="px-5 py-3.5 text-right whitespace-nowrap font-mono font-black <?= $row['net_profit'] >= 0 ? 'text-emerald-700' : 'text-rose-700' ?>">
                            <?= formatRupiah($row['net_profit']) ?>
                        </td>
                        <td class="px-5 py-3.5 text-right whitespace-nowrap font-mono font-black text-pepsi-navy bg-slate-50/50">
                            <?= formatRupiah($row['closing_balance']) ?>
                        </td>
                        <td class="px-5 py-3.5 text-center whitespace-nowrap">
                            <a href="<?= url('admin/canteen/monthly-report?month=' . urlencode($ym)) ?>" class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition <?= $isActive ? 'bg-pepsi-blue text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                                <?= $isActive ? 'Sedang Dilihat' : 'Lihat Bulan Ini' ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Grid Detail: Penjualan Harian & Pengeluaran Pepsi Food & Drink -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Penjualan Harian Bulan Ini -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="font-heading font-black text-sm text-pepsi-navy">Rincian Penjualan Harian</h3>
                    <p class="text-[11px] text-slate-400">Aktivitas penjualan per tanggal di bulan <?= $monthLabel ?></p>
                </div>
                <span class="text-xs font-bold text-slate-500 bg-slate-100 px-2.5 py-1 rounded-xl">
                    <?= count($cur['daily_sales']) ?> Hari Ada Penjualan
                </span>
            </div>

            <div class="overflow-y-auto max-h-96 flex-1">
                <?php if (empty($cur['daily_sales'])): ?>
                    <div class="p-8 text-center text-slate-400 text-xs italic">
                        Belum ada data penjualan tercatat pada bulan ini.
                    </div>
                <?php else: ?>
                    <table class="w-full text-left text-xs font-medium">
                        <thead class="bg-slate-50 text-slate-600 text-[10px] uppercase font-bold sticky top-0">
                            <tr>
                                <th class="px-4 py-2.5">Tanggal</th>
                                <th class="px-4 py-2.5 text-center">Transaksi</th>
                                <th class="px-4 py-2.5 text-right">Omset</th>
                                <th class="px-4 py-2.5 text-right">Laba Kotor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($cur['daily_sales'] as $ds): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-4 py-2.5 whitespace-nowrap font-bold text-slate-800">
                                    <?= date('d/m/Y', strtotime($ds['sale_date'])) ?>
                                </td>
                                <td class="px-4 py-2.5 text-center whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded-lg bg-blue-50 text-pepsi-blue text-[10px] font-bold">
                                        <?= (int)$ds['orders_count'] ?> order
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-right whitespace-nowrap font-mono text-emerald-600 font-bold">
                                    <?= formatRupiah($ds['daily_revenue']) ?>
                                </td>
                                <td class="px-4 py-2.5 text-right whitespace-nowrap font-mono text-slate-700 font-bold">
                                    <?= formatRupiah($ds['daily_profit']) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Rincian Pengeluaran Operasional Pepsi Food & Drink -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="font-heading font-black text-sm text-pepsi-navy">Pengeluaran Operasional Pepsi Food &amp; Drink</h3>
                    <p class="text-[11px] text-slate-400">Beban operasional &amp; belanja Pepsi Food &amp; Drink bulan <?= $monthLabel ?></p>
                </div>
                <span class="text-xs font-bold text-amber-700 bg-amber-50 px-2.5 py-1 rounded-xl">
                    Total: <?= formatRupiah($cur['expense_total']) ?>
                </span>
            </div>

            <div class="overflow-y-auto max-h-96 flex-1">
                <?php if (empty($cur['expenses'])): ?>
                    <div class="p-8 text-center text-slate-400 text-xs italic">
                        Tidak ada pengeluaran operasional Pepsi Food &amp; Drink tercatat pada bulan ini.
                    </div>
                <?php else: ?>
                    <table class="w-full text-left text-xs font-medium">
                        <thead class="bg-slate-50 text-slate-600 text-[10px] uppercase font-bold sticky top-0">
                            <tr>
                                <th class="px-4 py-2.5">Tanggal</th>
                                <th class="px-4 py-2.5">Keterangan</th>
                                <th class="px-4 py-2.5">Metode</th>
                                <th class="px-4 py-2.5 text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($cur['expenses'] as $ce): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-4 py-2.5 whitespace-nowrap font-bold text-slate-700">
                                    <?= date('d/m/Y', strtotime($ce['expense_date'])) ?>
                                </td>
                                <td class="px-4 py-2.5">
                                    <span class="font-bold text-slate-800 block"><?= sanitize($ce['description']) ?></span>
                                    <?php if (!empty($ce['notes'])): ?>
                                        <span class="text-[10px] text-slate-400 block"><?= sanitize($ce['notes']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-2.5 whitespace-nowrap uppercase text-[10px] font-bold text-slate-500">
                                    <?= sanitize($ce['payment_method'] ?? 'tunai') ?>
                                </td>
                                <td class="px-4 py-2.5 text-right whitespace-nowrap font-mono text-amber-700 font-bold">
                                    <?= formatRupiah($ce['amount']) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Top 10 Produk Terlaris Bulan Ini -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-5 space-y-4">
        <div>
            <h3 class="font-heading font-black text-sm text-pepsi-navy">10 Produk Paling Laris &mdash; Bulan <?= $monthLabel ?></h3>
            <p class="text-xs text-slate-400">Kontribusi produk terhadap omset dan laba Pepsi Food &amp; Drink selama bulan ini.</p>
        </div>

        <?php if (empty($cur['top_products'])): ?>
            <p class="text-center text-slate-400 text-xs italic py-4">Belum ada data penjualan produk pada bulan ini.</p>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
                <?php $rank = 0; foreach ($cur['top_products'] as $tp): $rank++; ?>
                <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="w-5 h-5 rounded-full bg-pepsi-navy text-white text-[10px] font-black grid place-items-center">
                                <?= $rank ?>
                            </span>
                            <span class="text-[10px] font-extrabold text-pepsi-blue">
                                <?= (int)$tp['total_qty'] ?> pcs
                            </span>
                        </div>
                        <h4 class="font-bold text-slate-800 text-xs line-clamp-1"><?= sanitize($tp['product_name']) ?></h4>
                        <span class="text-[10px] text-slate-400 block"><?= sanitize($tp['category_name'] ?? 'Pepsi Food & Drink') ?></span>
                    </div>
                    <div class="mt-2 pt-2 border-t border-slate-200/60 text-right">
                        <span class="text-[10px] text-slate-400 block">Omset: <?= formatRupiah($tp['total_sales']) ?></span>
                        <span class="text-[11px] font-black text-emerald-600 block">Laba: <?= formatRupiah($tp['total_profit']) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- MODAL ATUR / KOREKSI SALDO AWAL -->
    <div x-show="balanceModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="balanceModal = false" class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="font-heading font-black text-lg text-pepsi-navy">Atur Saldo Awal / Modal Pepsi Food &amp; Drink</h3>
                    <p class="text-xs text-slate-400">Bulan: <b><?= $monthLabel ?></b> (<?= $yearMonth ?>)</p>
                </div>
                <button @click="balanceModal = false" class="text-slate-400 hover:text-slate-700">&times;</button>
            </div>

            <form action="<?= url('admin/canteen/monthly-report/set-balance') ?>" method="POST" class="space-y-4 text-xs">
                <input type="hidden" name="year_month" value="<?= $yearMonth ?>">

                <div class="p-3 rounded-2xl bg-blue-50 border border-blue-200 text-blue-900 space-y-1">
                    <p class="font-bold">Penjelasan Saldo Berlanjut:</p>
                    <p class="text-[11px] text-blue-800">
                        Secara standar, saldo awal bulan ini otomatis mengambil saldo akhir bulan lalu. Gunakan fitur ini jika Anda ingin menetapkan modal awal perdana, atau memasukkan injeksi modal/koreksi pembukuan.
                    </p>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nominal Saldo Awal (Rp) *</label>
                    <input type="number" step="any" name="opening_balance" value="<?= (float)$cur['opening_balance'] ?>" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold font-mono text-sm">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Catatan / Alasan Penyesuaian (Opsional)</label>
                    <input type="text" name="notes" value="<?= sanitize($cur['manual_note']) ?>" placeholder="Contoh: Modal awal perdana / Injeksi modal owner" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <?php if ($cur['is_manual_opening']): ?>
                        <button type="submit" name="action" value="reset" class="px-3 py-2 rounded-xl bg-rose-50 text-rose-600 font-bold text-xs hover:bg-rose-100 transition" onclick="return confirm('Kembalikan saldo awal otomatis mengikuti saldo akhir bulan sebelumnya?')">
                            Reset Otomatis
                        </button>
                    <?php else: ?>
                        <span></span>
                    <?php endif; ?>

                    <div class="flex items-center gap-2">
                        <button type="button" @click="balanceModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
                        <button type="submit" name="action" value="save" class="px-5 py-2 rounded-xl bg-pepsi-blue hover:bg-blue-900 text-white font-bold transition shadow-sm">
                            Simpan Saldo Awal
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>
