<?php
// Template Bukti Pendaftaran Siswa MR. PEPSI (e-Nota Daftar)
$siteName    = $settings['app_name'] ?? $settings['site_name'] ?? 'MR. PEPSI UPGRADE EC';
$siteAddress = $settings['address'] ?? 'Jln.Kemuning Tulungrejo Pare';
$sitePhone   = $settings['admin_wa'] ?? $settings['phone'] ?? '085648077008';

$logoUrl = null;
if (!empty($settings['logo_path']) && file_exists(__DIR__ . '/../../../../' . ltrim($settings['logo_path'], '/'))) {
    $logoUrl = url($settings['logo_path']);
} elseif (file_exists(__DIR__ . '/../../../../uploads/logo/logo-20261008-8563a34e.png')) {
    $logoUrl = url('uploads/logo/logo-20261008-8563a34e.png');
}

$programs = json_decode($reg['programs_json'] ?? '[]', true) ?: [];
$hasPerProgramSlot = !empty(array_filter($programs, fn($p) => !empty($p['time_slot'])));
$totalQty = count($programs) > 0 ? count($programs) : 1;
$totalFee = (float)($reg['total_fee'] ?? 0);

if (!function_exists('fmtDaftar')) {
    function fmtDaftar(float $val): string {
        return number_format($val, 2, ',', '.');
    }
}

$createdAt = !empty($reg['created_at']) ? strtotime($reg['created_at']) : time();
$dateFormatted = date('d/m/Y H:i', $createdAt);
$statusLabel = match($reg['status'] ?? 'pending') {
    'approved' => 'DISETUJUI',
    'pending'  => 'MENUNGGU APPROVAL',
    default    => 'DITOLAK',
};

$periodRaw = trim($reg['period_name'] ?? '');
$periodDisplay = preg_replace('/^periode\s+/i', '', $periodRaw) ?: ($periodRaw ?: '-');

$isKidStudent = false;
if (!empty($programs)) {
    foreach ($programs as $p) {
        if (stripos($p['title'] ?? '', 'KID') !== false || stripos($p['code'] ?? '', 'KID') !== false || (int)($p['category_id'] ?? 0) === 3) {
            $isKidStudent = true;
            break;
        }
    }
}
if (!$isKidStudent && empty($periodRaw)) {
    $isKidStudent = true;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BuktiDaftar_<?= sanitize($reg['registration_no']) ?>_<?= sanitize($reg['student_name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: #f1f5f9;
            margin: 0;
            padding: 20px 10px;
            color: #000000;
        }
        .receipt-card {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            margin: 0 auto;
            padding: 24px 22px;
            border-radius: 8px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        @media print {
            body { background: #ffffff; padding: 0; }
            .receipt-card {
                max-width: 100%;
                width: 100%;
                box-shadow: none;
                border-radius: 0;
                padding: 12px 14px;
            }
            .no-print { display: none !important; }
            @page { margin: 0; size: auto; }
        }
        .watermark-stamp {
            position: absolute;
            top: 55%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-25deg);
            font-size: 52px;
            font-weight: 900;
            letter-spacing: 5px;
            pointer-events: none;
            user-select: none;
            z-index: 10;
            padding: 4px 18px;
            border-radius: 12px;
            border: 4px dashed;
            text-transform: uppercase;
        }
        .stamp-approved { color: rgba(5, 150, 105, 0.25); border-color: rgba(5, 150, 105, 0.25); }
        .stamp-pending  { color: rgba(245, 158, 11, 0.30); border-color: rgba(245, 158, 11, 0.30); }
        .stamp-rejected { color: rgba(220, 38, 38, 0.28); border-color: rgba(220, 38, 38, 0.28); }
    </style>
</head>
<body>

    <div class="no-print max-w-[440px] mx-auto mb-4 space-y-2">
        <div class="bg-slate-900 text-white rounded-2xl p-3.5 flex items-center justify-between shadow-md">
            <a href="<?= url('admin/registrations') ?>" class="inline-flex items-center gap-1.5 text-xs text-slate-300 hover:text-white font-bold transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Kembali</span>
            </a>
            <button onclick="window.print()" class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-black text-xs flex items-center gap-1.5 shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Cetak Bukti</span>
            </button>
        </div>
        <div class="text-[11px] text-center text-slate-500">
            Bukti pendaftaran resmi &bull; Siap cetak printer thermal / A4 / PDF
        </div>
    </div>

    <div class="receipt-card text-black text-xs leading-normal">

        <?php if ($reg['status'] === 'approved'): ?>
            <div class="watermark-stamp stamp-approved">APPROVED</div>
        <?php elseif ($reg['status'] === 'pending'): ?>
            <div class="watermark-stamp stamp-pending">PENDING</div>
        <?php else: ?>
            <div class="watermark-stamp stamp-rejected">CANCELED</div>
        <?php endif; ?>

        <!-- 1. Header -->
        <div class="flex items-start gap-3 pb-3">
            <div class="w-16 h-16 shrink-0 flex items-center justify-center">
                <?php if ($logoUrl): ?>
                    <img src="<?= $logoUrl ?>" alt="MR. PEPSI Logo" class="max-h-16 max-w-16 object-contain">
                <?php else: ?>
                    <div class="w-14 h-14 bg-slate-900 rounded-xl flex flex-col items-center justify-center text-white p-1 text-center">
                        <svg class="w-7 h-7 text-pepsi-red" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg>
                        <span class="text-[7px] font-black leading-tight uppercase">MR.PEPSI</span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="flex-1 leading-snug">
                <h2 class="text-base font-black tracking-wide text-slate-950 uppercase"><?= sanitize($siteName) ?></h2>
                <p class="text-xs text-slate-800 font-medium mt-0.5"><?= sanitize($siteAddress) ?></p>
                <p class="text-xs text-slate-900 font-semibold tracking-wide"><?= sanitize($sitePhone) ?></p>
            </div>
        </div>

        <!-- 2. Title -->
        <div class="text-center py-2">
            <div class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-900 border-y-2 border-slate-900 py-1.5">
                Bukti Pendaftaran Siswa
            </div>
        </div>

        <!-- 3. Date & Registration No -->
        <div class="pt-2 flex items-center justify-between font-medium text-xs">
            <span><?= $dateFormatted ?></span>
            <span class="font-bold tracking-wide font-mono text-xs"><?= sanitize($reg['registration_no']) ?></span>
        </div>
        <div class="border-b-2 border-black my-2"></div>

        <!-- 4. Status -->
        <div class="text-center mb-2">
            <span class="inline-block px-3 py-1 rounded-md text-[10px] font-black tracking-widest uppercase
                <?= $reg['status'] === 'approved' ? 'bg-green-600 text-white' : ($reg['status'] === 'pending' ? 'bg-amber-400 text-slate-950' : 'bg-red-600 text-white') ?>">
                Status: <?= $statusLabel ?>
            </span>
        </div>

        <!-- 5. Student Data -->
        <div class="space-y-0.5 text-xs text-slate-900">
            <div class="font-bold text-sm tracking-tight"><?= sanitize($reg['student_name']) ?></div>
            <div class="text-xs font-medium"><?= sanitize($reg['city'] ?: '-') ?></div>
            <div class="text-xs font-semibold"><?= sanitize($reg['phone']) ?></div>
        </div>
        <div class="border-b-2 border-black my-2"></div>

        <!-- 6. Jadwal -->
        <?php 
        $showPeriod = !$isKidStudent && !empty($periodRaw) && $periodDisplay !== '-';
        $showSlot = !$hasPerProgramSlot && !empty($reg['time_slot']);
        if ($showPeriod || $showSlot):
        ?>
        <div class="space-y-1 pt-1">
            <?php if ($showPeriod): ?>
            <div class="flex items-center justify-between">
                <span class="font-semibold text-slate-800">Periode</span>
                <span class="font-bold text-slate-950"><?= sanitize($periodDisplay) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($showSlot): ?>
            <div class="flex items-center justify-between">
                <span class="font-semibold text-slate-800">Jam Kelas</span>
                <span class="font-bold text-slate-950"><?= sanitize($reg['time_slot']) ?></span>
            </div>
            <?php endif; ?>
        </div>
        <div class="border-b border-black my-2.5"></div>
        <?php endif; ?>

        <!-- 7. Program List -->
        <div class="space-y-3 py-1">
            <?php if (!empty($programs)): ?>
                <?php foreach ($programs as $idx => $p):
                    $itemPrice = (float)($p['price'] ?? ($totalFee / max(1, count($programs))));
                ?>
                    <div class="text-xs">
                        <div class="font-bold text-slate-950">
                            <?= ($idx + 1) ?>) <?= sanitize($p['title'] ?? $p['code'] ?? 'Program Kursus') ?>
                        </div>
                        <div class="flex items-center justify-between text-xs mt-0.5 pl-4">
                            <span class="font-medium text-slate-800">1 x &nbsp;&nbsp;&nbsp; @ <?= fmtDaftar($itemPrice) ?></span>
                            <span class="font-bold text-slate-950"><?= fmtDaftar($itemPrice) ?></span>
                        </div>
                        <?php if (!empty($p['time_slot'])): ?>
                        <div class="flex items-center justify-between text-xs mt-0.5 pl-4">
                            <span class="font-medium text-slate-800">Jam</span>
                            <span class="font-bold text-slate-950"><?= sanitize($p['time_slot']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-xs">
                    <div class="font-bold text-slate-950"><?= sanitize($reg['period_name'] ?: 'Program Bahasa Inggris') ?></div>
                    <div class="flex items-center justify-between text-xs mt-0.5 pl-4">
                        <span class="font-medium text-slate-800">1 x &nbsp;&nbsp;&nbsp; @ <?= fmtDaftar($totalFee) ?></span>
                        <span class="font-bold text-slate-950"><?= fmtDaftar($totalFee) ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="border-b border-black my-2.5"></div>

        <!-- 8. Total -->
        <div class="pt-1 text-xs relative z-20">
            <div class="flex items-start justify-between">
                <span class="font-semibold text-slate-900">Total Qty: <?= $totalQty ?></span>
                <div class="w-56 space-y-1">
                    <div class="flex items-center justify-between font-black text-sm text-slate-950">
                        <span>TOTAL BIAYA</span>
                        <span><?= fmtDaftar($totalFee) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 9. Keterangan -->
        <div class="mt-4 pt-2 text-xs">
            <span class="font-medium text-slate-900">
                Keterangan:&nbsp;&nbsp;<?= sanitize($reg['admin_notes'] ?: 'Pendaftaran via web calon siswa') ?>
            </span>
        </div>

        <!-- 10. Footer -->
        <div class="mt-6 pt-3 text-center space-y-1 border-t border-dashed border-slate-300">
            <p class="font-bold text-xs tracking-wider">*** Terima Kasih ***</p>
            <p class="text-[11px] text-slate-800 leading-tight">
                Segera lakukan pembayaran biaya kursus<br>
                untuk mengkonfirmasi slot kelas Anda.
            </p>
        </div>

    </div>

</body>
</html>