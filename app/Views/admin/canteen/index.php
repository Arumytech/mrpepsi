<?php
// Admin Canteen Master Products View
?>
<div x-data="{ addModal: false, editModal: false, editItem: {}, catModal: false, catEditId: 0, catName: '' }" class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-heading font-black text-2xl sm:text-3xl text-pepsi-navy">Master Produk Pepsi Food &amp; Drink</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Kelola data produk, barcode/SKU, modal/HPP, harga jual, stok saat ini, dan batas stok minimum.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="<?= url('admin/canteen/opname') ?>" class="bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs px-4 py-2.5 rounded-2xl transition">
                Stok Opname
            </a>
            <button @click="catModal = true; catEditId = 0; catName = ''" class="bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs px-4 py-2.5 rounded-2xl transition">
                Kelola Kategori
            </button>
            <a href="<?= url('admin/canteen/report') ?>" class="bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs px-4 py-2.5 rounded-2xl transition">
                Laporan Laba Rugi
            </a>
            <button @click="addModal = true" class="bg-pepsi-blue hover:bg-blue-900 text-white font-bold text-xs sm:text-sm px-5 py-2.5 rounded-2xl shadow-md flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Tambah Produk</span>
            </button>
        </div>
    </div>

    <!-- Products Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-medium">
                <thead class="bg-pepsi-navy text-white text-[11px] uppercase tracking-wider font-bold">
                    <tr>
                        <th class="px-5 py-4">Barcode & Nama Produk</th>
                        <th class="px-5 py-4">Kategori</th>
                        <th class="px-5 py-4">HPP Rata-rata (FIFO)</th>
                        <th class="px-5 py-4">Harga Jual</th>
                        <th class="px-5 py-4">Margin Laba</th>
                        <th class="px-5 py-4">Stok Saat Ini</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($products as $p): 
                        $margin = $p['selling_price'] - $p['cost_price'];
                        $isLowStock = $p['stock'] <= $p['min_stock'];
                    ?>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 shrink-0 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden grid place-items-center">
                                    <?= product_photo_html($p, 'w-full h-full object-cover', 'text-xs font-black') ?>
                                </div>
                                <div>
                                    <span class="font-bold text-slate-900 text-sm block"><?= sanitize($p['name']) ?></span>
                                    <span class="text-[10px] text-slate-400 font-mono"><?= sanitize($p['barcode'] ?? 'No Barcode') ?></span>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-slate-600">
                            <?= sanitize($p['category_name'] ?? 'Umum') ?>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-slate-600 font-semibold">
                            <?= formatRupiah($p['avg_cost'] ?? $p['cost_price']) ?>
                            <?php if (($p['batch_count'] ?? 0) > 0): ?>
                            <span class="block text-[10px] text-slate-400 font-normal"><?= (int)$p['batch_count'] ?> batch aktif</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap font-bold text-slate-900 text-sm">
                            <?= formatRupiah($p['selling_price']) ?>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="text-green-700 font-bold text-xs">+<?= formatRupiah($margin) ?></span>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 rounded-full text-xs font-bold <?= $isLowStock ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-800' ?>">
                                <?= $p['stock'] ?> pcs <?= $isLowStock ? '(Menipis)' : '' ?>
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right whitespace-nowrap">
                            <button @click="editItem = <?= htmlspecialchars(json_encode([
                                'id' => $p['id'],
                                'name' => $p['name'],
                                'barcode' => $p['barcode'] ?? '',
                                'category_id' => $p['category_id'],
                                'cost_price' => $p['cost_price'],
                                'selling_price' => $p['selling_price'],
                                'stock' => (int)$p['stock'],
                                'min_stock' => $p['min_stock'],
                                'image' => product_photo_url($p['image'] ?? null) ?? '',
                                'photo_html' => product_photo_html($p, 'w-full h-full object-cover', 'text-lg font-black'),
                                'is_active' => (bool)$p['is_active']
                            ])) ?>; editModal = true;" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition">
                                Edit
                            </button>
                            <form action="<?= url('admin/canteen/delete-product/' . (int)$p['id']) ?>" method="POST" class="inline"
                                  onsubmit="return confirm('Hapus produk &quot;<?= sanitize($p['name']) ?>&quot;? Data stok & item transaksi terkait produk ini juga akan dihapus permanen.')">
                                <button type="submit" class="px-3 py-1.5 rounded-xl bg-red-50 hover:bg-red-100 text-red-600 font-bold transition">
                                    Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL TAMBAH PRODUK -->
    <div x-show="addModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="addModal = false" class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-heading font-black text-xl text-pepsi-navy">Tambah Produk Pepsi Food &amp; Drink</h3>
                <button @click="addModal = false" class="text-slate-400 hover:text-slate-700">&times;</button>
            </div>

            <form action="<?= url('admin/canteen/store-product') ?>" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Produk *</label>
                    <input type="text" name="name" required placeholder="Contoh: Es Kopi Susu Pare" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Barcode / SKU (Opsional)</label>
                    <input type="text" name="barcode" placeholder="Scan barcode produk atau kosongkan" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kategori Produk</label>
                    <select name="category_id" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-semibold">
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= sanitize($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Foto Produk</label>
                    <div class="flex items-center gap-3">
                        <div id="add-img-preview" class="w-16 h-16 shrink-0 rounded-xl bg-slate-50 border border-slate-200 grid place-items-center overflow-hidden text-slate-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <input type="file" name="image" accept="image/png,image/jpeg,image/webp,image/gif"
                               onchange="previewProductImage(this, 'add-img-preview')"
                               class="block w-full text-[11px] text-slate-600 file:mr-2 file:py-2 file:px-3 file:rounded-xl file:border-0 file:bg-pepsi-blue file:text-white file:font-bold file:text-[11px] hover:file:bg-blue-900 file:cursor-pointer bg-white border border-slate-200 rounded-xl p-2">
                    </div>
                    <span class="text-[10px] text-slate-400">Format PNG, JPG, WEBP, atau GIF. Maksimal 3 MB. Tampil di kartu produk POS.</span>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Modal / HPP (Rp) *</label>
                        <input type="number" name="cost_price" required placeholder="3000" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Harga Jual (Rp) *</label>
                        <input type="number" name="selling_price" required placeholder="5000" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold text-pepsi-blue">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Stok Awal</label>
                        <input type="number" name="stock" value="20" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Batas Minimal Stok</label>
                        <input type="number" name="min_stock" value="5" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="addModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-pepsi-blue text-white font-bold">Simpan Produk</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT PRODUK -->
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="editModal = false" class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-heading font-black text-xl text-pepsi-navy">Edit Produk Pepsi Food &amp; Drink</h3>
                <button @click="editModal = false" class="text-slate-400 hover:text-slate-700">&times;</button>
            </div>

            <form :action="'<?= url('admin/canteen/update-product/') ?>' + editItem.id" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Produk *</label>
                    <input type="text" name="name" x-model="editItem.name" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Barcode / SKU</label>
                    <input type="text" name="barcode" x-model="editItem.barcode" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kategori Produk</label>
                    <select name="category_id" x-model="editItem.category_id" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-semibold">
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= sanitize($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block font-bold text-slate-700">Foto Produk</label>
                        <label x-show="editItem.image" class="flex items-center gap-1.5 text-[11px] font-semibold text-red-600 cursor-pointer select-none">
                            <input type="checkbox" name="remove_image" value="1" class="w-3.5 h-3.5 rounded accent-red-600">
                            <span>Hapus foto</span>
                        </label>
                    </div>
                    <div class="flex items-center gap-3">
                        <div id="edit-img-preview" class="w-16 h-16 shrink-0 rounded-xl bg-slate-50 border border-slate-200 grid place-items-center overflow-hidden text-slate-300" x-html="editItem.photo_html || ''"></div>
                        <input type="file" name="image" accept="image/png,image/jpeg,image/webp,image/gif"
                               onchange="previewProductImage(this, 'edit-img-preview')"
                               class="block w-full text-[11px] text-slate-600 file:mr-2 file:py-2 file:px-3 file:rounded-xl file:border-0 file:bg-pepsi-blue file:text-white file:font-bold file:text-[11px] hover:file:bg-blue-900 file:cursor-pointer bg-white border border-slate-200 rounded-xl p-2">
                    </div>
                    <span class="text-[10px] text-slate-400">Biarkan kosong jika foto tidak diganti. Format PNG, JPG, WEBP, atau GIF. Maksimal 3 MB.</span>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Modal / HPP (Rp) *</label>
                        <input type="number" name="cost_price" x-model="editItem.cost_price" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Harga Jual (Rp) *</label>
                        <input type="number" name="selling_price" x-model="editItem.selling_price" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold text-pepsi-blue">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Stok Saat Ini (Koreksi) *</label>
                        <input type="number" name="stock" x-model="editItem.stock" min="0" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-pepsi-blue">
                        <span class="text-[10px] text-slate-400">Ubah jumlah stok fisik nyata produk.</span>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Batas Minimal Stok</label>
                        <input type="number" name="min_stock" x-model="editItem.min_stock" min="0" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-semibold focus:bg-white focus:outline-none focus:ring-2 focus:ring-pepsi-blue">
                        <span class="text-[10px] text-slate-400">Peringatan stok menipis.</span>
                    </div>
                </div>
                <div>
                    <label class="flex items-center gap-2 font-bold text-slate-700 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" :checked="editItem.is_active" class="rounded">
                        <span>Produk Aktif Dijual</span>
                    </label>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="editModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-pepsi-blue text-white font-bold">Perbarui Produk</button>
                </div>
            </form>
        </div>
    </div>
    <!-- MODAL KELOLA KATEGORI -->
    <div x-show="catModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="catEditId = 0" class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-heading font-black text-xl text-pepsi-navy">Kelola Kategori Pepsi Food &amp; Drink</h3>
                <button @click="catModal = false; catEditId = 0" class="text-slate-400 hover:text-slate-700">&times;</button>
            </div>

            <form action="<?= url('admin/canteen/store-category') ?>" method="POST" class="flex items-end gap-2 text-xs">
                <div class="flex-1">
                    <label class="block font-bold text-slate-700 mb-1">Kategori Baru *</label>
                    <input type="text" name="name" required maxlength="50" placeholder="Contoh: Minuman Dingin" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                </div>
                <button type="submit" class="px-4 py-2.5 rounded-xl bg-pepsi-blue text-white font-bold whitespace-nowrap">Tambah</button>
            </form>

            <div class="border-t border-slate-100 pt-3">
                <?php if (empty($categories)): ?>
                <p class="text-xs text-slate-400 text-center py-4">Belum ada kategori.</p>
                <?php else: ?>
                <ul class="divide-y divide-slate-100 text-xs max-h-72 overflow-y-auto">
                    <?php foreach ($categories as $cat): $used = $categoryCounts[(int)$cat['id']] ?? 0; ?>
                    <li class="py-2.5 flex items-center gap-2">
                        <template x-if="catEditId !== <?= (int)$cat['id'] ?>">
                            <div class="flex-1 flex items-center justify-between gap-2">
                                <div>
                                    <span class="font-bold text-slate-800"><?= sanitize($cat['name']) ?></span>
                                    <span class="text-[10px] text-slate-400 ml-2"><?= $used ?> produk</span>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <button @click="catEditId = <?= (int)$cat['id'] ?>; catName = <?= htmlspecialchars(json_encode($cat['name']), ENT_QUOTES) ?>"
                                            class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition">Edit</button>
                                    <form action="<?= url('admin/canteen/delete-category/' . (int)$cat['id']) ?>" method="POST"
                                          onsubmit="return confirm('Hapus kategori &quot;<?= sanitize($cat['name']) ?>&quot;?<?php if ($used > 0): ?> <?= $used ?> produk akan menjadi tanpa kategori.<?php endif; ?>')">
                                        <button type="submit" class="px-3 py-1.5 rounded-xl bg-red-50 hover:bg-red-100 text-red-600 font-bold transition">Hapus</button>
                                    </form>
                                </div>
                            </div>
                        </template>
                        <template x-if="catEditId === <?= (int)$cat['id'] ?>">
                            <form action="<?= url('admin/canteen/update-category/' . (int)$cat['id']) ?>" method="POST" class="flex-1 flex items-center gap-2">
                                <input type="text" name="name" maxlength="50" x-model="catName" required
                                       class="flex-1 rounded-xl border border-slate-200 p-2 bg-slate-50 font-bold">
                                <button type="submit" class="px-3 py-1.5 rounded-xl bg-pepsi-blue text-white font-bold">Simpan</button>
                                <button type="button" @click="catEditId = 0" class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
                            </form>
                        </template>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>

            <div class="pt-2 border-t border-slate-100 flex items-center justify-end">
                <button @click="catModal = false; catEditId = 0" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs">Tutup</button>
            </div>
        </div>
    </div>

</div>

<script>
function previewProductImage(input, previewId) {
    const box = document.getElementById(previewId);
    const file = input.files && input.files[0];
    if (!box || !file) return;

    const img = document.createElement('img');
    img.className = 'w-full h-full object-cover';
    img.src = URL.createObjectURL(file);
    box.innerHTML = '';
    box.appendChild(img);
}
</script>
