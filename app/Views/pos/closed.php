<?php
// Layar POS terkunci: transaksi ditutup untuk hari ini, otomatis terbuka besok.
$closedDate = trim($settings['pos_closed_date'] ?? '');
$closedLabel = $closedDate !== '' ? date('d M Y', strtotime($closedDate)) : date('d M Y');
$reopenLabel = date('d M Y', strtotime('+1 day'));
$isAdmin = ($_SESSION['user']['role'] ?? '') === 'admin' || ($_SESSION['user']['role'] ?? '') === 'owner';
?>
<div class="flex-1 grid place-items-center p-6">
    <div class="max-w-md w-full bg-white rounded-3xl border border-slate-200 shadow-sm p-8 text-center space-y-5">

        <div class="w-16 h-16 rounded-3xl bg-red-100 text-red-600 grid place-items-center mx-auto text-3xl">
            🔒
        </div>

        <div class="space-y-1">
            <h1 class="font-heading font-black text-2xl text-pepsi-navy">POS Ditutup untuk Hari Ini</h1>
            <p class="text-xs text-slate-500">
                Transaksi kasir telah ditutup. Mesin POS tidak dapat digunakan sampai besok,
                lalu akan otomatis terbuka kembali.
            </p>
        </div>

        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs space-y-2 text-left">
            <div class="flex justify-between">
                <span class="text-slate-500">Status</span>
                <span class="font-bold text-red-600 uppercase">Tertutup</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Ditutup</span>
                <span class="font-bold text-slate-800"><?= sanitize($closedLabel) ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Buka Otomatis</span>
                <span class="font-bold text-green-700"><?= sanitize($reopenLabel) ?></span>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-2 pt-1">
            <a href="<?= url('pos/shift') ?>" class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition">
                Lihat Rekap Shift
            </a>

            <?php if ($isAdmin): ?>
            <form action="<?= url('admin/settings/pos-open') ?>" method="POST" class="w-full sm:w-auto"
                  onsubmit="return confirm('Buka kembali POS sekarang? Kasir dapat bertransaksi lagi hari ini.')">
                <button type="submit" class="w-full px-5 py-2.5 rounded-xl bg-green-600 hover:bg-green-700 text-white font-bold text-xs shadow-md transition">
                    Buka POS Sekarang (Admin)
                </button>
            </form>
            <?php endif; ?>
        </div>

        <?php if ($isAdmin): ?>
        <p class="text-[10px] text-slate-400">
            Admin juga dapat membuka/menutup POS dari menu
            <a href="<?= url('admin/settings') ?>" class="font-bold text-pepsi-blue hover:underline">Pengaturan Umum</a>.
        </p>
        <?php endif; ?>
    </div>
</div>
