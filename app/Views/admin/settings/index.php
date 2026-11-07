<?php
// Admin General Settings View
?>
<div class="space-y-6">

    <div>
        <h1 class="font-heading font-black text-2xl sm:text-3xl text-pepsi-navy">Pengaturan Umum</h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-1">
            Konfigurasi nomor WhatsApp admin untuk registrasi otomatis, alamat lembaga, aturan kelas, dan periode aktif.
        </p>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 sm:p-8 max-w-3xl">
        <form action="<?= url('admin/settings/update') ?>" method="POST" enctype="multipart/form-data" class="space-y-5 text-xs">

            <!-- Upload Logo Lembaga -->
            <div class="border border-dashed border-slate-300 bg-slate-50/70 rounded-2xl p-4 sm:p-5 space-y-3">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <label class="block font-bold text-slate-700">Logo Lembaga / Brand</label>
                        <span class="text-[11px] text-slate-500">Format PNG, JPG, WEBP, GIF, atau SVG. Maksimal 3 MB.</span>
                    </div>
                    <?php if (!empty($settings['logo_path'])): ?>
                    <label class="flex items-center gap-1.5 text-[11px] font-semibold text-red-600 cursor-pointer select-none bg-white border border-red-200 rounded-lg px-2.5 py-1.5">
                        <input type="checkbox" name="remove_logo" value="1" class="w-3.5 h-3.5 rounded accent-red-600">
                        <span>Hapus logo</span>
                    </label>
                    <?php endif; ?>
                </div>

                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                    <div id="logo-preview-box" class="w-24 h-24 shrink-0 rounded-2xl bg-white border border-slate-200 grid place-items-center overflow-hidden">
                        <?php if ($lg = logo_url()): ?>
                        <img src="<?= sanitize($lg) ?>" alt="Logo saat ini" class="w-full h-full object-contain p-2">
                        <?php else: ?>
                        <span id="logo-placeholder" class="text-[10px] text-slate-400 text-center px-2">Belum ada logo</span>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 w-full space-y-2">
                        <input type="file" name="logo" id="logo-input" accept="image/png,image/jpeg,image/webp,image/gif,image/svg+xml"
                               class="block w-full text-[11px] text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-pepsi-blue file:text-white file:font-bold file:text-[11px] hover:file:bg-blue-900 file:cursor-pointer bg-white border border-slate-200 rounded-xl p-2">
                        <p class="text-[10px] text-slate-400">Logo tampil di header website publik, sidebar admin, halaman login, POS kasir, dan favicon.</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Lembaga *</label>
                    <input type="text" name="app_name" value="<?= sanitize($settings['app_name'] ?? APP_NAME) ?>" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nomor WhatsApp Admin (Untuk Pendaftaran) *</label>
                    <input type="text" name="admin_wa" value="<?= sanitize($settings['admin_wa'] ?? ADMIN_PHONE) ?>" required placeholder="6285648077008" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold font-mono text-pepsi-blue">
                    <span class="text-[10px] text-slate-400">Gunakan format angka internasional (contoh: 6285648077008)</span>
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Tagline / Slogan Lembaga</label>
                <input type="text" name="tagline" value="<?= sanitize($settings['tagline'] ?? APP_TAGLINE) ?>" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Pilih Periode Aktif Pendaftaran Utama</label>
                <select name="active_period_id" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold text-pepsi-blue">
                    <?php foreach ($periods as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $p['is_active'] ? 'selected' : '' ?>>
                        <?= sanitize($p['name']) ?> (<?= sanitize(tanggalIndo($p['start_date'])) ?> - <?= sanitize(tanggalIndo($p['end_date'])) ?>) <?= $p['is_active'] ? ' - [Aktif]' : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Akun Instagram</label>
                    <input type="text" name="instagram" value="<?= sanitize($settings['instagram'] ?? '@mrpepsiofficial01') ?>" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Email Resmi</label>
                    <input type="email" name="email" value="<?= sanitize($settings['email'] ?? 'mrpepsikampunginggris@gmail.com') ?>" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Alamat Kampus Pare, Kediri</label>
                <textarea name="address" rows="2" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50"><?= sanitize($settings['address'] ?? '') ?></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">SK Diknas</label>
                    <input type="text" name="sk_diknas" value="<?= sanitize($settings['sk_diknas'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">NPSN</label>
                    <input type="text" name="npsn" value="<?= sanitize($settings['npsn'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-mono">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Jam Operasional Kantor</label>
                <textarea name="office_hours" rows="3" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-mono"><?= sanitize($settings['office_hours'] ?? '') ?></textarea>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Tata Tertib & Aturan Kelas Siswa</label>
                <textarea name="class_rules" rows="4" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50"><?= sanitize($settings['class_rules'] ?? '') ?></textarea>
            </div>

            <!-- Format Nomor Sertifikat -->
            <div class="border-t border-slate-100 pt-5 space-y-4">
                <div>
                    <h3 class="font-bold text-slate-800 text-sm">Format Nomor Sertifikat</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">
                        Atur pola penulisan nomor sertifikat kelulusan. Nomor di-generate otomatis saat menerbitkan sertifikat.
                    </p>
                </div>

                <?php
                    $seqStart   = (int)($settings['certificate_seq_start'] ?? 1);
                    if ($seqStart < 1) $seqStart = 1;
                    $seqCounter = $settings['certificate_seq_counter'] ?? '';
                    $seqNext    = ($seqCounter === '' ? $seqStart : ((int)$seqCounter + 1));
                ?>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-3">
                        <label class="block font-bold text-slate-700 mb-1">Pola / Format Nomor *</label>
                        <input type="text" name="certificate_format" id="cert-format"
                               value="<?= sanitize($settings['certificate_format'] ?? '{seq}/MPU/C.{code}/{rm}/{Y}') ?>"
                               required placeholder="{seq}/MPU/C.{code}/{rm}/{Y}"
                               class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-mono font-bold text-pepsi-blue">
                        <span class="text-[10px] text-slate-400">
                            Token: <code class="font-bold text-slate-600">{Y}</code> tahun 4 digit,
                            <code class="font-bold text-slate-600">{y}</code> tahun 2 digit,
                            <code class="font-bold text-slate-600">{m}</code> bulan,
                            <code class="font-bold text-slate-600">{d}</code> hari,
                            <code class="font-bold text-slate-600">{rm}</code> bulan Romawi,
                            <code class="font-bold text-slate-600">{code}</code> kode program,
                            <code class="font-bold text-slate-600">{seq}</code> nomor urut.
                            Bisa juga <code class="font-bold text-slate-600">{seq:5}</code> untuk 5 digit.
                        </span>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Jumlah Digit Nomor Urut</label>
                        <input type="number" name="certificate_seq_digits" id="cert-digits" min="1" max="10"
                               value="<?= sanitize($settings['certificate_seq_digits'] ?? '5') ?>"
                               class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                        <span class="text-[10px] text-slate-400">Contoh 5 digit → 00001</span>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nomor Urut Awal</label>
                        <input type="number" name="certificate_seq_start" min="1"
                               value="<?= (int)$seqStart ?>"
                               class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                        <span class="text-[10px] text-slate-400">Mulai dari nomor ini (mis. 16745).</span>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nomor Urut Berikutnya</label>
                        <input type="text" readonly value="<?= (int)$seqNext ?>"
                               class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-100 font-bold text-slate-500">
                        <span class="text-[10px] text-slate-400">Otomatis bertambah tiap sertifikat terbit.</span>
                    </div>
                </div>

                <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200 flex items-center gap-3">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Pratinjau:</span>
                    <span id="cert-preview" class="font-mono font-black text-sm text-pepsi-blue">00001/MPU/C.GFS/X/2026</span>
                </div>
            </div>

            <!-- Pengaturan Kasir / POS -->
            <div class="border-t border-slate-100 pt-5 space-y-4">
                <div>
                    <h3 class="font-bold text-slate-800 text-sm">Cetak Struk Kasir (POS)</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">
                        Atur perilaku cetak struk/nota untuk transaksi makanan, minuman, dan barang Pepsi Food &amp; Drink.
                    </p>
                </div>
                <?php $posMode = $settings['pos_receipt_mode'] ?? 'ask'; ?>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Mode Cetak Struk</label>
                    <select name="pos_receipt_mode" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold text-pepsi-blue">
                        <option value="ask" <?= $posMode === 'ask' ? 'selected' : '' ?>>Tanya dulu — tampilkan pratinjau struk + tombol cetak</option>
                        <option value="always" <?= $posMode === 'always' ? 'selected' : '' ?>>Selalu cetak otomatis — buka jendela cetak tiap transaksi</option>
                        <option value="never" <?= $posMode === 'never' ? 'selected' : '' ?>>Tidak perlu cetak nota — tanpa struk</option>
                    </select>
                    <span class="text-[10px] text-slate-400">Mode "Tanya dulu" adalah bawaan. Semua transaksi tetap tersimpan di database apa pun modenya.</span>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end">
                <button type="submit" class="px-6 py-3 bg-pepsi-blue hover:bg-blue-900 text-white font-bold rounded-2xl shadow-md transition">
                    Simpan Perubahan Pengaturan
                </button>
            </div>
        </form>
    </div>

    <!-- Kontrol Operasional POS -->
    <?php
        $posClosed = trim($settings['pos_closed_date'] ?? '') === date('Y-m-d');
        $posClosedLabel = trim($settings['pos_closed_date'] ?? '') !== ''
            ? date('d M Y', strtotime($settings['pos_closed_date']))
            : '-';
    ?>
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 sm:p-8 max-w-3xl space-y-5">
        <div>
            <h2 class="font-heading font-black text-lg text-pepsi-navy">Kontrol Operasional POS</h2>
            <p class="text-xs text-slate-500 mt-1">
                Buka atau tutup mesin kasir POS Pepsi Food &amp; Drink secara manual. Saat ditutup,
                POS tidak dapat digunakan sampai dibuka kembali atau otomatis terbuka esok hari.
            </p>
        </div>

        <div class="p-4 rounded-2xl border flex items-center justify-between gap-4 <?= $posClosed ? 'bg-red-50 border-red-200' : 'bg-green-50 border-green-200' ?>">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-2xl grid place-items-center text-lg <?= $posClosed ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-700' ?>">
                    <?= $posClosed ? '🔒' : '🟢' ?>
                </span>
                <div>
                    <span class="block text-sm font-black <?= $posClosed ? 'text-red-700' : 'text-green-800' ?>">
                        POS <?= $posClosed ? 'TERTUTUP' : 'TERBUKA' ?> Hari Ini
                    </span>
                    <span class="block text-[11px] <?= $posClosed ? 'text-red-600' : 'text-green-700' ?>">
                        <?= $posClosed
                            ? 'Ditutup pada ' . sanitize($posClosedLabel) . ' — buka otomatis besok.'
                            : 'Kasir dapat melakukan transaksi sekarang.' ?>
                    </span>
                </div>
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
    </div>

</div>

<script>
(function () {
    var input = document.getElementById('logo-input');
    var box = document.getElementById('logo-preview-box');
    if (!input || !box) return;
    var currentUrl = '<?= $lg ?? '' ?>';

    input.addEventListener('change', function () {
        var file = this.files && this.files[0];
        if (!file) return;

        if (file.size > 3 * 1024 * 1024) {
            alert('Ukuran file maksimal 3 MB.');
            this.value = '';
            return;
        }
        if (file.type.indexOf('image/') !== 0) {
            alert('File harus berupa gambar (PNG, JPG, WEBP, GIF, atau SVG).');
            this.value = '';
            return;
        }

        box.innerHTML = '';
        var img = document.createElement('img');
        img.className = 'w-full h-full object-contain p-2';
        img.alt = 'Pratinjau logo';
        img.src = URL.createObjectURL(file);
        box.appendChild(img);
    });
})();

(function () {
    var fmt = document.getElementById('cert-format');
    var digits = document.getElementById('cert-digits');
    var out = document.getElementById('cert-preview');
    if (!fmt || !out) return;

    function pad(n, len) {
        n = String(n);
        while (n.length < len) n = '0' + n;
        return n;
    }

    function render() {
        var now = new Date();
        var Y = String(now.getFullYear());
        var y = Y.slice(-2);
        var m = pad(now.getMonth() + 1, 2);
        var d = pad(now.getDate(), 2);
        var roman = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][now.getMonth() + 1] || '';
        var dg = parseInt(digits && digits.value ? digits.value : '5', 10);
        if (isNaN(dg) || dg < 1) dg = 5;

        var tpl = fmt.value || '{seq}/MPU/C.{code}/{rm}/{Y}';
        var hasSeq = /\{seq(?::\d+)?\}/i.test(tpl);
        if (!hasSeq) tpl += '{seq}';

        var seq = pad(<?= (int)$seqNext ?>, dg);
        tpl = tpl.replace(/\{seq:(\d+)\}/i, function (_, n) { return pad(<?= (int)$seqNext ?>, parseInt(n, 10)); });
        tpl = tpl.replace(/\{seq\}/i, seq);
        tpl = tpl.replace(/\{Y\}/g, Y).replace(/\{y\}/g, y).replace(/\{m\}/g, m).replace(/\{d\}/g, d);
        tpl = tpl.replace(/\{rm\}/g, roman).replace(/\{code\}/g, 'GFS');
        out.textContent = tpl;
    }

    fmt.addEventListener('input', render);
    if (digits) digits.addEventListener('input', render);
    render();
})();
</script>
