<?php
// Admin User & Account Management View
$roleLabel = [
    'admin'   => 'Administrator',
    'cashier' => 'Kasir Pepsi Food & Drink',
];
?>
<div x-data="{ addModal: false, editModal: false, editItem: {}, permModal: false, permItem: { id: 0, name: '', role: 'admin', configured: false, allowed: {} } }" class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-heading font-black text-2xl sm:text-3xl text-pepsi-navy">Kelola User &amp; Akun</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Tambah, ubah, dan atur hak akses akun yang bisa login ke sistem (Administrator &amp; Kasir).
            </p>
        </div>
        <button @click="addModal = true" class="bg-pepsi-blue hover:bg-blue-900 text-white font-bold text-xs sm:text-sm px-5 py-2.5 rounded-2xl shadow-md flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Tambah User Baru</span>
        </button>
    </div>

    <!-- Stat Cards -->
<div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Akun Owner</p>
                    <p class="text-xl sm:text-2xl font-black text-slate-900"><?= number_format($ownerCount ?? 0) ?></p>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-blue-50 text-pepsi-blue flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Akun Administrator</p>
                <p class="text-xl sm:text-2xl font-black text-slate-900"><?= number_format($adminCount) ?></p>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Akun Kasir</p>
                <p class="text-xl sm:text-2xl font-black text-slate-900"><?= number_format($cashierCount) ?></p>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-medium">
                <thead class="bg-pepsi-navy text-white text-[11px] uppercase tracking-wider font-bold">
                    <tr>
                        <th class="px-5 py-4">Nama</th>
                        <th class="px-5 py-4">Username</th>
                        <th class="px-5 py-4">Hak Akses / Role</th>
                        <th class="px-5 py-4">Dibuat</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($users)): ?>
                        <tr><td colspan="5" class="p-8 text-center text-slate-400 italic">Belum ada user.</td></tr>
                    <?php else: ?>
                    <?php foreach ($users as $u): $isSelf = (int)$u['id'] === $currentUserId; ?>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full <?= $u['role'] === 'admin' ? 'bg-pepsi-blue' : 'bg-emerald-500' ?> text-white flex items-center justify-center font-bold text-xs">
                                    <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <span class="font-bold text-slate-900 text-sm block"><?= sanitize($u['name']) ?></span>
                                    <?php $isOnline = !empty($u['online']); ?>
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold <?= $isOnline ? 'text-emerald-600' : 'text-slate-400' ?>">
                                        <span class="w-1.5 h-1.5 rounded-full <?= $isOnline ? 'bg-emerald-500' : 'bg-slate-300' ?>"></span>
                                        <?= $isOnline ? 'Online' : 'Offline' ?>
                                    </span>
                                    <?php if ($isSelf): ?>
                                        <span class="text-[10px] font-bold text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded">Akun Anda</span>
                                    <?php endif; ?>
                                    <?php if (!empty($userPermConfigured[$u['id']])): ?>
                                        <span class="text-[10px] font-bold text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded ml-1">Hak akses khusus</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="font-mono text-slate-700 font-bold"><?= sanitize($u['username']) ?></span>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <?php if ($u['role'] === 'owner'): ?>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-violet-100 text-violet-700">Owner</span>
                            <?php elseif ($u['role'] === 'admin'): ?>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-100 text-pepsi-blue">Administrator</span>
                            <?php else: ?>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">Kasir Pepsi Food &amp; Drink</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-slate-500">
                            <?= !empty($u['created_at']) ? tanggalIndo($u['created_at'], 'd M Y') : '-' ?>
                        </td>
                        <td class="px-5 py-4 text-right whitespace-nowrap space-x-1">
                            <?php if ($u['role'] === 'owner' && ($_SESSION['user']['role'] ?? '') !== 'owner'): ?>
                                <span class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-400 font-bold text-[11px]">Khusus Owner</span>
                            <?php else: ?>
                            <button @click='permItem = <?= htmlspecialchars(json_encode([
                                "id" => (int)$u["id"],
                                "name" => $u["name"],
                                "role" => $u["role"],
                                "configured" => !empty($userPermConfigured[$u["id"]]),
                                "allowed" => (object)($userPerms[$u["id"]] ?? []),
                            ]), ENT_QUOTES) ?>; permModal = true;' class="px-3 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold transition">
                                Hak Akses
                            </button>
                            <button @click='editItem = <?= htmlspecialchars(json_encode([
                                "id" => (int)$u["id"],
                                "name" => $u["name"],
                                "username" => $u["username"],
                                "role" => $u["role"],
                            ]), ENT_QUOTES) ?>; editModal = true;' class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition">
                                Edit
                            </button>
                            <?php if (!$isSelf && (!empty($u['online']) || !empty($u['session_token']) || (int)($u['log_login'] ?? 0) === 1)): ?>
                            <form action="<?= url('admin/sessions/release/' . $u['id']) ?>" method="POST" class="inline" onsubmit="return confirm('Release sesi user <?= addslashes($u['name']) ?>? User akan langsung bisa login dari perangkat mana pun.')">
                                <button type="submit" class="px-2.5 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-700 font-bold transition text-xs" title="Release sesi akun ini jika terkunci">
                                    Release Sesi
                                </button>
                            </form>
                            <?php endif; ?>
                            <?php if (!$isSelf): ?>
                            <form action="<?= url('admin/users/delete/' . $u['id']) ?>" method="POST" class="inline" onsubmit="return confirm('Hapus user <?= addslashes($u['name']) ?>?')">
                                <button type="submit" class="px-3 py-1.5 rounded-xl bg-red-50 hover:bg-red-100 text-pepsi-red font-bold transition">
                                    Hapus
                                </button>
                            </form>
                            <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL TAMBAH USER -->
    <div x-show="addModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="addModal = false" class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-heading font-black text-xl text-pepsi-navy">Tambah User Baru</h3>
                <button @click="addModal = false" class="text-slate-400 hover:text-slate-700 text-lg">&times;</button>
            </div>

            <form action="<?= url('admin/users/store') ?>" method="POST" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Lengkap *</label>
                    <input type="text" name="name" required placeholder="Contoh: Administrator / Kasir Pepsi Food & Drink" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Username *</label>
                    <input type="text" name="username" required placeholder="Contoh: admin2 (tanpa spasi)" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-mono">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Password *</label>
                        <input type="text" name="password" required minlength="5" placeholder="Minimal 5 karakter" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-mono">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Hak Akses / Role *</label>
                        <select name="role" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                            <option value="admin">Administrator</option>
                            <option value="cashier">Kasir Pepsi Food &amp; Drink</option>
                            <?php if (($_SESSION['user']['role'] ?? '') === 'owner'): ?>
                            <option value="owner">Owner (Super Admin)</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <p class="text-[11px] text-amber-700 bg-amber-50 border border-amber-100 rounded-lg p-2">
                    Catat password ini. Password disimpan terenkripsi dan tidak bisa dilihat kembali.
                </p>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="addModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-pepsi-blue text-white font-bold">Simpan User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT USER -->
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="editModal = false" class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-heading font-black text-xl text-pepsi-navy">Edit User</h3>
                <button @click="editModal = false" class="text-slate-400 hover:text-slate-700 text-lg">&times;</button>
            </div>

            <form :action="'<?= url('admin/users/update/') ?>' + editItem.id" method="POST" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Lengkap *</label>
                    <input type="text" name="name" x-model="editItem.name" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Username *</label>
                    <input type="text" name="username" x-model="editItem.username" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Hak Akses / Role *</label>
                    <select name="role" x-model="editItem.role" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                        <option value="admin">Administrator</option>
                        <option value="cashier">Kasir Pepsi Food &amp; Drink</option>
                        <?php if (($_SESSION['user']['role'] ?? '') === 'owner'): ?>
                        <option value="owner">Owner (Super Admin)</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Password Baru (opsional)</label>
                    <input type="text" name="password" minlength="5" placeholder="Biarkan kosong jika tidak diubah" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-mono">
                    <p class="text-[11px] text-slate-400 mt-1">Isi hanya jika ingin mengganti password user ini.</p>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="editModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-pepsi-blue text-white font-bold">Perbarui User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL HAK AKSES PER USER -->
    <div x-show="permModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="permModal = false" class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="font-heading font-black text-xl text-pepsi-navy">Hak Akses Menu</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Atur menu yang bisa diakses oleh <b x-text="permItem.name"></b>.</p>
                </div>
                <button @click="permModal = false" class="text-slate-400 hover:text-slate-700 text-lg">&times;</button>
            </div>

            <p class="text-[11px] text-slate-500 bg-slate-50 border border-slate-100 rounded-xl p-2.5">
                Centang menu yang boleh dibuka user ini. Bila tidak ada perubahan disimpan, user memakai hak akses default role
                (<span class="font-bold" x-text="permItem.role === 'cashier' ? 'Kasir: POS saja' : 'Administrator: semua menu'"></span>).
            </p>

            <form :action="'<?= url('admin/users/permissions/') ?>' + permItem.id" method="POST" class="space-y-4">
                <div class="max-h-[50vh] overflow-y-auto space-y-4 pr-1">
                    <?php foreach ($permGroups as $group => $items): ?>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1.5"><?= sanitize($group) ?></p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <?php foreach ($items as $key => $m): ?>
                            <label class="flex items-start gap-2 p-2 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer">
                                <input type="checkbox" :name="'perm[<?= $key ?>]'" value="1" x-model="permItem.allowed['<?= $key ?>']" class="mt-0.5 rounded border-slate-300 text-pepsi-blue focus:ring-pepsi-blue">
                                <span class="text-xs font-semibold text-slate-700"><?= sanitize($m['label']) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                    <button type="button" @click="if (confirm('Kembalikan hak akses user ini ke default role?')) { document.getElementById('resetPermForm').submit(); }" class="px-4 py-2 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-700 font-bold text-xs">Kembalikan ke Default Role</button>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="permModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs">Batal</button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-pepsi-blue text-white font-bold text-xs">Simpan Hak Akses</button>
                    </div>
                </div>
            </form>

            <form id="resetPermForm" :action="'<?= url('admin/users/permissions/') ?>' + permItem.id + '/reset'" method="POST" class="hidden"></form>
        </div>
    </div>

</div>
