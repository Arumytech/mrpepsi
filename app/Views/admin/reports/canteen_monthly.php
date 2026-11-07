<?php
// Laporan Bulanan Pepsi Food & Drink (Print-Ready A4)
$siteName    = $settings['app_name'] ?? $settings['site_name'] ?? 'MR.PEPSI UPGRADE';
$siteAddress = $settings['address'] ?? 'Pare, Kediri, Jawa Timur';
$sitePhone   = $settings['admin_wa'] ?? '085648077008';
$logoUrl     = logo_url();

$cur = $current;
$printedAt = date('d/m/Y H:i');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Bulanan Pepsi Food &amp; Drink - <?= sanitize($monthLabel) ?></title>
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
            <a href="<?= url('admin/canteen/monthly-report?month=' . urlencode($yearMonth)) ?>" class="inline-flex items-center gap-2 text-xs font-bold text-slate-300 hover:text-white transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Kembali ke Laporan Bulanan</span>
            </a>
            <div class="flex items-center gap-2">
                <a href="<?= url('admin/canteen/monthly-report/pdf?month=' . urlencode($yearMonth)) ?>" target="_blank" class="px-4 py-2 rounded-xl bg-pepsi-blue hover:bg-blue-900 text-white font-bold text-xs flex items-center gap-1.5 shadow-md transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Download PDF File</span>
                </a>
                <button onclick="window.print()" class="px-5 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-black text-xs flex items-center gap-2 shadow-md transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>Cetak Sekarang (Print)</span>
                </button>
            </div>
        </div>

        <form method="GET" action="<?= url('admin/reports/canteen-monthly') ?>" class="bg-white rounded-2xl p-3.5 border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-500">Pilih Periode Bulan:</span>
                <input type="month" name="month" value="<?= $yearMonth ?>" class="font-bold text-xs text-slate-800 bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-xl">
                <button type="submit" class="px-4 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition">Tampilkan</button>
            </div>
            <span class="text-xs text-slate-400">Dicetak: <?= $printedAt ?> WIB</span>
        </form>
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
                <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 block">Laporan Bulanan</span>
                <span class="text-sm font-black text-slate-900 uppercase leading-tight block">Pepsi Food &amp; Drink</span>
                <span class="text-[10px] font-bold text-pepsi-blue block">Sistem Berlanjut Tiap Bulan</span>
            </div>
        </div>

        <!-- Meta Info -->
        <div class="my-4 p-3 rounded-xl bg-slate-50 border border-slate-200 flex flex-wrap items-center justify-between text-xs">
            <div>
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Periode Laporan</span>
                <span class="font-extrabold text-slate-900 text-sm"><?= $monthLabel ?></span>
                <span class="text-slate-500 text-[11px] block">(01 <?= date('F Y', strtotime($cur['start_date'])) ?> &mdash; <?= date('d F Y', strtotime($cur['end_date'])) ?>)</span>
            </div>
            <div class="text-right">
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Saldo Akhir Berjalan</span>
                <span class="font-extrabold text-pepsi-navy text-sm font-mono"><?= formatRupiah($cur['closing_balance']) ?></span>
                <span class="text-[10px] text-emerald-600 font-bold block">&check; Berlanjut ke Bulan Depan</span>
            </div>
        </div>

        <!-- TABEL 1: Ringkasan Laporan Laba Rugi & Kesinambungan Saldo -->
        <div class="mb-5">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-900 mb-2 flex items-center gap-2">
                <span>1. Ringkasan Kesinambungan Saldo &amp; Laba Rugi</span>
            </h2>
            <table class="report-table">
                <thead>
                    <tr>
                        <th class="w-8/12">Komponen Pembukuan Pepsi Food &amp; Drink</th>
                        <th class="w-4/12 text-right">Nilai (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="bg-blue-50/50">
                        <td class="font-extrabold text-slate-900">
                            SALDO BULAN LALU / SALDO AWAL
                            <?php if ($cur['is_manual_opening']): ?>
                                <span class="text-[9px] text-amber-700 italic font-semibold">(Koreksi/Modal Awal: <?= sanitize($cur['manual_note']) ?>)</span>
                            <?php else: ?>
                                <span class="text-[9px] text-slate-400 font-normal">(Otomatis dari Saldo Akhir Bulan Sebelumnya)</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right font-bold font-mono text-slate-900">
                            <?= formatRupiah($cur['opening_balance']) ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <b>Total Pendapatan Penjualan (Omset Pepsi Food &amp; Drink)</b>
                            <div class="text-[10px] text-slate-500 pl-3">
                                &bull; Penjualan Kasir POS: <?= formatRupiah($cur['total_pos']) ?><br>
                                &bull; Penjualan Tambahan Manual: <?= formatRupiah($cur['total_manual']) ?>
                            </div>
                        </td>
                        <td class="text-right font-bold font-mono text-emerald-600">
                            + <?= formatRupiah($cur['revenue']) ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-slate-700">
                            Total Modal Pokok / HPP Produk Terjual (Cost of Goods Sold)
                        </td>
                        <td class="text-right font-mono text-rose-600">
                            (<?= formatRupiah($cur['cost']) ?>)
                        </td>
                    </tr>
                    <tr class="bg-slate-50">
                        <td class="font-bold text-slate-800">
                            Laba Kotor Penjualan (Gross Profit)
                            <span class="text-[10px] text-slate-400 font-normal">&mdash; Margin <?= $cur['gross_margin'] ?>%</span>
                        </td>
                        <td class="text-right font-bold font-mono text-slate-800">
                            <?= formatRupiah($cur['gross_profit']) ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-slate-700">
                            Total Pengeluaran Operasional Pepsi Food &amp; Drink (Beban Operasional)
                        </td>
                        <td class="text-right font-mono text-amber-700">
                            (<?= formatRupiah($cur['expense_total']) ?>)
                        </td>
                    </tr>
                    <tr class="bg-emerald-50/70 font-extrabold">
                        <td class="text-emerald-900">
                            LABA BERSIH BULAN INI (NET PROFIT)
                            <span class="text-[10px] text-emerald-700 font-normal">&mdash; Margin <?= $cur['net_margin'] ?>%</span>
                        </td>
                        <td class="text-right font-mono text-emerald-700 font-black">
                            <?= formatRupiah($cur['net_profit']) ?>
                        </td>
                    </tr>
                    <tr class="bg-slate-900 text-white font-extrabold text-xs">
                        <td class="py-2.5">
                            SALDO AKHIR BULAN INI (AKUMULASI KAS BERJALAN)
                            <div class="text-[9px] text-slate-300 font-normal">
                                Rumus: Saldo Awal (<?= formatRupiah($cur['opening_balance']) ?>) + Laba Bersih (<?= formatRupiah($cur['net_profit']) ?>) &rarr; Berlanjut ke Bulan Depan
                            </div>
                        </td>
                        <td class="text-right font-mono text-emerald-400 py-2.5 text-sm">
                            <?= formatRupiah($cur['closing_balance']) ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- TABEL 2: Rincian Pengeluaran Operasional Pepsi Food &amp; Drink Bulan Ini -->
        <?php if (!empty($cur['expenses'])): ?>
            <div class="mb-5">
                <h2 class="text-xs font-black uppercase tracking-wider text-slate-900 mb-2 flex items-center justify-between">
                    <span>2. Rincian Pengeluaran Operasional Pepsi Food &amp; Drink (<?= $monthLabel ?>)</span>
                    <span class="text-[10px] text-slate-500 font-normal">Total: <?= formatRupiah($cur['expense_total']) ?></span>
                </h2>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th class="w-8 text-center">No</th>
                            <th class="w-20">Tanggal</th>
                            <th>Keterangan Pengeluaran</th>
                            <th class="w-20 text-center">Metode</th>
                            <th class="w-32 text-right">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $noE = 0;
                        foreach ($cur['expenses'] as $ce): $noE++; ?>
                            <tr>
                                <td class="text-center font-bold"><?= $noE ?></td>
                                <td class="whitespace-nowrap font-medium"><?= date('d/m/Y', strtotime($ce['expense_date'])) ?></td>
                                <td>
                                    <span class="font-bold text-slate-800"><?= sanitize($ce['description']) ?></span>
                                    <?php if (!empty($ce['notes'])): ?>
                                        <span class="text-[10px] text-slate-500 block"><?= sanitize($ce['notes']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center uppercase text-[10px] font-bold"><?= sanitize($ce['payment_method'] ?? 'tunai') ?></td>
                                <td class="text-right font-mono font-bold text-slate-800"><?= formatRupiah($ce['amount']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-right uppercase font-bold text-[10px]">TOTAL PENGELUARAN OPERASIONAL Pepsi Food &amp; Drink</td>
                            <td class="text-right font-mono font-black text-amber-800"><?= formatRupiah($cur['expense_total']) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>

        <!-- TABEL 3: Rekapitulasi Berkelanjutan Antar Bulan (Continuous Rolling Monthly Ledger) -->
        <?php if (!empty($rolling)): ?>
            <div class="mb-5">
                <h2 class="text-xs font-black uppercase tracking-wider text-slate-900 mb-2">
                    <span>3. Rekapitulasi Saldo Berlanjut Tiap Bulan (Continuous Rolling Ledger)</span>
                </h2>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Periode</th>
                            <th class="text-right">Saldo Awal</th>
                            <th class="text-right">Omset</th>
                            <th class="text-right">HPP</th>
                            <th class="text-right">Operasional</th>
                            <th class="text-right">Laba Bersih</th>
                            <th class="text-right">Saldo Akhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rolling as $rym => $rData):
                            $isTarget = ($rym === $yearMonth);
                        ?>
                            <tr class="<?= $isTarget ? 'bg-blue-50/70 font-bold' : '' ?>">
                                <td>
                                    <?= $rData['month_label'] ?>
                                    <?php if ($isTarget): ?>
                                        <span class="text-[9px] text-pepsi-blue font-black uppercase">(*)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right font-mono"><?= formatRupiah($rData['opening_balance']) ?></td>
                                <td class="text-right font-mono text-emerald-600"><?= formatRupiah($rData['revenue']) ?></td>
                                <td class="text-right font-mono text-rose-600"><?= formatRupiah($rData['cost']) ?></td>
                                <td class="text-right font-mono text-amber-700"><?= formatRupiah($rData['expense_total']) ?></td>
                                <td class="text-right font-mono font-bold <?= $rData['net_profit'] >= 0 ? 'text-emerald-700' : 'text-rose-700' ?>"><?= formatRupiah($rData['net_profit']) ?></td>
                                <td class="text-right font-mono font-black text-slate-900"><?= formatRupiah($rData['closing_balance']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="text-[9px] text-slate-400 mt-1 italic">(*) Periode bulan yang sedang dicetak pada laporan ini.</p>
            </div>
        <?php endif; ?>

        <!-- Signatures Section -->
        <div class="mt-8 pt-4 border-t border-slate-200">
            <div class="grid grid-cols-2 gap-8 text-center text-xs">
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Penanggung Jawab Pepsi Food &amp; Drink</span>
                    <span class="text-slate-600 text-[11px] block">Pepsi Food &amp; Drink</span>
                    <div class="h-16"></div>
                    <span class="font-bold text-slate-800 border-b border-slate-400 pb-0.5 inline-block min-w-[140px]">( ........................................ )</span>
                    <span class="text-[10px] text-slate-400 block mt-0.5">Staff / Pengelola Pepsi Food &amp; Drink</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Mengetahui &amp; Menyetujui</span>
                    <span class="text-slate-600 text-[11px] block"><?= sanitize($siteName) ?></span>
                    <div class="h-16"></div>
                    <span class="font-bold text-slate-800 border-b border-slate-400 pb-0.5 inline-block min-w-[140px]">( ........................................ )</span>
                    <span class="text-[10px] text-slate-400 block mt-0.5">Pimpinan / Direktur Lembaga</span>
                </div>
            </div>
        </div>

    </div>

</body>

</html>