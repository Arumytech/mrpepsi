<?php
// Admin Payroll - Manual CRUD Penggajian Tutor & Karyawan
$payMethods = [
    'tunai'    => ['label' => 'Tunai', 'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
    'transfer' => ['label' => 'Transfer', 'badge' => 'bg-blue-50 text-blue-700 border-blue-200'],
    'qris'     => ['label' => 'QRIS', 'badge' => 'bg-purple-50 text-purple-700 border-purple-200'],
];
?>
<div x-data="{ 
    tutorModal: false, 
    staffModal: false, 
    editModal: false, 
    editItem: {},
    tutorForm: { tutor_id: '', name: '', role: '', is_kids: false, basic: 0, bonus: 0, deduction: 0 },
    staffForm: { staff_id: '', name: '', role: '', salary_month: '<?= date('Y-m') ?>', basic: 0, bonus: 0, overtime: 0, deduction: 0 },
    
    onSelectTutor(id, name, spec, fee) {
        this.tutorForm.tutor_id = id;
        this.tutorForm.name = name;
        this.tutorForm.role = spec || 'Coach / Tutor';
        this.tutorForm.basic = fee ? parseFloat(fee) : 0;
    },
    onSelectStaff(id, name, pos, salary) {
        this.staffForm.staff_id = id;
        this.staffForm.name = name;
        this.staffForm.role = pos || 'Staff Office';
        this.staffForm.basic = salary ? parseFloat(salary) : 0;
    },
    openEdit(item) {
        this.editItem = { ...item, is_kids: Boolean(parseInt(item.is_kids || 0)) };
        this.editModal = true;
    }
}" class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-pink-100 text-pink-700">
                    Sistem Penggajian Manual
                </span>
                <span class="text-xs text-slate-400">&bull; Terintegrasi Laporan Pengeluaran</span>
            </div>
            <h1 class="font-heading font-black text-2xl sm:text-3xl text-pepsi-navy mt-1">Penggajian (Payroll) & Slip Gaji</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Input nominal gaji, reward/bonus, dan lembur secara manual dan fleksibel untuk <b>Tutor</b> maupun <b>Karyawan</b> tanpa terikat absensi.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Cetak Laporan / Tanda Terima -->
            <?php
            $reportQueryParams = [];
            if (!empty($selectedPeriodId)) $reportQueryParams['period_id'] = $selectedPeriodId;
            if (!empty($payrollType) && in_array($payrollType, ['tutor', 'kids', 'staff'])) $reportQueryParams['type'] = $payrollType;
            $reportLinkUrl = url('admin/payroll/report' . (!empty($reportQueryParams) ? '?' . http_build_query($reportQueryParams) : ''));
            ?>
            <a href="<?= $reportLinkUrl ?>" 
               target="_blank" 
               class="bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs sm:text-sm px-4 py-2.5 rounded-2xl shadow-sm flex items-center gap-2 transition">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Cetak Rekap Tanda Terima</span>
            </a>

            <!-- Tombol Tambah Gaji Tutor -->
            <button @click="tutorModal = true" class="bg-pepsi-blue hover:bg-blue-900 text-white font-bold text-xs sm:text-sm px-4 py-2.5 rounded-2xl shadow-md flex items-center gap-2 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>+ Gaji Tutor</span>
            </button>

            <!-- Tombol Tambah Gaji Karyawan -->
            <button @click="staffModal = true" class="bg-pepsi-red hover:bg-red-800 text-white font-bold text-xs sm:text-sm px-4 py-2.5 rounded-2xl shadow-md flex items-center gap-2 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                <span>+ Gaji Karyawan</span>
            </button>
        </div>
    </div>

    <!-- Ringkasan Statistik Beban Gaji -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Grand Total -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Beban Gaji</span>
                <span class="w-8 h-8 rounded-xl bg-red-50 text-pepsi-red grid place-items-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </span>
            </div>
            <span class="font-heading font-black text-2xl text-pepsi-red mt-2 block"><?= formatRupiah($summary['grand_total']) ?></span>
            <div class="text-[11px] text-slate-500 mt-1 flex items-center justify-between">
                <span><?= (int)$summary['total_count'] ?> Penerima Pembayaran</span>
                <span class="font-semibold text-emerald-600">Masuk Pengeluaran</span>
            </div>
        </div>

        <!-- Total Tutor -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Honor / Gaji Tutor</span>
                <span class="w-8 h-8 rounded-xl bg-blue-50 text-pepsi-blue grid place-items-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </span>
            </div>
            <span class="font-heading font-black text-2xl text-pepsi-blue mt-2 block"><?= formatRupiah($summary['tutor_total']) ?></span>
            <span class="text-[11px] text-slate-500 mt-1 block"><?= (int)$summary['tutor_count'] ?> Coach / Pengajar</span>
        </div>

        <!-- Total Karyawan -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Gaji Staff / Karyawan</span>
                <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 grid place-items-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </span>
            </div>
            <span class="font-heading font-black text-2xl text-slate-900 mt-2 block"><?= formatRupiah($summary['staff_total']) ?></span>
            <span class="text-[11px] text-slate-500 mt-1 block"><?= (int)$summary['staff_count'] ?> Karyawan Kantor</span>
        </div>

        <!-- Bonus & Lembur -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Reward, Bonus & Lembur</span>
                <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 grid place-items-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <span class="font-heading font-black text-2xl text-amber-600 mt-2 block"><?= formatRupiah($summary['total_bonus'] + $summary['total_overtime']) ?></span>
            <div class="text-[11px] text-slate-500 mt-1 flex items-center justify-between">
                <span>Bonus: <?= formatRupiah($summary['total_bonus']) ?></span>
                <span>Lembur: <?= formatRupiah($summary['total_overtime']) ?></span>
            </div>
        </div>
    </div>

    <!-- Filter & Navigasi Tab -->
    <div class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-200 shadow-sm space-y-4">
        <form method="GET" action="<?= url('admin/payroll') ?>" class="flex flex-wrap items-center justify-between gap-3">
            
            <!-- Quick Filter Tabs -->
            <div class="flex items-center gap-1.5 p-1 bg-slate-100 rounded-2xl">
                <a href="<?= url('admin/payroll?payroll_type=all' . ($selectedPeriodId ? '&period_id=' . $selectedPeriodId : '')) ?>" 
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition <?= $payrollType === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900' ?>">
                    Semua (<?= (int)$summary['total_count'] ?>)
                </a>
                <a href="<?= url('admin/payroll?payroll_type=tutor' . ($selectedPeriodId ? '&period_id=' . $selectedPeriodId : '')) ?>" 
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition <?= $payrollType === 'tutor' ? 'bg-pepsi-blue text-white shadow-sm' : 'text-slate-500 hover:text-slate-900' ?>">
                    Tutor / Coach (<?= (int)$summary['tutor_count'] ?>)
                </a>
                <a href="<?= url('admin/payroll?payroll_type=kids' . ($selectedPeriodId ? '&period_id=' . $selectedPeriodId : '')) ?>" 
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition <?= $payrollType === 'kids' ? 'bg-amber-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-900' ?>">
                    Kids &amp; Non-Kelas (<?= (int)($summary['kids_count'] ?? 0) ?>)
                </a>
                <a href="<?= url('admin/payroll?payroll_type=staff' . ($selectedPeriodId ? '&period_id=' . $selectedPeriodId : '')) ?>" 
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition <?= $payrollType === 'staff' ? 'bg-pepsi-red text-white shadow-sm' : 'text-slate-500 hover:text-slate-900' ?>">
                    Karyawan (<?= (int)$summary['staff_count'] ?>)
                </a>
            </div>

            <!-- Periode & Tanggal Filter -->
            <div class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="payroll_type" value="<?= sanitize($payrollType) ?>">

                <select name="period_id" onchange="this.form.submit()" class="px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-pepsi-blue">
                    <option value="">-- Semua Periode --</option>
                    <?php foreach ($periods as $per): ?>
                        <option value="<?= $per['id'] ?>" <?= $selectedPeriodId == $per['id'] ? 'selected' : '' ?>>
                            Periode: <?= sanitize($per['name']) ?> (<?= sanitize(tanggalIndo($per['start_date'], 'd M')) ?> - <?= sanitize(tanggalIndo($per['end_date'])) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="flex items-center gap-1.5 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200 text-xs">
                    <input type="date" name="start_date" value="<?= sanitize($startDate) ?>" class="font-bold text-slate-800 bg-transparent focus:outline-none" title="Tanggal Mulai">
                    <span class="text-slate-400 font-bold">s/d</span>
                    <input type="date" name="end_date" value="<?= sanitize($endDate) ?>" class="font-bold text-slate-800 bg-transparent focus:outline-none" title="Tanggal Selesai">
                </div>

                <div class="relative">
                    <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Cari nama / catatan..." class="pl-8 pr-3 py-2 rounded-xl border border-slate-200 text-xs font-medium text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-pepsi-blue w-48 sm:w-56">
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>

                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition shadow-sm">
                    Filter
                </button>
                <?php if ($selectedPeriodId || $startDate || $endDate || $search || $payrollType !== 'all'): ?>
                    <a href="<?= url('admin/payroll') ?>" class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold transition">
                        Reset
                    </a>
                <?php endif; ?>
            </div>

        </form>
    </div>

    <!-- Data Table Penggajian -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="font-heading font-bold text-base text-slate-900">
                    Daftar Penggajian Manual
                </h3>
                <p class="text-xs text-slate-500">
                    Setiap data penggajian yang tersimpan otomatis disinkronkan ke buku laporan pengeluaran.
                </p>
            </div>
            <span class="text-xs font-bold text-slate-400 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-100">
                Menampilkan <?= count($payrolls) ?> data
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-medium">
                <thead class="bg-pepsi-navy text-white text-[11px] uppercase tracking-wider font-bold">
                    <tr>
                        <th class="px-4 py-3.5 text-center w-12">No</th>
                        <th class="px-4 py-3.5">Penerima Gaji</th>
                        <th class="px-4 py-3.5">Periode & Tgl</th>
                        <th class="px-4 py-3.5 text-right">Gaji Pokok / Total</th>
                        <th class="px-4 py-3.5 text-right">Reward / Bonus</th>
                        <th class="px-4 py-3.5 text-right">Lembur</th>
                        <th class="px-4 py-3.5 text-right">Potongan</th>
                        <th class="px-4 py-3.5 text-right">Total Bersih</th>
                        <th class="px-4 py-3.5 text-center">Metode & Status</th>
                        <th class="px-4 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($payrolls)): ?>
                    <tr>
                        <td colspan="10" class="py-12 text-center text-slate-400">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span class="font-bold text-sm text-slate-600">Belum ada data penggajian</span>
                                <span class="text-xs text-slate-400 mt-1">Silakan klik tombol <b>+ Gaji Tutor</b> atau <b>+ Gaji Karyawan</b> di atas untuk mencatat penggajian manual.</span>
                            </div>
                        </td>
                    </tr>
                    <?php else: 
                        $no = 1;
                        foreach ($payrolls as $row): 
                            $isTutor = ($row['payroll_type'] === 'tutor');
                            $payInfo = $payMethods[$row['payment_method']] ?? ['label' => ucfirst($row['payment_method']), 'badge' => 'bg-slate-100 text-slate-700 border-slate-200'];
                    ?>
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="px-4 py-3.5 text-center text-slate-400 font-bold"><?= $no++ ?></td>
                        
                        <!-- Penerima -->
                        <td class="px-4 py-3.5">
                            <div class="flex items-center gap-2.5">
                                <?php if ($isTutor): ?>
                                    <div class="flex flex-col gap-1 items-start">
                                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-wider bg-blue-100 text-pepsi-blue">
                                            TUTOR
                                        </span>
                                        <?php if (!empty($row['is_kids'])): ?>
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-amber-100 text-amber-800">
                                                KIDS
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-wider bg-purple-100 text-purple-700">
                                        STAFF
                                    </span>
                                <?php endif; ?>
                                <div>
                                    <span class="font-bold text-slate-900 text-sm block"><?= sanitize($row['recipient_name']) ?></span>
                                    <span class="text-[11px] text-slate-500 block"><?= sanitize($row['recipient_role'] ?? ($isTutor ? 'Coach / Tutor' : 'Staff Office')) ?></span>
                                </div>
                            </div>
                        </td>

                        <!-- Periode / Bulan & Tanggal -->
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            <?php if (!$isTutor): ?>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase bg-slate-100 text-slate-700 inline-block mb-0.5">
                                    Gaji Bulanan
                                </span>
                                <span class="font-bold text-slate-900 block text-xs">
                                    Bulan <?= formatBulanTahun($row['salary_month'] ?: date('Y-m', strtotime($row['payment_date']))) ?>
                                </span>
                            <?php elseif (!empty($row['is_kids'])): ?>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase bg-amber-50 text-amber-700 border border-amber-200 inline-block mb-0.5">
                                    Program Kids, Online, Non Kelas
                                </span>
                                <span class="text-slate-400 block text-xs italic">Tanpa Periode</span>
                            <?php else: ?>
                                <span class="font-semibold text-slate-800 block text-xs">
                                    <?= !empty($row['period_name']) ? sanitize($row['period_name']) : '<span class="text-slate-400">Umum / Bebas</span>' ?>
                                </span>
                            <?php endif; ?>
                            <span class="text-[11px] text-slate-500 block">Bayar: <?= tanggalIndo($row['payment_date'], 'd M Y') ?></span>
                        </td>

                        <!-- Gaji Pokok -->
                        <td class="px-4 py-3.5 text-right font-medium text-slate-700 whitespace-nowrap">
                            <?= formatRupiah($row['basic_salary']) ?>
                        </td>

                        <!-- Reward / Bonus -->
                        <td class="px-4 py-3.5 text-right whitespace-nowrap">
                            <?php if ($row['bonus_reward'] > 0): ?>
                                <span class="font-bold text-emerald-600">+<?= formatRupiah($row['bonus_reward']) ?></span>
                            <?php else: ?>
                                <span class="text-slate-300">-</span>
                            <?php endif; ?>
                        </td>

                        <!-- Lembur -->
                        <td class="px-4 py-3.5 text-right whitespace-nowrap">
                            <?php if ($row['overtime'] > 0): ?>
                                <span class="font-bold text-amber-600">+<?= formatRupiah($row['overtime']) ?></span>
                            <?php else: ?>
                                <span class="text-slate-300">-</span>
                            <?php endif; ?>
                        </td>

                        <!-- Potongan -->
                        <td class="px-4 py-3.5 text-right whitespace-nowrap">
                            <?php if ($row['deduction'] > 0): ?>
                                <span class="font-bold text-red-600">-<?= formatRupiah($row['deduction']) ?></span>
                            <?php else: ?>
                                <span class="text-slate-300">-</span>
                            <?php endif; ?>
                        </td>

                        <!-- Total Bersih Diterima -->
                        <td class="px-4 py-3.5 text-right font-heading font-black text-sm text-pepsi-red whitespace-nowrap bg-red-50/30">
                            <?= formatRupiah($row['total_amount']) ?>
                        </td>

                        <!-- Metode & Sync Pengeluaran -->
                        <td class="px-4 py-3.5 text-center whitespace-nowrap">
                            <span class="px-2.5 py-1 rounded-xl text-[10px] font-bold border <?= $payInfo['badge'] ?> inline-block">
                                <?= $payInfo['label'] ?>
                            </span>
                            <?php if (!empty($row['expense_id'])): ?>
                                <span class="block text-[10px] text-emerald-600 font-semibold mt-0.5" title="Tercatat di tabel pengeluaran operasional">
                                    &#10003; Masuk Pengeluaran
                                </span>
                            <?php endif; ?>
                        </td>

                        <!-- Aksi -->
                        <td class="px-4 py-3.5 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-1.5">
                                <!-- Print Slip -->
                                <a href="<?= url('admin/payroll/slip/' . $row['id']) ?>" 
                                   target="_blank" 
                                   title="Cetak Slip Gaji"
                                   class="px-2.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-[11px] inline-flex items-center gap-1 transition shadow-sm">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    <span>Slip</span>
                                </a>

                                <!-- Edit -->
                                <button type="button" 
                                        @click="openEdit(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)"
                                        title="Edit Penggajian"
                                        class="p-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </button>

                                <!-- Hapus -->
                                <form method="POST" action="<?= url('admin/payroll/delete/' . $row['id']) ?>" 
                                      onsubmit="return confirm('Hapus data penggajian <?= sanitize(addslashes($row['recipient_name'])) ?>? Catatan di laporan pengeluaran juga akan dihapus.');"
                                      class="inline">
                                    <button type="submit" 
                                            title="Hapus"
                                            class="p-1.5 rounded-xl bg-red-50 hover:bg-red-100 text-red-600 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
                <?php if (!empty($payrolls)): ?>
                <tfoot class="bg-slate-50 font-bold text-xs text-slate-900 border-t-2 border-slate-200">
                    <tr>
                        <td colspan="3" class="px-4 py-3.5 text-right uppercase tracking-wider">Total Halaman Ini:</td>
                        <td class="px-4 py-3.5 text-right"><?= formatRupiah($summary['total_basic']) ?></td>
                        <td class="px-4 py-3.5 text-right text-emerald-600">+<?= formatRupiah($summary['total_bonus']) ?></td>
                        <td class="px-4 py-3.5 text-right text-amber-600">+<?= formatRupiah($summary['total_overtime']) ?></td>
                        <td class="px-4 py-3.5 text-right text-red-600">-<?= formatRupiah($summary['total_deduction']) ?></td>
                        <td class="px-4 py-3.5 text-right font-heading font-black text-sm text-pepsi-red bg-red-50/60"><?= formatRupiah($summary['grand_total']) ?></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL TAMBAH GAJI TUTOR -->
    <!-- ============================================================== -->
    <div x-show="tutorModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4"
         style="display: none;">
        
        <div @click.away="tutorModal = false" class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-8 shadow-2xl border border-slate-100 space-y-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-2xl bg-blue-100 text-pepsi-blue grid place-items-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    </span>
                    <div>
                        <h3 class="font-heading font-black text-lg text-slate-900">Input Gaji Tutor / Coach</h3>
                        <p class="text-xs text-slate-500">Pilih tutor dan masukkan nominal gaji manual serta bonus/reward.</p>
                    </div>
                </div>
                <button @click="tutorModal = false" class="text-slate-400 hover:text-slate-700 p-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="<?= url('admin/payroll/store') ?>" class="space-y-4">
                <input type="hidden" name="payroll_type" value="tutor">

                <!-- Pilih Tutor -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Tutor / Coach</label>
                    <select class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-pepsi-blue"
                            @change="
                                let opt = $event.target.selectedOptions[0];
                                if (opt.value) {
                                    onSelectTutor(opt.value, opt.dataset.name, opt.dataset.spec, opt.dataset.fee);
                                }
                            ">
                        <option value="">-- Pilih dari Daftar Tutor Aktif --</option>
                        <?php foreach ($tutors as $tut): ?>
                            <option value="<?= (int)$tut['id'] ?>" 
                                    data-name="<?= sanitize($tut['name']) ?>" 
                                    data-spec="<?= sanitize($tut['specialization'] ?? 'Speaking Coach') ?>" 
                                    data-fee="<?= (float)$tut['fee_per_session'] ?>">
                                Coach <?= sanitize($tut['name']) ?> (<?= sanitize($tut['specialization'] ?? 'Speaking Coach') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Input Nama & Spesialisasi (Bisa Diedit Manual) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap Tutor <span class="text-red-500">*</span></label>
                        <input type="text" name="recipient_name" x-model="tutorForm.name" required placeholder="Nama Tutor" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-pepsi-blue">
                        <input type="hidden" name="tutor_id" x-model="tutorForm.tutor_id">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Spesialisasi / Peran</label>
                        <input type="text" name="recipient_role" x-model="tutorForm.role" placeholder="Contoh: Speaking Coach / IELTS" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-pepsi-blue">
                    </div>
                </div>

                <!-- Checkbox Program Kids, Online, Non Kelas -->
                <div class="bg-amber-50/80 border border-amber-200/80 rounded-2xl p-3 sm:p-3.5 flex items-center justify-between">
                    <label class="flex items-start sm:items-center gap-2.5 cursor-pointer select-none">
                        <input type="checkbox" name="is_kids" value="1" x-model="tutorForm.is_kids"
                               class="w-4 h-4 mt-0.5 sm:mt-0 rounded text-amber-600 focus:ring-amber-500 border-amber-300">
                        <div>
                            <span class="text-xs font-bold text-amber-950 flex items-center gap-1.5">
                                <span>Program Kids, Online, Non Kelas</span>
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-200 text-amber-800 font-extrabold uppercase">Non Kelas / Online / Kids</span>
                            </span>
                            <span class="text-[11px] text-amber-700 block">Centang jika tutor mengajar program Kids, Online, atau Non Kelas (periode dinonaktifkan & hilang dari slip cetak).</span>
                        </div>
                    </label>
                </div>

                <!-- Periode & Tanggal Bayar -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-slate-700">Periode Penggajian</label>
                            <span x-show="tutorForm.is_kids" class="text-[10px] font-bold text-amber-700 bg-amber-100 px-1.5 py-0.5 rounded">
                                Dinonaktifkan
                            </span>
                        </div>
                        <select name="period_id" 
                                :disabled="tutorForm.is_kids"
                                :class="tutorForm.is_kids ? 'bg-slate-100 text-slate-400 cursor-not-allowed border-slate-200' : 'bg-white text-slate-800 border-slate-200 focus:ring-2 focus:ring-pepsi-blue'"
                                class="w-full px-3.5 py-2.5 rounded-xl border text-xs font-bold transition">
                            <option value="">-- Pilih Periode (Opsional) --</option>
                            <?php foreach ($periods as $per): ?>
                                <option value="<?= $per['id'] ?>" <?= $selectedPeriodId == $per['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($per['name']) ?> (<?= sanitize(tanggalIndo($per['start_date'], 'd M')) ?> - <?= sanitize(tanggalIndo($per['end_date'])) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p x-show="tutorForm.is_kids" class="text-[10px] text-slate-400 mt-1 italic">
                            * Periode dinonaktifkan untuk Program Kids, Online, Non Kelas dan tidak akan tampil di slip gaji.
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Pembayaran <span class="text-red-500">*</span></label>
                        <input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-pepsi-blue">
                    </div>
                </div>

                <!-- Nominal Gaji Pokok & Reward/Bonus -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Total Gaji Pokok (Rp) <span class="text-red-500">*</span></label>
                        <input type="number" name="basic_salary" x-model.number="tutorForm.basic" min="0" step="1000" required placeholder="0" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-black text-slate-900 focus:outline-none focus:ring-2 focus:ring-pepsi-blue">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Reward / Bonus (Rp)</label>
                        <input type="number" name="bonus_reward" x-model.number="tutorForm.bonus" min="0" step="1000" placeholder="0" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-black text-emerald-700 focus:outline-none focus:ring-2 focus:ring-pepsi-blue">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Potongan (Rp)</label>
                        <input type="number" name="deduction" x-model.number="tutorForm.deduction" min="0" step="1000" placeholder="0" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-black text-red-600 focus:outline-none focus:ring-2 focus:ring-pepsi-blue">
                    </div>
                </div>

                <!-- Total Kalkulasi Live -->
                <div class="p-4 rounded-2xl bg-blue-50 border border-blue-100 flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-bold text-blue-800 uppercase tracking-wide block">Total Bersih Yang Diterima</span>
                        <span class="text-[11px] text-blue-600">Gaji Pokok + Bonus - Potongan</span>
                    </div>
                    <span class="font-heading font-black text-xl text-pepsi-blue" 
                          x-text="'Rp ' + (Math.max(0, (parseFloat(tutorForm.basic || 0) + parseFloat(tutorForm.bonus || 0)) - parseFloat(tutorForm.deduction || 0))).toLocaleString('id-ID')">
                        Rp 0
                    </span>
                </div>

                <!-- Metode Pembayaran & Catatan -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Metode Bayar</label>
                        <select name="payment_method" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-pepsi-blue">
                            <option value="tunai">Tunai / Cash</option>
                            <option value="transfer">Transfer Bank</option>
                            <option value="qris">QRIS</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Catatan / Keterangan (Opsional)</label>
                        <input type="text" name="notes" placeholder="Contoh: Honor periode Oktober, bonus performa mengajar" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-pepsi-blue">
                    </div>
                </div>

                <!-- Sync Checkbox -->
                <label class="flex items-center gap-2 cursor-pointer pt-1">
                    <input type="checkbox" name="sync_expense" value="1" checked class="w-4 h-4 rounded text-pepsi-blue focus:ring-pepsi-blue border-slate-300">
                    <span class="text-xs font-bold text-slate-700">Otomatis catat ke Laporan Pengeluaran Operasional (Kategori: Gaji & Honor)</span>
                </label>

                <!-- Action Buttons -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="tutorModal = false" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition">
                        Batal
                    </button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-pepsi-blue hover:bg-blue-900 text-white font-bold text-xs shadow-md transition">
                        Simpan Gaji Tutor
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL TAMBAH GAJI KARYAWAN -->
    <!-- ============================================================== -->
    <div x-show="staffModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4"
         style="display: none;">
        
        <div @click.away="staffModal = false" class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-8 shadow-2xl border border-slate-100 space-y-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-2xl bg-red-100 text-pepsi-red grid place-items-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </span>
                    <div>
                        <h3 class="font-heading font-black text-lg text-slate-900">Input Gaji Karyawan Office</h3>
                        <p class="text-xs text-slate-500">Input nominal manual gaji pokok, bonus/reward, dan uang lembur karyawan.</p>
                    </div>
                </div>
                <button @click="staffModal = false" class="text-slate-400 hover:text-slate-700 p-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="<?= url('admin/payroll/store') ?>" class="space-y-4">
                <input type="hidden" name="payroll_type" value="staff">

                <!-- Pilih Karyawan dari Master -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Karyawan Kantor</label>
                    <select class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-pepsi-red"
                            @change="
                                let opt = $event.target.selectedOptions[0];
                                if (opt.value) {
                                    onSelectStaff(opt.value, opt.dataset.name, opt.dataset.pos, opt.dataset.salary);
                                }
                            ">
                        <option value="">-- Pilih dari Daftar Karyawan Aktif --</option>
                        <?php foreach ($staff as $st): ?>
                            <option value="<?= (int)$st['id'] ?>" 
                                    data-name="<?= sanitize($st['name']) ?>" 
                                    data-pos="<?= sanitize($st['position'] ?? 'Staff Office') ?>" 
                                    data-salary="<?= (float)$st['salary'] ?>">
                                <?= sanitize($st['name']) ?> (<?= sanitize($st['position'] ?? 'Staff Office') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Input Nama & Jabatan -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Nama Karyawan <span class="text-red-500">*</span></label>
                        <input type="text" name="recipient_name" x-model="staffForm.name" required placeholder="Nama Karyawan" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-pepsi-red">
                        <input type="hidden" name="staff_id" x-model="staffForm.staff_id">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Jabatan / Posisi</label>
                        <input type="text" name="recipient_role" x-model="staffForm.role" placeholder="Contoh: Admin, CS, Operasional" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-pepsi-red">
                    </div>
                </div>

                <!-- Bulan Gaji & Tanggal Bayar -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Gaji Bulan (1 Bulan Sekali) <span class="text-red-500">*</span></label>
                        <input type="month" name="salary_month" x-model="staffForm.salary_month" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-900 bg-white focus:outline-none focus:ring-2 focus:ring-pepsi-red">
                        <span class="text-[10px] text-slate-400 mt-0.5 block">Gaji bulanan karyawan.</span>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Pembayaran <span class="text-red-500">*</span></label>
                        <input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-pepsi-red">
                    </div>
                </div>

                <!-- Nominal Gaji, Bonus, Lembur & Potongan -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Gaji Pokok (Rp) <span class="text-red-500">*</span></label>
                        <input type="number" name="basic_salary" x-model.number="staffForm.basic" min="0" step="1000" required placeholder="0" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs font-black text-slate-900 focus:outline-none focus:ring-2 focus:ring-pepsi-red">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Bonus / Reward</label>
                        <input type="number" name="bonus_reward" x-model.number="staffForm.bonus" min="0" step="1000" placeholder="0" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs font-black text-emerald-700 focus:outline-none focus:ring-2 focus:ring-pepsi-red">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Uang Lembur (Rp)</label>
                        <input type="number" name="overtime" x-model.number="staffForm.overtime" min="0" step="1000" placeholder="0" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs font-black text-amber-700 focus:outline-none focus:ring-2 focus:ring-pepsi-red">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Potongan (Rp)</label>
                        <input type="number" name="deduction" x-model.number="staffForm.deduction" min="0" step="1000" placeholder="0" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs font-black text-red-600 focus:outline-none focus:ring-2 focus:ring-pepsi-red">
                    </div>
                </div>

                <!-- Total Live Calculation -->
                <div class="p-4 rounded-2xl bg-red-50 border border-red-100 flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-bold text-red-800 uppercase tracking-wide block">Total Bersih Yang Diterima</span>
                        <span class="text-[11px] text-red-600">Gaji Pokok + Bonus + Lembur - Potongan</span>
                    </div>
                    <span class="font-heading font-black text-xl text-pepsi-red" 
                          x-text="'Rp ' + (Math.max(0, (parseFloat(staffForm.basic || 0) + parseFloat(staffForm.bonus || 0) + parseFloat(staffForm.overtime || 0)) - parseFloat(staffForm.deduction || 0))).toLocaleString('id-ID')">
                        Rp 0
                    </span>
                </div>

                <!-- Metode Bayar & Catatan -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Metode Bayar</label>
                        <select name="payment_method" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-pepsi-red">
                            <option value="tunai">Tunai / Cash</option>
                            <option value="transfer">Transfer Bank</option>
                            <option value="qris">QRIS</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Catatan / Keterangan (Opsional)</label>
                        <input type="text" name="notes" placeholder="Contoh: Gaji bulanan staff, lembur event khusus" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-pepsi-red">
                    </div>
                </div>

                <!-- Sync Checkbox -->
                <label class="flex items-center gap-2 cursor-pointer pt-1">
                    <input type="checkbox" name="sync_expense" value="1" checked class="w-4 h-4 rounded text-pepsi-red focus:ring-pepsi-red border-slate-300">
                    <span class="text-xs font-bold text-slate-700">Otomatis catat ke Laporan Pengeluaran Operasional (Kategori: Gaji & Honor)</span>
                </label>

                <!-- Action Buttons -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="staffModal = false" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition">
                        Batal
                    </button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-pepsi-red hover:bg-red-800 text-white font-bold text-xs shadow-md transition">
                        Simpan Gaji Karyawan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL EDIT PENGGAJIAN -->
    <!-- ============================================================== -->
    <div x-show="editModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4"
         style="display: none;">
        
        <div @click.away="editModal = false" class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-8 shadow-2xl border border-slate-100 space-y-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-2xl bg-slate-900 text-white grid place-items-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </span>
                    <div>
                        <h3 class="font-heading font-black text-lg text-slate-900">Edit Data Penggajian</h3>
                        <p class="text-xs text-slate-500">Perubahan nominal akan otomatis memperbarui data laporan pengeluaran.</p>
                    </div>
                </div>
                <button @click="editModal = false" class="text-slate-400 hover:text-slate-700 p-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form :action="'<?= url('admin/payroll/update') ?>/' + editItem.id" method="POST" class="space-y-4">
                <input type="hidden" name="payroll_type" :value="editItem.payroll_type">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Nama Penerima <span class="text-red-500">*</span></label>
                        <input type="text" name="recipient_name" x-model="editItem.recipient_name" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Peran / Jabatan</label>
                        <input type="text" name="recipient_role" x-model="editItem.recipient_role" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900">
                    </div>
                </div>

                <!-- Checkbox Program Kids, Online, Non Kelas (Untuk Tutor) -->
                <template x-if="editItem.payroll_type === 'tutor'">
                    <div class="bg-amber-50/80 border border-amber-200/80 rounded-2xl p-3 sm:p-3.5 flex items-center justify-between">
                        <label class="flex items-start sm:items-center gap-2.5 cursor-pointer select-none">
                            <input type="checkbox" name="is_kids" value="1" x-model="editItem.is_kids"
                                   class="w-4 h-4 mt-0.5 sm:mt-0 rounded text-amber-600 focus:ring-amber-500 border-amber-300">
                            <div>
                                <span class="text-xs font-bold text-amber-950 flex items-center gap-1.5">
                                    <span>Program Kids, Online, Non Kelas</span>
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-200 text-amber-800 font-extrabold uppercase">Non Kelas / Online / Kids</span>
                                </span>
                                <span class="text-[11px] text-amber-700 block">Centang jika tutor mengajar program Kids, Online, atau Non Kelas (periode dinonaktifkan & hilang dari slip cetak).</span>
                            </div>
                        </label>
                    </div>
                </template>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <template x-if="editItem.payroll_type === 'staff'">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Gaji Bulan <span class="text-red-500">*</span></label>
                            <input type="month" name="salary_month" x-model="editItem.salary_month" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-900 bg-white focus:outline-none focus:ring-2 focus:ring-slate-900">
                        </div>
                    </template>
                    <div :class="editItem.payroll_type === 'staff' ? '' : 'sm:col-span-2'">
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-slate-700">Periode Kursus Terkait</label>
                            <span x-show="editItem.is_kids" class="text-[10px] font-bold text-amber-700 bg-amber-100 px-1.5 py-0.5 rounded">
                                Dinonaktifkan
                            </span>
                        </div>
                        <select name="period_id" x-model="editItem.period_id" 
                                :disabled="editItem.is_kids"
                                :class="editItem.is_kids ? 'bg-slate-100 text-slate-400 cursor-not-allowed border-slate-200' : 'bg-white text-slate-800 border-slate-200 focus:ring-2 focus:ring-slate-900'"
                                class="w-full px-3.5 py-2.5 rounded-xl border text-xs font-bold transition">
                            <option value="">-- Tanpa Periode Tertentu --</option>
                            <?php foreach ($periods as $per): ?>
                                <option value="<?= $per['id'] ?>"><?= sanitize($per['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p x-show="editItem.is_kids" class="text-[10px] text-slate-400 mt-1 italic">
                            * Periode dinonaktifkan untuk Program Kids, Online, Non Kelas dan tidak akan tampil di slip gaji.
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Pembayaran <span class="text-red-500">*</span></label>
                        <input type="date" name="payment_date" x-model="editItem.payment_date" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900">
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Gaji Pokok (Rp)</label>
                        <input type="number" name="basic_salary" x-model.number="editItem.basic_salary" min="0" step="1000" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs font-black text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Reward / Bonus</label>
                        <input type="number" name="bonus_reward" x-model.number="editItem.bonus_reward" min="0" step="1000" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs font-black text-emerald-700 focus:outline-none focus:ring-2 focus:ring-slate-900">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Lembur (Rp)</label>
                        <input type="number" name="overtime" x-model.number="editItem.overtime" min="0" step="1000" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs font-black text-amber-700 focus:outline-none focus:ring-2 focus:ring-slate-900">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Potongan (Rp)</label>
                        <input type="number" name="deduction" x-model.number="editItem.deduction" min="0" step="1000" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs font-black text-red-600 focus:outline-none focus:ring-2 focus:ring-slate-900">
                    </div>
                </div>

                <!-- Total Kalkulasi Live -->
                <div class="p-4 rounded-2xl bg-slate-100 border border-slate-200 flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-bold text-slate-800 uppercase tracking-wide block">Total Bersih Yang Diterima</span>
                        <span class="text-[11px] text-slate-500">Pokok + Bonus + Lembur - Potongan</span>
                    </div>
                    <span class="font-heading font-black text-xl text-pepsi-red" 
                          x-text="'Rp ' + (Math.max(0, (parseFloat(editItem.basic_salary || 0) + parseFloat(editItem.bonus_reward || 0) + parseFloat(editItem.overtime || 0)) - parseFloat(editItem.deduction || 0))).toLocaleString('id-ID')">
                        Rp 0
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Metode Bayar</label>
                        <select name="payment_method" x-model="editItem.payment_method" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-slate-900">
                            <option value="tunai">Tunai / Cash</option>
                            <option value="transfer">Transfer Bank</option>
                            <option value="qris">QRIS</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Catatan / Keterangan</label>
                        <input type="text" name="notes" x-model="editItem.notes" placeholder="Catatan tambahan..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900">
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="editModal = false" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition">
                        Batal
                    </button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-md transition">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
