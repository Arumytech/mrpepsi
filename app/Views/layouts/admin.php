<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require __DIR__ . '/../partials/pwa_head.php'; ?>
    <title>Admin Panel - <?= sanitize($settings['app_name'] ?? APP_NAME) ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Bricolage+Grotesque:wght@700;800&display=swap" rel="stylesheet">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        pepsi: {
                            navy: '#12131A',
                            blue: '#2B3A9E',
                            'blue-light': '#EEF2FF',
                            red: '#E3262E',
                            'red-light': '#FEE2E2',
                            wa: '#1FAF54',
                            'wa-dark': '#15803D'
                        }
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        heading: ['"Bricolage Grotesque"', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }

        .font-heading {
            font-family: 'Bricolage Grotesque', sans-serif;
        }
    </style>
</head>

<body class="bg-slate-100 text-slate-900 font-sans antialiased min-h-screen flex" x-data="{ sidebarOpen: false }">

    <!-- Mobile Sidebar Backdrop -->
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-slate-900/60 lg:hidden"></div>

    <!-- Sidebar Navigation -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        class="fixed inset-y-0 left-0 z-50 w-72 bg-pepsi-navy text-white flex flex-col justify-between transition-transform duration-300 ease-in-out border-r border-slate-800">

        <div>
            <!-- Header Brand -->
            <div class="h-20 flex items-center justify-between px-6 border-b border-slate-800">
                <a href="<?= url('admin') ?>" class="flex items-center gap-3">
                    <?php if (logo_img()): ?>
                        <span class="w-10 h-10 rounded-xl bg-white grid place-items-center overflow-hidden p-1 shrink-0">
                            <?= logo_img('max-w-full max-h-full object-contain') ?>
                        </span>
                    <?php else: ?>
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-pepsi-blue to-pepsi-red flex items-center justify-center text-white font-heading font-black text-lg">
                            MP
                        </div>
                    <?php endif; ?>
                    <div>
                        <span class="font-heading font-extrabold text-base tracking-tight block leading-none"><?= sanitize($settings['app_name'] ?? APP_NAME) ?></span>
                        <span class="text-[10px] font-bold text-pepsi-red tracking-widest uppercase">ADMIN PANEL</span>
                    </div>
                </a>
                <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-white p-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="p-4 space-y-1 text-xs font-semibold overflow-y-auto max-h-[calc(100vh-160px)]">
                <?php if (can('dashboard')): ?>
                    <a href="<?= url('admin') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/70 transition">
                        <svg class="w-4 h-4 text-pepsi-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Dashboard Utama</span>
                    </a>
                <?php endif; ?>

                <?php /* Menu "Pendaftaran Siswa" disembunyikan: pendaftaran dilakukan dari halaman Master & Pendaftaran Siswa */ ?>

                <?php if (can('students')): ?>
                    <a href="<?= url('admin/students') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/70 transition">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span>Master &amp; Pendaftaran Siswa</span>
                    </a>
                <?php endif; ?>

                <?php if (can('graduates')): ?>
                    <a href="<?= url('admin/students/graduates') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/70 transition">
                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                        </svg>
                        <span>Siswa Lulus & Sertifikat</span>
                    </a>
                <?php endif; ?>

                <?php if (can('programs')): ?>
                    <a href="<?= url('admin/programs') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/70 transition">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <span>Kelola Program & Harga</span>
                    </a>
                <?php endif; ?>

                <?php if (can('schedules')): ?>
                    <a href="<?= url('admin/schedules') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/70 transition">
                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>Schedule Builder (Anti Bentrok)</span>
                    </a>
                <?php endif; ?>



                <?php if (can('attendance_sheet') || can('attendance')): ?>
                    <a href="<?= url('admin/attendance/class-sheet') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/70 transition">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Cetak Presensi Kelas</span>
                    </a>
                <?php endif; ?>

                <?php if (can('payroll')): ?>
                    <a href="<?= url('admin/payroll') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/70 transition">
                        <svg class="w-4 h-4 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span>Penggajian (Payroll)</span>
                    </a>
                <?php endif; ?>

                <?php if (can('tutors') || can('rooms') || can('periods') || can('staff_office')): ?>
                    <div class="pt-3 pb-1 px-3 text-[10px] font-extrabold uppercase tracking-wider text-slate-300">Master Data</div>
                <?php endif; ?>

                <?php if (can('tutors')): ?>
                    <a href="<?= url('admin/tutors') ?>" class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/70 transition">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span>
                        <span>Data Tutor / Coach</span>
                    </a>
                <?php endif; ?>
                <?php if (can('staff_office')): ?>
                    <a href="<?= url('admin/staff') ?>" class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/70 transition">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span>
                        <span>Master Staff Office</span>
                    </a>
                <?php endif; ?>
                <?php if (can('rooms')): ?>
                    <a href="<?= url('admin/rooms') ?>" class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/70 transition">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span>
                        <span>Data Ruangan</span>
                    </a>
                <?php endif; ?>
                <?php if (can('periods')): ?>
                    <a href="<?= url('admin/periods') ?>" class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/70 transition">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span>
                        <span>Periode Pendaftaran</span>
                    </a>
                <?php endif; ?>

                <?php if (can('canteen') || can('canteen_opname') || can('canteen_report') || can('manual_sales') || can('canteen_barcode') || can('pos')): ?>
                    <div class="pt-3 pb-1 px-3 text-[10px] font-extrabold uppercase tracking-wider text-slate-300">Pepsi Food &amp; Drink</div>
                <?php endif; ?>

                <?php if (can('canteen')): ?>
                    <a href="<?= url('admin/canteen') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/70 transition">
                        <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        <span>Master Produk Pepsi Food &amp; Drink</span>
                    </a>
                <?php endif; ?>
                <?php if (can('canteen_opname')): ?>
                    <a href="<?= url('admin/canteen/opname') ?>" class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/70 transition">
                        <span class="w-1.5 h-1.5 rounded-full bg-orange-400"></span>
                        <span>Stok Opname & Riwayat</span>
                    </a>
                <?php endif; ?>
                <?php if (can('canteen_report')): ?>
                    <a href="<?= url('admin/canteen/report') ?>" class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/70 transition">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>
                        <span>Laporan Laba Rugi Pepsi Food &amp; Drink</span>
                    </a>
                <?php endif; ?>
                <?php if (can('canteen_monthly_report')): ?>
                    <a href="<?= url('admin/canteen/monthly-report') ?>" class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/70 transition">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                        <span>Laporan Bulanan (Berlanjut)</span>
                    </a>
                <?php endif; ?>
                <?php if (can('manual_sales')): ?>
                    <a href="<?= url('admin/manual-sales') ?>" class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/70 transition">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                        <span>Input Penjualan Tambahan</span>
                    </a>
                <?php endif; ?>
                <?php if (can('canteen_barcode')): ?>
                    <a href="<?= url('admin/canteen/barcode') ?>" class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/70 transition">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                        <span>Cetak Barcode Produk</span>
                    </a>
                <?php endif; ?>
                <?php if (can('canteen_transactions') || can('canteen')): ?>
                    <a href="<?= url('admin/canteen/transactions') ?>" class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/70 transition">
                        <span class="w-1.5 h-1.5 rounded-full bg-cyan-400"></span>
                        <span>Penyesuaian Transaksi (Kasir)</span>
                    </a>
                <?php endif; ?>
                <?php if (can('canteen_report') || can('canteen')): ?>
                    <a href="<?= url('admin/canteen/daily-report') ?>" class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/70 transition">
                        <span class="w-1.5 h-1.5 rounded-full bg-yellow-400"></span>
                        <span>Laporan Penjualan Harian</span>
                    </a>
                <?php endif; ?>
                <?php if (can('pos')): ?>
                    <a href="<?= url('pos') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl bg-pepsi-red/80 hover:bg-pepsi-red text-white transition font-bold mt-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        <span>Buka POS Kasir &rarr;</span>
                    </a>
                <?php endif; ?>

                <?php if (can('reports_students') || can('reports_finance') || can('reports_combined') || can('reports_canteen') || can('reports_canteen_monthly') || can('slip_report')): ?>
                    <div class="pt-3 pb-1 px-3 text-[10px] font-extrabold uppercase tracking-wider text-slate-300">Laporan &amp; Cetak</div>
                <?php endif; ?>

                <?php if (can('reports_students')): ?>
                    <a href="<?= url('admin/reports/students') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/70 transition">
                        <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Laporan Data Siswa</span>
                    </a>
                <?php endif; ?>
                <?php if (can('reports_finance')): ?>
                    <a href="<?= url('admin/reports/finance') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/70 transition">
                        <svg class="w-4 h-4 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Laporan Keuangan Kursus</span>
                    </a>
                <?php endif; ?>
                <?php if (can('reports_combined')): ?>
                    <a href="<?= url('admin/reports/combined') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/70 transition">
                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V9m3 8V5m3 12v-6M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        <span>Laporan Keuangan Gabungan</span>
                    </a>
                <?php endif; ?>
                <?php if (can('reports_canteen')): ?>
                    <a href="<?= url('admin/reports/canteen') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/70 transition">
                        <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span>Laporan Pepsi Food &amp; Drink</span>
                    </a>
                <?php endif; ?>
                <?php if (can('reports_canteen_monthly')): ?>
                    <a href="<?= url('admin/reports/canteen-monthly') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/70 transition">
                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>Cetak Laporan Bulanan Pepsi Food &amp; Drink</span>
                    </a>
                <?php endif; ?>
                <?php if (can('slip_report')): ?>
                    <a href="<?= url('admin/payroll/report') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/70 transition">
                        <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Cetak Laporan Slip Gaji</span>
                    </a>
                <?php endif; ?>

                <?php if (can('expenses') || can('expenses_report')): ?>
                    <div class="pt-3 pb-1 px-3 text-[10px] font-extrabold uppercase tracking-wider text-slate-300">Pengeluaran &amp; Laporan</div>
                <?php endif; ?>

                <?php if (can('expenses')): ?>
                    <a href="<?= url('admin/expenses') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/70 transition">
                        <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span>Input Pengeluaran Operasional</span>
                    </a>
                <?php endif; ?>
                <?php if (can('expenses_report')): ?>
                    <a href="<?= url('admin/expenses/report') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/70 transition">
                        <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V9m3 8V5m3 12v-6M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        <span>Laporan Pengeluaran Operasional</span>
                    </a>
                <?php endif; ?>

                <?php if (can('settings') || can('pos_control') || can('users') || can('permissions') || can('sessions') || can('database')): ?>
                    <div class="pt-3 pb-1 px-3 text-[10px] font-extrabold uppercase tracking-wider text-slate-300">Pengaturan</div>
                <?php endif; ?>

                <?php if (can('settings')): ?>
                    <a href="<?= url('admin/settings') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/70 transition">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>Pengaturan Umum</span>
                    </a>
                <?php endif; ?>

                <?php if (can('pos_control')): ?>
                    <a href="<?= url('admin/pos-control') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/70 transition">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        <span>Kontrol Operasional POS</span>
                    </a>
                <?php endif; ?>

                <?php if (can('users')): ?>
                    <a href="<?= url('admin/users') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/70 transition">
                        <svg class="w-4 h-4 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span>Kelola User &amp; Akun</span>
                    </a>
                <?php endif; ?>

                <?php if (can('permissions')): ?>
                    <a href="<?= url('admin/permissions') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/70 transition">
                        <svg class="w-4 h-4 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <span>Hak Akses &amp; Menu</span>
                    </a>
                <?php endif; ?>

                <?php if (can('sessions')): ?>
                    <a href="<?= url('admin/sessions') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/70 transition">
                        <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                        </svg>
                        <span>Release Sesi Login</span>
                    </a>
                <?php endif; ?>

                <?php if (can('database')): ?>
                    <a href="<?= url('admin/database') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/70 transition">
                        <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <span>Bersihkan Database</span>
                    </a>
                <?php endif; ?>
            </nav>
        </div>

        <!-- Footer Profile -->
        <div class="p-4 border-t border-slate-800 bg-slate-900/60 flex items-center justify-between text-xs">
            <div>
                <span class="font-bold text-white block leading-tight"><?= sanitize($_SESSION['user']['name'] ?? 'Admin') ?></span>
                <span class="text-[10px] text-slate-400 capitalize"><?= sanitize($_SESSION['user']['role'] ?? 'admin') ?></span>
            </div>
            <div class="flex items-center gap-1.5">
                <?php if (can('sessions')): ?>
                    <a href="<?= url('admin/sessions') ?>" class="p-2 rounded-xl bg-slate-800 text-slate-400 hover:text-cyan-400 hover:bg-slate-700 transition" title="Release Sesi Login">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                        </svg>
                    </a>
                <?php endif; ?>
                <a href="<?= url('logout') ?>" class="p-2 rounded-xl bg-slate-800 text-slate-300 hover:text-red-400 hover:bg-slate-700 transition" title="Logout">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 lg:pl-72 flex flex-col min-h-screen">
        <!-- Topbar -->
        <header class="h-16 bg-white border-b border-slate-200 sticky top-0 z-30 flex items-center justify-between px-4 sm:px-6">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-xl text-slate-600 hover:bg-slate-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <a href="<?= url('/') ?>" target="_blank" class="text-xs font-semibold text-slate-500 hover:text-pepsi-blue flex items-center gap-1.5 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                    <span>Lihat Web Calon Siswa (Publik)</span>
                </a>
            </div>

            <div class="flex items-center gap-2.5 sm:gap-3">
                <span class="text-xs font-bold text-slate-500 hidden sm:inline"><?= date('l, d F Y') ?></span>

                <!-- Tombol & Popup Cek Update Aplikasi -->
                <div class="relative" x-data="topbarUpdateChecker()">
                    <button type="button" @click="toggleCheck()" :disabled="loading || updating"
                            title="Cek Pembaruan Aplikasi"
                            class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-blue-50 hover:border-blue-200 text-slate-600 hover:text-pepsi-blue transition text-xs font-semibold shadow-sm cursor-pointer disabled:opacity-60">
                        <svg :class="{'animate-spin text-pepsi-blue': loading || updating}" class="w-3.5 h-3.5 text-slate-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span class="hidden md:inline text-[11px] font-bold" x-text="updating ? 'Memperbarui...' : (loading ? 'Memeriksa...' : 'Cek Update')">Cek Update</span>
                        <span class="w-2 h-2 rounded-full" :class="hasUpdate ? 'bg-amber-500 animate-ping' : 'bg-emerald-500'"></span>
                    </button>

                    <!-- Popup Dropdown Hasil Cek Update -->
                    <div x-show="showPopup" x-cloak @click.away="if (!updating) showPopup = false"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                         x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                         class="absolute right-0 mt-2 w-72 sm:w-80 bg-white rounded-2xl shadow-xl border border-slate-200 p-4 z-50 text-xs space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                            <div class="flex items-center gap-2">
                                <span class="text-sm">⚡</span>
                                <span class="font-bold text-slate-800 text-xs">Pembaruan Aplikasi</span>
                            </div>
                            <button type="button" @click="showPopup = false" :disabled="updating" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">✕</button>
                        </div>

                        <!-- Loading State -->
                        <div x-show="loading" class="py-4 text-center space-y-2">
                            <div class="w-6 h-6 border-2 border-pepsi-blue border-t-transparent rounded-full animate-spin mx-auto"></div>
                            <span class="text-slate-500 text-[11px] block">Memeriksa pembaruan aplikasi...</span>
                        </div>

                        <!-- Updating State -->
                        <div x-show="updating" class="py-4 text-center space-y-2">
                            <div class="w-6 h-6 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
                            <span class="text-slate-700 font-bold text-[11px] block">Sedang Menerapkan Pembaruan...</span>
                            <span class="text-slate-400 text-[10px] block">Mencadangkan file otomatis &amp; menyinkronkan sistem...</span>
                        </div>

                        <!-- Hasil Cek State -->
                        <div x-show="!loading && !updating && (result || updateSuccess)" class="space-y-2.5">
                            <!-- Bila update baru saja sukses diterapkan -->
                            <template x-if="updateSuccess">
                                <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl space-y-2 text-emerald-900">
                                    <div class="flex items-center gap-1.5 text-emerald-700 font-bold">
                                        <span>✓</span>
                                        <span>Pembaruan Berhasil Diterapkan!</span>
                                    </div>
                                    <p class="text-[11px]" x-text="updateSuccessMessage"></p>
                                    <button type="button" onclick="location.reload()"
                                            class="w-full text-center px-3 py-1.5 bg-pepsi-blue hover:bg-blue-700 text-white font-bold rounded-lg text-[11px] transition cursor-pointer">
                                        Muat Ulang Halaman
                                    </button>
                                </div>
                            </template>

                            <!-- Bila ada update baru tersedia -->
                            <template x-if="!updateSuccess && result && result.has_update">
                                <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl space-y-2 text-emerald-900">
                                    <span class="font-bold block text-xs">🎉 Pembaruan Baru Tersedia!</span>
                                    <p class="text-[11px]" x-text="result.message"></p>
                                    <div class="flex flex-col gap-1.5 pt-1">
                                        <button type="button" @click="runApply()"
                                                class="w-full text-center px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-[11px] transition shadow cursor-pointer">
                                            🚀 Terapkan Pembaruan Sekarang
                                        </button>
                                        <?php if (\App\Core\Permission::can('settings')): ?>
                                            <a href="<?= url('admin/settings#update-section') ?>" class="block text-center text-[10px] text-slate-500 hover:underline">
                                                Buka Pengaturan Lengkap &rarr;
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </template>

                            <!-- Bila sudah up to date -->
                            <template x-if="!updateSuccess && result && !result.has_update && !result.error">
                                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1 text-slate-700">
                                    <div class="flex items-center gap-1.5 text-emerald-700 font-bold">
                                        <span>✓</span>
                                        <span>Sistem Sudah Paling Baru</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500" x-text="result.message"></p>
                                    <div class="pt-1.5 flex items-center justify-between border-t border-slate-200/60 mt-2">
                                        <button type="button" @click="runCheck()" class="text-[10px] font-bold text-pepsi-blue hover:underline cursor-pointer">
                                            Periksa Ulang
                                        </button>
                                        <?php if (\App\Core\Permission::can('settings')): ?>
                                            <a href="<?= url('admin/settings#update-section') ?>" class="text-[10px] font-bold text-slate-500 hover:underline">
                                                Cadangan &amp; Versi &rarr;
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </template>

                            <!-- Bila error -->
                            <template x-if="!updateSuccess && result && result.error">
                                <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl space-y-2 text-amber-900">
                                    <span class="font-bold block text-xs">⚠️ Info Pengecekan</span>
                                    <p class="text-[11px]" x-text="result.error"></p>
                                    <button type="button" @click="runCheck()" class="inline-block text-[10px] font-bold text-amber-800 hover:underline cursor-pointer">
                                        Coba Lagi
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <a href="<?= url('pos') ?>" target="_blank" class="px-3.5 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-bold rounded-xl flex items-center gap-1.5 shadow-sm transition">
                    <span>Kasir POS</span>
                </a>
            </div>
        </header>

        <!-- Flash Message Alerts -->
        <div class="p-4 sm:p-6 pb-0">
            <?php if ($flashError = $this->getFlash('error')): ?>
                <div class="p-4 bg-red-50 border border-red-200 text-red-800 text-xs sm:text-sm font-semibold rounded-2xl flex items-center gap-3 shadow-sm mb-4">
                    <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <span><?= $flashError ?></span>
                </div>
            <?php endif; ?>

            <?php if ($flashSuccess = $this->getFlash('success')): ?>
                <div class="p-4 bg-green-50 border border-green-200 text-green-800 text-xs sm:text-sm font-semibold rounded-2xl flex items-center gap-3 shadow-sm mb-4">
                    <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span><?= $flashSuccess ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Inner Content -->
        <main class="flex-1 p-4 sm:p-6 pb-24 lg:pb-12">
            <?= $content ?>
        </main>
    </div>

    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('topbarUpdateChecker', () => ({
            loading: false,
            updating: false,
            showPopup: false,
            hasUpdate: false,
            updateSuccess: false,
            updateSuccessMessage: '',
            result: null,
            toggleCheck() {
                if (this.showPopup && (this.result || this.updateSuccess)) {
                    this.runCheck();
                } else {
                    this.showPopup = true;
                    if (!this.result) {
                        this.runCheck();
                    }
                }
            },
            runCheck() {
                this.loading = true;
                this.result = null;
                this.updateSuccess = false;
                fetch('<?= url('admin/settings/check-update') ?>', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(async res => {
                    const contentType = res.headers.get('content-type') || '';
                    if (!res.ok) {
                        if (contentType.includes('application/json')) {
                            const errData = await res.json();
                            throw new Error(errData.error || ('HTTP Error ' + res.status));
                        }
                        throw new Error('Server merespon dengan status ' + res.status);
                    }
                    if (!contentType.includes('application/json')) {
                        throw new Error('Respon server bukan data JSON yang valid.');
                    }
                    return res.json();
                })
                .then(data => {
                    this.loading = false;
                    this.result = data;
                    this.hasUpdate = Boolean(data && data.has_update);
                })
                .catch(err => {
                    this.loading = false;
                    this.result = { error: 'Gagal memeriksa pembaruan: ' + err.message };
                });
            },
            runApply() {
                if (!confirm('Apakah Anda yakin ingin menerapkan pembaruan sekarang? Cadangan (backup) otomatis akan dibuat terlebih dahulu.')) {
                    return;
                }
                this.updating = true;
                fetch('<?= url('admin/settings/apply-update') ?>', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(async res => {
                    const contentType = res.headers.get('content-type') || '';
                    if (!res.ok) {
                        if (contentType.includes('application/json')) {
                            const errData = await res.json();
                            throw new Error(errData.error || ('HTTP Error ' + res.status));
                        }
                        throw new Error('Server merespon dengan status ' + res.status);
                    }
                    return res.json();
                })
                .then(data => {
                    this.updating = false;
                    if (data && data.success) {
                        this.hasUpdate = false;
                        this.updateSuccess = true;
                        this.updateSuccessMessage = data.message || 'Pembaruan berhasil diterapkan!';
                    } else {
                        this.result = { error: (data && data.message) ? data.message : 'Gagal menerapkan pembaruan.' };
                    }
                })
                .catch(err => {
                    this.updating = false;
                    this.result = { error: 'Gagal menerapkan pembaruan: ' + err.message };
                });
            }
        }));
    });
    </script>

    <?php require __DIR__ . '/../partials/pwa_script.php'; ?>

</body>

</html>