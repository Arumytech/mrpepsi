<?php
// Admin Attendance & Tutor Substitution View (tampilan per Tutor)
?>
<div class="space-y-6">

    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <h1 class="font-heading font-black text-2xl sm:text-3xl text-pepsi-navy">Presensi & Tutor Pengganti</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Catat kehadiran <b>per Tutor</b> untuk tiap sesi mengajar beserta nominal gaji sesi.
                Tanggal hanya sebagai <b>tanggal input</b>, bukan pencatatan harian.
            </p>
        </div>

        <!-- Periode & Tanggal Input -->
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" onclick="markAllPresent()" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-2xl transition shadow-sm">
                Tandai Semua Hadir
            </button>
            <form method="GET" action="<?= url('admin/attendance') ?>" class="flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-2xl border border-slate-200 shadow-sm text-xs">
                    <span class="font-bold text-slate-500">Periode:</span>
                    <select name="period_id" onchange="this.form.submit()" class="font-bold text-slate-800 bg-transparent focus:outline-none">
                        <?php foreach ($periods as $p): ?>
                        <option value="<?= (int)$p['id'] ?>" <?= ((int)$p['id'] === (int)$selectedPeriodId) ? 'selected' : '' ?>>
                            <?= sanitize($p['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-2xl border border-slate-200 shadow-sm text-xs">
                    <span class="font-bold text-slate-500">Tanggal Input:</span>
                    <input type="date" name="date" value="<?= sanitize($inputDate) ?>" onchange="this.form.submit()" class="font-bold text-slate-800 bg-transparent focus:outline-none">
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($groups)): ?>
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-10 text-center text-slate-400 italic">
        Belum ada jadwal sesi pada periode ini.
    </div>
    <?php else: ?>
    <?php foreach ($groups as $g): ?>
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="font-heading font-black text-lg text-pepsi-navy">Coach <?= sanitize($g['tutor_name']) ?></h3>
                <p class="text-[11px] text-slate-500 font-semibold mt-0.5">
                    <?= (int)$g['total'] ?> sesi &middot; Tarif <?= formatRupiah($g['tutor_fee']) ?>/sesi
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-[11px] font-bold">
                <span class="px-3 py-1 rounded-full bg-green-100 text-green-700">Hadir: <?= (int)$g['hadir'] ?></span>
                <span class="px-3 py-1 rounded-full bg-amber-100 text-amber-800">Substitusi: <?= (int)$g['substitusi'] ?></span>
                <span class="px-3 py-1 rounded-full bg-red-100 text-red-700">Izin/Nihil: <?= (int)$g['izin'] ?></span>
                <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-500">Belum: <?= (int)$g['belum'] ?></span>
                <span class="px-3 py-1 rounded-full bg-pepsi-blue text-white">Gaji: <?= formatRupiah($g['gaji']) ?></span>
            </div>
        </div>

        <form action="<?= url('admin/attendance/save-bulk') ?>" method="POST">
            <input type="hidden" name="period_id" value="<?= (int)$selectedPeriodId ?>">
            <input type="hidden" name="date" value="<?= sanitize($inputDate) ?>">

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs font-medium">
                    <thead class="bg-pepsi-navy text-white text-[11px] uppercase tracking-wider font-bold">
                        <tr>
                            <th class="px-4 py-3">No</th>
                            <th class="px-4 py-3">Jam Sesi</th>
                            <th class="px-4 py-3">Program &amp; Ruangan</th>
                            <th class="px-4 py-3">Status Kehadiran</th>
                            <th class="px-4 py-3">Tutor Pengganti</th>
                            <th class="px-4 py-3">Gaji Sesi (Rp)</th>
                            <th class="px-4 py-3">Catatan</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    <?php foreach ($g['sessions'] as $i => $s):
                        $sid = (int)$s['schedule_id'];
                        $st = $s['status'] ?? 'present';
                        $feeVal = (float)($s['session_fee'] ?? $s['default_fee']);
                    ?>
                        <tr x-data="{ status: '<?= $st ?>' }" class="hover:bg-slate-50 transition align-top">
                            <td class="px-4 py-3 font-bold text-slate-400"><?= $i + 1 ?></td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="font-bold text-pepsi-blue text-sm block"><?= sanitize($s['time_slot']) ?> WIB</span>
                                <span class="text-[10px] text-slate-400"><?= sanitize($s['day_name']) ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-bold text-slate-900 block"><?= sanitize($s['program_title']) ?></span>
                                <span class="text-[11px] text-slate-500 font-semibold">Ruang <?= sanitize($s['room_name']) ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <select name="status[<?= $sid ?>]" x-model="status" class="rounded-xl border border-slate-200 p-2 bg-slate-50 font-bold text-slate-800 w-36">
                                    <option value="present">Hadir</option>
                                    <option value="substituted">Substitusi</option>
                                    <option value="absent">Izin / Nihil</option>
                                </select>
                            </td>
                            <td class="px-4 py-3">
                                <div x-show="status === 'substituted'" x-cloak>
                                    <select name="substituted_tutor_id[<?= $sid ?>]" class="rounded-xl border border-amber-300 p-2 bg-amber-50 font-bold text-slate-800 w-44">
                                        <option value="">-- Pilih Pengganti --</option>
                                        <?php foreach ($tutors as $t): ?>
                                        <option value="<?= (int)$t['id'] ?>"
                                            <?= ((int)$t['id'] === (int)$s['substituted_tutor_id']) ? 'selected' : '' ?>
                                            <?= ((int)$t['id'] === (int)$s['main_tutor_id']) ? 'disabled' : '' ?>>
                                            Coach <?= sanitize($t['name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <span x-show="status !== 'substituted'" class="text-[11px] text-slate-400 italic">-</span>
                            </td>
                            <td class="px-4 py-3">
                                <input type="number" min="0" step="500" name="session_fee[<?= $sid ?>]" value="<?= $feeVal ?>" class="rounded-xl border border-slate-200 p-2 bg-slate-50 font-bold text-slate-800 w-32">
                                <span class="block text-[10px] text-slate-400 mt-1">Default: <?= formatRupiah($s['default_fee']) ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <input type="text" name="notes[<?= $sid ?>]" value="<?= sanitize($s['notes'] ?? '') ?>" placeholder="Opsional" class="rounded-xl border border-slate-200 p-2 bg-slate-50 w-40">
                            </td>
                            <td class="px-4 py-3 text-right">
                                <?php if (!empty($s['attendance_id'])): ?>
                                <button type="submit" form="att-del-<?= $sid ?>" onclick="return confirm('Hapus presensi sesi ini?')" class="px-3 py-2 rounded-xl bg-red-50 hover:bg-red-100 text-red-600 font-bold transition">
                                    Hapus
                                </button>
                                <?php else: ?>
                                <span class="text-[11px] text-slate-300 italic">Belum ada</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100 flex items-center justify-end">
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-pepsi-blue hover:bg-blue-900 text-white font-bold text-xs transition shadow-sm">
                    Simpan Presensi Tutor
                </button>
            </div>
        </form>

        <?php foreach ($g['sessions'] as $s):
            $sid = (int)$s['schedule_id'];
            if (empty($s['attendance_id'])) continue;
        ?>
        <form id="att-del-<?= $sid ?>" action="<?= url('admin/attendance/delete') ?>" method="POST" class="hidden">
            <input type="hidden" name="schedule_id" value="<?= $sid ?>">
            <input type="hidden" name="date" value="<?= sanitize($inputDate) ?>">
            <input type="hidden" name="period_id" value="<?= (int)$selectedPeriodId ?>">
        </form>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

</div>

<script>
function markAllPresent() {
    var selects = document.querySelectorAll('select[name^="status["]');
    if (selects.length === 0) {
        return;
    }
    selects.forEach(function (sel) {
        if (sel.value !== 'present') {
            sel.value = 'present';
            sel.dispatchEvent(new Event('input', { bubbles: true }));
            sel.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });
}
</script>
