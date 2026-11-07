<?php
// Admin Schedule Builder with Conflict Prevention View

$roomCounts = [];
$slotCounts = [];
foreach ($schedules as $s) {
    $rid = $s['room_id'];
    $slot = $s['time_slot'];
    $roomCounts[$rid] = ($roomCounts[$rid] ?? 0) + 1;
    $slotCounts[$slot] = ($slotCounts[$slot] ?? 0) + 1;
}

$slotOptions = array_values(array_unique(array_merge($timeSlots, array_column($schedules, 'time_slot'))));
sort($slotOptions);

$filterRows = array_map(fn($s) => [
    'room' => (string)$s['room_id'],
    'slot' => (string)$s['time_slot']
], array_values($schedules));
?>
<div x-data="scheduleManager()" class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-heading font-black text-2xl sm:text-3xl text-pepsi-navy">Schedule Builder</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Atur matriks slot jam kelas, ruangan AC, dan tutor pengajar dengan <b>Sistem Proteksi Anti Bentrok Otomatis</b>.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <!-- Period filter -->
            <form method="GET" action="<?= url('admin/schedules') ?>" class="inline-flex">
                <select name="period_id" onchange="this.form.submit()" class="bg-white border border-slate-200 text-xs font-bold rounded-2xl px-4 py-2.5 shadow-sm text-slate-800">
                    <?php foreach ($periods as $per): ?>
                    <option value="<?= $per['id'] ?>" <?= $selectedPeriodId == $per['id'] ? 'selected' : '' ?>>
                        <?= sanitize($per['name']) ?> <?= $per['is_active'] ? '(Aktif)' : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <form method="GET" action="<?= url('admin/schedules/print-daily') ?>" class="inline-flex items-center gap-1.5 bg-white border border-slate-200 rounded-2xl pl-3.5 pr-1.5 py-1.5 shadow-sm">
                <input type="hidden" name="period_id" value="<?= (int)$selectedPeriodId ?>">
                <input type="date" name="date" value="<?= date('Y-m-d') ?>" required class="text-xs font-bold text-slate-800 bg-transparent border-0 focus:outline-none py-1">
                <button type="submit" title="Unduh jadwal harian sesuai tanggal dalam format PDF" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-3.5 py-2 rounded-xl flex items-center gap-1.5 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>Cetak PDF Harian</span>
                </button>
            </form>

            <button @click="openAddModal()" class="bg-pepsi-blue hover:bg-blue-900 text-white font-bold text-xs sm:text-sm px-5 py-2.5 rounded-2xl shadow-md flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Tambah Jadwal Baru</span>
            </button>
        </div>
    </div>

    <!-- Conflict Prevention Info Banner -->
    <div class="p-4 bg-blue-50 border border-blue-200 rounded-3xl flex items-start gap-3 text-xs text-blue-900">
        <div class="w-8 h-8 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold text-sm flex-shrink-0">
            🛡️
        </div>
        <div>
            <p class="font-bold text-sm text-pepsi-blue">Fitur Proteksi Jadwal Anti-Bentrok Aktif</p>
            <p class="text-slate-600 mt-0.5">
                Sistem secara otomatis menolak dan memperingatkan bila <b>Ruangan yang sama</b> atau <b>Coach yang sama</b> didaftarkan pada jam sesi yang bersamaan.
            </p>
        </div>
    </div>

    <!-- FILTER TABS: RUANGAN & SESI -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-4 sm:p-5 space-y-3.5">
        <div class="flex flex-wrap items-center gap-2">
            <span class="w-24 sm:w-28 text-[11px] font-black uppercase tracking-wider text-slate-400">Ruangan</span>
            <button type="button" @click="filterRoom = 'all'" :class="filterRoom === 'all' ? 'bg-pepsi-navy text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-3.5 py-1.5 rounded-full text-xs font-bold transition">
                Semua <span class="opacity-70">(<?= count($schedules) ?>)</span>
            </button>
            <?php foreach ($rooms as $r): ?>
            <button type="button" @click="filterRoom = '<?= (string)(int)$r['id'] ?>'" :class="filterRoom === '<?= (string)(int)$r['id'] ?>' ? 'bg-pepsi-blue text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-3.5 py-1.5 rounded-full text-xs font-bold transition">
                Ruang <?= sanitize($r['name']) ?> <span class="opacity-70">(<?= $roomCounts[$r['id']] ?? 0 ?>)</span>
            </button>
            <?php endforeach; ?>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="w-24 sm:w-28 text-[11px] font-black uppercase tracking-wider text-slate-400">Sesi</span>
            <button type="button" @click="filterSlot = 'all'" :class="filterSlot === 'all' ? 'bg-pepsi-navy text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-3.5 py-1.5 rounded-full text-xs font-bold transition">
                Semua <span class="opacity-70">(<?= count($schedules) ?>)</span>
            </button>
            <?php foreach ($slotOptions as $slot): ?>
            <button type="button" @click="filterSlot = <?= htmlspecialchars(json_encode((string)$slot), ENT_QUOTES) ?>" :class="filterSlot === <?= htmlspecialchars(json_encode((string)$slot), ENT_QUOTES) ?> ? 'bg-pepsi-blue text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-3.5 py-1.5 rounded-full text-xs font-bold transition">
                <?= sanitize($slot) ?> WIB <span class="opacity-70">(<?= $slotCounts[$slot] ?? 0 ?>)</span>
            </button>
            <?php endforeach; ?>
        </div>

        <div x-show="filterRoom !== 'all' || filterSlot !== 'all'" x-cloak class="flex items-center justify-between gap-3 pt-2 border-t border-slate-100">
            <p class="text-[11px] text-slate-500 font-semibold">
                Filter aktif — menampilkan <b class="text-pepsi-blue" x-text="visibleCount()"></b> dari <b><?= count($schedules) ?></b> sesi.
            </p>
            <button type="button" @click="filterRoom = 'all'; filterSlot = 'all'" class="px-3 py-1.5 rounded-full bg-red-50 hover:bg-red-100 text-pepsi-red text-[11px] font-bold transition">
                Reset Filter
            </button>
        </div>
    </div>

    <!-- Schedules Grid / Matrix -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-heading font-bold text-base text-slate-900">
                Daftar Matriks Jadwal (<span x-text="visibleCount()"><?= count($schedules) ?></span> Sesi Terdaftar)
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-medium">
                <thead class="bg-pepsi-navy text-white text-[11px] uppercase tracking-wider font-bold">
                    <tr>
                        <th class="px-5 py-4">Jam Sesi</th>
                        <th class="px-5 py-4">Program & Level</th>
                        <th class="px-5 py-4">Ruangan Kelas</th>
                        <th class="px-5 py-4">Tutor / Coach Utama</th>
                        <th class="px-5 py-4">Honor Sesi</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($schedules)): ?>
                    <tr>
                        <td colspan="6" class="px-5 py-8 text-center text-slate-400 italic">
                            Belum ada jadwal untuk periode ini. Klik "Tambah Jadwal Baru" untuk memulai.
                        </td>
                    </tr>
                    <?php else: ?>
                    <tr x-show="visibleCount() === 0" x-cloak>
                        <td colspan="6" class="px-5 py-8 text-center text-slate-400 italic">
                            Tidak ada sesi yang cocok dengan filter ruangan / sesi yang dipilih.
                        </td>
                    </tr>
                    <?php foreach ($schedules as $s): ?>
                    <tr class="hover:bg-slate-50 transition" x-show="matches('<?= (string)(int)$s['room_id'] ?>', <?= htmlspecialchars(json_encode((string)$s['time_slot']), ENT_QUOTES) ?>)" x-cloak>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="font-bold text-pepsi-blue text-sm block"><?= $s['time_slot'] ?> WIB</span>
                            <span class="text-[10px] text-slate-400"><?= sanitize($s['day_name']) ?></span>
                        </td>
                        <td class="px-5 py-4">
                            <span class="font-bold text-slate-900 block"><?= sanitize($s['program_title']) ?></span>
                            <span class="text-[11px] text-slate-500 font-semibold"><?= sanitize($s['level_label']) ?> &bull; <?= sanitize($s['program_code']) ?></span>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-800 font-bold border border-slate-200">
                                Ruang <?= sanitize($s['room_name']) ?>
                            </span>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-full bg-pepsi-blue text-white flex items-center justify-center font-bold text-xs">
                                    <?= substr($s['tutor_name'], 0, 1) ?>
                                </div>
                                <span class="font-bold text-slate-900">Coach <?= sanitize($s['tutor_name']) ?></span>
                            </div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap font-semibold text-slate-700">
                            <?= formatRupiah($s['fee_rate'] ?? $s['fee_per_session']) ?> / sesi
                        </td>
                        <td class="px-5 py-4 text-right whitespace-nowrap space-x-1">
                            <button @click="openEditModal(<?= htmlspecialchars(json_encode([
                                'id' => $s['id'],
                                'period_id' => $s['period_id'],
                                'day_name' => $s['day_name'],
                                'time_slot' => $s['time_slot'],
                                'program_id' => $s['program_id'],
                                'room_id' => $s['room_id'],
                                'tutor_id' => $s['tutor_id']
                            ])) ?>)" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition">
                                Edit
                            </button>
                            <form action="<?= url('admin/schedules/delete/' . $s['id']) ?>" method="POST" class="inline" onsubmit="return confirm('Hapus jadwal ini?')">
                                <button type="submit" class="px-3 py-1.5 rounded-xl bg-red-50 hover:bg-red-100 text-pepsi-red font-bold transition">
                                    Hapus
                                </button>
            </form>

                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL TAMBAH JADWAL (WITH REAL-TIME CONFLICT CHECKER) -->
    <div x-show="addModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="addModal = false" class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-heading font-black text-xl text-pepsi-navy">Tambah Jadwal Kelas</h3>
                <button @click="addModal = false" class="text-slate-400 hover:text-slate-700">&times;</button>
            </div>

            <!-- Dynamic conflict warning box -->
            <div x-show="conflictMessage" x-cloak class="p-3.5 rounded-2xl bg-red-50 border border-red-200 text-pepsi-red text-xs font-semibold flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <span x-text="conflictMessage"></span>
            </div>

            <form action="<?= url('admin/schedules/store') ?>" method="POST" class="space-y-4 text-xs">
                <input type="hidden" name="period_id" value="<?= $selectedPeriodId ?>">

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Jam Sesi Kelas *</label>
                    <select name="time_slot" x-model="formTime" @change="verifyConflict()" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold text-pepsi-blue">
                        <option value="">-- Pilih Jam Sesi --</option>
                        <?php foreach ($timeSlots as $slot): ?>
                        <option value="<?= $slot ?>"><?= $slot ?> WIB</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Program Kursus *</label>
                    <select name="program_id" x-model="formProgram" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-semibold">
                        <option value="">-- Pilih Program --</option>
                        <?php foreach ($programs as $prog): ?>
                        <option value="<?= $prog['id'] ?>"><?= sanitize($prog['title']) ?> (<?= sanitize($prog['code']) ?>) - <?= formatRupiah($prog['price']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Ruangan Kelas *</label>
                        <select name="room_id" x-model="formRoom" @change="verifyConflict()" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-semibold">
                            <option value="">-- Pilih Ruang --</option>
                            <?php foreach ($rooms as $r): ?>
                            <option value="<?= $r['id'] ?>">Ruang <?= sanitize($r['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Coach Utama *</label>
                        <select name="tutor_id" x-model="formTutor" @change="verifyConflict()" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-semibold">
                            <option value="">-- Pilih Tutor --</option>
                            <?php foreach ($tutors as $t): ?>
                            <option value="<?= $t['id'] ?>">Coach <?= sanitize($t['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Hari Belajar</label>
                    <input type="text" name="day_name" value="Senin - Jumat" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-medium">
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="addModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
                    <button type="submit" :disabled="hasConflict" :class="hasConflict ? 'bg-slate-300 cursor-not-allowed' : 'bg-pepsi-blue hover:bg-blue-900 text-white'" class="px-5 py-2 rounded-xl font-bold transition">
                        Simpan Jadwal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT JADWAL -->
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="editModal = false" class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-heading font-black text-xl text-pepsi-navy">Edit Jadwal Kelas</h3>
                <button @click="editModal = false" class="text-slate-400 hover:text-slate-700">&times;</button>
            </div>

            <form :action="'<?= url('admin/schedules/update/') ?>' + editItem.id" method="POST" class="space-y-4 text-xs">
                <input type="hidden" name="period_id" :value="editItem.period_id">

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Jam Sesi Kelas *</label>
                    <select name="time_slot" x-model="editItem.time_slot" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold text-pepsi-blue">
                        <?php foreach ($timeSlots as $slot): ?>
                        <option value="<?= $slot ?>"><?= $slot ?> WIB</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Program Kursus *</label>
                    <select name="program_id" x-model="editItem.program_id" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-semibold">
                        <?php foreach ($programs as $prog): ?>
                        <option value="<?= $prog['id'] ?>"><?= sanitize($prog['title']) ?> (<?= sanitize($prog['code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Ruangan Kelas *</label>
                        <select name="room_id" x-model="editItem.room_id" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-semibold">
                            <?php foreach ($rooms as $r): ?>
                            <option value="<?= $r['id'] ?>">Ruang <?= sanitize($r['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Coach Utama *</label>
                        <select name="tutor_id" x-model="editItem.tutor_id" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-semibold">
                            <?php foreach ($tutors as $t): ?>
                            <option value="<?= $t['id'] ?>">Coach <?= sanitize($t['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Hari Belajar</label>
                    <input type="text" name="day_name" x-model="editItem.day_name" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-medium">
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="editModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-pepsi-blue text-white font-bold">Perbarui Jadwal</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function scheduleManager() {
    return {
        addModal: false,
        editModal: false,
        editItem: {},
        formTime: '',
        formProgram: '',
        formRoom: '',
        formTutor: '',
        hasConflict: false,
        conflictMessage: '',
        filterRoom: 'all',
        filterSlot: 'all',
        rows: <?= json_encode($filterRows) ?>,

        matches(roomId, slot) {
            return (this.filterRoom === 'all' || this.filterRoom === String(roomId))
                && (this.filterSlot === 'all' || this.filterSlot === String(slot));
        },

        visibleCount() {
            if (this.filterRoom === 'all' && this.filterSlot === 'all') {
                return this.rows.length;
            }
            return this.rows.filter(r => this.matches(r.room, r.slot)).length;
        },

        openAddModal() {
            this.formTime = '';
            this.formProgram = '';
            this.formRoom = '';
            this.formTutor = '';
            this.hasConflict = false;
            this.conflictMessage = '';
            this.addModal = true;
        },

        openEditModal(item) {
            this.editItem = item;
            this.editModal = true;
        },

        async verifyConflict() {
            if (!this.formTime || !this.formRoom || !this.formTutor) {
                this.hasConflict = false;
                this.conflictMessage = '';
                return;
            }

            const periodId = '<?= $selectedPeriodId ?>';
            const url = `<?= url('admin/schedules/check-conflict') ?>?period_id=${periodId}&time_slot=${encodeURIComponent(this.formTime)}&room_id=${this.formRoom}&tutor_id=${this.formTutor}`;
            
            try {
                const res = await fetch(url);
                const data = await res.json();
                this.hasConflict = data.has_conflict;
                this.conflictMessage = data.has_conflict ? data.message : '';
            } catch (e) {
                console.error(e);
            }
        }
    };
}
</script>
