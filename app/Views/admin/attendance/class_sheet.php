<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </span>
                <h1 class="font-heading font-black text-2xl sm:text-3xl text-pepsi-navy">Presensi & Nilai Ujian Siswa</h1>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Cetak lembar presensi kelas 10 pertemuan & daftar siswa per program/ruangan untuk Coach/Tutor.</p>
        </div>

        <?php
            $printParams = [
                'period_id'    => $selectedPeriodId,
                'program_id'   => $selectedProgramId,
                'time_slot'    => $selectedTimeSlot,
                'room_name'    => $roomInput,
                'coach_name'   => $coachInput,
            ];
            $printUrl = url('admin/attendance/print-class-sheet?' . http_build_query($printParams));
        ?>

        <div class="flex items-center gap-2">
            <a href="<?= $printUrl ?>" target="_blank"
               class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs sm:text-sm flex items-center gap-2 shadow-md hover:shadow-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>Cetak Lembar Presensi (A4)</span>
            </a>
        </div>
    </div>

    <!-- Filter & Konfigurasi Kelas Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4 sm:p-5">
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-pepsi-blue"></span>
                <h3 class="font-bold text-slate-800 text-sm sm:text-base">Pilih Kelas & Konfigurasi Lembar Presensi</h3>
            </div>
            <span class="text-xs text-slate-400 font-medium">Ruang & Coach otomatis disarankan dari jadwal</span>
        </div>

        <form method="GET" action="<?= url('admin/attendance/class-sheet') ?>" id="classSheetFilterForm" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
                <!-- 1. Periode -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Periode Belajar</label>
                    <select name="period_id" id="selPeriod" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-pepsi-blue focus:outline-none">
                        <?php foreach ($periods as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= (int)$p['id'] === $selectedPeriodId ? 'selected' : '' ?>>
                                <?= sanitize($p['name']) ?><?= !empty($p['is_active']) ? ' (Aktif)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 2. Program Kursus -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Program Kursus</label>
                    <select name="program_id" id="selProgram" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-pepsi-blue focus:outline-none">
                        <?php foreach ($programs as $pr): ?>
                            <option value="<?= $pr['id'] ?>" <?= (int)$pr['id'] === $selectedProgramId ? 'selected' : '' ?>>
                                <?= sanitize($pr['title']) ?><?= !empty($pr['code']) ? ' (' . sanitize($pr['code']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 3. Jam / Sesi Kelas -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Pilihan Jam / Sesi</label>
                    <select name="time_slot" id="selTimeSlot" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-pepsi-blue focus:outline-none">
                        <option value="all" <?= ($selectedTimeSlot === 'all' || $selectedTimeSlot === '') ? 'selected' : '' ?>>-- Semua Jam Sesi --</option>
                        <?php foreach ($timeSlots as $ts): ?>
                            <?php $isSel = ($selectedTimeSlot === $ts || preg_replace('/\s+/', '', (string)$selectedTimeSlot) === preg_replace('/\s+/', '', (string)$ts)); ?>
                            <option value="<?= sanitize($ts) ?>" <?= $isSel ? 'selected' : '' ?>>
                                Sesi <?= sanitize($ts) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 4. Pilih Ruangan dari Master Data -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Ruang Kelas</label>
                    <select name="room_name" id="inputRoom"
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-pepsi-blue focus:outline-none">
                        <option value="">-- Pilih Ruangan --</option>
                        <?php foreach ($rooms as $rm): ?>
                            <?php $rmName = strtoupper(trim($rm['name'])); ?>
                            <option value="<?= sanitize($rmName) ?>"
                                <?= strtoupper(trim($roomInput)) === $rmName ? 'selected' : '' ?>>
                                <?= sanitize($rm['name']) ?><?= !empty($rm['capacity']) ? ' (Kapasitas: ' . (int)$rm['capacity'] . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 5. Nama Coach / Tutor -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Nama Coach / Tutor
                        <span class="text-[10px] text-slate-400 font-normal">(Bisa diketik)</span>
                    </label>
                    <div class="relative">
                        <input type="text" name="coach_name" id="inputCoach" list="coachList"
                               value="<?= sanitize($coachInput) ?>"
                               placeholder="Contoh: MR. PEPSI"
                               class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-pepsi-blue focus:outline-none uppercase">
                        <datalist id="coachList">
                            <?php foreach ($tutors as $tut): ?>
                                <option value="<?= sanitize(strtoupper($tut['name'])) ?>"><?= sanitize($tut['name']) ?></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                </div>
            </div>

            <!-- Action Toolbar inside Form -->
            <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                <div class="flex items-center gap-2">
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-pepsi-blue hover:bg-pepsi-blue-hover text-white font-bold text-xs flex items-center gap-2 shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Tampilkan &amp; Muat Siswa</span>
                    </button>
                    <button type="button" id="btnResetAuto" class="px-3.5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold text-xs transition" title="Sarankan nama Coach dari master jadwal berdasarkan Program &amp; Jam yang dipilih">
                        Saran Coach dari Jadwal
                    </button>
                </div>

                <div class="flex items-center gap-2 text-xs font-bold">
                    <span class="px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-100">
                        👨‍🎓 <?= count($students) ?> Siswa Terdaftar
                    </span>
                    <?php if (!empty($selectedTimeSlot) && $selectedTimeSlot !== 'all'): ?>
                        <span class="px-3 py-1.5 rounded-lg bg-cyan-50 text-cyan-700 border border-cyan-100">
                            ⏰ Jam <?= sanitize($selectedTimeSlot) ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($roomInput)): ?>
                        <span class="px-3 py-1.5 rounded-lg bg-amber-50 text-amber-800 border border-amber-200">
                            🏫 Ruang <?= sanitize(strtoupper($roomInput)) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <!-- Live Preview Lembar Presensi (Persis Seperti Foto Cetak) -->
    <div class="bg-slate-900/5 p-4 sm:p-8 rounded-3xl border border-slate-200 overflow-x-auto flex flex-col items-center">
        <div class="mb-3 flex items-center justify-between w-full max-w-[850px]">
            <div class="flex items-center gap-2">
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-xs font-extrabold uppercase tracking-wider text-slate-500">Live Preview Lembar Presensi (Standar A4)</span>
            </div>
            <a href="<?= $printUrl ?>" target="_blank"
               class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-700 hover:text-emerald-800 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-200 hover:bg-emerald-100 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                <span>Buka di Tab Baru untuk Cetak</span>
            </a>
        </div>

        <!-- The Paper Box -->
        <?php
            $periodClean = trim(preg_replace('/^periode\s+/i', '', (string)($chosenPeriod['name'] ?? $periods[0]['name'] ?? '10 OKTOBER 2026')));
            $progTitle = strtoupper((string)($chosenProgram['title'] ?? 'SPEAKING FOR IELTS (AC)'));
            $cName     = strtoupper((string)($coachInput ?: 'MR. PEPSI'));
            $rName     = strtoupper((string)($roomInput ?: 'CHARLIE'));
            $slotName  = (string)($selectedTimeSlot && $selectedTimeSlot !== 'all' ? $selectedTimeSlot : '10.00-11.30');

            $skDiknas   = $settings['sk_diknas'] ?? 'SK DIKNAS NO 421.9/3735/418.20/2026';
            $npsn       = $settings['npsn'] ?? 'NPSN: K9997239';
            $address    = $settings['address'] ?? 'Jl. Kemuning Tulungrejo 64212 Pare - Kediri, Jawa Timur';
            $wa         = $settings['admin_wa'] ?? '0813.5974.4144 & 0856.4807.7008';
            $email      = $settings['email'] ?? 'mrpepsikampunginggris@gmail.com';
            $instagram  = $settings['instagram'] ?? 'mrpepsikampunginggris';
            $website    = $settings['website'] ?? 'mrpepsikampunginggris.wordpress.com';
            $logoUrl    = logo_url();

            $TOTAL_ROWS = 26; // Jumlah baris presensi persis format kertas
        ?>

        <div class="bg-white text-black w-full max-w-[850px] p-8 shadow-2xl rounded-sm border border-slate-300 font-sans" style="min-height: 1100px;">
            <!-- Header Paper -->
            <div class="flex items-start justify-between pb-3">
                <!-- Logo Kiri -->
                <div class="flex items-center gap-3">
                    <?php if ($logoUrl): ?>
                        <img src="<?= $logoUrl ?>" alt="Logo" class="h-14 w-auto object-contain">
                    <?php else: ?>
                        <div class="w-12 h-12 rounded-lg bg-black text-white flex items-center justify-center font-black text-xl">
                            P
                        </div>
                    <?php endif; ?>
                    <div>
                        <div class="font-black text-xl tracking-tight text-black leading-tight">MR.PEPSI</div>
                        <div class="text-[9px] font-extrabold tracking-widest text-slate-800 uppercase">UPGRADE</div>
                        <div class="text-[8px] font-bold text-slate-600">KAMPUNG INGGRIS - PARE</div>
                    </div>
                </div>

                <!-- Kontak Kanan -->
                <div class="text-right text-[10px] leading-tight text-black font-semibold">
                    <div class="font-bold"><?= sanitize(strtoupper($skDiknas)) ?> <?= sanitize(strtoupper($npsn)) ?></div>
                    <div><?= sanitize($address) ?></div>
                    <div>Telp. | SMS/WA. <?= sanitize($wa) ?></div>
                    <div>Email: <?= sanitize($email) ?></div>
                </div>
            </div>

            <!-- Garis Tebal Pemisah Header -->
            <div class="border-b-2 border-black mb-4"></div>

            <!-- Title Header -->
            <div class="text-center mb-5">
                <h2 class="text-lg font-black tracking-wide uppercase leading-none">PRESENSI &amp; NILAI UJIAN CLASS</h2>
                <div class="text-xs font-bold uppercase tracking-wider mt-1.5">
                    PERIODE : <span class="border-b border-black font-black pb-0.5"><?= sanitize(strtoupper($periodClean)) ?></span>
                </div>
            </div>

            <!-- Metadata 2 Kolom -->
            <div class="grid grid-cols-2 gap-4 text-xs font-bold mb-3 leading-relaxed">
                <div>
                    <div class="flex">
                        <span class="w-32 uppercase">PROGRAM CLASS</span>
                        <span>: <span class="font-black"><?= sanitize($progTitle) ?></span></span>
                    </div>
                    <div class="flex mt-1">
                        <span class="w-32 uppercase">NAMA COACH</span>
                        <span>: <?= sanitize($cName) ?></span>
                    </div>
                </div>
                <div>
                    <div class="flex">
                        <span class="w-32 uppercase">RUANG KELAS</span>
                        <span>: <span class="font-black"><?= sanitize($rName) ?></span></span>
                    </div>
                    <div class="flex mt-1">
                        <span class="w-32 uppercase">JAM</span>
                        <span>: <?= sanitize($slotName) ?></span>
                    </div>
                </div>
            </div>

            <!-- Tabel Presensi -->
            <table class="w-full border-collapse border border-black text-[10px]">
                <thead>
                    <tr class="text-center font-bold">
                        <th rowspan="2" class="border border-black p-1 text-center w-[4.5%]">NO.</th>
                        <th rowspan="2" class="border border-black p-1.5 uppercase text-left w-[33.5%]">NAMA SISWA (SERTIFIKAT)</th>
                        <th colspan="10" class="border border-black p-1 uppercase text-center w-[45%]">PERTEMUAN KE</th>
                        <th rowspan="2" class="border border-black p-1 uppercase text-center w-[8.5%] leading-tight">NILAI<br>UJIAN</th>
                        <th rowspan="2" class="border border-black p-1 uppercase text-center w-[8.5%] leading-tight">PDF /<br>CETAK</th>
                    </tr>
                    <tr class="text-center font-bold">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <th class="border border-black p-1 w-[4.5%]"><?= $i ?></th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        $rowCount = 0;
                        foreach ($students as $st): 
                            $rowCount++;
                    ?>
                        <tr class="h-6">
                            <td class="border border-black text-center font-bold"><?= $rowCount ?></td>
                            <td class="border border-black px-2 py-1 font-bold truncate text-[11px]"><?= sanitize(strtoupper($st['full_name'])) ?></td>
                            <?php for ($c = 1; $c <= 10; $c++): ?>
                                <td class="border border-black text-center"></td>
                            <?php endfor; ?>
                            <td class="border border-black text-center"></td>
                            <td class="border border-black text-center"></td>
                        </tr>
                    <?php endforeach; ?>

                    <?php 
                        // Baris kosong pelengkap agar memenuhi standar formulir (minimal 25 baris)
                        $remaining = max(1, $TOTAL_ROWS - $rowCount);
                        for ($r = 0; $r < $remaining; $r++): 
                            $rowCount++;
                    ?>
                        <tr class="h-6">
                            <td class="border border-black text-center font-bold text-slate-700"><?= $rowCount ?></td>
                            <td class="border border-black px-2 py-1"></td>
                            <?php for ($c = 1; $c <= 10; $c++): ?>
                                <td class="border border-black text-center"></td>
                            <?php endfor; ?>
                            <td class="border border-black text-center"></td>
                            <td class="border border-black text-center"></td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>

            <!-- Catatan Bawah -->
            <div class="mt-4 text-xs font-bold text-black">
                <div>• CATATAN :</div>
                <div class="h-8"></div>
            </div>

            <!-- Footer Paper -->
            <div class="border-t border-black pt-2 flex items-center justify-between text-[10px] font-bold text-black">
                <div class="flex items-center gap-1.5">
                    <span><?= sanitize($instagram) ?></span>
                </div>
                <div>
                    <span><?= sanitize($website) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script untuk Interaktivitas Auto-fill Jadwal -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const schedules = <?= $schedulesJson ?: '[]' ?>;
    const selPeriod   = document.getElementById('selPeriod');
    const selProgram  = document.getElementById('selProgram');
    const selTimeSlot = document.getElementById('selTimeSlot');
    const inputRoom   = document.getElementById('inputRoom');
    const inputCoach  = document.getElementById('inputCoach');
    const btnReset    = document.getElementById('btnResetAuto');

    // Ruangan adalah dropdown independen dari master data → tidak perlu auto-fill
    // Hanya Coach yang disarankan otomatis berdasarkan jadwal Program + Jam + Periode
    function suggestCoachFromSchedule(force = false) {
        const periodId  = parseInt(selPeriod.value, 10);
        const programId = parseInt(selProgram.value, 10);
        const slotVal   = selTimeSlot.value;

        // Cari jadwal yang cocok (periode + program + jam)
        let found = schedules.find(s => {
            return s.period_id === periodId && s.program_id === programId && (slotVal === 'all' || s.time_slot === slotVal);
        });

        // Fallback: cari program tanpa filter slot
        if (!found) {
            found = schedules.find(s => s.period_id === periodId && s.program_id === programId);
        }

        if (found) {
            // Hanya isi Coach bila kosong atau dipaksa
            if (force || !inputCoach.value) {
                inputCoach.value = found.tutor_name.toUpperCase();
            }
            // Auto-isi jam bila masih 'all' dan force
            if (force && found.time_slot && (slotVal === 'all' || !slotVal)) {
                selTimeSlot.value = found.time_slot;
            }
        }
    }

    selProgram.addEventListener('change', function () {
        suggestCoachFromSchedule(true);
    });

    selTimeSlot.addEventListener('change', function () {
        suggestCoachFromSchedule(false); // jangan paksa timpa coach yang sudah ada
    });

    selPeriod.addEventListener('change', function () {
        suggestCoachFromSchedule(true);
    });

    // Tombol "Saran Coach dari Jadwal" — hanya isi coach, tidak sentuh ruangan
    btnReset.addEventListener('click', function () {
        suggestCoachFromSchedule(true);
    });
});
</script>
