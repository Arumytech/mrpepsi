<?php
// Login View
?>
<div class="bg-white rounded-3xl p-8 shadow-2xl border border-slate-200">
    <div class="text-center mb-6">
        <a href="<?= url('/') ?>" class="inline-flex items-center gap-3 justify-center mb-3">
            <?php if (logo_img()): ?>
                <span class="w-14 h-14 rounded-2xl bg-white border border-slate-200 shadow-md grid place-items-center overflow-hidden p-1.5">
                    <?= logo_img('max-w-full max-h-full object-contain') ?>
                </span>
            <?php else: ?>
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-pepsi-blue to-pepsi-red flex items-center justify-center text-white font-heading font-black text-xl shadow-md">
                    MP
                </div>
            <?php endif; ?>
        </a>
        <h1 class="font-heading font-black text-2xl text-pepsi-navy">Masuk Portal Staf</h1>
        <p class="text-xs text-slate-500 mt-1">Manajemen MR PEPSI UPGRADE EC</p>
    </div>

    <?php if ($flashError = $this->getFlash('error')): ?>
        <div class="mb-5 p-3.5 bg-red-50 border border-red-200 text-pepsi-red text-xs font-semibold rounded-2xl flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
            <span><?= $flashError ?></span>
        </div>
    <?php endif; ?>

    <?php if ($flashSuccess = $this->getFlash('success')): ?>
        <div class="mb-5 p-3.5 bg-green-50 border border-green-200 text-green-700 text-xs font-semibold rounded-2xl flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <span><?= $flashSuccess ?></span>
        </div>
    <?php endif; ?>

    <?php 
        $lockedUser = $_SESSION['locked_account'] ?? null; 
        $defaultUser = $lockedUser['username'] ?? '';
    ?>

    <?php if ($lockedUser): ?>
        <div class="mb-5 p-4 bg-amber-50 border border-amber-200 text-amber-900 text-xs rounded-2xl space-y-2">
            <div class="flex items-center gap-2 font-bold text-amber-800">
                <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>Akun "<?= sanitize($lockedUser['name']) ?>" Masih Tersimpan Aktif</span>
            </div>
            <p class="text-slate-600 leading-relaxed text-[11px]">
                Jika jendela browser sebelumnya tertutup tanpa logout, centang opsi <b>"Reset Sesi Lama"</b> di bawah, lalu masukkan password akun Anda untuk langsung masuk.
            </p>
        </div>
    <?php endif; ?>

    <form action="<?= url('login') ?>" method="POST" class="space-y-4">
        <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">Username</label>
            <input type="text" name="username" required autocomplete="username" placeholder="Masukkan username" value="<?= sanitize($defaultUser) ?>"
                class="w-full text-sm font-medium rounded-xl border-slate-200 bg-slate-50 px-4 py-3 focus:bg-white focus:ring-2 focus:ring-pepsi-blue focus:outline-none transition">
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">Password</label>
            <input type="password" name="password" required autocomplete="current-password" placeholder="Masukkan password"
                class="w-full text-sm font-medium rounded-xl border-slate-200 bg-slate-50 px-4 py-3 focus:bg-white focus:ring-2 focus:ring-pepsi-blue focus:outline-none transition">
        </div>

        <div class="pt-1">
            <label class="flex items-start gap-2.5 cursor-pointer p-2.5 rounded-xl border border-dashed <?= $lockedUser ? 'border-amber-300 bg-amber-50/60' : 'border-slate-200 bg-slate-50/50 hover:bg-slate-50' ?> transition select-none">
                <input type="checkbox" name="force_reset" value="1" <?= $lockedUser ? 'checked' : '' ?>
                    class="mt-0.5 rounded border-slate-300 text-pepsi-blue focus:ring-pepsi-blue">
                <div class="text-[11px] leading-tight">
                    <span class="font-bold text-slate-800 block">Reset sesi lama jika akun terkunci</span>
                    <span class="text-slate-400">Aktifkan jika browser sebelumnya tertutup tanpa klik logout</span>
                </div>
            </label>
        </div>

        <button type="submit"
            class="w-full bg-pepsi-blue hover:bg-blue-900 text-white font-bold text-sm py-3.5 rounded-xl shadow-lg shadow-blue-700/20 transition-all transform hover:-translate-y-0.5">
            Masuk ke Sistem
        </button>
    </form>

    <!-- Quick Demo Accounts 
    <div class="mt-6 pt-5 border-t border-slate-100 text-xs text-slate-500 space-y-2">
        <p class="font-bold text-slate-700 text-center">Akun Bawaan (Default):</p>
        <div class="grid text-[11px]">
            <div class="bg-slate-50 p-2 rounded-xl border border-slate-100 text-center">
                <span class="font-bold text-pepsi-navy block">Admin Utama</span>
                <span class="text-slate-500 font-mono">admin / admin123</span>
            </div>
            <!-- <div class="bg-slate-50 p-2 rounded-xl border border-slate-100 text-center">
                <span class="font-bold text-pepsi-red block">Kasir Pepsi Food &amp; Drink</span>
                <span class="text-slate-500 font-mono">kasir / kasir123</span>
            </div> 
        </div>
        <div class="text-center pt-2">
            <a href="<?= url('/') ?>" class="text-xs font-semibold text-pepsi-blue hover:underline">&larr; Kembali ke Beranda Publik</a>
        </div> -->
    </div>
</div>