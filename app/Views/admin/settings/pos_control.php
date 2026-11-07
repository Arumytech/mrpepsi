<?php
// Admin: Kontrol Operasional POS — buka/tutup mesin kasir.
$closedDate  = trim($settings['pos_closed_date'] ?? '');
$posClosed   = $closedDate === date('Y-m-d');
$closedLabel = $closedDate !== '' ? date('d M Y', strtotime($closedDate)) : '-';
$reopenLabel = date('d M Y', strtotime('+1 day'));
?>
<div class="space-y-6 max-w-3xl">

    <div>
        <h1 class="font-heading font-black text-2xl sm:text-3xl text-pepsi-navy">Kontrol Operasional POS</h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-1">
            Buka atau tutup mesin kasir POS Pepsi Food &amp; Drink secara manual.
        </p>
    </div>

    <!-- Status -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
        <div class="p-5 rounded-2xl border flex items-center gap-4 <?= $posClosed ? 'bg-red-50 border-red-200' : 'bg-green-50 border-green-200' ?>">
            <span class="w-14 h-14 rounded-3xl grid place-items-center text-2xl shrink-0 <?= $posClosed ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-700' ?>">
                <?= $posClosed ? '🔒' : '🟢' ?>
            </span>
            <div>
                <span class="block font-heading font-black text-lg <?= $posClosed ? 'text-red-700' : 'text-green-800' ?>">
                    POS <?= $posClosed ? 'TERTUTUP' : 'TERBUKA' ?> Hari Ini
                </span>
                <span class="block text-xs mt-0.5 <?= $posClosed ? 'text-red-600' : 'text-green-700' ?>">
                    <?= $posClosed
                        ? 'Ditutup pada ' . sanitize($closedLabel) . '. Terbuka otomatis ' . sanitize($reopenLabel) . '.'
                        : 'Kasir dapat melakukan transaksi sekarang.' ?>
                </span>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row items-center gap-3">
            <?php if ($posClosed): ?>
            <form action="<?= url('admin/settings/pos-open') ?>" method="POST" class="w-full sm:w-auto"
                  onsubmit="return confirm('Buka kembali POS sekarang? Kasir dapat bertransaksi lagi hari ini.')">
                <button type="submit" class="w-full px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-2xl shadow-md transition">
                    Buka POS Sekarang
                </button>
            </form>
            <?php else: ?>
            <form action="<?= url('admin/settings/pos-close') ?>" method="POST" class="w-full sm:w-auto"
                  onsubmit="return confirm('Tutup POS untuk hari ini?\n\nKasir tidak dapat bertransaksi sampai besok atau sampai Anda membukanya kembali.')">
                <button type="submit" class="w-full px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded-2xl shadow-md transition">
                    Tutup POS Hari Ini
                </button>
            </form>
            <?php endif; ?>

            <a href="<?= url('pos') ?>" target="_blank" class="w-full sm:w-auto text-center px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-2xl transition">
                Buka Mesin POS &rarr;
            </a>
        </div>

        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-[11px] text-slate-500 leading-relaxed">
            <p class="font-bold text-slate-600 mb-1">Cara kerja</p>
            <ul class="list-disc list-inside space-y-0.5">
                <li>Kasir dapat menutup POS sendiri lewat tombol <b>&ldquo;Tutup Transaksi&rdquo;</b> di layar POS.</li>
                <li>Selama ditutup, kasir tidak dapat membuka POS maupun memproses transaksi.</li>
                <li>Penutupan hanya berlaku untuk hari ini; POS otomatis terbuka kembali esok hari.</li>
                <li>Admin dapat membuka kembali POS kapan saja sebelum hari berakhir.</li>
            </ul>
        </div>
    </div>

</div>
