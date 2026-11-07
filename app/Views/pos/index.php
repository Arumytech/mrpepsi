<?php
// POS Fast Cashier Terminal View
?>
<div x-data="posTerminal()" x-init="$nextTick(() => $refs.searchInput && $refs.searchInput.focus())" @keydown.f2.window.prevent="openSearchModal()" class="flex-1 grid grid-cols-1 md:grid-cols-12 h-full min-h-0 overflow-hidden relative">

    <!-- LEFT / MAIN: PRODUCTS GRID & SEARCH -->
    <div class="md:col-span-7 lg:col-span-8 flex flex-col h-full min-h-0 bg-slate-100 border-r border-slate-200 overflow-hidden">
        
        <!-- Search & Category Filters Header -->
        <div class="p-3 sm:p-4 bg-white border-b border-slate-200 space-y-2.5 sm:space-y-3 shrink-0">
            <div class="flex items-center gap-3">
                <div class="relative flex-1">
                    <input type="text" 
                           x-ref="searchInput"
                           x-model="searchQuery" 
                           @keydown.enter="handleBarcodeScan()"
                           placeholder="Scan Barcode atau Cari Nama Produk (F2)..." 
                           class="w-full pl-10 pr-4 py-2.5 rounded-2xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-pepsi-blue focus:outline-none text-xs sm:text-sm font-semibold">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>

                <form action="<?= url('pos/close-day') ?>" method="POST"
                      onsubmit="return confirm('Tutup transaksi POS untuk hari ini?\n\nMesin kasir tidak dapat digunakan lagi sampai besok dan akan terbuka otomatis.')">
                    <button type="submit" class="px-4 py-2.5 rounded-2xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold whitespace-nowrap shadow-sm transition">
                        Tutup Transaksi
                    </button>
                </form>
            </div>

            <!-- Categories Tabs -->
            <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs font-bold select-none">
                <button @click="selectedCategory = 'all'" :class="selectedCategory === 'all' ? 'bg-pepsi-navy text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-3.5 py-1.5 rounded-xl transition whitespace-nowrap">
                    Semua Produk
                </button>
                <?php foreach ($categories as $cat): ?>
                <button @click="selectedCategory = '<?= $cat['id'] ?>'" :class="selectedCategory === '<?= $cat['id'] ?>' ? 'bg-pepsi-navy text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-3.5 py-1.5 rounded-xl transition whitespace-nowrap">
                    <?= sanitize($cat['name']) ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Product Cards Grid -->
        <div class="flex-1 min-h-0 p-3 sm:p-4 overflow-y-auto">
            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
                <?php foreach ($products as $p): ?>
                <button @click="addToCart(<?= htmlspecialchars(json_encode([
                            'id' => $p['id'],
                            'name' => $p['name'],
                            'barcode' => $p['barcode'] ?? '',
                            'category_id' => $p['category_id'],
                            'image' => product_photo_url($p['image'] ?? null) ?? '',
                            'initials' => product_initials($p['name']),
                            'gradient' => product_gradient($p),
                            'price' => (float)$p['selling_price'],
                            'stock' => (int)$p['stock']
                        ])) ?>)"
                        x-show="(selectedCategory === 'all' || selectedCategory === '<?= $p['category_id'] ?>') &&
                                (searchQuery === '' || '<?= strtolower(addslashes($p['name'])) ?>'.includes(searchQuery.toLowerCase()) || '<?= $p['barcode'] ?>'.includes(searchQuery))"
                        :disabled="<?= $p['stock'] ?> <= 0"
                        :class="<?= $p['stock'] ?> <= 0 ? 'opacity-50 cursor-not-allowed bg-slate-50' : 'hover:border-pepsi-blue hover:shadow-md active:scale-95 bg-white'"
                        class="p-3.5 rounded-2xl border border-slate-200 text-left flex flex-col justify-between transition-all select-none">
                    
                    <div class="mb-2 h-16 sm:h-20 rounded-xl overflow-hidden bg-slate-50 border border-slate-100">
                        <?= product_photo_html($p, 'w-full h-full object-cover', 'text-2xl font-black') ?>
                    </div>

                    <div>
                        <div class="flex items-center justify-between gap-1 mb-1">
                            <span class="text-[10px] text-slate-400 font-mono"><?= sanitize($p['barcode'] ?? 'ID:' . $p['id']) ?></span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $p['stock'] <= 5 ? 'bg-red-50 text-red-600' : 'bg-slate-100 text-slate-600' ?>">
                                <?= $p['stock'] ?> pcs
                            </span>
                        </div>
                        <h4 class="font-bold text-xs text-slate-800 line-clamp-2 leading-snug"><?= sanitize($p['name']) ?></h4>
                    </div>

                    <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between">
                        <span class="font-heading font-black text-sm text-pepsi-navy"><?= formatRupiah($p['selling_price']) ?></span>
                        <span class="w-6 h-6 rounded-lg bg-blue-50 text-pepsi-blue flex items-center justify-center font-bold text-xs">+</span>
                    </div>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <!-- RIGHT: ORDER CART & CHECKOUT -->
    <div class="md:col-span-5 lg:col-span-4 flex flex-col h-full min-h-0 bg-white border-l border-slate-200 overflow-hidden relative">
        
        <!-- Cart Header -->
        <div class="p-3 sm:p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 shrink-0">
            <div>
                <h3 class="font-heading font-black text-sm sm:text-base text-slate-900">Keranjang Transaksi</h3>
                <span class="text-[11px] text-slate-500" x-text="cart.length + ' jenis produk'"></span>
            </div>
            <button x-show="cart.length > 0" @click="clearCart()" class="text-xs font-bold text-red-500 hover:underline">
                Kosongkan
            </button>
        </div>

        <!-- Cart Items List (Scrollable Area Only) -->
        <div class="flex-1 min-h-0 p-3 sm:p-4 overflow-y-auto divide-y divide-slate-100 text-xs">
            <template x-if="cart.length === 0">
                <div class="h-full min-h-[140px] flex flex-col items-center justify-center text-slate-400 text-center p-6 space-y-2">
                    <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-2xl">🛒</div>
                    <p class="font-bold text-slate-600">Keranjang Kosong</p>
                    <p class="text-[11px]">Klik produk atau scan barcode untuk menambahkan barang.</p>
                </div>
            </template>

            <template x-for="(item, idx) in cart" :key="item.id">
                <div class="py-2.5 flex items-center justify-between gap-2">
                    <div class="flex-1 pr-2 flex items-center gap-2">
                        <div class="w-8 h-8 shrink-0 rounded-lg overflow-hidden bg-slate-100 border border-slate-200 grid place-items-center">
                            <template x-if="item.image">
                                <img :src="item.image" alt="" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!item.image">
                                <span class="text-[10px] font-black text-white leading-none drop-shadow-sm" :class="item.gradient" x-text="item.initials"></span>
                            </template>
                        </div>
                        <div class="min-w-0">
                            <span class="font-bold text-slate-800 block line-clamp-1" x-text="item.name"></span>
                            <span class="text-[11px] text-slate-500" x-text="formatRp(item.price)"></span>
                        </div>
                    </div>

                    <!-- Quantity controls -->
                    <div class="flex items-center gap-1.5 shrink-0">
                        <button @click="decreaseQty(idx)" class="w-6 h-6 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold flex items-center justify-center">-</button>
                        <span class="w-7 text-center font-bold" x-text="item.quantity"></span>
                        <button @click="increaseQty(idx)" class="w-6 h-6 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold flex items-center justify-center">+</button>
                    </div>

                    <div class="text-right pl-2 min-w-[70px] shrink-0">
                        <span class="font-heading font-extrabold text-slate-900" x-text="formatRp(item.price * item.quantity)"></span>
                    </div>
                </div>
            </template>
        </div>

        <!-- Checkout Bottom Summary (Tetap Di Layar / Pinned Bottom) -->
        <div class="p-3.5 sm:p-4 border-t border-slate-200 bg-white space-y-2.5 sm:space-y-3 shrink-0 sticky bottom-0 z-20 shadow-[0_-4px_16px_rgba(0,0,0,0.06)]">
            <div class="space-y-1.5 text-xs">
                <div class="flex justify-between text-slate-500">
                    <span>Subtotal:</span>
                    <span class="font-bold text-slate-800" x-text="formattedSubtotal">Rp 0</span>
                </div>
                <div class="flex justify-between items-baseline pt-1 border-t border-slate-100">
                    <span class="font-extrabold text-xs sm:text-sm text-slate-900 uppercase">Total Bayar:</span>
                    <span class="font-heading font-black text-xl sm:text-2xl text-pepsi-navy" x-text="formattedSubtotal">Rp 0</span>
                </div>
            </div>

            <!-- Customer Name Input -->
            <div>
                <input type="text" x-model="customerName" placeholder="Nama Pelanggan (Umum / Siswa)" class="w-full text-xs font-semibold rounded-xl border border-slate-200 px-3 py-2 bg-slate-50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-pepsi-blue">
            </div>

            <!-- Action Checkout Button -->
            <button @click="openPaymentModal()" 
                    :disabled="cart.length === 0"
                    :class="cart.length === 0 ? 'bg-slate-300 cursor-not-allowed opacity-60' : 'bg-green-600 hover:bg-green-700 text-white shadow-lg shadow-green-600/30 active:scale-[0.99]'"
                    class="w-full py-3 sm:py-3.5 rounded-2xl font-bold text-xs sm:text-sm transition-all flex items-center justify-center gap-2">
                <span>BAYAR SEKARANG &rarr;</span>
            </button>
        </div>

    </div>

    <!-- MOBILE / SMALL SCREEN FLOATING CHECKOUT BAR (< md) -->
    <div x-show="cart.length > 0" x-cloak class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur border-t border-slate-200 p-3 shadow-2xl flex items-center justify-between gap-3 animate-in slide-in-from-bottom duration-200">
        <div class="min-w-0">
            <span class="text-[10px] uppercase font-bold text-slate-400 block" x-text="cart.length + ' Jenis Produk'"></span>
            <span class="font-heading font-black text-base text-pepsi-navy block leading-tight" x-text="formattedSubtotal"></span>
        </div>
        <button @click="openPaymentModal()" class="px-5 py-2.5 rounded-xl bg-green-600 hover:bg-green-700 text-white font-bold text-xs shadow-lg shadow-green-600/30 flex items-center gap-1.5 shrink-0">
            <span>BAYAR SEKARANG &rarr;</span>
        </button>
    </div>

    <!-- PAYMENT MODAL (Tunai & Cash, Change Calculator) -->
    <div x-show="paymentModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="paymentModalOpen = false" class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl space-y-5 animate-in fade-in zoom-in-95 duration-150">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-heading font-black text-xl text-pepsi-navy">Pembayaran Transaksi</h3>
                <button @click="paymentModalOpen = false" class="text-slate-400 hover:text-slate-700">&times;</button>
            </div>

            <!-- Amount Due -->
            <div class="bg-blue-50/60 p-4 rounded-2xl border border-blue-100 text-center">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Tagihan Pembayaran</span>
                <span class="font-heading font-black text-3xl text-pepsi-navy block mt-1" x-text="formattedSubtotal"></span>
            </div>

            <!-- Payment Method -->
            <div class="grid grid-cols-2 gap-2 text-xs font-bold">
                <button type="button" @click="paymentMethod = 'cash'" :class="paymentMethod === 'cash' ? 'bg-pepsi-navy text-white ring-2 ring-slate-900' : 'bg-slate-100 text-slate-700'" class="py-2.5 rounded-xl transition">
                    💵 Tunai
                </button>
                <button type="button" @click="paymentMethod = 'qris'" :class="paymentMethod === 'qris' ? 'bg-pepsi-navy text-white ring-2 ring-slate-900' : 'bg-slate-100 text-slate-700'" class="py-2.5 rounded-xl transition">
                    📱 QRIS
                </button>
            </div>

            <!-- Cash Input Section -->
            <div x-show="paymentMethod === 'cash'" class="space-y-3 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nominal Uang Diterima (Rp) *</label>
                    <input type="number" x-model.number="cashPaid" placeholder="0" class="w-full text-lg font-black rounded-xl border border-slate-300 p-3 bg-slate-50 text-pepsi-blue">
                </div>

                <!-- Quick Cash Presets -->
                <div class="grid grid-cols-3 gap-1.5 font-bold">
                    <button @click="cashPaid = subtotal" class="p-2 rounded-lg bg-slate-100 hover:bg-slate-200">Uang Pas</button>
                    <button @click="cashPaid = 10000" class="p-2 rounded-lg bg-slate-100 hover:bg-slate-200">10.000</button>
                    <button @click="cashPaid = 20000" class="p-2 rounded-lg bg-slate-100 hover:bg-slate-200">20.000</button>
                    <button @click="cashPaid = 50000" class="p-2 rounded-lg bg-slate-100 hover:bg-slate-200">50.000</button>
                    <button @click="cashPaid = 100000" class="p-2 rounded-lg bg-slate-100 hover:bg-slate-200">100.000</button>
                    <button @click="cashPaid = roundUp(subtotal, 10000)" class="p-2 rounded-lg bg-slate-100 hover:bg-slate-200">Bulat 10k</button>
                </div>

                <!-- Change Calculation -->
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 flex justify-between items-center text-xs">
                    <span class="font-bold text-slate-600">Uang Kembalian:</span>
                    <span class="font-heading font-black text-xl" :class="changeAmount >= 0 ? 'text-green-700' : 'text-red-600'" x-text="formatRp(Math.max(0, changeAmount))"></span>
                </div>
            </div>

            <!-- QRIS Section -->
            <div x-show="paymentMethod === 'qris'" class="space-y-3 text-xs">
                <div class="p-4 rounded-2xl bg-blue-50/60 border border-blue-100 text-center space-y-1">
                    <span class="text-2xl">📱</span>
                    <p class="font-bold text-slate-700">Pembayaran via QRIS</p>
                    <p class="text-slate-500">Minta pelanggan membayar QRIS sebesar <b x-text="formattedSubtotal"></b>, lalu konfirmasi.</p>
                </div>
            </div>

            <!-- Submit Finish Checkout -->
            <button @click="finishCheckout()" 
                    :disabled="paymentMethod === 'cash' && cashPaid < subtotal"
                    :class="(paymentMethod === 'cash' && cashPaid < subtotal) ? 'bg-slate-300 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700 text-white shadow-lg shadow-green-600/30'"
                    class="w-full py-3.5 rounded-2xl font-bold text-sm transition-all flex items-center justify-center gap-2">
                <span>SELESAIKAN & CETAK STRUK</span>
            </button>
        </div>
    </div>

    <!-- RECEIPT MODAL (Thermal Instant Print) -->
    <div x-show="receiptModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-150">
            <div class="text-center space-y-1">
                <div class="w-12 h-12 rounded-full bg-green-100 text-green-700 flex items-center justify-center mx-auto text-xl font-bold">
                    &check;
                </div>
                <h3 class="font-heading font-black text-lg text-slate-900">Transaksi Selesai!</h3>
                <p class="text-xs text-slate-500">Stok barang telah otomatis terpotong dari sistem.</p>
            </div>

            <!-- Thermal Preview Box -->
            <div id="receipt-preview-box" class="bg-slate-50 p-4 rounded-2xl border border-slate-200 text-[11px] font-mono space-y-2">
                <div class="text-center border-b border-dashed border-slate-300 pb-2">
                    <b class="text-xs"><?= sanitize($settings['app_name'] ?? APP_NAME) ?></b>
                    <p class="text-[9px]"><?= sanitize($settings['address'] ?? 'Kampung Inggris Pare') ?></p>
                    <p class="text-[9px]">No: <span x-text="lastReceiptData.invoice_no"></span></p>
                </div>

                <div class="space-y-1 divide-y divide-dashed divide-slate-200 py-1">
                    <template x-for="item in lastReceiptData.items" :key="item.product_id">
                        <div class="pt-1 flex justify-between">
                            <div>
                                <span x-text="item.product_name" class="block font-bold"></span>
                                <span class="text-[9px] text-slate-500" x-text="item.quantity + 'x @' + formatRp(item.selling_price)"></span>
                            </div>
                            <span class="font-bold" x-text="formatRp(item.subtotal)"></span>
                        </div>
                    </template>
                </div>

                <div class="border-t border-dashed border-slate-300 pt-2 space-y-0.5">
                    <div class="flex justify-between font-bold">
                        <span>Total:</span>
                        <span x-text="formatRp(lastReceiptData.total_amount)"></span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Bayar (<span x-text="lastPaymentLabel"></span>):</span>
                        <span x-text="formatRp(lastReceiptData.cash_paid)"></span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Kembali:</span>
                        <span x-text="formatRp(lastReceiptData.change_returned)"></span>
                    </div>
                </div>

                <div class="text-center pt-2 text-[9px] text-slate-400">
                    Terima kasih atas kunjungan Anda!
                </div>
            </div>

            <!-- Print Actions -->
            <div class="flex items-center gap-2">
                <button @click="printReceiptWindow(lastReceiptData.transaction_id)" class="flex-1 py-3 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl flex items-center justify-center gap-1.5 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span>Cetak Thermal</span>
                </button>
                <button @click="receiptModalOpen = false; resetTerminal();" class="px-4 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition">
                    Transaksi Baru
                </button>
            </div>
        </div>
    </div>

    <!-- PRODUCT SEARCH MODAL (F2) -->
    <div x-show="searchModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-start justify-center p-4 pt-20">
        <div @click.away="searchModalOpen = false" class="bg-white rounded-3xl max-w-2xl w-full shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-150 flex flex-col max-h-[80vh]">
            <div class="p-4 border-b border-slate-100 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="font-heading font-black text-lg text-pepsi-navy">Cari Produk <span class="text-xs font-bold text-slate-400">(F2)</span></h3>
                    <button @click="searchModalOpen = false" class="text-slate-400 hover:text-slate-700 text-xl leading-none">&times;</button>
                </div>
                <div class="relative">
                    <input type="text"
                           x-ref="searchModalInput"
                           x-model="searchModalQuery"
                           @keydown.enter.prevent="addFirstMatch()"
                           @keydown.escape="searchModalOpen = false"
                           placeholder="Ketik nama atau barcode produk..."
                           class="w-full pl-10 pr-4 py-2.5 rounded-2xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-pepsi-blue focus:outline-none text-sm font-semibold">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto divide-y divide-slate-100">
                <template x-for="p in filteredModalProducts" :key="p.id">
                    <button @click="addToCart(p); searchModalOpen = false; searchModalQuery = '';"
                            :disabled="p.stock <= 0"
                            :class="p.stock <= 0 ? 'opacity-40 cursor-not-allowed' : 'hover:bg-slate-50'"
                            class="w-full p-3 flex items-center gap-3 text-left transition">
                        <div class="w-10 h-10 shrink-0 rounded-xl overflow-hidden bg-slate-100 border border-slate-200 grid place-items-center">
                            <template x-if="p.image">
                                <img :src="p.image" alt="" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!p.image">
                                <span class="text-[10px] font-black text-white leading-none drop-shadow-sm" :class="p.gradient" x-text="p.initials"></span>
                            </template>
                        </div>
                        <div class="flex-1 min-w-0">
                            <span class="font-bold text-xs text-slate-800 block line-clamp-1" x-text="p.name"></span>
                            <span class="text-[10px] text-slate-400 font-mono" x-text="p.barcode || ('ID:' + p.id)"></span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="p.stock <= 5 ? 'bg-red-50 text-red-600' : 'bg-slate-100 text-slate-600'" x-text="p.stock + ' pcs'"></span>
                        <span class="font-heading font-black text-sm text-pepsi-navy w-24 text-right" x-text="formatRp(p.price)"></span>
                    </button>
                </template>
                <template x-if="filteredModalProducts.length === 0">
                    <div class="p-8 text-center text-slate-400 text-xs italic">Produk tidak ditemukan.</div>
                </template>
            </div>
        </div>
    </div>

    <!-- TOAST KONFIRMASI -->
    <div x-show="toast" x-cloak x-transition.opacity.duration.200ms
         class="fixed top-4 left-1/2 -translate-x-1/2 z-[60] px-5 py-3 rounded-2xl bg-slate-900 text-white font-bold text-xs shadow-2xl flex items-center gap-2">
        <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span x-text="toast"></span>
    </div>

</div>

<!-- Interactive Alpine POS Logic -->
<script>
function posTerminal() {
    return {
        searchQuery: '',
        selectedCategory: 'all',
        cart: [],
        customerName: 'Umum / Siswa',
        paymentModalOpen: false,
        paymentMethod: 'cash',
        cashPaid: 0,
        receiptModalOpen: false,
        lastReceiptData: {},
        receiptMode: '<?= sanitize($settings['pos_receipt_mode'] ?? 'ask') ?>',
        toast: '',
        searchModalOpen: false,
        searchModalQuery: '',
        products: <?= json_encode(array_map(fn($p) => [
            'id' => (int)$p['id'],
            'name' => $p['name'],
            'barcode' => $p['barcode'] ?? '',
            'image' => product_photo_url($p['image'] ?? null) ?? '',
            'initials' => product_initials($p['name']),
            'gradient' => product_gradient($p),
            'price' => (float)$p['selling_price'],
            'stock' => (int)$p['stock']
        ], $products)) ?>,

        get filteredModalProducts() {
            const q = this.searchModalQuery.trim().toLowerCase();
            if (q === '') return this.products;
            return this.products.filter(p =>
                p.name.toLowerCase().includes(q) || String(p.barcode).includes(q)
            );
        },

        openSearchModal() {
            this.searchModalOpen = true;
            this.searchModalQuery = '';
            this.$nextTick(() => this.$refs.searchModalInput && this.$refs.searchModalInput.focus());
        },

        addFirstMatch() {
            const list = this.filteredModalProducts;
            if (list.length > 0) {
                this.addToCart(list[0]);
                this.searchModalOpen = false;
                this.searchModalQuery = '';
            }
        },

        get subtotal() {
            return this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        },

        get formattedSubtotal() {
            return this.formatRp(this.subtotal);
        },

        get changeAmount() {
            return this.cashPaid - this.subtotal;
        },

        get lastPaymentLabel() {
            return this.lastReceiptData.payment_method === 'qris' ? 'QRIS' : 'Tunai';
        },

        addToCart(product) {
            if (product.stock <= 0) {
                alert('Stok barang habis.');
                return;
            }

            const existing = this.cart.find(i => i.id === product.id);
            if (existing) {
                if (existing.quantity >= product.stock) {
                    alert('Jumlah melebihi stok yang tersedia (' + product.stock + ' pcs).');
                    return;
                }
                existing.quantity++;
            } else {
                this.cart.push({
                    id: product.id,
                    name: product.name,
                    image: product.image || '',
                    initials: product.initials || '',
                    gradient: product.gradient || 'from-slate-400 to-slate-600',
                    price: product.price,
                    stock: product.stock,
                    quantity: 1
                });
            }
        },

        increaseQty(idx) {
            const item = this.cart[idx];
            if (item.quantity >= item.stock) {
                alert('Maksimal stok tercapai.');
                return;
            }
            item.quantity++;
        },

        decreaseQty(idx) {
            const item = this.cart[idx];
            if (item.quantity > 1) {
                item.quantity--;
            } else {
                this.cart.splice(idx, 1);
            }
        },

        clearCart() {
            this.cart = [];
        },

        openPaymentModal() {
            if (this.cart.length === 0) return;
            this.cashPaid = this.subtotal;
            this.paymentMethod = 'cash';
            this.paymentModalOpen = true;
        },

        roundUp(val, step) {
            return Math.ceil(val / step) * step;
        },

        async finishCheckout() {
            const isCash = this.paymentMethod === 'cash';
            const payload = {
                items: this.cart.map(i => ({ product_id: i.id, quantity: i.quantity })),
                payment_method: this.paymentMethod,
                cash_paid: isCash ? this.cashPaid : this.subtotal,
                customer_name: this.customerName
            };

            try {
                const res = await fetch('<?= url('pos/checkout') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (data.success) {
                    this.lastReceiptData = data;
                    this.paymentModalOpen = false;

                    if (this.receiptMode === 'never') {
                        // Tidak cetak nota: tampilkan konfirmasi singkat lalu reset
                        this.showToast('Transaksi berhasil disimpan: ' + (data.invoice_no || ''));
                        setTimeout(() => this.resetTerminal(), 1200);
                    } else {
                        this.receiptModalOpen = true;
                        if (this.receiptMode === 'always') {
                            this.$nextTick(() => this.printReceiptWindow(data.transaction_id));
                        }
                    }
                } else {
                    alert('Gagal memproses transaksi: ' + (data.error || 'Terjadi kesalahan'));
                }
            } catch (e) {
                alert('Koneksi terputus.');
            }
        },

        showToast(msg) {
            this.toast = msg;
            clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => { this.toast = ''; }, 2000);
        },

        printReceiptWindow(txId) {
            const win = window.open('<?= url('pos/receipt/') ?>' + txId, '_blank', 'width=350,height=500');
            if (!win) {
                // Popup diblokir browser — biarkan modal tetap tampil untuk cetak manual
                alert('Jendela cetak diblokir browser. Klik "Cetak Thermal" pada dialog.');
                this.receiptModalOpen = true;
                return;
            }
            win.focus();
        },

        resetTerminal() {
            this.cart = [];
            this.customerName = 'Umum / Siswa';
            this.searchQuery = '';
            location.reload(); // Refresh products stock
        },

        handleBarcodeScan() {
            const query = this.searchQuery.trim();
            if (!query) return;

            const lower = query.toLowerCase();

            let product = this.products.find(p => String(p.barcode) === query);

            if (!product) {
                const matches = this.products.filter(p =>
                    p.name.toLowerCase().includes(lower) || String(p.barcode).includes(query)
                );
                if (matches.length === 1) {
                    product = matches[0];
                } else if (matches.length > 1) {
                    this.openSearchModal();
                    this.searchModalQuery = query;
                    return;
                }
            }

            if (product) {
                this.addToCart(product);
                this.searchQuery = '';
            } else {
                alert('Produk tidak ditemukan: ' + query);
            }
        },

        formatRp(val) {
            return 'Rp ' + Number(val).toLocaleString('id-ID');
        }
    };
}
</script>
