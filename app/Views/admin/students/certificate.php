<?php
// Template Cetak Sertifikat Kelulusan Resmi - A4 Landscape
// Menggunakan template gambar resmi: kid vs non-kid
$siteName    = $settings['site_name'] ?? 'MR. PEPSI ENGLISH COURSE';
$siteAddress = $settings['address'] ?? 'Kampung Inggris, Pare - Kediri, Jawa Timur';
$sitePhone   = $settings['phone'] ?? '0812-3456-7890';
$director    = $settings['director_name'] ?? 'Fipsi Endrawan, SS.M.Pd.I';

// Nilai ujian akhir berupa kategori predikat (bukan angka)
$grade = strtoupper(trim((string)($grad['grade'] ?? '')));
$legacyMap = ['A' => 'EXCELLENT', 'B' => 'VERY GOOD', 'C' => 'GOOD', 'D' => 'AVERAGE', 'E' => 'POOR'];
if (isset($legacyMap[$grade])) $grade = $legacyMap[$grade];
if ($grade === '') $grade = 'GOOD';

// Resolusi jenjang level program (khusus sertifikat non-kid)
$programLevel = trim((string)($grad['program_level'] ?? ''));
if ($programLevel === '') {
    $programLevel = \App\Models\StudentGraduation::resolveLevel($grad['program_title'] ?? '', $grad['program_code'] ?? '');
} else {
    $programLevel = \App\Models\StudentGraduation::normalizeLevelLabel($programLevel);
}

// Deteksi template: kid atau non-kid
// Cek kolom is_kid (tersimpan di DB), fallback ke deteksi judul
$isKid = (bool)(int)($grad['is_kid'] ?? 0);
if (!$isKid) {
    // fallback: deteksi dari judul program
    $haystack = strtoupper(($grad['program_title'] ?? '') . ' ' . ($grad['program_code'] ?? ''));
    $isKid = str_contains($haystack, 'KID');
}

// Varian template non-kid: 'default' (tanpa ttd) atau 'ttd' (dengan tanda tangan)
$variant = strtolower(trim((string)($variant ?? 'default')));
if (!in_array($variant, ['default', 'ttd'], true)) $variant = 'default';

// URL template gambar
if ($isKid) {
    $templateImg = url('uploads/sertifikat/Templatesertifikatkid.png');
} elseif ($variant === 'ttd') {
    $templateImg = url('uploads/sertifikat/Templatesertifikatnonkidttd.png');
} else {
    $templateImg = url('uploads/sertifikat/Templatesertifikatnonkid.png');
}

// Nama file saat sertifikat diunduh sebagai gambar
$downloadName = 'Sertifikat_' . preg_replace('#[\\\\/:*?"<>|]+#', '-', (string)$grad['certificate_no']) . '_' . preg_replace('#[\\\\/:*?"<>|]+#', '-', (string)$grad['full_name']);

$formattedDate = date('d F Y', strtotime($grad['graduated_at']));

// Nama bulan Indonesia
$bulanId = ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April',
    'May'=>'Mei','June'=>'Juni','July'=>'Juli','August'=>'Agustus',
    'September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember'];
$formattedDateId = str_replace(array_keys($bulanId), array_values($bulanId), $formattedDate);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sertifikat_<?= sanitize($grad['certificate_no']) ?>_<?= sanitize($grad['full_name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Cinzel:wght@500;700;900&family=Playfair+Display:ital,wght@0,600;0,800;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #e2e8f0;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* ── Certificate Container ── */
        .cert-wrapper {
            width: 297mm;
            height: 210mm;
            position: relative;
            margin: 20px auto;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35);
            overflow: hidden;
        }
        @media print {
            body { background: white; }
            .cert-wrapper {
                margin: 0;
                box-shadow: none;
                width: 297mm;
                height: 210mm;
                page-break-after: always;
            }
            .no-print { display: none !important; }
        }

        /* Template background image */
        .cert-bg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
        }

        /* Overlay content layer */
        .cert-overlay {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
        }

        /* ═══════════════════════════════════
           NON-KID OVERLAY POSITIONS
           (Template: elegan, navy-gold, A4 landscape)
           Template size reference: ~1270×900px (approx)
        ═══════════════════════════════════ */
        .overlay-nonkid .cert-no-badge {
            position: absolute;
            top: 26%;           /* Certificate No. below the title heading */
            left: 50%;
            transform: translateX(-50%);
            font-family: 'Cinzel', serif;
            font-size: 11pt;
            font-weight: 600;
            color: #6b4e1a;
            letter-spacing: 0.05em;
            white-space: nowrap;
        }
        .overlay-nonkid .student-name {
            position: absolute;
            top: 43%;           /* Sits on the blank name line */
            left: 50%;
            transform: translateX(-50%);
            font-family: 'Cinzel', serif;
            font-size: 34pt;
            font-weight: 900;
            color: #1a2d5a;
            white-space: nowrap;
            line-height: 1;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .overlay-nonkid .student-nis {
            position: absolute;
            top: 53%;           /* Just below the name divider */
            left: 50%;
            transform: translateX(-50%);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 7.5pt;
            font-weight: 600;
            color: #4a5568;
            white-space: nowrap;
        }
        .overlay-nonkid .program-name {
            position: absolute;
            top: 60%;           /* Program name area */
            left: 50%;
            transform: translateX(-50%);
            font-family: 'Cinzel', serif;
            font-size: 13pt;
            font-weight: 700;
            color: #9a7000;
            text-align: center;
            white-space: nowrap;
            text-transform: uppercase;
        }
        .overlay-nonkid .grade-value {
            position: absolute;
            top: 71%;           /* Grade row */
            left: 50%;
            transform: translateX(-50%);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 11pt;
            font-weight: 800;
            color: #1a2d5a;
            white-space: nowrap;
        }
        .overlay-nonkid .grad-date {
            position: absolute;
            top: 79%;           /* Date row */
            right: 15%;
            font-family: 'Cinzel', serif;
            font-size: 9pt;
            font-weight: 700;
            color: #1a2d5a;
            white-space: nowrap;
        }
        .overlay-nonkid .program-info {
            position: absolute;
            top: 57%;           /* PROGRAM / LEVEL / Result rows */
            left: 50%;
            transform: translateX(-50%);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 16pt;
            font-weight: 600;
            color: #1a2d5a;
            text-align: center;
            white-space: nowrap;
            line-height: 1.1;
        }
        .overlay-nonkid .program-info .lbl {
            display: inline-block;
            min-width: 105px;
            font-family: 'Cinzel', serif;
            font-weight: 700;
            color: #1a2d5a;
        }
        .overlay-nonkid .program-info .row-date {
            color: #4a360f;      /* nomor sertifikat (#6b4e1a), dibuat lebih gelap */
            position: relative;
            left: 280px;         /* digeser ke kanan agar lurus seimbang dengan nama FIPSI */
            top: 22px;           /* posisi vertikal */
        }

        /* ═══════════════════════════════════
           KID OVERLAY POSITIONS
           (Template: colorful, fun, landscape)
           Layout: name line ~34%, grade pill center-left ~63%
        ═══════════════════════════════════ */
        .overlay-kid .cert-no-badge {
            position: absolute;
            top: 5%;
            right: 6%;
            font-family: 'Cinzel', serif;
            font-size: 12pt;
            font-weight: 600;
            color: #57400a;
            letter-spacing: 0.04em;
            white-space: nowrap;
        }
        .overlay-kid .student-name {
            position: absolute;
            top: 40%;
            left: 50%;
            transform: translateX(-50%);
            font-family: 'Playfair Display', serif;
            font-size: 42pt;
            font-weight: 800;
            color: #1a2d5a;
            white-space: nowrap;
            line-height: 1;
        }
        .overlay-kid .student-nis {
            position: absolute;
            top: 49%;
            left: 50%;
            transform: translateX(-50%);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 7.5pt;
            font-weight: 600;
            color: #4a3000;
            white-space: nowrap;
        }
        .overlay-kid .program-name {
            position: absolute;
            top: 55%;
            left: 50%;
            transform: translateX(-50%);
            font-family: 'Cinzel', serif;
            font-size: 28pt;
            font-weight: 700;
            color: #1a2d5a;
            text-align: center;
            white-space: nowrap;
        }
        .overlay-kid .grade-value {
            position: absolute;
            top: 62%;
            left: 62%;
            transform: translateX(-50%);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 23pt;
            font-weight: 800;
            color: #ffffff;
            white-space: nowrap;
            letter-spacing: 0.1em;
        }
        .overlay-kid .grad-date {
            position: absolute;
            top: 71%;
            left: 50%;
            transform: translateX(-50%);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 17pt;
            font-weight: 600;
            color: #4a3000;
            white-space: nowrap;
        }
    </style>
</head>
<body class="py-6">

    <!-- Top Action Bar (Screen Only) -->
    <div class="no-print" style="max-width:800px;margin:0 auto 24px auto;padding:0 16px;">
        <div style="background:#0f172a;color:white;border-radius:16px;padding:16px 20px;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;box-shadow:0 10px 30px -10px rgba(0,0,0,0.4);">
            <div style="display:flex;align-items:center;gap:12px;">
                <a href="<?= url('admin/students/graduates') ?>" style="background:#1e293b;color:#94a3b8;border-radius:10px;padding:8px;display:flex;align-items:center;text-decoration:none;" onmouseover="this.style.color='white'" onmouseout="this.style.color='#94a3b8'">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div>
                    <div style="font-size:13px;font-weight:800;">Pratinjau Sertifikat — <?= $isKid ? '🧒 Program Kids' : ($variant === 'ttd' ? '🎓 Program Reguler + TTD' : '🎓 Program Reguler') ?></div>
                    <div style="font-size:11px;color:#94a3b8;">
                        No: <span style="color:#fbbf24;font-family:monospace;font-weight:bold;"><?= sanitize($grad['certificate_no']) ?></span>
                        &bull; <?= sanitize($grad['full_name']) ?>
                    </div>
                </div>
            </div>
            <div style="display:flex;gap:10px;align-items:center;">
                <span style="font-size:11px;color:<?= $isKid ? '#fb923c' : '#60a5fa' ?>;font-weight:700;background:<?= $isKid ? 'rgba(251,146,60,0.15)' : 'rgba(96,165,250,0.15)' ?>;border:1px solid <?= $isKid ? 'rgba(251,146,60,0.3)' : 'rgba(96,165,250,0.3)' ?>;padding:4px 10px;border-radius:20px;">
                    Template: <?= $isKid ? 'KIDS' : ($variant === 'ttd' ? 'REGULER + TTD' : 'REGULER') ?>
                </span>
                <?php if (!$isKid): ?>
                <a href="<?= url('admin/students/certificate/' . $grad['id']) . ($variant === 'ttd' ? '' : '?v=ttd') ?>" style="font-size:11px;color:#a5b4fc;font-weight:700;background:rgba(129,140,248,0.15);border:1px solid rgba(129,140,248,0.3);padding:8px 14px;border-radius:10px;text-decoration:none;display:flex;align-items:center;gap:6px;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    <?= $variant === 'ttd' ? 'Ganti ke Tanpa TTD' : 'Cetak dengan TTD' ?>
                </a>
                <?php endif; ?>
                <button id="btnDownloadImage" type="button" style="background:#10b981;color:#ffffff;font-weight:800;font-size:12px;padding:10px 20px;border-radius:10px;border:none;cursor:pointer;display:flex;align-items:center;gap:6px;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span class="btn-label">Download Gambar</span>
                </button>
                <button onclick="window.print()" style="background:#f59e0b;color:#1c1917;font-weight:800;font-size:12px;padding:10px 20px;border-radius:10px;border:none;cursor:pointer;display:flex;align-items:center;gap:6px;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Cetak / Simpan PDF
                </button>
            </div>
        </div>
        <p style="text-align:center;font-size:11px;color:#64748b;margin-top:8px;">
            💡 <strong>Tips:</strong> Di dialog print pilih <em>Destination: Save as PDF</em>, <em>Layout: Landscape</em>, dan centang <em>Background graphics</em>.
        </p>
    </div>

    <!-- CERTIFICATE DOCUMENT (A4 LANDSCAPE: 297mm x 210mm) -->
    <div class="cert-wrapper">
        <!-- Background Template Image -->
        <img class="cert-bg" src="<?= $templateImg ?>" alt="Certificate Template">

        <!-- Dynamic Data Overlay -->
        <div class="cert-overlay <?= $isKid ? 'overlay-kid' : 'overlay-nonkid' ?>">

            <!-- Certificate No -->
            <div class="cert-no-badge">
                Certificate No. <?= sanitize($grad['certificate_no']) ?>
            </div>

            <!-- Student Full Name (calligraphic) -->
            <div class="student-name">
                <?= sanitize($grad['full_name']) ?>
            </div>

            <?php if (!$isKid): ?>
            <!-- PROGRAM / LEVEL / Result (non-kid) -->
            <div class="program-info">
                <div class="row"><span class="lbl">PROGRAM</span> : <?= sanitize($grad['program_title']) ?></div>
                <div class="row"><span class="lbl">LEVEL</span> : <?= sanitize($programLevel) ?></div>
                <div class="row"><span class="lbl">Result</span> : <?= sanitize($grade) ?></div>
                <div class="row row-date">PARE,&nbsp; <?= $formattedDateId ?></div>
            </div>
            <?php else: ?>

            <!-- Program Name -->
            <div class="program-name">
                <?= sanitize($grad['program_title']) ?>
            </div>

            <!-- Grade -->
            <div class="grade-value">
                <?= sanitize($grade) ?>
            </div>

            <!-- Date -->
            <div class="grad-date">
                Pare, <?= $formattedDateId ?>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script>
    (function () {
        var btn = document.getElementById('btnDownloadImage');
        if (!btn) return;
        var label = btn.querySelector('.btn-label');
        var originalText = label ? label.textContent : 'Download Gambar';

        btn.addEventListener('click', function () {
            var el = document.querySelector('.cert-wrapper');
            if (!el) { alert('Sertifikat tidak ditemukan.'); return; }
            if (typeof html2canvas === 'undefined') {
                alert('Fitur download gambar gagal dimuat. Periksa koneksi internet lalu coba lagi.');
                return;
            }

            btn.disabled = true;
            btn.style.opacity = '0.7';
            btn.style.cursor = 'wait';
            if (label) label.textContent = 'Memproses...';

            var restore = function () {
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.style.cursor = 'pointer';
                if (label) label.textContent = originalText;
            };

            if (document.fonts && document.fonts.ready) {
                document.fonts.ready.catch(function () {});
            }

            html2canvas(el, {
                scale: 3,
                useCORS: true,
                allowTaint: true,
                backgroundColor: '#ffffff',
                logging: false,
                width: el.offsetWidth,
                height: el.offsetHeight
            }).then(function (canvas) {
                canvas.toBlob(function (blob) {
                    var a = document.createElement('a');
                    var url = URL.createObjectURL(blob);
                    a.href = url;
                    a.download = '<?= addslashes($downloadName) ?>.png';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    setTimeout(function () { URL.revokeObjectURL(url); }, 1500);
                    restore();
                }, 'image/png');
            }).catch(function (err) {
                console.error(err);
                alert('Gagal membuat gambar sertifikat. Silakan coba lagi.');
                restore();
            });
        });
    })();
    </script>

</body>
</html>
