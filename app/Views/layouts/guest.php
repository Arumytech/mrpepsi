<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($settings['app_name'] ?? APP_NAME) ?> - <?= sanitize($settings['tagline'] ?? APP_TAGLINE) ?></title>
    <meta name="description" content="Kursus speaking dan persiapan IELTS di Kampung Inggris Pare, Kediri. Periode tanggal 10 dan 25 setiap bulan.">
    <?php require __DIR__ . '/../partials/pwa_head.php'; ?>
    <?php if ($lgFav = logo_url()): ?>
    <link rel="icon" href="<?= sanitize($lgFav) ?>">
    <?php endif; ?>
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
    <!-- Alpine.js for smooth micro-interactions -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .font-heading { font-family: 'Bricolage Grotesque', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 font-sans antialiased selection:bg-pepsi-blue selection:text-white" x-data="{ mobileMenu: false }">

    <!-- Top Announcement Bar -->
    <div class="bg-pepsi-navy text-white text-xs sm:text-sm py-2 px-4 text-center flex items-center justify-center gap-2 font-medium">
        <span class="inline-block w-2 h-2 rounded-full bg-pepsi-red animate-ping"></span>
        <span>Pendaftaran Kelas Baru Periode <b><?= sanitize($activePeriod['name'] ?? 'Tgl 10 & 25 Tiap Bulan') ?></b> Resmi Dibuka!</span>
        <a href="#kalkulator" class="underline hover:text-pepsi-red transition font-semibold ml-2">Hitung Biaya & Daftar &rarr;</a>
    </div>

    <!-- Main Navigation Bar -->
    <header class="sticky top-0 z-40 bg-white/90 backdrop-blur-md border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <!-- Brand Logo -->
                <a href="<?= url('/') ?>" class="flex items-center gap-3">
                    <?php if ($lg = logo_url()): ?>
                    <img src="<?= sanitize($lg) ?>" alt="<?= sanitize($settings['app_name'] ?? APP_NAME) ?>"
                         class="h-11 sm:h-12 w-auto max-w-[45vw] object-contain" style="image-rendering:auto">
                    <?php else: ?>
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-pepsi-blue to-pepsi-red flex items-center justify-center text-white font-heading font-black text-xl shadow-md shadow-blue-900/20">
                        MP
                    </div>
                    <div>
                        <span class="font-heading font-extrabold text-xl tracking-tight text-pepsi-navy block leading-none">MR.PEPSI</span>
                        <span class="text-xs font-bold text-pepsi-red tracking-widest uppercase">UPGRADE PARE</span>
                    </div>
                    <?php endif; ?>
                </a>

                <!-- Desktop Nav -->
                <nav class="hidden md:flex items-center gap-8 font-semibold text-sm text-slate-700">
                    <a href="#program" class="hover:text-pepsi-blue transition py-1">Katalog Program</a>
                    <a href="#jadwal" class="hover:text-pepsi-blue transition py-1">Jadwal & Ruangan</a>
                    <a href="#kalkulator" class="hover:text-pepsi-blue transition py-1">Kalkulator Biaya</a>
                    <a href="#daftar" class="hover:text-pepsi-blue transition py-1">Daftar via WA</a>
                    <a href="#faq" class="hover:text-pepsi-blue transition py-1">Aturan & FAQ</a>
                </nav>

                <!-- Actions -->
                <div class="hidden md:flex items-center gap-3">
                    <a href="<?= url('login') ?>" class="text-xs font-bold text-slate-600 hover:text-pepsi-navy px-3 py-2 rounded-lg hover:bg-slate-100 transition">
                        Portal Staf
                    </a>
                    <a href="https://wa.me/<?= sanitize($settings['admin_wa'] ?? ADMIN_PHONE) ?>?text=Halo%20Admin%20MR.PEPSI%20UPGRADE,%20saya%20ingin%20konsultasi%20program%20belajar" target="_blank" class="bg-pepsi-wa hover:bg-pepsi-wa-dark text-white font-bold text-sm px-5 py-2.5 rounded-xl flex items-center gap-2 shadow-lg shadow-green-500/25 transition-all transform hover:-translate-y-0.5">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.007c.106.005.249-.04.39.299.144.347.491 1.199.534 1.286.043.087.072.188.014.303-.058.116-.087.188-.173.289l-.26.302c-.087.087-.179.182-.077.357.101.174.45 1.084 1.144 1.702.89.794 1.64 1.04 1.871 1.155.231.116.368.101.505-.058.137-.159.587-.683.743-.918.156-.235.312-.196.526-.117.214.079 1.36.642 1.594.757.234.115.39.173.448.271.058.098.058.568-.086.973z"/></svg>
                        <span>Chat Admin</span>
                    </a>
                </div>

                <!-- Mobile Hamburger Button -->
                <div class="flex items-center gap-2 md:hidden">
                    <a href="https://wa.me/<?= sanitize($settings['admin_wa'] ?? ADMIN_PHONE) ?>" target="_blank" class="p-2 rounded-lg bg-green-50 text-pepsi-wa">
                        <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.007c.106.005.249-.04.39.299.144.347.491 1.199.534 1.286.043.087.072.188.014.303-.058.116-.087.188-.173.289l-.26.302c-.087.087-.179.182-.077.357.101.174.45 1.084 1.144 1.702.89.794 1.64 1.04 1.871 1.155.231.116.368.101.505-.058.137-.159.587-.683.743-.918.156-.235.312-.196.526-.117.214.079 1.36.642 1.594.757.234.115.39.173.448.271.058.098.058.568-.086.973z"/></svg>
                    </a>
                    <button @click="mobileMenu = !mobileMenu" class="p-2.5 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-100 focus:outline-none">
                        <svg x-show="!mobileMenu" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        <svg x-show="mobileMenu" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Drawer -->
        <div x-show="mobileMenu" x-cloak class="md:hidden border-b border-slate-200 bg-white px-4 pt-2 pb-6 space-y-3">
            <a @click="mobileMenu = false" href="#program" class="block font-semibold text-slate-800 px-3 py-2 rounded-lg hover:bg-slate-50">Katalog Program & Silabus</a>
            <a @click="mobileMenu = false" href="#jadwal" class="block font-semibold text-slate-800 px-3 py-2 rounded-lg hover:bg-slate-50">Jadwal Kelas & Ruangan</a>
            <a @click="mobileMenu = false" href="#kalkulator" class="block font-semibold text-slate-800 px-3 py-2 rounded-lg hover:bg-slate-50">Kalkulator Biaya Paket</a>
            <a @click="mobileMenu = false" href="#daftar" class="block font-semibold text-slate-800 px-3 py-2 rounded-lg hover:bg-slate-50">Pendaftaran via WhatsApp</a>
            <a @click="mobileMenu = false" href="#faq" class="block font-semibold text-slate-800 px-3 py-2 rounded-lg hover:bg-slate-50">Aturan & Info Kursus</a>
            <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                <a href="<?= url('login') ?>" class="text-xs font-bold text-slate-600 py-2">Login Staf / Admin</a>
                <a href="https://wa.me/<?= sanitize($settings['admin_wa'] ?? ADMIN_PHONE) ?>" class="bg-pepsi-wa text-white text-xs font-bold px-4 py-2 rounded-lg">WA CS Admin</a>
            </div>
        </div>
    </header>

    <!-- Page Content -->
    <main>
        <?= $content ?>
    </main>

    <!-- Footer -->
    <footer id="faq" class="bg-pepsi-navy text-white pt-16 pb-12 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-10">
                <!-- Col 1 -->
                <div class="md:col-span-1 space-y-4">
                    <div class="flex items-center gap-3">
                        <?php if ($lgFoot = logo_url()): ?>
                        <span class="shrink-0 rounded-xl bg-white p-1.5 grid place-items-center">
                            <img src="<?= sanitize($lgFoot) ?>" alt="<?= sanitize($settings['app_name'] ?? APP_NAME) ?>" class="h-9 w-auto max-w-[170px] object-contain">
                        </span>
                        <?php else: ?>
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-pepsi-blue to-pepsi-red flex items-center justify-center text-white font-heading font-black text-lg">
                            MP
                        </div>
                        <span class="font-heading font-extrabold text-lg tracking-tight">MR.PEPSI UPGRADE</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Lembaga pelatihan Bahasa Inggris dan persiapan IELTS terpercaya di Kampung Inggris Pare, Kediri. Mengutamakan kepercayaan diri berbicara dan hasil nyata.
                    </p>
                    <div class="text-xs text-slate-400 bg-slate-800/80 p-3 rounded-xl border border-slate-700/80 space-y-1">
                        <p class="font-semibold text-slate-200"><?= sanitize($settings['sk_diknas'] ?? 'SK Diknas No. 421.9/3735/418.20/2026') ?></p>
                        <p><?= sanitize($settings['npsn'] ?? 'NPSN K9997239') ?></p>
                    </div>
                </div>

                <!-- Col 2: Alamat -->
                <div class="space-y-3">
                    <h4 class="font-heading font-bold text-base text-white tracking-wide">Lokasi Kampus</h4>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        <?= nl2br(sanitize($settings['address'] ?? 'Jl. Kemuning, Tulungrejo, Kode Pos 64212, Pare, Kediri, Jawa Timur')) ?>
                    </p>
                    <a href="https://maps.google.com" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-semibold text-pepsi-blue hover:text-white transition">
                        <span>Buka di Google Maps</span> &rarr;
                    </a>
                </div>

                <!-- Col 3: Jam Operasional -->
                <div class="space-y-3">
                    <h4 class="font-heading font-bold text-base text-white tracking-wide">Jam Operasional Kantor</h4>
                    <div class="text-slate-400 text-sm leading-relaxed space-y-1">
                        <?= nl2br(sanitize($settings['office_hours'] ?? "Senin - Jumat: 07.00 - 16.00 WIB\nSabtu: 08.00 - 14.00 WIB\nIstirahat: 12.00 - 13.00 WIB")) ?>
                    </div>
                </div>

                <!-- Col 4: Kontak & Medsos -->
                <div class="space-y-3">
                    <h4 class="font-heading font-bold text-base text-white tracking-wide">Hubungi Kami</h4>
                    <ul class="text-slate-400 text-sm space-y-2">
                        <li>
                            <a href="https://wa.me/<?= sanitize($settings['admin_wa'] ?? ADMIN_PHONE) ?>" target="_blank" class="hover:text-green-400 transition flex items-center gap-2">
                                <span class="text-green-500 font-bold">&bull;</span> WhatsApp: <?= sanitize($settings['admin_wa'] ?? ADMIN_PHONE) ?>
                            </a>
                        </li>
                        <li>
                            <a href="https://instagram.com/mrpepsiofficial01" target="_blank" class="hover:text-pink-400 transition flex items-center gap-2">
                                <span class="text-pink-500 font-bold">&bull;</span> Instagram: <?= sanitize($settings['instagram'] ?? '@mrpepsiofficial01') ?>
                            </a>
                        </li>
                        <li>
                            <a href="mailto:<?= sanitize($settings['email'] ?? 'mrpepsikampunginggris@gmail.com') ?>" class="hover:text-white transition flex items-center gap-2">
                                <span class="text-blue-500 font-bold">&bull;</span> <?= sanitize($settings['email'] ?? 'mrpepsikampunginggris@gmail.com') ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-12 pt-8 border-t border-slate-800 text-xs text-slate-400 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p>&copy; <?= date('Y') ?> MR.PEPSI UPGRADE Kampung Inggris Pare. Hak Cipta Dilindungi Undang-Undang.</p>
                <div class="flex items-center gap-6">
                    <a href="<?= url('login') ?>" class="hover:text-white transition">Masuk Staf / Kasir</a>
                    <span>Privasi & Kebijakan Kelas</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Floating Action WhatsApp Button (Mobile Android Friendly) -->
    <a href="https://wa.me/<?= sanitize($settings['admin_wa'] ?? ADMIN_PHONE) ?>?text=Halo%20Admin%20MR.PEPSI%20UPGRADE,%20saya%20tertarik%20dengan%20program%20kursus%20di%20Kampung%20Inggris" 
       target="_blank" 
       class="fixed bottom-6 right-6 z-50 flex items-center gap-2 bg-pepsi-wa hover:bg-pepsi-wa-dark text-white px-4 py-3 rounded-full shadow-2xl shadow-green-600/50 hover:scale-105 active:scale-95 transition-all font-bold text-sm">
        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.007c.106.005.249-.04.39.299.144.347.491 1.199.534 1.286.043.087.072.188.014.303-.058.116-.087.188-.173.289l-.26.302c-.087.087-.179.182-.077.357.101.174.45 1.084 1.144 1.702.89.794 1.64 1.04 1.871 1.155.231.116.368.101.505-.058.137-.159.587-.683.743-.918.156-.235.312-.196.526-.117.214.079 1.36.642 1.594.757.234.115.39.173.448.271.058.098.058.568-.086.973z"/></svg>
        <span class="hidden sm:inline">Tanya CS Admin</span>
    </a>

    <?php require __DIR__ . '/../partials/pwa_script.php'; ?>

</body>
</html>
