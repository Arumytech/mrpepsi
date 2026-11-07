<?php
// Penyesuaian & Riwayat Transaksi Pepsi Food & Drink
$currentQs = http_build_query([
    'start_date'      => $startDate,
    'end_date'        => $endDate,
    'payment_method'  => $paymentMethod,
    'type'            => $type,
    'status_adjusted' => $statusAdjusted ?? 'all',
    'search'          => $search,
]);
?>
<div x-data="{
    editModal: false,
    voidModal: false,
    selectedTx: null,
    allProducts: <?= htmlspecialchars(json_encode($products ?? []), ENT_QUOTES, 'UTF-8') ?>,
    selectedNewProductId: '',
    editForm: {
        id: 0,
        invoice_no: '',
        total_amount: 0,
        payment_method: 'cash',
        cash_paid: 0,
        customer_name: '',
        adjustment_note: '',
        is_manual: 0,
        items: []
    },
    voidForm: {
        id: 0,
        invoice_no: '',
        total_amount: 0,
        is_manual: 0,
        reason: ''
    },
    openEdit(tx) {
        this.selectedTx = tx;
        this.editForm = {
            id: tx.id,
            invoice_no: tx.invoice_no,
            total_amount: parseFloat(tx.total_amount || 0),
            payment_method: tx.payment_method,
            cash_paid: parseFloat(tx.cash_paid || tx.total_amount),
            customer_name: tx.customer_name || 'Umum / Siswa',
            adjustment_note: tx.adjustment_note || tx.manual_note || '',
            is_manual: parseInt(tx.is_manual || 0),
            items: (tx.items || []).map(function(item) {
                return {
                    id: item.id,
                    product_id: item.product_id,
                    product_name: item.product_name,
                    quantity: parseInt(item.quantity || 1),
                    selling_price: parseFloat(item.selling_price || 0),
                    cost_price: parseFloat(item.cost_price || 0),
                    subtotal: parseFloat(item.subtotal || 0)
                };
            })
        };
        this.recalc();
        this.editModal = true;
    },
    recalc() {
        let total = 0;
        for (let i = 0; i < this.editForm.items.length; i++) {
            let it = this.editForm.items[i];
            it.quantity = Math.max(0, parseInt(it.quantity || 0));
            it.subtotal = it.quantity * parseFloat(it.selling_price || 0);
            total += it.subtotal;
        }
        this.editForm.total_amount = total;
        if (this.editForm.payment_method === 'cash') {
            if (this.editForm.cash_paid < total) {
                this.editForm.cash_paid = total;
            }
        } else {
            this.editForm.cash_paid = total;
        }
    },
    changeQty(idx, diff) {
        this.editForm.items[idx].quantity = Math.max(0, parseInt(this.editForm.items[idx].quantity || 0) + diff);
        this.recalc();
    },
    removeItem(idx) {
        this.editForm.items.splice(idx, 1);
        this.recalc();
    },
    addProduct() {
        if (!this.selectedNewProductId) return;
        let pid = parseInt(this.selectedNewProductId);
        let p = this.allProducts.find(function(x) { return parseInt(x.id) === pid; });
        if (!p) return;

        let existing = this.editForm.items.find(function(x) { return parseInt(x.product_id) === pid; });
        if (existing) {
            existing.quantity += 1;
        } else {
            this.editForm.items.push({
                id: null,
                product_id: p.id,
                product_name: p.name,
                quantity: 1,
                selling_price: parseFloat(p.selling_price || 0),
                cost_price: parseFloat(p.cost_price || 0),
                subtotal: parseFloat(p.selling_price || 0)
            });
        }
        this.selectedNewProductId = '';
        this.recalc();
    },
    openVoid(tx) {
        this.selectedTx = tx;
        this.voidForm = {
            id: tx.id,
            invoice_no: tx.invoice_no,
            total_amount: parseFloat(tx.total_amount || 0),
            is_manual: parseInt(tx.is_manual || 0),
            reason: ''
        };
        this.voidModal = true;
    },
    get calculatedChange() {
        if (this.editForm.payment_method !== 'cash') return 0;
        let diff = this.editForm.cash_paid - this.editForm.total_amount;
        return diff > 0 ? diff : 0;
    }
}" class="space-y-6">

    <!-- Header & Action -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-cyan-100 text-cyan-800">
                    Kasir &amp; POS
                </span>
                <span class="text-xs text-slate-400">&bull; Pepsi Food &amp; Drink</span>
            </div>
            <h1 class="font-heading font-black text-2xl sm:text-3xl text-pepsi-navy mt-1">Penyesuaian &amp; Riwayat Transaksi</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Koreksi salah metode bayar (Tunai &harr; QRIS), perbaiki nama/catatan, atau batalkan transaksi salah input dengan <b>pengembalian stok otomatis</b>.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="<?= url('admin/manual-sales') ?>" class="bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs sm:text-sm px-4 py-2.5 rounded-2xl shadow-sm flex items-center gap-2 transition">
                <span>+ Penjualan Tambahan</span>
            </a>
            <a href="<?= url('pos') ?>" target="_blank" class="bg-pepsi-red hover:bg-red-700 text-white font-bold text-xs sm:text-sm px-4 py-2.5 rounded-2xl shadow-md flex items-center gap-2 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                <span>Buka Layar POS Kasir &rarr;</span>
            </a>
        </div>
    </div>

    <!-- Statistik Ringkasan Filter -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Omset -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Omset</span>
                <span class="w-8 h-8 rounded-xl bg-blue-50 text-pepsi-blue grid place-items-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <span class="font-heading font-black text-2xl text-pepsi-navy mt-2 block"><?= formatRupiah($summary['total_revenue'] ?? 0) ?></span>
            <div class="text-[11px] text-slate-500 mt-1 flex items-center justify-between">
                <span><?= (int)($summary['total_count'] ?? 0) ?> Transaksi</span>
                <span class="text-emerald-600 font-bold">Laba: <?= formatRupiah($summary['total_profit'] ?? 0) ?></span>
            </div>
        </div>

        <!-- Pembayaran Tunai (Cash) -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pembayaran Tunai (Cash)</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 grid place-items-center font-black text-xs">
                    Rp
                </span>
            </div>
            <span class="font-heading font-black text-2xl text-emerald-700 mt-2 block"><?= formatRupiah($summary['total_cash'] ?? 0) ?></span>
            <span class="text-[11px] text-slate-500 mt-1 block"><?= (int)($summary['count_cash'] ?? 0) ?> Transaksi Tunai</span>
        </div>

        <!-- Pembayaran QRIS -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pembayaran QRIS</span>
                <span class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 grid place-items-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                </span>
            </div>
            <span class="font-heading font-black text-2xl text-purple-700 mt-2 block"><?= formatRupiah($summary['total_qris'] ?? 0) ?></span>
            <span class="text-[11px] text-slate-500 mt-1 block"><?= (int)($summary['count_qris'] ?? 0) ?> Transaksi QRIS</span>
        </div>

        <!-- Tipe Transaksi & Penyesuaian -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Status &amp; Penyesuaian</span>
                <span class="w-8 h-8 rounded-xl bg-slate-100 text-slate-600 grid place-items-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                </span>
            </div>
            <div class="mt-3 space-y-1.5 text-xs">
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">Kasir POS Mesin:</span>
                    <span class="font-bold text-slate-800"><?= (int)($summary['count_pos'] ?? 0) ?> Transaksi</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">Pernah Disesuaikan:</span>
                    <span class="font-bold text-amber-600">✏️ <?= (int)($summary['count_adjusted'] ?? 0) ?> Transaksi</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-200 shadow-sm">
        <form method="GET" action="<?= url('admin/canteen/transactions') ?>" class="flex flex-wrap items-center justify-between gap-3">
            
            <!-- Quick Tabs Metode Pembayaran & Penyesuaian -->
            <div class="flex flex-wrap items-center gap-1.5 p-1 bg-slate-100 rounded-2xl text-xs font-bold">
                <a href="<?= url('admin/canteen/transactions?' . http_build_query(array_merge($_GET, ['payment_method' => 'all', 'status_adjusted' => 'all']))) ?>"
                   class="px-3.5 py-1.5 rounded-xl transition <?= ($paymentMethod === 'all' && ($statusAdjusted ?? 'all') === 'all') ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900' ?>">
                    Semua
                </a>
                <a href="<?= url('admin/canteen/transactions?' . http_build_query(array_merge($_GET, ['payment_method' => 'cash']))) ?>"
                   class="px-3.5 py-1.5 rounded-xl transition <?= $paymentMethod === 'cash' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-900' ?>">
                    💵 Tunai (<?= (int)($summary['count_cash'] ?? 0) ?>)
                </a>
                <a href="<?= url('admin/canteen/transactions?' . http_build_query(array_merge($_GET, ['payment_method' => 'qris']))) ?>"
                   class="px-3.5 py-1.5 rounded-xl transition <?= $paymentMethod === 'qris' ? 'bg-purple-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-900' ?>">
                    📱 QRIS (<?= (int)($summary['count_qris'] ?? 0) ?>)
                </a>
                <a href="<?= url('admin/canteen/transactions?' . http_build_query(array_merge($_GET, ['status_adjusted' => 'adjusted']))) ?>"
                   class="px-3.5 py-1.5 rounded-xl transition <?= ($statusAdjusted ?? 'all') === 'adjusted' ? 'bg-amber-500 text-white shadow-sm' : 'text-amber-700 hover:text-amber-900' ?>">
                    ✏️ Disesuaikan (<?= (int)($summary['count_adjusted'] ?? 0) ?>)
                </a>
            </div>

            <!-- Periode & Input Pencarian -->
            <div class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="payment_method" value="<?= sanitize($paymentMethod) ?>">

                <select name="type" onchange="this.form.submit()" class="px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-pepsi-blue">
                    <option value="all" <?= $type === 'all' ? 'selected' : '' ?>>Semua Tipe Transaksi</option>
                    <option value="pos" <?= $type === 'pos' ? 'selected' : '' ?>>Mesin Kasir (POS)</option>
                    <option value="manual" <?= $type === 'manual' ? 'selected' : '' ?>>Penjualan Manual</option>
                </select>

                <div class="flex items-center gap-1.5 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200 text-xs">
                    <input type="date" name="start_date" value="<?= sanitize($startDate) ?>" class="font-bold text-slate-800 bg-transparent focus:outline-none" title="Tanggal Mulai">
                    <span class="text-slate-400 font-bold">s/d</span>
                    <input type="date" name="end_date" value="<?= sanitize($endDate) ?>" class="font-bold text-slate-800 bg-transparent focus:outline-none" title="Tanggal Selesai">
                </div>

                <div class="relative">
                    <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Cari invoice / pelanggan..." class="pl-8 pr-3 py-2 rounded-xl border border-slate-200 text-xs font-medium text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-pepsi-blue w-44 sm:w-52">
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>

                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition shadow-sm">
                    Filter
                </button>
                <?php if ($startDate !== date('Y-m-01') || $endDate !== date('Y-m-d') || $paymentMethod !== 'all' || $type !== 'all' || $search !== ''): ?>
                    <a href="<?= url('admin/canteen/transactions') ?>" class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold transition">
                        Reset
                    </a>
                <?php endif; ?>
            </div>

        </form>
    </div>

    <!-- Data Table Transaksi -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="font-heading font-bold text-base text-slate-900">
                    Daftar Transaksi Penjualan
                </h3>
                <p class="text-xs text-slate-500">
                    Klik tombol <b>Penyesuaian Bayar</b> jika salah memilih metode bayar kasir, atau <b>Batalkan (Void)</b> untuk menghapus dan mengembalikan stok fisik.
                </p>
            </div>
            <span class="text-xs font-bold text-slate-400 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-100">
                Menampilkan <?= count($transactions) ?> transaksi
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-medium">
                <thead class="bg-pepsi-navy text-white text-[11px] uppercase tracking-wider font-bold">
                    <tr>
                        <th class="px-4 py-3.5 text-center w-12">No</th>
                        <th class="px-4 py-3.5">Invoice &amp; Waktu</th>
                        <th class="px-4 py-3.5">Pelanggan &amp; Kasir</th>
                        <th class="px-4 py-3.5">Rincian Barang</th>
                        <th class="px-4 py-3.5 text-right">Total Transaksi</th>
                        <th class="px-4 py-3.5 text-center">Metode Bayar</th>
                        <th class="px-4 py-3.5 text-right">Bayar / Kembali</th>
                        <th class="px-4 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="8" class="py-12 text-center text-slate-400">
                            <div class="flex flex-col items-center justify-center space-y-2">
                                <span class="text-3xl">🧾</span>
                                <span class="font-bold text-sm text-slate-600">Tidak ada riwayat transaksi pada filter ini</span>
                                <span class="text-xs text-slate-400">Silakan sesuaikan tanggal atau metode pembayaran di atas.</span>
                            </div>
                        </td>
                    </tr>
                    <?php else:
                        $no = 1;
                        foreach ($transactions as $tx):
                            $isCash = ($tx['payment_method'] === 'cash');
                            $isManual = ((int)($tx['is_manual'] ?? 0) === 1);
                    ?>
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="px-4 py-3.5 text-center text-slate-400 font-bold"><?= $no++ ?></td>
                        
                        <!-- Invoice & Waktu -->
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            <span class="font-mono font-black text-slate-900 block"><?= sanitize($tx['invoice_no']) ?></span>
                            <span class="text-[11px] text-slate-500 block"><?= date('d/m/Y H:i', strtotime($tx['created_at'])) ?></span>
                            <div class="flex flex-wrap items-center gap-1 mt-1">
                                <?php if ($isManual): ?>
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase bg-amber-100 text-amber-800">
                                        Manual Input
                                    </span>
                                <?php else: ?>
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase bg-blue-100 text-pepsi-blue">
                                        POS Kasir
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($tx['is_adjusted'])): ?>
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase bg-amber-500 text-white shadow-xs" title="Telah disesuaikan: <?= sanitize($tx['adjustment_note'] ?? '') ?>">
                                        ✏️ Disesuaikan
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>

                        <!-- Pelanggan & Kasir -->
                        <td class="px-4 py-3.5">
                            <span class="font-bold text-slate-800 block"><?= sanitize($tx['customer_name'] ?: 'Umum / Siswa') ?></span>
                            <span class="text-[11px] text-slate-500 block">
                                Kasir: <?= sanitize($tx['cashier_name'] ?: 'Admin') ?>
                            </span>
                            <?php if (!empty($tx['is_adjusted']) && !empty($tx['adjustment_note'])): ?>
                                <span class="text-[10px] text-amber-700 bg-amber-50 rounded px-1.5 py-0.5 block mt-1 border border-amber-200" title="Catatan Penyesuaian">
                                    <strong>Penyesuaian:</strong> <?= sanitize($tx['adjustment_note']) ?>
                                    <?php if (!empty($tx['adjusted_at'])): ?>
                                        <span class="text-[9px] text-slate-400 block"><?= date('d/m/Y H:i', strtotime($tx['adjusted_at'])) ?></span>
                                    <?php endif; ?>
                                </span>
                            <?php elseif (!empty($tx['manual_note'])): ?>
                                <span class="text-[10px] text-slate-400 italic block mt-0.5" title="Catatan">
                                    "<?= sanitize($tx['manual_note']) ?>"
                                </span>
                            <?php endif; ?>
                        </td>

                        <!-- Rincian Barang -->
                        <td class="px-4 py-3.5 min-w-[200px]">
                            <?php if (!empty($tx['items'])): ?>
                                <div class="space-y-1">
                                    <?php foreach ($tx['items'] as $item): ?>
                                    <div class="flex items-center justify-between text-[11px] gap-2">
                                        <span class="text-slate-700 truncate max-w-[170px]" title="<?= sanitize($item['product_name']) ?>">
                                            &bull; <?= sanitize($item['product_name']) ?>
                                        </span>
                                        <span class="font-mono font-bold text-slate-900 whitespace-nowrap">
                                            <?= (int)$item['quantity'] ?>x
                                        </span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span class="text-slate-400 italic text-[11px]">- Tanpa item -</span>
                            <?php endif; ?>
                        </td>

                        <!-- Total Transaksi & Laba -->
                        <td class="px-4 py-3.5 text-right whitespace-nowrap">
                            <span class="font-heading font-black text-sm text-pepsi-navy block">
                                <?= formatRupiah($tx['total_amount']) ?>
                            </span>
                            <span class="text-[10px] text-emerald-600 font-bold block">
                                Laba: <?= formatRupiah($tx['gross_profit']) ?>
                            </span>
                        </td>

                        <!-- Metode Bayar -->
                        <td class="px-4 py-3.5 text-center whitespace-nowrap">
                            <?php if ($isCash): ?>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-200 inline-flex items-center gap-1">
                                    <span>💵</span> TUNAI
                                </span>
                            <?php else: ?>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-purple-100 text-purple-800 border border-purple-200 inline-flex items-center gap-1">
                                    <span>📱</span> QRIS
                                </span>
                            <?php endif; ?>
                        </td>

                        <!-- Bayar / Kembali -->
                        <td class="px-4 py-3.5 text-right whitespace-nowrap text-[11px]">
                            <div class="text-slate-700 font-medium">
                                Bayar: <?= formatRupiah($tx['cash_paid']) ?>
                            </div>
                            <?php if ($isCash && (float)$tx['change_returned'] > 0): ?>
                                <div class="text-slate-500">
                                    Kembali: <?= formatRupiah($tx['change_returned']) ?>
                                </div>
                            <?php endif; ?>
                        </td>

                        <!-- Aksi -->
                        <td class="px-4 py-3.5 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-1.5">
                                <!-- Tombol Penyesuaian Bayar -->
                                <button type="button"
                                        @click="openEdit(<?= htmlspecialchars(json_encode($tx), ENT_QUOTES, 'UTF-8') ?>)"
                                        title="Penyesuaian Transaksi (Ubah Metode / Catatan)"
                                        class="px-2.5 py-1.5 rounded-xl bg-cyan-50 hover:bg-cyan-100 text-cyan-800 font-bold text-[11px] border border-cyan-200 flex items-center gap-1 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    <span>Sesuaikan</span>
                                </button>

                                <!-- Cetak Ulang Struk -->
                                <a href="<?= url('pos/receipt/' . (int)$tx['id']) ?>" 
                                   target="_blank"
                                   title="Cetak Ulang Struk Kasir"
                                   class="p-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                </a>

                                <!-- Batalkan / Void Transaksi -->
                                <button type="button"
                                        @click="openVoid(<?= htmlspecialchars(json_encode($tx), ENT_QUOTES, 'UTF-8') ?>)"
                                        title="Batalkan / Hapus Transaksi (Kembalikan Stok)"
                                        class="p-1.5 rounded-xl bg-red-50 hover:bg-red-100 text-red-600 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL PENYESUAIAN METODE BAYAR & SALAH STOK TRANSAKSI -->
    <!-- ============================================================== -->
    <div x-show="editModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4"
         style="display: none;">
        
        <div @click.away="editModal = false" class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-7 shadow-2xl border border-slate-100 space-y-5 my-8">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-2xl bg-cyan-100 text-cyan-800 grid place-items-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </span>
                    <div>
                        <h3 class="font-heading font-black text-lg text-slate-900">Penyesuaian Transaksi</h3>
                        <p class="text-xs text-slate-500 font-mono" x-text="'No. Invoice: ' + editForm.invoice_no"></p>
                    </div>
                </div>
                <button @click="editModal = false" class="text-slate-400 hover:text-slate-700 p-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" :action="'<?= url('admin/canteen/transactions/update/') ?>' + editForm.id" class="space-y-5 text-xs">
                <input type="hidden" name="redirect_qs" value="<?= sanitize($currentQs) ?>">
                <!-- Kirim data items array JSON ke backend -->
                <input type="hidden" name="items" :value="JSON.stringify(editForm.items)">

                <!-- BAGIAN 1: SALAH STOK / RINCIAN BARANG -->
                <div class="space-y-3 bg-slate-50/80 p-4 rounded-2xl border border-slate-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-slate-800 block">Koreksi Stok &amp; Barang Terjual</span>
                            <span class="text-[11px] text-slate-500">Sesuaikan quantity atau hapus/tambah barang yang salah input</span>
                        </div>
                        <span class="text-[10px] font-bold px-2 py-0.5 bg-blue-100 text-blue-800 rounded">
                            Stok Otomatis Disesuaikan
                        </span>
                    </div>

                    <!-- Tabel Item Transaksi -->
                    <div class="overflow-x-auto bg-white rounded-xl border border-slate-200">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-100 text-slate-600 text-[10px] uppercase font-bold border-b border-slate-200">
                                <tr>
                                    <th class="py-2 px-3">Nama Produk</th>
                                    <th class="py-2 px-3 text-right">Harga</th>
                                    <th class="py-2 px-3 text-center w-28">Jumlah (Qty)</th>
                                    <th class="py-2 px-3 text-right">Subtotal</th>
                                    <th class="py-2 px-2 text-center w-8">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="(item, idx) in editForm.items" :key="idx">
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="py-2.5 px-3">
                                            <span class="font-bold text-slate-800 block" x-text="item.product_name"></span>
                                        </td>
                                        <td class="py-2.5 px-3 text-right font-medium text-slate-600" x-text="'Rp ' + Number(item.selling_price).toLocaleString('id-ID')"></td>
                                        <td class="py-2.5 px-3 text-center">
                                            <div class="inline-flex items-center border border-slate-200 rounded-lg overflow-hidden bg-slate-50">
                                                <button type="button" @click="changeQty(idx, -1)" 
                                                        class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition">
                                                    -
                                                </button>
                                                <input type="number" x-model.number="item.quantity" @input="recalc()" min="1"
                                                       class="w-12 text-center text-xs font-black bg-white py-1 focus:outline-none">
                                                <button type="button" @click="changeQty(idx, 1)" 
                                                        class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition">
                                                    +
                                                </button>
                                            </div>
                                        </td>
                                        <td class="py-2.5 px-3 text-right font-bold text-slate-900" x-text="'Rp ' + Number(item.subtotal).toLocaleString('id-ID')"></td>
                                        <td class="py-2.5 px-2 text-center">
                                            <button type="button" @click="removeItem(idx)" 
                                                    title="Hapus barang (stok otomatis dikembalikan)"
                                                    class="p-1 rounded text-red-500 hover:bg-red-50 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="editForm.items.length === 0">
                                    <td colspan="5" class="py-4 text-center text-red-500 italic">
                                        Minimal harus ada 1 item barang dalam transaksi.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Tambah Produk Baru ke Transaksi -->
                    <div class="flex items-center gap-2 pt-1">
                        <select x-model="selectedNewProductId" class="flex-1 px-3 py-2 rounded-xl border border-slate-200 text-xs font-medium text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-pepsi-blue">
                            <option value="">+ Tambah produk lain ke transaksi ini...</option>
                            <template x-for="p in allProducts" :key="p.id">
                                <option :value="p.id" x-text="p.name + ' (Rp ' + Number(p.selling_price).toLocaleString('id-ID') + ' | Stok: ' + p.stock + ')'"></option>
                            </template>
                        </select>
                        <button type="button" @click="addProduct()" :disabled="!selectedNewProductId"
                                class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:opacity-40 text-white font-bold transition">
                            Tambah
                        </button>
                    </div>

                    <!-- Total Tagihan Baru -->
                    <div class="p-3 rounded-xl bg-blue-50/60 border border-blue-200/80 flex items-center justify-between">
                        <span class="text-xs font-bold text-blue-900">Total Nilai Belanja Baru:</span>
                        <span class="font-heading font-black text-xl text-pepsi-navy" x-text="'Rp ' + Number(editForm.total_amount).toLocaleString('id-ID')"></span>
                    </div>
                </div>

                <!-- BAGIAN 2: SALAH BAYAR / METODE PEMBAYARAN -->
                <div class="space-y-3 bg-slate-50/80 p-4 rounded-2xl border border-slate-200">
                    <span class="text-xs font-bold text-slate-800 block">Koreksi Metode Pembayaran</span>

                    <!-- Pilihan Metode Bayar (Tunai vs QRIS) -->
                    <div class="grid grid-cols-2 gap-3 font-bold text-xs">
                        <label :class="editForm.payment_method === 'cash' ? 'border-emerald-600 bg-emerald-50 text-emerald-900 ring-2 ring-emerald-500' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                               class="border rounded-2xl p-3 flex items-center justify-center gap-2 cursor-pointer transition select-none">
                            <input type="radio" name="payment_method" value="cash" x-model="editForm.payment_method" @change="recalc()" class="sr-only">
                            <span class="text-base">💵</span>
                            <span>Tunai (Cash)</span>
                        </label>
                        <label :class="editForm.payment_method === 'qris' ? 'border-purple-600 bg-purple-50 text-purple-900 ring-2 ring-purple-500' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                               class="border rounded-2xl p-3 flex items-center justify-center gap-2 cursor-pointer transition select-none">
                            <input type="radio" name="payment_method" value="qris" x-model="editForm.payment_method" @change="recalc()" class="sr-only">
                            <span class="text-base">📱</span>
                            <span>QRIS</span>
                        </label>
                    </div>

                    <!-- Input Khusus Tunai -->
                    <div x-show="editForm.payment_method === 'cash'" class="space-y-2 bg-emerald-50/70 p-3 rounded-xl border border-emerald-100">
                        <div class="flex items-center justify-between gap-3">
                            <label class="font-bold text-emerald-900 text-xs">Uang Diterima dari Pelanggan (Rp):</label>
                            <input type="number" name="cash_paid" x-model.number="editForm.cash_paid" :min="editForm.total_amount" step="500" required
                                   class="w-40 px-3 py-1.5 rounded-lg border border-emerald-200 text-xs font-black text-slate-900 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 text-right">
                        </div>
                        <div class="flex justify-between items-center text-xs font-bold text-emerald-800 pt-1 border-t border-emerald-100">
                            <span>Uang Kembalian (Dihitung Otomatis):</span>
                            <span class="font-heading font-black text-sm" x-text="'Rp ' + Number(calculatedChange).toLocaleString('id-ID')"></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                        <!-- Input Nama Pelanggan -->
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Pelanggan</label>
                            <input type="text" name="customer_name" x-model="editForm.customer_name" placeholder="Umum / Siswa"
                                   class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-900 bg-white focus:outline-none focus:ring-2 focus:ring-pepsi-blue">
                        </div>

                        <!-- Catatan Penyesuaian -->
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Alasan Penyesuaian *</label>
                            <input type="text" name="adjustment_note" x-model="editForm.adjustment_note" required
                                   placeholder="Contoh: Salah jumlah qty barang, bayar QRIS"
                                   class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-pepsi-blue">
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="editModal = false" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition">
                        Batal
                    </button>
                    <button type="submit" :disabled="editForm.items.length === 0"
                            class="px-5 py-2.5 rounded-xl bg-cyan-700 hover:bg-cyan-800 disabled:opacity-50 text-white font-bold transition shadow-md">
                        Simpan Penyesuaian Transaksi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL BATALKAN / VOID TRANSAKSI -->
    <!-- ============================================================== -->
    <div x-show="voidModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4"
         style="display: none;">
        
        <div @click.away="voidModal = false" class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl border border-slate-100 space-y-5">
            <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                <span class="w-10 h-10 rounded-2xl bg-red-100 text-red-600 grid place-items-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </span>
                <div>
                    <h3 class="font-heading font-black text-lg text-slate-900">Batalkan / Void Transaksi</h3>
                    <p class="text-xs text-red-600 font-bold" x-text="voidForm.invoice_no"></p>
                </div>
            </div>

            <form method="POST" :action="'<?= url('admin/canteen/transactions/delete/') ?>' + voidForm.id" class="space-y-4 text-xs">
                <input type="hidden" name="redirect_qs" value="<?= sanitize($currentQs) ?>">

                <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 space-y-2">
                    <div class="flex items-start gap-2 text-amber-900 font-bold">
                        <span>⚠️</span>
                        <span>Konsekuensi Pembatalan Transaksi:</span>
                    </div>
                    <ul class="text-[11px] text-amber-800 list-disc list-inside space-y-1">
                        <li>Semua barang pada invoice ini akan <b>otomatis dikembalikan ke stok fisik</b> gudang / display.</li>
                        <li>Riwayat mutasi pengembalian stok dicatat otomatis di log FIFO sistem.</li>
                        <li>Nominal belanja <b x-text="'Rp ' + Number(voidForm.total_amount).toLocaleString('id-ID')"></b> akan ditiadakan dari laporan omset kasir.</li>
                    </ul>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Alasan Pembatalan / Void *</label>
                    <textarea name="void_reason" rows="2" required placeholder="Contoh: Salah pilih item / salah kasir bayar / pelanggan membatalkan pesanan"
                              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="voidModal = false" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold transition shadow-md shadow-red-600/30">
                        Ya, Batalkan &amp; Kembalikan Stok
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
