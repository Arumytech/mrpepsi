<?php
// Laporan Data Siswa — standalone print-ready view
$siteName    = $settings['app_name'] ?? $settings['site_name'] ?? 'MR.PEPSI UPGRADE';
$siteAddress = $settings['address'] ?? 'Pare, Kediri, Jawa Timur';
$sitePhone   = $settings['admin_wa'] ?? '085648077008';

$logoUrl = logo_url();

$status    = $filters['status'] ?? 'all';
$search    = $filters['search'] ?? '';
$period    = $filters['period'] ?? '';
$startDate = $filters['start_date'] ?? '';
$endDate   = $filters['end_date'] ?? '';

$statusLabel = [
    'all'       => 'Semua Status',
    'active'    => 'Aktif Belajar',
    'graduated' => 'Lulus',
    'dropped'   => 'Drop Out',
][$status] ?? 'Semua Status';

$printedAt = date('d/m/Y H:i');

// Helper untuk mencocokkan periode dan sesi jam tiap program dari pendaftaran siswa
$regsById = $regsById ?? [];
$regsByPhone = $regsByPhone ?? [];

$resolveStudentProgramSessions = function(array $s) use ($regsById, $regsByPhone): array {
    $progs = json_decode($s['programs_json'] ?? '[]', true) ?: [];
    $studentPeriod = $s['period_name'] ?: '-';
    $studentSlot   = $s['time_slot'] ?: '-';

    // Kumpulkan pendaftaran terkait (dari registration_id maupun nomor HP)
    $matchedRegs = [];
    if (!empty($s['registration_id']) && isset($regsById[$s['registration_id']])) {
        $matchedRegs[] = $regsById[$s['registration_id']];
    }
    $cleanPhone = preg_replace('/[^0-9]/', '', (string)($s['phone'] ?? ''));
    if ($cleanPhone !== '' && isset($regsByPhone[$cleanPhone])) {
        foreach ($regsByPhone[$cleanPhone] as $rg) {
            if (!in_array($rg['id'], array_column($matchedRegs, 'id'))) {
                $matchedRegs[] = $rg;
            }
        }
    }

    // Jika progs kosong di student, ambil dari registrations
    if (empty($progs)) {
        foreach ($matchedRegs as $rg) {
            $rProgs = json_decode($rg['programs_json'] ?? '[]', true) ?: [];
            foreach ($rProgs as $rp) {
                $progs[] = $rp;
            }
        }
    }

    $results = [];
    foreach ($progs as $p) {
        $title  = trim($p['title'] ?? ($p['code'] ?? 'Program'));
        $pid    = (int)($p['id'] ?? 0);
        $slot   = trim($p['time_slot'] ?? '');
        $period = trim($p['period_name'] ?? '');

        // Cari detail sesi/periode di data pendaftaran siswa jika belum ada di record siswa
        if ($slot === '' || $period === '') {
            foreach ($matchedRegs as $rg) {
                $rProgs = json_decode($rg['programs_json'] ?? '[]', true) ?: [];
                foreach ($rProgs as $rp) {
                    $rpId    = (int)($rp['id'] ?? 0);
                    $rpTitle = trim($rp['title'] ?? '');
                    if (($pid > 0 && $rpId === $pid) || ($title !== '' && strcasecmp($title, $rpTitle) === 0)) {
                        if ($slot === '' && !empty($rp['time_slot'])) {
                            $slot = trim($rp['time_slot']);
                        }
                        if ($period === '' && !empty($rp['period_name'])) {
                            $period = trim($rp['period_name']);
                        }
                    }
                }
                // Fallback ke slot/period level registration
                if ($slot === '' && !empty($rg['time_slot']) && count($rProgs) <= 1) {
                    $slot = trim($rg['time_slot']);
                }
                if ($period === '' && !empty($rg['period_name'])) {
                    $period = trim($rg['period_name']);
                }
            }
        }

        if ($slot === '')   $slot = $studentSlot;
        if ($period === '') $period = $studentPeriod;

        $results[] = [
            'id'     => $pid,
            'title'  => $title,
            'period' => $period ?: '-',
            'slot'   => $slot ?: '-',
        ];
    }

    if (empty($results)) {
        $results[] = [
            'id'     => 0,
            'title'  => 'Program Kelas',
            'period' => $studentPeriod,
            'slot'   => $studentSlot,
        ];
    }

    return $results;
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Data Siswa - <?= sanitize($siteName) ?></title>
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
            <a href="<?= url('admin/students') ?>" class="inline-flex items-center gap-2 text-xs font-bold text-slate-300 hover:text-white transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Kembali ke Master Siswa</span>
            </a>
            <button onclick="window.print()" class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-black text-xs flex items-center gap-2 shadow-md transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Cetak / Simpan PDF</span>
            </button>
        </div>

        <form method="GET" action="<?= url('admin/reports/students') ?>" class="bg-white rounded-2xl p-3.5 border border-slate-200 shadow-sm flex flex-wrap items-center gap-2">
            <span class="text-xs font-bold text-slate-500 mr-1">Filter:</span>
            <div class="flex items-center gap-1.5 bg-slate-50 px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs">
                <input type="date" name="start_date" value="<?= sanitize($startDate) ?>" class="text-xs font-semibold text-slate-700 bg-transparent focus:outline-none" title="Tanggal Mulai">
                <span class="text-slate-400 font-bold text-[11px]">s/d</span>
                <input type="date" name="end_date" value="<?= sanitize($endDate) ?>" class="text-xs font-semibold text-slate-700 bg-transparent focus:outline-none" title="Tanggal Akhir">
            </div>
            <select name="status" class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-pepsi-blue">
                <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Semua Status</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Aktif Belajar</option>
                <option value="graduated" <?= $status === 'graduated' ? 'selected' : '' ?>>Lulus</option>
                <option value="dropped" <?= $status === 'dropped' ? 'selected' : '' ?>>Drop Out</option>
            </select>
            <select name="period" class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-pepsi-blue">
                <option value="">-- Semua Periode --</option>
                <?php foreach ($periods as $p): ?>
                    <option value="<?= sanitize($p) ?>" <?= $period === $p ? 'selected' : '' ?>><?= sanitize($p) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Cari nama / kontak / kota..."
                   class="flex-1 min-w-[160px] px-3 py-2 rounded-xl border border-slate-200 text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-pepsi-blue">
            <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition">Terapkan</button>
            <?php if ($search || $period || $status !== 'all' || !empty($startDate) || !empty($endDate)): ?>
                <a href="<?= url('admin/reports/students') ?>" class="px-3 py-2 rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 text-xs font-bold transition">Reset</a>
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
                <span class="text-sm font-black text-slate-900 uppercase">Data Siswa</span>
            </div>
        </div>

        <!-- Meta Info -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-[11px] py-3 border-b border-slate-200">
            <div><span class="text-slate-400 block">Status:</span><span class="font-bold text-slate-800"><?= sanitize($statusLabel) ?></span></div>
            <div><span class="text-slate-400 block">Periode:</span><span class="font-bold text-slate-800"><?= sanitize($period ?: 'Semua Periode') ?></span></div>
            <div>
                <span class="text-slate-400 block">Rentang Tgl:</span>
                <span class="font-bold text-slate-800">
                    <?= (!empty($startDate) || !empty($endDate)) ? sanitize((!empty($startDate) ? date('d/m/Y', strtotime($startDate)) : 'Awal') . ' - ' . (!empty($endDate) ? date('d/m/Y', strtotime($endDate)) : 'Sekarang')) : 'Semua Tanggal' ?>
                </span>
            </div>
            <div class="text-left sm:text-right"><span class="text-slate-400 block">Dicetak:</span><span class="font-bold text-slate-800"><?= $printedAt ?> WIB</span></div>
        </div>

        <!-- Summary -->
        <div class="grid grid-cols-5 gap-2.5 py-3">
            <div class="rounded-xl border border-slate-200 p-2.5">
                <span class="text-[9px] font-bold text-slate-400 uppercase block">Total Siswa</span>
                <span class="text-base font-black text-slate-900"><?= number_format($summary['total']) ?> <span class="text-[10px] font-semibold text-slate-400">Siswa</span></span>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-2.5">
                <span class="text-[9px] font-bold text-amber-800 uppercase block">Program Terjual</span>
                <span class="text-base font-black text-amber-600"><?= number_format($summary['total_programs'] ?? 0) ?> <span class="text-[10px] font-semibold text-amber-700">Program</span></span>
            </div>
            <div class="rounded-xl border border-slate-200 p-2.5">
                <span class="text-[9px] font-bold text-slate-400 uppercase block">Aktif / Lulus</span>
                <span class="text-base font-black text-emerald-600"><?= number_format($summary['active']) ?> <span class="text-slate-300">/</span> <span class="text-amber-600"><?= number_format($summary['graduated']) ?></span></span>
            </div>
            <div class="rounded-xl border border-slate-200 p-2.5">
                <span class="text-[9px] font-bold text-slate-400 uppercase block">Total Biaya</span>
                <span class="text-xs font-black text-slate-900 block mt-1"><?= formatRupiah($summary['total_fee']) ?></span>
            </div>
            <div class="rounded-xl border border-slate-200 p-2.5">
                <span class="text-[9px] font-bold text-slate-400 uppercase block">Terbayar / Sisa</span>
                <span class="text-xs font-black text-emerald-600 block mt-1"><?= formatRupiah($summary['total_paid']) ?> <br><span class="text-red-500 font-bold"><?= formatRupiah($summary['total_due']) ?></span></span>
            </div>
        </div>

        <!-- Data Table -->
        <table class="report-table mt-1">
            <thead>
                <tr>
                    <th style="width:30px" class="text-center">No</th>
                    <th>Nama Siswa</th>
                    <th>Kontak &amp; Kota</th>
                    <th>Program Kursus</th>
                    <th>Periode &amp; Jam</th>
                    <th class="text-right">Biaya</th>
                    <th class="text-right">Dibayar</th>
                    <th class="text-right">Sisa</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="9" style="text-align:center; padding:18px; color:#94a3b8;">Belum ada data siswa sesuai filter ini.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $i => $r):
                        $fee  = (float)$r['total_fee'];
                        $paid = (float)$r['paid_amount'];
                        $due  = max(0, $fee - $paid);
                        $programSessions = $resolveStudentProgramSessions($r);
                    ?>
                    <tr>
                        <td class="text-center"><?= $i + 1 ?></td>
                        <td><span style="font-weight:800; font-size:11px;"><?= sanitize($r['full_name']) ?></span></td>
                        <td><?= sanitize($r['phone']) ?><br><span style="color:#64748b;"><?= sanitize($r['city'] ?: '-') ?></span></td>
                        <td>
                            <?php if (!empty($programSessions)): ?>
                                <div style="display:flex; flex-direction:column; gap:2px;">
                                    <?php foreach ($programSessions as $ps): ?>
                                        <div style="font-weight:700; font-size:10.5px;"><?= sanitize($ps['title']) ?></div>
                                    <?php endforeach; ?>
                                </div>
                                <?php if (count($programSessions) > 1): ?>
                                    <span style="display:inline-block; font-size:8.5px; font-weight:800; color:#1e40af; background:#dbeafe; padding:1px 5px; border-radius:4px; margin-top:3px; width:fit-content;"><?= count($programSessions) ?> Program</span>
                                <?php endif; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (count($programSessions) > 1): ?>
                                <div style="display:flex; flex-direction:column; gap:4px;">
                                    <?php foreach ($programSessions as $ps): ?>
                                        <div style="font-size:10px; line-height:1.3; padding-bottom:3px; border-bottom:1px dashed #e2e8f0;">
                                            <span style="font-weight:800; color:#1e40af;"><?= sanitize($ps['title']) ?>:</span><br>
                                            <span style="font-weight:700; color:#0f172a;"><?= sanitize($ps['period']) ?></span><br>
                                            <span style="color:#475569; font-weight:600;"><?= sanitize($ps['slot']) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span style="font-weight:700;"><?= sanitize($programSessions[0]['period'] ?? ($r['period_name'] ?: '-')) ?></span><br>
                                <span style="color:#64748b; font-weight:600; font-size:10px;"><?= sanitize($programSessions[0]['slot'] ?? ($r['time_slot'] ?: '-')) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right"><?= formatRupiah($fee) ?></td>
                        <td class="text-right">
                            <?= formatRupiah($paid) ?>
                            <?php if ($paid > 0): ?>
                                <br><span style="font-size:9px; color:<?= ($r['payment_method'] ?? 'tunai') === 'transfer' ? '#2563eb' : '#64748b' ?>; font-weight:700; text-transform:uppercase;"><?= sanitize($r['payment_method'] ?? 'tunai') ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right" style="<?= $due > 0 ? 'color:#dc2626; font-weight:700;' : '' ?>"><?= formatRupiah($due) ?></td>
                        <td class="text-center">
                            <?php if ($r['status'] === 'active'): ?>Aktif
                            <?php elseif ($r['status'] === 'graduated'): ?>Lulus
                            <?php else: ?>Drop<?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="text-right">JUMLAH (<?= number_format($summary['total']) ?> Siswa &bull; <?= number_format($summary['total_programs'] ?? 0) ?> Program Terjual)</td>
                    <td class="text-right"><?= formatRupiah($summary['total_fee']) ?></td>
                    <td class="text-right"><?= formatRupiah($summary['total_paid']) ?></td>
                    <td class="text-right" style="color:#dc2626;"><?= formatRupiah($summary['total_due']) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <!-- Signature -->
        <div class="grid grid-cols-2 gap-12 pt-10 text-[11px] text-center">
            <div></div>
            <div>
                <p class="text-slate-500">Pare, <?= date('d F Y') ?><br>Pimpinan Lembaga,</p>
                <div class="h-16"></div>
                <p class="font-bold text-slate-900 border-t border-slate-400 pt-1 inline-block min-w-[180px]">MR.PEPSI Management</p>
            </div>
        </div>
    </div>

</body>
</html>
