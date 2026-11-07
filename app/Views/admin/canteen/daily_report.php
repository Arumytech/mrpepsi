<?php
// Laporan Penjualan Harian Pepsi Food & Drink
$siteName    = $settings['app_name'] ?? $settings['site_name'] ?? 'MR.PEPSI UPGRADE';
$siteAddress = $settings['address'] ?? 'Pare, Kediri, Jawa Timur';
$sitePhone   = $settings['admin_wa'] ?? '085648077008';

$totalQtyAll = 0;
foreach ($items as $it) {
    $totalQtyAll += (int)($it['total_qty'] ?? 0);
}

$rev = (float)($summary['total_revenue'] ?? 0);
$profit = (float)($summary['gross_profit'] ?? 0);
$cost = (float)($summary['total_cost'] ?? 0);
$overallMargin = $rev > 0 ? round(($profit / $rev) * 100, 1) : 0;

$prevDate = date('Y-m-d', strtotime($date . ' -1 day'));
$nextDate = date('Y-m-d', strtotime($date . ' +1 day'));
$isToday  = ($date === date('Y-m-d'));

// Temukan jam puncak
$peakHour = null;
$maxRevenue = 0;
foreach ($hours as $h) {
    if ((float)($h['revenue'] ?? 0) > $maxRevenue) {
        $maxRevenue = (float)$h['revenue'];
        $peakHour = (int)$h['hour'];
    }
}
?>

<div x-data="{
    searchItem: '',
    expandedTx: {},
    allExpanded: false,
    toggleAllTx() {
        this.allExpanded = !this.allExpanded;
        let self = this;
        document.querySelectorAll('[data-tx-id]').forEach(function(el) {
            let id = el.getAttribute('data-tx-id');
            self.expandedTx[id] = self.allExpanded;
        });
    },
    toggleTx(id) {
        this.expandedTx[id] = !this.expandedTx[id];
    }
}" class="space-y-6 print:space-y-4">

    <!-- CSS Print Khusus -->
    <style>
        @media print {
            aside, nav, header, #sidebar, .no-print, button, form {
                display: none !important;
            }
            body {
                background: #ffffff !important;
                color: #000000 !important;
                font-size: 11pt !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .print-only {
                display: block !important;
            }
            .print-card {
                box-shadow: none !important;
                border: 1px solid #cbd5e1 !important;
                break-inside: avoid;
            }
            .print-header {
                border-bottom: 2px solid #0f172a;
                padding-bottom: 12px;
                margin-bottom: 16px;
            }
        }
        @media screen {
            .print-only {
                display: none !important;
            }
        }
    </style>

    <!-- Header Dokumen Cetak (Hanya Muncul Saat Print) -->
    <div class="print-only print-header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900"><?= sanitize($siteName) ?></h1>
                <p class="text-xs text-slate-600"><?= sanitize($siteAddress) ?> | Telp: <?= sanitize($sitePhone) ?></p>
            </div>
            <div class="text-right">
                <span class="inline-block px-2.5 py-1 text-xs font-bold uppercase tracking-wider bg-slate-900 text-white rounded">
                    Laporan Penjualan Harian
                </span>
                <p class="text-xs text-slate-600 mt-1">Tanggal: <strong><?= tanggalIndo($date, 'd F Y') ?></strong></p>
                <p class="text-[10px] text-slate-400">Dicetak: <?= date('d/m/Y H:i') ?></p>
            </div>
        </div>
    </div>

    <!-- Header Interaktif (Screen View) -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-slate-900 text-white p-5 rounded-2xl shadow-xl relative overflow-hidden no-print">
        <div class="relative z-10">
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wider bg-blue-600/80 text-blue-100 rounded-md">
                    Pepsi Food &amp; Drink
                </span>
                <span class="text-xs text-slate-400">·</span>
                <span class="text-xs text-slate-300">Laporan Penjualan Harian</span>
            </div>
            <h1 class="text-2xl font-black tracking-tight text-white flex items-center gap-2">
                <?= tanggalIndo($date, 'l, d F Y') ?>
                <?php if ($isToday): ?>
                    <span class="text-xs font-bold px-2 py-0.5 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded-full">Hari Ini</span>
                <?php endif; ?>
            </h1>
            <p class="text-sm text-slate-300 mt-1">
                Rincian barang yang terjual, omset, modal (HPP), keuntungan, serta daftar transaksi kasir.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2 relative z-10">
            <!-- Navigasi Tanggal Cepat -->
            <form action="<?= url('admin/canteen/daily-report') ?>" method="GET" class="flex items-center gap-1.5 bg-slate-800/90 p-1.5 rounded-xl border border-slate-700">
                <a href="<?= url('admin/canteen/daily-report?date=' . $prevDate) ?>" 
                   title="Hari Sebelumnya (<?= $prevDate ?>)"
                   class="p-2 hover:bg-slate-700 text-slate-300 hover:text-white rounded-lg transition text-xs flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span class="hidden sm:inline">Kemarin</span>
                </a>

                <input type="date" name="date" value="<?= sanitize($date) ?>" 
                       onchange="this.form.submit()"
                       class="bg-slate-900 text-white text-xs font-semibold px-2.5 py-1.5 rounded-lg border border-slate-700 focus:outline-none focus:border-blue-500 transition cursor-pointer">

                <a href="<?= url('admin/canteen/daily-report?date=' . $nextDate) ?>" 
                   title="Hari Berikutnya (<?= $nextDate ?>)"
                   class="p-2 hover:bg-slate-700 text-slate-300 hover:text-white rounded-lg transition text-xs flex items-center gap-1 <?= ($date >= date('Y-m-d')) ? 'opacity-40 pointer-events-none' : '' ?>">
                    <span class="hidden sm:inline">Besok</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                <?php if (!$isToday): ?>
                    <a href="<?= url('admin/canteen/daily-report?date=' . date('Y-m-d')) ?>" 
                       class="px-2.5 py-1.5 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded-lg transition shadow">
                        Hari Ini
                    </a>
                <?php endif; ?>
            </form>

            <!-- Tombol Cetak / PDF -->
            <button type="button" onclick="window.print()" 
                    class="inline-flex items-center gap-2 px-3.5 py-2.5 bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold rounded-xl border border-slate-700 transition shadow">
                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                <span>Cetak / PDF</span>
            </button>

            <!-- Tautan ke Penyesuaian Transaksi -->
            <a href="<?= url('admin/canteen/transactions?start_date=' . $date . '&end_date=' . $date) ?>" 
               class="inline-flex items-center gap-2 px-3.5 py-2.5 bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 text-xs font-semibold rounded-xl border border-amber-500/30 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                <span>Penyesuaian</span>
            </a>
        </div>
    </div>

    <!-- Alert Notifikasi Jika Ada Transaksi Disesuaikan -->
    <?php if (!empty($summary['total_adjusted']) && (int)$summary['total_adjusted'] > 0): ?>
        <div class="p-3.5 bg-amber-50 border border-amber-200 text-amber-900 rounded-2xl flex items-center justify-between gap-3 text-xs no-print">
            <div class="flex items-center gap-2">
                <span class="text-base">✏️</span>
                <span>
                    Terdapat <strong><?= (int)$summary['total_adjusted'] ?></strong> transaksi yang telah dilakukan penyesuaian (koreksi stok / pembayaran). Semua data omset, HPP modal, laba kotor, dan rincian barang terjual telah disesuaikan secara otomatis.
                </span>
            </div>
            <a href="<?= url('admin/canteen/transactions?start_date=' . $date . '&end_date=' . $date . '&status_adjusted=adjusted') ?>" 
               class="px-2.5 py-1 bg-amber-200 hover:bg-amber-300 text-amber-950 font-bold rounded-lg transition whitespace-nowrap text-[11px]">
                Lihat Penyesuaian &rarr;
            </a>
        </div>
    <?php endif; ?>

    <!-- Ringkasan Kartu Metrik (4 Kartu) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Kartu 1: Total Omset -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm relative overflow-hidden print-card">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Penjualan / Omset</span>
                <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-black text-slate-900"><?= formatRupiah($rev) ?></div>
                <div class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
                    <span class="font-bold text-slate-700"><?= (int)($summary['total_orders'] ?? 0) ?></span> transaksi
                    <?php if ((int)($summary['total_manual'] ?? 0) > 0): ?>
                        <span class="text-amber-600 text-[11px]">(Ada manual: <?= formatRupiah($summary['total_manual']) ?>)</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Kartu 2: Laba Kotor & Margin -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm relative overflow-hidden print-card">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Laba Kotor</span>
                <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-black text-emerald-600"><?= formatRupiah($profit) ?></div>
                <div class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 font-bold text-[11px]">
                        Margin <?= $overallMargin ?>%
                    </span>
                    <span>dari total omset</span>
                </div>
            </div>
        </div>

        <!-- Kartu 3: Total Modal (HPP) & Item Terjual -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm relative overflow-hidden print-card">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Modal Barang (HPP)</span>
                <span class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-black text-slate-800"><?= formatRupiah($cost) ?></div>
                <div class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
                    <span class="font-bold text-slate-700"><?= number_format($totalQtyAll, 0, ',', '.') ?></span> pcs barang terjual
                </div>
            </div>
        </div>

        <!-- Kartu 4: Metode Bayar (Cash vs QRIS) -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm relative overflow-hidden print-card">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Metode Bayar</span>
                <span class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2 space-y-1">
                <div class="flex items-center justify-between text-xs">
                    <span class="flex items-center gap-1 text-slate-600">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Tunai:
                    </span>
                    <span class="font-bold text-slate-800"><?= formatRupiah($summary['total_cash'] ?? 0) ?> <span class="text-[10px] text-slate-400 font-normal">(<?= (int)($summary['count_cash'] ?? 0) ?>)</span></span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="flex items-center gap-1 text-slate-600">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span> QRIS:
                    </span>
                    <span class="font-bold text-slate-800"><?= formatRupiah($summary['total_qris'] ?? 0) ?> <span class="text-[10px] text-slate-400 font-normal">(<?= (int)($summary['count_qris'] ?? 0) ?>)</span></span>
                </div>
                <!-- Progress Bar Ratio -->
                <?php 
                $cashVal = (float)($summary['total_cash'] ?? 0);
                $qrisVal = (float)($summary['total_qris'] ?? 0);
                $sumPay = $cashVal + $qrisVal;
                $cashPct = $sumPay > 0 ? round(($cashVal / $sumPay) * 100) : 50;
                ?>
                <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden flex mt-2">
                    <div class="bg-emerald-500 h-full" style="width: <?= $cashPct ?>%" title="Tunai: <?= $cashPct ?>%"></div>
                    <div class="bg-blue-500 h-full" style="width: <?= 100 - $cashPct ?>%" title="QRIS: <?= 100 - $cashPct ?>%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafik Distribusi Penjualan per Jam (06:00 - 22:00) -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm print-card no-print">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
            <div>
                <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Aktivitas Penjualan Berdasarkan Jam (06:00 – 22:00)
                </h2>
                <p class="text-xs text-slate-500">Grafik transaksi dan nominal omset per jam sepanjang hari.</p>
            </div>
            <?php if ($peakHour !== null && $maxRevenue > 0): ?>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-50 text-amber-800 border border-amber-200 text-xs font-semibold rounded-lg self-start sm:self-auto">
                    🔥 Jam Teramai: <strong><?= sprintf('%02d:00', $peakHour) ?> - <?= sprintf('%02d:59', $peakHour) ?></strong> (<?= formatRupiah($maxRevenue) ?>)
                </span>
            <?php endif; ?>
        </div>

        <!-- Visual Bar Chart Interaktif -->
        <div class="flex gap-1 pt-6 pb-2 items-end border-b border-slate-100 overflow-hidden" style="height: 160px;">
            <?php 
            // Jam 06 sampai 22 (17 jam)
            for ($h = 6; $h <= 22; $h++):
                $dataH = $hours[$h] ?? ['revenue' => 0, 'tx_count' => 0];
                $hRev = (float)($dataH['revenue'] ?? 0);
                $hTx  = (int)($dataH['tx_count'] ?? 0);
                $barHeightPct = ($maxRevenue > 0) ? max(4, round(($hRev / $maxRevenue) * 100)) : 4;
                $isPeak = ($peakHour === $h && $hRev > 0);
                $barColor = $hRev > 0 ? ($isPeak ? '#f59e0b' : '#2563eb') : '#e2e8f0';
            ?>
                <div class="group relative cursor-pointer flex flex-col items-center justify-end" 
                     style="flex: 1; height: 100%; min-width: 0;">
                    <!-- Tooltip Hover -->
                    <div class="absolute bottom-full mb-2 hidden group-hover:flex flex-col items-center z-30 pointer-events-none" style="left: 50%; transform: translateX(-50%);">
                        <div class="bg-slate-900 text-white rounded-lg py-1 px-2 shadow-xl whitespace-nowrap text-center" style="font-size: 10px;">
                            <div class="font-bold" style="color: #fcd34d;"><?= sprintf('%02d:00', $h) ?></div>
                            <div><?= formatRupiah($hRev) ?></div>
                            <div style="color: #94a3b8;"><?= $hTx ?> Transaksi</div>
                        </div>
                        <div class="w-2 h-2 bg-slate-900 rotate-45" style="margin-top: -4px;"></div>
                    </div>

                    <!-- Batang Bar -->
                    <div class="w-full rounded-t-sm transition-all duration-300" 
                         style="height: <?= $barHeightPct ?>%; background: <?= $barColor ?>; max-width: 32px; margin: 0 auto;">
                    </div>

                    <!-- Label Jam -->
                    <span style="font-size: 9px; font-weight: 600; color: <?= $isPeak ? '#d97706' : '#94a3b8' ?>; margin-top: 4px; flex-shrink: 0; display: block; text-align: center;">
                        <?= sprintf('%02d', $h) ?>
                    </span>
                </div>
            <?php endfor; ?>
        </div>
        <div class="flex items-center justify-between text-[11px] text-slate-400 mt-2 px-1">
            <span>Pagi (06:00)</span>
            <span>Siang (12:00)</span>
            <span>Sore (17:00)</span>
            <span>Malam (22:00)</span>
        </div>
    </div>

    <!-- Tabel Utama: Rincian Barang yang Terjual -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden print-card">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-base font-bold text-slate-900">Rincian Barang Terjual</h2>
                    <span class="px-2 py-0.5 text-xs font-bold bg-blue-50 text-blue-700 rounded-full">
                        <?= count($items) ?> Produk
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">Daftar semua produk yang laku terjual pada tanggal ini beserta profitnya.</p>
            </div>

            <!-- Search Filter Item (Alpine.js) -->
            <div class="no-print relative min-w-[220px]">
                <input type="text" x-model="searchItem" placeholder="Cari nama produk..." 
                       class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl pl-8 pr-3 py-2 text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white transition">
                <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>

        <!-- Tabel Produk -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-900 text-white uppercase text-[11px] font-bold tracking-wider">
                        <th class="py-3 px-3 text-center w-12">No</th>
                        <th class="py-3 px-3">Nama Produk / Barang</th>
                        <th class="py-3 px-3 text-center">Qty Terjual</th>
                        <th class="py-3 px-3 text-right">Harga Jual</th>
                        <th class="py-3 px-3 text-right">Total Penjualan</th>
                        <th class="py-3 px-3 text-right">Total HPP</th>
                        <th class="py-3 px-3 text-right">Laba Kotor</th>
                        <th class="py-3 px-3 text-center">Margin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                <div class="max-w-xs mx-auto space-y-2">
                                    <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                    <p class="font-semibold text-slate-600 text-sm">Tidak ada barang terjual</p>
                                    <p class="text-xs text-slate-400">Belum ada transaksi kasir ataupun entri manual pada tanggal <?= tanggalIndo($date) ?>.</p>
                                    <div class="pt-2 no-print">
                                        <a href="<?= url('pos') ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 text-white font-bold rounded-lg text-xs hover:bg-blue-500 shadow">
                                            Buka Mesin POS
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $no = 1;
                        foreach ($items as $item): 
                            $name = $item['product_name'] ?? '-';
                            $qty  = (int)($item['total_qty'] ?? 0);
                            $price = (float)($item['unit_price'] ?? 0);
                            $itemSales = (float)($item['total_sales'] ?? 0);
                            $itemCost  = (float)($item['total_cost'] ?? 0);
                            $itemProfit = (float)($item['total_profit'] ?? 0);
                            $margin = (float)($item['margin_pct'] ?? 0);
                        ?>
                            <tr class="hover:bg-slate-50/80 transition"
                                x-show="!searchItem || '<?= strtolower(addslashes($name)) ?>'.includes(searchItem.toLowerCase())">
                                <td class="py-3 px-3 text-center text-slate-400 font-medium"><?= $no++ ?></td>
                                <td class="py-3 px-3">
                                    <span class="font-bold text-slate-800 text-[13px] block"><?= sanitize($name) ?></span>
                                    <?php if (!empty($item['product_id'])): ?>
                                        <span class="text-[10px] text-slate-400">ID #<?= (int)$item['product_id'] ?></span>
                                    <?php else: ?>
                                        <span class="text-[10px] text-amber-600 italic">Item Manual / Non-Katalog</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span class="inline-block px-2.5 py-1 bg-blue-50 text-blue-700 font-extrabold rounded-md text-xs">
                                        <?= number_format($qty, 0, ',', '.') ?>
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-right text-slate-600 font-medium">
                                    <?= formatRupiah($price) ?>
                                </td>
                                <td class="py-3 px-3 text-right font-bold text-slate-900">
                                    <?= formatRupiah($itemSales) ?>
                                </td>
                                <td class="py-3 px-3 text-right text-slate-500">
                                    <?= formatRupiah($itemCost) ?>
                                </td>
                                <td class="py-3 px-3 text-right font-bold <?= $itemProfit >= 0 ? 'text-emerald-600' : 'text-rose-600' ?>">
                                    <?= formatRupiah($itemProfit) ?>
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold <?= $margin >= 25 ? 'bg-emerald-50 text-emerald-700' : ($margin >= 10 ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600') ?>">
                                        <?= $margin ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($items)): ?>
                    <tfoot>
                        <tr class="bg-slate-100/90 font-black text-slate-900 border-t-2 border-slate-300">
                            <td colspan="2" class="py-3.5 px-3 text-center uppercase tracking-wider text-xs">Total Keseluruhan</td>
                            <td class="py-3.5 px-3 text-center text-blue-700 text-xs">
                                <?= number_format($totalQtyAll, 0, ',', '.') ?> pcs
                            </td>
                            <td class="py-3.5 px-3"></td>
                            <td class="py-3.5 px-3 text-right text-xs text-slate-900">
                                <?= formatRupiah($rev) ?>
                            </td>
                            <td class="py-3.5 px-3 text-right text-xs text-slate-600">
                                <?= formatRupiah($cost) ?>
                            </td>
                            <td class="py-3.5 px-3 text-right text-xs text-emerald-600">
                                <?= formatRupiah($profit) ?>
                            </td>
                            <td class="py-3.5 px-3 text-center text-xs">
                                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded font-black">
                                    <?= $overallMargin ?>%
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- Daftar Rincian Transaksi Kasir Hari Ini -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden print-card">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-base font-bold text-slate-900">Daftar Transaksi Lengkap</h2>
                    <span class="px-2 py-0.5 text-xs font-bold bg-slate-100 text-slate-700 rounded-full">
                        <?= count($transactions) ?> Struk
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">Daftar tiap transaksi beserta rincian belanja pelanggan.</p>
            </div>

            <?php if (!empty($transactions)): ?>
                <div class="no-print">
                    <button type="button" @click="toggleAllTx()" 
                            class="text-xs font-semibold px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition">
                        <span x-text="allExpanded ? 'Tutup Semua Rincian' : 'Buka Semua Rincian'"></span>
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <?php if (empty($transactions)): ?>
            <div class="p-8 text-center text-slate-400 text-xs">
                Tidak ada transaksi pada tanggal ini.
            </div>
        <?php else: ?>
            <div class="divide-y divide-slate-100">
                <?php foreach ($transactions as $tx): 
                    $txId = (int)$tx['id'];
                    $inv = $tx['invoice_no'];
                    $time = date('H:i', strtotime($tx['created_at']));
                    $pay = $tx['payment_method'];
                    $amt = (float)$tx['total_amount'];
                    $txGross = (float)($tx['gross_profit'] ?? 0);
                    $cashier = $tx['cashier_name'] ?? 'Kasir';
                    $cust = $tx['customer_name'] ?? 'Umum';
                    $isManual = ((int)($tx['is_manual'] ?? 0) === 1);
                    $txItems = $tx['items'] ?? [];
                ?>
                    <div class="transition" data-tx-id="<?= $txId ?>">
                        <!-- Baris Ringkasan Transaksi -->
                        <div @click="toggleTx(<?= $txId ?>)" 
                             class="p-3 sm:px-5 flex flex-wrap items-center justify-between gap-3 hover:bg-slate-50/70 cursor-pointer">
                            <div class="flex items-center gap-3">
                                <button type="button" class="w-6 h-6 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition no-print">
                                    <svg class="w-3.5 h-3.5 transition-transform duration-200" 
                                         :class="expandedTx[<?= $txId ?>] ? 'rotate-90' : ''"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-slate-900 text-xs font-mono"><?= sanitize($inv) ?></span>
                                        <?php if ($isManual): ?>
                                            <span class="px-1.5 py-0.5 text-[10px] font-bold bg-amber-100 text-amber-800 rounded">Manual</span>
                                        <?php endif; ?>
                                        <?php if (!empty($tx['is_adjusted'])): ?>
                                            <span class="px-1.5 py-0.5 text-[10px] font-bold bg-amber-500 text-white rounded shadow-xs" title="Telah disesuaikan: <?= sanitize($tx['adjustment_note'] ?? '') ?>">
                                                ✏️ Disesuaikan
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-[11px] text-slate-400 flex flex-wrap items-center gap-2 mt-0.5">
                                        <span>⏰ <?= $time ?> WIB</span>
                                        <span>·</span>
                                        <span>Kasir: <strong class="text-slate-600"><?= sanitize($cashier) ?></strong></span>
                                        <span>·</span>
                                        <span>Pelanggan: <?= sanitize($cust) ?></span>
                                        <?php if (!empty($tx['is_adjusted']) && !empty($tx['adjustment_note'])): ?>
                                            <span class="text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200 text-[10px]">
                                                Koreksi: <?= sanitize($tx['adjustment_note']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 text-right">
                                <div>
                                    <div class="font-black text-slate-900 text-xs sm:text-sm"><?= formatRupiah($amt) ?></div>
                                    <div class="text-[10px] text-emerald-600 font-semibold">Laba: <?= formatRupiah($txGross) ?></div>
                                </div>
                                <span class="px-2 py-1 rounded-md text-[11px] font-bold uppercase <?= $pay === 'cash' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-blue-50 text-blue-700 border border-blue-200' ?>">
                                    <?= $pay === 'cash' ? 'Tunai' : 'QRIS' ?>
                                </span>
                            </div>
                        </div>

                        <!-- Dropdown Rincian Item Transaksi -->
                        <div x-show="expandedTx[<?= $txId ?>]" 
                             x-collapse
                             class="bg-slate-50/60 px-4 sm:px-12 py-3 border-t border-slate-100 text-xs">
                            <div class="bg-white rounded-xl border border-slate-200/70 p-3 shadow-xs">
                                <div class="text-[11px] font-bold uppercase text-slate-400 mb-2 tracking-wider">Item Dalam Struk Ini:</div>
                                <table class="w-full text-left text-xs">
                                    <thead>
                                        <tr class="text-[10px] text-slate-400 uppercase border-b border-slate-100">
                                            <th class="py-1">Nama Barang</th>
                                            <th class="py-1 text-center">Qty</th>
                                            <th class="py-1 text-right">Harga</th>
                                            <th class="py-1 text-right">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50">
                                        <?php foreach ($txItems as $ti): ?>
                                            <tr>
                                                <td class="py-1.5 font-medium text-slate-800"><?= sanitize($ti['product_name'] ?? '-') ?></td>
                                                <td class="py-1.5 text-center font-bold text-slate-700"><?= (int)($ti['quantity'] ?? 0) ?></td>
                                                <td class="py-1.5 text-right text-slate-500"><?= formatRupiah((float)($ti['selling_price'] ?? 0)) ?></td>
                                                <td class="py-1.5 text-right font-bold text-slate-800"><?= formatRupiah((float)($ti['subtotal'] ?? 0)) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="border-t border-slate-200 text-slate-700 font-bold">
                                            <td colspan="3" class="py-1.5 text-right text-[11px]">Total:</td>
                                            <td class="py-1.5 text-right text-blue-600 font-black"><?= formatRupiah($amt) ?></td>
                                        </tr>
                                        <?php if ($pay === 'cash' && (float)($tx['cash_paid'] ?? 0) > 0): ?>
                                            <tr class="text-[11px] text-slate-500">
                                                <td colspan="3" class="py-0.5 text-right">Diterima: <?= formatRupiah((float)$tx['cash_paid']) ?></td>
                                                <td class="py-0.5 text-right">Kembalian: <?= formatRupiah((float)$tx['change_returned']) ?></td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php if (!empty($tx['manual_note'])): ?>
                                            <tr class="text-[11px] text-amber-700 italic">
                                                <td colspan="4" class="py-1 text-right">Catatan: <?= sanitize($tx['manual_note']) ?></td>
                                            </tr>
                                        <?php endif; ?>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tanda Tangan Cetak (Hanya Muncul Saat Print) -->
    <div class="print-only mt-8 pt-4 border-t border-slate-300">
        <div class="flex justify-between items-start text-xs text-slate-700">
            <div>
                <p>Catatan:</p>
                <p class="text-[10px] text-slate-500">Laporan ini dibuat otomatis oleh Sistem Kasir &amp; POS Pepsi Food &amp; Drink.</p>
            </div>
            <div class="text-center w-48">
                <p>Kediri, <?= tanggalIndo($date, 'd F Y') ?></p>
                <p class="mt-1 font-semibold">Penanggung Jawab Koperasi,</p>
                <div class="h-16"></div>
                <p class="font-bold underline">( <?= sanitize($_SESSION['user']['name'] ?? 'Admin Koperasi') ?> )</p>
            </div>
        </div>
    </div>

</div>
