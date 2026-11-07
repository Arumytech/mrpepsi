<?php
// Master Data Siswa Aktif
$totalStudents = (int)($stats['total'] ?? 0);
$activeStudents = (int)($stats['active'] ?? 0);
$gradStudents   = (int)($stats['graduated'] ?? 0);
$todayStudents  = (int)($stats['today'] ?? 0);
$todayPrograms  = (int)($stats['today_programs'] ?? 0);
$totalRevenue   = (float)($stats['total_revenue'] ?? 0);
$isTodayFilter  = (!empty($startDate) && !empty($endDate) && $startDate === date('Y-m-d') && $endDate === date('Y-m-d'));

// Hitung total program yang ada pada daftar siswa terfilter saat ini
$filteredProgramsCount = 0;
foreach ($students as $st) {
    $pList = json_decode($st['programs_json'] ?? '[]', true) ?: [];
    $filteredProgramsCount += max(1, count($pList));
}

$kidsProgramIds = array_values(array_map('strval', array_column(array_filter($programs ?? [], function($p) {
    return ((int)($p['category_id'] ?? 0) === 3)
        || stripos($p['title'] ?? '', 'KID') !== false
        || stripos($p['code'] ?? '', 'KID') !== false;
}), 'id')));
?>

<script>
function studentsManager() {
    return {
        addModal: false,
        regModal: false,
        regPrograms: [],
        kidsProgramIds: [<?= implode(',', array_map('intval', $kidsProgramIds)) ?>],
        get isKidsSelected() {
            var self = this;
            if (!Array.isArray(self.regPrograms)) return false;
            return self.regPrograms.some(function(id) {
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
        },
        editModal: false,
        graduateModal: false,
        payModal: false,
        payData: {},
        payAmount: 0,
        payMethod: 'tunai',
        payTotalFee: 0,
        payPaid: 0,
        openPay(student) {
            this.payData = student;
            this.payTotalFee = parseFloat(student.total_fee || 0);
            this.payPaid = parseFloat(student.paid_amount || 0);
            this.payAmount = Math.max(0, this.payTotalFee - this.payPaid);
            this.payMethod = student.payment_method || 'tunai';
            this.payModal = true;
        },
        get payRemaining() {
            return Math.max(0, this.payTotalFee - this.payPaid);
        },
        get payAfter() {
            return Math.min(this.payTotalFee, this.payPaid + parseFloat(this.payAmount || 0));
        },
        editData: {},
        gradData: {},
        openEdit(student) {
            this.editData = JSON.parse(JSON.stringify(student));
            this.editModal = true;
        },
        openGraduate(student) {
            this.gradData = {
                student_id: student.id,
                student_name: student.full_name,
                student_no: student.student_no,
                period_name: student.period_name || '',
                program_title: '',
                program_code: '',
                grade: 'GOOD'
            };
            try {
                let progs = JSON.parse(student.programs_json || '[]');
                if (progs.length > 0) {
                    this.gradData.program_title = progs[0].title || '';
                    this.gradData.program_code = progs[0].code || '';
                }
            } catch(e){}
            this.graduateModal = true;
        }
    };
}
</script>

<div x-data="studentsManager()" class="space-y-6">

    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2">
                <span>Master Data Siswa</span>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-extrabold uppercase">Aktif & Akademik</span>
            </h1>
            <p class="text-sm text-slate-500 mt-0.5">Kelola data siswa aktif, pemantauan program, dan kelulusan sertifikasi kursus.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <?php
            $reportParams = [];
            if ($currentStatus !== 'all') $reportParams['status'] = $currentStatus;
            if ($currentPeriod)           $reportParams['period'] = $currentPeriod;
            if ($search)                  $reportParams['search'] = $search;
            if (!empty($startDate))       $reportParams['start_date'] = $startDate;
            if (!empty($endDate))         $reportParams['end_date'] = $endDate;
            $reportQuery = http_build_query($reportParams);
            $reportUrl = url('admin/reports/students' . ($reportQuery ? '?' . $reportQuery : ''));
            ?>
            <a href="<?= $reportUrl ?>" target="_blank" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs flex items-center gap-2 shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Cetak Laporan</span>
            </a>
            <button @click="regModal = true" class="px-4 py-2.5 rounded-xl bg-pepsi-blue hover:bg-pepsi-blue-hover text-white font-bold text-xs flex items-center gap-2 shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                <span>Pendaftaran</span>
            </button>
            <a href="<?= url('admin/students/graduates') ?>" class="px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs flex items-center gap-2 shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                <span>Lihat Siswa Lulus (<?= $gradStudents ?>)</span>
            </a>
            <!-- Tombol Tambah Siswa disembunyikan: pendaftaran siswa dilakukan via menu Pendaftaran -->
            <?php /* ?>
            <button @click="addModal = true" class="px-4 py-2.5 rounded-xl bg-pepsi-blue hover:bg-pepsi-blue-hover text-white font-bold text-xs flex items-center gap-2 shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Tambah Siswa Baru</span>
            </button>
            <?php */ ?>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <!-- 1. Siswa Aktif -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Siswa Aktif</p>
                <p class="text-xl sm:text-2xl font-black text-emerald-600"><?= number_format($activeStudents) ?></p>
            </div>
        </div>

        <!-- 2. Siswa Daftar Hari Ini -->
        <a href="<?= url('admin/students?start_date=' . date('Y-m-d') . '&end_date=' . date('Y-m-d')) ?>"
           class="bg-white rounded-2xl p-4 sm:p-5 border <?= $isTodayFilter ? 'border-pepsi-blue ring-2 ring-pepsi-blue/20 bg-blue-50/20' : 'border-slate-200/80 hover:border-pepsi-blue/40' ?> shadow-sm flex items-center justify-between gap-3.5 transition group cursor-pointer"
           title="Klik untuk filter siswa daftar hari ini (<?= tanggalIndo(date('Y-m-d'), 'd F Y') ?>)">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-blue-50 text-pepsi-blue flex items-center justify-center shrink-0 group-hover:scale-105 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                </div>
                <div>
                    <div class="flex items-center gap-1.5">
                        <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Daftar Hari Ini</p>
                        <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse" title="Tanggal berjalan: <?= date('d/m/Y') ?>"></span>
                    </div>
                    <div class="flex items-baseline gap-2 mt-0.5">
                        <p class="text-xl sm:text-2xl font-black text-pepsi-blue"><?= number_format($todayStudents) ?></p>
                        <span class="text-[11px] font-semibold text-slate-400">Siswa</span>
                    </div>
                </div>
            </div>
            <span class="text-[11px] font-bold text-pepsi-blue opacity-0 group-hover:opacity-100 transition hidden sm:inline-flex items-center gap-1">
                Filter &rarr;
            </span>
        </a>

        <!-- 3. Total Program Terjual Hari Ini -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm flex items-center gap-3.5"
             title="Total unit program kursus yang terjual hari ini (siswa bisa ambil >1 program)">
            <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </div>
            <div>
                <div class="flex items-center gap-1.5">
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Program Hari Ini</p>
                    <span class="text-[9px] font-extrabold px-1.5 py-0.2 rounded bg-amber-100 text-amber-800">Terjual</span>
                </div>
                <div class="flex items-baseline gap-2 mt-0.5">
                    <p class="text-xl sm:text-2xl font-black text-amber-600"><?= number_format($todayPrograms) ?></p>
                    <span class="text-[11px] font-semibold text-slate-400">Program</span>
                </div>
            </div>
        </div>

        <!-- 4. Total Biaya Kursus -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Biaya Kursus</p>
                <p class="text-lg sm:text-xl font-black text-slate-900"><?= formatRupiah($totalRevenue) ?></p>
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm">
        <form method="GET" action="<?= url('admin/students') ?>" class="flex flex-col lg:flex-row lg:items-center gap-3 justify-between">
            <div class="flex flex-wrap items-center gap-2">
                <!-- Status Tabs -->
                <?php
                $tabQueryAll = http_build_query(array_filter([
                    'status'     => 'all',
                    'search'     => $search ?: null,
                    'period'     => $currentPeriod ?: null,
                    'start_date' => $startDate ?: null,
                    'end_date'   => $endDate ?: null,
                ]));
                $tabQueryGrad = http_build_query(array_filter([
                    'status'     => 'graduated',
                    'search'     => $search ?: null,
                    'period'     => $currentPeriod ?: null,
                    'start_date' => $startDate ?: null,
                    'end_date'   => $endDate ?: null,
                ]));
                ?>
                <a href="<?= url('admin/students?' . $tabQueryAll) ?>"
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition <?= $currentStatus === 'all' ? 'bg-pepsi-blue text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                    Semua (<?= $totalStudents ?>)
                </a>
                <a href="<?= url('admin/students?' . $tabQueryGrad) ?>"
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition <?= $currentStatus === 'graduated' ? 'bg-amber-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                    Lulus (<?= $gradStudents ?>)
                </a>
            </div>

            <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto">
                <input type="hidden" name="status" value="<?= sanitize($currentStatus) ?>">

                <!-- Date Range Filter -->
                <div class="flex items-center gap-1.5 bg-slate-50 px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs" title="Filter berdasarkan tanggal gabung siswa">
                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <input type="date" name="start_date" value="<?= sanitize($startDate ?? '') ?>" class="text-xs font-semibold text-slate-700 bg-transparent focus:outline-none" title="Tanggal Mulai">
                    <span class="text-slate-400 font-bold text-[11px]">s/d</span>
                    <input type="date" name="end_date" value="<?= sanitize($endDate ?? '') ?>" class="text-xs font-semibold text-slate-700 bg-transparent focus:outline-none" title="Tanggal Akhir">
                </div>

                <!-- Period filter -->
                <?php if (!empty($periods)): ?>
                <select name="period" class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-medium text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-pepsi-blue">
                    <option value="">-- Semua Periode --</option>
                    <?php foreach ($periods as $p): ?>
                        <option value="<?= sanitize($p) ?>" <?= $currentPeriod === $p ? 'selected' : '' ?>><?= sanitize($p) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>

                <!-- Search Input -->
                <div class="relative flex-1 sm:w-56 min-w-[160px]">
                    <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Cari nama, NIS, telp, kota..."
                           class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-pepsi-blue">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>

                <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    <span>Filter</span>
                </button>
                <?php if ($search || $currentPeriod || !empty($startDate) || !empty($endDate)): ?>
                    <a href="<?= url('admin/students' . ($currentStatus !== 'all' ? '?status=' . $currentStatus : '')) ?>" class="px-3 py-2 rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 text-xs font-bold transition flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span>Reset</span>
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <?php if (!empty($startDate) || !empty($endDate)): ?>
        <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap items-center gap-2 text-xs">
            <span class="text-slate-400 font-semibold text-[11px]">Filter tanggal aktif:</span>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-50 text-pepsi-blue border border-blue-100 font-bold text-[11px]">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span><?= !empty($startDate) ? date('d M Y', strtotime($startDate)) : 'Awal' ?> s/d <?= !empty($endDate) ? date('d M Y', strtotime($endDate)) : 'Sekarang' ?></span>
                <?php
                $clearDateQuery = http_build_query(array_filter([
                    'status' => $currentStatus !== 'all' ? $currentStatus : null,
                    'search' => $search ?: null,
                    'period' => $currentPeriod ?: null,
                ]));
                ?>
                <a href="<?= url('admin/students' . ($clearDateQuery ? '?' . $clearDateQuery : '')) ?>" class="ml-1 hover:text-red-600 font-black text-sm" title="Hapus filter tanggal">&times;</a>
            </span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Student Cards / Table Container -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-extrabold text-sm text-slate-800 flex items-center gap-2">
                <span>Daftar Siswa (<?= count($students) ?> Siswa · <?= $filteredProgramsCount ?> Total Program)</span>
            </h3>
            <span class="text-xs text-slate-400 font-medium">Klik nama siswa untuk kelola detail</span>
        </div>

        <?php if (empty($students)): ?>
            <div class="p-12 text-center">
                <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
                <h4 class="text-base font-bold text-slate-700">Belum Ada Data Siswa</h4>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Siswa akan otomatis masuk ke sini saat pendaftaran di-approve oleh Admin.</p>
            </div>
        <?php else: ?>
            <!-- Desktop Table View -->
            <div class="hidden lg:block overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50/80 text-slate-500 uppercase font-extrabold text-[10px] tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3 px-4">No. Siswa / Nama</th>
                            <th class="py-3 px-4">Kontak & Kota</th>
                            <th class="py-3 px-4">Program Kursus</th>
                            <th class="py-3 px-4">Periode & Jam</th>
                            <th class="py-3 px-4 text-right">Biaya / Bayar</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($students as $s): 
                            $progs = json_decode($s['programs_json'] ?? '[]', true) ?: [];
                            $gradCount = (int)($s['grad_count'] ?? 0);
                            $isKidStudentRow = false;
                            if (!empty($progs)) {
                                foreach ($progs as $pr) {
                                    $txt = strtoupper(($pr['title'] ?? '') . ' ' . ($pr['code'] ?? ''));
                                    if (str_contains($txt, 'KID')) { $isKidStudentRow = true; break; }
                                }
                            }
                            if (!$isKidStudentRow && !empty($s['period_name']) && str_contains(strtoupper($s['period_name']), 'KID')) {
                                $isKidStudentRow = true;
                            }
                        ?>
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3 px-4">
                                    <div class="font-extrabold text-slate-800 text-sm"><?= sanitize($s['full_name']) ?></div>
                                    <div class="font-mono text-[11px] text-pepsi-blue font-bold flex items-center gap-1.5 mt-0.5">
                                        <span><?= sanitize($s['student_no']) ?></span>
                                        <?php if ($s['gender']): ?>
                                            <span class="text-[10px] font-semibold px-1 rounded bg-slate-100 text-slate-500"><?= $s['gender'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">Gabung: <?= date('d M Y', strtotime($s['enrolled_at'])) ?></div>
                                </td>
                                <td class="py-3 px-4">
                                    <a href="https://wa.me/<?= preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $s['phone'])) ?>" target="_blank"
                                       class="inline-flex items-center gap-1 text-emerald-600 hover:text-emerald-700 font-bold">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                        <span><?= sanitize($s['phone']) ?></span>
                                    </a>
                                    <div class="text-[11px] text-slate-500 mt-0.5"><?= sanitize($s['city'] ?: 'Kota -') ?></div>
                                </td>
                                <td class="py-3 px-4 max-w-xs">
                                    <div class="flex flex-wrap items-center gap-1">
                                        <?php if (!empty($progs)): ?>
                                            <?php foreach ($progs as $p): ?>
                                                <span class="inline-block px-2 py-0.5 rounded-md bg-blue-50 text-pepsi-blue border border-blue-100 font-semibold text-[10px]">
                                                    <?= sanitize($p['title'] ?? $p['code'] ?? 'Program') ?>
                                                </span>
                                            <?php endforeach; ?>
                                            <?php if (count($progs) > 1): ?>
                                                <span class="inline-block px-1.5 py-0.2 rounded-md bg-indigo-100 text-indigo-700 font-extrabold text-[9px]" title="Siswa mengambil <?= count($progs) ?> program kursus">
                                                    <?= count($progs) ?> Program
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-slate-400 text-[11px]">-</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <?php if ($isKidStudentRow && (empty($s['period_name']) || str_contains(strtoupper($s['period_name']), 'KID'))): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-50 text-amber-800 border border-amber-200 font-bold text-[10px]">
                                            <span>🧒</span> Kids (Tanpa Periode)
                                        </span>
                                    <?php else: ?>
                                        <div class="font-bold text-slate-700 text-[11px]"><?= sanitize($s['period_name'] ?: '-') ?></div>
                                    <?php endif; ?>
                                    <?php
                                        // Cek apakah ada time_slot per program di programs_json
                                        $progsWithSlot = array_filter($progs, fn($p) => !empty($p['time_slot']));
                                        if (!empty($progsWithSlot)):
                                    ?>
                                        <div class="mt-0.5 flex flex-col gap-0.5">
                                            <?php foreach ($progsWithSlot as $ps): ?>
                                                <div class="inline-flex items-center gap-1 text-[10px] text-slate-600">
                                                    <span class="font-semibold text-pepsi-blue text-[9px] truncate max-w-[70px]"><?= sanitize($ps['title'] ?? '') ?></span>
                                                    <span class="text-slate-400">·</span>
                                                    <span class="font-medium"><?= sanitize($ps['time_slot']) ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-[10px] text-slate-500 font-medium mt-0.5"><?= sanitize($s['time_slot'] ?: '-') ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="font-extrabold text-slate-800"><?= formatRupiah($s['total_fee']) ?></div>
                                    <?php if ($s['paid_amount'] > 0 && $s['paid_amount'] < $s['total_fee']): ?>
                                        <div class="text-[10px] text-emerald-600 font-bold">Bayar: <?= formatRupiah($s['paid_amount']) ?></div>
                                        <div class="text-[10px] text-red-500 font-bold">Kurang: <?= formatRupiah($s['total_fee'] - $s['paid_amount']) ?></div>
                                    <?php elseif ($s['paid_amount'] >= $s['total_fee'] && $s['total_fee'] > 0): ?>
                                        <div class="text-[10px] text-emerald-600 font-bold">Lunas 100%</div>
                                    <?php else: ?>
                                        <div class="text-[10px] text-amber-600 font-bold">Belum Ada Bayar</div>
                                    <?php endif; ?>
                                    <?php if ($s['paid_amount'] > 0): ?>
                                        <div class="text-[10px] text-slate-400 font-semibold mt-0.5">via <?= ucfirst($s['payment_method'] ?? 'tunai') ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <?php if ($s['status'] === 'active'): ?>
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-700 border border-emerald-200">
                                            Aktif Belajar
                                        </span>
                                    <?php elseif ($s['status'] === 'graduated'): ?>
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-700 border border-amber-200">
                                            Lulus (<?= $gradCount ?> Sertifikat)
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-200">
                                            Drop Out
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <?php if ($s['payment_status'] !== 'paid'): ?>
                                        <!-- Bayar / Catat Pembayaran -->
                                        <button @click="openPay(<?= htmlspecialchars(json_encode($s)) ?>)"
                                                title="Catat Pembayaran (Bayar)"
                                                class="p-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition relative">
                                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse absolute top-0.5 right-0.5"></span>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm8-7V5a2 2 0 00-2-2H9a2 2 0 00-2 2v3h10z"/></svg>
                                        </button>
                                        <?php endif; ?>
                                        <!-- Cetak Kwitansi -->
                                        <a href="<?= url('admin/students/receipt/' . $s['id']) ?>" target="_blank"
                                           title="Cetak Kwitansi / e-Nota Pembayaran"
                                           class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        </a>

                                        <!-- Graduate & Issue Cert -->
                                        <button @click="openGraduate(<?= htmlspecialchars(json_encode($s)) ?>)"
                                                title="Luluskan Program & Terbitkan Sertifikat"
                                                class="p-1.5 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-500 hover:text-white transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                                        </button>

                                        <!-- Edit -->
                                        <button @click="openEdit(<?= htmlspecialchars(json_encode($s)) ?>)"
                                                title="Edit Data Siswa"
                                                class="p-1.5 rounded-lg bg-slate-100 text-slate-600 hover:bg-pepsi-blue hover:text-white transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>

                                        <!-- Delete -->
                                        <form method="POST" action="<?= url('admin/students/delete/' . $s['id']) ?>" onsubmit="return confirm('Yakin hapus data siswa <?= addslashes($s['full_name']) ?>?')">
                                            <button type="submit" title="Hapus Siswa" class="p-1.5 rounded-lg bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition">
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
                <?php foreach ($students as $s): 
                    $progs = json_decode($s['programs_json'] ?? '[]', true) ?: [];
                    $gradCount = (int)($s['grad_count'] ?? 0);
                ?>
                    <div class="p-4 space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="font-mono text-[10px] text-pepsi-blue font-bold"><?= sanitize($s['student_no']) ?></span>
                                <h4 class="font-extrabold text-slate-900 text-sm leading-tight"><?= sanitize($s['full_name']) ?></h4>
                                <div class="text-[11px] text-slate-500 mt-0.5 flex items-center gap-2">
                                    <span><?= sanitize($s['city'] ?: 'Kota -') ?></span>
                                    <span>&bull;</span>
                                    <a href="https://wa.me/<?= preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $s['phone'])) ?>" target="_blank" class="text-emerald-600 font-bold">
                                        <?= sanitize($s['phone']) ?>
                                    </a>
                                </div>
                            </div>
                            <?php if ($s['status'] === 'active'): ?>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-700">Aktif</span>
                            <?php elseif ($s['status'] === 'graduated'): ?>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-700">Lulus</span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-red-100 text-red-700">Drop</span>
                            <?php endif; ?>
                        </div>

                        <!-- Programs badge -->
                        <div class="flex flex-wrap items-center gap-1">
                            <?php foreach ($progs as $p): ?>
                                <span class="px-2 py-0.5 rounded bg-blue-50 text-pepsi-blue border border-blue-100 text-[10px] font-bold">
                                    <?= sanitize($p['title'] ?? $p['code'] ?? '') ?>
                                </span>
                            <?php endforeach; ?>
                            <?php if (count($progs) > 1): ?>
                                <span class="px-1.5 py-0.2 rounded-md bg-indigo-100 text-indigo-700 font-extrabold text-[9px]">
                                    <?= count($progs) ?> Program
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Details Grid -->
                        <div class="grid grid-cols-2 gap-2 text-[11px] bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                            <div>
                                <span class="text-slate-400 block text-[9px] uppercase font-bold">Periode & Jam</span>
                                <?php if ($isKidStudentRow && (empty($s['period_name']) || str_contains(strtoupper($s['period_name']), 'KID'))): ?>
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-200 font-bold text-[9px]">
                                        <span>🧒</span> Kids (Tanpa Periode)
                                    </span>
                                <?php else: ?>
                                    <span class="font-bold text-slate-700 text-[10px]"><?= sanitize($s['period_name'] ?: '-') ?></span>
                                <?php endif; ?>
                                <?php
                                    $progsWithSlot2 = array_filter($progs, fn($p) => !empty($p['time_slot']));
                                    if (!empty($progsWithSlot2)):
                                ?>
                                    <?php foreach ($progsWithSlot2 as $ps2): ?>
                                        <span class="text-slate-500 block text-[10px]">
                                            <span class="text-pepsi-blue font-semibold text-[9px]"><?= sanitize($ps2['title'] ?? '') ?></span>
                                            · <?= sanitize($ps2['time_slot']) ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-slate-500 block text-[10px]"><?= sanitize($s['time_slot'] ?: '-') ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="text-right">
                                <span class="text-slate-400 block text-[9px] uppercase font-bold">Biaya Kursus</span>
                                <span class="font-extrabold text-slate-800"><?= formatRupiah($s['total_fee']) ?></span>
                                <span class="block text-[10px] <?= $s['paid_amount'] >= $s['total_fee'] ? 'text-emerald-600 font-bold' : 'text-amber-600 font-bold' ?>">
                                    <?= $s['paid_amount'] >= $s['total_fee'] ? 'Lunas 100%' : 'Dibayar: ' . formatRupiah($s['paid_amount']) ?>
                                </span>
                                <?php if ($s['paid_amount'] > 0): ?>
                                    <span class="block text-[9px] text-slate-400 font-semibold">via <?= ucfirst($s['payment_method'] ?? 'tunai') ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Actions Mobile -->
                        <div class="flex items-center gap-2 pt-1">
                            <?php if ($s['payment_status'] !== 'paid'): ?>
                            <button @click="openPay(<?= htmlspecialchars(json_encode($s)) ?>)"
                                    class="flex-1 py-2 rounded-xl bg-emerald-600 text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-sm">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm8-7V5a2 2 0 00-2-2H9a2 2 0 00-2 2v3h10z"/></svg>
                                <span>Bayar</span>
                            </button>
                            <?php endif; ?>
                            <a href="<?= url('admin/students/receipt/' . $s['id']) ?>" target="_blank"
                               class="px-2.5 py-2 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white font-bold text-xs flex items-center justify-center gap-1 transition shadow-xs"
                               title="Cetak Kwitansi">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span>Kwitansi</span>
                            </a>
                            <button @click="openGraduate(<?= htmlspecialchars(json_encode($s)) ?>)"
                                    class="flex-1 py-2 rounded-xl bg-amber-500 text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-sm">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138z"/></svg>
                                <span>Luluskan & Sertifikat</span>
                            </button>
                            <button @click="openEdit(<?= htmlspecialchars(json_encode($s)) ?>)"
                                    class="px-3 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs hover:bg-slate-200">
                                Edit
                            </button>
                            <form method="POST" action="<?= url('admin/students/delete/' . $s['id']) ?>" onsubmit="return confirm('Hapus siswa <?= addslashes($s['full_name']) ?>?')">
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
         MODAL 1: TAMBAH SISWA MANUAL
         ───────────────────────────────────────────────────────────── -->
    <div x-show="addModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="addModal = false"></div>
        <div class="min-h-screen px-4 flex items-center justify-center p-4">
            <div class="relative bg-white rounded-3xl max-w-xl w-full p-6 shadow-2xl border border-slate-100 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-base font-black text-slate-800 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-xl bg-pepsi-blue/10 text-pepsi-blue flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        </span>
                        <span>Tambah Data Siswa Baru</span>
                    </h3>
                    <button @click="addModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
                </div>

                <form method="POST" action="<?= url('admin/students/store') ?>" class="space-y-4 text-xs">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Siswa *</label>
                            <input type="text" name="full_name" x-model="studentName" @input="checkRepeat()" required placeholder="Contoh: Muhammad Budi Santoso"
                                   class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-pepsi-blue focus:outline-none font-bold">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">No. WhatsApp / HP *</label>
                            <input type="text" name="phone" x-model="studentPhone" @input="checkRepeat()" required placeholder="Contoh: 081234567890"
                                   class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-pepsi-blue focus:outline-none font-bold font-mono">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Kota / Domisili</label>
                            <input type="text" name="city" placeholder="Contoh: Kediri, Jawa Timur"
                                   class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-pepsi-blue focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Jenis Kelamin</label>
                            <select name="gender" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-pepsi-blue focus:outline-none">
                                <option value="">- Pilih -</option>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Tanggal Mulai Belajar</label>
                            <input type="date" name="enrolled_at" value="<?= date('Y-m-d') ?>"
                                   class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-pepsi-blue focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">
                                Periode Kursus
                                <span x-show="isKidsSelected" class="text-amber-600 text-[10px] font-bold ml-1">(Kids: Otomatis Tanpa Periode)</span>
                            </label>
                            <input type="hidden" name="period_name" value="" :disabled="!isKidsSelected">
                            <input type="text" name="period_name"
                                   :disabled="isKidsSelected"
                                   :placeholder="isKidsSelected ? 'Tanpa Periode (Program Kids Fleksibel)' : 'Contoh: Periode 10 Oktober 2026'"
                                   :class="isKidsSelected ? 'bg-slate-100 text-slate-400 cursor-not-allowed border-slate-200 opacity-60' : 'border-slate-200'"
                                   class="w-full px-3 py-2.5 rounded-xl border focus:ring-2 focus:ring-pepsi-blue focus:outline-none transition">
                            <div x-show="isKidsSelected" x-cloak class="mt-1 flex items-center gap-1.5 text-[11px] font-bold text-amber-800 bg-amber-50 border border-amber-200 px-2 py-1 rounded-lg">
                                <span>🧒</span>
                                <span>Program Kids: Fleksibel tanpa sistem periode.</span>
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Pilihan Sesi / Jam Kelas</label>
                            <select name="time_slot"
                                    class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-pepsi-blue focus:outline-none font-medium">
                                <option value="">-- Pilih Sesi Jam --</option>
                                <?php foreach ($timeSlots as $ts): ?>
                                    <option value="<?= sanitize($ts) ?>"><?= sanitize($ts) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Program Selection -->
                    <?php 
                        $allProgs = (new \App\Models\Program())->getActivePrograms();
                    ?>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1.5">Pilih Program Kursus yang Diikuti</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-40 overflow-y-auto p-2 border border-slate-200 rounded-xl bg-slate-50">
                            <?php foreach ($allProgs as $ap): ?>
                                <label class="flex items-center gap-2 p-2 rounded-lg bg-white border border-slate-200 hover:border-pepsi-blue cursor-pointer">
                                    <input type="checkbox" name="program_ids[]" value="<?= $ap['id'] ?>" x-model="regPrograms" @change="checkRepeat()" class="rounded text-pepsi-blue focus:ring-pepsi-blue">
                                    <div class="leading-tight">
                                        <span class="font-bold text-slate-800 text-[11px] block"><?= sanitize($ap['title']) ?></span>
                                        <span class="text-[10px] text-slate-500 font-semibold"><?= formatRupiah($ap['price']) ?></span>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Warning Siswa Pernah Mengambil Program yang Sama -->
                    <div x-show="repeatWarning.show" x-cloak class="p-3.5 rounded-2xl bg-red-50 border-2 border-red-300 text-red-900 space-y-2 animate-pulse-once">
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

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Total Biaya Kursus (Rp)</label>
                            <input type="number" name="total_fee" value="0" placeholder="0 jika otomatis dari program"
                                   class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-pepsi-blue focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Sudah Dibayar (Rp)</label>
                            <input type="number" name="paid_amount" value="0"
                                   class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-pepsi-blue focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Metode Pembayaran</label>
                            <select name="payment_method" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-pepsi-blue focus:outline-none">
                                <option value="tunai">💵 Tunai &amp; Cash</option>
                                <option value="transfer">🏦 Transfer Bank</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Catatan Tambahan</label>
                        <textarea name="notes" rows="2" placeholder="Catatan siswa, background pendidikan, dll..."
                                  class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-pepsi-blue focus:outline-none"></textarea>
                    </div>

                    <!-- Print Receipt Checkbox Option -->
                    <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200/80 flex items-center justify-between">
                        <label class="flex items-center gap-2.5 cursor-pointer text-xs font-bold text-emerald-900 select-none">
                            <input type="checkbox" name="print_receipt" value="1" checked class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                            <span>Langsung cetak kwitansi / e-Nota setelah simpan</span>
                        </label>
                        <span class="text-[10px] text-emerald-700 font-extrabold bg-emerald-100 px-2 py-0.5 rounded-full uppercase">e-Nota</span>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                        <button type="button" @click="addModal = false" class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-600 font-bold hover:bg-slate-200 transition">
                            Batal
                        </button>
                        <button type="submit" name="action" value="save" class="px-4 py-2.5 rounded-xl bg-slate-800 text-white font-bold hover:bg-slate-900 transition">
                            Simpan Saja
                        </button>
                        <button type="submit" name="action" value="save_and_print" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold transition shadow-sm flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            <span>Simpan &amp; Cetak Kwitansi</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ─────────────────────────────────────────────────────────────
         MODAL 2: EDIT SISWA
         ───────────────────────────────────────────────────────────── -->
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="editModal = false"></div>
        <div class="min-h-screen px-4 flex items-center justify-center p-4">
            <div class="relative bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-base font-black text-slate-800">Edit Data Siswa</h3>
                        <p class="text-xs text-slate-500 font-mono" x-text="editData.student_no"></p>
                    </div>
                    <button @click="editModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
                </div>

                <form :action="'<?= url('admin/students/update/') ?>' + editData.id" method="POST" class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Lengkap</label>
                        <input type="text" name="full_name" x-model="editData.full_name" required
                               class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-pepsi-blue focus:outline-none">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">No. WhatsApp</label>
                            <input type="text" name="phone" x-model="editData.phone" required
                                   class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-pepsi-blue focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Kota</label>
                            <input type="text" name="city" x-model="editData.city"
                                   class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-pepsi-blue focus:outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Status Akademik</label>
                            <select name="status" x-model="editData.status"
                                    class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-pepsi-blue focus:outline-none font-bold">
                                <option value="active">Aktif Belajar</option>
                                <option value="graduated">Sudah Lulus</option>
                                <option value="dropped">Drop Out / Mundur</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nominal Terbayar (Rp)</label>
                            <input type="number" name="paid_amount" x-model="editData.paid_amount"
                                   class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-pepsi-blue focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Metode Pembayaran</label>
                        <select name="payment_method" x-model="editData.payment_method"
                                class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-pepsi-blue focus:outline-none font-bold">
                            <option value="tunai">💵 Tunai &amp; Cash</option>
                            <option value="transfer">🏦 Transfer Bank</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Sesi / Jam Kelas</label>
                        <select name="time_slot" x-model="editData.time_slot"
                                class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-pepsi-blue focus:outline-none font-medium">
                            <option value="">-- Pilih Sesi Jam --</option>
                            <?php foreach ($timeSlots as $ts): ?>
                                <option value="<?= sanitize($ts) ?>"><?= sanitize($ts) ?></option>
                            <?php endforeach; ?>
                            <?php
                                // Jika time_slot siswa tidak ada di list, tampilkan sebagai opsi tambahan via JS
                            ?>
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1">Sesi jam sesuai jadwal yang aktif</p>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Catatan</label>
                        <textarea name="notes" x-model="editData.notes" rows="2"
                                  class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-pepsi-blue focus:outline-none"></textarea>
                    </div>

                    <div class="flex items-center justify-between gap-2 pt-2 border-t border-slate-100">
                        <a :href="'<?= url('admin/students/receipt/') ?>' + editData.id" target="_blank"
                           class="px-3.5 py-2 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-bold text-xs flex items-center gap-1.5 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Cetak Kwitansi</span>
                        </a>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="editModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-600 font-bold">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2 rounded-xl bg-pepsi-blue text-white font-bold hover:bg-pepsi-blue-hover transition shadow-sm">
                                Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ─────────────────────────────────────────────────────────────
         MODAL 3: QUICK GRADUATE & ISSUE CERTIFICATE
         ───────────────────────────────────────────────────────────── -->
    <div x-show="graduateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="graduateModal = false"></div>
        <div class="min-h-screen px-4 flex items-center justify-center p-4">
            <div class="relative bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-black text-slate-800">Luluskan Siswa & Cetak Sertifikat</h3>
                            <p class="text-xs text-slate-500">Terbitkan nomor sertifikat resmi kursus</p>
                        </div>
                    </div>
                    <button @click="graduateModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
                </div>

                <form method="POST" action="<?= url('admin/students/issue-cert') ?>" class="space-y-3.5 text-xs">
                    <input type="hidden" name="student_id" :value="gradData.student_id">

                    <!-- Student Info Box -->
                    <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80">
                        <span class="text-[10px] text-slate-400 font-bold uppercase block">Penerima Sertifikat</span>
                        <div class="font-extrabold text-slate-800 text-sm" x-text="gradData.student_name"></div>
                        <div class="font-mono text-pepsi-blue text-[11px] font-bold" x-text="gradData.student_no"></div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Program yang Diselesaikan *</label>
                        <input type="text" name="program_title" x-model="gradData.program_title" required
                               placeholder="Contoh: English for Speaking - Level 1"
                               class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-amber-500 focus:outline-none font-semibold">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Kode Program (Opsional)</label>
                            <input type="text" name="program_code" x-model="gradData.program_code" placeholder="Misal: ENG-SPK-1"
                                   class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Periode Belajar</label>
                            <input type="text" name="period_name" x-model="gradData.period_name" placeholder="Periode Oktober 2026"
                                   class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Tanggal Kelulusan</label>
                            <input type="date" name="graduated_at" value="<?= date('Y-m-d') ?>"
                                   class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nilai Ujian Akhir *</label>
                            <select name="grade" x-model="gradData.grade" required
                                    class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-amber-500 focus:outline-none font-bold text-slate-800">
                                <option value="EXCELLENT">EXCELLENT</option>
                                <option value="VERY GOOD">VERY GOOD</option>
                                <option value="GOOD">GOOD</option>
                                <option value="AVERAGE">AVERAGE</option>
                                <option value="POOR">POOR</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                        <button type="button" @click="graduateModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-600 font-bold">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold transition shadow-sm flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Terbitkan Sertifikat</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ─────────────────────────────────────────────────────────────
         MODAL 4: CATAT PEMBAYARAN (BAYAR)
         ───────────────────────────────────────────────────────────── -->
    <div x-show="payModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="payModal = false"></div>
        <div class="min-h-screen px-4 flex items-center justify-center p-4">
            <div class="relative bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm8-7V5a2 2 0 00-2-2H9a2 2 0 00-2 2v3h10z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-black text-slate-800">Catat Pembayaran</h3>
                            <p class="text-xs text-slate-500">Terima pembayaran biaya kursus</p>
                        </div>
                    </div>
                    <button @click="payModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
                </div>

                <form :action="'<?= url('admin/students/pay/') ?>' + payData.id" method="POST" class="space-y-3.5 text-xs">
                    <input type="hidden" name="student_id" :value="payData.id">

                    <!-- Student Info -->
                    <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80">
                        <span class="text-[10px] text-slate-400 font-bold uppercase block">Pembayar</span>
                        <div class="font-extrabold text-slate-800 text-sm" x-text="payData.full_name"></div>
                        <div class="font-mono text-pepsi-blue text-[11px] font-bold" x-text="payData.student_no"></div>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80">
                            <span class="text-[10px] text-slate-400 font-bold block">Total Tagihan</span>
                            <span class="font-black text-slate-800" x-text="'Rp ' + payTotalFee.toLocaleString('id-ID')"></span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-emerald-50 border border-emerald-200/80">
                            <span class="text-[10px] text-emerald-600 font-bold block">Sudah Bayar</span>
                            <span class="font-black text-emerald-700" x-text="'Rp ' + payPaid.toLocaleString('id-ID')"></span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-red-50 border border-red-200/80">
                            <span class="text-[10px] text-red-500 font-bold block">Sisa</span>
                            <span class="font-black text-pepsi-red" x-text="'Rp ' + payRemaining.toLocaleString('id-ID')"></span>
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
                        <span class="text-[10px] text-slate-400 mt-1 block">
                            Total setelah pembayaran: <b class="text-emerald-600" x-text="'Rp ' + payAfter.toLocaleString('id-ID')"></b>
                            <template x-if="payAfter >= payTotalFee && payTotalFee > 0">
                                <span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700 font-black ml-1 uppercase">Lunas</span>
                            </template>
                        </span>
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
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Simpan Pembayaran</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL PENDAFTARAN SISWA (Direct Admin - langsung Disetujui & masuk Master Data) -->
    <div x-show="regModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="regModal = false" class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-8 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="font-heading font-black text-xl text-pepsi-navy">Daftarkan Siswa Baru (Direct Admin)</h3>
                    <p class="text-xs text-slate-400">Input pendaftaran siswa offline atau via telepon langsung ke sistem.</p>
                </div>
                <button @click="regModal = false" class="text-slate-400 hover:text-slate-700">&times;</button>
            </div>

            <form action="<?= url('admin/registrations/store') ?>" method="POST" class="space-y-4 text-xs">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Siswa *</label>
                        <input type="text" name="student_name" x-model="studentName" @input.debounce.350ms="checkRepeat()" required placeholder="Contoh: Rian Anggara" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nomor WhatsApp Siswa *</label>
                        <input type="tel" name="phone" x-model="studentPhone" @input.debounce.350ms="checkRepeat()" required placeholder="081234567890" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold font-mono">
                    </div>
                </div>

                <!-- Box Peringatan Program Sama -->
                <div x-show="repeatWarning.show" x-cloak class="p-3.5 rounded-2xl bg-amber-50 border-2 border-amber-400 text-amber-950 space-y-2 shadow-sm animate-pulse">
                    <div class="flex items-center gap-2 font-black text-amber-900 text-xs">
                        <span class="text-lg">⚠️</span>
                        <span class="uppercase tracking-wide">PERINGATAN: SISWA SUDAH PERNAH MENGAMBIL PROGRAM KELAS INI!</span>
                    </div>
                    <div class="text-[11px] font-semibold text-amber-900 pl-6 leading-relaxed" x-html="repeatWarning.message"></div>
                    <div class="pt-2 border-t border-amber-300/80 pl-6 flex items-center justify-between">
                        <label class="flex items-center gap-2 text-[11px] font-bold text-amber-950 cursor-pointer select-none">
                            <input type="checkbox" name="allow_repeat_override" value="1" class="rounded text-amber-600 focus:ring-amber-500 w-4 h-4">
                            <span>Tetap izinkan pendaftaran ini (Dispensasi Khusus Pimpinan Lembaga)</span>
                        </label>
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
                            <?php foreach ($regPeriods as $per): ?>
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
                    <button type="button" @click="regModal = false" class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
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

</div>
