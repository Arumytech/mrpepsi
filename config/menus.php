<?php
/**
 * Definisi menu & hak akses (single source of truth).
 *
 * Setiap item memuat:
 *   - label : nama menu di sidebar
 *   - group : kelompok menu di sidebar
 *   - url   : tujuan menu (relatif, dipakai helper url())
 *   - paths : daftar prefix URL yang dilindungi menu ini (untuk guard akses)
 *
 * Urutan pengecekan guard memakai longest-prefix match, jadi path yang lebih
 * spesifik (mis. /admin/students/graduates) otomatis menang atas induknya.
 */
return [
    // ── Utama ──────────────────────────────────────────────
    'dashboard' => [
        'label' => 'Dashboard Utama',
        'group' => 'Utama',
        'url'   => 'admin',
        'paths' => ['/admin/dashboard', '/admin'],
    ],
    'students' => [
        'label' => 'Master & Pendaftaran Siswa',
        'group' => 'Utama',
        'url'   => 'admin/students',
        'paths' => ['/admin/students'],
    ],
    'graduates' => [
        'label' => 'Siswa Lulus & Sertifikat',
        'group' => 'Utama',
        'url'   => 'admin/students/graduates',
        'paths' => ['/admin/students/graduates'],
    ],

    // ── Akademik ───────────────────────────────────────────
    'programs' => [
        'label' => 'Kelola Program & Harga',
        'group' => 'Akademik',
        'url'   => 'admin/programs',
        'paths' => ['/admin/programs'],
    ],
    'schedules' => [
        'label' => 'Schedule Builder (Anti Bentrok)',
        'group' => 'Akademik',
        'url'   => 'admin/schedules',
        'paths' => ['/admin/schedules'],
    ],
    'attendance' => [
        'label' => 'Presensi & Tutor Pengganti',
        'group' => 'Akademik',
        'url'   => 'admin/attendance',
        'paths' => ['/admin/attendance'],
    ],
    'attendance_sheet' => [
        'label' => 'Cetak Presensi Kelas',
        'group' => 'Akademik',
        'url'   => 'admin/attendance/class-sheet',
        'paths' => ['/admin/attendance/class-sheet', '/admin/attendance/print-class-sheet'],
    ],
    'payroll' => [
        'label' => 'Penggajian (Payroll)',
        'group' => 'Akademik',
        'url'   => 'admin/payroll',
        'paths' => ['/admin/payroll'],
    ],

    // ── Master Data ────────────────────────────────────────
    'tutors' => [
        'label' => 'Data Tutor / Coach',
        'group' => 'Master Data',
        'url'   => 'admin/tutors',
        'paths' => ['/admin/tutors'],
    ],
    'staff_office' => [
        'label' => 'Master Staff Office',
        'group' => 'Master Data',
        'url'   => 'admin/staff',
        'paths' => ['/admin/staff'],
    ],
    'rooms' => [
        'label' => 'Data Ruangan',
        'group' => 'Master Data',
        'url'   => 'admin/rooms',
        'paths' => ['/admin/rooms'],
    ],
    'periods' => [
        'label' => 'Periode Pendaftaran',
        'group' => 'Master Data',
        'url'   => 'admin/periods',
        'paths' => ['/admin/periods'],
    ],

    // ── Kantin & Kasir POS ─────────────────────────────────
    'canteen' => [
        'label' => 'Master Produk Pepsi Food & Drink',
        'group' => 'Kantin & Kasir POS',
        'url'   => 'admin/canteen',
        'paths' => ['/admin/canteen'],
    ],
    'canteen_opname' => [
        'label' => 'Stok Opname & Riwayat',
        'group' => 'Kantin & Kasir POS',
        'url'   => 'admin/canteen/opname',
        'paths' => ['/admin/canteen/opname'],
    ],
    'canteen_report' => [
        'label' => 'Laporan Laba Rugi Pepsi Food & Drink',
        'group' => 'Kantin & Kasir POS',
        'url'   => 'admin/canteen/report',
        'paths' => ['/admin/canteen/report'],
    ],
    'canteen_monthly_report' => [
        'label' => 'Laporan Bulanan Kantin (Berlanjut)',
        'group' => 'Kantin & Kasir POS',
        'url'   => 'admin/canteen/monthly-report',
        'paths' => ['/admin/canteen/monthly-report'],
    ],
    'manual_sales' => [
        'label' => 'Input Penjualan Tambahan',
        'group' => 'Kantin & Kasir POS',
        'url'   => 'admin/manual-sales',
        'paths' => ['/admin/manual-sales'],
    ],
    'canteen_barcode' => [
        'label' => 'Cetak Barcode Produk',
        'group' => 'Kantin & Kasir POS',
        'url'   => 'admin/canteen/barcode',
        'paths' => ['/admin/canteen/barcode'],
    ],
    'canteen_transactions' => [
        'label' => 'Penyesuaian Transaksi (Kasir)',
        'group' => 'Kantin & Kasir POS',
        'url'   => 'admin/canteen/transactions',
        'paths' => ['/admin/canteen/transactions'],
    ],
    'pos' => [
        'label' => 'Buka POS Kasir',
        'group' => 'Kantin & Kasir POS',
        'url'   => 'pos',
        'paths' => ['/pos'],
    ],

    // ── Laporan & Cetak ────────────────────────────────────
    'reports_students' => [
        'label' => 'Laporan Data Siswa',
        'group' => 'Laporan & Cetak',
        'url'   => 'admin/reports/students',
        'paths' => ['/admin/reports/students'],
    ],
    'reports_finance' => [
        'label' => 'Laporan Keuangan Kursus',
        'group' => 'Laporan & Cetak',
        'url'   => 'admin/reports/finance',
        'paths' => ['/admin/reports/finance'],
    ],
    'reports_combined' => [
        'label' => 'Laporan Keuangan Gabungan',
        'group' => 'Laporan & Cetak',
        'url'   => 'admin/reports/combined',
        'paths' => ['/admin/reports/combined'],
    ],
    'reports_canteen' => [
        'label' => 'Laporan Pepsi Food & Drink',
        'group' => 'Laporan & Cetak',
        'url'   => 'admin/reports/canteen',
        'paths' => ['/admin/reports/canteen'],
    ],
    'reports_canteen_monthly' => [
        'label' => 'Cetak Laporan Bulanan Kantin',
        'group' => 'Laporan & Cetak',
        'url'   => 'admin/reports/canteen-monthly',
        'paths' => ['/admin/reports/canteen-monthly'],
    ],
    'slip_report' => [
        'label' => 'Cetak Laporan Slip Gaji',
        'group' => 'Laporan & Cetak',
        'url'   => 'admin/payroll/report',
        'paths' => ['/admin/payroll/report'],
    ],

    // ── Pengeluaran Operasional ────────────────────────────
    'expenses' => [
        'label' => 'Input Pengeluaran Operasional',
        'group' => 'Pengeluaran & Laporan',
        'url'   => 'admin/expenses',
        'paths' => ['/admin/expenses'],
    ],
    'expenses_report' => [
        'label' => 'Laporan Pengeluaran Operasional',
        'group' => 'Pengeluaran & Laporan',
        'url'   => 'admin/expenses/report',
        'paths' => ['/admin/expenses/report'],
    ],

    // ── Pengaturan ─────────────────────────────────────────
    'settings' => [
        'label' => 'Pengaturan Umum',
        'group' => 'Pengaturan',
        'url'   => 'admin/settings',
        'paths' => ['/admin/settings'],
    ],
    'pos_control' => [
        'label' => 'Kontrol Operasional POS',
        'group' => 'Pengaturan',
        'url'   => 'admin/pos-control',
        'paths' => ['/admin/pos-control'],
    ],
    'users' => [
        'label' => 'Kelola User & Akun',
        'group' => 'Pengaturan',
        'url'   => 'admin/users',
        'paths' => ['/admin/users'],
    ],
    'permissions' => [
        'label' => 'Hak Akses & Menu',
        'group' => 'Pengaturan',
        'url'   => 'admin/permissions',
        'paths' => ['/admin/permissions'],
    ],
    'sessions' => [
        'label' => 'Release Sesi Login',
        'group' => 'Pengaturan',
        'url'   => 'admin/sessions',
        'paths' => ['/admin/sessions'],
    ],
    'database' => [
        'label' => 'Bersihkan Database',
        'group' => 'Pengaturan',
        'url'   => 'admin/database',
        'paths' => ['/admin/database'],
    ],
];
