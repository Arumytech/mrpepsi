<?php
// Template Kwitansi / e-Nota Pembayaran Siswa MR. PEPSI — Format Printer Thermal 58mm
$siteName    = $settings['app_name'] ?? $settings['site_name'] ?? 'MR. PEPSI UPGRADE EC';
$siteAddress = $settings['address'] ?? 'Jln.Kemuning Tulungrejo Pare';
$sitePhone   = $settings['admin_wa'] ?? $settings['phone'] ?? '085648077008';

// Logo check
$logoUrl = null;
if (!empty($settings['logo_path']) && file_exists(__DIR__ . '/../../../../' . ltrim($settings['logo_path'], '/'))) {
    $logoUrl = url($settings['logo_path']);
} elseif (file_exists(__DIR__ . '/../../../../uploads/logo/logo-20261008-8563a34e.png')) {
    $logoUrl = url('uploads/logo/logo-20261008-8563a34e.png');
}

$programs = $student['programs'] ?? [];
$totalQty = count($programs) > 0 ? count($programs) : 1;
$totalFee = (float)($student['total_fee'] ?? 0);
$paidAmt  = (float)($student['paid_amount'] ?? 0);
$change   = $paidAmt > $totalFee ? ($paidAmt - $totalFee) : 0;
$remaining = $totalFee > $paidAmt ? ($totalFee - $paidAmt) : 0;

$isLunas = ($paidAmt >= $totalFee && $totalFee > 0);
if ($isLunas) {
    $statusLabel = 'LUNAS';
} elseif ($paidAmt > 0) {
    $statusLabel = 'BAYAR SEBAGIAN';
} else {
    $statusLabel = 'BELUM BAYAR';
}

// Format rupiah ringkas untuk thermal (tanpa ,00)
if (!function_exists('fmtNota')) {
    function fmtNota(float $val): string
    {
        return number_format($val, 0, ',', '.');
    }
}

$createdAt = !empty($student['created_at']) ? strtotime($student['created_at']) : time();
$dateFormatted = date('d/m/Y H:i', $createdAt);

// Bersihkan kata 'Periode' di awal agar tidak tercetak dobel (Label "Periode" + "Periode 10...")
$periodRaw = trim($student['period_name'] ?? '');
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
    <title>Kwitansi_<?= sanitize($invoiceNo) ?>_<?= sanitize($student['full_name']) ?></title>
    <?php require __DIR__ . '/../../partials/pwa_head.php'; ?>
    <style>
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            color: #000;
        }

        body {
            font-family: 'Courier New', 'Space Mono', ui-monospace, monospace;
            font-size: 10px;
            font-weight: 700;
            line-height: 1.35;
            background: #e2e8f0;
            padding: 16px 8px;
        }

        .receipt {
            width: 56mm;
            margin: 0 auto;
            background: #fff;
            padding: 2mm 1mm;
            border-radius: 4px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, .25);
            font-weight: 700;
        }

        .receipt,
        .receipt * {
            font-weight: 700;
        }

        .center {
            text-align: center;
            padding-right: 4ch;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 4px;
            padding-right: 4ch;
        }

        .row>span:last-child {
            white-space: nowrap;
            text-align: right;
            flex-shrink: 0;
            max-width: 60%;
        }

        .b {
            font-weight: 700;
        }

        .bb {
            font-weight: 900;
        }

        .big {
            font-size: 13px;
            font-weight: 900;
        }

        .up {
            text-transform: uppercase;
        }

        .sm {
            font-size: 9px;
        }

        .sep {
            border-top: 1px dashed #000;
            margin: 4px 0;
        }

        .sep-solid {
            border-top: 2px solid #000;
            margin: 4px 0;
        }

        .logo {
            max-width: 44px;
            max-height: 44px;
            object-fit: contain;
        }

        .item-title {
            font-weight: 700;
            word-break: break-word;
            padding-right: 4ch;
        }

        .indent {
            padding-left: 8px;
        }

        .note {
            padding-right: 4ch;
            word-break: break-word;
        }

        .status-stamp {
            text-align: center;
            font-weight: 900;
            font-size: 12px;
            letter-spacing: 2px;
            border: 1.5px dashed #000;
            padding: 1px 6px;
            margin: 4px auto;
            display: inline-block;
        }

        .no-print {}

        @media print {
            @page {
                size: 58mm auto;
                margin: 0;
            }

            body {
                background: #fff;
                padding: 0;
            }

            .receipt {
                width: 56mm;
                box-shadow: none;
                border-radius: 0;
                margin: 0;
                padding: 2mm 1mm;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body>

    <!-- Toolbar (screen only) -->
    <div class="no-print" style="width: 56mm;margin:0 auto 12px;">
        <div style="background:#0f172a;color:#fff;border-radius:10px;padding:10px 12px;display:flex;align-items:center;justify-content:space-between;font-family:'Plus Jakarta Sans',sans-serif;">
            <a href="<?= url('admin/students') ?>" style="color:#cbd5e1;text-decoration:none;font-weight:700;font-size:11px;">&larr; Kembali</a>
            <button onclick="window.print()" style="background:#22c55e;color:#052e16;border:0;border-radius:8px;padding:7px 12px;font-weight:900;font-size:11px;cursor:pointer;">Cetak 58mm</button>
        </div>
        <div style="text-align:center;color:#64748b;font-size:10px;margin-top:6px;font-family:sans-serif;">Kwitansi thermal 58mm &bull; pastikan ukuran kertas printer = 58mm</div>
    </div>

    <!-- RECEIPT -->
    <div class="receipt">

        <!-- Header -->
        <div class="center">
            <?php if ($logoUrl): ?>
                <img src="<?= $logoUrl ?>" alt="Logo" class="logo">
            <?php endif; ?>
            <div class="big up" style="margin-top:2px;"><?= sanitize($siteName) ?></div>
            <div class="sm"><?= sanitize($siteAddress) ?></div>
            <div class="sm">Telp/WA: <?= sanitize($sitePhone) ?></div>
        </div>

        <div class="sep-solid"></div>

        <!-- Invoice & Date -->
        <div class="row">
            <span class="b"><?= sanitize($invoiceNo) ?></span>
            <span><?= $dateFormatted ?></span>
        </div>

        <div class="sep"></div>

        <!-- Student -->
        <div class="row"><span>Nama</span><span class="b"><?= sanitize($student['full_name']) ?></span></div>
        <div class="row"><span>Kota</span><span><?= sanitize($student['city'] ?: '-') ?></span></div>
        <div class="row"><span>WA</span><span><?= sanitize($student['phone']) ?></span></div>
        <?php if (!$isKidStudent && !empty($periodRaw) && $periodDisplay !== '-'): ?>
            <div class="row"><span>Periode</span><span><?= sanitize($periodDisplay) ?></span></div>
        <?php endif; ?>

        <div class="sep"></div>

        <!-- Items -->
        <?php if (!empty($programs)): ?>
            <?php $hasPerProgramSlot = false; ?>
            <?php foreach ($programs as $idx => $p):
                $itemPrice = (float)($p['price'] ?? ($totalFee / max(1, count($programs))));
                if (!empty($p['time_slot'])) $hasPerProgramSlot = true;
            ?>
                <div class="item-title"><?= ($idx + 1) ?>. <?= sanitize($p['title'] ?? $p['code'] ?? 'Program Kursus') ?></div>
                <div class="row indent"><span>1 x @ <?= fmtNota($itemPrice) ?></span><span class="b"><?= fmtNota($itemPrice) ?></span></div>
                <?php if (!empty($p['time_slot'])): ?>
                    <div class="row indent"><span>Jam</span><span><?= sanitize($p['time_slot']) ?></span></div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <?php $hasPerProgramSlot = false; ?>
            <div class="item-title">1. <?= sanitize($student['period_name'] ?: 'Program Bahasa Inggris') ?></div>
            <div class="row indent"><span>1 x @ <?= fmtNota($totalFee) ?></span><span class="b"><?= fmtNota($totalFee) ?></span></div>
        <?php endif; ?>

        <?php if (empty($hasPerProgramSlot) && !empty($student['time_slot'])): ?>
            <div class="row"><span>Jam</span><span><?= sanitize($student['time_slot']) ?></span></div>
        <?php endif; ?>

        <div class="sep"></div>

        <!-- Totals -->
        <div class="row"><span>Total Qty</span><span><?= $totalQty ?></span></div>
        <div class="row big" style="margin-top:2px;"><span>TOTAL</span><span><?= fmtNota($totalFee) ?></span></div>
        <div class="row"><span>BAYAR</span><span><?= fmtNota($paidAmt) ?></span></div>
        <?php if ($paidAmt > 0): ?>
            <div class="row"><span>METODE</span><span class="up"><?= sanitize($student['payment_method'] ?? 'tunai') ?></span></div>
        <?php endif; ?>
        <div class="row"><span>KEMBALI</span><span><?= fmtNota($change) ?></span></div>
        <?php if ($remaining > 0): ?>
            <div class="row b" style="margin-top:2px;"><span>SISA TAGIHAN</span><span><?= fmtNota($remaining) ?></span></div>
        <?php endif; ?>

        <div class="center" style="margin-top:5px;">
            <span class="status-stamp"><?= $statusLabel ?></span>
        </div>

        <div class="sep"></div>

        <!-- Note -->
        <?php if (trim((string)($student['notes'] ?? '')) !== ''): ?>
            <div class="note">Catatan: <?= sanitize($student['notes']) ?></div>

            <div class="sep"></div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="center">
            <div class="b">*** TERIMA KASIH ***</div>
            <div class="sm" style="margin-top:2px;">Pembayaran program tidak bisa</div>
            <div class="sm b up" style="margin-top:2px;line-height:1.3;">DIBATALKAN / DIGANTI<br>PERIODE &amp; KELAS</div>
        </div>

        <div class="center sm" style="margin-top:4px;">[ Printed by MR. PEPSI ]</div>

    </div>

</body>

</html>