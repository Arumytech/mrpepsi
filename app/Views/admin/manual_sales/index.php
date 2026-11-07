<?php
// Input Penjualan Tambahan — pencatatan manual oleh Admin/Owner setelah kasir menutup POS.
$productOptions = [];
foreach ($products as $p) {
    $productOptions[] = [
        'id'    => (int)$p['id'],
        'name'  => (string)$p['name'],
        'price' => (float)$p['selling_price'],
    ];
}

$totalManual = 0;
foreach ($sales as $s) {
    $totalManual += (float)$s['total_amount'];
}
?>
<div class="space-y-6" x-data="manualSaleForm(<?= sanitize(json_encode($productOptions)) ?>)">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-heading font-black text-2xl sm:text-3xl text-pepsi-navy">Input Penjualan Tambahan</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Pencatatan transaksi yang terjadi setelah kasir menutup POS (khusus Admin / Owner).
                Tanggal penjualan bisa diisi mundur &mdash; otomatis masuk ke laporan laba rugi pada tanggal tersebut.
            </p>
        </div>

        <a href="<?= url('admin/canteen/report') ?>"
           class="px-4 py-2 bg-pepsi-blue hover:bg-blue-900 text-white font-bold text-xs rounded-xl shadow-sm transition text-center">
            Lihat Laporan Laba Rugi
        </a>
    </div>

    <!-- Penjelasan skema -->
    <div class="p-4 sm:p-5 rounded-3xl bg-amber-50 border border-amber-200 flex items-start gap-3">
        <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div class="text-xs text-amber-900 space-y-1">
            <p class="font-bold">Cara kerja pencatatan ini</p>
            <ul class="list-disc list-inside space-y-0.5">
                <li>Stok TIDAK ikut terpotong (barang sudah terjual sebelumnya).</li>
                <li>Pilih produk dari daftar untuk HPP otomatis, atau isi nama item sendiri (HPP dianggap Rp 0).</li>
                <li>Data bertanda <b>manual</b> ditampilkan terpisah di laporan sebagai &ldquo;Penjualan Tambahan&rdquo;.</li>
                <li>Penjualan tambahan bisa dihapus bila terinput keliru.</li>
            </ul>
        </div>
    </div>

    <!-- Form input -->
    <form action="<?= url('admin/manual-sales/store') ?>" method="POST" @submit="validate">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-100">
                <h3 class="font-heading font-bold text-sm text-slate-900">Form Entri Penjualan Tambahan</h3>
            </div>

            <div class="p-5 space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Tanggal Penjualan</label>
                        <input type="date" name="sale_date" value="<?= sanitize($defaultDate) ?>" required
                               class="w-full px-3.5 py-2.5 text-xs font-bold text-slate-800 bg-white rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-pepsi-blue/30">
                        <span class="block text-[10px] text-slate-400 mt-1">Default kemarin; bisa diubah mundur ke tanggal lain.</span>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Metode Pembayaran</label>
                        <select name="payment_method"
                                class="w-full px-3.5 py-2.5 text-xs font-bold text-slate-800 bg-white rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-pepsi-blue/30">
                            <option value="cash">Tunai / Cash</option>
                            <option value="qris">QRIS</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Catatan</label>
                        <input type="text" name="note" maxlength="255" placeholder="Mis. transaksi setelah tutup kas"
                               class="w-full px-3.5 py-2.5 text-xs font-bold text-slate-800 bg-white rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-pepsi-blue/30">
                    </div>
                </div>

                <!-- Item -->
                <div class="border border-slate-200 rounded-2xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-slate-500 uppercase font-bold text-[10px]">
                                <tr>
                                    <th class="px-3 py-2.5 w-44">Produk</th>
                                    <th class="px-3 py-2.5">Nama Item</th>
                                    <th class="px-3 py-2.5 w-24">Qty</th>
                                    <th class="px-3 py-2.5 w-36">Harga Jual</th>
                                    <th class="px-3 py-2.5 w-32 text-right">Subtotal</th>
                                    <th class="px-3 py-2.5 w-12"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="(item, i) in items" :key="i">
                                    <tr class="hover:bg-slate-50/70 align-top">
                                        <td class="px-3 py-2.5">
                                            <select :name="'items[' + i + '][product_id]'" x-model="item.product_id"
                                                    @change="pickProduct(i)"
                                                    class="w-full px-2.5 py-2 text-[11px] font-bold text-slate-700 bg-white rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-pepsi-blue/30">
                                                <option value="">— Item lain (isi manual) —</option>
                                                <template x-for="p in products" :key="p.id">
                                                    <option :value="p.id" x-text="p.name"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="px-3 py-2.5">
                                            <input type="text" :name="'items[' + i + '][name]'" x-model="item.name"
                                                   placeholder="Nama item (wajib bila bukan produk)"
                                                   class="w-full px-2.5 py-2 text-[11px] font-bold text-slate-800 bg-white rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-pepsi-blue/30">
                                        </td>
                                        <td class="px-3 py-2.5">
                                            <input type="number" :name="'items[' + i + '][quantity]'" x-model.number="item.quantity"
                                                   min="1" step="1" placeholder="1"
                                                   class="w-full px-2.5 py-2 text-[11px] font-bold text-slate-800 bg-white rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-pepsi-blue/30">
                                        </td>
                                        <td class="px-3 py-2.5">
                                            <input type="number" :name="'items[' + i + '][price]'" x-model.number="item.price"
                                                   min="0" step="any" placeholder="0"
                                                   class="w-full px-2.5 py-2 text-[11px] font-bold text-slate-800 bg-white rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-pepsi-blue/30">
                                        </td>
                                        <td class="px-3 py-2.5 text-right font-black text-slate-900 whitespace-nowrap"
                                            x-text="rupiah(lineTotal(item))"></td>
                                        <td class="px-3 py-2.5 text-center">
                                            <button type="button" @click="removeItem(i)" title="Hapus baris"
                                                    class="w-6 h-6 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition font-bold">&times;</button>
                                        </td>
                                    </tr>
                                </template>

                                <tr x-show="items.length === 0">
                                    <td colspan="6" class="px-3 py-5 text-center text-[11px] text-slate-400 italic">
                                        Belum ada item. Klik &ldquo;Tambah Item&rdquo; untuk mulai mengisi.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <button type="button" @click="addItem()"
                            class="px-4 py-2.5 bg-white border border-slate-200 hover:border-pepsi-blue text-pepsi-blue font-bold text-xs rounded-xl shadow-sm transition self-start">
                        + Tambah Item
                    </button>

                    <div class="flex items-center gap-4">
                        <span class="text-[11px] text-slate-500">
                            Total: <b class="text-pepsi-navy font-black text-sm" x-text="rupiah(grandTotal)"></b>
                            <span class="text-slate-400">(<span x-text="items.length"></span> baris)</span>
                        </span>
                        <button type="submit"
                                class="px-5 py-2.5 bg-pepsi-red hover:bg-red-700 text-white font-bold text-xs rounded-xl shadow-sm transition">
                            Simpan Penjualan Tambahan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Riwayat -->
    <div class="space-y-4">
        <form method="GET" action="<?= url('admin/manual-sales') ?>"
              class="flex flex-wrap items-end gap-3 bg-white rounded-2xl border border-slate-200 shadow-sm px-5 py-4">
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Dari Tanggal</label>
                <input type="date" name="start_date" value="<?= sanitize($startDate) ?>"
                       class="px-3 py-2 text-xs font-bold text-slate-800 bg-white rounded-xl border border-slate-200 focus:outline-none">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Sampai</label>
                <input type="date" name="end_date" value="<?= sanitize($endDate) ?>"
                       class="px-3 py-2 text-xs font-bold text-slate-800 bg-white rounded-xl border border-slate-200 focus:outline-none">
            </div>
            <button type="submit" class="px-4 py-2 bg-pepsi-blue hover:bg-blue-900 text-white font-bold text-xs rounded-xl shadow-sm transition">
                Filter
            </button>
            <span class="ml-auto text-[11px] text-slate-500">
                Total penjualan tambahan:
                <b class="text-amber-600 font-black"><?= formatRupiah($totalManual) ?></b>
                &middot; <span class="font-bold"><?= count($sales) ?></span> entri
            </span>
        </form>

        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-heading font-bold text-sm text-slate-900">Riwayat Penjualan Tambahan</h3>
                <span class="text-[10px] font-bold text-slate-400"><?= tanggalIndo($startDate, 'd M Y') ?> &mdash; <?= tanggalIndo($endDate, 'd M Y') ?></span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 uppercase font-bold text-[10px]">
                        <tr>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">No. Invoice</th>
                            <th class="px-4 py-3">Item</th>
                            <th class="px-4 py-3">Catatan</th>
                            <th class="px-4 py-3">Oleh</th>
                            <th class="px-4 py-3">Bayar</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($sales)): ?>
                        <tr><td colspan="8" class="px-4 py-6 text-center text-slate-400 italic">Belum ada penjualan tambahan pada rentang tanggal ini.</td></tr>
                        <?php else: ?>
                        <?php foreach ($sales as $s): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-bold text-slate-800 whitespace-nowrap"><?= tanggalIndo($s['created_at'], 'd M Y') ?></td>
                            <td class="px-4 py-3 font-mono text-[11px] text-pepsi-blue font-bold whitespace-nowrap"><?= sanitize($s['invoice_no']) ?></td>
                            <td class="px-4 py-3 text-slate-600 max-w-xs">
                                <?php
                                $itemLabels = [];
                                foreach ($s['items'] as $it) {
                                    $itemLabels[] = sanitize($it['product_name']) . ' &times;' . (int)$it['quantity'];
                                }
                                echo $itemLabels !== [] ? implode(', ', $itemLabels) : '&mdash;';
                                ?>
                            </td>
                            <td class="px-4 py-3 text-slate-500 max-w-[10rem] truncate"><?= $s['manual_note'] !== null ? sanitize($s['manual_note']) : '&mdash;' ?></td>
                            <td class="px-4 py-3 text-slate-600 whitespace-nowrap"><?= sanitize($s['admin_name'] ?? 'Admin') ?></td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full font-bold text-[10px] <?= $s['payment_method'] === 'qris' ? 'bg-pepsi-blue/10 text-pepsi-blue' : 'bg-slate-100 text-slate-600' ?>">
                                    <?= $s['payment_method'] === 'qris' ? 'QRIS' : 'Tunai' ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-black text-slate-900 whitespace-nowrap"><?= formatRupiah($s['total_amount']) ?></td>
                            <td class="px-4 py-3 text-center">
                                <form action="<?= url('admin/manual-sales/delete/' . (int)$s['id']) ?>" method="POST"
                                      onsubmit="return confirm('Hapus penjualan tambahan <?= sanitize($s['invoice_no']) ?> dari laporan?');">
                                    <button type="submit" title="Hapus"
                                            class="px-2.5 py-1.5 rounded-lg text-[10px] font-bold text-red-600 hover:bg-red-50 border border-red-100 transition">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
function manualSaleForm(products) {
    return {
        products: products || [],
        items: [{ product_id: '', name: '', quantity: 1, price: 0 }],

        addItem() {
            this.items.push({ product_id: '', name: '', quantity: 1, price: 0 });
        },
        removeItem(i) {
            this.items.splice(i, 1);
        },
        pickProduct(i) {
            const item = this.items[i];
            const id = Number(item.product_id);
            if (!id) return;
            const p = this.products.find(x => Number(x.id) === id);
            if (p) {
                item.name = p.name;
                item.price = Number(p.price) || 0;
            }
        },
        lineTotal(item) {
            return (Number(item.quantity) || 0) * (Number(item.price) || 0);
        },
        get grandTotal() {
            return this.items.reduce((sum, it) => sum + this.lineTotal(it), 0);
        },
        rupiah(v) {
            return 'Rp ' + Math.round(Number(v) || 0).toLocaleString('id-ID');
        },
        validate(e) {
            if (this.items.length === 0) {
                e.preventDefault();
                alert('Minimal isi satu item penjualan.');
                return;
            }

            const invalid = this.items.some(it => {
                const qty = Number(it.quantity) || 0;
                const name = String(it.name).trim();
                const hasProduct = Number(it.product_id) > 0;
                return qty < 1 || (!hasProduct && name === '') || Number(it.price) <= 0;
            });

            if (invalid) {
                e.preventDefault();
                alert('Lengkapi setiap baris: minimal qty 1, pilih produk atau isi nama item, dan harga jual lebih dari Rp 0.');
            }
        }
    };
}
</script>
