<?php
// Admin Stock Opname & Stock Logs View
?>
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-heading font-black text-2xl sm:text-3xl text-pepsi-navy">Stok Opname &amp; Audit Pepsi Food &amp; Drink</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Catat barang masuk beserta harga beli (metode <b>FIFO</b>), sesuaikan stok fisik gudang, dan pantau riwayat mutasi barang.
            </p>
        </div>
        <a href="<?= url('admin/canteen') ?>" class="text-xs font-bold text-pepsi-blue hover:underline">
            &larr; Kembali ke Master Produk
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Kolom Kiri: Barang Masuk & Penyesuaian Stok -->
        <div class="lg:col-span-5 space-y-6">

            <!-- Form Barang Masuk / Restock (FIFO) -->
            <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm space-y-4">
                <h3 class="font-heading font-bold text-base text-slate-900 border-b border-slate-100 pb-3">
                    Barang Masuk / Restock
                </h3>
                <p class="text-[11px] text-slate-500 leading-relaxed">
                    Catat pembelian stok baru beserta harga belinya. Sistem menyimpan harga beli tiap pembelian sebagai <b>batch</b>, dan HPP penjualan dihitung <b>FIFO</b> (batch tertua dikeluarkan dulu).
                </p>

                <form action="<?= url('admin/canteen/restock') ?>" method="POST" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Pilih Produk *</label>
                        <select name="product_id" required onchange="fillRestockCost(this)" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-semibold">
                            <option value="">-- Pilih Produk --</option>
                            <?php foreach ($products as $p): ?>
                            <option value="<?= $p['id'] ?>" data-cost="<?= (float)$p['cost_price'] ?>">
                                <?= sanitize($p['name']) ?> (Stok: <?= $p['stock'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Jumlah Masuk *</label>
                            <input type="number" name="quantity" min="1" required placeholder="0" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-black text-base text-pepsi-blue">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Harga Beli / Unit (Rp) *</label>
                            <input type="number" name="unit_cost" id="restock-cost" min="0" required placeholder="0" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Catatan / Toko Pembelian</label>
                        <input type="text" name="notes" placeholder="Contoh: Beli di Toko Sinar Abadi" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                    </div>

                    <button type="submit" class="w-full py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl transition shadow-md">
                        Simpan Barang Masuk
                    </button>
                </form>
            </div>

            <!-- Form Penyesuaian Stok Opname -->
            <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm space-y-4">
                <h3 class="font-heading font-bold text-base text-slate-900 border-b border-slate-100 pb-3">
                    Input Penyesuaian Stok Fisik
                </h3>

                <form action="<?= url('admin/canteen/opname') ?>" method="POST" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Pilih Produk *</label>
                        <select name="product_id" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-semibold">
                            <option value="">-- Pilih Produk --</option>
                            <?php foreach ($products as $p): ?>
                            <option value="<?= $p['id'] ?>">
                                <?= sanitize($p['name']) ?> (Sistem: <?= $p['stock'] ?> pcs)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Jumlah Stok Fisik Nyata (Hasil Hitung) *</label>
                        <input type="number" name="actual_stock" min="0" required placeholder="0" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-black text-base text-pepsi-blue">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Alasan / Catatan Penyesuaian</label>
                        <textarea name="notes" rows="2" placeholder="Contoh: Stok opname akhir bulan / barang rusak / salah hitung" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50"></textarea>
                    </div>

                    <button type="submit" class="w-full py-3 bg-pepsi-blue hover:bg-blue-900 text-white font-bold rounded-xl transition shadow-md">
                        Simpan Penyesuaian Stok Opname
                    </button>
                </form>
            </div>
        </div>

        <!-- Riwayat Log Mutasi Stok -->
        <div class="lg:col-span-7 bg-white rounded-3xl border border-slate-200 p-6 shadow-sm space-y-4">
            <h3 class="font-heading font-bold text-base text-slate-900 border-b border-slate-100 pb-3">
                Riwayat Aktivitas &amp; Mutasi Stok Terakhir
            </h3>

            <div class="overflow-x-auto max-h-[560px]">
                <table class="w-full text-left text-xs font-medium">
                    <thead class="bg-slate-50 text-slate-500 text-[10px] uppercase font-bold sticky top-0">
                        <tr>
                            <th class="px-4 py-2.5">Waktu</th>
                            <th class="px-4 py-2.5">Produk</th>
                            <th class="px-4 py-2.5">Tipe</th>
                            <th class="px-4 py-2.5">Perubahan</th>
                            <th class="px-4 py-2.5">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($logs)): ?>
                        <tr><td colspan="5" class="p-4 text-center text-slate-400 italic">Belum ada riwayat stok.</td></tr>
                        <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-2.5 whitespace-nowrap text-slate-400 text-[11px]">
                                <?= date('d/m H:i', strtotime($log['created_at'])) ?>
                            </td>
                            <td class="px-4 py-2.5 font-bold text-slate-900">
                                <?= sanitize($log['product_name']) ?>
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap">
                                <?php if ($log['type'] === 'sale'): ?>
                                    <span class="px-2 py-0.5 rounded bg-blue-50 text-pepsi-blue font-bold text-[10px]">Terjual</span>
                                <?php elseif ($log['type'] === 'in'): ?>
                                    <span class="px-2 py-0.5 rounded bg-green-50 text-green-700 font-bold text-[10px]">Masuk</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-800 font-bold text-[10px]">Opname</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap font-bold text-slate-800">
                                <?= $log['old_stock'] ?> &rarr; <?= $log['new_stock'] ?>
                            </td>
                            <td class="px-4 py-2.5 text-slate-500 text-[11px]">
                                <?= sanitize($log['notes'] ?? '-') ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Batch Stok Aktif (FIFO) -->
    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="font-heading font-bold text-base text-slate-900">
                Batch Stok Aktif (HPP FIFO)
            </h3>
            <span class="text-[11px] text-slate-400">Batch teratas dikeluarkan lebih dulu</span>
        </div>

        <div class="overflow-x-auto max-h-[520px]">
            <table class="w-full text-left text-xs font-medium">
                <thead class="bg-slate-50 text-slate-500 text-[10px] uppercase font-bold sticky top-0">
                    <tr>
                        <th class="px-4 py-2.5">Produk</th>
                        <th class="px-4 py-2.5">Sisa / Masuk</th>
                        <th class="px-4 py-2.5">Harga Beli / Unit</th>
                        <th class="px-4 py-2.5">Nilai Batch</th>
                        <th class="px-4 py-2.5">Sumber</th>
                        <th class="px-4 py-2.5">Tanggal Terima</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($batches)): ?>
                    <tr><td colspan="6" class="p-4 text-center text-slate-400 italic">Belum ada batch stok aktif.</td></tr>
                    <?php else: ?>
                    <?php foreach ($batches as $b):
                        $srcLabel = ['initial' => 'Stok Awal', 'restock' => 'Barang Masuk', 'opname' => 'Opname'][$b['source']] ?? $b['source'];
                        $srcClass = ['initial' => 'bg-slate-100 text-slate-600', 'restock' => 'bg-green-50 text-green-700', 'opname' => 'bg-amber-50 text-amber-800'][$b['source']] ?? 'bg-slate-100 text-slate-600';
                    ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-2.5">
                            <span class="font-bold text-slate-900 block"><?= sanitize($b['product_name']) ?></span>
                            <span class="text-[10px] text-slate-400 font-mono"><?= sanitize($b['barcode'] ?? 'No Barcode') ?></span>
                        </td>
                        <td class="px-4 py-2.5 whitespace-nowrap font-bold text-slate-800">
                            <?= (int)$b['qty_remaining'] ?> <span class="text-slate-400 font-normal">/ <?= (int)$b['qty_in'] ?> pcs</span>
                        </td>
                        <td class="px-4 py-2.5 whitespace-nowrap font-semibold text-slate-700">
                            <?= formatRupiah($b['unit_cost']) ?>
                        </td>
                        <td class="px-4 py-2.5 whitespace-nowrap font-bold text-slate-900">
                            <?= formatRupiah((float)$b['unit_cost'] * (int)$b['qty_remaining']) ?>
                        </td>
                        <td class="px-4 py-2.5 whitespace-nowrap">
                            <span class="px-2 py-0.5 rounded font-bold text-[10px] <?= $srcClass ?>"><?= $srcLabel ?></span>
                        </td>
                        <td class="px-4 py-2.5 whitespace-nowrap text-slate-500 text-[11px]">
                            <?= date('d/m/Y H:i', strtotime($b['created_at'])) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
function fillRestockCost(sel) {
    const opt = sel.options[sel.selectedIndex];
    const cost = opt ? opt.getAttribute('data-cost') : null;
    const input = document.getElementById('restock-cost');
    if (input && cost !== null && cost !== '' && parseFloat(cost) > 0) {
        input.value = Math.round(parseFloat(cost));
    }
}
</script>
