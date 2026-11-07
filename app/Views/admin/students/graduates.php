<?php
// Siswa Lulus Program & Sertifikat
$totalCerts      = (int)($gradStats['total'] ?? 0);
$uniqueStudents  = (int)($gradStats['unique_students'] ?? 0);
$topGrade        = $gradStats['grades'][0]['grade'] ?? null;
$topGradeCount   = (int)($gradStats['grades'][0]['total'] ?? 0);

// Warna badge predikat kelulusan
$gradeColors = [
    'EXCELLENT' => 'bg-emerald-100 text-emerald-700 border border-emerald-300',
    'VERY GOOD' => 'bg-blue-100 text-blue-700 border border-blue-300',
    'GOOD'      => 'bg-amber-100 text-amber-700 border border-amber-300',
    'AVERAGE'   => 'bg-orange-100 text-orange-700 border border-orange-300',
    'POOR'      => 'bg-red-100 text-red-700 border border-red-300',
];

// Data siswa aktif untuk search & dropdown
$studentsData = array_map(function($s) {
    return [
        'id'           => (int)$s['id'],
        'full_name'    => (string)$s['full_name'],
        'student_no'   => (string)$s['student_no'],
        'phone'        => (string)($s['phone'] ?? ''),
        'city'         => (string)($s['city'] ?? ''),
        'period_name'  => (string)($s['period_name'] ?? ''),
        'programs_raw' => (string)($s['programs_json'] ?? '[]'),
    ];
}, $activeStudents);
?>

<script>
function graduatesApp() {
    return {
        issueModal: false,
        allStudents: <?= json_encode($studentsData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
        studentSearch: '',
        searchOpen: false,
        selectedStudentId: '',
        selectedStudent: null,
        programs: [],
        periodName: '',
        grade: 'GOOD',
        certMode: 'auto',
        manualCertNo: '',
        lastCertNo: <?= json_encode($lastCert['certificate_no'] ?? null) ?>,
        lastCertStudent: <?= json_encode($lastCert['full_name'] ?? null) ?>,
        lastCertDate: <?= json_encode($lastCert ? date('d/m/Y', strtotime($lastCert['graduated_at'])) : null) ?>,
        nextCertPreview: <?= json_encode($nextCertPreview ?? '') ?>,

        filteredStudents() {
            let q = (this.studentSearch || '').toLowerCase().trim();
            if (!q) return this.allStudents;
            return this.allStudents.filter(s => {
                return (s.full_name && s.full_name.toLowerCase().includes(q)) ||
                       (s.student_no && s.student_no.toLowerCase().includes(q)) ||
                       (s.city && s.city.toLowerCase().includes(q)) ||
                       (s.phone && s.phone.toLowerCase().includes(q));
            });
        },

        selectStudent(s) {
            if (!s) {
                this.clearStudent();
                return;
            }
            this.selectedStudentId = String(s.id);
            this.selectedStudent = s;
            this.periodName = s.period_name || '';
            this.searchOpen = false;
            this.studentSearch = '';

            this.programs = [];
            try {
                let progs = JSON.parse(s.programs_raw || '[]');
                this.programs = progs.map(p => ({
                    title: p.title || '',
                    code: p.code || '',
                    manual_cert_no: '',
                    checked: true
                }));
            } catch(e) {}
            if (this.programs.length === 0) {
                this.programs = [{ title: '', code: '', manual_cert_no: '', checked: true }];
            }
        },

        clearStudent() {
            this.selectedStudentId = '';
            this.selectedStudent = null;
            this.periodName = '';
            this.programs = [];
            this.studentSearch = '';
            this.searchOpen = false;
        },

        onDropdownChange(event) {
            let val = event.target.value;
            if (!val) {
                this.clearStudent();
                return;
            }
            let s = this.allStudents.find(x => String(x.id) === String(val));
            if (s) {
                this.selectStudent(s);
            }
        },

        addProgram() {
            this.programs.push({ title: '', code: '', manual_cert_no: '', checked: true });
        },

        removeProgram(i) {
            this.programs.splice(i, 1);
        },

        validateAndSubmit(event) {
            if (!this.selectedStudentId) {
                alert('Silakan pilih siswa terlebih dahulu.');
                return;
            }
            if (this.certMode === 'manual' && !this.manualCertNo.trim()) {
                alert('Nomor sertifikat manual wajib diisi.');
                return;
            }
            event.target.submit();
        }
    };
}

document.addEventListener('alpine:init', () => {
    Alpine.data('graduatesApp', graduatesApp);
});
</script>

<div x-data="graduatesApp()" class="space-y-6">

    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2">
                <span>Siswa Lulus & Sertifikat</span>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 font-extrabold uppercase">Sertifikasi Resmi</span>
            </h1>
            <p class="text-sm text-slate-500 mt-0.5">Daftar kelulusan siswa, riwayat nilai ujian akhir, dan cetak sertifikat kelulusan standar.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="<?= url('admin/students') ?>" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs flex items-center gap-2 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Kembali ke Siswa Aktif</span>
            </a>
            <button @click="issueModal = true" class="px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs flex items-center gap-2 shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Terbitkan Sertifikat Baru</span>
            </button>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Sertifikat Terbit</p>
                <p class="text-2xl font-black text-slate-900"><?= number_format($totalCerts) ?></p>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Siswa Berhasil Lulus</p>
                <p class="text-2xl font-black text-emerald-600"><?= number_format($uniqueStudents) ?> <span class="text-xs font-semibold text-slate-400">Orang</span></p>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-pepsi-blue flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Predikat Terbanyak</p>
                <p class="text-xl font-black text-slate-900"><?= $topGrade ? sanitize($topGrade) : '-' ?> <span class="text-xs font-semibold text-slate-400">(<?= number_format($topGradeCount) ?>)</span></p>
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm">
        <form method="GET" action="<?= url('admin/students/graduates') ?>" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="text-xs font-extrabold text-slate-700">Filter Kelulusan:</span>
                <?php if (!empty($periods)): ?>
                <select name="period" onchange="this.form.submit()" class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-medium text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <option value="">-- Semua Periode --</option>
                    <?php foreach ($periods as $p): ?>
                        <option value="<?= sanitize($p) ?>" <?= $currentPeriod === $p ? 'selected' : '' ?>><?= sanitize($p) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
            </div>

            <div class="flex items-center gap-2">
                <div class="relative flex-1 sm:w-72">
                    <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Cari nama, no sertifikat, program..."
                           class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <button type="submit" class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition">
                    Cari
                </button>
                <?php if ($search || $currentPeriod): ?>
                    <a href="<?= url('admin/students/graduates') ?>" class="px-3 py-2 rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 text-xs font-bold transition">
                        Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Graduates List Container -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-extrabold text-sm text-slate-800 flex items-center gap-2">
                <span>Daftar Sertifikat Terbit (<?= count($graduations) ?>)</span>
            </h3>
            <span class="text-xs text-slate-400 font-medium">Klik "Cetak" untuk melihat / print sertifikat</span>
        </div>

        <?php if (empty($graduations)): ?>
            <div class="p-12 text-center">
                <div class="w-16 h-16 rounded-full bg-amber-50 text-amber-500 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                </div>
                <h4 class="text-base font-bold text-slate-700">Belum Ada Sertifikat Kelulusan</h4>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Terbitkan sertifikat kelulusan bagi siswa yang telah menyelesaikan masa program belajar kursus.</p>
                <button @click="issueModal = true" class="mt-4 px-4 py-2 rounded-xl bg-amber-500 text-white text-xs font-bold hover:bg-amber-600 transition inline-flex items-center gap-1.5 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Terbitkan Sertifikat Sekarang</span>
                </button>
            </div>
        <?php else: ?>
            <!-- Desktop Table View -->
            <div class="hidden lg:block overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50/80 text-slate-500 uppercase font-extrabold text-[10px] tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3 px-4">No. Sertifikat</th>
                            <th class="py-3 px-4">Nama Siswa / NIS</th>
                            <th class="py-3 px-4">Program Kursus</th>
                            <th class="py-3 px-4">Periode & Tgl Lulus</th>
                            <th class="py-3 px-4 text-center">Predikat Nilai</th>
                            <th class="py-3 px-4 text-center">Aksi Cetak</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($graduations as $g): ?>
                            <?php
                                $isKidRow = (bool)(int)($g['is_kid'] ?? 0);
                                if (!$isKidRow) {
                                    $hay = strtoupper(($g['program_title'] ?? '') . ' ' . ($g['program_code'] ?? ''));
                                    $isKidRow = str_contains($hay, 'KID');
                                }
                            ?>
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3 px-4">
                                    <div class="font-mono font-bold text-amber-700 bg-amber-50 border border-amber-200/80 px-2.5 py-1 rounded-lg inline-block text-[11px]">
                                        <?= sanitize($g['certificate_no']) ?>
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-1">Dibuat: <?= date('d M Y', strtotime($g['created_at'])) ?></div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-extrabold text-slate-800 text-sm"><?= sanitize($g['full_name']) ?></div>
                                    <div class="font-mono text-[11px] text-slate-500 mt-0.5">NIS: <?= sanitize($g['student_no']) ?></div>
                                    <div class="text-[10px] text-slate-400"><?= sanitize($g['city'] ?: 'Kota -') ?> &bull; <?= sanitize($g['phone']) ?></div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-800 text-xs"><?= sanitize($g['program_title']) ?></div>
                                    <?php if ($g['program_code']): ?>
                                        <div class="text-[10px] text-slate-400 font-mono mt-0.5">Kode: <?= sanitize($g['program_code']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-700"><?= sanitize($g['period_name'] ?: '-') ?></div>
                                    <div class="text-[11px] text-emerald-600 font-bold mt-0.5">Lulus: <?= date('d F Y', strtotime($g['graduated_at'])) ?></div>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-black <?= $gradeColors[$g['grade']] ?? 'bg-slate-100 text-slate-700 border border-slate-300' ?>">
                                        <?= sanitize($g['grade'] ?: '-') ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Cetak Sertifikat -->
                                        <a href="<?= url('admin/students/certificate/' . $g['id']) ?>" target="_blank"
                                           class="px-3 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                            <span>Cetak Sertifikat</span>
                                        </a>

                                        <?php if (!$isKidRow): ?>
                                        <!-- Cetak Sertifikat dengan TTD (khusus non-kid) -->
                                        <a href="<?= url('admin/students/certificate/' . $g['id']) ?>?v=ttd" target="_blank"
                                           class="px-3 py-1.5 rounded-xl bg-indigo-500 hover:bg-indigo-600 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            <span>Cetak + TTD</span>
                                        </a>
                                        <?php endif; ?>

                                        <!-- Hapus Riwayat Kelulusan -->
                                        <form method="POST" action="<?= url('admin/students/delete-grad/' . $g['id']) ?>" onsubmit="return confirm('Hapus catatan kelulusan sertifikat <?= addslashes($g['certificate_no']) ?>?')">
                                            <button type="submit" title="Hapus Sertifikat" class="p-1.5 rounded-xl bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Android Card View -->
            <div class="lg:hidden divide-y divide-slate-100">
                <?php foreach ($graduations as $g): ?>
                    <?php
                        $isKidRow = (bool)(int)($g['is_kid'] ?? 0);
                        if (!$isKidRow) {
                            $hay = strtoupper(($g['program_title'] ?? '') . ' ' . ($g['program_code'] ?? ''));
                            $isKidRow = str_contains($hay, 'KID');
                        }
                    ?>
                    <div class="p-4 space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="font-mono text-[10px] text-amber-700 bg-amber-50 px-2 py-0.5 rounded font-bold border border-amber-200/60 inline-block">
                                    <?= sanitize($g['certificate_no']) ?>
                                </span>
                                <h4 class="font-extrabold text-slate-900 text-sm mt-1"><?= sanitize($g['full_name']) ?></h4>
                                <div class="text-[11px] text-slate-500 font-mono">NIS: <?= sanitize($g['student_no']) ?></div>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-black <?= $gradeColors[$g['grade']] ?? 'bg-slate-100 text-slate-700' ?>">
                                <?= sanitize($g['grade'] ?: '-') ?>
                            </span>
                        </div>

                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 text-[11px] space-y-1">
                            <div class="flex justify-between">
                                <span class="text-slate-400 font-semibold">Program:</span>
                                <span class="font-bold text-slate-800 text-right"><?= sanitize($g['program_title']) ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400 font-semibold">Periode:</span>
                                <span class="font-medium text-slate-700"><?= sanitize($g['period_name'] ?: '-') ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400 font-semibold">Tgl Lulus:</span>
                                <span class="font-bold text-emerald-600"><?= date('d F Y', strtotime($g['graduated_at'])) ?></span>
                            </div>
                        </div>

                        <!-- Mobile Action Buttons -->
                        <div class="flex items-center gap-2 pt-1">
                            <a href="<?= url('admin/students/certificate/' . $g['id']) ?>" target="_blank"
                               class="flex-1 py-2 rounded-xl bg-amber-500 text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-sm">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                <span>Cetak</span>
                            </a>
                            <?php if (!$isKidRow): ?>
                            <a href="<?= url('admin/students/certificate/' . $g['id']) ?>?v=ttd" target="_blank"
                               class="flex-1 py-2 rounded-xl bg-indigo-500 text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-sm">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                <span>+ TTD</span>
                            </a>
                            <?php endif; ?>
                            <form method="POST" action="<?= url('admin/students/delete-grad/' . $g['id']) ?>" onsubmit="return confirm('Hapus catatan kelulusan sertifikat <?= addslashes($g['certificate_no']) ?>?')">
                                <button type="submit" class="p-2 rounded-xl bg-red-50 text-red-500 hover:bg-red-500 hover:text-white">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ─────────────────────────────────────────────────────────────
         MODAL: TERBITKAN SERTIFIKAT BARU
         ───────────────────────────────────────────────────────────── -->
    <div x-show="issueModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="issueModal = false"></div>
        <div class="min-h-screen px-4 flex items-center justify-center p-4">
            <div class="relative bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-7 shadow-2xl border border-slate-100 space-y-4 my-8">
                <!-- Modal Header -->
                <div class="flex items-start justify-between border-b border-slate-100 pb-3.5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base sm:text-lg font-black text-slate-800 tracking-tight">Form Penerbitan Sertifikat Kelulusan</h3>
                            <p class="text-xs text-slate-500">Nomor sertifikat resmi otomatis atau input manual sesuai kebutuhan</p>
                        </div>
                    </div>
                    <button @click="issueModal = false" class="text-slate-400 hover:text-slate-700 text-xl font-bold p-1 rounded-xl hover:bg-slate-100 transition">&times;</button>
                </div>

                <!-- Info Nomor Sertifikat Terakhir & Estimasi Berikutnya -->
                <div class="p-3.5 bg-gradient-to-r from-amber-50/90 via-amber-50/50 to-indigo-50/50 border border-amber-200/80 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
                    <div class="flex items-start gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-xs mt-0.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
                        </div>
                        <div>
                            <span class="text-[10px] font-black uppercase text-amber-800/80 tracking-wider block">Nomor Terakhir Sertifikat</span>
                            <div class="font-mono font-black text-xs text-slate-900 mt-0.5 flex flex-wrap items-center gap-1.5">
                                <template x-if="lastCertNo">
                                    <span class="bg-white border border-amber-300 text-amber-900 px-2.5 py-0.5 rounded-lg shadow-2xs font-bold" x-text="lastCertNo"></span>
                                </template>
                                <template x-if="!lastCertNo">
                                    <span class="text-slate-500 italic font-sans font-normal text-xs">Belum ada sertifikat terbit</span>
                                </template>
                                <template x-if="lastCertStudent">
                                    <span class="text-[11px] font-sans font-semibold text-slate-600" x-text="'• an. ' + lastCertStudent + (lastCertDate ? ' (' + lastCertDate + ')' : '')"></span>
                                </template>
                            </div>
                        </div>
                    </div>
                    <div class="text-left sm:text-right border-t sm:border-t-0 pt-2 sm:pt-0 border-amber-200/60 shrink-0">
                        <span class="text-[10px] font-bold uppercase text-slate-500 tracking-wider block">No. Berikutnya (Otomatis)</span>
                        <span class="font-mono font-black text-xs text-indigo-700 bg-white border border-indigo-200 px-2.5 py-0.5 rounded-lg inline-block mt-0.5 shadow-2xs" x-text="nextCertPreview || '-'"></span>
                    </div>
                </div>

                <form method="POST" action="<?= url('admin/students/issue-cert') ?>"
                      @submit.prevent="validateAndSubmit($event)"
                      class="space-y-4 text-xs">

                    <input type="hidden" name="student_id" :value="selectedStudentId">

                    <!-- ── PILIH SISWA (SEARCH & DROPDOWN) ──────────────── -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="block font-bold text-slate-700 text-xs">
                                <span>Pilih Siswa *</span>
                            </label>
                            <span class="text-[10px] text-slate-400 font-medium">Bisa cari ketik nama/NIS atau pilih dropdown</span>
                        </div>

                        <!-- Card Siswa Terpilih (jika sudah memilih) -->
                        <div x-show="selectedStudent" class="p-3 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center justify-between transition">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-black text-xs shrink-0 shadow-2xs">
                                    ✓
                                </div>
                                <div>
                                    <div class="font-black text-slate-900 text-xs sm:text-sm" x-text="selectedStudent ? selectedStudent.full_name : ''"></div>
                                    <div class="text-[11px] text-slate-600 font-mono" x-text="selectedStudent ? ('NIS: ' + selectedStudent.student_no + (selectedStudent.city ? ' • ' + selectedStudent.city : '') + (selectedStudent.phone ? ' • ' + selectedStudent.phone : '')) : ''"></div>
                                </div>
                            </div>
                            <button type="button" @click="clearStudent()" class="px-2.5 py-1.5 rounded-xl bg-white border border-emerald-300 hover:bg-emerald-100 text-emerald-800 text-xs font-bold transition flex items-center gap-1 shadow-2xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                <span>Ganti Siswa</span>
                            </button>
                        </div>

                        <!-- Input Pencarian & Dropdown (Tampil saat belum pilih atau ingin ganti) -->
                        <div x-show="!selectedStudent" class="space-y-2">
                            <!-- Input Search Nama / NIS -->
                            <div class="relative">
                                <input type="text" x-model="studentSearch" @input="searchOpen = true" @focus="searchOpen = true"
                                       placeholder="🔍 Ketik nama, NIS, atau kota siswa untuk mencari..."
                                       class="w-full pl-9 pr-8 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-amber-500 focus:outline-none text-xs font-medium text-slate-800 bg-white shadow-2xs">
                                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                <button type="button" x-show="studentSearch.length > 0" @click="studentSearch = ''; searchOpen = false" class="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 font-bold text-xs p-1">&times;</button>

                                <!-- Popover Hasil Pencarian Cepat saat Mengetik -->
                                <div x-show="studentSearch.trim().length > 0 && searchOpen" @click.away="searchOpen = false"
                                     class="absolute left-0 right-0 top-full mt-1 max-h-48 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl divide-y divide-slate-100 z-30">
                                    <template x-for="s in filteredStudents()" :key="s.id">
                                        <button type="button" @click="selectStudent(s)" class="w-full text-left px-3 py-2 hover:bg-amber-50 flex items-center justify-between transition">
                                            <div>
                                                <div class="font-bold text-slate-800 text-xs" x-text="s.full_name"></div>
                                                <div class="text-[10px] text-slate-400 font-mono" x-text="'NIS: ' + s.student_no + (s.city ? ' • ' + s.city : '')"></div>
                                            </div>
                                            <span class="text-[10px] px-2 py-0.5 rounded-md bg-amber-100 text-amber-800 font-extrabold">Pilih</span>
                                        </button>
                                    </template>
                                    <div x-show="filteredStudents().length === 0" class="px-3 py-3 text-center text-slate-400 text-xs italic">
                                        Tidak ada siswa aktif yang cocok dengan " <span x-text="studentSearch"></span> "
                                    </div>
                                </div>
                            </div>

                            <!-- Dropdown Select Alternatif -->
                            <div>
                                <select x-model="selectedStudentId" @change="onDropdownChange($event)"
                                        class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-amber-500 focus:outline-none font-semibold text-slate-800 bg-slate-50 text-xs">
                                    <option value="">-- Atau Pilih dari Dropdown Siswa (<?= count($activeStudents) ?> Siswa) --</option>
                                    <?php foreach ($activeStudents as $as): ?>
                                        <option value="<?= (int)$as['id'] ?>">
                                            <?= sanitize($as['full_name']) ?> (<?= sanitize($as['student_no']) ?><?= !empty($as['city']) ? ' - ' . sanitize($as['city']) : '' ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <span class="text-[10px] text-slate-400 block">Hanya menampilkan siswa aktif. Program kursus akan dimuat otomatis saat siswa dipilih.</span>
                        </div>
                    </div>

                    <!-- ── MODE PENOMORAN & FIELD MANUAL ──────────────── -->
                    <div class="p-3.5 bg-slate-50/80 rounded-2xl border border-slate-200 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <label class="block font-extrabold text-slate-800 text-xs">Penomoran Sertifikat *</label>
                                <span class="text-[10px] text-slate-400">Pilih nomor otomatis sistem atau input manual sendiri</span>
                            </div>
                            <div class="inline-flex rounded-xl p-1 bg-slate-200/80 text-[11px] font-bold self-start sm:self-auto">
                                <button type="button" @click="certMode = 'auto'"
                                        :class="certMode === 'auto' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                                        class="px-3 py-1 rounded-lg transition flex items-center gap-1.5">
                                    <span>⚡ Generate Otomatis</span>
                                </button>
                                <button type="button" @click="certMode = 'manual'"
                                        :class="certMode === 'manual' ? 'bg-amber-500 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                                        class="px-3 py-1 rounded-lg transition flex items-center gap-1.5">
                                    <span>✏️ Isi Nomor Manual</span>
                                </button>
                            </div>
                        </div>
                        <input type="hidden" name="cert_mode" :value="certMode">

                        <!-- Keterangan Mode Otomatis -->
                        <div x-show="certMode === 'auto'" class="flex items-center gap-2 text-[11px] text-slate-600 bg-white p-2.5 rounded-xl border border-slate-200/60">
                            <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <div>
                                Nomor sertifikat resmi akan <strong class="text-slate-800">di-generate otomatis</strong> oleh sistem.
                                <span class="text-indigo-600 font-mono font-bold" x-text="nextCertPreview ? '(Perkiraan: ' + nextCertPreview + ')' : ''"></span>
                            </div>
                        </div>

                        <!-- Field Input Nomor Manual -->
                        <div x-show="certMode === 'manual'" x-cloak class="space-y-1.5 bg-amber-50/70 p-3 rounded-xl border border-amber-200">
                            <div class="flex items-center justify-between">
                                <label class="block font-extrabold text-amber-900 text-xs">Nomor Sertifikat Manual *</label>
                                <span class="text-[10px] text-amber-700 font-bold uppercase tracking-wider">Mode Manual Aktif</span>
                            </div>
                            <input type="text" name="manual_cert_no" x-model="manualCertNo"
                                   placeholder="Contoh: 00003/MPU/C.GFS/XI/2026 atau SERT-2026-001"
                                   class="w-full px-3 py-2 rounded-xl border border-amber-300 focus:ring-2 focus:ring-amber-500 focus:outline-none font-mono font-bold text-slate-900 bg-white text-xs shadow-2xs">
                            <p class="text-[10px] text-slate-500">
                                Ketik nomor sertifikat resmi secara manual. Nomor ini akan langsung disimpan dan dicetak pada lembar sertifikat.
                            </p>
                        </div>
                    </div>

                    <!-- ── DAFTAR PROGRAM ─────────────────────────────── -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block font-bold text-slate-700">Program yang Diselesaikan *</label>
                            <button type="button" @click="addProgram()" class="text-[10px] font-bold text-amber-600 hover:text-amber-700">
                                + Tambah Program
                            </button>
                        </div>
                        <p class="text-[10px] text-slate-400 mb-1.5">Centang program yang ingin diterbitkan sertifikatnya. 1 program = 1 sertifikat.</p>
                        <div class="space-y-2">
                            <template x-for="(prog, i) in programs" :key="i">
                                <div class="p-2.5 rounded-xl border border-slate-200 bg-slate-50 space-y-2">
                                    <div class="flex items-start gap-2">
                                        <input type="checkbox" x-model="prog.checked" class="mt-2.5 rounded text-amber-500 focus:ring-amber-500">
                                        <div class="flex-1 grid grid-cols-1 sm:grid-cols-[2fr_1fr] gap-2">
                                            <input type="text" name="program_title[]" x-model="prog.title" :disabled="!prog.checked"
                                                   placeholder="Nama program"
                                                   class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-amber-500 focus:outline-none font-semibold disabled:opacity-50 text-xs">
                                            <input type="text" name="program_code[]" x-model="prog.code" :disabled="!prog.checked"
                                                   placeholder="Kode (mis. GFS)"
                                                   class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-amber-500 focus:outline-none font-mono font-bold uppercase disabled:opacity-50 text-xs">
                                        </div>
                                        <button type="button" @click="removeProgram(i)" class="mt-2 text-slate-400 hover:text-red-500 font-bold">&times;</button>
                                    </div>
                                    <!-- Jika Lebih dari 1 Program & Mode Manual Aktif -->
                                    <div x-show="certMode === 'manual' && programs.length > 1 && prog.checked" class="pl-6">
                                        <input type="text" name="manual_cert_no_item[]" x-model="prog.manual_cert_no"
                                               :placeholder="'No. manual program ' + (prog.title || ('#' + (i+1))) + ' (opsional jika sama dengan no di atas)'"
                                               class="w-full px-2.5 py-1.5 rounded-lg border border-amber-200 bg-white font-mono text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                    </div>
                                </div>
                            </template>
                            <p x-show="programs.length === 0" class="text-[10px] text-slate-400 italic">Pilih siswa terlebih dahulu untuk memuat daftar program.</p>
                        </div>
                    </div>

                    <!-- ── PERIODE KURSUS ─────────────────────────────── -->
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Periode Kursus</label>
                        <input type="text" name="period_name" x-model="periodName" placeholder="Contoh: Periode 10 Oktober 2026"
                               class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-amber-500 focus:outline-none text-xs font-medium">
                    </div>

                    <!-- ── TANGGAL & NILAI AKHIR ──────────────────────── -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Tanggal Kelulusan *</label>
                            <input type="date" name="graduated_at" value="<?= date('Y-m-d') ?>" required
                                   class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-amber-500 focus:outline-none text-xs font-medium">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nilai Ujian Akhir (Predikat) *</label>
                            <select name="grade" x-model="grade" required
                                    class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-amber-500 focus:outline-none font-bold text-slate-800 text-xs">
                                <option value="EXCELLENT">EXCELLENT (Istimewa)</option>
                                <option value="VERY GOOD">VERY GOOD (Sangat Baik)</option>
                                <option value="GOOD">GOOD (Baik)</option>
                                <option value="AVERAGE">AVERAGE (Cukup)</option>
                                <option value="POOR">POOR (Kurang)</option>
                            </select>
                        </div>
                    </div>

                    <!-- ── MODAL FOOTER ───────────────────────────────── -->
                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" @click="issueModal = false" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold transition">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold transition shadow-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Terbitkan Sertifikat</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
