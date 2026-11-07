<?php
// Admin - Cetak Barcode Produk Pepsi Food & Drink
$barcodeProducts = array_map(function ($p) {
    return [
        'id' => (int)$p['id'],
        'name' => $p['name'],
        'barcode' => (string)($p['barcode'] ?? ''),
        'category' => $p['category_name'] ?? 'Umum',
        'price' => (float)$p['selling_price'],
    ];
}, $products);
?>
<div x-data="barcodePrinter(<?= htmlspecialchars(json_encode($barcodeProducts), ENT_QUOTES, 'UTF-8') ?>)" class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-heading font-black text-2xl sm:text-3xl text-pepsi-navy">Cetak Barcode Produk</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Pilih produk yang ingin dicetak label barcodenya, atur jumlah salinan tiap produk, lalu cetak. Produk tanpa barcode akan otomatis memakai kode <span class="font-mono font-bold">PRD0001</span>.
            </p>
        </div>
        <a href="<?= url('admin/canteen') ?>" class="bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs px-4 py-2.5 rounded-2xl transition self-start">
            &larr; Kembali ke Master Produk
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Product Selection -->
        <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
            <div class="p-4 border-b border-slate-100 space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="relative flex-1">
                        <input type="text" x-model="search" placeholder="Cari nama atau barcode produk..."
                               class="w-full pl-9 pr-4 py-2.5 rounded-2xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-pepsi-blue focus:outline-none text-xs font-semibold">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <button @click="selectAll()" class="px-3.5 py-2.5 rounded-2xl bg-pepsi-blue hover:bg-blue-900 text-white font-bold text-xs transition">Pilih Semua</button>
                        <button @click="clearAll()" class="px-3.5 py-2.5 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition">Kosongkan</button>
                    </div>
                </div>
                <div class="flex items-center gap-2 overflow-x-auto pb-0.5 text-[11px] font-bold select-none">
                    <button @click="category = 'all'" :class="category === 'all' ? 'bg-pepsi-navy text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-3 py-1.5 rounded-xl transition whitespace-nowrap">Semua Kategori</button>
                    <template x-for="c in categories" :key="c">
                        <button @click="category = c" :class="category === c ? 'bg-pepsi-navy text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-3 py-1.5 rounded-xl transition whitespace-nowrap" x-text="c"></button>
                    </template>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto max-h-[60vh]">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 text-[10px] uppercase tracking-wider font-bold sticky top-0">
                        <tr>
                            <th class="px-4 py-3 w-10"></th>
                            <th class="px-4 py-3">Produk</th>
                            <th class="px-4 py-3">Barcode</th>
                            <th class="px-4 py-3 w-24 text-center">Salinan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="p in filtered" :key="p.id">
                            <tr :class="selected[p.id] ? 'bg-blue-50/50' : ''" class="hover:bg-slate-50 transition">
                                <td class="px-4 py-3">
                                    <input type="checkbox" :checked="!!selected[p.id]" @change="toggle(p.id, $event.target.checked)"
                                           class="w-4 h-4 rounded accent-pepsi-blue cursor-pointer">
                                </td>
                                <td class="px-4 py-3">
                                    <span class="font-bold text-slate-800 block" x-text="p.name"></span>
                                    <span class="text-[10px] text-slate-400" x-text="p.category"></span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="font-mono text-[11px]" :class="p.barcode ? 'text-slate-600' : 'text-amber-600'" x-text="p.barcode || barcodeValue(p)"></span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <input type="number" min="1" x-model.number="selected[p.id]" :disabled="!selected[p.id]"
                                           @click.stop
                                           :class="!selected[p.id] ? 'opacity-40' : ''"
                                           class="w-16 rounded-xl border border-slate-200 p-1.5 bg-slate-50 font-bold text-center disabled:cursor-not-allowed">
                                </td>
                            </tr>
                        </template>
                        <template x-if="filtered.length === 0">
                            <tr><td colspan="4" class="px-4 py-10 text-center text-slate-400 italic text-xs">Tidak ada produk yang cocok.</td></tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Options & Print -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-5 space-y-5 h-fit lg:sticky lg:top-20">
            <h3 class="font-heading font-black text-lg text-pepsi-navy">Pengaturan Label</h3>

            <div class="space-y-2 text-xs font-semibold text-slate-700">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" x-model="showName" class="w-4 h-4 rounded accent-pepsi-blue">
                    <span>Tampilkan nama produk</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" x-model="showPrice" class="w-4 h-4 rounded accent-pepsi-blue">
                    <span>Tampilkan harga jual</span>
                </label>
            </div>

            <div>
                <label class="block font-bold text-slate-700 text-xs mb-2">Ukuran Label</label>
                <div class="grid grid-cols-3 gap-2 text-[11px] font-bold">
                    <button @click="size = 'small'" :class="size === 'small' ? 'bg-pepsi-navy text-white' : 'bg-slate-100 text-slate-600'" class="py-2 rounded-xl transition">Kecil</button>
                    <button @click="size = 'medium'" :class="size === 'medium' ? 'bg-pepsi-navy text-white' : 'bg-slate-100 text-slate-600'" class="py-2 rounded-xl transition">Sedang</button>
                    <button @click="size = 'large'" :class="size === 'large' ? 'bg-pepsi-navy text-white' : 'bg-slate-100 text-slate-600'" class="py-2 rounded-xl transition">Besar</button>
                </div>
            </div>

            <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4 text-xs space-y-1">
                <div class="flex justify-between">
                    <span class="text-slate-500">Produk dipilih:</span>
                    <span class="font-bold text-slate-800" x-text="selectedCount + ' produk'"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Total label dicetak:</span>
                    <span class="font-heading font-black text-pepsi-navy" x-text="totalLabels + ' label'"></span>
                </div>
            </div>

            <button @click="printLabels()" :disabled="selectedCount === 0"
                    :class="selectedCount === 0 ? 'bg-slate-300 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700 text-white shadow-lg shadow-green-600/30'"
                    class="w-full py-3.5 rounded-2xl font-bold text-sm transition-all flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Cetak Barcode</span>
            </button>
            <p class="text-[10px] text-slate-400 text-center">Gunakan ukuran kertas label yang sesuai lalu pilih printer label saat dialog cetak muncul.</p>
        </div>
    </div>
</div>

<!-- Print area (hanya tampil saat mencetak) -->
<div id="barcode-print-area" class="barcode-print-area"></div>

<style>
    .barcode-print-area { display: none; }
    @media print {
        body * { visibility: hidden !important; }
        #barcode-print-area, #barcode-print-area * { visibility: visible !important; }
        #barcode-print-area {
            display: flex !important;
            flex-wrap: wrap;
            align-content: flex-start;
            gap: 4mm;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            background: #fff;
        }
        .barcode-label { break-inside: avoid; page-break-inside: avoid; }
        @page { margin: 6mm; }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
function barcodePrinter(products) {
    return {
        products: products || [],
        selected: {},
        search: '',
        category: 'all',
        showName: true,
        showPrice: false,
        size: 'medium',

        get categories() {
            const set = new Set();
            this.products.forEach(p => set.add(p.category || 'Umum'));
            return Array.from(set).sort();
        },

        get filtered() {
            const q = this.search.trim().toLowerCase();
            return this.products.filter(p => {
                const catOk = this.category === 'all' || (p.category || 'Umum') === this.category;
                if (!catOk) return false;
                if (q === '') return true;
                return p.name.toLowerCase().includes(q) || String(p.barcode).toLowerCase().includes(q) || this.barcodeValue(p).toLowerCase().includes(q);
            });
        },

        get selectedCount() {
            return Object.keys(this.selected).filter(id => Number(this.selected[id]) > 0).length;
        },

        get totalLabels() {
            return Object.keys(this.selected).reduce((sum, id) => {
                const n = parseInt(this.selected[id]) || 0;
                return sum + (n > 0 ? n : 0);
            }, 0);
        },

        toggle(id, checked) {
            if (checked) {
                this.selected[id] = Number(this.selected[id]) > 0 ? Number(this.selected[id]) : 1;
            } else {
                delete this.selected[id];
            }
        },

        selectAll() {
            this.filtered.forEach(p => {
                this.selected[p.id] = Number(this.selected[p.id]) > 0 ? Number(this.selected[p.id]) : 1;
            });
        },

        clearAll() {
            this.selected = {};
        },

        barcodeValue(p) {
            if (p.barcode && String(p.barcode).trim() !== '') return String(p.barcode).trim();
            return 'PRD' + String(p.id).padStart(4, '0');
        },

        formatRp(val) {
            return 'Rp ' + Number(val).toLocaleString('id-ID');
        },

        printLabels() {
            const dims = {
                small: { w: 150, h: 40, fs: 11 },
                medium: { w: 200, h: 52, fs: 13 },
                large: { w: 260, h: 66, fs: 15 }
            }[this.size];

            const list = this.products.filter(p => Number(this.selected[p.id]) > 0);
            if (list.length === 0) { alert('Pilih minimal satu produk untuk dicetak.'); return; }

            const area = document.getElementById('barcode-print-area');
            area.innerHTML = '';

            list.forEach(p => {
                const copies = Math.max(1, parseInt(this.selected[p.id]) || 1);
                for (let i = 0; i < copies; i++) {
                    const label = document.createElement('div');
                    label.className = 'barcode-label';
                    label.style.width = dims.w + 'px';
                    label.style.padding = '4px 6px';
                    label.style.border = '1px dashed #cbd5e1';
                    label.style.boxSizing = 'border-box';
                    label.style.textAlign = 'center';
                    label.style.background = '#fff';

                    if (this.showName) {
                        const nm = document.createElement('div');
                        nm.textContent = p.name;
                        nm.style.fontSize = Math.max(9, dims.fs - 3) + 'px';
                        nm.style.fontWeight = '700';
                        nm.style.lineHeight = '1.1';
                        nm.style.marginBottom = '2px';
                        nm.style.whiteSpace = 'nowrap';
                        nm.style.overflow = 'hidden';
                        nm.style.textOverflow = 'ellipsis';
                        label.appendChild(nm);
                    }

                    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                    label.appendChild(svg);
                    area.appendChild(label);

                    try {
                        JsBarcode(svg, this.barcodeValue(p), {
                            format: 'CODE128',
                            width: 1.6,
                            height: dims.h,
                            fontSize: dims.fs,
                            textMargin: 1,
                            margin: 2,
                            displayValue: true
                        });
                    } catch (e) {
                        label.removeChild(svg);
                        const err = document.createElement('div');
                        err.textContent = 'Barcode tidak valid';
                        err.style.fontSize = '10px';
                        err.style.color = '#b91c1c';
                        label.appendChild(err);
                    }

                    if (this.showPrice) {
                        const pr = document.createElement('div');
                        pr.textContent = this.formatRp(p.price);
                        pr.style.fontSize = Math.max(9, dims.fs - 2) + 'px';
                        pr.style.fontWeight = '800';
                        label.appendChild(pr);
                    }
                }
            });

            window.print();
        }
    };
}
</script>
