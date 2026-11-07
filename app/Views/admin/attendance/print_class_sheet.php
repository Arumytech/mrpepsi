<?php
/**
 * Stand-alone Printable View — Lembar Presensi & Nilai Ujian Siswa
 * Format 100% Persis Contoh Fisik Form Cetak MR.PEPSI UPGRADE
 */

$periodClean = trim(preg_replace('/^periode\s+/i', '', (string)$periodName));
$progTitle   = strtoupper((string)$programName);
$cName       = strtoupper((string)($coachName ?: 'MR. PEPSI'));
$rName       = strtoupper((string)($roomName ?: 'CHARLIE'));
$slotName    = (string)($timeSlot && $timeSlot !== 'all' ? $timeSlot : '10.00-11.30');

$skDiknas   = $settings['sk_diknas'] ?? 'SK DIKNAS NO 421.9/3735/418.20/2026';
$npsn       = $settings['npsn'] ?? 'NPSN: K9997239';
$address    = $settings['address'] ?? 'Jl. Kemuning Tulungrejo 64212 Pare - Kediri, Jawa Timur';
$wa         = $settings['admin_wa'] ?? '0813.5974.4144 & 0856.4807.7008';
$email      = $settings['email'] ?? 'mrpepsikampunginggris@gmail.com';
$instagram  = $settings['instagram'] ?? 'mrpepsikampunginggris';
$website    = $settings['website'] ?? 'mrpepsikampunginggris.wordpress.com';
$logoUrl    = logo_url();

$TOTAL_ROWS = 26; // Jumlah baris formulir persis lembar asli
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presensi - <?= sanitize($progTitle) ?> - <?= sanitize($rName) ?></title>
    <style>
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #cbd5e1;
            margin: 0;
            padding: 24px 10px;
            color: #000;
        }
        .paper-container {
            width: 100%;
            max-width: 820px;
            margin: 0 auto;
        }
        .paper {
            background: #fff;
            padding: 28px 36px 24px 36px;
            border-radius: 4px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            min-height: 1080px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Toolbar Screen Only */
        .no-print {
            max-width: 820px;
            margin: 0 auto 16px auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #0f172a;
            color: #fff;
            padding: 12px 18px;
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.2);
            font-size: 13px;
        }
        .no-print a {
            color: #94a3b8;
            text-decoration: none;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .no-print a:hover {
            color: #fff;
        }
        .no-print button {
            background: #10b981;
            color: #022c22;
            border: none;
            padding: 8px 18px;
            border-radius: 8px;
            font-weight: 800;
            font-size: 13px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 8px rgba(16,185,129,0.3);
            transition: all 0.15s;
        }
        .no-print button:hover {
            background: #059669;
            color: #fff;
        }

        /* Header Style */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        .header-logo-cell {
            vertical-align: middle;
            width: 50%;
        }
        .header-info-cell {
            vertical-align: middle;
            text-align: right;
            font-size: 9.5px;
            line-height: 1.35;
            font-weight: 600;
            width: 50%;
        }
        .brand-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .brand-text-title {
            font-weight: 900;
            font-size: 20px;
            letter-spacing: -0.5px;
            line-height: 1;
        }
        .brand-text-sub {
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 2px;
        }
        .brand-text-city {
            font-size: 8px;
            font-weight: bold;
            color: #475569;
        }
        .header-divider {
            border-bottom: 2.5px solid #000;
            margin-top: 6px;
            margin-bottom: 14px;
        }

        /* Title */
        .title-block {
            text-align: center;
            margin-bottom: 16px;
        }
        .main-title {
            font-size: 17px;
            font-weight: 900;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin: 0 0 4px 0;
        }
        .period-title {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .period-name {
            font-weight: 900;
            border-bottom: 1.5px solid #000;
            padding-bottom: 1px;
        }

        /* Metadata Grid */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .meta-table td {
            padding: 2.5px 0;
            vertical-align: top;
        }
        .meta-label {
            width: 115px;
            text-transform: uppercase;
        }
        .meta-sep {
            width: 12px;
            text-align: center;
        }
        .meta-val {
            font-weight: 900;
        }
        .meta-dots {
            border-bottom: 1px dotted #000;
            min-width: 120px;
            display: inline-block;
        }

        /* Presensi Table */
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 4px;
        }
        .attendance-table th, 
        .attendance-table td {
            border: 1px solid #000;
            padding: 4px 3px;
        }
        .attendance-table thead th {
            font-weight: 900;
            text-align: center;
            background: #fff;
        }
        .col-no {
            width: 4.5%;
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            padding: 0 !important;
        }
        .col-name {
            width: 33.5%;
            text-align: left;
            padding-left: 6px !important;
            font-size: 10.5px;
            font-weight: bold;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .col-slot {
            width: 4.5%;
            text-align: center;
            padding: 0 !important;
        }
        .col-grade {
            width: 8.5%;
            text-align: center;
            font-size: 9px;
            line-height: 1.1;
        }
        .col-cert {
            width: 8.5%;
            text-align: center;
            font-size: 9px;
            line-height: 1.1;
        }
        .row-student {
            height: 25px;
        }
        .row-empty {
            height: 25px;
        }

        /* Catatan */
        .notes-section {
            margin-top: 10px;
            font-size: 11px;
            font-weight: bold;
        }
        .notes-lines {
            height: 24px;
        }

        /* Footer */
        .footer-divider {
            border-top: 1.5px solid #000;
            padding-top: 6px;
            margin-top: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 10.5px;
            font-weight: bold;
        }

        /* Print Media Query */
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .paper-container {
                max-width: 100%;
                margin: 0;
            }
            .paper {
                box-shadow: none;
                padding: 0;
                border-radius: 0;
                min-height: auto;
            }
            @page {
                size: A4 portrait;
                margin: 10mm 12mm 10mm 12mm;
            }
            .attendance-table {
                font-size: 9.5px;
            }
            .col-name {
                font-size: 10px;
            }
            .row-student, .row-empty {
                height: 24px;
            }
        }
    </style>
</head>
<body>

    <!-- Toolbar Screen Only -->
    <div class="no-print">
        <a href="<?= url('admin/attendance/class-sheet?period_id=' . (int)$selectedPeriodId . '&program_id=' . (int)$selectedProgramId . '&time_slot=' . urlencode($timeSlot) . '&room_name=' . urlencode($roomName) . '&coach_name=' . urlencode($coachName)) ?>">
            <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Kembali ke Pengaturan Presensi</span>
        </a>

        <div style="display:flex; align-items:center; gap:12px;">
            <span style="font-size:11px; color:#94a3b8;">Format: A4 Portrait</span>
            <button onclick="window.print()">
                <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Cetak / Simpan PDF</span>
            </button>
        </div>
    </div>

    <!-- The Printable Paper Document -->
    <div class="paper-container">
        <div class="paper">
            <div>
                <!-- Header -->
                <table class="header-table">
                    <tr>
                        <td class="header-logo-cell">
                            <div class="brand-wrap">
                                <?php if ($logoUrl): ?>
                                    <img src="<?= $logoUrl ?>" alt="Logo" style="height: 52px; width: auto; object-fit: contain;">
                                <?php else: ?>
                                    <div style="width:44px;height:44px;background:#000;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:18px;border-radius:6px;">P</div>
                                <?php endif; ?>
                                <div>
                                    <div class="brand-text-title">MR.PEPSI</div>
                                    <div class="brand-text-sub">UPGRADE</div>
                                    <div class="brand-text-city">KAMPUNG INGGRIS - PARE</div>
                                </div>
                            </div>
                        </td>
                        <td class="header-info-cell">
                            <div style="font-weight:800;"><?= sanitize(strtoupper($skDiknas)) ?> <?= sanitize(strtoupper($npsn)) ?></div>
                            <div><?= sanitize($address) ?></div>
                            <div>Telp. | SMS/WA. <?= sanitize($wa) ?></div>
                            <div>Email: <?= sanitize($email) ?></div>
                        </td>
                    </tr>
                </table>

                <div class="header-divider"></div>

                <!-- Main Title & Periode -->
                <div class="title-block">
                    <h1 class="main-title">PRESENSI &amp; NILAI UJIAN CLASS</h1>
                    <div class="period-title">
                        PERIODE : <span class="period-name"><?= sanitize(strtoupper($periodClean)) ?></span>
                    </div>
                </div>

                <!-- Metadata (2 Kolom) -->
                <table class="meta-table">
                    <tr>
                        <td style="width: 54%;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td class="meta-label">PROGRAM CLASS</td>
                                    <td class="meta-sep">:</td>
                                    <td class="meta-val"><?= sanitize($progTitle) ?></td>
                                </tr>
                                <tr>
                                    <td class="meta-label">NAMA COACH</td>
                                    <td class="meta-sep">:</td>
                                    <td class="meta-val"><?= sanitize($cName) ?></td>
                                </tr>
                            </table>
                        </td>
                        <td style="width: 46%;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td class="meta-label" style="width: 100px;">RUANG KELAS</td>
                                    <td class="meta-sep">:</td>
                                    <td class="meta-val"><?= sanitize($rName) ?></td>
                                </tr>
                                <tr>
                                    <td class="meta-label" style="width: 100px;">JAM</td>
                                    <td class="meta-sep">:</td>
                                    <td class="meta-val"><?= sanitize($slotName) ?></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <!-- Tabel Presensi 10 Pertemuan -->
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th rowspan="2" class="col-no">NO.</th>
                            <th rowspan="2" class="col-name" style="text-align: center !important;">NAMA SISWA (SERTIFIKAT)</th>
                            <th colspan="10" style="text-align: center;">PERTEMUAN KE</th>
                            <th rowspan="2" class="col-grade">NILAI<br>UJIAN</th>
                            <th rowspan="2" class="col-cert">PDF /<br>CETAK</th>
                        </tr>
                        <tr>
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <th class="col-slot"><?= $i ?></th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            $rowNum = 0;
                            foreach ($students as $stu): 
                                $rowNum++;
                        ?>
                            <tr class="row-student">
                                <td class="col-no"><?= $rowNum ?></td>
                                <td class="col-name"><?= sanitize(strtoupper($stu['full_name'])) ?></td>
                                <?php for ($c = 1; $c <= 10; $c++): ?>
                                    <td class="col-slot"></td>
                                <?php endfor; ?>
                                <td class="col-grade"></td>
                                <td class="col-cert"></td>
                            </tr>
                        <?php endforeach; ?>

                        <?php 
                            // Baris kosong pelengkap agar presensi memenuhi satu halaman penuh (minimal 26 baris)
                            $pad = max(1, $TOTAL_ROWS - $rowNum);
                            for ($r = 0; $r < $pad; $r++): 
                                $rowNum++;
                        ?>
                            <tr class="row-empty">
                                <td class="col-no"><?= $rowNum ?></td>
                                <td class="col-name"></td>
                                <?php for ($c = 1; $c <= 10; $c++): ?>
                                    <td class="col-slot"></td>
                                <?php endfor; ?>
                                <td class="col-grade"></td>
                                <td class="col-cert"></td>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <!-- Bagian Bawah: Catatan & Footer -->
            <div>
                <div class="notes-section">
                    <div>• CATATAN :</div>
                    <div class="notes-lines"></div>
                </div>

                <div class="footer-divider">
                    <div><?= sanitize($instagram) ?></div>
                    <div><?= sanitize($website) ?></div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
