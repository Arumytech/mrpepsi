<?php
// Cashier Shift & Cash Reconciliation View
?>
<div class="p-6 max-w-5xl mx-auto w-full space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-heading font-black text-2xl sm:text-3xl text-pepsi-navy">Rekap Shift & Kas Operasional</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Laporan uang masuk di akhir jam kerja/shift kasir, pencatatan modal awal kasir, dan rekonsiliasi selisih kas.
            </p>
        </div>
        <a href="<?= url('pos') ?>" class="px-4 py-2 bg-pepsi-navy text-white rounded-xl text-xs font-bold hover:bg-slate-800 transition">
            &larr; Kembali ke Mesin POS
        </a>
    </div>

    <!-- Active Shift Status Card -->
    <?php if ($currentShift): ?>
    <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-sm space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 inline-flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-green-600 animate-ping"></span>
                    <span>Shift Sedang Aktif</span>
                </span>
                <h3 class="font-heading font-black text-xl text-slate-900 mt-2">
                    Shift Dibuka Sejak: <?= date('d M Y, H:i', strtotime($currentShift['start_time'])) ?> WIB
                </h3>
            </div>
            <div class="text-right">
                <span class="text-xs text-slate-400 block">Kasir Bertugas</span>
                <span class="font-bold text-slate-800 text-sm"><?= sanitize($_SESSION['user']['name']) ?></span>
            </div>
        </div>

        <!-- Metric Tally for Current Shift -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                <span class="text-slate-400 block font-semibold">Modal Awal:</span>
                <span class="font-heading font-black text-lg text-slate-900 block mt-1"><?= formatRupiah($shiftTotals['starting_cash']) ?></span>
            </div>
            <div class="p-4 rounded-2xl bg-blue-50/60 border border-blue-100">
                <span class="text-slate-500 block font-semibold">Penjualan Tunai &amp; Cash:</span>
                <span class="font-heading font-black text-lg text-pepsi-blue block mt-1"><?= formatRupiah($shiftTotals['total_cash_sales']) ?></span>
                <span class="text-[10px] text-slate-400"><?= $shiftTotals['total_transactions'] ?> Transaksi</span>
            </div>
            <div class="p-4 rounded-2xl bg-purple-50 border border-purple-100">
                <span class="text-purple-700 block font-semibold">Penjualan QRIS:</span>
                <span class="font-heading font-black text-lg text-purple-700 block mt-1"><?= formatRupiah($shiftTotals['total_qris_sales'] ?? 0) ?></span>
                <span class="text-[10px] text-slate-400">Total: <?= formatRupiah($shiftTotals['total_sales']) ?></span>
            </div>
            <div class="p-4 rounded-2xl bg-green-50 border border-green-200">
                <span class="text-green-800 block font-bold">Total Fisik Kas Seharusnya:</span>
                <span class="font-heading font-black text-xl text-green-800 block mt-1"><?= formatRupiah($shiftTotals['expected_cash']) ?></span>
                <span class="text-[10px] text-green-600 font-semibold">(Modal + Penjualan Cash)</span>
            </div>
        </div>

        <!-- Form Close Shift -->
        <div class="pt-4 border-t border-slate-100 bg-slate-50 p-6 rounded-2xl">
            <h4 class="font-heading font-bold text-sm text-slate-900 mb-3">Tutup Shift & Serah Terima Kas Akhir</h4>
            <form action="<?= url('pos/shift/close') ?>" method="POST" class="space-y-4 text-xs" onsubmit="return confirm('Apakah Anda yakin ingin menutup shift ini?')">
                <input type="hidden" name="shift_id" value="<?= $currentShift['id'] ?>">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Total Uang Fisik di Laci Kasir (Hitung Nyata) *</label>
                        <input type="number" name="actual_cash" required placeholder="0" class="w-full text-base font-black rounded-xl border border-slate-300 p-3 bg-white text-pepsi-navy">
                        <span class="text-[10px] text-slate-400 mt-1 block">Masukkan jumlah uang kertas & koin fisik yang ada di laci kasir saat tutup shift.</span>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Catatan Penutupan / Serah Terima</label>
                        <textarea name="closing_notes" rows="3" placeholder="Catatan jika ada uang receh tambahan / selisih kas..." class="w-full rounded-xl border border-slate-300 p-2.5 bg-white"></textarea>
                    </div>
                </div>

                <button type="submit" class="w-full py-3.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl transition shadow-md flex items-center justify-center gap-2">
                    <span>TUTUP SHIFT KASIR & CETAK REKAP SERAH TERIMA</span>
                </button>
            </form>
        </div>
    </div>

    <?php else: ?>
    <!-- Open New Shift Card -->
    <div class="bg-white rounded-3xl border border-slate-200 p-8 shadow-sm text-center max-w-md mx-auto space-y-4">
        <div class="w-16 h-16 rounded-3xl bg-amber-100 text-amber-800 flex items-center justify-center mx-auto text-2xl font-bold">
            🔒
        </div>
        <div>
            <h3 class="font-heading font-black text-xl text-slate-900">Shift Belum Dibuka</h3>
            <p class="text-xs text-slate-500 mt-1">Masukkan modal awal uang kembalian untuk membuka jam operasional kasir baru.</p>
        </div>

        <form action="<?= url('pos/shift/open') ?>" method="POST" class="space-y-4 text-xs text-left">
            <div>
                <label class="block font-bold text-slate-700 mb-1">Modal Awal Kasir (Uang Pecahan / Kembalian) *</label>
                <input type="number" name="starting_cash" required value="100000" placeholder="100000" class="w-full text-base font-black rounded-xl border border-slate-300 p-3 bg-slate-50 text-pepsi-blue">
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Catatan Shift</label>
                <input type="text" name="notes" placeholder="Shift Pagi / Shift Sore" class="w-full rounded-xl border border-slate-300 p-2.5 bg-slate-50">
            </div>

            <button type="submit" class="w-full py-3.5 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl transition shadow-md">
                Buka Shift Kasir Baru
            </button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Shift History Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 space-y-4">
        <h3 class="font-heading font-bold text-base text-slate-900">Riwayat Shift Kasir Terakhir</h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-medium">
                <thead class="bg-slate-50 text-slate-500 text-[10px] uppercase font-bold">
                    <tr>
                        <th class="px-4 py-3">Kasir</th>
                        <th class="px-4 py-3">Buka Shift</th>
                        <th class="px-4 py-3">Tutup Shift</th>
                        <th class="px-4 py-3">Modal Awal</th>
                        <th class="px-4 py-3">Penjualan Cash</th>
                        <th class="px-4 py-3">Penjualan QRIS</th>
                        <th class="px-4 py-3">Uang Fisik Kas</th>
                        <th class="px-4 py-3">Selisih Kas</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($history)): ?>
                    <tr><td colspan="9" class="p-6 text-center text-slate-400 italic">Belum ada riwayat shift.</td></tr>
                    <?php else: ?>
                    <?php foreach ($history as $h): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-bold text-slate-900"><?= sanitize($h['cashier_name']) ?></td>
                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap"><?= date('d/m H:i', strtotime($h['start_time'])) ?></td>
                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap"><?= $h['end_time'] ? date('d/m H:i', strtotime($h['end_time'])) : '-' ?></td>
                        <td class="px-4 py-3"><?= formatRupiah($h['starting_cash']) ?></td>
                        <td class="px-4 py-3 font-bold text-pepsi-blue"><?= formatRupiah($h['total_cash']) ?></td>
                        <td class="px-4 py-3 font-bold text-purple-700"><?= formatRupiah($h['total_qris'] ?? 0) ?></td>
                        <td class="px-4 py-3 font-bold text-slate-900"><?= formatRupiah($h['actual_cash']) ?></td>
                        <td class="px-4 py-3 font-bold <?= $h['cash_difference'] < 0 ? 'text-red-600' : ($h['cash_difference'] > 0 ? 'text-green-600' : 'text-slate-500') ?>">
                            <?= formatRupiah($h['cash_difference']) ?>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold <?= $h['status'] === 'open' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500' ?>">
                                <?= strtoupper($h['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
