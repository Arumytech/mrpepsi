<?php
// Admin Dashboard View
?>
<div x-data="{
    payModal: false,
    payData: {},
    payAmount: 0,
    payMethod: 'tunai',
    openPay(student) {
        this.payData = student;
        this.payAmount = Math.max(0, (parseFloat(student.total_fee) || 0) - (parseFloat(student.paid_amount) || 0));
        this.payMethod = student.payment_method || 'tunai';
        this.payModal = true;
    }
}" class="space-y-6">

    <!-- Header greeting -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-heading font-black text-2xl sm:text-3xl text-pepsi-navy">Dashboard Utama</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Ringkasan akademik, jadwal kelas, presensi harian coach, dan LAPORAN KEUANGAN PROGRAM PEPSI CLASS dan PEPSI FOOD &amp; DRINK.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3.5 py-1.5 rounded-full text-xs font-bold bg-blue-100 text-pepsi-blue">
                Periode Aktif: <?= sanitize($activePeriod['name'] ?? 'Belum ada periode') ?>
            </span>
        </div>
    </div>

    <!-- Stat Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6 gap-4">
        <!-- Card 0: Pendaftaran -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Pendaftaran Siswa</span>
                <span class="font-heading font-black text-3xl text-sky-600 mt-1 block"><?= $regStats['total'] ?? 0 ?></span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center font-bold text-xl">
                📝
            </div>
        </div>

        <!-- Card: Total Siswa Aktif -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Siswa Aktif</span>
                <span class="font-heading font-black text-3xl text-violet-600 mt-1 block"><?= $activeStudents ?? 0 ?></span>
                <span class="text-[11px] text-slate-500">Siswa kursus aktif</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-violet-50 text-violet-600 flex items-center justify-center font-bold text-xl">
                👥
            </div>
        </div>

        <!-- Card 1 -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Program</span>
                <span class="font-heading font-black text-3xl text-pepsi-navy mt-1 block"><?= $totalPrograms ?></span>
                <span class="text-[11px] text-slate-500">Speaking & IELTS</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-pepsi-blue flex items-center justify-center font-bold text-xl">
                📚
            </div>
        </div>

        <!-- Card 2 -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Tutor / Coach</span>
                <span class="font-heading font-black text-3xl text-pepsi-red mt-1 block"><?= $totalTutors ?></span>
                <span class="text-[11px] text-slate-500">Coach Pengajar</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-red-50 text-pepsi-red flex items-center justify-center font-bold text-xl">
                👨‍🏫
            </div>
        </div>

        <!-- Card 3 -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Jadwal Kelas</span>
                <span class="font-heading font-black text-3xl text-emerald-600 mt-1 block"><?= $totalSchedules ?></span>
                <span class="text-[11px] text-slate-500">Matriks slot aktif</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xl">
                🗓️
            </div>
        </div>

        <!-- Card 4 -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Omset Pepsi Food &amp; Drink Hari Ini</span>
                <span class="font-heading font-black text-xl text-slate-900 mt-1 block"><?= formatRupiah($todayPos['total_revenue'] ?? 0) ?></span>
                <span class="text-[11px] text-green-600 font-semibold">Laba: <?= formatRupiah($todayPos['gross_profit'] ?? 0) ?></span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-xl">
                🛒
            </div>
        </div>
    </div>

    <!-- Unpaid Students -->
    <?php
    $unpaidSummary = $unpaidSummary ?? ['count' => 0, 'total_due' => 0];
    $unpaidStudents = $unpaidStudents ?? [];
    ?>
    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
            <div>
                <h3 class="font-heading font-bold text-lg text-slate-900 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-red-50 text-pepsi-red flex items-center justify-center text-base">💳</span>
                    Siswa Belum Bayar
                </h3>
                <p class="text-xs text-slate-500 mt-1">
                    Siswa yang belum melunasi biaya kursus (belum bayar / bayar sebagian).
                    Total sisa tagihan <b class="text-pepsi-red"><?= formatRupiah($unpaidSummary['total_due'] ?? 0) ?></b>
                    dari <b><?= (int)($unpaidSummary['count'] ?? 0) ?></b> siswa.
                </p>
            </div>
            <a href="<?= url('admin/reports/finance?payment_status=unpaid') ?>" class="text-xs font-bold text-pepsi-blue hover:underline whitespace-nowrap">
                Laporan Keuangan &rarr;
            </a>
        </div>

        <?php if (empty($unpaidStudents)): ?>
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-100 text-emerald-700 text-xs font-bold text-center">
                🎉 Semua siswa sudah melunasi pembayaran.
            </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-medium">
                <thead class="bg-slate-50 text-slate-500 uppercase font-bold text-[10px]">
                    <tr>
                        <th class="px-4 py-3">Siswa</th>
                        <th class="px-4 py-3">Program</th>
                        <th class="px-4 py-3">Periode</th>
                        <th class="px-4 py-3 text-right">Tagihan</th>
                        <th class="px-4 py-3 text-right">Dibayar</th>
                        <th class="px-4 py-3 text-right">Sisa</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($unpaidStudents as $u):
                        $fee  = (float)$u['total_fee'];
                        $paid = (float)$u['paid_amount'];
                        $due  = max(0, $fee - $paid);
                        $progs = json_decode($u['programs_json'] ?? '[]', true) ?: [];
                        $wa = 'https://wa.me/' . preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $u['phone']));
                    ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <div class="font-extrabold text-slate-800"><?= sanitize($u['full_name']) ?></div>
                            <div class="font-mono text-[10px] text-pepsi-blue font-bold"><?= sanitize($u['student_no']) ?></div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                <?php if (!empty($progs)): ?>
                                    <?php foreach ($progs as $p): ?>
                                        <span class="inline-block px-2 py-0.5 rounded-md bg-blue-50 text-pepsi-blue border border-blue-100 font-semibold text-[10px]">
                                            <?= sanitize($p['title'] ?? $p['code'] ?? 'Program') ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-slate-400">-</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-bold text-slate-700"><?= sanitize($u['period_name'] ?: '-') ?></div>
                            <div class="text-[10px] text-slate-500"><?= sanitize($u['time_slot'] ?: '-') ?></div>
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-slate-800"><?= formatRupiah($fee) ?></td>
                        <td class="px-4 py-3 text-right font-bold text-emerald-600"><?= formatRupiah($paid) ?></td>
                        <td class="px-4 py-3 text-right font-black text-pepsi-red"><?= formatRupiah($due) ?></td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($u['payment_status'] === 'partial'): ?>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-700 border border-amber-200">Sebagian</span>
                            <?php else: ?>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-200">Belum Bayar</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                <button @click="openPay(<?= htmlspecialchars(json_encode($u)) ?>)" title="Catat Pembayaran" target="_blank"
                                   class="px-2.5 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition text-[10px] font-extrabold whitespace-nowrap">
                                    💵 Bayar
                                </button>
                                <a href="<?= $wa ?>" target="_blank" title="Ingatkan via WhatsApp"
                                   class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white transition">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                </a>
                                <a href="<?= url('admin/students/receipt/' . $u['id']) ?>" target="_blank" title="Cetak Kwitansi"
                                   class="p-1.5 rounded-lg bg-slate-100 text-slate-600 hover:bg-pepsi-blue hover:text-white transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Attendance Today Status -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
            <div>
                <h3 class="font-heading font-bold text-lg text-slate-900">Presensi Tutor Hari Ini (<?= date('d M Y') ?>)</h3>
                <p class="text-xs text-slate-500">Monitoring kehadiran tutor utama & tutor pengganti sesi hari ini.</p>
            </div>
            <a href="<?= url('admin/attendance?date=' . $today) ?>" class="text-xs font-bold text-pepsi-blue hover:underline">
                Buka Lembar Presensi Lengkap &rarr;
            </a>
        </div>

        <div class="grid grid-cols-3 gap-3 text-center">
            <div class="p-3 bg-green-50 rounded-2xl border border-green-100">
                <span class="text-xs text-green-700 font-bold block">Tutor Hadir</span>
                <span class="font-heading font-black text-2xl text-green-700"><?= $presentCount ?></span>
            </div>
            <div class="p-3 bg-amber-50 rounded-2xl border border-amber-100">
                <span class="text-xs text-amber-700 font-bold block">Digantikan (Substitusi)</span>
                <span class="font-heading font-black text-2xl text-amber-700"><?= $substitutedCount ?></span>
            </div>
            <div class="p-3 bg-red-50 rounded-2xl border border-red-100">
                <span class="text-xs text-red-700 font-bold block">Tidak Hadir / Izin</span>
                <span class="font-heading font-black text-2xl text-red-700"><?= $absentCount ?></span>
            </div>
        </div>
    </div>

    <!-- Room Occupancy -->
    <?php
    $occ = $occupancy ?? ['rooms' => [], 'total_students' => 0, 'unassigned' => 0];
    $occUsed = 0;
    $occCap  = 0;
    foreach ($occ['rooms'] as $rm) {
        $occUsed += (int)$rm['used'];
        $occCap  += (int)$rm['capacity'];
    }
    $occPct = $occCap > 0 ? (int)round($occUsed / $occCap * 100) : 0;
    ?>
    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
            <div>
                <h3 class="font-heading font-bold text-lg text-slate-900">Okupansi Ruangan Kelas</h3>
                <p class="text-xs text-slate-500">
                    Jumlah siswa aktif yang menempati tiap ruangan pada periode aktif. Total
                    <b><?= (int)$occ['total_students'] ?></b> siswa ditempatkan.
                </p>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                <div class="text-right">
                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Terpakai</span>
                    <span class="font-heading font-black text-lg text-pepsi-navy"><?= $occUsed ?> <span class="text-slate-400 text-sm font-bold">/ <?= $occCap ?> kursi</span></span>
                </div>
                <span class="px-2.5 py-1.5 rounded-xl text-xs font-black <?= $occPct >= 100 ? 'bg-red-50 text-pepsi-red' : ($occPct >= 70 ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700') ?>"><?= $occPct ?>%</span>
                <a href="<?= url('admin/rooms') ?>" class="text-xs font-bold text-pepsi-blue hover:underline hidden sm:inline">Kelola &rarr;</a>
            </div>
        </div>

        <?php if (empty($occ['rooms'])): ?>
            <p class="text-xs text-slate-400 italic">Belum ada data ruangan.</p>
        <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            <?php foreach ($occ['rooms'] as $room):
                $used = (int)$room['used'];
                $cap  = (int)$room['capacity'];
                $pct  = (int)$room['percent'];
                if ($pct >= 100)      { $bar = 'bg-pepsi-red';   $chip = 'bg-red-50 text-pepsi-red'; }
                elseif ($pct >= 70)   { $bar = 'bg-amber-500';   $chip = 'bg-amber-50 text-amber-700'; }
                else                  { $bar = 'bg-emerald-500'; $chip = 'bg-emerald-50 text-emerald-700'; }
                $barW = min($pct, 100);
                $free = $cap - $used;
            ?>
            <div class="p-4 rounded-2xl border border-slate-100 bg-slate-50/60 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-8 h-8 rounded-xl bg-pepsi-navy text-white flex items-center justify-center text-xs font-black"><?= sanitize(substr($room['name'], 0, 1)) ?></span>
                        <span class="font-heading font-bold text-sm text-slate-900">Ruang <?= sanitize($room['name']) ?></span>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-black <?= $chip ?>"><?= $pct ?>%</span>
                </div>
                <div>
                    <div class="flex items-end justify-between mb-1">
                        <span class="text-[11px] font-bold text-slate-500">Terisi</span>
                        <span class="text-sm font-black text-slate-800"><?= $used ?> <span class="text-slate-400 font-bold text-xs">/ <?= $cap ?> siswa</span></span>
                    </div>
                    <div class="w-full h-2.5 bg-slate-200 rounded-full overflow-hidden">
                        <div class="<?= $bar ?> h-full rounded-full transition-all" style="width: <?= $barW ?>%"></div>
                    </div>
                </div>
                <p class="text-[11px] text-slate-400"><?= $free > 0 ? $free . ' kursi tersisa' : 'Kapasitas penuh' ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if (($occ['unassigned'] ?? 0) > 0): ?>
            <p class="text-[11px] text-amber-700 bg-amber-50 border border-amber-100 rounded-xl p-2.5">
                <?= (int)$occ['unassigned'] ?> siswa belum punya ruangan (program/jam belum terjadwal). Atur di menu Schedule Builder.
            </p>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Quick Schedule Matrix Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden space-y-4 p-6">
        <div class="flex items-center justify-between">
            <h3 class="font-heading font-bold text-lg text-slate-900">Pratinjau Matriks Jadwal Kelas</h3>
            <a href="<?= url('admin/schedules') ?>" class="text-xs font-bold text-pepsi-blue hover:underline">Kelola Builder &rarr;</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-medium">
                <thead class="bg-slate-50 text-slate-500 uppercase font-bold text-[10px]">
                    <tr>
                        <th class="px-4 py-3">Jam Sesi</th>
                        <th class="px-4 py-3">Program</th>
                        <th class="px-4 py-3">Coach Utama</th>
                        <th class="px-4 py-3">Ruangan</th>
                        <th class="px-4 py-3 text-right">Biaya Siswa</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($recentSchedules as $s): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-bold text-pepsi-blue"><?= $s['time_slot'] ?> WIB</td>
                        <td class="px-4 py-3 font-bold text-slate-900"><?= sanitize($s['program_title']) ?></td>
                        <td class="px-4 py-3 text-slate-600">Coach <?= sanitize($s['tutor_name']) ?></td>
                        <td class="px-4 py-3"><span class="px-2 py-0.5 bg-slate-100 rounded-md font-semibold">Ruang <?= sanitize($s['room_name']) ?></span></td>
                        <td class="px-4 py-3 text-right font-bold text-slate-900"><?= formatRupiah($s['price']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Payment Modal -->
    <div x-show="payModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="payModal = false"></div>
        <div class="min-h-screen px-4 flex items-center justify-center p-4">
            <div class="relative bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-base font-black text-slate-800 flex items-center gap-2">
                            <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">💵</span>
                            Catat Pembayaran
                        </h3>
                        <p class="text-xs text-slate-500 mt-1" x-text="(payData.full_name || '') + '  •  ' + (payData.student_no || '')"></p>
                    </div>
                    <button @click="payModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
                </div>

                <form :action="'<?= url('admin/students/pay/') ?>' + payData.id" method="POST" class="space-y-3.5 text-xs">
                    <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-slate-400 font-bold uppercase block">Sisa Tagihan</span>
                            <span class="font-black text-pepsi-red text-lg"
                                  x-text="'Rp ' + Math.max(0, (parseFloat(payData.total_fee) || 0) - (parseFloat(payData.paid_amount) || 0)).toLocaleString('id-ID')"></span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] text-slate-400 font-bold uppercase block">Sudah Dibayar</span>
                            <span class="font-extrabold text-emerald-600"
                                  x-text="'Rp ' + (parseFloat(payData.paid_amount) || 0).toLocaleString('id-ID')"></span>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1.5">Metode Pembayaran *</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center justify-center gap-2 p-2.5 rounded-xl border-2 cursor-pointer transition text-center"
                                   :class="payMethod === 'tunai' ? 'border-emerald-500 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-slate-50 text-slate-600'">
                                <input type="radio" name="payment_method" value="tunai" x-model="payMethod" class="sr-only">
                                <span class="text-base leading-none">💵</span>
                                <span class="text-[11px] font-black">Tunai &amp; Cash</span>
                            </label>
                            <label class="flex items-center justify-center gap-2 p-2.5 rounded-xl border-2 cursor-pointer transition text-center"
                                   :class="payMethod === 'transfer' ? 'border-blue-500 bg-blue-50 text-blue-800' : 'border-slate-200 bg-slate-50 text-slate-600'">
                                <input type="radio" name="payment_method" value="transfer" x-model="payMethod" class="sr-only">
                                <span class="text-base leading-none">🏦</span>
                                <span class="text-[11px] font-black">Transfer Bank</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Jumlah Dibayar Sekarang (Rp) *</label>
                        <input type="number" name="pay_amount" min="1" x-model="payAmount" required
                               class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 focus:outline-none font-black text-base">
                    </div>

                    <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200/80 flex items-center justify-between">
                        <label class="flex items-center gap-2.5 cursor-pointer text-xs font-bold text-emerald-900 select-none">
                            <input type="checkbox" name="print_receipt" value="1" checked class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                            <span>Cetak kwitansi setelah pembayaran</span>
                        </label>
                        <span class="text-[10px] text-emerald-700 font-extrabold bg-emerald-100 px-2 py-0.5 rounded-full uppercase">e-Nota</span>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                        <button type="button" @click="payModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-600 font-bold">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold transition shadow-sm flex items-center gap-1.5">
                            <span>Simpan Pembayaran</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
