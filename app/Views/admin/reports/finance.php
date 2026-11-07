<?php
// Laporan Keuangan Kursus — standalone print-ready view
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
    <title>Laporan Keuangan Kursus - <?= sanitize($siteName) ?></title>
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

        <form method="GET" action="<?= url('admin/reports/finance') ?>" class="bg-white rounded-2xl p-3.5 border border-slate-200 shadow-sm flex flex-wrap items-center gap-2">
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
            <?php if ($period || $paymentStatus !== 'all' || ($filters['payment_method'] ?? 'all') !== 'all' || $startDate || $endDate): ?>
                <a href="<?= url('admin/reports/finance') ?>" class="px-3 py-2 rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 text-xs font-bold transition">Reset</a>
            <?php endif; ?>
        </form>
        <p class="text-[11px] text-center text-slate-500">
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
                <span class="text-sm font-black text-slate-900 uppercase">Keuangan Kursus</span>
            </div>
        </div>

        <!-- Meta Info -->
        <div class="grid grid-cols-3 gap-3 text-[11px] py-3 border-b border-slate-200">
            <div><span class="text-slate-400 block">Periode:</span><span class="font-bold text-slate-800"><?= sanitize($period ?: 'Semua Periode') ?></span></div>
            <div>
                <span class="text-slate-400 block">Rentang Tanggal:</span>
                <span class="font-bold text-slate-800"><?= sanitize(($startDate ?: 'awal') . ' s/d ' . ($endDate ?: 'akhir')) ?></span>
            </div>
            <div class="text-right"><span class="text-slate-400 block">Dicetak:</span><span class="font-bold text-slate-800"><?= $printedAt ?> WIB</span></div>
        </div>

        <!-- Summary -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 py-3">
            <div class="rounded-xl border border-slate-200 p-2.5">
                <span class="text-[9px] font-bold text-slate-400 uppercase block">Total Tagihan</span>
                <span class="text-base font-black text-slate-900"><?= formatRupiah($summary['total_fee']) ?></span>
            </div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-2.5">
                <span class="text-[9px] font-bold text-emerald-600 uppercase block">Pendapatan Diterima</span>
                <span class="text-base font-black text-emerald-700"><?= formatRupiah($summary['total_paid']) ?></span>
                <div class="text-[9.5px] text-emerald-700 font-bold mt-1 flex flex-wrap items-center gap-1.5">
                    <span>Tunai: <?= formatRupiah($summary['total_tunai'] ?? 0) ?></span>
                    <span class="text-emerald-300">&bull;</span>
                    <span>Transfer: <?= formatRupiah($summary['total_transfer'] ?? 0) ?></span>
                </div>
            </div>
            <div class="rounded-xl border border-red-200 bg-red-50 p-2.5">
                <span class="text-[9px] font-bold text-red-500 uppercase block">Piutang / Sisa Tagihan</span>
                <span class="text-base font-black text-red-600"><?= formatRupiah($summary['total_due']) ?></span>
            </div>
            <div class="rounded-xl border border-slate-200 p-2.5">
                <span class="text-[9px] font-bold text-slate-400 uppercase block">Tingkat Koleksi</span>
                <span class="text-base font-black text-pepsi-blue"><?= $summary['collection_rate'] ?>%</span>
            </div>
        </div>

        <div class="grid grid-cols-4 gap-3 pb-3 text-center text-[10px]">
            <div class="rounded-lg bg-slate-50 border border-slate-200 py-1.5"><b class="block text-sm text-slate-900"><?= number_format($summary['count']) ?> Siswa <span class="text-[10px] font-bold text-amber-700">(<?= number_format($summary['total_programs'] ?? 0) ?> Program)</span></b> Total Pendaftar</div>
            <div class="rounded-lg bg-emerald-50 border border-emerald-100 py-1.5"><b class="block text-sm text-emerald-700"><?= number_format($summary['paid']) ?></b> Lunas</div>
            <div class="rounded-lg bg-amber-50 border border-amber-100 py-1.5"><b class="block text-sm text-amber-700"><?= number_format($summary['partial']) ?></b> Sebagian</div>
            <div class="rounded-lg bg-red-50 border border-red-100 py-1.5"><b class="block text-sm text-red-600"><?= number_format($summary['unpaid']) ?></b> Belum Bayar</div>
        </div>

        <!-- Pembagian per Grup Program: A. Online / B. Offline / C. Non Kelas -->
        <?php
        $groupLetters = ['online' => 'A', 'offline' => 'B', 'non_kelas' => 'C'];
        $groupOrder   = ['online', 'offline', 'non_kelas'];
        foreach ($groupOrder as $gk):
            $g = $groups[$gk];
        ?>
        <div style="margin-top:12px; page-break-inside:avoid;">
            <h3 style="font-weight:800; font-size:12px; margin:8px 0 6px; text-transform:uppercase; letter-spacing:.03em; color:#0f172a; border-bottom:2px solid #0f172a; padding-bottom:3px;">
                <?= $groupLetters[$gk] ?>. <?= sanitize($g['label']) ?>
            </h3>

            <div class="grid grid-cols-4 gap-2 py-1 text-center text-[10px]">
                <div class="rounded-lg bg-slate-50 border border-slate-200 py-1.5">
                    <b class="block text-sm text-slate-900"><?= number_format($g['count']) ?></b>
                    <span>Siswa</span>
                    <?php if (($g['total_programs'] ?? 0) > $g['count']): ?>
                    <span class="block text-[9px] text-amber-700 font-bold">(<?= number_format($g['total_programs']) ?> program terjual)</span>
                    <?php endif; ?>
                </div>
                <div class="rounded-lg border border-slate-200 py-1.5"><b class="block text-sm text-slate-900"><?= formatRupiah($g['total_fee']) ?></b> Tagihan</div>
                <div class="rounded-lg bg-emerald-50 border border-emerald-100 py-1.5"><b class="block text-sm text-emerald-700"><?= formatRupiah($g['total_paid']) ?></b> Dibayar</div>
                <div class="rounded-lg bg-red-50 border border-red-100 py-1.5"><b class="block text-sm text-red-600"><?= formatRupiah($g['total_due']) ?></b> Sisa</div>
            </div>

            <?php if (!empty($g['programs'])): ?>
            <table class="report-table mb-3">
                <thead>
                    <tr>
                        <th style="width:28px" class="text-center">No</th>
                        <th>Program</th>
                        <th class="text-center" style="width:90px">Terjual<br><span style="font-weight:400;font-size:9px;text-transform:none;letter-spacing:0">(unit program)</span></th>
                        <th class="text-right" style="width:140px">Nilai Program</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($g['programs'] as $i => $pr): ?>
                    <tr>
                        <td class="text-center"><?= $i + 1 ?></td>
                        <td style="font-weight:700;"><?= sanitize($pr['title']) ?></td>
                        <td class="text-center"><?= number_format($pr['count']) ?> unit</td>
                        <td class="text-right"><?= formatRupiah($pr['fee']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="text-right">SUBTOTAL <?= sanitize(mb_strtoupper($g['label'])) ?></td>
                        <td class="text-center">
                            <span style="display:block;font-size:10px;font-weight:800"><?= number_format($g['total_programs'] ?? 0) ?> program</span>
                            <span style="display:block;font-size:9px;font-weight:600;color:#64748b"><?= number_format($g['count']) ?> siswa</span>
                        </td>
                        <td class="text-right"><?= formatRupiah($g['total_fee']) ?></td>
                    </tr>
                </tfoot>
            </table>
            <?php else: ?>
            <p style="font-size:11px; color:#94a3b8; padding:6px 2px; margin:0 0 8px;">Belum ada data siswa/program pada bagian ini.</p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

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
