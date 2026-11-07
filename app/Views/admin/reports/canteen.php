<?php
// Laporan Pepsi Food & Drink — Laba Rugi Pepsi Food &amp; Drink (print-ready)
$siteName    = $settings['app_name'] ?? $settings['site_name'] ?? 'MR.PEPSI UPGRADE';
$siteAddress = $settings['address'] ?? 'Pare, Kediri, Jawa Timur';
$sitePhone   = $settings['admin_wa'] ?? '085648077008';

$logoUrl = logo_url();

$startDate = $filters['start_date'] ?? '';
$endDate   = $filters['end_date'] ?? '';

$printedAt = date('d/m/Y H:i');
$grossProfit         = (float)($grossProfit ?? $summary['gross_profit'] ?? 0);
$grossMargin         = (float)($grossMargin ?? $margin ?? 0);
$canteenExpenseTotal = (float)($canteenExpenseTotal ?? 0);
$netProfit           = (float)($netProfit ?? ($grossProfit - $canteenExpenseTotal));
$netMargin           = (float)($netMargin ?? 0);
$expenseRows         = $canteenExpenses['rows'] ?? [];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pepsi Food &amp; Drink - <?= sanitize($siteName) ?></title>
    <?php require __DIR__ . '/../../partials/pwa_head.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            background: #e2e8f0;
            margin: 0;
            padding: 24px 12px;
            color: #0f172a;
        }

        .paper {
            background: #fff;
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            padding: 28px 32px;
            border-radius: 12px;
            box-shadow: 0 12px 30px -10px rgba(0, 0, 0, .25);
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .report-table th,
        .report-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            vertical-align: middle;
        }

        .report-table thead th {
            background: #12131A;
            color: #fff;
            text-align: left;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: .02em;
        }

        .report-table tfoot td {
            background: #f1f5f9;
            font-weight: 800;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .paper {
                max-width: 100%;
                box-shadow: none;
                border-radius: 0;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            @page {
                size: A4 portrait;
                margin: 12mm;
            }

            .report-table {
                font-size: 9.5px;
            }

            .report-table tr {
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>

    <!-- Toolbar (screen only) -->
    <div class="no-print max-w-[900px] mx-auto mb-4 space-y-3">
        <div class="bg-slate-900 text-white rounded-2xl p-4 flex items-center justify-between shadow-lg">
            <a href="<?= url('admin/canteen/report') ?>" class="inline-flex items-center gap-2 text-xs font-bold text-slate-300 hover:text-white transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Kembali ke Laporan Pepsi Food &amp; Drink</span>
            </a>
            <button onclick="window.print()" class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-black text-xs flex items-center gap-2 shadow-md transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>Cetak / Simpan PDF</span>
            </button>
        </div>

        <form method="GET" action="<?= url('admin/reports/canteen') ?>" class="bg-white rounded-2xl p-3.5 border border-slate-200 shadow-sm flex flex-wrap items-center gap-2">
            <span class="text-xs font-bold text-slate-500 mr-1">Filter Tanggal:</span>
            <div class="flex items-center gap-1.5 bg-white px-3 py-1.5 rounded-xl border border-slate-200 text-xs">
                <input type="date" name="start_date" value="<?= sanitize($startDate) ?>" class="font-bold text-slate-800 bg-transparent focus:outline-none">
                <span class="text-slate-400 font-bold">s/d</span>
                <input type="date" name="end_date" value="<?= sanitize($endDate) ?>" class="font-bold text-slate-800 bg-transparent focus:outline-none">
            </div>
            <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition">Terapkan</button>
            <a href="<?= url('admin/reports/canteen') ?>" class="px-3 py-2 rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 text-xs font-bold transition">Reset</a>
        </form>
        <p class="text-[11px] text-center text-slate-500">
            Laporan Rekapitulasi Pendapatan, HPP, Pengeluaran Operasional, dan Laba Bersih <b>Pepsi Food &amp; Drink</b>.
        </p>
    </div>

    <!-- REPORT PAPER -->
    <div class="paper">

        <!-- Kop Surat -->
        <div class="flex items-center gap-4 pb-4 border-b-4 border-double border-slate-900">
            <?php if ($logoUrl): ?>
                <img src="<?= $logoUrl ?>" alt="Logo" class="w-16 h-16 object-contain">
            <?php else: ?>
                <div class="w-16 h-16 rounded-2xl bg-slate-900 text-white grid place-items-center font-black text-xl">MP</div>
            <?php endif; ?>
            <div class="flex-1">
                <h1 class="text-xl font-black tracking-tight text-slate-900 uppercase"><?= sanitize($siteName) ?></h1>
                <p class="text-[11px] text-slate-600 font-medium"><?= sanitize($siteAddress) ?></p>
                <p class="text-[11px] text-slate-600 font-semibold">Telp/WA: <?= sanitize($sitePhone) ?></p>
            </div>
            <div class="text-right">
                <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 block">Laporan Keuangan</span>
                <span class="text-sm font-black text-slate-900 uppercase leading-tight block">Pepsi Food &amp; Drink</span>
                <span class="text-[10px] font-semibold text-slate-500 block">Pendapatan &amp; Pengeluaran Pepsi Food &amp; Drink</span>
            </div>
        </div>

        <!-- Meta Info -->
        <div class="grid grid-cols-2 gap-3 text-[11px] py-3 border-b border-slate-200">
            <div>
                <span class="text-slate-400 block">Rentang Tanggal:</span>
                <span class="font-bold text-slate-800"><?= sanitize(($startDate ?: 'Awal') . ' s/d ' . ($endDate ?: 'Akhir')) ?></span>
            </div>
            <div class="text-right">
                <span class="text-slate-400 block">Dicetak:</span>
                <span class="font-bold text-slate-800"><?= $printedAt ?> WIB</span>
            </div>
        </div>

        <!-- Ringkasan Cards 5 Kolom / Grid -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-2.5 py-3">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-2.5">
                <span class="text-[9px] font-bold text-slate-500 uppercase block">Total Omset</span>
                <span class="text-sm font-black text-slate-900 block"><?= formatRupiah($summary['total_revenue']) ?></span>
                <span class="text-[8.5px] text-slate-400 block"><?= (int)($summary['total_orders'] ?? 0) ?> transaksi</span>
            </div>
            <div class="rounded-xl border border-slate-200 p-2.5">
                <span class="text-[9px] font-bold text-slate-500 uppercase block">Total Modal (HPP)</span>
                <span class="text-sm font-black text-slate-700 block"><?= formatRupiah($summary['total_cost']) ?></span>
                <span class="text-[8.5px] text-slate-400 block">Harga Pokok</span>
            </div>
            <div class="rounded-xl border border-blue-200 bg-blue-50/50 p-2.5">
                <span class="text-[9px] font-bold text-blue-700 uppercase block">Laba Kotor</span>
                <span class="text-sm font-black text-blue-900 block"><?= formatRupiah($grossProfit) ?></span>
                <span class="text-[8.5px] text-blue-600 font-bold block">Margin: <?= $grossMargin ?>%</span>
            </div>
            <div class="rounded-xl border border-red-200 bg-red-50/50 p-2.5">
                <span class="text-[9px] font-bold text-red-600 uppercase block">Operasional Pepsi Food &amp; Drink</span>
                <span class="text-sm font-black text-red-600 block"><?= formatRupiah($canteenExpenseTotal) ?></span>
                <span class="text-[8.5px] text-red-400 block"><?= count($expenseRows) ?> item beban</span>
            </div>
            <div class="rounded-xl border border-emerald-300 bg-emerald-50 p-2.5 col-span-2 md:col-span-1">
                <span class="text-[9px] font-bold text-emerald-700 uppercase block">Laba Bersih Akhir</span>
                <span class="text-sm font-black <?= $netProfit >= 0 ? 'text-emerald-800' : 'text-red-700' ?> block"><?= formatRupiah($netProfit) ?></span>
                <span class="text-[8.5px] font-bold <?= $netMargin >= 0 ? 'text-emerald-600' : 'text-red-500' ?> block">Margin: <?= $netMargin ?>%</span>
            </div>
        </div>

        <?php if (!empty($summary['total_adjusted']) && (int)$summary['total_adjusted'] > 0): ?>
            <div class="mb-3 p-2.5 rounded-lg border border-amber-300 bg-amber-50 text-[10px] text-amber-900 flex items-center justify-between">
                <span>
                    ✏️ <strong>Catatan Penyesuaian:</strong> Laporan ini mencakup <strong><?= (int)$summary['total_adjusted'] ?></strong> transaksi yang telah disesuaikan (koreksi salah stok / salah bayar kasir). Seluruh nilai omset, beban pokok (HPP), dan laba telah disesuaikan secara akurat.
                </span>
            </div>
        <?php endif; ?>

        <!-- ============ REKONSILIASI LABA RUGI ============ -->
        <h3 style="font-weight:800; font-size:12px; margin:10px 0 6px; text-transform:uppercase; letter-spacing:.03em; color:#0f172a; border-bottom:2px solid #0f172a; padding-bottom:3px;">
            A. Rekonsiliasi Laba Rugi Pepsi Food &amp; Drink
        </h3>

        <table class="report-table mb-4">
            <tbody>
                <!-- 1. PENDAPATAN -->
                <tr>
                    <td colspan="2" style="background:#e2e8f0; font-weight:800; text-transform:uppercase; font-size:10px;">1. Pendapatan Penjualan</td>
                </tr>
                <tr>
                    <td style="padding-left:16px;">Penjualan POS Kasir</td>
                    <td class="text-right" style="font-weight:600;"><?= formatRupiah($summary['total_pos'] ?? $summary['total_revenue']) ?></td>
                </tr>
                <tr>
                    <td style="padding-left:16px;">Penjualan Tambahan (Manual)</td>
                    <td class="text-right" style="font-weight:600;"><?= formatRupiah($summary['total_manual'] ?? 0) ?></td>
                </tr>
                <tr style="background:#f8fafc;">
                    <td style="font-weight:800;">Total Pendapatan Penjualan (Omset)</td>
                    <td class="text-right" style="font-weight:800;"><?= formatRupiah($summary['total_revenue']) ?></td>
                </tr>
                <tr>
                    <td style="padding-left:16px; color:#374151;">
                        &nbsp;&nbsp;↳ 💵 Tunai (Cash) — <?= number_format((int)($summary['count_cash'] ?? 0)) ?> transaksi
                    </td>
                    <td class="text-right" style="font-weight:700; color:#374151;"><?= formatRupiah($summary['total_cash'] ?? 0) ?></td>
                </tr>
                <tr>
                    <td style="padding-left:16px; color:#1e40af;">
                        &nbsp;&nbsp;↳ 📱 QRIS / Non-Tunai — <?= number_format((int)($summary['count_qris'] ?? 0)) ?> transaksi
                    </td>
                    <td class="text-right" style="font-weight:700; color:#1e40af;"><?= formatRupiah($summary['total_qris'] ?? 0) ?></td>
                </tr>

                <!-- 2. BEBAN POKOK (HPP) -->
                <tr>
                    <td colspan="2" style="background:#e2e8f0; font-weight:800; text-transform:uppercase; font-size:10px;">2. Beban Pokok Penjualan</td>
                </tr>
                <tr>
                    <td style="padding-left:16px;">Harga Pokok Penjualan (HPP) Produk Terjual</td>
                    <td class="text-right" style="font-weight:700; color:#dc2626;">(<?= formatRupiah($summary['total_cost']) ?>)</td>
                </tr>
                <tr style="background:#eff6ff;">
                    <td style="font-weight:800; color:#1e40af;">LABA KOTOR PENJUALAN (Omset − HPP)</td>
                    <td class="text-right" style="font-weight:800; color:#1e40af;"><?= formatRupiah($grossProfit) ?> (<?= $grossMargin ?>%)</td>
                </tr>

                <!-- 3. BEBAN OPERASIONAL Pepsi Food &amp; Drink -->
                <tr>
                    <td colspan="2" style="background:#e2e8f0; font-weight:800; text-transform:uppercase; font-size:10px;">3. Pengeluaran Operasional Pepsi Food &amp; Drink</td>
                </tr>
                <tr>
                    <td style="padding-left:16px;">Beban Operasional Pepsi Food &amp; Drink (<?= count($expenseRows) ?> item transaksi)</td>
                    <td class="text-right" style="font-weight:700; color:#dc2626;">(<?= formatRupiah($canteenExpenseTotal) ?>)</td>
                </tr>

                <!-- 4. LABA BERSIH AKHIR -->
                <tr style="background:#12131A; color:#fff;">
                    <td style="font-weight:800; color:#fff; font-size:11px;">LABA / RUGI BERSIH AKHIR PEPSI FOOD &amp; DRINK</td>
                    <td class="text-right" style="font-weight:800; color:#fff; font-size:11px;">
                        <?= formatRupiah($netProfit) ?>
                        <span style="font-size:9.5px; opacity:0.85; margin-left:4px;">(<?= $netMargin ?>%)</span>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- ============ RINCIAN PENGELUARAN OPERASIONAL Pepsi Food &amp; Drink ============ -->
        <h3 style="font-weight:800; font-size:12px; margin:14px 0 6px; text-transform:uppercase; letter-spacing:.03em; color:#0f172a; border-bottom:2px solid #0f172a; padding-bottom:3px;">
            B. Rincian Pengeluaran Operasional Pepsi Food &amp; Drink
        </h3>
        <table class="report-table mb-4">
            <thead>
                <tr>
                    <th style="width:28px" class="text-center">No</th>
                    <th style="width:75px">Tanggal</th>
                    <th>Keterangan Pengeluaran</th>
                    <th style="width:70px">Metode</th>
                    <th class="text-right" style="width:130px">Nominal</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($expenseRows)): ?>
                    <tr>
                        <td colspan="5" style="text-align:center; padding:14px; color:#94a3b8; font-style:italic;">Belum ada pengeluaran operasional Pepsi Food &amp; Drink pada rentang tanggal ini.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($expenseRows as $i => $ce): ?>
                        <tr>
                            <td class="text-center"><?= $i + 1 ?></td>
                            <td><?= date('d/m/Y', strtotime($ce['expense_date'])) ?></td>
                            <td style="font-weight:600;">
                                <?= sanitize($ce['description']) ?>
                                <?php if (!empty($ce['notes'])): ?>
                                    <span style="color:#64748b; font-size:9.5px;">(<?= sanitize($ce['notes']) ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="text-transform:uppercase; font-size:9px; font-weight:700;">
                                    <?= sanitize($ce['payment_method'] ?? 'tunai') ?>
                                </span>
                            </td>
                            <td class="text-right" style="color:#dc2626; font-weight:700;">-<?= formatRupiah($ce['amount']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr style="background:#fef2f2;">
                        <td colspan="4" style="text-align:right; font-weight:800; text-transform:uppercase; font-size:10px; color:#991b1b;">Total Pengeluaran Operasional Pepsi Food &amp; Drink</td>
                        <td class="text-right" style="font-weight:800; color:#dc2626;"><?= formatRupiah($canteenExpenseTotal) ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- ============ PRODUK PALING LARIS ============ -->
        <h3 style="font-weight:800; font-size:12px; margin:14px 0 6px; text-transform:uppercase; letter-spacing:.03em; color:#0f172a; border-bottom:2px solid #0f172a; padding-bottom:3px;">
            C. 10 Produk Paling Laris
        </h3>
        <table class="report-table mb-4">
            <thead>
                <tr>
                    <th style="width:28px" class="text-center">No</th>
                    <th>Nama Produk</th>
                    <th class="text-center" style="width:110px">Jumlah Terjual</th>
                    <th class="text-right" style="width:140px">Total Penjualan</th>
                    <th class="text-right" style="width:140px">Kontribusi Laba</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($topProducts)): ?>
                    <tr>
                        <td colspan="5" style="text-align:center; padding:18px; color:#94a3b8; font-style:italic;">Belum ada transaksi Pepsi Food &amp; Drink pada rentang tanggal ini.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($topProducts as $i => $tp): ?>
                        <tr>
                            <td class="text-center"><?= $i + 1 ?></td>
                            <td style="font-weight:700;"><?= sanitize($tp['product_name']) ?></td>
                            <td class="text-center"><?= number_format((int)$tp['total_qty']) ?> pcs</td>
                            <td class="text-right"><?= formatRupiah($tp['total_sales']) ?></td>
                            <td class="text-right" style="color:#059669; font-weight:700;">+<?= formatRupiah($tp['total_profit']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- ============ REKAP METODE PEMBAYARAN ============ -->
        <?php
        $totalCash  = (float)($summary['total_cash']  ?? 0);
        $totalQris  = (float)($summary['total_qris']  ?? 0);
        $countCash  = (int)($summary['count_cash']    ?? 0);
        $countQris  = (int)($summary['count_qris']    ?? 0);
        $totalOrders = (int)($summary['total_orders'] ?? 0);
        ?>
        <h3 style="font-weight:800; font-size:12px; margin:14px 0 6px; text-transform:uppercase; letter-spacing:.03em; color:#0f172a; border-bottom:2px solid #0f172a; padding-bottom:3px;">
            D. Rekap Metode Pembayaran
        </h3>
        <table class="report-table mb-4">
            <thead>
                <tr>
                    <th>Metode Pembayaran</th>
                    <th class="text-center" style="width:110px">Jumlah Transaksi</th>
                    <th class="text-right" style="width:180px">Total Diterima</th>
                    <th class="text-right" style="width:100px">% dari Omset</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="font-weight:700;">
                        💵 Tunai (Cash)
                    </td>
                    <td class="text-center"><?= number_format($countCash) ?> transaksi</td>
                    <td class="text-right" style="font-weight:800; color:#0f172a;"><?= formatRupiah($totalCash) ?></td>
                    <td class="text-right" style="font-weight:700; color:#0f172a;">
                        <?= $summary['total_revenue'] > 0 ? number_format(($totalCash / $summary['total_revenue']) * 100, 1) : '0.0' ?>%
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:700;">
                        📱 QRIS / Non-Tunai
                    </td>
                    <td class="text-center"><?= number_format($countQris) ?> transaksi</td>
                    <td class="text-right" style="font-weight:800; color:#1e40af;"><?= formatRupiah($totalQris) ?></td>
                    <td class="text-right" style="font-weight:700; color:#1e40af;">
                        <?= $summary['total_revenue'] > 0 ? number_format(($totalQris / $summary['total_revenue']) * 100, 1) : '0.0' ?>%
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td style="text-align:right; text-transform:uppercase; font-size:10px;">TOTAL OMSET (Tunai + QRIS)</td>
                    <td class="text-center"><?= number_format($totalOrders) ?> transaksi</td>
                    <td class="text-right"><?= formatRupiah($summary['total_revenue']) ?></td>
                    <td class="text-right">100%</td>
                </tr>
            </tfoot>
        </table>

        <!-- Signature -->
        <div class="grid grid-cols-2 gap-12 pt-10 text-[11px] text-center">
            <div></div>
            <div>
                <p class="text-slate-500">Pare, <?= date('d F Y') ?><br>Pimpinan Lembaga / Keuangan,</p>
                <div class="h-16"></div>
                <p class="font-bold text-slate-900 border-t border-slate-400 pt-1 inline-block min-w-[180px]">MR.PEPSI Management</p>
            </div>
        </div>
    </div>

</body>

</html>