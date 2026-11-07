<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require __DIR__ . '/../partials/pwa_head.php'; ?>
    <title>POS Kasir Pepsi Food &amp; Drink - <?= sanitize($settings['app_name'] ?? APP_NAME) ?></title>
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
                            red: '#E3262E'
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
        [x-cloak] { display: none !important; }
        .font-heading { font-family: 'Bricolage Grotesque', sans-serif; }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 font-sans antialiased h-screen max-h-screen overflow-hidden flex flex-col">

    <!-- Top POS Navigation Bar -->
    <header class="bg-pepsi-navy text-white h-16 shrink-0 flex items-center justify-between px-4 sm:px-6 shadow-md z-30">
        <div class="flex items-center gap-3">
            <a href="<?= url('pos') ?>" class="flex items-center gap-2">
                <?php if (logo_img()): ?>
                <span class="w-9 h-9 rounded-xl bg-white grid place-items-center overflow-hidden p-1 shrink-0">
                    <?= logo_img('max-w-full max-h-full object-contain') ?>
                </span>
                <?php else: ?>
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-pepsi-blue to-pepsi-red flex items-center justify-center font-heading font-black text-sm">
                    MP
                </div>
                <?php endif; ?>
                <span class="font-heading font-extrabold text-base tracking-tight">KASIR PEPSI FOOD &amp; DRINK</span>
            </a>
            <span class="hidden sm:inline px-2.5 py-0.5 rounded-full bg-slate-800 text-slate-300 text-[11px] font-semibold border border-slate-700">
                Operasional Cepat
            </span>
        </div>

        <!-- Center / Right Status -->
        <div class="flex items-center gap-3 text-xs">
            <?php if (($_SESSION['user']['role'] ?? '') === 'admin'): ?>
            <a href="<?= url('admin') ?>" class="px-3 py-1.5 rounded-xl bg-blue-900 hover:bg-blue-800 text-blue-200 font-bold transition">
                &larr; Ke Admin Utama
            </a>
            <?php endif; ?>

            <div class="hidden md:flex items-center gap-2 pl-2 border-l border-slate-800">
                <span class="font-bold text-slate-300"><?= sanitize($_SESSION['user']['name'] ?? 'Kasir') ?></span>
                <a href="<?= url('logout') ?>" class="text-slate-400 hover:text-red-400 p-1" title="Logout">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </a>
            </div>
        </div>
    </header>

    <!-- Flash Message -->
    <?php $flashError = $this->getFlash('error'); $flashSuccess = $this->getFlash('success'); ?>
    <?php if ($flashError || $flashSuccess): ?>
    <div class="fixed top-20 left-1/2 -translate-x-1/2 z-[60] px-4 w-full max-w-md" id="pos-flash">
        <div class="p-3.5 rounded-2xl text-xs font-bold shadow-lg flex items-center gap-2 <?= $flashError ? 'bg-red-50 border border-red-200 text-red-800' : 'bg-green-50 border border-green-200 text-green-800' ?>">
            <span><?= $flashError ? '&#9888;&#65039;' : '&#9989;' ?></span>
            <span><?= sanitize($flashError ?: $flashSuccess) ?></span>
        </div>
    </div>
    <script>setTimeout(function () { var e = document.getElementById('pos-flash'); if (e) e.remove(); }, 4000);</script>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col min-h-0 overflow-hidden">
        <?= $content ?>
    </main>

    <?php require __DIR__ . '/../partials/pwa_script.php'; ?>

</body>
</html>
