<?php
// Cetak Daftar Tanda Terima Gaji Tutor & Karyawan (Tanpa Nominal Sesuai Permintaan)
$siteName    = $settings['app_name'] ?? $settings['site_name'] ?? 'MR.PEPSI UPGRADE';
$siteAddress = $settings['address'] ?? 'Pare, Kediri, Jawa Timur';
$sitePhone   = $settings['admin_wa'] ?? '085648077008';

$logoUrl = logo_url();
$printedAt = date('d/m/Y H:i');

$periodLabel = isset($period['name']) ? $period['name'] : 'Semua Periode';
$periodRange = (isset($period['start_date'], $period['end_date']))
    ? (tanggalIndo($period['start_date']) . ' s/d ' . tanggalIndo($period['end_date']))
    : '';

$reportType = $reportType ?? ($_GET['type'] ?? 'all'); // 'all', 'tutor', 'kids', 'staff'

// Pisahkan payroll list menjadi tutor reguler, kids/non-kelas, dan staff
$payrollTutors = [];
$payrollKids   = [];
$payrollStaff  = [];

foreach ($payrollList as $p) {
    if ($p['payroll_type'] === 'staff') {
        $payrollStaff[] = $p;
    } elseif (!empty($p['is_kids'])) {
        $payrollKids[] = $p;
    } else {
        $payrollTutors[] = $p;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Tanda Terima Gaji - <?= sanitize($siteName) ?></title>
    <?php require __DIR__ . '/../../partials/pwa_head.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; background: #e2e8f0; margin: 0; padding: 24px 12px; color: #0f172a; }
        .paper { background: #fff; width: 100%; max-width: 860px; margin: 0 auto; padding: 36px 40px; border-radius: 16px; box-shadow: 0 12px 30px -10px rgba(0,0,0,.2); }
        .receipt-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .receipt-table th, .receipt-table td { border: 1px solid #94a3b8; padding: 8px 12px; vertical-align: middle; }
        .receipt-table thead th { background: #0f172a; color: #fff; text-align: left; font-weight: 700; text-transform: uppercase; font-size: 10.5px; letter-spacing: .03em; }
        .sign-cell { height: 50px; text-align: center; vertical-align: bottom; }
        @media print {
            body { background: #fff; padding: 0; }
            .paper { max-width: 100%; box-shadow: none; border-radius: 0; padding: 0; }
            .no-print { display: none !important; }
            @page { size: A4 portrait; margin: 12mm 15mm; }
        }
    </style>
</head>
<body>

    <!-- Toolbar (screen only) -->
    <div class="no-print max-w-[860px] mx-auto mb-4 space-y-3">
        <div class="bg-slate-900 text-white rounded-2xl p-4 flex items-center justify-between shadow-lg">
            <a href="<?= url('admin/payroll' . ($selectedPeriodId ? '?period_id=' . $selectedPeriodId : '')) ?>" class="inline-flex items-center gap-2 text-xs font-bold text-slate-300 hover:text-white transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Kembali ke Penggajian</span>
            </a>
            <div class="flex items-center gap-2">
                <button onclick="window.print()" class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-black text-xs flex items-center gap-2 shadow-md transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span>Cetak Lembar Tanda Terima (A4)</span>
                </button>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-3.5 border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-3">
            <form method="GET" action="<?= url('admin/payroll/report') ?>" class="flex flex-wrap items-center gap-2.5 flex-1">
                <!-- Filter Jenis Tanda Terima -->
                <div class="flex items-center gap-1.5">
                    <span class="text-xs font-bold text-slate-600">Filter Tanda Terima:</span>
                    <select name="type" onchange="this.form.submit()" class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-pepsi-blue">
                        <option value="all" <?= $reportType === 'all' ? 'selected' : '' ?>>Semua Tanda Terima</option>
                        <option value="tutor" <?= $reportType === 'tutor' ? 'selected' : '' ?>>A. Tanda Terima Coach / Tutor (Berdasarkan Periode)</option>
                        <option value="kids" <?= $reportType === 'kids' ? 'selected' : '' ?>>B. Tanda Terima Coach Program Kids & Non-Kelas</option>
                        <option value="staff" <?= $reportType === 'staff' ? 'selected' : '' ?>>C. Tanda Terima Karyawan Kantor (Gaji Bulanan)</option>
                    </select>
                </div>

                <!-- Filter Periode -->
                <div class="flex items-center gap-1.5">
                    <span class="text-xs font-bold text-slate-600">Periode:</span>
                    <select name="period_id" onchange="this.form.submit()" class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-pepsi-blue">
                        <option value="">-- Semua Periode --</option>
                        <?php foreach ($periods as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" <?= $selectedPeriodId == $p['id'] ? 'selected' : '' ?>>
                                <?= sanitize($p['name']) ?> (<?= tanggalIndo($p['start_date'], 'd M') ?> - <?= tanggalIndo($p['end_date']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>

            <!-- Quick Filter Tabs -->
            <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl text-xs font-bold">
                <a href="<?= url('admin/payroll/report?type=all' . ($selectedPeriodId ? '&period_id=' . $selectedPeriodId : '')) ?>" 
                   class="px-3 py-1.5 rounded-lg transition <?= $reportType === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800' ?>">
                    Semua
                </a>
                <a href="<?= url('admin/payroll/report?type=tutor' . ($selectedPeriodId ? '&period_id=' . $selectedPeriodId : '')) ?>" 
                   class="px-3 py-1.5 rounded-lg transition <?= $reportType === 'tutor' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800' ?>">
                    Coach / Tutor
                </a>
                <a href="<?= url('admin/payroll/report?type=kids' . ($selectedPeriodId ? '&period_id=' . $selectedPeriodId : '')) ?>" 
                   class="px-3 py-1.5 rounded-lg transition <?= $reportType === 'kids' ? 'bg-amber-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800' ?>">
                    Program Kids
                </a>
                <a href="<?= url('admin/payroll/report?type=staff' . ($selectedPeriodId ? '&period_id=' . $selectedPeriodId : '')) ?>" 
                   class="px-3 py-1.5 rounded-lg transition <?= $reportType === 'staff' ? 'bg-red-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800' ?>">
                    Karyawan Kantor
                </a>
            </div>
        </div>
    </div>

    <!-- REPORT PAPER -->
    <div class="paper space-y-6">

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
                <p class="text-[11px] text-slate-600 font-semibold">Telp/WA: <?= sanitize($sitePhone) ?> &bull; Lembaga Kursus Speaking & IELTS</p>
            </div>
            <div class="text-right">
                <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-800 text-[10px] font-black uppercase tracking-wider block mb-1">
                    BUKTI TANDA TERIMA
                </span>
                <p class="text-[10px] text-slate-400">Dicetak: <?= $printedAt ?> WIB</p>
            </div>
        </div>

        <!-- Judul Dokumen -->
        <div class="text-center py-1">
            <h2 class="text-lg font-black uppercase tracking-wide text-slate-900 underline underline-offset-4">
                <?php if ($reportType === 'tutor'): ?>
                    Daftar Tanda Terima Penggajian Coach / Tutor
                <?php elseif ($reportType === 'kids'): ?>
                    Daftar Tanda Terima Honor Coach Program Kids & Non-Kelas
                <?php elseif ($reportType === 'staff'): ?>
                    Daftar Tanda Terima Penggajian Karyawan Kantor
                <?php else: ?>
                    Daftar Tanda Terima Penggajian
                <?php endif; ?>
            </h2>
            <p class="text-xs text-slate-600 mt-1 font-medium">
                <?php if ($reportType === 'tutor'): ?>
                    Bukti penerimaan honor Coach / Tutor Kelas Lembaga
                <?php elseif ($reportType === 'kids'): ?>
                    Bukti penerimaan honor Coach / Tutor Program Kids, Online & Non-Kelas Lembaga
                <?php elseif ($reportType === 'staff'): ?>
                    Bukti penerimaan gaji bulanan Karyawan Kantor Lembaga
                <?php else: ?>
                    Bukti penerimaan gaji & honor Coach / Tutor dan Karyawan Kantor Lembaga
                <?php endif; ?>
            </p>
            <?php if ($periodLabel && $periodLabel !== 'Semua Periode' && $reportType !== 'staff'): ?>
            <p class="text-[11px] text-slate-500 font-semibold mt-0.5">
                Periode Kursus: <?= sanitize($periodLabel) ?> <?= $periodRange ? "({$periodRange})" : '' ?>
            </p>
            <?php endif; ?>
        </div>

        <?php if ($reportType === 'all' || $reportType === 'tutor'): ?>
        <!-- A. TANDA TERIMA TUTOR / COACH REGULER (IKUT PERIODE) -->
        <div>
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-900 flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-600 inline-block"></span>
                    <span><?= $reportType === 'tutor' ? 'Tanda Terima Coach / Tutor (Berdasarkan Periode)' : 'A. Tanda Terima Coach / Tutor (Berdasarkan Periode)' ?></span>
                </h3>
                <span class="text-[11px] font-semibold text-slate-400">
                    Total: <?= !empty($payrollTutors) ? count($payrollTutors) : count($tutors) ?> Tutor
                </span>
            </div>

            <table class="receipt-table">
                <thead>
                    <tr>
                        <th style="width: 38px; text-align: center;">No</th>
                        <th>Nama Coach / Tutor</th>
                        <th style="width: 210px; text-align: center;">Periode Kursus</th>
                        <th style="width: 180px; text-align: center;">Tanda Tangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payrollTutors)): ?>
                        <?php if (!empty($tutors)): ?>
                            <?php $no = 1; foreach ($tutors as $t): ?>
                            <tr>
                                <td style="text-align: center; font-weight: 700;"><?= $no++ ?></td>
                                <td class="font-bold">Coach <?= sanitize($t['name']) ?></td>
                                <td style="text-align: center; font-medium text-slate-700;">
                                    <?= sanitize($periodLabel) ?>
                                </td>
                                <td class="sign-cell">
                                    <div class="text-[9px] text-slate-300 text-left pl-2"><?= $no-1 ?>. ....................</div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center; color:#94a3b8; font-style:italic; padding:12px;">Belum ada data tutor pada periode ini.</td></tr>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php $no = 1; foreach ($payrollTutors as $pt): ?>
                        <tr>
                            <td style="text-align: center; font-weight: 700;"><?= $no++ ?></td>
                            <td class="font-bold">Coach <?= sanitize($pt['recipient_name']) ?></td>
                            <td style="text-align: center; font-medium text-slate-700;">
                                <?= !empty($pt['period_name']) ? sanitize($pt['period_name']) : sanitize($periodLabel) ?>
                            </td>
                            <td class="sign-cell">
                                <div class="text-[9px] text-slate-300 text-left pl-2"><?= $no-1 ?>. ....................</div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if ($reportType === 'all' || $reportType === 'kids'): ?>
        <?php if ($reportType === 'kids' || !empty($payrollKids)): ?>
        <!-- B. TANDA TERIMA COACH PROGRAM KIDS, ONLINE & NON-KELAS -->
        <div>
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-900 flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 inline-block"></span>
                    <span><?= $reportType === 'kids' ? 'Tanda Terima Coach Program Kids & Non-Kelas' : 'B. Tanda Terima Coach Program Kids & Non-Kelas' ?></span>
                </h3>
                <span class="text-[11px] font-semibold text-slate-400">
                    Total: <?= count($payrollKids) ?> Coach
                </span>
            </div>

            <table class="receipt-table">
                <thead>
                    <tr>
                        <th style="width: 38px; text-align: center;">No</th>
                        <th>Nama Coach / Tutor</th>
                        <th style="width: 230px; text-align: center;">Program / Kategori</th>
                        <th style="width: 180px; text-align: center;">Tanda Tangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payrollKids)): ?>
                        <tr><td colspan="4" style="text-align:center; color:#94a3b8; font-style:italic; padding:12px;">Belum ada data tanda terima program kids pada periode ini.</td></tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($payrollKids as $pk): ?>
                        <tr>
                            <td style="text-align: center; font-weight: 700;"><?= $no++ ?></td>
                            <td class="font-bold">Coach <?= sanitize($pk['recipient_name']) ?></td>
                            <td style="text-align: center; font-semibold text-amber-700;">
                                Program Kids, Online, Non Kelas
                            </td>
                            <td class="sign-cell">
                                <div class="text-[9px] text-slate-300 text-left pl-2"><?= $no-1 ?>. ....................</div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php if ($reportType === 'all' || $reportType === 'staff'): ?>
        <!-- C. TANDA TERIMA STAFF / KARYAWAN (1 BULAN SEKALI) -->
        <div>
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-900 flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-600 inline-block"></span>
                    <span><?= $reportType === 'staff' ? 'Tanda Terima Karyawan Kantor (Gaji Bulanan)' : ($reportType === 'all' && !empty($payrollKids) ? 'C. Tanda Terima Karyawan Kantor (Gaji Bulanan)' : 'B. Tanda Terima Karyawan Kantor (Gaji Bulanan)') ?></span>
                </h3>
                <span class="text-[11px] font-semibold text-slate-400">
                    Total: <?= !empty($payrollStaff) ? count($payrollStaff) : count($staff) ?> Karyawan
                </span>
            </div>

            <table class="receipt-table">
                <thead>
                    <tr>
                        <th style="width: 38px; text-align: center;">No</th>
                        <th>Nama Karyawan</th>
                        <th style="width: 170px;">Posisi / Jabatan</th>
                        <th style="width: 190px; text-align: center;">Gaji Bulan</th>
                        <th style="width: 170px; text-align: center;">Tanda Tangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payrollStaff)): ?>
                        <?php if (!empty($staff)): ?>
                            <?php $no = 1; foreach ($staff as $s): ?>
                            <tr>
                                <td style="text-align: center; font-weight: 700;"><?= $no++ ?></td>
                                <td class="font-bold"><?= sanitize($s['name']) ?></td>
                                <td class="text-slate-600"><?= sanitize($s['position'] ?? 'Staff Office') ?></td>
                                <td style="text-align: center; font-bold text-slate-800;">
                                    <?= formatBulanTahun(date('Y-m')) ?>
                                </td>
                                <td class="sign-cell">
                                    <div class="text-[9px] text-slate-300 text-left pl-2"><?= $no-1 ?>. ....................</div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center; color:#94a3b8; font-style:italic; padding:12px;">Belum ada data staff office aktif.</td></tr>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php $no = 1; foreach ($payrollStaff as $ps): ?>
                        <tr>
                            <td style="text-align: center; font-weight: 700;"><?= $no++ ?></td>
                            <td class="font-bold"><?= sanitize($ps['recipient_name']) ?></td>
                            <td class="text-slate-600"><?= sanitize($ps['recipient_role'] ?? 'Staff Office') ?></td>
                            <td style="text-align: center; font-bold text-slate-800;">
                                <?= formatBulanTahun($ps['salary_month'] ?: date('Y-m', strtotime($ps['payment_date']))) ?>
                            </td>
                            <td class="sign-cell">
                                <div class="text-[9px] text-slate-300 text-left pl-2"><?= $no-1 ?>. ....................</div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Tanda Tangan Pengesahan -->
        <div class="pt-6 flex justify-end text-xs text-center border-t border-slate-200">
            <div class="min-w-[220px]">
                <p class="text-slate-500">Pare, <?= tanggalIndo(date('Y-m-d'), 'd F Y') ?><br>Mengetahui & Menyetujui,<br><span class="font-bold text-slate-800">Owner MR.PEPSI</span></p>
                <div class="h-20"></div>
                <p class="font-black text-slate-900 border-t border-slate-400 pt-1.5 inline-block min-w-[200px]">
                    MITA KHASANAH
                </p>
                <p class="text-[10px] text-slate-500 mt-0.5 font-semibold">Owner / Pimpinan Lembaga</p>
            </div>
        </div>

        <!-- Footer Note -->
        <div class="text-center text-[10px] text-slate-400 pt-3 border-t border-slate-100">
            Lembar tanda terima fisik ini sah untuk pengarsipan dokumen penggajian internal lembaga MR.PEPSI.
        </div>

    </div>

    <?php if (!empty($_GET['print'])): ?>
    <script>
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 400);
        });
    </script>
    <?php endif; ?>

</body>
</html>
