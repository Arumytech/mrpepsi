<?php
// Printable Slip Gaji Tutor & Karyawan (Ukuran Kertas Khusus: Lebar 12 cm, Tinggi 16 cm)
$siteName    = $settings['app_name'] ?? $settings['site_name'] ?? 'MR.PEPSI UPGRADE';
$siteAddress = $settings['address'] ?? 'Pare, Kediri, Jawa Timur';
$sitePhone   = $settings['admin_wa'] ?? '085648077008';
$logoUrl     = logo_url();

$isTutor     = ($payroll['payroll_type'] === 'tutor');
$slipTypeLabel = $isTutor ? 'SLIP GAJI TUTOR / COACH' : 'SLIP GAJI KARYAWAN';
$docNo       = 'SLIP/' . date('Ym', strtotime($payroll['payment_date'])) . '/' . ($isTutor ? 'TUT-' : 'STF-') . str_pad($payroll['id'], 4, '0', STR_PAD_LEFT);

$totalEarnings = (float)$payroll['basic_salary'] + (float)$payroll['bonus_reward'] + (float)$payroll['overtime'];
$netSalary     = (float)$payroll['total_amount'];
$terbilangText = function_exists('terbilang') ? terbilang($netSalary) . ' Rupiah' : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip Gaji - <?= sanitize($payroll['recipient_name']) ?> - <?= sanitize($siteName) ?></title>
    <?php require __DIR__ . '/../../partials/pwa_head.php'; ?>
    <!-- Fonts & Tailwind -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Bricolage+Grotesque:wght@700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; background: #f1f5f9; margin: 0; padding: 20px 10px; color: #0f172a; }
        .slip-card { 
            background: #fff; 
            width: 120mm; 
            max-width: 120mm; 
            min-height: 160mm; 
            margin: 0 auto; 
            padding: 16px 18px; 
            border-radius: 12px; 
            box-shadow: 0 10px 30px -10px rgba(0,0,0,.15); 
        }
        .table-slip { width: 100%; border-collapse: collapse; font-size: 10.5px; }
        .table-slip th, .table-slip td { padding: 6px 8px; border-bottom: 1px solid #f1f5f9; }
        @media print {
            html, body { background: #fff !important; margin: 0 !important; padding: 0 !important; width: 120mm !important; height: 160mm !important; }
            .slip-card { 
                width: 100% !important; 
                max-width: 100% !important; 
                min-height: 100% !important; 
                box-shadow: none !important; 
                border-radius: 0 !important; 
                border: none !important; 
                padding: 6mm 8mm !important; 
                margin: 0 !important; 
            }
            .no-print { display: none !important; }
            @page { 
                size: 12cm 16cm; 
                margin: 0; 
            }
        }
    </style>
</head>
<body>

    <!-- Action Bar (Screen Only) -->
    <div class="w-[120mm] max-w-full mx-auto mb-3 flex items-center justify-between no-print">
        <a href="<?= url('admin/payroll' . ($payroll['period_id'] ? '?period_id=' . $payroll['period_id'] : '')) ?>" 
           class="text-[11px] font-bold text-slate-600 hover:text-black flex items-center gap-1.5 transition">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Kembali</span>
        </a>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="bg-slate-900 hover:bg-slate-800 text-white font-bold text-[11px] px-3.5 py-2 rounded-xl flex items-center gap-1.5 shadow-md transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Cetak Slip (12x16 cm)</span>
            </button>
        </div>
    </div>

    <!-- Container Slip -->
    <div class="slip-card space-y-3 border border-slate-200">

        <!-- Kop Surat -->
        <div class="flex items-center gap-2.5 pb-2.5 border-b-2 border-double border-slate-900">
            <?php if ($logoUrl): ?>
                <img src="<?= $logoUrl ?>" alt="Logo" class="w-11 h-11 object-contain">
            <?php else: ?>
                <div class="w-11 h-11 rounded-xl bg-slate-900 text-white grid place-items-center font-black text-base">MP</div>
            <?php endif; ?>
            <div class="flex-1 min-w-0">
                <h1 class="text-sm font-black tracking-tight text-slate-900 uppercase leading-snug"><?= sanitize($siteName) ?></h1>
                <p class="text-[9px] text-slate-600 font-medium leading-tight truncate"><?= sanitize($siteAddress) ?></p>
                <p class="text-[9px] text-slate-600 font-semibold leading-tight">Telp/WA: <?= sanitize($sitePhone) ?> &bull; Speaking & IELTS</p>
            </div>
            <div class="text-right flex-shrink-0">
                <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-800 text-[8px] font-black uppercase tracking-wider block mb-0.5 text-center">
                    <?= $slipTypeLabel ?>
                </span>
                <p class="text-[9.5px] font-mono font-bold text-slate-700 leading-tight"><?= $docNo ?></p>
                <p class="text-[8.5px] text-slate-400 leading-tight">Tgl: <?= date('d/m/Y') ?></p>
            </div>
        </div>

        <!-- Meta Penerima & Pembayaran -->
        <div class="grid grid-cols-2 gap-2 text-[10px] bg-slate-50 p-2.5 rounded-xl border border-slate-100">
            <div>
                <span class="text-slate-400 block font-semibold text-[9px] uppercase tracking-wider">Nama Penerima:</span>
                <span class="font-bold text-xs text-slate-900 block mt-0.5 leading-snug">
                    <?= $isTutor ? 'Coach ' : '' ?><?= sanitize($payroll['recipient_name']) ?>
                </span>
                <?php if (!$isTutor && !empty($payroll['recipient_role'])): ?>
                <span class="text-slate-500 font-medium block text-[9.5px] leading-tight">
                    <?= sanitize($payroll['recipient_role']) ?>
                </span>
                <?php endif; ?>
            </div>
            <div class="text-right space-y-0.5 text-[9.5px]">
                <div>
                    <?php if ($isTutor): ?>
                        <span class="text-slate-400 text-[9px]">Periode:</span>
                        <span class="font-bold text-slate-800 ml-1">
                            <?= !empty($payroll['period_name']) ? sanitize($payroll['period_name']) : 'Periode Berjalan' ?>
                        </span>
                    <?php else: ?>
                        <span class="text-slate-400 text-[9px]">Gaji Bulan:</span>
                        <span class="font-bold text-slate-800 ml-1">
                            <?= formatBulanTahun($payroll['salary_month'] ?: date('Y-m', strtotime($payroll['payment_date']))) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div>
                    <span class="text-slate-400 text-[9px]">Tgl Bayar:</span>
                    <span class="font-bold text-slate-800 ml-1"><?= tanggalIndo($payroll['payment_date'], 'd M Y') ?></span>
                </div>
                <div>
                    <span class="text-slate-400 text-[9px]">Metode:</span>
                    <span class="font-bold text-slate-800 uppercase ml-1"><?= sanitize($payroll['payment_method']) ?></span>
                </div>
            </div>
        </div>

        <!-- Rincian Penghasilan / Gaji -->
        <div class="space-y-1.5">
            <h3 class="font-bold text-[9.5px] uppercase tracking-wider text-slate-500">Rincian Komponen Gaji:</h3>
            
            <div class="border border-slate-200 rounded-xl overflow-hidden">
                <table class="table-slip">
                    <thead class="bg-slate-100 font-bold text-slate-700 uppercase text-[9px]">
                        <tr>
                            <th class="text-center w-6" style="width: 26px;">No</th>
                            <th>Komponen Gaji</th>
                            <th class="text-center w-[120px]" style="width: 120px;">Keterangan</th>
                            <th class="text-right w-28 whitespace-nowrap" style="width: 110px; white-space: nowrap;">Nominal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr>
                            <td class="text-center font-bold text-slate-400">1</td>
                            <td class="font-bold text-slate-800">
                                <?= $isTutor ? 'Honor Mengajar' : 'Gaji Pokok' ?>
                            </td>
                            <td class="text-center text-slate-600 text-[9.5px] max-w-[120px] break-words leading-tight" style="max-width: 120px; word-break: break-word;">
                                <?= !empty($payroll['notes']) ? sanitize($payroll['notes']) : ($isTutor ? 'Honor periode' : 'Gaji bulanan') ?>
                            </td>
                            <td class="text-right font-semibold text-slate-900 whitespace-nowrap" style="white-space: nowrap;"><?= formatRupiah($payroll['basic_salary']) ?></td>
                        </tr>

                        <?php $itemNo = 2; ?>
                        <?php if ((float)$payroll['bonus_reward'] > 0): ?>
                        <tr>
                            <td class="text-center font-bold text-slate-400"><?= $itemNo++ ?></td>
                            <td class="font-bold text-emerald-700">Reward / Bonus</td>
                            <td class="text-center text-slate-500 text-[9.5px] max-w-[120px] break-words leading-tight" style="max-width: 120px; word-break: break-word;">Tambahan apresiasi</td>
                            <td class="text-right font-bold text-emerald-700 whitespace-nowrap" style="white-space: nowrap;">+<?= formatRupiah($payroll['bonus_reward']) ?></td>
                        </tr>
                        <?php endif; ?>

                        <?php if ((float)$payroll['overtime'] > 0): ?>
                        <tr>
                            <td class="text-center font-bold text-slate-400"><?= $itemNo++ ?></td>
                            <td class="font-bold text-amber-700">Uang Lembur</td>
                            <td class="text-center text-slate-500 text-[9.5px] max-w-[120px] break-words leading-tight" style="max-width: 120px; word-break: break-word;">Lembur kerja</td>
                            <td class="text-right font-bold text-amber-700 whitespace-nowrap" style="white-space: nowrap;">+<?= formatRupiah($payroll['overtime']) ?></td>
                        </tr>
                        <?php endif; ?>

                        <?php if ((float)$payroll['deduction'] > 0): ?>
                        <tr>
                            <td class="text-center font-bold text-slate-400"><?= $itemNo++ ?></td>
                            <td class="font-bold text-red-600">Potongan Gaji</td>
                            <td class="text-center text-slate-500 text-[9.5px] max-w-[120px] break-words leading-tight" style="max-width: 120px; word-break: break-word;">Izin / Kasbon</td>
                            <td class="text-right font-bold text-red-600 whitespace-nowrap" style="white-space: nowrap;">-<?= formatRupiah($payroll['deduction']) ?></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="bg-slate-50 font-bold border-t border-slate-200">
                        <tr>
                            <td colspan="3" class="text-right uppercase tracking-wider text-[9px] py-1.5">Total Diterima (Take Home Pay):</td>
                            <td class="text-right text-[11px] text-slate-900 font-black font-heading bg-emerald-50 text-emerald-800 whitespace-nowrap py-1.5 px-2" style="white-space: nowrap;">
                                <?= formatRupiah($payroll['total_amount']) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Terbilang -->
        <div>
            <?php if ($terbilangText): ?>
            <div class="bg-slate-50 p-2 rounded-lg border border-slate-100 text-[9px] leading-tight">
                <span class="text-slate-400 font-semibold block text-[8px] uppercase tracking-wider">Terbilang:</span>
                <span class="font-bold text-slate-800 italic">"<?= sanitize($terbilangText) ?>"</span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Tanda Tangan Pengesahan (Hanya Owner) -->
        <div class="pt-2 flex justify-end text-[9.5px] text-center border-t border-slate-200">
            <div class="w-44">
                <p class="text-slate-500 leading-tight">Pare, <?= tanggalIndo($payroll['payment_date'], 'd M Y') ?><br>Mengetahui / Menyetujui,<br><span class="font-bold text-slate-800">Owner MR.PEPSI</span></p>
                <div class="h-10"></div>
                <p class="font-black text-slate-900 border-t border-slate-400 pt-1 inline-block min-w-[130px] text-[10px]">
                    MITA KHASANAH
                </p>
                <p class="text-[8.5px] text-slate-500 font-semibold leading-tight">Owner / Pimpinan Lembaga</p>
            </div>
        </div>

        <!-- Footer Note -->
        <div class="text-center text-[8px] text-slate-400 pt-1 border-t border-slate-100">
            Slip gaji ini sah dan diterbitkan resmi oleh Sistem Penggajian MR.PEPSI.
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
