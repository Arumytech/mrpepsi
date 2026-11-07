<?php
// Admin Student Registrations & Approvals View
$kidsProgramIds = array_values(array_map('strval', array_column(array_filter($programs ?? [], function($p) {
    return ((int)($p['category_id'] ?? 0) === 3)
        || stripos($p['title'] ?? '', 'KID') !== false
        || stripos($p['code'] ?? '', 'KID') !== false;
}), 'id')));
?>
<script>
function registrationsManager() {
    return {
        addModal: false, 
        approveModal: false, 
        rejectModal: false, 
        editModal: false, 
        selectedReg: {}, 
        editReg: {}, 
        regPrograms: [],
        kidsProgramIds: [<?= implode(',', array_map('intval', $kidsProgramIds)) ?>],
        get isKidsSelected() {
            var self = this;
            if (!Array.isArray(self.regPrograms)) return false;
            return self.regPrograms.some(function(id) {
                return self.kidsProgramIds.indexOf(parseInt(id, 10)) !== -1;
            });
        },
        get isEditKidsSelected() {
            var self = this;
            if (!self.editReg || !Array.isArray(self.editReg.program_ids)) return false;
            return self.editReg.program_ids.some(function(id) {
                return self.kidsProgramIds.indexOf(parseInt(id, 10)) !== -1;
            });
        },
        repeatWarning: { show: false, message: '', count: 0 },
        studentPhone: '',
        studentName: '',
        checkRepeat() {
            if ((!this.studentPhone || this.studentPhone.length < 7) && (!this.studentName || this.studentName.length < 3)) {
                this.repeatWarning = { show: false, message: '', count: 0 };
                return;
            }
            if (!this.regPrograms || this.regPrograms.length === 0) {
                this.repeatWarning = { show: false, message: '', count: 0 };
                return;
            }
            let url = '<?= url('api/check-repeat-program') ?>?phone=' + encodeURIComponent(this.studentPhone) + '&name=' + encodeURIComponent(this.studentName) + '&programs=' + this.regPrograms.join(',');
            fetch(url)
                .then(r => r.json())
                .then(res => {
                    if (res && res.has_repeat) {
                        this.repeatWarning = { show: true, message: res.message.replace(/\n/g, '<br>'), count: res.count };
                    } else {
                        this.repeatWarning = { show: false, message: '', count: 0 };
                    }
                })
                .catch(() => {});
        }
    };
}
</script>

<div x-data="registrationsManager()" class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-heading font-black text-2xl sm:text-3xl text-pepsi-navy">Pendaftaran Siswa</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Kelola data pendaftaran masuk dari <b>Web Publik (Calon Siswa)</b> dan <b>Pendaftaran Langsung oleh Admin</b>.
            </p>
        </div>
        <button @click="addModal = true" class="bg-pepsi-blue hover:bg-blue-900 text-white font-bold text-xs sm:text-sm px-5 py-2.5 rounded-2xl shadow-md flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            <span>Daftarkan Siswa Baru</span>
        </button>
    </div>

    <!-- Metric Summary Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Pendaftar</span>
            <span class="font-heading font-black text-2xl sm:text-3xl text-slate-900 mt-1 block"><?= $stats['total'] ?></span>
            <span class="text-[11px] text-slate-500">Semua Calon Siswa</span>
        </div>

        <div class="bg-white p-5 rounded-3xl border border-amber-200 bg-amber-50/20 shadow-sm">
            <span class="text-xs font-bold text-amber-700 uppercase tracking-wider block flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                <span>Menunggu Approval</span>
            </span>
            <span class="font-heading font-black text-2xl sm:text-3xl text-amber-700 mt-1 block"><?= $stats['pending'] ?></span>
            <span class="text-[11px] text-amber-600 font-semibold">Perlu dicek admin</span>
        </div>

        <div class="bg-white p-5 rounded-3xl border border-green-200 bg-green-50/20 shadow-sm">
            <span class="text-xs font-bold text-green-700 uppercase tracking-wider block">Disetujui (Approved)</span>
            <span class="font-heading font-black text-2xl sm:text-3xl text-green-700 mt-1 block"><?= $stats['approved'] ?></span>
            <span class="text-[11px] text-green-600 font-semibold">Resmi diterima kelas</span>
        </div>

        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Ditolak / Batal</span>
            <span class="font-heading font-black text-2xl sm:text-3xl text-slate-400 mt-1 block"><?= $stats['rejected'] ?></span>
            <span class="text-[11px] text-slate-400">Pendaftaran dibatalkan</span>
        </div>
    </div>

    <!-- Filter Status Tabs & Search -->
    <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
        <!-- Status Tabs -->
        <div class="flex items-center gap-1.5 overflow-x-auto w-full md:w-auto pb-1 text-xs font-bold">
            <a href="<?= url('admin/registrations?status=all' . ($search ? '&search=' . urlencode($search) : '')) ?>" 
               class="px-3.5 py-2 rounded-xl transition whitespace-nowrap <?= $currentStatus === 'all' ? 'bg-pepsi-navy text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                Semua (<?= $stats['total'] ?>)
            </a>
            <a href="<?= url('admin/registrations?status=pending' . ($search ? '&search=' . urlencode($search) : '')) ?>" 
               class="px-3.5 py-2 rounded-xl transition whitespace-nowrap <?= $currentStatus === 'pending' ? 'bg-amber-500 text-white shadow-sm' : 'bg-amber-50 text-amber-800 hover:bg-amber-100' ?>">
                Menunggu Approval (<?= $stats['pending'] ?>)
            </a>
            <a href="<?= url('admin/registrations?status=approved' . ($search ? '&search=' . urlencode($search) : '')) ?>" 
               class="px-3.5 py-2 rounded-xl transition whitespace-nowrap <?= $currentStatus === 'approved' ? 'bg-green-600 text-white shadow-sm' : 'bg-green-50 text-green-800 hover:bg-green-100' ?>">
                Disetujui (<?= $stats['approved'] ?>)
            </a>
            <a href="<?= url('admin/registrations?status=rejected' . ($search ? '&search=' . urlencode($search) : '')) ?>" 
               class="px-3.5 py-2 rounded-xl transition whitespace-nowrap <?= $currentStatus === 'rejected' ? 'bg-red-600 text-white shadow-sm' : 'bg-red-50 text-red-800 hover:bg-red-100' ?>">
                Ditolak (<?= $stats['rejected'] ?>)
            </a>
        </div>

        <!-- Search Form -->
        <form method="GET" action="<?= url('admin/registrations') ?>" class="flex items-center gap-2 w-full md:w-auto">
            <input type="hidden" name="status" value="<?= $currentStatus ?>">
            <div class="relative flex-1 md:w-64">
                <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Cari nama, no reg, no WA..." class="w-full text-xs font-medium pl-8 pr-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-pepsi-blue">
                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <button type="submit" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl transition">
                Cari
            </button>
        </form>
    </div>

    <!-- Registrations Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-medium">
                <thead class="bg-pepsi-navy text-white text-[11px] uppercase tracking-wider font-bold">
                    <tr>
                        <th class="px-5 py-4">No. Registrasi & Tanggal</th>
                        <th class="px-5 py-4">Data Siswa & WhatsApp</th>
                        <th class="px-5 py-4">Periode & Jam Sesi</th>
                        <th class="px-5 py-4">Program Dipilih</th>
                        <th class="px-5 py-4">Total Biaya</th>
                        <th class="px-5 py-4">Status & Jalur</th>
                        <th class="px-5 py-4 text-right">Aksi & Approval</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($registrations)): ?>
                    <tr>
                        <td colspan="7" class="px-5 py-10 text-center text-slate-400 italic">
                            Tidak ada data pendaftaran siswa yang sesuai dengan filter ini.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($registrations as $r): 
                        $progs = json_decode($r['programs_json'], true) ?: [];
                        $cleanPhone = preg_replace('/[^0-9]/', '', $r['phone']);
                        if (str_starts_with($cleanPhone, '0')) {
                            $cleanPhone = '62' . substr($cleanPhone, 1);
                        }
                        $waApprovedMsg = "Halo kak *{$r['student_name']}*,\n\nPendaftaran Anda di *MR.PEPSI UPGRADE Kampung Inggris Pare* (No. {$r['registration_no']}) telah *DISETUJUI (APPROVED)*.\n\nPeriode: {$r['period_name']}\nJam Kelas: {$r['time_slot']}\nTotal Biaya: " . formatRupiah($r['total_fee']) . "\n\nSilakan selesaikan pembayaran dan konfirmasi kembali bukti pembayaran. Terima kasih!";
                        $waChatUrl = "https://wa.me/{$cleanPhone}?text=" . urlencode($waApprovedMsg);
                    ?>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="font-bold text-pepsi-blue font-mono text-xs block"><?= sanitize($r['registration_no']) ?></span>
                            <span class="text-[10px] text-slate-400"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?> WIB</span>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="font-bold text-slate-900 text-sm block"><?= sanitize($r['student_name']) ?></span>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <a href="https://wa.me/<?= $cleanPhone ?>" target="_blank" class="text-green-600 hover:text-green-700 font-bold text-[11px] flex items-center gap-1" title="Chat WhatsApp">
                                    <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.007c.106.005.249-.04.39.299.144.347.491 1.199.534 1.286.043.087.072.188.014.303-.058.116-.087.188-.173.289l-.26.302c-.087.087-.179.182-.077.357.101.174.45 1.084 1.144 1.702.89.794 1.64 1.04 1.871 1.155.231.116.368.101.505-.058.137-.159.587-.683.743-.918.156-.235.312-.196.526-.117.214.079 1.36.642 1.594.757.234.115.39.173.448.271.058.098.058.568-.086.973z"/></svg>
                                    <span><?= sanitize($r['phone']) ?></span>
                                </a>
                                <span class="text-slate-300">&bull;</span>
                                <span class="text-[11px] text-slate-500"><?= sanitize($r['city']) ?></span>
                            </div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="font-bold text-slate-800 block"><?= sanitize($r['period_name']) ?></span>
                            <span class="text-[11px] text-pepsi-blue font-semibold"><?= sanitize($r['time_slot']) ?></span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="space-y-1">
                                <?php foreach ($progs as $p): ?>
                                <span class="inline-block px-2.5 py-0.5 rounded-lg bg-blue-50 text-pepsi-blue font-bold text-[10px] border border-blue-100">
                                    <?= sanitize($p['title']) ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap font-heading font-black text-sm text-pepsi-navy">
                            <?= formatRupiah($r['total_fee']) ?>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <!-- Status Badge -->
                            <div class="mb-1">
                                <?php if ($r['status'] === 'pending'): ?>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 inline-flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                    <span>Pending Approval</span>
                                </span>
                                <?php elseif ($r['status'] === 'approved'): ?>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-green-100 text-green-700 inline-flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-600"></span>
                                    <span>Disetujui</span>
                                </span>
                                <?php else: ?>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-red-100 text-red-700 inline-flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-600"></span>
                                    <span>Ditolak</span>
                                </span>
                                <?php endif; ?>
                            </div>
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">
                                <?= $r['registered_by'] === 'admin' ? 'Input: Staf Admin' : 'Input: Web Calon Siswa' ?>
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right whitespace-nowrap space-x-1">
                            <!-- Cetak Bukti Daftar -->
                            <a href="<?= url('admin/registrations/proof/' . $r['id']) ?>" target="_blank"
                               title="Cetak Bukti Pendaftaran"
                               class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-blue-50 hover:bg-blue-100 text-pepsi-blue font-bold transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span>Bukti</span>
                            </a>
                            <!-- Edit -->
                            <button @click="editReg = <?= htmlspecialchars(json_encode([
                                'id' => $r['id'],
                                'student_name' => $r['student_name'],
                                'phone' => $r['phone'],
                                'city' => $r['city'],
                                'time_slot' => $r['time_slot'],
                                'period_name' => $r['period_name'],
                                'program_ids' => array_values(array_filter(array_map(fn($p) => (string)($p['id'] ?? ''), $progs))),
                                'admin_notes' => $r['admin_notes'] ?? ''
                            ])) ?>; editModal = true;" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition">
                                Edit
                            </button>

                            <!-- Approval actions -->
                            <?php if ($r['status'] === 'pending'): ?>
                                <button @click="selectedReg = <?= htmlspecialchars(json_encode([
                                    'id' => $r['id'],
                                    'name' => $r['student_name'],
                                    'no' => $r['registration_no']
                                ])) ?>; approveModal = true;" class="px-3 py-1.5 rounded-xl bg-green-600 hover:bg-green-700 text-white font-bold transition shadow-sm">
                                    &check; Approve
                                </button>
                                <button @click="selectedReg = <?= htmlspecialchars(json_encode([
                                    'id' => $r['id'],
                                    'name' => $r['student_name'],
                                    'no' => $r['registration_no']
                                ])) ?>; rejectModal = true;" class="px-2.5 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-800 font-bold transition">
                                    Tolak
                                </button>
                            <?php elseif ($r['status'] === 'approved'): ?>
                                <?php if ($r['registered_by'] !== 'admin'): ?>
                                <a href="<?= $waChatUrl ?>" target="_blank" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-green-100 hover:bg-green-200 text-green-800 font-bold transition">
                                    <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.007c.106.005.249-.04.39.299.144.347.491 1.199.534 1.286.043.087.072.188.014.303-.058.116-.087.188-.173.289l-.26.302c-.087.087-.179.182-.077.357.101.174.45 1.084 1.144 1.702.89.794 1.64 1.04 1.871 1.155.231.116.368.101.505-.058.137-.159.587-.683.743-.918.156-.235.312-.196.526-.117.214.079 1.36.642 1.594.757.234.115.39.173.448.271.058.098.058.568-.086.973z"/></svg>
                                    <span>Kirim Konfirmasi WA</span>
                                </a>
                                <button @click="selectedReg = <?= htmlspecialchars(json_encode([
                                    'id' => $r['id'],
                                    'name' => $r['student_name'],
                                    'no' => $r['registration_no']
                                ])) ?>; rejectModal = true;" class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold transition">
                                    Batalkan
                                </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <button @click="selectedReg = <?= htmlspecialchars(json_encode([
                                    'id' => $r['id'],
                                    'name' => $r['student_name'],
                                    'no' => $r['registration_no']
                                ])) ?>; approveModal = true;" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition">
                                    Setujui Ulang
                                </button>
                            <?php endif; ?>

                            <!-- Delete -->
                            <form action="<?= url('admin/registrations/delete/' . $r['id']) ?>" method="POST" class="inline" onsubmit="return confirm('Hapus data pendaftaran ini?')">
                                <button type="submit" class="px-2.5 py-1.5 rounded-xl bg-red-50 hover:bg-red-100 text-pepsi-red font-bold transition">
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

    <!-- MODAL PENDAFTARAN OLEH ADMIN -->
    <div x-show="addModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="addModal = false" class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-8 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="font-heading font-black text-xl text-pepsi-navy">Daftarkan Siswa Baru (Direct Admin)</h3>
                    <p class="text-xs text-slate-400">Input pendaftaran siswa offline atau via telepon langsung ke sistem.</p>
                </div>
                <button @click="addModal = false" class="text-slate-400 hover:text-slate-700">&times;</button>
            </div>

            <form action="<?= url('admin/registrations/store') ?>" method="POST" class="space-y-4 text-xs">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Siswa *</label>
                        <input type="text" name="student_name" x-model="studentName" @input="checkRepeat()" required placeholder="Contoh: Rian Anggara" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nomor WhatsApp Siswa *</label>
                        <input type="tel" name="phone" x-model="studentPhone" @input="checkRepeat()" required placeholder="081234567890" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kota Domisili *</label>
                        <input type="text" name="city" required placeholder="Contoh: Bandung" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">
                            Periode Belajar
                            <span x-show="!isKidsSelected" class="text-red-500">*</span>
                            <span x-show="isKidsSelected" class="text-amber-600 text-[10px] font-bold ml-1">(Kids: Otomatis Tanpa Periode)</span>
                        </label>
                        <input type="hidden" name="period_name" value="" :disabled="!isKidsSelected">
                        <select name="period_name"
                                :disabled="isKidsSelected"
                                :required="!isKidsSelected"
                                :class="isKidsSelected ? 'bg-slate-100 text-slate-400 cursor-not-allowed border-slate-200 opacity-60' : 'bg-slate-50 font-bold text-pepsi-blue border-slate-200'"
                                class="w-full rounded-xl border p-2.5 transition">
                            <?php foreach ($periods as $per): ?>
                            <option value="<?= sanitize($per['name']) ?>" <?= $per['is_active'] ? 'selected' : '' ?>>
                                <?= sanitize($per['name']) ?> <?= $per['is_active'] ? '(Aktif)' : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div x-show="isKidsSelected" x-cloak class="mt-1 flex items-center gap-1.5 text-[11px] font-bold text-amber-800 bg-amber-50 border border-amber-200 px-2 py-1 rounded-lg">
                            <span>🧒</span>
                            <span>Program Kids: Fleksibel tanpa sistem periode.</span>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1.5">Pilih Program Kursus &amp; Jam Sesi (1 program 1 sesi) *</label>
                    <div class="space-y-2 max-h-52 overflow-y-auto border border-slate-200 rounded-2xl p-2.5 bg-slate-50">
                        <?php foreach ($programs as $prog): ?>
                        <div class="flex flex-col sm:flex-row sm:items-center gap-2 p-2 rounded-xl bg-white border border-slate-200">
                            <label class="flex items-center gap-2 flex-1 cursor-pointer text-[11px]">
                                <input type="checkbox" name="program_ids[]" value="<?= $prog['id'] ?>" x-model="regPrograms" @change="checkRepeat()" class="rounded text-pepsi-blue">
                                <span class="font-bold text-slate-800"><?= sanitize($prog['title']) ?> (<?= formatRupiah($prog['price']) ?>)</span>
                            </label>
                            <select name="time_slots[<?= $prog['id'] ?>]" :disabled="!regPrograms.includes('<?= $prog['id'] ?>')" :required="regPrograms.includes('<?= $prog['id'] ?>')" class="w-full sm:w-44 rounded-lg border border-slate-200 p-1.5 bg-slate-50 font-bold text-[10px] disabled:opacity-40 disabled:cursor-not-allowed">
                                <option value="">-- Pilih Jam --</option>
                                <option value="07.00-08.30 WIB">07.00-08.30 WIB</option>
                                <option value="08.30-10.00 WIB">08.30-10.00 WIB</option>
                                <option value="10.00-11.30 WIB">10.00-11.30 WIB</option>
                                <option value="13.00-14.30 WIB">13.00-14.30 WIB</option>
                                <option value="14.30-16.00 WIB">14.30-16.00 WIB</option>
                                <option value="16.00-17.30 WIB">16.00-17.30 WIB</option>
                                <option value="16.15-17.30 WIB">16.15-17.30 WIB</option>
                                <option value="19.00-20.00 WIB">19.00-20.00 WIB</option>
                                <option value="20.00-21.00 WIB">20.00-21.00 WIB</option>
                            </select>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">Centang program, lalu pilih jam sesi untuk masing-masing program (boleh berbeda tiap program).</p>
                </div>

                <!-- Warning Siswa Pernah Mengambil Program yang Sama -->
                <div x-show="repeatWarning.show" x-cloak class="p-3.5 rounded-2xl bg-red-50 border-2 border-red-300 text-red-900 space-y-2">
                    <div class="flex items-start gap-2.5">
                        <span class="text-xl leading-none">⚠️</span>
                        <div class="flex-1 text-xs">
                            <h4 class="font-heading font-black text-red-700 uppercase tracking-wide">Peringatan: Program Sudah Pernah Diambil!</h4>
                            <div class="mt-1 font-medium text-red-800" x-html="repeatWarning.message"></div>
                            <p class="text-[11px] text-red-600 mt-1 italic font-semibold">
                                Aturan Lembaga: Siswa tidak diperkenankan mengambil program yang sama walaupun sudah lama.
                            </p>
                        </div>
                    </div>
                    <div class="pt-2 border-t border-red-200 flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer text-[11px] font-bold text-red-900 select-none">
                            <input type="checkbox" name="allow_repeat_override" value="1" class="rounded text-red-600 focus:ring-red-500">
                            <span>Dispensasi Khusus (Disetujui Pimpinan untuk mengulang)</span>
                        </label>
                    </div>
                </div>

                <div class="p-3 rounded-2xl bg-green-50 border border-green-200 text-[11px] text-green-800 font-semibold flex items-start gap-2">
                    <span class="text-green-600 font-black">&check;</span>
                    <span>Pendaftaran direct admin otomatis <b>LANGSUNG DISETUJUI (Approved)</b> tanpa menunggu approval — siswa langsung masuk ke Master Data Siswa Aktif.</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Sudah Dibayar (Rp)</label>
                        <input type="number" name="paid_amount" value="0" placeholder="0 jika belum bayar" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Metode Pembayaran</label>
                        <select name="payment_method" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                            <option value="tunai">💵 Tunai / Cash</option>
                            <option value="transfer">🏦 Transfer Bank</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Catatan Admin (Opsional)</label>
                    <input type="text" name="admin_notes" placeholder="Catatan khusus siswa..." class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>

                <!-- Print Receipt Checkbox Option -->
                <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200/80 flex items-center justify-between">
                    <label class="flex items-center gap-2.5 cursor-pointer text-xs font-bold text-emerald-900 select-none">
                        <input type="checkbox" name="print_receipt" value="1" checked class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span>Langsung cetak kwitansi / e-Nota setelah simpan</span>
                    </label>
                    <span class="text-[10px] text-emerald-700 font-extrabold bg-emerald-100 px-2 py-0.5 rounded-full uppercase">e-Nota</span>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="addModal = false" class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
                    <button type="submit" name="action" value="save" class="px-4 py-2.5 rounded-xl bg-slate-800 text-white font-bold hover:bg-slate-900 transition">
                        Simpan Saja
                    </button>
                    <button type="submit" name="action" value="save_and_print" class="px-5 py-2.5 rounded-xl bg-pepsi-blue hover:bg-blue-900 text-white font-bold transition flex items-center gap-1.5 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        <span>Simpan Pendaftaran</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT PENDAFTARAN -->
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="editModal = false" class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-8 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="font-heading font-black text-xl text-pepsi-navy">Edit Data Pendaftaran</h3>
                    <p class="text-xs text-slate-400">Koreksi data siswa, periode, jam sesi, dan program yang dipilih.</p>
                </div>
                <button @click="editModal = false" class="text-slate-400 hover:text-slate-700">&times;</button>
            </div>

            <form :action="'<?= url('admin/registrations/update/') ?>' + editReg.id" method="POST" class="space-y-4 text-xs">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Siswa *</label>
                        <input type="text" name="student_name" x-model="editReg.student_name" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nomor WhatsApp Siswa *</label>
                        <input type="tel" name="phone" x-model="editReg.phone" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kota Domisili *</label>
                        <input type="text" name="city" x-model="editReg.city" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">
                            Periode Belajar
                            <span x-show="!isEditKidsSelected" class="text-red-500">*</span>
                            <span x-show="isEditKidsSelected" class="text-amber-600 text-[10px] font-bold ml-1">(Kids: Otomatis Tanpa Periode)</span>
                        </label>
                        <input type="hidden" name="period_name" value="" :disabled="!isEditKidsSelected">
                        <select name="period_name" x-model="editReg.period_name"
                                :disabled="isEditKidsSelected"
                                :required="!isEditKidsSelected"
                                :class="isEditKidsSelected ? 'bg-slate-100 text-slate-400 cursor-not-allowed border-slate-200 opacity-60' : 'bg-slate-50 font-bold text-pepsi-blue border-slate-200'"
                                class="w-full rounded-xl border p-2.5 transition">
                            <option value="">-- Pilih Periode --</option>
                            <?php foreach ($periods as $per): ?>
                            <option value="<?= sanitize($per['name']) ?>"><?= sanitize($per['name']) ?> <?= $per['is_active'] ? '(Aktif)' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div x-show="isEditKidsSelected" x-cloak class="mt-1 flex items-center gap-1.5 text-[11px] font-bold text-amber-800 bg-amber-50 border border-amber-200 px-2 py-1 rounded-lg">
                            <span>🧒</span>
                            <span>Program Kids: Fleksibel tanpa sistem periode.</span>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Pilihan Jam Sesi Kelas *</label>
                    <select name="time_slot" x-model="editReg.time_slot" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                        <option value="">-- Pilih Jam Kelas --</option>
                        <option value="07.00-08.30 WIB">07.00-08.30 WIB</option>
                        <option value="08.30-10.00 WIB">08.30-10.00 WIB</option>
                        <option value="10.00-11.30 WIB">10.00-11.30 WIB</option>
                        <option value="13.00-14.30 WIB">13.00-14.30 WIB</option>
                        <option value="14.30-16.00 WIB">14.30-16.00 WIB</option>
                        <option value="16.00-17.30 WIB">16.00-17.30 WIB</option>
                        <option value="16.15-17.30 WIB">16.15-17.30 WIB</option>
                        <option value="19.00-20.00 WIB">19.00-20.00 WIB</option>
                        <option value="20.00-21.00 WIB">20.00-21.00 WIB</option>
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1.5">Pilih Program Kursus (Bisa lebih dari 1) *</label>
                    <div class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto border border-slate-200 rounded-2xl p-2.5 bg-slate-50">
                        <?php foreach ($programs as $prog): ?>
                        <label class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-white text-[11px] cursor-pointer">
                            <input type="checkbox" name="program_ids[]" value="<?= $prog['id'] ?>" x-model="editReg.program_ids" class="rounded text-pepsi-blue">
                            <span class="font-bold text-slate-800"><?= sanitize($prog['title']) ?> (<?= formatRupiah($prog['price']) ?>)</span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Catatan Admin (Opsional)</label>
                    <input type="text" name="admin_notes" x-model="editReg.admin_notes" placeholder="Catatan khusus siswa..." class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="editModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-pepsi-blue hover:bg-blue-900 text-white font-bold transition">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL APPROVE -->
    <div x-show="approveModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="approveModal = false" class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl space-y-4">
            <div class="text-center space-y-1">
                <div class="w-12 h-12 rounded-full bg-green-100 text-green-700 flex items-center justify-center mx-auto text-xl font-bold">
                    &check;
                </div>
                <h3 class="font-heading font-black text-lg text-slate-900">Setujui Pendaftaran?</h3>
                <p class="text-xs text-slate-500">
                    Pendaftaran atas nama <b class="text-slate-800" x-text="selectedReg.name"></b> (<span x-text="selectedReg.no"></span>) akan disetujui resmi.
                </p>
            </div>

            <form :action="'<?= url('admin/registrations/approve/') ?>' + selectedReg.id" method="POST" class="space-y-3 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Catatan Persetujuan (Opsional)</label>
                    <input type="text" name="notes" value="Pendaftaran disetujui dan slot kelas dikonfirmasi" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <button type="button" @click="approveModal = false" class="flex-1 py-2.5 bg-slate-100 text-slate-700 font-bold rounded-xl">Batal</button>
                    <button type="submit" class="flex-1 py-2.5 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl shadow-md">Setujui Siswa</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL REJECT -->
    <div x-show="rejectModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="rejectModal = false" class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl space-y-4">
            <div class="text-center space-y-1">
                <div class="w-12 h-12 rounded-full bg-red-100 text-red-700 flex items-center justify-center mx-auto text-xl font-bold">
                    &times;
                </div>
                <h3 class="font-heading font-black text-lg text-slate-900">Tolak / Batalkan Pendaftaran?</h3>
                <p class="text-xs text-slate-500">
                    Pendaftaran atas nama <b class="text-slate-800" x-text="selectedReg.name"></b> (<span x-text="selectedReg.no"></span>) akan ditolak.
                </p>
            </div>

            <form :action="'<?= url('admin/registrations/reject/') ?>' + selectedReg.id" method="POST" class="space-y-3 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Alasan Penolakan</label>
                    <input type="text" name="notes" placeholder="Contoh: Kuota kelas penuh / batal atas permintaan siswa" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <button type="button" @click="rejectModal = false" class="flex-1 py-2.5 bg-slate-100 text-slate-700 font-bold rounded-xl">Batal</button>
                    <button type="submit" class="flex-1 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-md">Tolak Pendaftaran</button>
                </div>
            </form>
        </div>
    </div>

</div>
