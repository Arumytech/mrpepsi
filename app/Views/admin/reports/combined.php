<?php
// Laporan Keuangan Gabungan — Kursus + Non-Kursus / Pepsi Food & Drink (print-ready)
$siteName    = $settings['app_name'] ?? $settings['site_name'] ?? 'MR.PEPSI UPGRADE';
$siteAddress = $settings['address'] ?? 'Pare, Kediri, Jawa Timur';
$sitePhone   = $settings['admin_wa'] ?? '085648077008';

$logoUrl = logo_url();

$period        = $filters['period'] ?? '';
$paymentStatus = $filters['payment_status'] ?? 'all';
$startDate     = $filters['start_date'] ?? '';
$endDate       = $filters['end_date'] ?? '';

$payLabel = [
    'all'     => 'Semua Pembayaran',
    'paid'    => 'Lunas',
    'partial' => 'Sebagian',
    'unpaid'  => 'Belum Bayar',
][$paymentStatus] ?? 'Semua Pembayaran';

$printedAt = date('d/m/Y H:i');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan Gabungan - <?= sanitize($siteName) ?></title>
    <?php require __DIR__ . '/../../partials/pwa_head.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; background: #e2e8f0; margin: 0; padding: 24px 12px; color: #0f172a; }
        .paper { background: #fff; width: 100%; max-width: 900px; margin: 0 auto; padding: 28px 32px; border-radius: 12px; box-shadow: 0 12px 30px -10px rgba(0,0,0,.25); }
        .report-table { width: 100%; border-collapse: collapse; font-size: 11px; }
        .report-table th, .report-table td { border: 1px solid #cbd5e1; padding: 6px 8px; vertical-align: top; }
        .report-table thead th { background: #12131A; color: #fff; text-align: left; font-weight: 700; text-transform: uppercase; font-size: 10px; letter-spacing: .02em; }
        .report-table tfoot td { background: #f1f5f9; font-weight: 800; }
        @media print {
            body { background: #fff; padding: 0; }
            .paper { max-width: 100%; box-shadow: none; border-radius: 0; padding: 0; }
            .no-print { display: none !important; }
            @page { size: A4 portrait; margin: 12mm; }
            .report-table { font-size: 9.5px; }
            .report-table tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

    <!-- Toolbar (screen only) -->
    <div class="no-print max-w-[900px] mx-auto mb-4 space-y-3">
        <div class="bg-slate-900 text-white rounded-2xl p-4 flex items-center justify-between shadow-lg">
            <a href="<?= url('admin') ?>" class="inline-flex items-center gap-2 text-xs font-bold text-slate-300 hover:text-white transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Kembali ke Dashboard</span>
            </a>
            <button onclick="window.print()" class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-black text-xs flex items-center gap-2 shadow-md transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Cetak / Simpan PDF</span>
            </button>
        </div>

        <form method="GET" action="<?= url('admin/reports/combined') ?>" class="bg-white rounded-2xl p-3.5 border border-slate-200 shadow-sm flex flex-wrap items-center gap-2">
            <span class="text-xs font-bold text-slate-500 mr-1">Filter:</span>
            <select name="period" class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-pepsi-blue">
                <option value="">-- Semua Periode --</option>
                <?php foreach ($periods as $p): ?>
                    <option value="<?= sanitize($p) ?>" <?= $period === $p ? 'selected' : '' ?>><?= sanitize($p) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="payment_status" class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-pepsi-blue">
                <option value="all" <?= $paymentStatus === 'all' ? 'selected' : '' ?>>Semua Pembayaran</option>
                <option value="paid" <?= $paymentStatus === 'paid' ? 'selected' : '' ?>>Lunas</option>
                <option value="partial" <?= $paymentStatus === 'partial' ? 'selected' : '' ?>>Sebagian</option>
                <option value="unpaid" <?= $paymentStatus === 'unpaid' ? 'selected' : '' ?>>Belum Bayar</option>
            </select>
            <select name="payment_method" class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-pepsi-blue">
                <option value="all" <?= ($filters['payment_method'] ?? 'all') === 'all' ? 'selected' : '' ?>>Semua Metode</option>
                <option value="tunai" <?= ($filters['payment_method'] ?? '') === 'tunai' ? 'selected' : '' ?>>💵 Tunai</option>
                <option value="transfer" <?= ($filters['payment_method'] ?? '') === 'transfer' ? 'selected' : '' ?>>🏦 Transfer Bank</option>
            </select>
            <div class="flex items-center gap-1.5 bg-white px-3 py-1.5 rounded-xl border border-slate-200 text-xs">
                <input type="date" name="start_date" value="<?= sanitize($startDate) ?>" class="font-bold text-slate-800 bg-transparent focus:outline-none">
                <span class="text-slate-400 font-bold">s/d</span>
                <input type="date" name="end_date" value="<?= sanitize($endDate) ?>" class="font-bold text-slate-800 bg-transparent focus:outline-none">
            </div>
            <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition">Terapkan</button>
            <a href="<?= url('admin/reports/combined') ?>" class="px-3 py-2 rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 text-xs font-bold transition">Reset</a>
        </form>
        <p class="text-[11px] text-center text-slate-500">
            Laporan gabungan pendapatan <b>Program Kursus</b> (Pepsi Class) dengan <b>Pengeluaran Operasional</b> lembaga.
            Tip: pada dialog cetak pilih <b>Destination: Save as PDF</b> dan centang <b>Background graphics</b>.
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
                <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 block">Laporan</span>
                <span class="text-sm font-black text-slate-900 uppercase leading-tight block">Keuangan Gabungan</span>
                <span class="text-[10px] font-semibold text-slate-500 block">Kursus &amp; Pengeluaran</span>
            </div>
        </div>

        <!-- Meta Info -->
        <div class="grid grid-cols-3 gap-3 text-[11px] py-3 border-b border-slate-200">
            <div><span class="text-slate-400 block">Periode Kursus:</span><span class="font-bold text-slate-800"><?= sanitize($period ?: 'Semua Periode') ?></span></div>
            <div>
                <span class="text-slate-400 block">Rentang Tanggal:</span>
                <span class="font-bold text-slate-800"><?= sanitize(($startDate ?: 'awal') . ' s/d ' . ($endDate ?: 'akhir')) ?></span>
            </div>
            <div class="text-right"><span class="text-slate-400 block">Dicetak:</span><span class="font-bold text-slate-800"><?= $printedAt ?> WIB</span></div>
        </div>

        <!-- Ringkasan Gabungan -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 py-3">
            <div class="rounded-xl border border-blue-200 bg-blue-50 p-2.5">
                <span class="text-[9px] font-bold text-blue-600 uppercase block">Pendapatan Kursus</span>
                <span class="text-base font-black text-blue-800"><?= formatRupiah($combined['income_course']) ?></span>
                <div class="text-[9.5px] text-blue-700 font-bold mt-1 flex flex-wrap items-center gap-1.5">
                    <span>Tunai: <?= formatRupiah($course['total_tunai'] ?? 0) ?></span>
                    <span class="text-blue-300">&bull;</span>
                    <span>Transfer: <?= formatRupiah($course['total_transfer'] ?? 0) ?></span>
                </div>
                <span class="text-[9px] text-blue-500 block mt-0.5"><?= number_format($course['count']) ?> siswa</span>
            </div>
            <div class="rounded-xl border border-slate-300 bg-slate-100 p-2.5">
                <span class="text-[9px] font-bold text-slate-500 uppercase block">Total Pendapatan</span>
                <span class="text-base font-black text-slate-900"><?= formatRupiah($combined['total_income']) ?></span>
            </div>
            <div class="rounded-xl border border-rose-200 bg-rose-50 p-2.5">
                <span class="text-[9px] font-bold text-rose-500 uppercase block">Pengeluaran Operasional</span>
                <span class="text-base font-black text-rose-600"><?= formatRupiah($combined['total_expense'] ?? 0) ?></span>
                <span class="text-[9px] text-rose-400 block"><?= (int)($expenses['count'] ?? 0) ?> catatan</span>
            </div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-2.5">
                <span class="text-[9px] font-bold text-emerald-600 uppercase block">Laba / Rugi Bersih</span>
                <span class="text-base font-black text-emerald-700"><?= formatRupiah($combined['net']) ?></span>
            </div>
            <div class="rounded-xl border border-slate-200 p-2.5">
                <span class="text-[9px] font-bold text-slate-400 uppercase block">Piutang Kursus</span>
                <span class="text-base font-black text-pepsi-red"><?= formatRupiah($combined['receivable']) ?></span>
                <span class="text-[9px] text-slate-400 block">Tingkat koleksi <?= $course['collection_rate'] ?>%</span>
            </div>
        </div>

        <!-- ============ A. KEUANGAN KURSUS (PEPSI CLASS) ============ -->
        <h3 style="font-weight:800; font-size:12px; margin:10px 0 6px; text-transform:uppercase; letter-spacing:.03em; color:#0f172a; border-bottom:2px solid #0f172a; padding-bottom:3px;">
            A. Keuangan Kursus — Pepsi Class
        </h3>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 py-2">
            <div class="rounded-xl border border-slate-200 p-2.5">
                <span class="text-[9px] font-bold text-slate-400 uppercase block">Total Tagihan</span>
                <span class="text-sm font-black text-slate-900"><?= formatRupiah($course['total_fee']) ?></span>
            </div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-2.5">
                <span class="text-[9px] font-bold text-emerald-600 uppercase block">Pendapatan Diterima</span>
                <span class="text-sm font-black text-emerald-700"><?= formatRupiah($course['total_paid']) ?></span>
            </div>
            <div class="rounded-xl border border-red-200 bg-red-50 p-2.5">
                <span class="text-[9px] font-bold text-red-500 uppercase block">Piutang / Sisa Tagihan</span>
                <span class="text-sm font-black text-red-600"><?= formatRupiah($course['total_due']) ?></span>
            </div>
            <div class="rounded-xl border border-slate-200 p-2.5">
                <span class="text-[9px] font-bold text-slate-400 uppercase block">Tingkat Koleksi</span>
                <span class="text-sm font-black text-pepsi-blue"><?= $course['collection_rate'] ?>%</span>
            </div>
        </div>

        <div class="grid grid-cols-4 gap-3 pb-3 text-center text-[10px]">
            <div class="rounded-lg bg-slate-50 border border-slate-200 py-1.5"><b class="block text-sm text-slate-900"><?= number_format($course['count']) ?></b> Total Siswa</div>
            <div class="rounded-lg bg-emerald-50 border border-emerald-100 py-1.5"><b class="block text-sm text-emerald-700"><?= number_format($course['paid']) ?></b> Lunas</div>
            <div class="rounded-lg bg-amber-50 border border-amber-100 py-1.5"><b class="block text-sm text-amber-700"><?= number_format($course['partial']) ?></b> Sebagian</div>
            <div class="rounded-lg bg-red-50 border border-red-100 py-1.5"><b class="block text-sm text-red-600"><?= number_format($course['unpaid']) ?></b> Belum Bayar</div>
        </div>

        <?php if (!empty($programs)): ?>
        <?php
            $totalProgCount = array_sum(array_column($programs, 'count'));
            $totalProgFee   = array_sum(array_column($programs, 'fee'));
        ?>
        <h4 style="font-weight:800; font-size:11px; margin:6px 0 4px; text-transform:uppercase; letter-spacing:.03em; color:#334155;">Rekap per Program Kursus</h4>
        <table class="report-table mb-4">
            <thead>
                <tr>
                    <th style="width:28px" class="text-center">No</th>
                    <th>Program Kursus</th>
                    <th class="text-center" style="width:160px">Jumlah Program Yang Terisi</th>
                    <th class="text-right" style="width:150px">Nilai Program</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($programs as $i => $pr): ?>
                <tr>
                    <td class="text-center"><?= $i + 1 ?></td>
                    <td style="font-weight:700;"><?= sanitize($pr['title']) ?></td>
                    <td class="text-center font-bold"><?= number_format($pr['count']) ?></td>
                    <td class="text-right"><?= formatRupiah($pr['fee']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f1f5f9; font-weight:800;">
                    <td colspan="2" class="text-right font-black">JUMLAH TOTAL</td>
                    <td class="text-center font-black"><?= number_format($totalProgCount) ?> Program</td>
                    <td class="text-right font-black" style="color:#0f172a;"><?= formatRupiah($totalProgFee) ?></td>
                </tr>
            </tfoot>
        </table>
        <?php endif; ?>

        <!-- ============ B. REKONSILIASI GABUNGAN ============ -->
        <h3 style="font-weight:800; font-size:12px; margin:10px 0 6px; text-transform:uppercase; letter-spacing:.03em; color:#0f172a; border-bottom:2px solid #0f172a; padding-bottom:3px;">
            B. Rekonsiliasi Gabungan
        </h3>

        <table class="report-table mb-6">
            <tbody>
                <tr><td colspan="2" style="background:#e2e8f0; font-weight:800; text-transform:uppercase; font-size:10px;">Pendapatan</td></tr>
                <tr>
                    <td>Pendapatan Kursus (terkumpul) — Pepsi Class</td>
                    <td class="text-right" style="font-weight:700;"><?= formatRupiah($combined['income_course']) ?></td>
                </tr>
                <tr style="background:#f8fafc;">
                    <td style="font-weight:800;">Total Pendapatan</td>
                    <td class="text-right" style="font-weight:800;"><?= formatRupiah($combined['total_income']) ?></td>
                </tr>
                <tr><td colspan="2" style="background:#e2e8f0; font-weight:800; text-transform:uppercase; font-size:10px;">Beban</td></tr>
                <tr>
                    <td>Pengeluaran Operasional Lembaga</td>
                    <td class="text-right" style="font-weight:700; color:#dc2626;">(<?= formatRupiah($combined['total_expense'] ?? 0) ?>)</td>
                </tr>
                <tr style="background:#f8fafc;">
                    <td style="font-weight:800;">Total Beban</td>
                    <td class="text-right" style="font-weight:800; color:#dc2626;">(<?= formatRupiah($combined['total_expense'] ?? 0) ?>)</td>
                </tr>
                <tr class="tfoot" style="background:#12131A; color:#fff;">
                    <td style="font-weight:800; color:#fff;">LABA / RUGI BERSIH GABUNGAN</td>
                    <td class="text-right" style="font-weight:800; color:#fff;"><?= formatRupiah($combined['net']) ?></td>
                </tr>
                <tr>
                    <td style="color:#64748b;">Piutang Kursus (belum tertagih)</td>
                    <td class="text-right" style="color:#64748b; font-weight:700;"><?= formatRupiah($combined['receivable']) ?></td>
                </tr>
            </tbody>
        </table>

        <!-- ============ C. RINCIAN PENGELUARAN OPERASIONAL ============ -->
        <?php if (!empty($expenseCategories)): ?>
        <h3 style="font-weight:800; font-size:12px; margin:10px 0 6px; text-transform:uppercase; letter-spacing:.03em; color:#0f172a; border-bottom:2px solid #0f172a; padding-bottom:3px;">
            C. Rincian Pengeluaran Operasional
        </h3>
        <table class="report-table mb-6">
            <thead>
                <tr>
                    <th style="width:28px" class="text-center">No</th>
                    <th>Kategori Pengeluaran</th>
                    <th class="text-center" style="width:90px">Jumlah</th>
                    <th class="text-right" style="width:150px">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($expenseCategories as $i => $ec): ?>
                <tr>
                    <td class="text-center"><?= $i + 1 ?></td>
                    <td style="font-weight:700;"><?= sanitize($ec['category_name']) ?></td>
                    <td class="text-center"><?= (int)$ec['total_count'] ?>x</td>
                    <td class="text-right" style="color:#dc2626; font-weight:700;"><?= formatRupiah($ec['total_amount']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right">TOTAL PENGELUARAN OPERASIONAL</td>
                    <td class="text-right" style="color:#dc2626;"><?= formatRupiah($expenses['amount'] ?? 0) ?></td>
                </tr>
            </tfoot>
        </table>
        <?php endif; ?>

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
