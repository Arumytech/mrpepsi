<?php
// Admin Permissions Matrix View
$roleTitles = [
    'admin'   => 'Administrator',
    'cashier' => 'Kasir Pepsi Food & Drink',
];
?>
<div x-data="{ selectAll: { admin: false, cashier: false } }" class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-heading font-black text-2xl sm:text-3xl text-pepsi-navy">Hak Akses &amp; Menu (Role)</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Matrik checkbox: tentukan menu mana saja yang boleh dibuka tiap role. Owner selalu punya akses penuh.
            </p>
        </div>
    </div>

    <!-- Info Banner -->
    <div class="p-4 bg-violet-50 border border-violet-200 rounded-3xl flex items-start gap-3 text-xs text-violet-900">
        <div class="w-8 h-8 rounded-xl bg-violet-600 text-white flex items-center justify-center font-bold text-sm flex-shrink-0">🔒</div>
        <div>
            <p class="font-bold text-sm text-violet-800">Owner = akses penuh, tidak bisa dibatasi</p>
            <p class="text-slate-600 mt-0.5">
                Role <b>Owner</b> selalu bisa membuka seluruh menu (kolom terkunci). Matrik ini dikelola oleh <b>Owner</b> &amp; <b>Administrator</b>.
                Perubahan langsung berlaku.
            </p>
        </div>
    </div>

    <form action="<?= url('admin/permissions/save') ?>" method="POST">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <!-- Table Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <h3 class="font-heading font-bold text-base text-slate-900">Matrik Checkbox Menu per Role</h3>
                <div class="flex items-center gap-2">
                    <span class="text-[11px] text-slate-400 font-semibold hidden sm:inline">Pilih semua:</span>
                    <button type="button" @click="selectAll.admin = true; document.querySelectorAll('.col-admin').forEach(c => c.checked = true)" class="text-[11px] font-bold px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700">
                        Admin
                    </button>
                    <button type="button" @click="selectAll.cashier = true; document.querySelectorAll('.col-cashier').forEach(c => c.checked = true)" class="text-[11px] font-bold px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700">
                        Kasir
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs font-medium">
                    <thead class="bg-pepsi-navy text-white text-[11px] uppercase tracking-wider font-bold">
                        <tr>
                            <th class="px-5 py-4 w-1/3">Menu / Halaman</th>
                            <th class="px-4 py-4 text-center"><?= $roleTitles['admin'] ?></th>
                            <th class="px-4 py-4 text-center"><?= $roleTitles['cashier'] ?></th>
                            <th class="px-4 py-4 text-center">Owner</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($groups as $group => $items): ?>
                        <tr>
                            <td colspan="4" class="px-5 py-2.5 bg-slate-50">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500"><?= sanitize($group) ?></span>
                            </td>
                        </tr>
                        <?php foreach ($items as $item): $key = $item['key']; ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-5 py-3.5">
                                <span class="font-bold text-slate-900"><?= sanitize($item['label']) ?></span>
                                <span class="block text-[10px] text-slate-400 font-mono"><?= url($item['url']) ?></span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <input type="checkbox" name="perm[admin][<?= $key ?>]" value="1"
                                       class="col-admin w-4.5 h-4.5 accent-pepsi-blue cursor-pointer"
                                       <?= in_array($key, $per['admin'], true) ? 'checked' : '' ?>>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <input type="checkbox" name="perm[cashier][<?= $key ?>]" value="1"
                                       class="col-cashier w-4.5 h-4.5 accent-emerald-600 cursor-pointer"
                                       <?= in_array($key, $per['cashier'], true) ? 'checked' : '' ?>>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-md bg-violet-100 text-violet-600">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between gap-3 bg-slate-50/60">
                <p class="text-[11px] text-slate-500">
                    Centang menu yang boleh diakses role tsb. Kosongkan untuk memblokir menu.
                </p>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-pepsi-blue hover:bg-blue-900 text-white font-bold text-xs shadow-md transition">
                    Simpan Hak Akses
                </button>
            </div>
        </div>
    </form>
</div>